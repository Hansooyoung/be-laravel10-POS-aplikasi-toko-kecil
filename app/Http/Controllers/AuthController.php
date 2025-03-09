<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{

    public function user(Request $request)
{
    return response()->json(auth()->user());
}

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');

        $token = JWTAuth::attempt($credentials);

        try {
            if (!$token) {
                return response()->json(['message' => 'Email atau password salah'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['message' => 'Gagal membuat token'], 500);
        }

        $user = auth()->user();

        return response()->json([
            'message' => 'Login berhasil',
            'user' => $user,
            'token' => $token
        ], 200);
    }


    public function logout(Request $request)
    {
        // Hapus token autentikasi pengguna yang sedang login
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil'
        ], 200);
    }
}
