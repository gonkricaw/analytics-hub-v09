<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PasswordReset;
use App\Services\PasswordService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Password Reset Controller
 *
 * Handles password reset functionality with security measures including:
 * - Rate limiting (30-second cooldown between requests)
 * - UUID-based reset tokens with 120-minute expiry
 * - Email verification and secure token delivery
 * - Password validation and history tracking
 * - Comprehensive logging and audit trail
 *
 * Security Features:
 * - Rate limiting to prevent abuse
 * - Secure token generation and validation
 * - Token expiry enforcement
 * - Password strength validation
 * - History tracking to prevent reuse
 * - IP tracking and logging
 */
class PasswordResetController extends Controller
{
    /**
     * Password reset service instance
     */
    private PasswordService $passwordService;

    /**
     * Token expiry time in minutes
     */
    const TOKEN_EXPIRY_MINUTES = 120;

    /**
     * Rate limit cooldown in seconds
     */
    const RATE_LIMIT_COOLDOWN = 30;

    /**
     * Constructor
     *
     * @param PasswordService $passwordService
     */
    public function __construct(PasswordService $passwordService)
    {
        $this->passwordService = $passwordService;
    }

    /**
     * Show forgot password form
     *
     * @return \Illuminate\View\View
     */
    public function showForgotPasswordForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Send password reset email
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function sendResetLink(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $email = $request->input('email');
        $clientIp = $request->ip();

        // Check rate limiting
        $rateLimitKey = 'password-reset:' . $clientIp;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 1)) {
            $remainingSeconds = RateLimiter::availableIn($rateLimitKey);

            return response()->json([
                'success' => false,
                'message' => "Too many password reset attempts. Please wait {$remainingSeconds} seconds before trying again."
            ], 429);
        }

        // Find user
        $user = User::where('email', $email)->first();

        if (!$user) {
            // Still apply rate limiting even for non-existent users
            RateLimiter::hit($rateLimitKey, self::RATE_LIMIT_COOLDOWN);

            return response()->json([
                'success' => false,
                'message' => 'If an account with that email exists, you will receive a password reset link.'
            ]);
        }

        // Check if user is active
        if ($user->status !== 'active') {
            RateLimiter::hit($rateLimitKey, self::RATE_LIMIT_COOLDOWN);

            return response()->json([
                'success' => false,
                'message' => 'Account is not active. Please contact administrator.'
            ]);
        }

        // Generate reset token
        $token = Str::uuid()->toString();
        $expiresAt = now()->addMinutes(self::TOKEN_EXPIRY_MINUTES);

        // Store reset token (delete any existing tokens for this user)
        PasswordReset::where('email', $email)->delete();

        PasswordReset::create([
            'email' => $email,
            'token' => Hash::make($token),
            'expires_at' => $expiresAt,
            'ip_address' => $clientIp,
            'user_agent' => $request->userAgent()
        ]);

        // Send reset email
        try {
            Mail::to($user->email)->send(new \App\Mail\PasswordResetMail($user, $token));

            // Apply rate limiting after successful send
            RateLimiter::hit($rateLimitKey, self::RATE_LIMIT_COOLDOWN);

            // Log password reset request
            activity()
                ->causedBy($user)
                ->withProperties([
                    'ip_address' => $clientIp,
                    'user_agent' => $request->userAgent(),
                    'expires_at' => $expiresAt
                ])
                ->log('password_reset_requested');

            return response()->json([
                'success' => true,
                'message' => 'Password reset link has been sent to your email address.'
            ]);

        } catch (\Exception $e) {
            \Log::error('Password reset email failed', [
                'email' => $email,
                'error' => $e->getMessage(),
                'ip' => $clientIp
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send password reset email. Please try again later.'
            ], 500);
        }
    }

    /**
     * Show password reset form
     *
     * @param Request $request
     * @param string $token
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showResetForm(Request $request, string $token)
    {
        $email = $request->query('email');

        if (!$email) {
            return redirect()->route('login')->withErrors(['email' => 'Invalid reset link.']);
        }

        // Validate token
        $resetRecord = PasswordReset::where('email', $email)
            ->where('expires_at', '>', now())
            ->first();

        if (!$resetRecord || !Hash::check($token, $resetRecord->token)) {
            return redirect()->route('login')->withErrors(['email' => 'Invalid or expired reset link.']);
        }

        return view('auth.reset-password', compact('token', 'email'));
    }

    /**
     * Reset password
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function resetPassword(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $email = $request->input('email');
        $token = $request->input('token');
        $password = $request->input('password');
        $clientIp = $request->ip();

        // Find reset record
        $resetRecord = PasswordReset::where('email', $email)
            ->where('expires_at', '>', now())
            ->first();

        if (!$resetRecord || !Hash::check($token, $resetRecord->token)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired reset token.'
            ], 400);
        }

        // Find user
        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);
        }

        // Validate password using password service
        $passwordValidation = $this->passwordService->validatePassword($password, $user);

        if (!$passwordValidation['success']) {
            return response()->json([
                'success' => false,
                'errors' => ['password' => $passwordValidation['errors']]
            ], 422);
        }

        // Update password
        $updateResult = $this->passwordService->updatePassword($user, $password);

        if (!$updateResult['success']) {
            return response()->json([
                'success' => false,
                'errors' => ['password' => $updateResult['errors']]
            ], 422);
        }

        // Clear reset token
        $resetRecord->delete();

        // Clear any existing sessions for this user
        \DB::table('sessions')->where('user_id', $user->id)->delete();

        // Log password reset completion
        activity()
            ->causedBy($user)
            ->withProperties([
                'ip_address' => $clientIp,
                'user_agent' => $request->userAgent(),
                'password_strength' => $updateResult['strength']
            ])
            ->log('password_reset_completed');

        return response()->json([
            'success' => true,
            'message' => 'Password has been reset successfully. You can now log in with your new password.',
            'redirect' => route('login')
        ]);
    }

    /**
     * Validate reset token
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateToken(Request $request)
    {
        $token = $request->input('token');
        $email = $request->input('email');

        if (!$token || !$email) {
            return response()->json([
                'success' => false,
                'message' => 'Token and email are required.'
            ], 400);
        }

        $resetRecord = PasswordReset::where('email', $email)
            ->where('expires_at', '>', now())
            ->first();

        if (!$resetRecord || !Hash::check($token, $resetRecord->token)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired reset token.'
            ], 400);
        }

        $expiresIn = now()->diffInMinutes($resetRecord->expires_at);

        return response()->json([
            'success' => true,
            'message' => 'Token is valid.',
            'expires_in_minutes' => $expiresIn
        ]);
    }

    /**
     * Cancel password reset
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelReset(Request $request)
    {
        $email = $request->input('email');
        $token = $request->input('token');

        if (!$email || !$token) {
            return response()->json([
                'success' => false,
                'message' => 'Email and token are required.'
            ], 400);
        }

        $resetRecord = PasswordReset::where('email', $email)->first();

        if ($resetRecord && Hash::check($token, $resetRecord->token)) {
            $resetRecord->delete();

            // Log cancellation
            if ($user = User::where('email', $email)->first()) {
                activity()
                    ->causedBy($user)
                    ->withProperties([
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent()
                    ])
                    ->log('password_reset_cancelled');
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Password reset cancelled.'
        ]);
    }
}
