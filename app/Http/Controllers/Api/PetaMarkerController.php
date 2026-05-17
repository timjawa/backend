<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PetaMarker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PetaMarkerController extends Controller
{
    /**
     * GET /api/peta-marker
     * Public: ambil semua marker aktif
     */
    public function index(Request $request)
    {
        $query = PetaMarker::with('pembuat')
            ->where('is_active', true)
            ->orderBy('dibuat_pada', 'desc');

        if ($request->filled('kategori') && $request->kategori !== 'all') {
            $query->where('kategori', $request->kategori);
        }

        if ($request->filled('tingkat_bahaya') && $request->tingkat_bahaya !== 'all') {
            $query->where('tingkat_bahaya', $request->tingkat_bahaya);
        }

        $data = $query->get();

        return response()->json(['data' => $data]);
    }

    /**
     * POST /api/admin/peta-marker
     * Admin: tambah marker baru
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude'       => 'required|numeric|between:-90,90',
            'longitude'      => 'required|numeric|between:-180,180',
            'tipe_marker'    => 'nullable|string|in:titik,garis,area',
            'path_data'      => 'nullable|array',
            'label'          => 'nullable|string|max:255',
            'kategori'       => 'required|string|max:100',
            'tingkat_bahaya' => 'required|in:rendah,sedang,tinggi,kritis',
            'is_active'      => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $marker = PetaMarker::create([
            'id'             => Str::uuid()->toString(),
            'latitude'       => $request->latitude,
            'longitude'      => $request->longitude,
            'tipe_marker'    => $request->input('tipe_marker', 'titik'),
            'path_data'      => $request->path_data,
            'label'          => $request->label,
            'kategori'       => strtoupper($request->kategori),
            'tingkat_bahaya' => $request->tingkat_bahaya,
            'dibuat_oleh'    => $request->user()?->id,
            'is_active'      => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'message' => 'Marker berhasil ditambahkan.',
            'data'    => $marker,
        ], 201);
    }

    /**
     * GET /api/admin/peta-marker
     * Admin: ambil semua marker (termasuk non-aktif)
     */
    public function adminIndex(Request $request)
    {
        $query = PetaMarker::with('pembuat')
            ->orderBy('dibuat_pada', 'desc');

        if ($request->filled('kategori') && $request->kategori !== 'all') {
            $query->where('kategori', $request->kategori);
        }

        if ($request->filled('tingkat_bahaya') && $request->tingkat_bahaya !== 'all') {
            $query->where('tingkat_bahaya', $request->tingkat_bahaya);
        }

        $data = $query->get();

        return response()->json(['data' => $data]);
    }

    /**
     * DELETE /api/admin/peta-marker/{id}
     * Admin: hapus marker
     */
    public function destroy($id)
    {
        $marker = PetaMarker::findOrFail($id);
        $marker->delete();

        return response()->json(['message' => 'Marker berhasil dihapus.']);
    }

    /**
     * PUT /api/admin/peta-marker/{id}
     * Admin: update marker
     */
    public function update(Request $request, $id)
    {
        $marker = PetaMarker::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'latitude'       => 'nullable|numeric|between:-90,90',
            'longitude'      => 'nullable|numeric|between:-180,180',
            'tipe_marker'    => 'nullable|string|in:titik,garis,area',
            'path_data'      => 'nullable|array',
            'label'          => 'nullable|string|max:255',
            'kategori'       => 'nullable|string|max:100',
            'tingkat_bahaya' => 'nullable|in:rendah,sedang,tinggi,kritis',
            'is_active'      => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $marker->update(array_filter([
            'latitude'       => $request->latitude,
            'longitude'      => $request->longitude,
            'tipe_marker'    => $request->tipe_marker,
            'path_data'      => $request->path_data,
            'label'          => $request->label,
            'kategori'       => $request->filled('kategori') ? strtoupper($request->kategori) : null,
            'tingkat_bahaya' => $request->tingkat_bahaya,
            'is_active'      => $request->has('is_active') ? $request->boolean('is_active') : null,
        ], fn($v) => $v !== null));

        return response()->json([
            'message' => 'Marker berhasil diperbarui.',
            'data'    => $marker,
        ]);
    }
}
