<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class PenggunaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $allowedRoles = ['masyarakat', 'admin_bpbd'];
        $query = User::query()->whereIn('role', $allowedRoles);

        // Pencarian (Search)
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter Role
        if (
            $request->has('role') &&
            $request->role != 'all' &&
            $request->role != '' &&
            in_array($request->role, $allowedRoles, true)
        ) {
            $query->where('role', $request->role);
        }

        // Sorting
        $query->orderBy('created_at', 'desc');

        // Pagination
        $perPage = $request->get('per_page', 10);
        $users = $query->paginate($perPage);

        return response()->json($users);
    }

    /**
     * Get user statistics.
     */
    public function stats()
    {
        $allowedRoles = ['masyarakat', 'admin_bpbd'];
        $total = User::whereIn('role', $allowedRoles)->count();
        $aktif = User::whereIn('role', $allowedRoles)->where('is_active', true)->count();
        $admin = User::where('role', 'admin_bpbd')->count();
        $masyarakat = User::where('role', 'masyarakat')->count();

        return response()->json([
            'total' => $total,
            'aktif' => $aktif,
            'admin' => $admin,
            'masyarakat' => $masyarakat,
        ]);
    }

    /**
     * Toggle the active status of a user.
     */
    public function toggleActive($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak ditemukan'
            ], 404);
        }

        // Cegah super admin menonaktifkan dirinya sendiri
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak dapat menonaktifkan akun Anda sendiri'
            ], 403);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Status pengguna berhasil diperbarui',
            'is_active' => $user->is_active
        ]);
    }

    /**
     * Display the specified resource with full detail.
     */
    public function show($id)
    {
        $user = User::find($id);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak ditemukan'
            ], 404);
        }

        // ── Statistik Poin ──────────────────────────────────────
        $userPoints = \DB::table('user_points')->where('user_id', $id)->first();

        // ── Statistik Laporan ────────────────────────────────────
        $totalLaporan    = \DB::table('laporan_bencana')->where('user_id', $id)->where('is_draft', 0)->count();
        $totalKomentar   = \DB::table('laporan_komentar')->where('user_id', $id)->count();

        // ── Aktivitas Terbaru ─────────────────────────────────────
        // Laporan terbaru (5)
        $laporanTerbaru = \DB::table('laporan_bencana')
            ->where('user_id', $id)
            ->where('is_draft', 0)
            ->orderByDesc('dibuat_pada')
            ->limit(5)
            ->get(['id', 'jenis_bencana', 'alamat_lengkap', 'status', 'dibuat_pada']);

        // Komentar terbaru (5)
        $komentarTerbaru = \DB::table('laporan_komentar')
            ->where('laporan_komentar.user_id', $id)
            ->join('laporan_bencana', 'laporan_komentar.laporan_id', '=', 'laporan_bencana.id')
            ->orderByDesc('laporan_komentar.dibuat_pada')
            ->limit(5)
            ->get([
                'laporan_komentar.id',
                'laporan_komentar.isi',
                'laporan_komentar.dibuat_pada',
                'laporan_bencana.jenis_bencana',
                'laporan_bencana.id as laporan_id',
            ]);

        // Transaksi poin terbaru (5)
        $pointTx = \DB::table('point_transactions')
            ->where('user_id', $id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'points', 'type', 'description', 'created_at']);

        // ── Gabungkan Feed Aktivitas ─────────────────────────────
        $feed = collect();

        foreach ($laporanTerbaru as $l) {
            $feed->push([
                'id'      => 'laporan-' . $l->id,
                'tipe'    => 'laporan',
                'judul'   => $l->jenis_bencana . ($l->alamat_lengkap ? ' — ' . \Str::limit($l->alamat_lengkap, 40) : ''),
                'deskripsi' => 'Laporan bencana dikirim · Status: ' . ucfirst($l->status),
                'waktu'   => $l->dibuat_pada,
            ]);
        }

        foreach ($komentarTerbaru as $k) {
            $feed->push([
                'id'      => 'komentar-' . $k->id,
                'tipe'    => 'komentar',
                'judul'   => 'Komentar pada laporan ' . $k->jenis_bencana,
                'deskripsi' => '"' . \Str::limit($k->isi, 60) . '"',
                'waktu'   => $k->dibuat_pada,
            ]);
        }

        foreach ($pointTx as $p) {
            $sign = $p->points >= 0 ? '+' : '';
            $feed->push([
                'id'      => 'poin-' . $p->id,
                'tipe'    => 'poin',
                'judul'   => $sign . $p->points . ' Poin — ' . ucfirst($p->type),
                'deskripsi' => $p->description ?? 'Transaksi poin',
                'waktu'   => $p->created_at,
            ]);
        }

        // Sort feed by waktu desc, ambil 10 teratas
        $feedSorted = $feed->sortByDesc('waktu')->values()->take(10);

        return response()->json([
            'user'   => $user,
            'points' => $userPoints ? [
                'total_points' => $userPoints->total_points,
                'updated_at'   => $userPoints->updated_at,
            ] : ['total_points' => 0, 'updated_at' => null],
            'stats'  => [
                'total_laporan'               => $totalLaporan,
                'total_komentar'              => $totalKomentar,
            ],
            'aktivitas' => $feedSorted,
        ]);
    }
}
