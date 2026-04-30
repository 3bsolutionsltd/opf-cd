<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetService
{
    // Token expires after 60 minutes
    const TOKEN_EXPIRY_MINUTES = 60;

    /**
     * Send a password reset link to the given email address.
     *
     * Always returns a success-like message to prevent user enumeration.
     *
     * @param string $email
     * @return array ['success' => bool, 'message' => string]
     */
    public function sendResetLink(string $email): array
    {
        $user = DB::table('users')->where('email', $email)->where('is_active', true)->first();

        // Return generic message regardless of whether email exists (prevent enumeration)
        if (!$user) {
            return ['success' => true, 'message' => 'If that email is registered, a reset link has been sent.'];
        }

        // Throttle: block if a token was sent in the last 60 seconds
        $recent = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->where('created_at', '>=', now()->subMinute())
            ->exists();

        if ($recent) {
            return ['success' => false, 'message' => 'Please wait before requesting another reset link.'];
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        $resetUrl = url('/reset-password/' . $token . '?email=' . urlencode($email));
        $appName = config('app.name', 'OPF-CD');

        Mail::send([], [], function ($message) use ($email, $resetUrl, $appName) {
            $message->to($email)
                ->subject("[$appName] Password Reset Request")
                ->html(
                    "<p>You requested a password reset for your $appName account.</p>"
                    . "<p><a href=\"$resetUrl\">Click here to reset your password</a></p>"
                    . "<p>This link expires in " . self::TOKEN_EXPIRY_MINUTES . " minutes.</p>"
                    . "<p>If you did not request this, ignore this email.</p>"
                );
        });

        return ['success' => true, 'message' => 'If that email is registered, a reset link has been sent.'];
    }

    /**
     * Verify a password reset token.
     *
     * @param string $email
     * @param string $token
     * @return bool
     */
    public function verifyToken(string $email, string $token): bool
    {
        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (!$record) {
            return false;
        }

        if (now()->diffInMinutes($record->created_at) > self::TOKEN_EXPIRY_MINUTES) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return false;
        }

        return Hash::check($token, $record->token);
    }

    /**
     * Reset the user's password.
     *
     * @param string $email
     * @param string $token
     * @param string $password
     * @return array ['success' => bool, 'message' => string]
     */
    public function resetPassword(string $email, string $token, string $password): array
    {
        if (!$this->verifyToken($email, $token)) {
            return ['success' => false, 'message' => 'This reset link is invalid or has expired.'];
        }

        DB::table('users')
            ->where('email', $email)
            ->update(['password_hash' => Hash::make($password), 'updated_at' => now()]);

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        return ['success' => true, 'message' => 'Password reset successfully.'];
    }
}
