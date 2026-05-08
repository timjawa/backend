<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kecamatan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KecamatanController extends Controller
{
    /**
     * List all kecamatan (public access).
     * Columns: id, nama, latitude, longitude, elevasi, kode_wilayah, level_rawan
     */
    public function index(Request $request): JsonResponse
    {
        $query = Kecamatan::query()->orderBy('nama');

        // Optional: filter by level_rawan
        if ($request->has('level_rawan')) {
            $query->where('level_rawan', $request->level_rawan);
        }

        // Optional: search by nama
        if ($request->has('search') && $request->search) {
            $query->where('nama', 'like', '%' . $request->search . '%');
        }

        // Paginate or get all
        if ($request->boolean('all')) {
            $kecamatan = $query->get();
            return response()->json(['data' => $kecamatan]);
        }

        $perPage = $request->input('per_page', 10);
        $kecamatan = $query->paginate($perPage);

        return response()->json($kecamatan);
    }

    /**
     * Show single kecamatan by ID.
     */
    public function show(string $id): JsonResponse
    {
        $kecamatan = Kecamatan::findOrFail($id);

        return response()->json(['data' => $kecamatan]);
    }

    /**
     * Create new kecamatan (admin only).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama'         => ['required', 'string', 'max:100'],
            'latitude'     => ['required', 'numeric', 'between:-90,90'],
            'longitude'    => ['required', 'numeric', 'between:-180,180'],
            'elevasi'      => ['nullable', 'numeric'],
            'kode_wilayah' => ['required', 'string', 'max:20', 'unique:kecamatan,kode_wilayah'],
            'level_rawan'  => ['required', 'in:rendah,sedang,tinggi'],
        ]);

        $kecamatan = Kecamatan::create($validated);

        return response()->json([
            'message' => 'Kecamatan berhasil ditambahkan.',
            'data'    => $kecamatan,
        ], 201);
    }

    /**
     * Update kecamatan (admin only).
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $kecamatan = Kecamatan::findOrFail($id);

        $validated = $request->validate([
            'nama'         => ['sometimes', 'string', 'max:100'],
            'latitude'     => ['sometimes', 'numeric', 'between:-90,90'],
            'longitude'    => ['sometimes', 'numeric', 'between:-180,180'],
            'elevasi'      => ['nullable', 'numeric'],
            'kode_wilayah' => ['sometimes', 'string', 'max:20', 'unique:kecamatan,kode_wilayah,' . $id],
            'level_rawan'  => ['sometimes', 'in:rendah,sedang,tinggi'],
        ]);

        $kecamatan->update($validated);

        return response()->json([
            'message' => 'Kecamatan berhasil diupdate.',
            'data'    => $kecamatan,
        ]);
    }

    /**
     * Delete kecamatan (admin only).
     */
    public function destroy(string $id): JsonResponse
    {
        $kecamatan = Kecamatan::findOrFail($id);
        $kecamatan->delete();

        return response()->json([
            'message' => 'Kecamatan berhasil dihapus.',
        ]);
    }

    /**
     * Get statistics for kecamatan (public access).
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'tinggi' => Kecamatan::where('level_rawan', 'tinggi')->count(),
            'sedang' => Kecamatan::where('level_rawan', 'sedang')->count(),
            'rendah' => Kecamatan::where('level_rawan', 'rendah')->count(),
        ];

        return response()->json($stats);
    }
}
