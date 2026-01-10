<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // LOGIN
    public function login(Request $request)
    {
        // Validasi Input
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        // Cari User di tb_user
        $user = User::where('username', $request->username)->first();

        // Cek apakah user ada & passwordny cocok
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Username atau Password salah.',
            ], 401);
        }

        // Cek Status
        if ($user->status_aktif == '0') {
            return response()->json([
                'success' => false,
                'message' => 'Akun anda dinonaktifkan oleh Admin.',
            ], 403);
        }

        // Hapus token lama (1 device 1 token)
        $user->tokens()->delete();

        // Buat Token Baru (Sanctum)
        $token = $user->createToken('auth_token')->plainTextToken;

        // Return JSON
        return response()->json([
            'success' => true,
            'message' => 'Login Berhasil',
            'data' => [
                'token' => $token,
                'user' => [
                    'id_user' => $user->id_user,
                    'nama_lengkap' => $user->nama_lengkap,
                    'role' => $user->role, 
                ]
            ]
        ]);
    }

    // LOGOUT
    public function logout(Request $request)
    {
        // Hapus token yang sedang dipakai
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout Berhasil'
        ]);
    }

    // CEK USER (Next.js mengecek user yang sedang login)
    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}
