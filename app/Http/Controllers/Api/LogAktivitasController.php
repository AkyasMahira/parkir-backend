<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;

class LogAktivitasController extends Controller
{
    public function index()
    {
        $logs = LogAktivitas::with('user')
            ->orderBy('id_log', 'desc')
            ->limit(100)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }
}