<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Berita;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BeritaController extends Controller
{
    /**
     * List published berita (public access).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Berita::published()
            ->with(['author:id,name', 'tags:id,berita_id,tag'])
            ->orderByDesc('dipublikasi_pada');

        // Optional: filter by kategori
        if ($request->has('kategori')) {
            $query->byKategori($request->kategori);
        }

        $berita = $query->paginate(10);

        return response()->json($berita);
    }

    /**
     * Create new berita (admin only).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'judul'            => ['required', 'string', 'max:255'],
            'slug'             => ['required', 'string', 'max:255', 'unique:berita,slug'],
            'konten'           => ['required', 'string'],
            'ringkasan'        => ['nullable', 'string'],
            'foto_cover'       => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:10240'],
            'kategori'         => ['nullable', 'in:umum,banjir,longsor,kebakaran,angin_kencang,gempa,cuaca'],
            'sumber'           => ['nullable', 'string', 'max:255'],
            'status'           => ['nullable', 'in:draft,published,archived'],
            'tags'             => ['nullable', 'string'], // Menerima string dari FormData
        ]);

        // Auto-fill author from authenticated user
        $validated['dibuat_oleh'] = $request->user()->id;

        // If publishing, set the publish date
        if (($validated['status'] ?? 'draft') === 'published') {
            $validated['dipublikasi_pada'] = now();
        }

        // Handle upload foto_cover
        if ($request->hasFile('foto_cover')) {
            $path = $request->file('foto_cover')->store('uploads/berita', 'public');
            $validated['foto_cover'] = $path;
        }

        $berita = Berita::create($validated);

        // Create tags if provided (dipecah dari comma-separated string)
        if (!empty($validated['tags'])) {
            $tagsArray = array_map('trim', explode(',', $validated['tags']));
            foreach ($tagsArray as $tag) {
                if (!empty($tag)) {
                    $berita->tags()->create(['tag' => $tag]);
                }
            }
        }

        $berita->load(['author:id,name', 'tags:id,berita_id,tag']);

        return response()->json([
            'message' => 'Berita berhasil dibuat.',
            'berita'  => $berita,
        ], 201);
    }

    /**
     * Show single berita by ID.
     */
    public function show(string $id): JsonResponse
    {
        $berita = Berita::with(['author:id,name', 'tags:id,berita_id,tag'])
            ->findOrFail($id);

        return response()->json(['berita' => $berita]);
    }

    /**
     * Update berita (admin only).
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $berita = Berita::findOrFail($id);

        $validated = $request->validate([
            'judul'            => ['sometimes', 'string', 'max:255'],
            'slug'             => ['sometimes', 'string', 'max:255', 'unique:berita,slug,' . $berita->id],
            'konten'           => ['sometimes', 'string'],
            'ringkasan'        => ['nullable', 'string'],
            'foto_cover'       => ['nullable'], // Bisa string atau file
            'kategori'         => ['nullable', 'in:umum,banjir,longsor,kebakaran,angin_kencang,gempa,cuaca'],
            'sumber'           => ['nullable', 'string', 'max:255'],
            'status'           => ['nullable', 'in:draft,published,archived'],
            'tags'             => ['nullable', 'string'],
        ]);

        if ($request->hasFile('foto_cover')) {
            // Hapus file foto lama dari storage agar "clean" (tidak menumpuk sampah)
            if ($berita->foto_cover && Storage::disk('public')->exists($berita->foto_cover)) {
                Storage::disk('public')->delete($berita->foto_cover);
            }
            
            $validated['foto_cover'] = $request->file('foto_cover')->store('uploads/berita', 'public');
        } else {
            // Jika tidak ada file baru, hapus dari validated agar tidak menimpa data lama dengan null
            unset($validated['foto_cover']);
        }

        // If changing to published and not yet published, set the date
        if (($validated['status'] ?? null) === 'published' && !$berita->dipublikasi_pada) {
            $validated['dipublikasi_pada'] = now();
        }

        $berita->update($validated);

        // Sync tags if provided
        if (isset($validated['tags'])) {
            $berita->tags()->delete();
            $tagsArray = array_map('trim', explode(',', $validated['tags']));
            foreach ($tagsArray as $tag) {
                if (!empty($tag)) {
                    $berita->tags()->create(['tag' => $tag]);
                }
            }
        }

        $berita->load(['author:id,name', 'tags:id,berita_id,tag']);

        return response()->json([
            'message' => 'Berita berhasil diupdate.',
            'berita'  => $berita,
        ]);
    }

    /**
     * Delete berita (admin only).
     */
    public function destroy(string $id): JsonResponse
    {
        $berita = Berita::findOrFail($id);
        
        // Hapus file fisik foto dari storage agar "clean"
        if ($berita->foto_cover && Storage::disk('public')->exists($berita->foto_cover)) {
            Storage::disk('public')->delete($berita->foto_cover);
        }
        
        $berita->delete();

        return response()->json([
            'message' => 'Berita berhasil dihapus.',
        ]);
    }
}
