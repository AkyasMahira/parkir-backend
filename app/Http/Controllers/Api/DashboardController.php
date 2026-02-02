<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            // 1. Hitung Total Pendapatan dari transaksi yang sudah 'selesai'
            $pendapatan = Transaksi::where('status', 'selesai')->sum('biaya_total');

            // 2. Hitung Total Transaksi (semua kendaraan yang masuk)
            $totalTransaksi = Transaksi::count();

            // 3. Hitung Total User
            $totalUsers = User::count();

            return response()->json([
                'success' => true,
                'data' => [
                    'pendapatan' => (int) $pendapatan,
                    'total_transaksi' => $totalTransaksi,
                    'total_users' => $totalUsers
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data statistik'
            ], 500);
        }
    }
}
