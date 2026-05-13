<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     * Hanya admin_bpbd, admin_bmkg (opsional), dan super_admin (sesuai enum users.role).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Akses ditolak. Hanya admin yang dapat mengakses.',
            ], 403);
        }

        return $next($request);
    }
}
