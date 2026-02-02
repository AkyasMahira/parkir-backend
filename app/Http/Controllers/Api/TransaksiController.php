<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use App\Models\AreaParkir;
use App\Models\LogAktivitas;
use App\Models\Tarif;
use App\Services\CashiService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TransaksiController extends Controller
{

    private function formatRupiah($angka)
    {
        return "Rp " . number_format($angka, 0, ',', '.');
    }
    // CHECK IN - Generate Struk ID
    public function store(Request $request)
    {
        $request->validate([
            'plat_nomor' => 'required',
            'jenis_kendaraan' => 'required',
            'id_area' => 'required|exists:tb_area_parkir,id_area',
        ]);

        $area = AreaParkir::find($request->id_area);
        if ($area->terisi >= $area->kapasitas) {
            return response()->json(['success' => false, 'message' => 'Area penuh'], 400);
        }

        $strukId = 'TRX-' . strtoupper(Str::random(8));

        $transaksi = Transaksi::create([
            'struk_id' => $strukId,
            'id_user' => $request->user()->id_user,
            'id_area' => $request->id_area,
            'plat_nomor' => strtoupper($request->plat_nomor),
            'jenis_kendaraan' => $request->jenis_kendaraan,
            'waktu_masuk' => now(),
            'status' => 'parkir',
        ]);

        $area->increment('terisi');

        // --- IMPLEMENTASI LOG AUDIT (CHECK-IN) ---
        LogAktivitas::create([
            'id_user' => $request->user()->id_user,
            'aktivitas' => "Check-in: Petugas memproses kendaraan {$transaksi->plat_nomor} di {$area->nama_area}",
            'waktu_aktivitas' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Check-in berhasil',
            'data' => $transaksi->load('area'),
            'struk_id' => $strukId,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'struk_id' => 'required_without:foto_identitas',
            'foto_identitas' => 'required_without:struk_id|image|max:2048',
            'metode_bayar' => 'required|in:cash,qris',
        ]);

        if ($request->struk_id) {
            $transaksi = Transaksi::where('struk_id', $request->struk_id)
                ->where('status', 'parkir')
                ->first();
        } else {
            $fotoPath = $request->file('foto_identitas')->store('identitas', 'public');
            $transaksi = Transaksi::where('status', 'parkir')
                ->orderBy('waktu_masuk', 'desc')
                ->first();

            if ($transaksi) {
                $transaksi->update(['foto_identitas' => $fotoPath]);
            }
        }

        if (!$transaksi) {
            return response()->json(['success' => false, 'message' => 'Transaksi tidak ditemukan'], 404);
        }

        $waktuMasuk = Carbon::parse($transaksi->waktu_masuk);
        $waktuKeluar = now();
        $selisihMenit = $waktuMasuk->diffInMinutes($waktuKeluar);
        $durasi = $selisihMenit <= 0 ? 1 : ceil($selisihMenit / 60);

        $tarif = Tarif::where('jenis_kendaraan', $transaksi->jenis_kendaraan)->first();
        $biaya = $durasi * ($tarif ? $tarif->tarif_per_jam : 0);

        $transaksi->update([
            'waktu_keluar' => $waktuKeluar,
            'durasi_jam' => $durasi,
            'biaya_total' => $biaya,
            'metode_bayar' => $request->metode_bayar,
            'status' => $request->metode_bayar === 'cash' ? 'selesai' : 'menunggu_bayar',
        ]);

        $transaksi->area->decrement('terisi');

        // --- IMPLEMENTASI LOG AUDIT (CHECK-OUT) ---
        LogAktivitas::create([
            'id_user' => $request->user()->id_user,
            'aktivitas' => "Check-out: Petugas memproses pembayaran {$transaksi->plat_nomor} sebesar " . $this->formatRupiah($biaya),
            'waktu_aktivitas' => now(),
        ]);

        if ($request->metode_bayar === 'qris') {
            try {
                $qrData = $this->generateQRIS($transaksi);
                return response()->json([
                    'success' => true,
                    'is_qris' => true,
                    'qr_image' => $qrData['qr_image'],
                    'order_id' => $qrData['order_id'],
                    'nominal' => $qrData['nominal'],
                    'data' => $transaksi,
                ]);
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
        }

        return response()->json([
            'success' => true,
            'is_qris' => false,
            'message' => 'Checkout berhasil',
            'data' => $transaksi,
        ]);
    }

    private function generateQRIS($transaksi)
    {
        $cashi = new CashiService();
        $orderId = 'ORD-' . $transaksi->id_transaksi . '-' . time();

        // Create order ke Cashi
        $result = $cashi->createOrder($orderId, $transaksi->biaya_total);

        if ($result['success']) {
            $dataCashi = $result['data'];

            // Update nominal jika ada kode unik dari Cashi
            $transaksi->update([
                'order_id' => $orderId,
                'biaya_total' => $dataCashi['amount']
            ]);

            return [
                'qr_image' => $dataCashi['qrUrl'],
                'order_id' => $orderId,
                'nominal'  => $dataCashi['amount']
            ];
        } else {
            throw new \Exception('Gagal generate QRIS: ' . ($result['message'] ?? 'Error'));
        }
    }

    public function checkStatus($orderId)
    {
        $transaksi = Transaksi::where('order_id', $orderId)->first();

        if (!$transaksi) {
            return response()->json(['status' => 'not_found'], 404);
        }

        if ($transaksi->status === 'selesai') {
            return response()->json(['status' => 'paid']);
        }

        // Cek status real ke Cashi
        $cashi = new CashiService();
        $res = $cashi->checkStatus($orderId);

        if (isset($res['status']) && ($res['status'] == 'SETTLED' || $res['status'] == 'PAID')) {
            $transaksi->update(['status' => 'selesai']);
            return response()->json(['status' => 'paid']);
        }

        return response()->json(['status' => 'pending']);
    }

    // Cetak Struk
    public function cetakStruk($id)
    {
        $transaksi = Transaksi::with(['user', 'area'])->find($id);

        if (!$transaksi) {
            return response()->json(['message' => 'Tidak ditemukan'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'struk_id' => $transaksi->struk_id,
                'plat_nomor' => $transaksi->plat_nomor,
                'waktu_masuk' => $transaksi->waktu_masuk->format('d/m/Y H:i'),
                'waktu_keluar' => $transaksi->waktu_keluar ? $transaksi->waktu_keluar->format('d/m/Y H:i') : '-',
                'durasi' => $transaksi->durasi_jam . ' jam',
                'biaya' => $transaksi->biaya_total,
                'petugas' => $transaksi->user->nama_lengkap,
                'metode_bayar' => $transaksi->metode_bayar ?? '-',
                'status_bayar' => $transaksi->status,
            ],
        ]);
    }

    // List transaksi
    // List transaksi
    public function index(Request $request)
    {
        // Load relasi user dan area
        $query = Transaksi::with(['area', 'user']);

        // Filter Server-side (Opsional, jika ingin pindah dari client-side filtering)
        if ($request->has('status') && $request->status != 'all') {
            // Mapping status sederhana jika diperlukan
            $status = $request->status;
            if ($status === 'masuk') $status = 'parkir';

            $query->where('status', $status);
        }

        // Pencarian Server-side (Opsional)
        if ($request->has('q')) {
            $query->where('plat_nomor', 'like', '%' . $request->q . '%');
        }

        // Sorting default: Terbaru paling atas
        $query->orderBy('waktu_masuk', 'desc');

        // Check limit dari request frontend, default 50
        // Frontend mengirim ?limit=500 untuk kebutuhan tabel riwayat
        $limit = $request->input('limit', 50);

        // Gunakan paginate agar response structure konsisten { data: [...], links: ... }
        $data = $query->paginate($limit);

        return response()->json([
            'success' => true,
            'data' => $data->items(), // Mengambil array datanya saja
            'meta' => [ // Info tambahan untuk pagination server-side kedepannya
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'total' => $data->total(),
                'per_page' => $data->perPage()
            ]
        ]);
    }
}
