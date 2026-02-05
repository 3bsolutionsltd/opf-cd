<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * Authenticate user with email and password.
     * 
     * Returns user data if credentials are valid, or validation error.
     * Uses Laravel Hash facade for password verification.
     * 
     * @param string $email
     * @param string $password
     * @return array ['success' => bool, 'user' => array|null, 'message' => string]
     */
    public function authenticate(string $email, string $password): array
    {
        $user = DB::table('users')
            ->where('email', $email)
            ->first();

        if (!$user) {
            return [
                'success' => false,
                'user' => null,
                'message' => 'Invalid credentials'
            ];
        }

        if (!Hash::check($password, $user->password_hash)) {
            return [
                'success' => false,
                'user' => null,
                'message' => 'Invalid credentials'
            ];
        }

        return [
            'success' => true,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'created_at' => $user->created_at
            ],
            'message' => 'Authentication successful'
        ];
    }
}
