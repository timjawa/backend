<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Kreait\Firebase\Factory;

class AuthFirebaseController extends Controller
{
    public function verifyFirebase(Request $request)
    {
        // 1. Inisialisasi Firebase Admin SDK
        $factory = (new Factory)->withServiceAccount(
            config('firebase.credentials')
        );
        
        // Tambahkan validasi issuer dan audience
        $auth = $factory->createAuth();
        
        // Debug: Cek project ID dari credentials
        $serviceAccount = json_decode(file_get_contents(config('firebase.credentials')), true);
        $projectId = $serviceAccount['project_id'] ?? 'unknown';
        
        // Log untuk debugging (hapus di production)
        \Log::info('Firebase Project ID from backend: ' . $projectId);

        try {
            // Validasi input
            if (!$request->has('idToken')) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'idToken is required',
                ], 400);
            }

            // Debug: Decode token manual untuk melihat isinya
            $tokenParts = explode('.', $request->idToken);
            if (count($tokenParts) === 3) {
                $header = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[0])), true);
                $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[1])), true);
                
                \Log::info('Token Header: ' . json_encode($header));
                \Log::info('Token Payload: ' . json_encode($payload));
            }

            // 2. Verifikasi ID Token dari Flutter dengan options yang benar
            // Coba tanpa parameter tambahan
            $verifiedIdToken = $auth->verifyIdToken($request->idToken);
            $firebaseUid     = $verifiedIdToken->claims()->get('sub');
            $email           = $verifiedIdToken->claims()->get('email');
            $displayName     = $request->displayName
                ?? $verifiedIdToken->claims()->get('name')
                ?? 'User';

            // 3. Cek apakah sudah ada entri di user_auth untuk provider Firebase ini
            $userAuth = UserAuth::where('provider', 'firebase')
                ->where('provider_id', $firebaseUid)
                ->first();

            if ($userAuth) {
                // Pengguna sudah terdaftar — update nama jika berubah
                $user = $userAuth->user;
                $user->update([
                    'name'         => $displayName,
                    'full_name'    => $displayName,
                    'firebase_uid' => $firebaseUid,
                ]);
            } else {
                // Cek apakah email sudah terdaftar via metode lain
                $user = User::where('email', $email)->first();

                if (!$user) {
                    // Buat user baru di tabel users
                    $user = User::create([
                        'name'         => $displayName,
                        'full_name'    => $displayName,
                        'firebase_uid' => $firebaseUid,
                        'email'        => $email,
                        'role'         => 'masyarakat',
                        'is_active'    => true,
                    ]);
                } else {
                    // User sudah ada via email — link firebase_uid
                    $user->update([
                        'firebase_uid' => $firebaseUid,
                        'full_name'    => $user->full_name ?? $displayName,
                    ]);
                }

                // Buat entri di user_auth untuk provider 'firebase'
                UserAuth::create([
                    'user_id'     => $user->id,
                    'provider'    => 'firebase',
                    'provider_id' => $firebaseUid,
                    'password'    => Hash::make(Str::random(24)),
                ]);
            }

            // 4. Buat Token Sanctum untuk sesi API lokal
            $token = $user->createToken('firebase_auth_token')->plainTextToken;

            return response()->json([
                'status'  => 'success',
                'message' => 'Autentikasi Firebase berhasil',
                'user'    => [
                    'id'        => $user->id,
                    'name'      => $user->name,
                    'full_name' => $user->full_name,
                    'email'     => $user->email,
                    'role'      => $user->role,
                ],
                'token' => $token,
            ]);

        } catch (\Kreait\Firebase\Exception\AuthException $e) {
            // Decode token untuk debugging issuer dan audience
            $tokenParts = explode('.', $request->idToken);
            $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[1])), true);
            
            return response()->json([
                'status'  => 'error',
                'message' => 'Token Firebase tidak valid: ' . $e->getMessage(),
                'debug'   => [
                    'error_type' => get_class($e),
                    'error_code' => $e->getCode(),
                    'backend_project_id' => $projectId,
                    'token_issuer' => $payload['iss'] ?? 'unknown',
                    'token_audience' => $payload['aud'] ?? 'unknown',
                    'expected_issuer' => "https://securetoken.google.com/$projectId",
                    'expected_audience' => $projectId,
                ]
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan server: ' . $e->getMessage(),
            ], 500);
        }
    }
}