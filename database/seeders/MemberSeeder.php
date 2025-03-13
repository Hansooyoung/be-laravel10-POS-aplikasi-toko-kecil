<?php

namespace Database\Seeders;

use App\Models\Member;
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
        Member::insert([
            [
                'nama_member' => 'John Doe',
                'email' => 'johndoe@example.com',
                'no_hp' => '081234567891',
                'password' => Hash::make('password123'),
                'total_point' => 1000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_member' => 'Jane Smith',
                'email' => 'janesmith@example.com',
                'no_hp' => '081234567892',
                'password' => Hash::make('password123'),
                'total_point' => 2000,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
