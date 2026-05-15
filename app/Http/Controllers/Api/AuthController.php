<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserAuth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Register a new user.
     * Creates a record in `users` and `user_auth` tables.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        // Create user in `users` table
        $user = User::create([
            'name'  => $validated['name'],
            'email' => $validated['email'],
            'role'  => 'masyarakat', // Default role
        ]);

        // Create auth record in `user_auth` table
        UserAuth::create([
            'user_id'  => $user->id,
            'provider' => 'local',
            'password' => $validated['password'], // 'hashed' cast di model yang akan hash otomatis
        ]);

        return response()->json([
            'message' => 'Registrasi berhasil. Silakan login.',
            'user'    => $user,
        ], 201);
    }

    /**
     * Login with email and password.
     * Lookup user → verify password from user_auth → create session.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Find the user by email
        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json([
                'message' => 'Email tidak ditemukan.',
            ], 401);
        }

        // Check if user is active
        if (!$user->is_active) {
            return response()->json([
                'message' => 'Akun Anda telah dinonaktifkan. Hubungi administrator.',
            ], 403);
        }

        // Hanya admin yang diizinkan login ke dashboard web
        if (!in_array($user->role, ['admin_bpbd', 'super_admin'])) {
            return response()->json([
                'message' => 'Akses ditolak. Halaman ini hanya untuk Admin BPBD dan Super Admin.',
            ], 403);
        }

        // Get local auth record
        $localAuth = $user->localAuth;

        if (!$localAuth || !Hash::check($validated['password'], $localAuth->password)) {
            return response()->json([
                'message' => 'Email atau password salah.',
            ], 401);
        }

        // Login the user (creates session)
        Auth::login($user, $request->boolean('remember'));

        // Regenerate session to prevent fixation
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Login berhasil.',
            'user'    => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'role'       => $user->role,
                'alamat'     => $user->alamat,
                'no_telepon' => $user->no_telepon,
                'foto_url'   => $user->foto_url,
            ],
        ]);
    }

    /**
     * Logout the current user.
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logout berhasil.',
        ]);
    }

    /**
     * Get the authenticated user's data.
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'role'       => $user->role,
                'alamat'     => $user->alamat,
                'no_telepon' => $user->no_telepon,
                'foto_url'   => $user->foto_url,
            ],
        ]);
    }

    /**
     * Update the authenticated user's profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'name'                  => ['sometimes', 'required', 'string', 'max:255'],
            'alamat'                => ['sometimes', 'nullable', 'string'],
            'no_telepon'            => ['sometimes', 'nullable', 'string', 'max:20'],
            'foto'                  => ['sometimes', 'nullable', 'image', 'mimes:jpeg,png,jpg', 'max:5120'],
            'old_password'          => ['sometimes', 'nullable', 'string'],
            'password'              => ['sometimes', 'nullable', 'string', 'min:8', 'confirmed'],
        ]);

        // Handle Password Change
        if ($request->filled('password')) {
            // Query langsung agar mendapatkan kolom password (tidak terpengaruh $hidden)
            $localAuth = \App\Models\UserAuth::where('user_id', $user->id)
                            ->where('provider', 'local')
                            ->first();

            // Pastikan user punya password lokal
            if (!$localAuth) {
                return response()->json([
                    'message' => 'Akun ini tidak memiliki password lokal. Gunakan metode login lain.',
                ], 422);
            }

            // Verifikasi password lama menggunakan getRawOriginal agar tidak terkena hidden
            $hashedPassword = $localAuth->getRawOriginal('password') ?? $localAuth->getAttributes()['password'] ?? null;

            if (!$request->filled('old_password') || !Hash::check($request->input('old_password'), $hashedPassword)) {
                return response()->json([
                    'message' => 'Password lama tidak sesuai.',
                ], 422);
            }

            // Simpan password baru (cast 'hashed' di model UserAuth akan auto-hash)
            $localAuth->password = $request->input('password');
            $localAuth->save();
        }

        // Handle Photo Upload
        if ($request->hasFile('foto')) {
            // Delete old photo if exists
            if ($user->foto) {
                Storage::disk('public')->delete($user->foto);
            }

            // Store new photo
            $path = $request->file('foto')->store('uploads/profil', 'public');
            $user->foto = $path;
        }

        $user->name       = $request->input('name', $user->name);
        $user->alamat     = $request->input('alamat', $user->alamat);
        $user->no_telepon = $request->input('no_telepon', $user->no_telepon);
        $user->save();

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'user'    => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'role'       => $user->role,
                'alamat'     => $user->alamat,
                'no_telepon' => $user->no_telepon,
                'foto_url'   => $user->foto_url,
            ]
        ]);
    }

    // =============================================
    // MOBILE / TOKEN-BASED AUTH
    // =============================================

    /**
     * Login for mobile apps (returns API token).
     * Use this endpoint for Flutter/React Native apps.
     */
    public function loginMobile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string'], // e.g., "iPhone 12", "Samsung Galaxy"
        ]);

        // Find the user by email
        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json([
                'message' => 'Email tidak ditemukan.',
            ], 401);
        }

        // Check if user is active
        if (!$user->is_active) {
            return response()->json([
                'message' => 'Akun Anda telah dinonaktifkan.',
            ], 403);
        }

        // Get local auth record
        $localAuth = $user->localAuth;

        if (!$localAuth || !Hash::check($validated['password'], $localAuth->password)) {
            return response()->json([
                'message' => 'Email atau password salah.',
            ], 401);
        }

        // Create API token for mobile
        $token = $user->createToken($validated['device_name'])->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil.',
            'token' => $token,
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
                'foto_url' => $user->foto_url,
            ],
        ]);
    }

    /**
     * Logout for mobile apps (revoke current token).
     */
    public function logoutMobile(Request $request): JsonResponse
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil.',
        ]);
    }

    /**
     * Logout from all devices (revoke all tokens).
     */
    public function logoutAllDevices(Request $request): JsonResponse
    {
        // Revoke all tokens for the user
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logout dari semua perangkat berhasil.',
        ]);
    }
}
