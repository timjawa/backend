<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LaporanBencana;
use App\Models\LaporanMedia;
use App\Models\LaporanKomentar;
use App\Models\Kecamatan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class LaporanBencanaController extends Controller
{
    /**
     * Ambil daftar laporan milik user yang sedang login.
     */
    public function index(Request $request)
    {
        $laporan = LaporanBencana::with(['kecamatan', 'media'])
            ->where('user_id', $request->user()->id)
            ->where('is_draft', false)
            ->orderByDesc('dibuat_pada')
            ->get();

        return response()->json([
            'status'  => 'success',
            'data'    => $laporan,
        ]);
    }

    /**
     * Admin: Get all laporan with pagination and filtering
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $query = LaporanBencana::with(['user', 'kecamatan', 'media'])
            ->where('is_draft', false)
            ->orderByDesc('dibuat_pada');

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by kecamatan
        if ($request->has('kecamatan_id') && $request->kecamatan_id) {
            $query->where('kecamatan_id', $request->kecamatan_id);
        }

        // Search by jenis_bencana, user name, or alamat
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('jenis_bencana', 'like', '%' . $search . '%')
                  ->orWhere('alamat_lengkap', 'like', '%' . $search . '%')
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', '%' . $search . '%');
                  });
            });
        }

        // Paginate
        $perPage = $request->input('per_page', 10);
        $laporan = $query->paginate($perPage);

        return response()->json($laporan);
    }

    /**
     * Admin: Get statistics for all laporan
     */
    public function adminStats(): JsonResponse
    {
        $stats = [
            'total' => LaporanBencana::where('is_draft', false)->count(),
            'baru' => LaporanBencana::where('is_draft', false)->where('status', 'baru')->count(),
            'diverifikasi' => LaporanBencana::where('is_draft', false)->where('status', 'diverifikasi')->count(),
            'ditolak' => LaporanBencana::where('is_draft', false)->where('status', 'ditolak')->count(),
            'selesai' => LaporanBencana::where('is_draft', false)->where('status', 'selesai')->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Simpan laporan baru (bisa real submit atau draft).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'jenis_bencana'  => 'required|string|max:100',
            'deskripsi'      => 'required|string',
            'alamat_lengkap' => 'nullable|string|max:255',
            'kecamatan_id'   => 'nullable|exists:kecamatan,id',
            'latitude'       => 'nullable|numeric',
            'longitude'      => 'nullable|numeric',
            'is_draft'       => 'boolean',
            'foto.*'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'video'          => 'nullable|mimes:mp4,mov,avi,mkv|max:51200',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Buat laporan
        $laporan = LaporanBencana::create([
            'user_id'        => $request->user()->id,
            'kecamatan_id'   => $request->kecamatan_id,
            'jenis_bencana'  => $request->jenis_bencana,
            'deskripsi'      => $request->deskripsi,
            'alamat_lengkap' => $request->alamat_lengkap,
            'latitude'       => $request->latitude,
            'longitude'      => $request->longitude,
            'status'         => 'baru',
            'is_draft'       => $request->boolean('is_draft', false),
        ]);

        // Upload foto jika ada
        if ($request->hasFile('foto')) {
            foreach ($request->file('foto') as $index => $file) {
                $path = $file->store('laporan/foto', 'public');
                LaporanMedia::create([
                    'laporan_id' => $laporan->id,
                    'url'        => Storage::url($path),
                    'tipe'       => 'foto',
                    'urutan'     => $index,
                ]);
            }
        }

        // Upload video jika ada
        if ($request->hasFile('video')) {
            $path = $request->file('video')->store('laporan/video', 'public');
            LaporanMedia::create([
                'laporan_id' => $laporan->id,
                'url'        => Storage::url($path),
                'tipe'       => 'video',
                'urutan'     => 0,
            ]);
        }

        return response()->json([
            'status'  => 'success',
            'message' => $laporan->is_draft ? 'Draft berhasil disimpan.' : 'Laporan berhasil dikirim.',
            'data'    => $laporan->load('media', 'kecamatan'),
        ], 201);
    }

    /**
     * Admin: Tampilkan detail laporan.
     */
    public function adminShow(string $id): JsonResponse
    {
        $laporan = LaporanBencana::with(['user', 'kecamatan', 'media', 'komentar.user'])
            ->where('id', $id)
            ->firstOrFail();

        return response()->json([
            'status' => 'success',
            'data'   => $laporan,
        ]);
    }

    /**
     * Admin: Update laporan status
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:baru,diverifikasi,ditolak,selesai',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $laporan = LaporanBencana::findOrFail($id);
        $laporan->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Status laporan berhasil diperbarui.',
            'data'    => $laporan->fresh()->load(['user', 'kecamatan', 'media', 'komentar.user']),
        ]);
    }

    /**
     * Admin: Add comment to laporan
     */
    public function addComment(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'isi' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $laporan = LaporanBencana::findOrFail($id);
        
        $komentar = LaporanKomentar::create([
            'laporan_id' => $laporan->id,
            'user_id'    => $request->user()->id,
            'isi'        => $request->isi,
        ]);

        return response()->json([
            'message' => 'Komentar berhasil ditambahkan.',
            'data'    => $komentar->load('user'),
        ], 201);
    }

    /**
     * Admin: Delete laporan
     */
    public function adminDestroy(string $id): JsonResponse
    {
        $laporan = LaporanBencana::findOrFail($id);
        
        // Delete related records
        $laporan->media()->delete();
        $laporan->komentar()->delete();
        
        // Delete laporan
        $laporan->delete();

        return response()->json([
            'message' => 'Laporan berhasil dihapus.',
        ]);
    }

    /**
     * Tampilkan detail laporan.
     */
    public function show(Request $request, string $id)
    {
        $laporan = LaporanBencana::with(['kecamatan', 'media', 'komentar.user'])
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json([
            'status' => 'success',
            'data'   => $laporan,
        ]);
    }
}
