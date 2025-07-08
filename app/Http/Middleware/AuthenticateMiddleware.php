<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Models\UserActivity;
use App\Models\BlacklistedIp;

/**
 * Analytics Hub Authentication Middleware
 *
 * Comprehensive authentication middleware that handles:
 * - User authentication verification
 * - Session timeout management (30 minutes)
 * - Session fingerprinting for security
 * - IP blacklist checking
 * - User status validation
 * - Activity logging
 *
 * Security Features:
 * - Session fingerprint validation
 * - Automatic logout on session timeout
 * - IP blacklist enforcement
 * - Comprehensive audit logging
 */
class AuthenticateMiddleware
{
    /**
     * Session timeout in minutes
     */
    const SESSION_TIMEOUT = 30;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string|null  ...$guards
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();

        // Check if IP is blacklisted
        if ($this->isIpBlacklisted($ipAddress)) {
            Log::channel('analytics_security')->warning('Blacklisted IP attempted access', [
                'ip_address' => $ipAddress,
                'url' => $request->fullUrl(),
                'user_agent' => $userAgent,
                'timestamp' => now(),
            ]);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(403, 'Access denied.');
        }

        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->guest(route('login'));
        }

        $user = Auth::user();

        // Validate session timeout
        if ($this->isSessionExpired($request)) {
            Log::channel('analytics_activity')->info('Session expired', [
                'user_id' => $user->id,
                'session_duration' => $request->session()->get('last_activity') ?
                    time() - $request->session()->get('last_activity') : null,
                'ip_address' => $ipAddress,
            ]);

            // Log activity
            UserActivity::logActivity([
                'user_id' => $user->id,
                'activity_type' => 'session_expired',
                'description' => 'Session expired due to inactivity',
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                           ->with('status', 'Your session has expired. Please log in again.');
        }

        // Validate session fingerprint
        if (!$this->validateSessionFingerprint($request)) {
            Log::channel('analytics_security')->warning('Session fingerprint mismatch', [
                'user_id' => $user->id,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'expected_fingerprint' => $request->session()->get('session_fingerprint'),
                'actual_fingerprint' => $this->createSessionFingerprint($request),
            ]);

            // Log suspicious activity
            UserActivity::logActivity([
                'user_id' => $user->id,
                'activity_type' => 'security_violation',
                'description' => 'Session fingerprint mismatch detected',
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'metadata' => [
                    'violation_type' => 'fingerprint_mismatch',
                    'risk_level' => 'high',
                ],
            ]);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                           ->with('status', 'Security violation detected. Please log in again.');
        }

        // Check user status
        if ($user->status !== 'active') {
            $message = $this->getUserStatusMessage($user->status);

            Log::channel('analytics_activity')->info('Inactive user attempted access', [
                'user_id' => $user->id,
                'status' => $user->status,
                'ip_address' => $ipAddress,
            ]);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('status', $message);
        }

        // Check if account is locked
        if ($user->is_locked) {
            Log::channel('analytics_security')->info('Locked user attempted access', [
                'user_id' => $user->id,
                'locked_until' => $user->locked_until,
                'ip_address' => $ipAddress,
            ]);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                           ->with('status', 'Your account is locked. Please contact administrator.');
        }

        // Update session activity
        $this->updateSessionActivity($request);

        // Log user activity for non-AJAX requests
        if (!$request->ajax() && !$request->wantsJson()) {
            $this->logUserActivity($request, $user);
        }

        return $next($request);
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
     * Check if session has expired
     *
     * @param Request $request
     * @return bool
     */
    protected function isSessionExpired(Request $request)
    {
        $lastActivity = $request->session()->get('last_activity');
        $sessionExpiresAt = $request->session()->get('session_expires_at');

        if (!$lastActivity || !$sessionExpiresAt) {
            return true;
        }

        $currentTime = time();

        // Check if session has expired based on timeout
        if ($currentTime > $sessionExpiresAt) {
            return true;
        }

        // Check if session has been inactive for too long
        if (($currentTime - $lastActivity) > (self::SESSION_TIMEOUT * 60)) {
            return true;
        }

        return false;
    }

    /**
     * Validate session fingerprint
     *
     * @param Request $request
     * @return bool
     */
    protected function validateSessionFingerprint(Request $request)
    {
        $storedFingerprint = $request->session()->get('session_fingerprint');
        $currentFingerprint = $this->createSessionFingerprint($request);

        return $storedFingerprint === $currentFingerprint;
    }

    /**
     * Create session fingerprint
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
     * Update session activity timestamp
     *
     * @param Request $request
     */
    protected function updateSessionActivity(Request $request)
    {
        $currentTime = time();

        $request->session()->put('last_activity', $currentTime);
        $request->session()->put('session_expires_at', $currentTime + (self::SESSION_TIMEOUT * 60));
    }

    /**
     * Log user activity
     *
     * @param Request $request
     * @param \App\Models\User $user
     */
    protected function logUserActivity(Request $request, $user)
    {
        // Only log page views, not every request
        if ($request->method() === 'GET' && !str_contains($request->path(), 'api/')) {
            UserActivity::logActivity([
                'user_id' => $user->id,
                'activity_type' => 'page_view',
                'description' => 'User accessed: ' . $request->path(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => [
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'referrer' => $request->header('referer'),
                ],
            ]);
        }
    }

    /**
     * Get user status message
     *
     * @param string $status
     * @return string
     */
    protected function getUserStatusMessage($status)
    {
        return match ($status) {
            'suspended' => 'Your account has been suspended. Please contact administrator.',
            'pending' => 'Your account is pending activation. Please contact administrator.',
            'expired' => 'Your account has expired. Please contact administrator.',
            default => 'Your account is not active. Please contact administrator.',
        };
    }
}
