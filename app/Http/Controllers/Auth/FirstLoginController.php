<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserActivity;
use App\Services\PasswordService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * First Login Controller
 *
 * Handles the first login flow for users who need to:
 * - Change their password
 * - Accept terms and conditions
 * - Complete their profile setup
 */
class FirstLoginController extends Controller
{
    protected $passwordService;

    public function __construct(PasswordService $passwordService)
    {
        $this->passwordService = $passwordService;
    }

    /**
     * Show the first login form
     *
     * @return \Illuminate\View\View
     */
    public function showFirstLoginForm()
    {
        $user = Auth::user();

        if (!$user || !$user->is_first_login) {
            return redirect()->route('dashboard');
        }

        return view('auth.first-login', [
            'title' => 'Analytics Hub - First Login Setup',
            'user' => $user,
        ]);
    }

    /**
     * Handle first login completion
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function completeFirstLogin(Request $request)
    {
        $user = Auth::user();

        if (!$user || !$user->is_first_login) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request.'
            ], 400);
        }

        // Validate the request
        $validator = Validator::make($request->all(), [
            'new_password' => [
                'required',
                'string',
                'min:12',
                'confirmed',
                function ($attribute, $value, $fail) use ($user) {
                    $result = $this->passwordService->validatePassword($value, $user);
                    if (!$result['valid']) {
                        $fail($result['message']);
                    }
                }
            ],
            'terms_accepted' => 'required|accepted',
            'full_name' => 'nullable|string|max:255',
            'timezone' => 'nullable|string|max:50',
            'language' => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Update user password
            $user->update([
                'password' => Hash::make($request->new_password),
                'password_changed_at' => now(),
            ]);

            // Store password in history
            $this->passwordService->storePasswordHistory($user, $request->new_password);

            // Accept terms
            $user->acceptTerms();

            // Update profile information if provided
            $profileData = [];
            if ($request->filled('full_name')) {
                $profileData['full_name'] = $request->full_name;
            }
            if ($request->filled('timezone')) {
                $profileData['timezone'] = $request->timezone;
            }
            if ($request->filled('language')) {
                $profileData['language'] = $request->language;
            }

            if (!empty($profileData)) {
                $user->update($profileData);
            }

            // Mark first login as complete
            $user->markFirstLoginComplete();

            // Log the activity
            UserActivity::logActivity([
                'user_id' => $user->id,
                'activity_type' => 'first_login_complete',
                'description' => 'User completed first login setup',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => [
                    'password_changed' => true,
                    'terms_accepted' => true,
                    'profile_updated' => !empty($profileData),
                ],
            ]);

            Log::channel('analytics_activity')->info('First login completed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip_address' => $request->ip(),
                'timestamp' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'First login setup completed successfully.',
                'redirect' => route('dashboard')
            ]);

        } catch (\Exception $e) {
            Log::error('First login setup failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during setup. Please try again.'
            ], 500);
        }
    }

    /**
     * Show password expiry form
     *
     * @return \Illuminate\View\View
     */
    public function showPasswordExpiredForm()
    {
        $user = Auth::user();

        if (!$user || !$user->is_password_expired) {
            return redirect()->route('dashboard');
        }

        return view('auth.password-expired', [
            'title' => 'Analytics Hub - Password Expired',
            'user' => $user,
        ]);
    }

    /**
     * Handle password change for expired passwords
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeExpiredPassword(Request $request)
    {
        $user = Auth::user();

        if (!$user || !$user->is_password_expired) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request.'
            ], 400);
        }

        // Validate the request
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => [
                'required',
                'string',
                'min:12',
                'confirmed',
                function ($attribute, $value, $fail) use ($user) {
                    $result = $this->passwordService->validatePassword($value, $user);
                    if (!$result['valid']) {
                        $fail($result['message']);
                    }
                }
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.'
            ], 422);
        }

        try {
            // Update user password
            $user->update([
                'password' => Hash::make($request->new_password),
                'password_changed_at' => now(),
            ]);

            // Store password in history
            $this->passwordService->storePasswordHistory($user, $request->new_password);

            // Log the activity
            UserActivity::logActivity([
                'user_id' => $user->id,
                'activity_type' => 'password_change',
                'description' => 'User changed expired password',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => [
                    'reason' => 'expired_password',
                ],
            ]);

            Log::channel('analytics_activity')->info('Expired password changed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip_address' => $request->ip(),
                'timestamp' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully.',
                'redirect' => route('dashboard')
            ]);

        } catch (\Exception $e) {
            Log::error('Password change failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while changing password. Please try again.'
            ], 500);
        }
    }
}
