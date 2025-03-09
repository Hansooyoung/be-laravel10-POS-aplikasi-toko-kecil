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
            $query->where('nama_member', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('email', 'LIKE', '%' . $request->search . '%');
        }

        $members = $query->paginate(10);

        return response()->json([
            'message' => 'Data member berhasil diambil',
            'data' => $members
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_member' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:member,email',
            'no_hp' => 'required|string|max:15',
            'password' => 'required|string|min:6',
        ]);

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
