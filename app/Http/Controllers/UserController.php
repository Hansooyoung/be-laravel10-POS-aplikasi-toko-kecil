<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    /**
     * Tampilkan daftar user dengan pagination (10 per halaman).
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Filter pencarian berdasarkan nama atau email
        if ($request->has('search') && !empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('nama', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('email', 'LIKE', '%' . $request->search . '%');
            });
        }

        // Filter berdasarkan role, contoh: ?role=admin
        if ($request->has('role') && !empty($request->role)) {
            $query->where('role', $request->role);
        }

        $users = $query->paginate(10);

        return response()->json([
            'message' => 'Data user berhasil diambil',
            'data' => $users
        ]);
    }


    /**
     * Tampilkan user berdasarkan ID.
     */
    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User tidak ditemukan'
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'message' => 'User berhasil ditemukan',
            'data' => $user
        ], Response::HTTP_OK);
    }

    /**
     * Simpan user baru ke database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:50',
            'email' => 'required|email|unique:user,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:user,admin,super,operator',
        ]);


        $user = User::create($validated);

        return response()->json([
            'message' => 'User berhasil ditambahkan',
            'data' => $user
        ], Response::HTTP_CREATED);
    }

    /**
     * Perbarui data user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'nama' => 'sometimes|required|string|max:50',
            'email' => [
                'sometimes', 'required', 'email',
                Rule::unique('user', 'email')->ignore($user->id),
            ],
            'password' => 'nullable|string|min:6',
            'role' => 'sometimes|required|in:user,admin,super',
        ]);

        if ($request->filled('password')) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'User berhasil diperbarui',
            'data' => $user
        ]);
    }
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan.'], Response::HTTP_NOT_FOUND);
        }

        // Cek apakah user memiliki barang atau pembelian terkait
        if ($user->barang()->exists() || $user->pembelian()->exists()) {
            return response()->json([
                'message' => 'Tidak dapat menghapus user, karena masih memiliki data barang atau transaksi pembelian.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $user->delete();

        return response()->json([
            'message' => 'User berhasil dihapus'
        ], Response::HTTP_OK);
    }
}
