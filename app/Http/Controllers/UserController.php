<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Tampilkan daftar user dengan pagination (10 per halaman).
     */
    public function index()
    {
        $users = User::paginate(10);

        return response()->json([
            'message' => 'Data user berhasil diambil',
            'data' => $users
        ]);
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
            'role' => 'required|in:user,admin,super',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        return response()->json([
            'message' => 'User berhasil ditambahkan',
            'data' => $user
        ], 201);
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

    /**
     * Hapus user dengan pengecekan relasi ke barang dan pembelian.
     */
    public function destroy(User $user)
    {
        // Cek apakah user memiliki barang atau pembelian terkait
        if ($user->barang()->exists() || $user->pembelian()->exists()) {
            return response()->json([
                'error' => 'Tidak dapat menghapus user, karena masih memiliki data barang atau transaksi pembelian.'
            ], 400);
        }

        $user->delete();

        return response()->json([
            'message' => 'User berhasil dihapus'
        ]);
    }
}
