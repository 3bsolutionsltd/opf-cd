<?php

namespace App\Services;

class SessionService
{
    /**
     * Create session for authenticated user.
     * 
     * Stores user ID, email, and role in session.
     * 
     * @param array $userData ['id' => int, 'email' => string, 'role' => string]
     * @return void
     */
    public function createSession(array $userData): void
    {
        session([
            'user_id' => $userData['id'],
            'user_email' => $userData['email'],
            'user_role' => $userData['role']
        ]);
    }

    /**
     * Destroy current session.
     * 
     * Clears all session data and invalidates session.
     * 
     * @return void
     */
    public function destroySession(): void
    {
        session()->flush();
        session()->invalidate();
        session()->regenerateToken();
    }

    /**
     * Check if user is authenticated.
     * 
     * Returns true if session contains user_id, false otherwise.
     * 
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        return session()->has('user_id');
    }

    /**
     * Get current authenticated user ID.
     * 
     * Returns user ID from session or null if not authenticated.
     * 
     * @return int|null
     */
    public function getCurrentUserId(): ?int
    {
        return session('user_id');
    }
}
