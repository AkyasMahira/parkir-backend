<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kendaraan; 
use App\Models\Transaksi;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    // Daftar Kendaraan
    public function index(Request $request)
    {
        $kendaraan = Kendaraan::where('id_user', $request->user()->id_user)->get();
        return response()->json(['success' => true, 'data' => $kendaraan]);
    }

    // Pendaftaran Member
    public function store(Request $request)
    {
        $request->validate([
            'plat_nomor' => 'required|unique:tb_kendaraan,plat_nomor',
            'jenis_kendaraan' => 'required',
            'warna' => 'required', 
            'pemilik' => 'required', 
        ]);

        $kendaraan = Kendaraan::create([
            'id_user' => $request->user()->id_user,
            'plat_nomor' => strtoupper($request->plat_nomor),
            'jenis_kendaraan' => $request->jenis_kendaraan,
            'warna' => $request->warna,
            'pemilik' => $request->pemilik,
            'merk' => $request->merk ?? '-', 
        ]);

        return response()->json(['success' => true, 'message' => 'Kendaraan terdaftar', 'data' => $kendaraan]);
    }

    // Hapus
    public function destroy(Request $request, $id)
    {
        $kendaraan = Kendaraan::where('id_user', $request->user()->id_user)
            ->where('id_kendaraan', $id)
            ->first();

        if (!$kendaraan) return response()->json(['message' => 'Tidak ditemukan'], 404);

        $kendaraan->delete();
        return response()->json(['success' => true, 'message' => 'Dihapus']);
    }

    // Riwayat
    public function history(Request $request)
    {
        // Ambil semua plat nomor milik user ini
        $myPlates = Kendaraan::where('id_user', $request->user()->id_user)
            ->pluck('plat_nomor')
            ->toArray();

        // Cari transaksi yang plat-nya cocok dengan milik user
        $history = Transaksi::whereIn('plat_nomor', $myPlates)
            ->with('area') 
            ->orderBy('waktu_masuk', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $history]);
    }
}
