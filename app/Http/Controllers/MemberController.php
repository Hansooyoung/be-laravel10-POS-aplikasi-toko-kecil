<?php

    namespace App\Http\Controllers;

    use App\Models\Log;
    use App\Models\Member;
    use App\Models\User;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Hash;
    use Symfony\Component\HttpFoundation\Response;
    use Illuminate\Support\Facades\DB;
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
                'email' => 'required|string|email|max:255|unique:user,email',
                'no_hp' => 'required|string|max:15|unique:member,no_hp',
                'password' => 'required|string|min:6',
                'alamat' => 'required|string|max:255'
            ], $messages);

            DB::beginTransaction();
            try {
                // Buat user terlebih dahulu
                $user = User::create([
                    'nama' => $validated['nama_member'],
                    'email' => $validated['email'],
                    'password' => $validated['password'],
                    'role' => 'member'
                ]);
                if (!$user) {
                    DB::rollBack();
                    return response()->json(['message' => 'Gagal membuat user'], 500);
                }
                // Buat member setelah user berhasil dibuat
                $member = Member::create([
                    'user_id' => $user->id,
                    'nama_member' => $validated['nama_member'],
                    'email' => $validated['email'],
                    'no_hp' => $validated['no_hp'],
                    'password' => $user->password, // Menggunakan password yang sudah di-hash
                    'alamat' => $validated['alamat']
                ]);

                DB::commit();
                return response()->json([
                    'message' => 'Member berhasil ditambahkan',
                    'data' => $member
                ], 201);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Terjadi kesalahan saat menambahkan member',
                    'error' => $e->getMessage()
                ], 500);
            }
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
