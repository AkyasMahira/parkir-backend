<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tarif;
use Illuminate\Http\Request;

class TarifController extends Controller
{
    // GET
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Tarif::all()
        ]);
    }

    // POST
    public function store(Request $request)
    {
        $request->validate([
            'jenis_kendaraan' => 'required|string|max:20',
            'tarif_per_jam' => 'required|numeric|min:0',
        ]);

        $tarif = Tarif::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Tarif berhasil ditambahkan',
            'data' => $tarif
        ], 201);
    }

    // PUT
    public function update(Request $request, $id)
    {
        $tarif = Tarif::find($id);
        if (!$tarif) return response()->json(['message' => 'Data tidak ditemukan'], 404);

        $request->validate([
            'jenis_kendaraan' => 'required|string',
            'tarif_per_jam' => 'required|numeric',
        ]);

        $tarif->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Tarif berhasil diupdate',
            'data' => $tarif
        ]);
    }

    // DELETE
    public function destroy($id)
    {
        $tarif = Tarif::find($id);
        if (!$tarif) return response()->json(['message' => 'Data tidak ditemukan'], 404);

        $tarif->delete();
        return response()->json(['success' => true, 'message' => 'Tarif dihapus']);
    }
}
