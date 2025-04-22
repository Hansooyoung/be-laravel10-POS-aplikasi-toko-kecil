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
                'no_hp.unique' => 'Nomor HP sudah terdaftar.',

                'password.required' => 'Password wajib diisi.',
                'password.string' => 'Password harus berupa teks.',
                'password.min' => 'Password minimal 6 karakter.',

                'alamat.required' => 'Alamat wajib diisi.',
                'alamat.string' => 'Alamat harus berupa teks.',
                'alamat.max' => 'Alamat maksimal 255 karakter.'
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
                // 1. Buat user terlebih dahulu dengan password yang di-hash
                $user = User::create([
                    'nama' => $validated['nama_member'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']), // Pastikan password di-hash
                    'role' => 'member',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                if (!$user) {
                    throw new \Exception('Gagal membuat user');
                }

                // 2. Buat member setelah user berhasil dibuat
                $member = Member::create([
                    'user_id' => $user->id,
                    'nama_member' => $validated['nama_member'],
                    'email' => $validated['email'],
                    'no_hp' => $validated['no_hp'],
                    'alamat' => $validated['alamat'],
                    'total_point' => 0, // Default point 0
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                DB::commit();

                return response()->json([
                    'message' => 'Member berhasil didaftarkan',
                    'data' => [
                        'user' => $user,
                        'member' => $member
                    ]
                ], 201);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Gagal mendaftarkan member',
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
                'nama_member' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|string|email|max:255|unique:member,email,' . $member->id,
                'no_hp' => 'sometimes|required|string|max:15',
                'password' => 'sometimes|required|string|min:6',
                'alamat' => 'sometimes|required|string|max:255'
            ], $messages);

            DB::beginTransaction();
            try {
                // 1. Update data member (tanpa password)
                $memberData = $validated;
                if (isset($memberData['password'])) {
                    unset($memberData['password']); // Hapus password dari data member
                }

                $member->update($memberData);

                // 2. Jika ada perubahan password, update di tabel user
                if (isset($validated['password'])) {
                    $user = $member->user; // Ambil relasi user dari member
                    if ($user) {
                        $user->update([
                            'password' => Hash::make($validated['password']),
                            'updated_at' => now()
                        ]);
                    } else {
                        throw new \Exception('User terkait tidak ditemukan');
                    }
                }

                // 3. Jika ada perubahan email, update juga di tabel user
                if (isset($validated['email'])) {
                    $user = $member->user;
                    if ($user) {
                        // Pastikan email unik di tabel user
                        if (User::where('email', $validated['email'])->where('id', '!=', $user->id)->exists()) {
                            throw new \Exception('Email sudah digunakan oleh user lain');
                        }

                        $user->update([
                            'email' => $validated['email'],
                            'updated_at' => now()
                        ]);
                    }
                }

                DB::commit();

                return response()->json([
                    'message' => 'Data member berhasil diperbarui',
                    'data' => $member->refresh() // Reload data terbaru
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Gagal memperbarui data member',
                    'error' => $e->getMessage()
                ], 500);
            }
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
