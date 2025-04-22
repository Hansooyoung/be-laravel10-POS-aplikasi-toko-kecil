<?php

namespace Database\Seeders;

use App\Models\User;
use Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
            User::insert([
            [
                'nama' => 'Super Admin',
                'email' => 'super@gmail.com',
                'password' => Hash::make('super123'),
                'role' => 'super',
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Manager 1',
                'email' => 'admin@gmail.com',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Kasir 1',
                'email' => 'user@gmail.com',
                'password' => Hash::make('user123'),
                'role' => 'user',
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Kasir 2',
                'email' => 'user2@gmail.com',
                'password' => Hash::make('user123'),
                'role' => 'user',
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Operator 1',
                'email' => 'operator@gmail.com',
                'password' => Hash::make('operator123'),
                'role' => 'operator',
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Operator 2',
                'email' => 'operator2@gmail.com',
                'password' => Hash::make('operator123'),
                'role' => 'operator',
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Jane Smith Member',
                'email' => 'janesmith@example.com',
                'password' => Hash::make('password123'),
                'role' => 'member',
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Jone Smith Member',
                'email' => 'jonesmith@example.com',
                'password' => Hash::make('password123'),
                'role' => 'member',
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ]);
    }
}
