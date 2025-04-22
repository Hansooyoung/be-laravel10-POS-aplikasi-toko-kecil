<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\User;
use Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $joneUser = User::where('email', 'jonesmith@example.com')->first();
        $janeUser = User::where('email', 'janesmith@example.com')->first();
        Member::insert([
            [
                'user_id' => $joneUser->id,
                'nama_member' => 'Jone Smith',
                'email' => 'jonesmith@example.com',
                'alamat' => 'Jl. Member No. 1',
                'no_hp' => '082240176504',
                'total_point' => 1000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $janeUser->id,
                'nama_member' => 'Jane Smith',
                'email' => 'janesmith@example.com',
                'alamat' => 'Jl. Member No. 2',
                'no_hp' => '083893181030',
                'total_point' => 2000,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
