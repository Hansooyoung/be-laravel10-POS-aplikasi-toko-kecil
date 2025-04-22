<?php

namespace Tests;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

trait AuthHelper
{
    // Data dasar untuk semua user
    protected $usersData = [
        'super' => [
            'email' => 'super@gmail.com',
            'nama' => 'Super Admin',
            'password' => 'super123',
            'role' => 'super'
        ],
        'admin' => [
            'email' => 'admin@gmail.com',
            'nama' => 'Admin User',
            'password' => 'admin123',
            'role' => 'admin'
        ],
        'member' => [
            'email' => 'member@gmail.com',
            'nama' => 'Regular Member',
            'password' => 'member123',
            'role' => 'member'
        ],
        'user' => [
            'email' => 'user@gmail.com',
            'nama' => 'Regular User',
            'password' => 'user123',
            'role' => 'user'
        ]
    ];

    /**
     * Membuat user berdasarkan role
     */
    protected function createUserByRole($role)
    {
        if (!array_key_exists($role, $this->usersData)) {
            throw new \InvalidArgumentException("Role $role tidak valid");
        }

        $userData = $this->usersData[$role];

        return User::firstOrCreate(
            ['email' => $userData['email']],
            [
                'nama' => $userData['nama'],
                'password' => Hash::make($userData['password']),
                'role' => $userData['role'],
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Mendapatkan token JWT berdasarkan role
     */
    protected function getTokenByRole($role)
    {
        $user = $this->createUserByRole($role);
        return JWTAuth::fromUser($user);
    }

    /**
     * Method untuk auth dengan role super admin
     */
    protected function withSuperAdminAuth()
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->getTokenByRole('super'),
            'Accept' => 'application/json'
        ]);
    }

    /**
     * Method untuk auth dengan role admin
     */
    protected function withAdminAuth()
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->getTokenByRole('admin'),
            'Accept' => 'application/json'
        ]);
    }

    /**
     * Method untuk auth dengan role member
     */
    protected function withMemberAuth()
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->getTokenByRole('member'),
            'Accept' => 'application/json'
        ]);
    }

    /**
     * Method untuk auth dengan role user biasa
     */
    protected function withUserAuth()
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->getTokenByRole('user'),
            'Accept' => 'application/json'
        ]);
    }

    /**
     * Method untuk auth dengan custom role
     */
    protected function withRoleAuth($role)
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->getTokenByRole($role),
            'Accept' => 'application/json'
        ]);
    }

    /**
     * Mendapatkan credentials untuk login (digunakan untuk testing login)
     */
    protected function getCredentialsByRole($role)
    {
        if (!array_key_exists($role, $this->usersData)) {
            throw new \InvalidArgumentException("Role $role tidak valid");
        }

        $userData = $this->usersData[$role];

        return [
            'email' => $userData['email'],
            'password' => $userData['password']
        ];
    }
}