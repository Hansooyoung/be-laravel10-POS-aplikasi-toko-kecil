<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Get the authenticated user's data
     *
     * @param Request $request The incoming HTTP request
     * @return \Illuminate\Http\JsonResponse JSON response containing user details
     */
    public function user(Request $request)
    {
        // Get the currently authenticated user
        $user = auth()->user();

        // Return user details as JSON response
        return response()->json([
            'id' => $user->id,         // User's unique identifier
            'nama' => $user->nama,     // User's name
            'email' => $user->email,   // User's email address
            'role' => $user->role,     // User's role (e.g., admin, user)
        ]);
    }

    /**
     * Authenticate user and generate JWT token
     *
     * @param Request $request The incoming HTTP request containing credentials
     * @return \Illuminate\Http\JsonResponse JSON response with token/user data or error
     */
    public function login(Request $request)
    {
        // Validate incoming request data
        $request->validate([
            'email' => 'required|email',       // Must be a valid email
            'password' => 'required|string',    // Must be a string
        ]);

        // Extract only email and password from request
        $credentials = $request->only('email', 'password');

        try {
            // Attempt to generate JWT token using credentials
            if (!$token = JWTAuth::attempt($credentials)) {
                // Return error if credentials are invalid
                return response()->json(['message' => 'Email atau password salah'], 401);
            }
        } catch (JWTException $e) {
            // Return error if token creation fails
            return response()->json(['message' => 'Gagal membuat token'], 500);
        }

        // Get the authenticated user details
        $user = auth()->user();

        // Return successful login response with user data and token
        return response()->json([
            'message' => 'Login berhasil',  // Success message
            'user' => [                     // User details
                'id' => $user->id,
                'nama' => $user->nama,
                'email' => $user->email,
                'role' => $user->role,

            ],
            'token' => $token              // JWT token for authentication
        ], 200);
    }

    /**
     * Invalidate the current JWT token (logout)
     *
     * @param Request $request The incoming HTTP request
     * @return \Illuminate\Http\JsonResponse JSON response indicating logout status
     */
    public function logout(Request $request)
    {
        try {
            // Invalidate the current token (logout user)
            auth()->logout();

            // Return success message
            return response()->json(['message' => 'Logout berhasil'], 200);
        } catch (\Exception $e) {
            // Return error if logout fails
            return response()->json(['message' => 'Gagal logout'], 500);
        }
    }
}