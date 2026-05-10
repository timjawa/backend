<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PeringatanDini;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

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
            'berlaku_hingga' => 'nullable|date',
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
            'berlaku_hingga' => $request->berlaku_hingga ? Carbon::parse($request->berlaku_hingga) : null,
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
            'berlaku_hingga' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $peringatan->update([
            'kecamatan_id' => $request->kecamatan_id,
            'deskripsi' => $request->deskripsi,
            'tingkat_urgensi' => $request->tingkat_urgensi,
            'berlaku_hingga' => $request->berlaku_hingga ? Carbon::parse($request->berlaku_hingga) : null,
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
