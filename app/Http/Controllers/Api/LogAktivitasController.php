<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;

class LogAktivitasController extends Controller
{
    public function index(Request $request)
    {
        $query = LogAktivitas::with('user');

        // Filter pencarian berdasarkan aktivitas atau nama user
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('aktivitas', 'like', "%{$search}%")
                ->orWhereHas('user', function ($q) use ($search) {
                    $q->where('nama_lengkap', 'like', "%{$search}%");
                });
        }

        // Ambil 100 log terbaru
        $logs = $query->orderBy('id_log', 'desc')->limit(100)->get();

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }
}
