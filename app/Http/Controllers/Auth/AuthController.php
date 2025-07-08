<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\LoginAttempt;
use App\Models\BlacklistedIp;
use App\Models\UserActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Analytics Hub Authentication Controller
 *
 * Handles user authentication with comprehensive security features including:
 * - IP tracking and blacklisting
 * - Failed login attempt counting
 * - Session management with timeout
 * - Activity logging and monitoring
 * - Remember me functionality
 * - CSRF protection
 *
 * Security Features:
 * - Rate limiting (5 attempts per minute per IP)
 * - IP blacklisting after 30 failed attempts
 * - Account locking after repeated failures
 * - Session fingerprinting
 * - Comprehensive audit logging
 */
class AuthController extends Controller
{
    /**
     * Maximum failed login attempts before account lock
     */
    const MAX_FAILED_ATTEMPTS = 30;

    /**
     * Login rate limit (attempts per minute)
     */
    const LOGIN_RATE_LIMIT = 5;

    /**
     * Session timeout in minutes
     */
    const SESSION_TIMEOUT = 30;

    /**
     * Show the login form
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showLoginForm(Request $request)
    {
        // Check if user is already authenticated
        if (Auth::check()) {
            return redirect()->intended('/dashboard');
        }

        // Check if IP is blacklisted
        $ipAddress = $request->ip();
        if ($this->isIpBlacklisted($ipAddress)) {
            Log::channel('analytics_security')->warning('Blacklisted IP attempted to access login', [
                'ip_address' => $ipAddress,
                'user_agent' => $request->userAgent(),
                'timestamp' => now(),
            ]);

            // Return generic error to avoid information disclosure
            abort(403, 'Access denied.');
        }

        return view('auth.login', [
            'title' => 'Analytics Hub - Login',
        ]);
    }

    /**
     * Handle user login attempt
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();
        $email = $request->input('email');

        // Check if IP is blacklisted
        if ($this->isIpBlacklisted($ipAddress)) {
            $this->logLoginAttempt($request, null, false, 'ip_blacklisted');

            Log::channel('analytics_security')->warning('Blacklisted IP attempted login', [
                'ip_address' => $ipAddress,
                'email' => $email,
                'user_agent' => $userAgent,
            ]);

            return $this->failedLoginResponse('Access denied.');
        }

        // Rate limiting check
        $rateLimitKey = 'login:' . $ipAddress;
        if (RateLimiter::tooManyAttempts($rateLimitKey, self::LOGIN_RATE_LIMIT)) {
            $this->logLoginAttempt($request, null, false, 'rate_limited');

            $seconds = RateLimiter::availableIn($rateLimitKey);
            return $this->failedLoginResponse("Too many login attempts. Please try again in {$seconds} seconds.");
        }

        // Validate input
        $this->validateLogin($request);

        // Find user by email
        $user = User::where('email', $email)->first();

        // Check if user exists and account is not locked
        if (!$user) {
            RateLimiter::hit($rateLimitKey);
            $this->logLoginAttempt($request, null, false, 'user_not_found');
            return $this->failedLoginResponse('Invalid credentials.');
        }

        // Check if user account is locked
        if ($user->is_locked) {
            $this->logLoginAttempt($request, $user, false, 'account_locked');
            return $this->failedLoginResponse('Account is locked. Please contact administrator.');
        }

        // Check user status
        if ($user->status === 'suspended') {
            $this->logLoginAttempt($request, $user, false, 'account_suspended');
            return $this->failedLoginResponse('Account is suspended. Please contact administrator.');
        }

        // Verify password
        if (!Hash::check($request->input('password'), $user->password)) {
            RateLimiter::hit($rateLimitKey);

            // Increment failed attempts
            $user->incrementFailedAttempts();

            // Check if account should be locked
            if ($user->failed_login_attempts >= self::MAX_FAILED_ATTEMPTS) {
                $this->addIpToBlacklist($ipAddress, $user->id, 'excessive_failed_attempts');
            }

            $this->logLoginAttempt($request, $user, false, 'invalid_password');

            Log::channel('analytics_security')->info('Failed login attempt', [
                'user_id' => $user->id,
                'email' => $email,
                'ip_address' => $ipAddress,
                'failed_attempts' => $user->failed_login_attempts,
            ]);

            return $this->failedLoginResponse('Invalid credentials.');
        }

        // Clear rate limiting on successful login
        RateLimiter::clear($rateLimitKey);

        // Reset failed attempts
        $user->resetFailedAttempts();

        // Update last login info
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $ipAddress,
            'last_login_user_agent' => $userAgent,
        ]);

        // Log successful login attempt
        $this->logLoginAttempt($request, $user, true, null);

        // Create session with fingerprinting
        $this->createSecureSession($request, $user);

        // Log user activity
        UserActivity::logActivity([
            'user_id' => $user->id,
            'activity_type' => 'login',
            'description' => 'User logged in successfully',
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'metadata' => [
                'browser' => $this->getBrowserFromUserAgent($userAgent),
                'os' => $this->getOSFromUserAgent($userAgent),
            ],
        ]);

        Log::channel('analytics_activity')->info('User logged in', [
            'user_id' => $user->id,
            'email' => $email,
            'ip_address' => $ipAddress,
            'timestamp' => now(),
        ]);

        // Check if this is first login
        if ($user->is_first_login) {
            return redirect()->route('auth.first-login');
        }

        // Check if terms need to be accepted
        if (!$user->has_accepted_terms) {
            return redirect()->route('auth.terms');
        }

        // Check if password is expired
        if ($user->is_password_expired) {
            return redirect()->route('auth.password-expired');
        }

        return redirect()->intended('/dashboard');
    }

    /**
     * Handle user logout
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            // Log logout activity
            UserActivity::logActivity([
                'user_id' => $user->id,
                'activity_type' => 'logout',
                'description' => 'User logged out',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            Log::channel('analytics_activity')->info('User logged out', [
                'user_id' => $user->id,
                'email' => $user->email,
                'session_duration' => $user->last_login_at ?
                    now()->diffInMinutes($user->last_login_at) : null,
            ]);
        }

        // Clear session data
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('status', 'You have been logged out successfully.');
    }

    /**
     * Validate login request
     *
     * @param Request $request
     * @throws ValidationException
     */
    protected function validateLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|max:255',
            'remember' => 'boolean',
        ], [
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
        ]);
    }

    /**
     * Create secure session for authenticated user
     *
     * @param Request $request
     * @param User $user
     */
    protected function createSecureSession(Request $request, User $user)
    {
        // Login user
        Auth::login($user, $request->boolean('remember'));

        // Regenerate session to prevent fixation
        $request->session()->regenerate();

        // Set session timeout
        $request->session()->put('last_activity', time());
        $request->session()->put('session_expires_at', time() + (self::SESSION_TIMEOUT * 60));

        // Create session fingerprint for security
        $fingerprint = $this->createSessionFingerprint($request);
        $request->session()->put('session_fingerprint', $fingerprint);

        // Store additional security info
        $request->session()->put('login_ip', $request->ip());
        $request->session()->put('login_user_agent', $request->userAgent());
        $request->session()->put('login_timestamp', now());
    }

    /**
     * Create session fingerprint for security
     *
     * @param Request $request
     * @return string
     */
    protected function createSessionFingerprint(Request $request)
    {
        $components = [
            $request->ip(),
            $request->userAgent(),
            $request->header('Accept-Language'),
            $request->header('Accept-Encoding'),
        ];

        return hash('sha256', implode('|', array_filter($components)));
    }

    /**
     * Check if IP address is blacklisted
     *
     * @param string $ipAddress
     * @return bool
     */
    protected function isIpBlacklisted($ipAddress)
    {
        return BlacklistedIp::where('ip_address', $ipAddress)
                           ->where('is_active', true)
                           ->where(function ($query) {
                               $query->whereNull('expires_at')
                                     ->orWhere('expires_at', '>', now());
                           })
                           ->exists();
    }

    /**
     * Add IP address to blacklist
     *
     * @param string $ipAddress
     * @param string|null $userId
     * @param string $reason
     */
    protected function addIpToBlacklist($ipAddress, $userId = null, $reason = 'excessive_failed_attempts')
    {
        BlacklistedIp::create([
            'ip_address' => $ipAddress,
            'user_id' => $userId,
            'reason' => $reason,
            'is_active' => true,
            'blocked_at' => now(),
            'expires_at' => now()->addHours(24), // 24-hour ban
            'metadata' => [
                'user_agent' => request()->userAgent(),
                'auto_blacklisted' => true,
            ],
        ]);

        Log::channel('analytics_security')->warning('IP address blacklisted', [
            'ip_address' => $ipAddress,
            'user_id' => $userId,
            'reason' => $reason,
            'expires_at' => now()->addHours(24),
        ]);
    }

    /**
     * Log login attempt for security monitoring
     *
     * @param Request $request
     * @param User|null $user
     * @param bool $success
     * @param string|null $failureReason
     */
    protected function logLoginAttempt(Request $request, $user, $success, $failureReason = null)
    {
        $data = [
            'user_id' => $user ? $user->id : null,
            'email' => $request->input('email'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'success' => $success,
            'failure_reason' => $failureReason,
            'session_id' => session()->getId(),
            'location' => $this->getLocationFromIp($request->ip()),
            'metadata' => [
                'browser' => $this->getBrowserFromUserAgent($request->userAgent()),
                'os' => $this->getOSFromUserAgent($request->userAgent()),
                'timestamp' => now(),
            ],
        ];

        LoginAttempt::create($data);
    }

    /**
     * Return failed login response
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    protected function failedLoginResponse($message)
    {
        if (request()->expectsJson()) {
            return response()->json([
                'message' => $message,
                'errors' => ['email' => [$message]],
            ], 422);
        }

        return back()->withErrors([
            'email' => $message,
        ])->withInput(request()->except('password'));
    }

    /**
     * Get location from IP address (stub for GeoIP service)
     *
     * @param string $ipAddress
     * @return array
     */
    protected function getLocationFromIp($ipAddress)
    {
        // This would integrate with a GeoIP service like MaxMind
        return [
            'country' => 'Unknown',
            'country_code' => 'XX',
            'region' => 'Unknown',
            'city' => 'Unknown',
        ];
    }

    /**
     * Extract browser from user agent
     *
     * @param string $userAgent
     * @return string
     */
    protected function getBrowserFromUserAgent($userAgent)
    {
        if (!$userAgent) return 'Unknown';

        $browsers = [
            'Chrome' => 'Chrome',
            'Firefox' => 'Firefox',
            'Safari' => 'Safari',
            'Edge' => 'Edge',
            'Opera' => 'Opera',
        ];

        foreach ($browsers as $browser => $identifier) {
            if (str_contains($userAgent, $identifier)) {
                return $browser;
            }
        }

        return 'Unknown';
    }

    /**
     * Extract OS from user agent
     *
     * @param string $userAgent
     * @return string
     */
    protected function getOSFromUserAgent($userAgent)
    {
        if (!$userAgent) return 'Unknown';

        $systems = [
            'Windows' => 'Windows',
            'MacOS' => 'Macintosh',
            'Linux' => 'Linux',
            'Android' => 'Android',
            'iOS' => 'iPhone|iPad',
        ];

        foreach ($systems as $os => $identifier) {
            if (preg_match("/$identifier/i", $userAgent)) {
                return $os;
            }
        }

        return 'Unknown';
    }
}
