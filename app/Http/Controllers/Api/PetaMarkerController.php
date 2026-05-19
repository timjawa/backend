<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PetaMarker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PetaMarkerController extends Controller
{
    public function index(Request $request)
    {
        // 1. Ambil manual markers yang aktif
        $manualQuery = PetaMarker::with('pembuat')
            ->where('is_active', true)
            ->orderBy('dibuat_pada', 'desc');

        if ($request->filled('kategori') && $request->kategori !== 'all') {
            $manualQuery->where('kategori', $request->kategori);
        }

        if ($request->filled('tingkat_bahaya') && $request->tingkat_bahaya !== 'all') {
            $manualQuery->where('tingkat_bahaya', $request->tingkat_bahaya);
        }

        $manualMarkers = $manualQuery->get()->map(function ($m) {
            return [
                'id'             => $m->id,
                'latitude'       => (float) $m->latitude,
                'longitude'      => (float) $m->longitude,
                'tipe_marker'    => $m->tipe_marker ?? 'titik',
                'path_data'      => $m->path_data,
                'label'          => $m->label ?? $m->kategori,
                'kategori'       => strtoupper($m->kategori),
                'tingkat_bahaya' => $m->tingkat_bahaya ?? 'sedang',
                'radius'         => $m->radius ? (int) $m->radius : null,
                'dibuat_pada'    => $m->dibuat_pada ? $m->dibuat_pada->toIso8601String() : null,
                'source'         => 'manual',
            ];
        });

        // 2. Ambil laporan bencana yang terverifikasi (status = diverifikasi)
        $laporanQuery = \App\Models\LaporanBencana::with(['kecamatan', 'user'])
            ->where('is_draft', false)
            ->where('status', 'diverifikasi')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '!=', 0)
            ->where('longitude', '!=', 0)
            ->orderBy('dibuat_pada', 'desc');

        if ($request->filled('kategori') && $request->kategori !== 'all') {
            $kat = strtolower(str_replace('_', ' ', $request->kategori));
            $laporanQuery->where('jenis_bencana', 'like', '%' . $kat . '%');
        }

        $laporanMarkers = $laporanQuery->get()->map(function ($l) {
            $kecamatanName = $l->kecamatan?->nama;
            $label = $l->jenis_bencana;
            if ($kecamatanName) {
                $label .= ' - ' . $kecamatanName;
            } elseif ($l->alamat_lengkap) {
                $label .= ' - ' . $l->alamat_lengkap;
            }

            return [
                'id'             => $l->id,
                'latitude'       => (float) $l->latitude,
                'longitude'      => (float) $l->longitude,
                'tipe_marker'    => 'titik',
                'path_data'      => null,
                'label'          => $label,
                'kategori'       => strtoupper($l->jenis_bencana),
                'tingkat_bahaya' => 'sedang', // Default level untuk laporan dari warga
                'dibuat_pada'    => $l->dibuat_pada ? $l->dibuat_pada->toIso8601String() : null,
                'source'         => 'laporan',
            ];
        });

        // Filter tingkat bahaya pada hasil gabungan jika diperlukan
        if ($request->filled('tingkat_bahaya') && $request->tingkat_bahaya !== 'all') {
            if ($request->tingkat_bahaya !== 'sedang') {
                $laporanMarkers = collect();
            }
        }

        // 3. Ambil pos pengungsian yang aktif (tidak dalam status tutup)
        $posQuery = \App\Models\PosPengungsian::where('is_active', true)
            ->where('status', '!=', 'tutup');

        if ($request->filled('kategori') && $request->kategori !== 'all') {
            $normalizedKat = strtoupper(str_replace('_', ' ', $request->kategori));
            if ($normalizedKat !== 'POS PENGUNGSIAN') {
                $posQuery->whereRaw('0 = 1');
            }
        }

        if ($request->filled('tingkat_bahaya') && $request->tingkat_bahaya !== 'all') {
            if ($request->tingkat_bahaya !== 'sedang') {
                $posQuery->whereRaw('0 = 1');
            }
        }

        $posMarkers = $posQuery->get()->map(function ($pos) {
            return [
                'id'             => $pos->id,
                'latitude'       => (float) $pos->latitude,
                'longitude'      => (float) $pos->longitude,
                'tipe_marker'    => 'titik',
                'path_data'      => null,
                'label'          => $pos->nama,
                'kategori'       => 'POS PENGUNGSIAN',
                'tingkat_bahaya' => 'sedang',
                'dibuat_pada'    => $pos->updated_at ? $pos->updated_at->toIso8601String() : null,
                'source'         => 'pos_pengungsian',
                'status'         => $pos->status ?? 'standby',
                'kapasitas'      => (int) ($pos->kapasitas ?? 0),
                'terisi'         => (int) ($pos->terisi ?? 0),
            ];
        });

        // Gabungkan manual markers, verified laporan bencana, dan pos pengungsian
        $merged = $manualMarkers->concat($laporanMarkers)->concat($posMarkers)
            ->sortByDesc('dibuat_pada')
            ->values();

        return response()->json(['data' => $merged]);
    }

    /**
     * POST /api/admin/peta-marker
     * Admin: tambah marker baru
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude'       => 'required|numeric|between:-8.55,-7.85',
            'longitude'      => 'required|numeric|between:113.10,114.15',
            'tipe_marker'    => 'nullable|string|in:titik,garis,area',
            'path_data'      => 'nullable|array',
            'label'          => 'nullable|string|max:255',
            'kategori'       => 'required|string|max:100',
            'tingkat_bahaya' => 'required|in:rendah,sedang,tinggi,kritis',
            'radius'         => 'nullable|integer|min:0',
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
            'radius'         => $request->radius,
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
        // 1. Ambil manual markers yang aktif
        $manualQuery = PetaMarker::with('pembuat')
            ->where('is_active', true)
            ->orderBy('dibuat_pada', 'desc');

        if ($request->filled('kategori') && $request->kategori !== 'all') {
            $manualQuery->where('kategori', $request->kategori);
        }

        if ($request->filled('tingkat_bahaya') && $request->tingkat_bahaya !== 'all') {
            $manualQuery->where('tingkat_bahaya', $request->tingkat_bahaya);
        }

        $manualMarkers = $manualQuery->get()->map(function ($m) {
            return [
                'id'             => $m->id,
                'latitude'       => (float) $m->latitude,
                'longitude'      => (float) $m->longitude,
                'tipe_marker'    => $m->tipe_marker ?? 'titik',
                'path_data'      => $m->path_data,
                'label'          => $m->label ?? $m->kategori,
                'kategori'       => strtoupper($m->kategori),
                'tingkat_bahaya' => $m->tingkat_bahaya ?? 'sedang',
                'radius'         => $m->radius ? (int) $m->radius : null,
                'dibuat_pada'    => $m->dibuat_pada ? $m->dibuat_pada->toIso8601String() : null,
                'source'         => 'manual',
            ];
        });

        // 2. Ambil laporan bencana yang terverifikasi (status = diverifikasi)
        $laporanQuery = \App\Models\LaporanBencana::with(['kecamatan', 'user'])
            ->where('is_draft', false)
            ->where('status', 'diverifikasi')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '!=', 0)
            ->where('longitude', '!=', 0)
            ->orderBy('dibuat_pada', 'desc');

        if ($request->filled('kategori') && $request->kategori !== 'all') {
            $kat = strtolower(str_replace('_', ' ', $request->kategori));
            $laporanQuery->where('jenis_bencana', 'like', '%' . $kat . '%');
        }

        $laporanMarkers = $laporanQuery->get()->map(function ($l) {
            $kecamatanName = $l->kecamatan?->nama;
            $label = $l->jenis_bencana;
            if ($kecamatanName) {
                $label .= ' - ' . $kecamatanName;
            } elseif ($l->alamat_lengkap) {
                $label .= ' - ' . $l->alamat_lengkap;
            }

            return [
                'id'             => $l->id,
                'latitude'       => (float) $l->latitude,
                'longitude'      => (float) $l->longitude,
                'tipe_marker'    => 'titik',
                'path_data'      => null,
                'label'          => $label,
                'kategori'       => strtoupper($l->jenis_bencana),
                'tingkat_bahaya' => 'sedang',
                'dibuat_pada'    => $l->dibuat_pada ? $l->dibuat_pada->toIso8601String() : null,
                'source'         => 'laporan',
            ];
        });

        // Filter tingkat bahaya pada hasil gabungan jika diperlukan
        if ($request->filled('tingkat_bahaya') && $request->tingkat_bahaya !== 'all') {
            if ($request->tingkat_bahaya !== 'sedang') {
                $laporanMarkers = collect();
            }
        }

        // 3. Ambil pos pengungsian yang aktif (tidak dalam status tutup)
        $posQuery = \App\Models\PosPengungsian::where('is_active', true)
            ->where('status', '!=', 'tutup');

        if ($request->filled('kategori') && $request->kategori !== 'all') {
            $normalizedKat = strtoupper(str_replace('_', ' ', $request->kategori));
            if ($normalizedKat !== 'POS PENGUNGSIAN') {
                $posQuery->whereRaw('0 = 1');
            }
        }

        if ($request->filled('tingkat_bahaya') && $request->tingkat_bahaya !== 'all') {
            if ($request->tingkat_bahaya !== 'sedang') {
                $posQuery->whereRaw('0 = 1');
            }
        }

        $posMarkers = $posQuery->get()->map(function ($pos) {
            return [
                'id'             => $pos->id,
                'latitude'       => (float) $pos->latitude,
                'longitude'      => (float) $pos->longitude,
                'tipe_marker'    => 'titik',
                'path_data'      => null,
                'label'          => $pos->nama,
                'kategori'       => 'POS PENGUNGSIAN',
                'tingkat_bahaya' => 'sedang',
                'dibuat_pada'    => $pos->updated_at ? $pos->updated_at->toIso8601String() : null,
                'source'         => 'pos_pengungsian',
                'status'         => $pos->status ?? 'standby',
                'kapasitas'      => (int) ($pos->kapasitas ?? 0),
                'terisi'         => (int) ($pos->terisi ?? 0),
            ];
        });

        // Gabungkan manual markers, verified laporan bencana, dan pos pengungsian
        $merged = $manualMarkers->concat($laporanMarkers)->concat($posMarkers)
            ->sortByDesc('dibuat_pada')
            ->values();

        return response()->json(['data' => $merged]);
    }

    /**
     * GET /api/admin/peta-marker/{id}
     * Admin: ambil detail satu marker
     */
    public function show($id)
    {
        $marker = PetaMarker::with('pembuat')->findOrFail($id);
        return response()->json(['data' => $marker]);
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
            'latitude'       => 'nullable|numeric|between:-8.55,-7.85',
            'longitude'      => 'nullable|numeric|between:113.10,114.15',
            'tipe_marker'    => 'nullable|string|in:titik,garis,area',
            'path_data'      => 'nullable|array',
            'label'          => 'nullable|string|max:255',
            'kategori'       => 'nullable|string|max:100',
            'tingkat_bahaya' => 'nullable|in:rendah,sedang,tinggi,kritis',
            'radius'         => 'nullable|integer|min:0',
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
            'radius'         => $request->has('radius') ? $request->radius : null,
            'is_active'      => $request->has('is_active') ? $request->boolean('is_active') : null,
        ], fn($v) => $v !== null));

        return response()->json([
            'message' => 'Marker berhasil diperbarui.',
            'data'    => $marker,
        ]);
    }
}
