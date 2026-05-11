<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FaqController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $faqs = Faq::orderBy('urutan', 'asc')->get();
        return response()->json([
            'success' => true,
            'message' => 'Daftar FAQ berhasil diambil',
            'data'    => $faqs
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pertanyaan' => 'required|string|max:512',
            'jawaban'    => 'required|string',
            'kategori'   => 'string|max:100',
            'urutan'     => 'integer',
            'is_active'  => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        $faq = Faq::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'FAQ berhasil dibuat',
            'data'    => $faq
        ], 210);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $faq = Faq::find($id);

        if (!$faq) {
            return response()->json([
                'success' => false,
                'message' => 'FAQ tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail FAQ berhasil diambil',
            'data'    => $faq
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $faq = Faq::find($id);

        if (!$faq) {
            return response()->json([
                'success' => false,
                'message' => 'FAQ tidak ditemukan'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'pertanyaan' => 'string|max:512',
            'jawaban'    => 'string',
            'kategori'   => 'string|max:100',
            'urutan'     => 'integer',
            'is_active'  => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        $faq->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'FAQ berhasil diupdate',
            'data'    => $faq
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $faq = Faq::find($id);

        if (!$faq) {
            return response()->json([
                'success' => false,
                'message' => 'FAQ tidak ditemukan'
            ], 404);
        }

        $faq->delete();

        return response()->json([
            'success' => true,
            'message' => 'FAQ berhasil dihapus'
        ]);
    }
}
