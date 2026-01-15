<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use App\Models\AreaParkir;
use App\Models\Tarif;
use App\Models\LogAktivitas;
use App\Services\CashiService; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TransaksiController extends Controller
{

    public function store(Request $request)
    {
        $request->validate([
            'plat_nomor' => 'required|string|max:15',
            'jenis_kendaraan' => 'required|string|exists:tb_tarif,jenis_kendaraan',
            'id_area' => 'required|exists:tb_area_parkir,id_area',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                // Lock area biar gak race condition
                $area = AreaParkir::where('id_area', $request->id_area)->lockForUpdate()->first();

                if ($area->terisi >= $area->kapasitas) {
                    throw new \Exception('Area parkir penuh!', 400);
                }

                // Cek double check-in
                $isParked = Transaksi::where('plat_nomor', $request->plat_nomor)
                    ->where('status', 'masuk')
                    ->exists();

                if ($isParked) {
                    throw new \Exception('Kendaraan sudah parkir (belum checkout).', 400);
                }

                $transaksi = Transaksi::create([
                    'id_user' => $request->user()->id_user, // Petugas yang input
                    'id_area' => $request->id_area,
                    'plat_nomor' => strtoupper($request->plat_nomor),
                    'jenis_kendaraan' => $request->jenis_kendaraan,
                    'waktu_masuk' => Carbon::now(),
                    'status' => 'masuk',
                    'metode_bayar' => 'cash', // Default awal
                    'status_pembayaran' => 'pending' // Default awal
                ]);

                // Update Slot
                $area->increment('terisi');

                // Catat Log
                LogAktivitas::create([
                    'id_user' => $request->user()->id_user,
                    'aktivitas' => "Input Masuk: {$transaksi->plat_nomor} ({$transaksi->jenis_kendaraan})",
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Berhasil Check-in',
                    'data' => $transaksi
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    // --- 2. CHECK OUT (KENDARAAN KELUAR & BAYAR) ---
    public function update(Request $request)
    {
        $request->validate([
            'plat_nomor' => 'required_without:id_transaksi',
            'id_transaksi' => 'required_without:plat_nomor',
            'metode_bayar' => 'required|in:cash,qris' // Validasi tambahan
        ]);

        try {
            // Cari Data Transaksi
            $query = Transaksi::where('status', 'masuk');
            if ($request->id_transaksi) {
                $query->where('id_transaksi', $request->id_transaksi);
            } else {
                $query->where('plat_nomor', $request->plat_nomor);
            }
            $transaksi = $query->first();

            if (!$transaksi) {
                return response()->json(['message' => 'Data parkir tidak ditemukan atau sudah keluar.'], 404);
            }

            // Hitung Durasi & Biaya
            $waktuMasuk = Carbon::parse($transaksi->waktu_masuk);
            $waktuKeluar = Carbon::now();
            $selisihMenit = $waktuMasuk->diffInMinutes($waktuKeluar);

            $durasiJam = ($selisihMenit <= 0) ? 1 : ceil($selisihMenit / 60);

            $tarifMaster = Tarif::where('jenis_kendaraan', $transaksi->jenis_kendaraan)->first();
            $hargaPerJam = $tarifMaster ? $tarifMaster->tarif_per_jam : 2000;
            $biayaTotal = (int) ($durasiJam * $hargaPerJam);

            // Update Data Dasar Transaksi
            $transaksi->waktu_keluar = $waktuKeluar;
            $transaksi->durasi_jam = $durasiJam;
            $transaksi->biaya_total = $biayaTotal;
            $transaksi->status = 'keluar';
            $transaksi->metode_bayar = $request->metode_bayar;

            // --- A. JIKA BAYAR PAKAI QRIS (CASHI) ---
            if ($request->metode_bayar === 'qris') {
                $cashi = new CashiService();
                $orderId = 'PARK-' . $transaksi->id_transaksi . '-' . time();

                // Generate QR ke API Cashi
                $result = $cashi->createOrder($orderId, $biayaTotal);

                if ($result['success']) {
                    $dataCashi = $result['data'];

                    // Simpan data QRIS ke DB
                    $transaksi->status_pembayaran = 'pending'; // Belum lunas
                    $transaksi->external_id = $orderId;
                    $transaksi->biaya_total = $dataCashi['amount']; // Update nominal unik (misal 5023)
                    $transaksi->qris_content = $dataCashi['qrUrl']; // Simpan gambar QR
                    $transaksi->save();

                    // Return Data QR ke Frontend
                    return response()->json([
                        'success' => true,
                        'is_qris' => true,
                        'qr_image' => $dataCashi['qrUrl'],
                        'nominal' => $dataCashi['amount'],
                        'order_id' => $orderId,
                        'message' => 'Silakan scan QRIS',
                        'data' => $transaksi
                    ]);
                } else {
                    throw new \Exception('Gagal membuat QRIS: ' . $result['message']);
                }
            }

            // --- B. JIKA BAYAR CASH ---
            else {
                DB::transaction(function () use ($transaksi, $request, $biayaTotal) {
                    $transaksi->status_pembayaran = 'paid';
                    $transaksi->save();

                    // Kurangi Slot Parkir
                    $area = AreaParkir::where('id_area', $transaksi->id_area)->lockForUpdate()->first();
                    if ($area && $area->terisi > 0) {
                        $area->decrement('terisi');
                    }

                    // Log Aktivitas
                    LogAktivitas::create([
                        'id_user' => $request->user()->id_user,
                        'aktivitas' => "Proses Keluar (CASH): {$transaksi->plat_nomor}. Total: Rp " . number_format($biayaTotal),
                    ]);
                });

                return response()->json([
                    'success' => true,
                    'is_qris' => false,
                    'message' => 'Transaksi Cash Selesai',
                    'data' => $transaksi
                ]);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // --- 3. GET LIST TRANSAKSI ---
    public function index(Request $request)
    {
        // Load relasi user, area, dan kendaraan (untuk cek member)
        $query = Transaksi::with(['user', 'area', 'kendaraan']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $data = $query->orderBy('id_transaksi', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    // --- 4. CEK STATUS QRIS (POLLING) ---
    public function checkStatus($orderId)
    {
        $transaksi = Transaksi::where('external_id', $orderId)->first();

        if (!$transaksi) {
            return response()->json(['success' => false, 'message' => 'Transaksi tidak ditemukan'], 404);
        }

        // Jika di DB kita sudah paid, return langsung
        if ($transaksi->status_pembayaran == 'paid') {
            return response()->json(['success' => true, 'status' => 'paid']);
        }

        // Jika belum, cek manual ke Cashi
        $cashi = new CashiService();
        $res = $cashi->checkStatus($orderId);

        if (isset($res['status']) && $res['status'] == 'SETTLED') {
            // Update jadi paid
            $transaksi->status_pembayaran = 'paid';
            $transaksi->save();

            // Slot parkir dikurangi (karena sudah lunas, mobil boleh keluar)
            AreaParkir::where('id_area', $transaksi->id_area)->decrement('terisi');

            return response()->json(['success' => true, 'status' => 'paid']);
        }

        return response()->json(['success' => true, 'status' => 'pending']);
    }

    // --- 5. CETAK STRUK ---
    public function cetakStruk($id)
    {
        $trx = Transaksi::with(['user', 'area', 'kendaraan'])->find($id);

        if (!$trx) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'struk_id' => '#TRX-' . $trx->id_transaksi,
                'waktu_masuk' => date('d-m-Y H:i', strtotime($trx->waktu_masuk)),
                'waktu_keluar' => $trx->waktu_keluar ? date('d-m-Y H:i', strtotime($trx->waktu_keluar)) : '-',
                'durasi' => $trx->durasi_jam . ' Jam',
                'plat_nomor' => $trx->plat_nomor,
                'jenis' => strtoupper($trx->jenis_kendaraan),
                'biaya' => $trx->biaya_total,
                'petugas' => $trx->user ? $trx->user->nama_lengkap : 'Sistem',
                'area' => $trx->area->nama_area,
                'metode_bayar' => strtoupper($trx->metode_bayar),
                'status_bayar' => strtoupper($trx->status_pembayaran),
                'member' => $trx->kendaraan ? $trx->kendaraan->pemilik : '-'
            ]
        ]);
    }
}
