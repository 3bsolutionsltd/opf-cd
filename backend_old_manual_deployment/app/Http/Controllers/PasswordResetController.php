<?php

namespace App\Http\Controllers;

use App\Services\PasswordResetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PasswordResetController extends Controller
{
    protected $passwordResetService;

    public function __construct(PasswordResetService $passwordResetService)
    {
        $this->passwordResetService = $passwordResetService;
    }

    /**
     * Show the forgot password form.
     */
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    /**
     * Send a password reset link to the given email.
     */
    public function sendResetLink(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid email address.',
            ], 422);
        }

        $result = $this->passwordResetService->sendResetLink($request->input('email'));

        $status = $result['success'] ? 200 : 429;

        return response()->json($result, $status);
    }

    /**
     * Show the password reset form for a given token.
     */
    public function showResetForm(Request $request, string $token)
    {
        $email = $request->query('email', '');

        return view('auth.reset-password', compact('token', 'email'));
    }

    /**
     * Process the password reset.
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'                 => 'required|email',
            'token'                 => 'required|string',
            'password'              => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $result = $this->passwordResetService->resetPassword(
            $request->input('email'),
            $request->input('token'),
            $request->input('password')
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }
}
