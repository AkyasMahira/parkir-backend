<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OwnerController extends Controller
{
    // Pendapatan Harian/Bulanan
    public function pendapatan(Request $request)
    {
        $bulan = $request->bulan ?? date('Y-m');

        $data = Transaksi::whereYear('waktu_masuk', '=', date('Y', strtotime($bulan)))
            ->whereMonth('waktu_masuk', '=', date('m', strtotime($bulan)))
            ->where('status', 'selesai')
            ->selectRaw('DATE(waktu_keluar) as tanggal, COUNT(*) as jumlah, SUM(biaya_total) as total')
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

    // Laporan Transaksi
    public function laporan(Request $request)
    {
        $query = Transaksi::with(['user:id_user,nama_lengkap', 'area:id_area,nama_area'])
            ->where('status', 'selesai')
            ->orderBy('waktu_keluar', 'desc');

        // Filter tanggal
        if ($request->tanggal_mulai) {
            $query->whereDate('waktu_keluar', '>=', $request->tanggal_mulai);
        }
        if ($request->tanggal_akhir) {
            $query->whereDate('waktu_keluar', '<=', $request->tanggal_akhir);
        }

        $transaksi = $query->paginate(50);

        return response()->json(['success' => true, 'data' => $transaksi]);
    }

    // Statistik Dashboard
    public function dashboard()
    {
        $today = date('Y-m-d');

        $stats = [
            'hari_ini' => Transaksi::whereDate('waktu_masuk', $today)
                ->selectRaw('COUNT(*) as total, SUM(biaya_total) as pendapatan')
                ->where('status', 'selesai')
                ->first(),
            'bulan_ini' => Transaksi::whereMonth('waktu_masuk', date('m'))
                ->whereYear('waktu_masuk', date('Y'))
                ->selectRaw('COUNT(*) as total, SUM(biaya_total) as pendapatan')
                ->where('status', 'selesai')
                ->first(),
            'kendaraan_parkir' => Transaksi::where('status', 'parkir')->count(),
        ];

        return response()->json(['success' => true, 'data' => $stats]);
    }
}
