<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Donasi;
use App\Models\KampanyeDonasi;
use App\Models\NotifikasiMidtrans;
use App\Models\PenyaluranDonasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminDonasiController extends Controller
{
    public function kampanyeIndex(Request $request): JsonResponse
    {
        $query = KampanyeDonasi::with(['kecamatan:id,nama', 'laporanBencana:id,jenis_bencana,status'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                    ->orWhere('jenis_bencana', 'like', "%{$search}%");
            });
        }

        return response()->json($query->paginate(min((int) $request->input('per_page', 10), 50)));
    }

    public function kampanyeStats(): JsonResponse
    {
        return response()->json([
            'total' => KampanyeDonasi::count(),
            'aktif' => KampanyeDonasi::where('status', 'aktif')->count(),
            'draft' => KampanyeDonasi::where('status', 'draft')->count(),
            'ditutup' => KampanyeDonasi::where('status', 'ditutup')->count(),
            'total_terkumpul' => KampanyeDonasi::sum('total_terkumpul'),
            'total_disalurkan' => KampanyeDonasi::sum('total_disalurkan'),
        ]);
    }

    public function kampanyeShow(string $id): JsonResponse
    {
        $kampanye = KampanyeDonasi::with(['kecamatan:id,nama', 'laporanBencana:id,jenis_bencana,status,alamat_lengkap'])
            ->findOrFail($id);

        return response()->json(['data' => $kampanye]);
    }

    public function kampanyeStore(Request $request): JsonResponse
    {
        $validated = $request->validate($this->kampanyeRules());
        $validated['dibuat_oleh'] = $request->user()->id;
        $validated['gambar'] = $this->storeFile($request, 'gambar', 'uploads/donasi/kampanye');

        $kampanye = KampanyeDonasi::create($validated);

        return response()->json([
            'message' => 'Kampanye donasi berhasil dibuat.',
            'data' => $kampanye->load('kecamatan:id,nama'),
        ], 201);
    }

    public function kampanyeUpdate(Request $request, string $id): JsonResponse
    {
        $kampanye = KampanyeDonasi::findOrFail($id);
        $validated = $request->validate($this->kampanyeRules(true));

        if ($request->hasFile('gambar')) {
            $validated['gambar'] = $this->storeFile($request, 'gambar', 'uploads/donasi/kampanye');
        }

        $kampanye->update($validated);

        return response()->json([
            'message' => 'Kampanye donasi berhasil diperbarui.',
            'data' => $kampanye->fresh()->load('kecamatan:id,nama'),
        ]);
    }

    public function transaksiIndex(Request $request): JsonResponse
    {
        $query = Donasi::with(['kampanye:id,judul', 'user:id,name,email', 'pembayaran'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('kampanye_id')) {
            $query->where('kampanye_id', $request->kampanye_id);
        }

        if ($request->filled('metode_bayar')) {
            $query->whereHas('pembayaran', fn ($q) => $q->where('metode_bayar', $request->metode_bayar));
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        return response()->json($query->paginate(min((int) $request->input('per_page', 10), 50)));
    }

    public function transaksiShow(string $id): JsonResponse
    {
        $donasi = Donasi::with(['kampanye:id,judul', 'user:id,name,email,no_telepon', 'pembayaran'])->findOrFail($id);

        return response()->json(['data' => $donasi]);
    }

    public function penyaluranIndex(Request $request): JsonResponse
    {
        $query = PenyaluranDonasi::with(['kampanye:id,judul,total_terkumpul,total_disalurkan', 'pembuat:id,name'])
            ->orderByDesc('tanggal_penyaluran');

        if ($request->filled('kampanye_id')) {
            $query->where('kampanye_id', $request->kampanye_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate(min((int) $request->input('per_page', 10), 50)));
    }

    public function penyaluranShow(string $id): JsonResponse
    {
        $penyaluran = PenyaluranDonasi::with(['kampanye:id,judul,total_terkumpul,total_disalurkan'])
            ->findOrFail($id);

        return response()->json(['data' => $penyaluran]);
    }

    public function penyaluranStore(Request $request): JsonResponse
    {
        $validated = $request->validate($this->penyaluranRules());
        $validated['dibuat_oleh'] = $request->user()->id;
        $validated['bukti'] = $this->storeFile($request, 'bukti', 'uploads/donasi/penyaluran');

        $penyaluran = DB::transaction(function () use ($validated) {
            $this->assertPenyaluranAllowed($validated['kampanye_id'], (float) $validated['nominal'], $validated['status']);
            $penyaluran = PenyaluranDonasi::create($validated);
            if ($penyaluran->status === 'publish') {
                $penyaluran->kampanye()->increment('total_disalurkan', (float) $penyaluran->nominal);
            }
            return $penyaluran;
        });

        return response()->json([
            'message' => 'Penyaluran donasi berhasil dibuat.',
            'data' => $penyaluran->load('kampanye:id,judul'),
        ], 201);
    }

    public function penyaluranUpdate(Request $request, string $id): JsonResponse
    {
        $penyaluran = PenyaluranDonasi::findOrFail($id);
        $validated = $request->validate($this->penyaluranRules(true));

        if ($request->hasFile('bukti')) {
            $validated['bukti'] = $this->storeFile($request, 'bukti', 'uploads/donasi/penyaluran');
        }

        DB::transaction(function () use ($penyaluran, $validated) {
            $oldKampanye = KampanyeDonasi::where('id', $penyaluran->kampanye_id)->lockForUpdate()->firstOrFail();
            $newKampanyeId = $validated['kampanye_id'] ?? $penyaluran->kampanye_id;
            $newKampanye = $newKampanyeId === $penyaluran->kampanye_id
                ? $oldKampanye
                : KampanyeDonasi::where('id', $newKampanyeId)->lockForUpdate()->firstOrFail();

            $oldPublishNominal = $penyaluran->status === 'publish' ? (float) $penyaluran->nominal : 0;
            $newStatus = $validated['status'] ?? $penyaluran->status;
            $newNominal = (float) ($validated['nominal'] ?? $penyaluran->nominal);
            $newPublishNominal = $newStatus === 'publish' ? $newNominal : 0;

            if ($newKampanye->id === $oldKampanye->id) {
                $delta = $newPublishNominal - $oldPublishNominal;
                if ($delta > 0 && ((float) $oldKampanye->total_disalurkan + $delta) > (float) $oldKampanye->total_terkumpul) {
                    abort(422, 'Nominal penyaluran melebihi dana terkumpul.');
                }

                $penyaluran->update($validated);
                if ($delta > 0) {
                    $oldKampanye->increment('total_disalurkan', $delta);
                } elseif ($delta < 0) {
                    $oldKampanye->decrement('total_disalurkan', abs($delta));
                }
                return;
            }

            if ($newPublishNominal > (float) $newKampanye->total_terkumpul - (float) $newKampanye->total_disalurkan) {
                abort(422, 'Nominal penyaluran melebihi dana terkumpul.');
            }

            $penyaluran->update($validated);
            if ($oldPublishNominal > 0) {
                $oldKampanye->decrement('total_disalurkan', $oldPublishNominal);
            }
            if ($newPublishNominal > 0) {
                $newKampanye->increment('total_disalurkan', $newPublishNominal);
            }
        });

        return response()->json([
            'message' => 'Penyaluran donasi berhasil diperbarui.',
            'data' => $penyaluran->fresh()->load('kampanye:id,judul'),
        ]);
    }

    public function notifikasiIndex(Request $request): JsonResponse
    {
        $query = NotifikasiMidtrans::orderByDesc('diterima_pada');

        if ($request->filled('status_proses')) {
            $query->where('status_proses', $request->status_proses);
        }

        if ($request->filled('order_id')) {
            $query->where('order_id', 'like', '%' . $request->order_id . '%');
        }

        return response()->json($query->paginate(min((int) $request->input('per_page', 10), 50)));
    }

    private function kampanyeRules(bool $partial = false): array
    {
        $required = $partial ? 'sometimes|required' : 'required';

        return [
            'judul' => [$required, 'string', 'max:255'],
            'deskripsi' => [$required, 'string'],
            'jenis_bencana' => [$required, 'string', 'max:100'],
            'kecamatan_id' => ['nullable', 'exists:kecamatan,id'],
            'laporan_bencana_id' => ['nullable', 'exists:laporan_bencana,id'],
            'target_donasi' => ['nullable', 'numeric', 'min:0'],
            'gambar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'tanggal_mulai' => [$required, 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'status' => [$required, Rule::in(['draft', 'aktif', 'ditutup'])],
        ];
    }

    private function penyaluranRules(bool $partial = false): array
    {
        $required = $partial ? 'sometimes|required' : 'required';

        return [
            'kampanye_id' => [$required, 'exists:kampanye_donasi,id'],
            'judul' => [$required, 'string', 'max:255'],
            'deskripsi' => [$required, 'string'],
            'nominal' => [$required, 'numeric', 'min:1'],
            'penerima' => [$required, 'string', 'max:255'],
            'tanggal_penyaluran' => [$required, 'date'],
            'bukti' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'status' => [$required, Rule::in(['draft', 'publish'])],
        ];
    }

    private function storeFile(Request $request, string $field, string $folder): ?string
    {
        if (!$request->hasFile($field)) {
            return null;
        }

        $file = $request->file($field);
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->storeAs($folder, $filename, 'public');

        return $folder . '/' . $filename;
    }

    private function assertPenyaluranAllowed(string $kampanyeId, float $nominal, string $status): void
    {
        if ($status !== 'publish') {
            return;
        }

        $kampanye = KampanyeDonasi::where('id', $kampanyeId)->lockForUpdate()->firstOrFail();
        if (((float) $kampanye->total_disalurkan + $nominal) > (float) $kampanye->total_terkumpul) {
            abort(422, 'Nominal penyaluran melebihi dana terkumpul.');
        }
    }
}
