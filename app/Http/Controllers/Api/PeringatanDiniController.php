<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PeringatanDini;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PeringatanDiniController extends Controller
{
    public function index(Request $request)
    {
        $query = PeringatanDini::with(['kecamatan', 'pembuat'])
            ->orderBy('created_at', 'desc');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('deskripsi', 'like', "%{$search}%")
                  ->orWhereHas('kecamatan', function($q) use ($search) {
                      $q->where('nama', 'like', "%{$search}%");
                  });
        }

        if ($request->filled('tingkat_urgensi') && $request->tingkat_urgensi !== 'all') {
            $query->where('tingkat_urgensi', $request->tingkat_urgensi);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $perPage = $request->input('per_page', 10);
        $data = $query->paginate($perPage);

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kecamatan_id' => 'required|uuid|exists:kecamatan,id',
            'deskripsi' => 'required|string',
            'tingkat_urgensi' => 'required|in:rendah,sedang,tinggi,kritis',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $peringatan = PeringatanDini::create([
            'id' => Str::uuid()->toString(),
            'kecamatan_id' => $request->kecamatan_id,
            'dibuat_oleh' => $request->user()->id ?? null, // Need user ID
            'deskripsi' => $request->deskripsi,
            'tingkat_urgensi' => $request->tingkat_urgensi,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'message' => 'Peringatan dini berhasil ditambahkan.',
            'data' => $peringatan
        ], 201);
    }

    public function show($id)
    {
        $peringatan = PeringatanDini::with(['kecamatan', 'pembuat'])->findOrFail($id);
        return response()->json(['data' => $peringatan]);
    }

    public function update(Request $request, $id)
    {
        $peringatan = PeringatanDini::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'kecamatan_id' => 'required|uuid|exists:kecamatan,id',
            'deskripsi' => 'required|string',
            'tingkat_urgensi' => 'required|in:rendah,sedang,tinggi,kritis',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $peringatan->update([
            'kecamatan_id' => $request->kecamatan_id,
            'deskripsi' => $request->deskripsi,
            'tingkat_urgensi' => $request->tingkat_urgensi,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'message' => 'Peringatan dini berhasil diperbarui.',
            'data' => $peringatan
        ]);
    }

    public function destroy($id)
    {
        $peringatan = PeringatanDini::findOrFail($id);
        $peringatan->delete();

        return response()->json(['message' => 'Peringatan dini berhasil dihapus.']);
    }
}
