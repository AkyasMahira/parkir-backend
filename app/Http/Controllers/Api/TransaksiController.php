<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use App\Models\AreaParkir;
use App\Models\Tarif;
use App\Models\LogAktivitas;
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
                $area = AreaParkir::where('id_area', $request->id_area)->lockForUpdate()->first();

                if ($area->terisi >= $area->kapasitas) {
                    throw new \Exception('Area parkir penuh!', 400);
                }

                $isParked = Transaksi::where('plat_nomor', $request->plat_nomor)
                    ->where('status', 'masuk')
                    ->exists();

                if ($isParked) {
                    throw new \Exception('Kendaraan sudah parkir (belum checkout).', 400);
                }

                $transaksi = Transaksi::create([
                    'id_user' => $request->user()->id_user,
                    'id_area' => $request->id_area,
                    'plat_nomor' => strtoupper($request->plat_nomor),
                    'jenis_kendaraan' => $request->jenis_kendaraan,
                    'waktu_masuk' => Carbon::now(),
                    'status' => 'masuk',
                    'metode_bayar' => 'cash',
                ]);

                $area->increment('terisi');

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
            $statusCode = (int) $e->getCode();
            if ($statusCode < 100 || $statusCode > 599) {
                $statusCode = 500;
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }

    public function update(Request $request)
    {
        $request->validate([
            'plat_nomor' => 'required_without:id_transaksi',
            'id_transaksi' => 'required_without:plat_nomor',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $query = Transaksi::where('status', 'masuk');

                if ($request->id_transaksi) {
                    $query->where('id_transaksi', $request->id_transaksi);
                } else {
                    $query->where('plat_nomor', $request->plat_nomor);
                }

                $transaksi = $query->lockForUpdate()->first();

                if (!$transaksi) {
                    throw new \Exception('Data parkir tidak ditemukan atau sudah keluar.', 404);
                }

                $waktuMasuk = Carbon::parse($transaksi->waktu_masuk);
                $waktuKeluar = Carbon::now();

                $selisihMenit = $waktuMasuk->diffInMinutes($waktuKeluar);

                if ($selisihMenit <= 0) {
                    $durasiJam = 1; // Minimal 1 jam meskipun baru masuk
                } else {
                    $durasiJam = ceil($selisihMenit / 60);
                }

                $tarifMaster = Tarif::where('jenis_kendaraan', $transaksi->jenis_kendaraan)->first();
                $hargaPerJam = $tarifMaster ? $tarifMaster->tarif_per_jam : 0;
                $totalBiaya = (int) ($durasiJam * $hargaPerJam);

                $transaksi->update([
                    'waktu_keluar' => $waktuKeluar,
                    'durasi_jam' => $durasiJam,
                    'biaya_total' => $totalBiaya,
                    'status' => 'keluar',
                ]);

                $area = AreaParkir::where('id_area', $transaksi->id_area)->lockForUpdate()->first();
                if ($area && $area->terisi > 0) {
                    $area->decrement('terisi');
                }

                LogAktivitas::create([
                    'id_user' => $request->user()->id_user,
                    'aktivitas' => "Proses Keluar: {$transaksi->plat_nomor}. Total: Rp " . number_format($totalBiaya),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Transaksi Selesai',
                    'data' => $transaksi
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    public function index(Request $request)
    {
        $query = Transaksi::with(['user', 'area']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $data = $query->orderBy('id_transaksi', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
