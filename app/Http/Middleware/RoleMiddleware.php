<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Illuminate\Support\Facades\Log; // Tambahkan ini untuk logging

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            // 🛠 DEBUGGING: Log informasi user dan role
            Log::info('RoleMiddleware Debugging:', [
                'user_id' => optional($user)->id,
                'user_role' => optional($user)->role,
                'expected_role' => $role,
            ]);

            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            // Jika user memiliki role yang sesuai atau superuser
            if ($user->role === $role || $user->role === 'super') {
                return $next($request);
            }

            return response()->json(['message' => 'Tidak Memiliki Akses' ], 403);

        } catch (TokenExpiredException $e) {
            return response()->json(['message' => 'Token kadaluarsa'], 401);
        } catch (TokenInvalidException $e) {
            return response()->json(['message' => 'Token tidak valid'], 401);
        } catch (JWTException $e) {
            return response()->json(['message' => 'Token tidak ditemukan'], 401);
        }
    }
}
