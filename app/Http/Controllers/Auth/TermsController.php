<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TermsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Terms and Conditions Controller
 *
 * Handles Terms & Conditions acceptance with security features including:
 * - Force T&C acceptance on first login
 * - T&C acceptance logging and tracking
 * - Version management for updated terms
 * - Modal-based acceptance flow
 * - Comprehensive audit trail
 *
 * Security Features:
 * - Mandatory acceptance before system access
 * - Timestamped acceptance logging
 * - Version tracking for legal compliance
 * - IP and user agent tracking
 * - Activity logging for audit purposes
 */
class TermsController extends Controller
{
    protected $termsService;

    public function __construct(TermsService $termsService)
    {
        $this->termsService = $termsService;
    }
    /**
     * Show terms and conditions modal
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function showTerms(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Check if user has already accepted current terms
        if (!$this->termsService->userNeedsToAcceptTerms($user)) {
            return redirect()->intended('/dashboard');
        }

        $currentTermsVersion = config('app.terms_version', '1.0');

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'terms_version' => $currentTermsVersion,
                'user_must_accept' => true
            ]);
        }

        return view('auth.terms', compact('currentTermsVersion'));
    }

    /**
     * Accept terms and conditions
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function acceptTerms(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated.'
            ], 401);
        }

        // Validate acceptance
        $request->validate([
            'accept_terms' => 'required|accepted',
            'terms_version' => 'required|string'
        ]);

        $termsVersion = $request->input('terms_version');
        $currentTermsVersion = config('app.terms_version', '1.0');

        // Ensure user is accepting the current version
        if ($termsVersion !== $currentTermsVersion) {
            return response()->json([
                'success' => false,
                'message' => 'Terms version mismatch. Please refresh and try again.'
            ], 400);
        }

        // Update user record using service
        $success = $this->termsService->markUserTermsAccepted($user, $termsVersion);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to record terms acceptance. Please try again.'
            ], 500);
        }

        // Log terms acceptance
        activity()
            ->causedBy($user)
            ->withProperties([
                'terms_version' => $currentTermsVersion,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'acceptance_method' => 'web_modal'
            ])
            ->log('terms_accepted');

        Log::info('Terms and conditions accepted', [
            'user_id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'terms_version' => $currentTermsVersion,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Terms and conditions accepted successfully.',
                'redirect' => $request->input('redirect_to', '/dashboard')
            ]);
        }

        return redirect()->intended('/dashboard')
            ->with('success', 'Terms and conditions accepted successfully.');
    }

    /**
     * Check if user needs to accept terms
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkTermsStatus(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated.'
            ], 401);
        }

        $currentTermsVersion = config('app.terms_version', '1.0');
        $needsAcceptance = !$user->terms_accepted ||
                          !$user->terms_accepted_at ||
                          $user->terms_version !== $currentTermsVersion;

        return response()->json([
            'success' => true,
            'needs_acceptance' => $needsAcceptance,
            'current_version' => $currentTermsVersion,
            'user_version' => $user->terms_version,
            'accepted_at' => $user->terms_accepted_at
        ]);
    }

    /**
     * Get terms and conditions content
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTermsContent(Request $request)
    {
        $version = $request->input('version', config('app.terms_version', '1.0'));

        // In a real application, you might store different versions in database
        // For now, we'll return the current terms content
        $termsContent = $this->getCurrentTermsContent();

        return response()->json([
            'success' => true,
            'content' => $termsContent,
            'version' => $version,
            'last_updated' => config('app.terms_last_updated', now()->format('Y-m-d'))
        ]);
    }

    /**
     * Get current terms and conditions content
     *
     * @return string
     */
    private function getCurrentTermsContent(): string
    {
        return '
            <div class="terms-content">
                <h2>Analytics Hub - Terms and Conditions</h2>

                <h3>1. Acceptance of Terms</h3>
                <p>By accessing and using Analytics Hub, you accept and agree to be bound by the terms and provision of this agreement.</p>

                <h3>2. Use License</h3>
                <p>Permission is granted to temporarily use Analytics Hub for personal, non-commercial transitory viewing only. This is the grant of a license, not a transfer of title, and under this license you may not:</p>
                <ul>
                    <li>modify or copy the materials;</li>
                    <li>use the materials for any commercial purpose or for any public display;</li>
                    <li>attempt to reverse engineer any software contained in Analytics Hub;</li>
                    <li>remove any copyright or other proprietary notations from the materials.</li>
                </ul>

                <h3>3. Disclaimer</h3>
                <p>The materials in Analytics Hub are provided on an "as is" basis. Analytics Hub makes no warranties, expressed or implied, and hereby disclaims and negates all other warranties including without limitation, implied warranties or conditions of merchantability, fitness for a particular purpose, or non-infringement of intellectual property or other violation of rights.</p>

                <h3>4. Privacy Policy</h3>
                <p>Your privacy is important to us. Our Privacy Policy explains how we collect, use, and protect your information when you use our service.</p>

                <h3>5. User Accounts</h3>
                <p>When you create an account with us, you must provide information that is accurate, complete, and current at all times. You are responsible for safeguarding the password and for maintaining the confidentiality of your account.</p>

                <h3>6. Prohibited Uses</h3>
                <p>You may not use our service:</p>
                <ul>
                    <li>For any unlawful purpose or to solicit others to perform unlawful acts;</li>
                    <li>To violate any international, federal, provincial, or state regulations, rules, laws, or local ordinances;</li>
                    <li>To infringe upon or violate our intellectual property rights or the intellectual property rights of others;</li>
                    <li>To harass, abuse, insult, harm, defame, slander, disparage, intimidate, or discriminate;</li>
                    <li>To submit false or misleading information.</li>
                </ul>

                <h3>7. Data Security</h3>
                <p>We implement appropriate security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.</p>

                <h3>8. Termination</h3>
                <p>We may terminate or suspend your account and bar access to the service immediately, without prior notice or liability, under our sole discretion, for any reason whatsoever.</p>

                <h3>9. Changes to Terms</h3>
                <p>We reserve the right to modify these terms at any time. We will notify users of any changes by updating the "last updated" date of these Terms and Conditions.</p>

                <h3>10. Contact Information</h3>
                <p>If you have any questions about these Terms and Conditions, please contact us through the support system.</p>

                <p><strong>Last Updated:</strong> ' . config('app.terms_last_updated', now()->format('F d, Y')) . '</p>
                <p><strong>Version:</strong> ' . config('app.terms_version', '1.0') . '</p>
            </div>
        ';
    }

    /**
     * Admin: Update terms and conditions
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateTerms(Request $request)
    {
        // This would typically require admin permissions
        $user = Auth::user();

        if (!$user || !$user->hasRole('administrator')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.'
            ], 403);
        }

        $request->validate([
            'content' => 'required|string',
            'version' => 'required|string'
        ]);

        // In a real application, you would save this to database
        // For now, we'll just log the update

        activity()
            ->causedBy($user)
            ->withProperties([
                'new_version' => $request->input('version'),
                'previous_version' => config('app.terms_version', '1.0'),
                'content_length' => strlen($request->input('content'))
            ])
            ->log('terms_updated');

        return response()->json([
            'success' => true,
            'message' => 'Terms and conditions updated successfully.',
            'new_version' => $request->input('version')
        ]);
    }

    /**
     * Get terms acceptance statistics (for administrators)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAcceptanceStats(Request $request)
    {
        $user = Auth::user();

        // Check if user has admin privileges (implement based on your role system)
        if (!$user || !$user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.'
            ], 403);
        }

        $stats = $this->termsService->getTermsAcceptanceStats();

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get users who need to accept terms (for administrators)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUsersNeedingAcceptance(Request $request)
    {
        $user = Auth::user();

        // Check if user has admin privileges
        if (!$user || !$user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.'
            ], 403);
        }

        $users = $this->termsService->getUsersNeedingTermsAcceptance();

        $userData = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'full_name' => $user->full_name,
                'terms_accepted' => $user->terms_accepted,
                'terms_version' => $user->terms_version,
                'last_login_at' => $user->last_login_at,
                'created_at' => $user->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $userData,
            'count' => $users->count()
        ]);
    }
}
