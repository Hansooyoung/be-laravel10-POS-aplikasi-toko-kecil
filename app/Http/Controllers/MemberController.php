<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $query = Member::query();

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where('email', 'LIKE', "%$search%")
                  ->orWhere('no_hp', 'LIKE', "%$search%");
        }

        $members = $query->paginate(10);

        return response()->json([
            'message' => 'Data member berhasil diambil',
            'data' => $members
        ]);
    }


    public function store(Request $request)
    {
        $messages = [
            'nama_member.required' => 'Nama member wajib diisi.',
            'nama_member.string' => 'Nama member harus berupa teks.',
            'nama_member.max' => 'Nama member maksimal 255 karakter.',

            'email.required' => 'Email wajib diisi.',
            'email.string' => 'Email harus berupa teks.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email maksimal 255 karakter.',
            'email.unique' => 'Email sudah terdaftar.',

            'no_hp.required' => 'Nomor HP wajib diisi.',
            'no_hp.string' => 'Nomor HP harus berupa teks.',
            'no_hp.max' => 'Nomor HP maksimal 15 karakter.',

            'password.required' => 'Password wajib diisi.',
            'password.string' => 'Password harus berupa teks.',
            'password.min' => 'Password minimal 6 karakter.',
        ];

        $validated = $request->validate([
            'nama_member' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:member,email',
            'no_hp' => 'required|string|max:15',
            'password' => 'required|string|min:6',
        ], $messages);

        $validated['password'] = Hash::make($validated['password']);
        $validated['total_point'] = 0;

        $member = Member::create($validated);

        return response()->json([
            'message' => 'Member berhasil ditambahkan',
            'data' => $member
        ], 201);
    }


    public function show($id)
    {
        $member = Member::find($id);

        if (!$member) {
            return response()->json([
                'message' => 'Member tidak ditemukan'
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'message' => 'Member berhasil ditemukan',
            'data' => $member
        ], Response::HTTP_OK);
    }

    public function update(Request $request, Member $member)
    {
        $validated = $request->validate([
            'nama_member' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:member,email,' . $member->id,
            'no_hp' => 'sometimes|required|string|max:15',
            'password' => 'sometimes|required|string|min:6',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $member->update($validated);

        return response()->json([
            'message' => 'Member berhasil diperbarui',
            'data' => $member
        ]);
    }

    public function destroy($id)
    {
        $member = Member::find($id);

        if (!$member) {
            return response()->json(['message' => 'Member tidak ditemukan.'], Response::HTTP_NOT_FOUND);
        }

        $member->delete();

        return response()->json(['message' => 'Member berhasil dihapus.'], Response::HTTP_OK);
    }
}
