<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AreaParkir;
use Illuminate\Http\Request;

class AreaParkirController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => AreaParkir::all()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_area' => 'required|string',
            'kapasitas' => 'required|integer|min:1',
        ]);

        $area = AreaParkir::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Area parkir berhasil dibuat',
            'data' => $area
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $area = AreaParkir::find($id);
        if (!$area) return response()->json(['message' => 'Area tidak ditemukan'], 404);

        $request->validate([
            'nama_area' => 'required|string',
            'kapasitas' => 'required|integer',
        ]);

        $area->update($request->only(['nama_area', 'kapasitas']));

        return response()->json([
            'success' => true,
            'message' => 'Area berhasil diupdate',
            'data' => $area
        ]);
    }

    public function destroy($id)
    {
        $area = AreaParkir::find($id);
        if (!$area) return response()->json(['message' => 'Area tidak ditemukan'], 404);
        
        $area->delete();
        return response()->json(['success' => true, 'message' => 'Area dihapus']);
    }
}