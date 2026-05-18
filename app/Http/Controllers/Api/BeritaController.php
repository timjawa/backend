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
        $query = Berita::with(['author:id,name', 'tags:id,berita_id,tag'])
            ->orderByDesc('dibuat_pada');

        // Filter by search (judul)
        if ($request->filled('search')) {
            $query->where('judul', 'like', '%' . $request->search . '%');
        }

        // Filter by status (draft, published, archived)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else if (!$request->has('admin')) {
            // Default public only see published
            $query->published();
        }

        // Filter by kategori
        if ($request->filled('kategori')) {
            $query->byKategori($request->kategori);
        }

        $berita = $query->paginate($request->get('per_page', 10));

        return response()->json($berita);
    }

    /**
     * Get statistics for news (total, published, draft, archived).
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'total' => Berita::count(),
            'published' => Berita::where('status', 'published')->count(),
            'draft' => Berita::where('status', 'draft')->count(),
            'archived' => Berita::where('status', 'archived')->count(),
        ]);
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
            $validated['foto_cover'] = basename($path); // Simpan nama filenya saja
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

        // Increment the view counter by 1 on every access
        $berita->increment('views_count');

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
            $oldFile = 'uploads/berita/' . basename($berita->foto_cover);
            if ($berita->foto_cover && Storage::disk('public')->exists($oldFile)) {
                Storage::disk('public')->delete($oldFile);
            }
            
            $path = $request->file('foto_cover')->store('uploads/berita', 'public');
            $validated['foto_cover'] = basename($path); // Simpan nama filenya saja
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
        if ($berita->foto_cover) {
            $oldFile = 'uploads/berita/' . basename($berita->foto_cover);
            if (Storage::disk('public')->exists($oldFile)) {
                Storage::disk('public')->delete($oldFile);
            }
        }
        
        $berita->delete();

        return response()->json([
            'message' => 'Berita berhasil dihapus.',
        ]);
    }
}
