<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LaporanBencana;
use App\Models\LaporanMedia;
use App\Models\Kecamatan;
use Illuminate\Http\Request;
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
