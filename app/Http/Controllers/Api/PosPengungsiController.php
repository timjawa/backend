<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PosPengungsian;
use Illuminate\Http\Request;

class PosPengungsiController extends Controller
{
    public function index(Request $request)
    {
        $query = PosPengungsian::with('kecamatan:id,nama');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('alamat', 'like', "%{$search}%")
                  ->orWhereHas('kecamatan', fn($k) => $k->where('nama', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('kecamatan_id')) {
            $query->where('kecamatan_id', $request->kecamatan_id);
        }

        $query->orderBy('nama');

        $perPage = $request->get('per_page', 10);
        return response()->json($query->paginate($perPage));
    }

    public function stats()
    {
        $total   = PosPengungsian::count();
        $aktif   = PosPengungsian::where('status', 'aktif')->count();
        $standby = PosPengungsian::where('status', 'standby')->count();
        $penuh   = PosPengungsian::where('status', 'penuh')->count();
        $tutup   = PosPengungsian::where('status', 'tutup')->count();

        $totalKapasitas = PosPengungsian::sum('kapasitas');
        $totalTerisi    = PosPengungsian::sum('terisi');

        return response()->json([
            'total'           => $total,
            'aktif'           => $aktif,
            'standby'         => $standby,
            'penuh'           => $penuh,
            'tutup'           => $tutup,
            'total_kapasitas' => $totalKapasitas,
            'total_terisi'    => $totalTerisi,
        ]);
    }

    public function show($id)
    {
        $pos = PosPengungsian::with('kecamatan:id,nama')->find($id);
        if (!$pos) {
            return response()->json(['message' => 'Pos pengungsian tidak ditemukan'], 404);
        }
        return response()->json($pos);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama'              => ['required', 'string', 'max:255'],
            'kecamatan_id'      => ['nullable', 'string', 'exists:kecamatan,id'],
            'alamat'            => ['nullable', 'string', 'max:255'],
            'latitude'          => ['required', 'numeric'],
            'longitude'         => ['required', 'numeric'],
            'kapasitas'         => ['required', 'integer', 'min:0'],
            'terisi'            => ['nullable', 'integer', 'min:0'],
            'fasilitas'         => ['nullable', 'array'],
            'status'            => ['nullable', 'in:standby,aktif,penuh,tutup'],
            'penanggung_jawab'  => ['nullable', 'string', 'max:255'],
            'telepon'           => ['nullable', 'string', 'max:30'],
            'is_active'         => ['nullable', 'boolean'],
        ]);

        $pos = PosPengungsian::create($validated);
        return response()->json(['message' => 'Pos pengungsian berhasil ditambahkan', 'data' => $pos], 201);
    }

    public function update(Request $request, $id)
    {
        $pos = PosPengungsian::find($id);
        if (!$pos) {
            return response()->json(['message' => 'Pos pengungsian tidak ditemukan'], 404);
        }

        $validated = $request->validate([
            'nama'              => ['sometimes', 'required', 'string', 'max:255'],
            'kecamatan_id'      => ['nullable', 'string', 'exists:kecamatan,id'],
            'alamat'            => ['nullable', 'string', 'max:255'],
            'latitude'          => ['sometimes', 'required', 'numeric'],
            'longitude'         => ['sometimes', 'required', 'numeric'],
            'kapasitas'         => ['sometimes', 'required', 'integer', 'min:0'],
            'terisi'            => ['nullable', 'integer', 'min:0'],
            'fasilitas'         => ['nullable', 'array'],
            'status'            => ['nullable', 'in:standby,aktif,penuh,tutup'],
            'penanggung_jawab'  => ['nullable', 'string', 'max:255'],
            'telepon'           => ['nullable', 'string', 'max:30'],
            'is_active'         => ['nullable', 'boolean'],
        ]);

        $pos->update($validated);
        return response()->json(['message' => 'Pos pengungsian berhasil diperbarui', 'data' => $pos]);
    }

    public function destroy($id)
    {
        $pos = PosPengungsian::find($id);
        if (!$pos) {
            return response()->json(['message' => 'Pos pengungsian tidak ditemukan'], 404);
        }
        $pos->delete();
        return response()->json(['message' => 'Pos pengungsian berhasil dihapus']);
    }
}
