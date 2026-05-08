<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KontakDarurat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KontakDaruratController extends Controller
{
    /**
     * List all kontak darurat (public access).
     */
    public function index(Request $request): JsonResponse
    {
        $query = KontakDarurat::query()->orderBy('nama');

        if ($request->has('kategori') && $request->kategori) {
            $query->where('kategori', $request->kategori);
        }

        if ($request->has('search') && $request->search) {
            $query->where('nama', 'like', '%' . $request->search . '%')
                  ->orWhere('nomor', 'like', '%' . $request->search . '%');
        }

        if ($request->boolean('all')) {
            $data = $query->get();
            return response()->json(['data' => $data]);
        }

        $perPage = $request->input('per_page', 10);
        $data = $query->paginate($perPage);

        $response = $data->toArray();
        $response['summary'] = [
            'total_kontak' => KontakDarurat::count(),
            'total_aktif' => KontakDarurat::where('is_active', true)->count(),
        ];

        return response()->json($response);
    }

    /**
     * Show single kontak darurat.
     */
    public function show(string $id): JsonResponse
    {
        $kontak = KontakDarurat::findOrFail($id);
        return response()->json(['data' => $kontak]);
    }

    /**
     * Create new kontak darurat (admin only).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama'       => ['required', 'string', 'max:255'],
            'nomor'      => ['required', 'string', 'max:30'],
            'kategori'   => ['required', 'in:polisi,pemadam,ambulans,bpbd,sar,pln,lainnya'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'is_active'  => ['boolean'],
        ]);

        $kontak = KontakDarurat::create($validated);

        return response()->json([
            'message' => 'Kontak Darurat berhasil ditambahkan.',
            'data'    => $kontak,
        ], 201);
    }

    /**
     * Update kontak darurat (admin only).
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $kontak = KontakDarurat::findOrFail($id);

        $validated = $request->validate([
            'nama'       => ['sometimes', 'string', 'max:255'],
            'nomor'      => ['sometimes', 'string', 'max:30'],
            'kategori'   => ['sometimes', 'in:polisi,pemadam,ambulans,bpbd,sar,pln,lainnya'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'is_active'  => ['sometimes', 'boolean'],
        ]);

        $kontak->update($validated);

        return response()->json([
            'message' => 'Kontak Darurat berhasil diupdate.',
            'data'    => $kontak,
        ]);
    }

    /**
     * Delete kontak darurat (admin only).
     */
    public function destroy(string $id): JsonResponse
    {
        $kontak = KontakDarurat::findOrFail($id);
        $kontak->delete();

        return response()->json([
            'message' => 'Kontak Darurat berhasil dihapus.',
        ]);
    }
}
