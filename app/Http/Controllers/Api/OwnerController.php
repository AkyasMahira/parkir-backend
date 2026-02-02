<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AreaParkir;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OwnerController extends Controller
{
    // 1. STATISTIK DASHBOARD (Home)
    public function dashboard()
    {
        $today = date('Y-m-d');

        // 1. Stats Utama (Uang)
        $hariIni = Transaksi::where('status', 'selesai')
            ->whereDate('waktu_keluar', $today)
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(biaya_total), 0) as pendapatan')
            ->first();

        $bulanIni = Transaksi::where('status', 'selesai')
            ->whereMonth('waktu_keluar', date('m'))
            ->whereYear('waktu_keluar', date('Y'))
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(biaya_total), 0) as pendapatan')
            ->first();

        // 2. Data Area Parkir (Realtime Kapasitas)
        $areaStats = AreaParkir::select('nama_area', 'kapasitas', 'terisi')
            ->get()
            ->map(function ($area) {
                // Hitung persentase keterisian
                $area->persentase = $area->kapasitas > 0
                    ? round(($area->terisi / $area->kapasitas) * 100)
                    : 0;
                return $area;
            });

        // 3. Breakdown Jenis Kendaraan (Hari Ini)
        $jenisKendaraan = Transaksi::whereDate('waktu_masuk', $today)
            ->selectRaw('jenis_kendaraan, COUNT(*) as total')
            ->groupBy('jenis_kendaraan')
            ->pluck('total', 'jenis_kendaraan')
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'hari_ini' => $hariIni,
                'bulan_ini' => $bulanIni,
                'kendaraan_parkir' => $areaStats->sum('terisi'), // Total mobil di dalam
                'area_stats' => $areaStats, // Data detail per area
                'jenis_stats' => [
                    'mobil' => $jenisKendaraan['mobil'] ?? 0,
                    'motor' => $jenisKendaraan['motor'] ?? 0,
                ]
            ]
        ]);
    }

    // 2. LAPORAN TRANSAKSI (Tabel & Export)
    public function laporan(Request $request)
    {
        // Load relasi user & area agar nama petugas & area muncul
        $query = Transaksi::with(['user:id_user,nama_lengkap', 'area:id_area,nama_area'])
            ->where('status', 'selesai') // Hanya ambil yang sudah bayar
            ->orderBy('waktu_keluar', 'desc');

        // Filter Tanggal Mulai (Berdasarkan Waktu Keluar)
        if ($request->has('tanggal_mulai') && !empty($request->tanggal_mulai)) {
            $query->whereDate('waktu_keluar', '>=', $request->tanggal_mulai);
        }

        // Filter Tanggal Akhir
        if ($request->has('tanggal_akhir') && !empty($request->tanggal_akhir)) {
            $query->whereDate('waktu_keluar', '<=', $request->tanggal_akhir);
        }

        // Pagination 50 data per halaman (Frontend sudah siap handle ini)
        $transaksi = $query->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $transaksi
        ]);
    }

    // 3. GRAFIK / ANALISA PENDAPATAN (Opsional jika nanti butuh grafik)
    public function pendapatan(Request $request)
    {
        $bulan = $request->bulan ?? date('Y-m'); // Format YYYY-MM

        $year = date('Y', strtotime($bulan));
        $month = date('m', strtotime($bulan));

        $data = Transaksi::whereYear('waktu_keluar', $year)
            ->whereMonth('waktu_keluar', $month)
            ->where('status', 'selesai')
            ->selectRaw('DATE(waktu_keluar) as tanggal, COUNT(*) as jumlah, COALESCE(SUM(biaya_total), 0) as total')
            ->groupBy('tanggal')
            ->orderBy('tanggal', 'desc')
            ->get();

        $totalPendapatan = $data->sum('total');
        $totalTransaksi = $data->sum('jumlah');

        return response()->json([
            'success' => true,
            'bulan' => $bulan,
            'total_pendapatan' => $totalPendapatan,
            'total_transaksi' => $totalTransaksi,
            'detail_harian' => $data
        ]);
    }
}
