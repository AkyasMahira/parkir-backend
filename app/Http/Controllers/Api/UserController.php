<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    // GET User
    public function index()
    { 
        $users = User::orderBy('id_user', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    // POST User 
    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|unique:tb_user,username',
            'password' => 'required|min:6',
            'nama_lengkap' => 'required',
            'role' => 'required|in:admin,petugas,owner',
        ]);

        $user = User::create([
            'username' => $request->username,
            'password' => Hash::make($request->password), 
            'nama_lengkap' => $request->nama_lengkap,
            'role' => $request->role,
            'status_aktif' => '1',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User berhasil ditambahkan',
            'data' => $user
        ], 201);
    }

    // PUT User 
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User tidak ditemukan'], 404);
        }

        $request->validate([
            'username' => ['required', Rule::unique('tb_user')->ignore($user->id_user, 'id_user')],
            'nama_lengkap' => 'required',
            'role' => 'required|in:admin,petugas,owner',
            'password' => 'nullable|min:6',
        ]);

        $dataToUpdate = [
            'username' => $request->username,
            'nama_lengkap' => $request->nama_lengkap,
            'role' => $request->role,
            'status_aktif' => $request->status_aktif ?? $user->status_aktif,
        ];

        // Hanya update password jika diisi
        if ($request->filled('password')) {
            $dataToUpdate['password'] = Hash::make($request->password);
        }

        $user->update($dataToUpdate);

        return response()->json([
            'success' => true,
            'message' => 'Data user berhasil diperbarui',
            'data' => $user
        ]);
    }

    // DELETE Hapus User
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User tidak ditemukan'], 404);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User berhasil dihapus'
        ]);
    }
}
