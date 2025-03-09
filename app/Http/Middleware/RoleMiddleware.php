<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

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
