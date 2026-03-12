<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
        {
            $user = $request->user();

            // If user is authenticated, check their status
            if ($user) {
                // If user is suspended, block access completely
                if ($user->isSuspended()) {
                    $reason = $user->suspended_reason ? " Reason: {$user->suspended_reason}" : '';
                    return response()->json([
                        'error' => true,
                        'message' => "Your account has been suspended.{$reason}",
                        'code' => 'ACCOUNT_SUSPENDED'
                    ], 403);
                }

                // If user hasn't verified email, they should be redirected to email verification
                // But we allow API access for basic profile/user data
                if (!$user->email_verified_at) {
                    // Allow access to basic user endpoints needed for email verification flow
                    $allowedPaths = [
                        '/api/user/profile-data',
                        '/api/otp/send-email',
                        '/api/otp/verify',
                        '/api/otp/resend'
                    ];

                    if (!in_array($request->path(), $allowedPaths)) {
                        return response()->json([
                            'error' => true,
                            'message' => 'Please verify your email address to access this feature.',
                            'code' => 'EMAIL_VERIFICATION_REQUIRED'
                        ], 403);
                    }
                }

                // If email is verified but not approved, auto-approve them
                if ($user->email_verified_at && !$user->isApproved()) {
                    $user->update(['approved' => true]);
                }
            }

            return $next($request);
        }
}
