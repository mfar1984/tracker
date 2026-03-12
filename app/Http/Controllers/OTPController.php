<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\OTPService;
use App\Services\InfobipService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class OTPController extends Controller
{
    protected $otpService;
    protected $infobipService;

    public function __construct(OTPService $otpService, InfobipService $infobipService)
    {
        $this->otpService = $otpService;
        $this->infobipService = $infobipService;
    }

    /**
     * Send OTP to phone number
     * POST /api/otp/send-phone
     */
    public function sendPhone(Request $request): JsonResponse
    {
        try {
            // Add basic service check
            if (!$this->otpService || !$this->infobipService) {
                return response()->json([
                    'error' => true,
                    'message' => 'OTP services not available',
                    'code' => 'SERVICE_UNAVAILABLE'
                ], 500);
            }

            $validated = $request->validate([
                'phone_number' => ['required', 'string', 'regex:/^(\+?60|0)[0-9]{8,10}$/']
            ]);

            $phoneNumber = $validated['phone_number'];

            // Format phone number for consistent checking
            $formattedPhone = $this->formatPhoneNumber($phoneNumber);
            
            // Check if phone number already exists in database
            if (\App\Models\User::where('phone_number', $formattedPhone)->exists()) {
                return response()->json([
                    'error' => true,
                    'message' => 'This phone number is already registered. Please use a different phone number.',
                    'code' => 'PHONE_ALREADY_EXISTS'
                ], 422);
            }

            // Check rate limit
            if ($this->otpService->checkRateLimit($phoneNumber, 'phone')) {
                return response()->json([
                    'error' => true,
                    'message' => 'Too many OTP requests. Please try again in 1 hour.',
                    'code' => 'RATE_LIMIT_EXCEEDED'
                ], 429);
            }

            // Check resend cooldown
            if (!$this->otpService->canResend($phoneNumber, 'phone')) {
                $timeRemaining = $this->otpService->getTimeRemaining($phoneNumber, 'phone');
                return response()->json([
                    'error' => true,
                    'message' => "Please wait {$timeRemaining} seconds before requesting another OTP.",
                    'code' => 'RESEND_COOLDOWN',
                    'time_remaining' => $timeRemaining
                ], 429);
            }

            // Generate OTP
            $otpCode = $this->otpService->generate($phoneNumber, 'phone');

            // Send SMS
            $smsResult = $this->infobipService->sendOTP($phoneNumber, $otpCode);

            if ($smsResult['success']) {
                // Increment rate limit counter
                $this->otpService->incrementRateLimit($phoneNumber, 'phone');

                return response()->json([
                    'success' => true,
                    'message' => 'OTP sent successfully to your phone number.',
                    'expires_in' => 300, // 5 minutes
                    'phone_number' => $smsResult['phone_number']
                ]);
            } else {
                // Clear OTP if SMS failed
                $this->otpService->clear($phoneNumber, 'phone');

                return response()->json([
                    'error' => true,
                    'message' => 'Failed to send SMS: ' . $smsResult['error'],
                    'code' => 'SMS_SEND_FAILED'
                ], 500);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => true,
                'message' => 'Invalid phone number format. Please use Malaysian format (e.g., 0123456789 or +60123456789).',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('OTP send phone failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'phone_number' => $phoneNumber ?? 'unknown'
            ]);

            return response()->json([
                'error' => true,
                'message' => 'Failed to send OTP. Please try again. Error: ' . $e->getMessage(),
                'code' => 'OTP_SEND_ERROR'
            ], 500);
        }
    }

    /**
     * Send OTP to email address
     * POST /api/otp/send-email
     */
    public function sendEmail(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email'
            ]);

            $email = $validated['email'];

            // Check rate limit
            if ($this->otpService->checkRateLimit($email, 'email')) {
                return response()->json([
                    'error' => true,
                    'message' => 'Too many OTP requests. Please try again in 1 hour.',
                    'code' => 'RATE_LIMIT_EXCEEDED'
                ], 429);
            }

            // Check resend cooldown
            if (!$this->otpService->canResend($email, 'email')) {
                $timeRemaining = $this->otpService->getTimeRemaining($email, 'email');
                return response()->json([
                    'error' => true,
                    'message' => "Please wait {$timeRemaining} seconds before requesting another OTP.",
                    'code' => 'RESEND_COOLDOWN',
                    'time_remaining' => $timeRemaining
                ], 429);
            }

            // Generate OTP
            $otpCode = $this->otpService->generate($email, 'email');

            // Send email
            $emailResult = $this->sendOTPEmail($email, $otpCode);

            if ($emailResult['success']) {
                // Increment rate limit counter
                $this->otpService->incrementRateLimit($email, 'email');

                return response()->json([
                    'success' => true,
                    'message' => 'OTP sent successfully to your email address.',
                    'expires_in' => 300, // 5 minutes
                    'email' => $email
                ]);
            } else {
                // Clear OTP if email failed
                $this->otpService->clear($email, 'email');

                return response()->json([
                    'error' => true,
                    'message' => 'Failed to send email: ' . $emailResult['error'],
                    'code' => 'EMAIL_SEND_FAILED'
                ], 500);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => true,
                'message' => 'Invalid email address.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('OTP send email failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'email' => $email ?? 'unknown'
            ]);

            return response()->json([
                'error' => true,
                'message' => 'Failed to send OTP. Please try again.',
                'code' => 'OTP_SEND_ERROR'
            ], 500);
        }
    }

    /**
     * Verify OTP code
     * POST /api/otp/verify
     */
    public function verify(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'identifier' => 'required|string', // phone number or email
                'code' => 'required|string|size:6',
                'type' => 'required|string|in:phone,email'
            ]);

            $identifier = $validated['identifier'];
            $code = $validated['code'];
            $type = $validated['type'];

            // Verify OTP
            $isValid = $this->otpService->verify($identifier, $code, $type);

            if ($isValid) {
                // Store verification status in session
                $sessionKey = "otp_verified_{$type}_{$identifier}";
                session([$sessionKey => true]);

                // If this is email verification, find user by email and mark as verified
                if ($type === 'email') {
                    // Find user by email
                    $user = \App\Models\User::where('email', $identifier)->first();
                    
                    if ($user) {
                        \Log::info('Updating email verification for user found by email', [
                            'user_id' => $user->id,
                            'email' => $user->email,
                            'identifier' => $identifier,
                            'before_update' => [
                                'email_verified_at' => $user->email_verified_at,
                                'approved' => $user->approved
                            ]
                        ]);
                        
                        $user->update([
                            'email_verified_at' => now(),
                            'approved' => true  // Auto-approve after email verification
                        ]);
                        
                        // Refresh user model to get updated values
                        $user->refresh();
                        
                        \Log::info('Email verification updated successfully', [
                            'user_id' => $user->id,
                            'after_update' => [
                                'email_verified_at' => $user->email_verified_at,
                                'approved' => $user->approved
                            ]
                        ]);
                    } else {
                        \Log::warning('User not found for email verification', [
                            'identifier' => $identifier
                        ]);
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => ucfirst($type) . ' verification successful.',
                    'verified' => true
                ]);
            } else {
                $remainingAttempts = $this->otpService->getRemainingAttempts($identifier, $type);
                
                if ($remainingAttempts > 0) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Invalid OTP code. Please try again.',
                        'code' => 'INVALID_OTP',
                        'attempts_remaining' => $remainingAttempts
                    ], 400);
                } else {
                    return response()->json([
                        'error' => true,
                        'message' => 'Too many failed attempts. Please request a new OTP.',
                        'code' => 'MAX_ATTEMPTS_EXCEEDED'
                    ], 400);
                }
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => true,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('OTP verify failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'identifier' => $identifier ?? 'unknown',
                'type' => $type ?? 'unknown'
            ]);

            return response()->json([
                'error' => true,
                'message' => 'Failed to verify OTP. Please try again.',
                'code' => 'OTP_VERIFY_ERROR'
            ], 500);
        }
    }
    /**
     * Verify email OTP and mark user as email verified
     * POST /api/otp/verify-email
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|size:6'
            ]);

            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'error' => true,
                    'message' => 'User not authenticated',
                    'code' => 'NOT_AUTHENTICATED'
                ], 401);
            }

            $code = $validated['code'];
            $email = $user->email;

            // Verify OTP
            $isValid = $this->otpService->verify($email, $code, 'email');

            if ($isValid) {
                // Mark email as verified
                $user->update(['email_verified_at' => now()]);

                return response()->json([
                    'success' => true,
                    'message' => 'Email verification successful.',
                    'verified' => true
                ]);
            } else {
                $remainingAttempts = $this->otpService->getRemainingAttempts($email, 'email');

                if ($remainingAttempts > 0) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Invalid verification code. Please try again.',
                        'code' => 'INVALID_OTP',
                        'attempts_remaining' => $remainingAttempts
                    ], 400);
                } else {
                    return response()->json([
                        'error' => true,
                        'message' => 'Too many failed attempts. Please request a new verification code.',
                        'code' => 'MAX_ATTEMPTS_EXCEEDED'
                    ], 400);
                }
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => true,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Email verification failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'error' => true,
                'message' => 'Failed to verify email. Please try again.',
                'code' => 'EMAIL_VERIFY_ERROR'
            ], 500);
        }
    }

    /**
     * Resend OTP
     * POST /api/otp/resend
     */
    public function resend(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'identifier' => 'required|string', // phone number or email
                'type' => 'required|string|in:phone,email'
            ]);

            $identifier = $validated['identifier'];
            $type = $validated['type'];

            // Check if resend is allowed (60s cooldown)
            if (!$this->otpService->canResend($identifier, $type)) {
                $timeRemaining = $this->otpService->getTimeRemaining($identifier, $type);
                return response()->json([
                    'error' => true,
                    'message' => "Please wait {$timeRemaining} seconds before requesting another OTP.",
                    'code' => 'RESEND_COOLDOWN',
                    'time_remaining' => $timeRemaining
                ], 429);
            }

            // Check rate limit
            if ($this->otpService->checkRateLimit($identifier, $type)) {
                return response()->json([
                    'error' => true,
                    'message' => 'Too many OTP requests. Please try again in 1 hour.',
                    'code' => 'RATE_LIMIT_EXCEEDED'
                ], 429);
            }

            // Generate new OTP (invalidates old one)
            $otpCode = $this->otpService->generate($identifier, $type);

            // Send OTP based on type
            if ($type === 'phone') {
                $result = $this->infobipService->sendOTP($identifier, $otpCode);
            } else {
                $result = $this->sendOTPEmail($identifier, $otpCode);
            }

            if ($result['success']) {
                // Increment rate limit counter
                $this->otpService->incrementRateLimit($identifier, $type);

                return response()->json([
                    'success' => true,
                    'message' => 'New OTP sent successfully.',
                    'expires_in' => 300 // 5 minutes
                ]);
            } else {
                // Clear OTP if sending failed
                $this->otpService->clear($identifier, $type);

                return response()->json([
                    'error' => true,
                    'message' => 'Failed to send OTP: ' . $result['error'],
                    'code' => 'OTP_SEND_FAILED'
                ], 500);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => true,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('OTP resend failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'identifier' => $identifier ?? 'unknown',
                'type' => $type ?? 'unknown'
            ]);

            return response()->json([
                'error' => true,
                'message' => 'Failed to resend OTP. Please try again.',
                'code' => 'OTP_RESEND_ERROR'
            ], 500);
        }
    }

    /**
     * Send OTP email
     * 
     * @param string $email Email address
     * @param string $otpCode OTP code
     * @return array Result with success status
     */
    protected function sendOTPEmail(string $email, string $otpCode): array
    {
        try {
            Mail::raw(
                "Your GPS Tracker email verification code is: {$otpCode}\n\n" .
                "This code will expire in 5 minutes. Do not share this code with anyone.\n\n" .
                "If you did not request this code, please ignore this email.",
                function ($message) use ($email) {
                    $message->to($email)
                            ->subject('GPS Tracker - Email Verification Code');
                }
            );

            return [
                'success' => true,
                'message' => 'Email sent successfully'
            ];

        } catch (\Exception $e) {
            Log::error('OTP email send failed', [
                'error' => $e->getMessage(),
                'email' => $email
            ]);

            return [
                'success' => false,
                'error' => 'Failed to send email: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Format phone number for consistent checking
     * Convert to +60 format for consistency
     */
    private function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove spaces and dashes
        $phoneNumber = preg_replace('/[\s\-]/', '', $phoneNumber);
        
        // If starts with 0, replace with +60
        if (str_starts_with($phoneNumber, '0')) {
            return '+60' . substr($phoneNumber, 1);
        }
        
        // If starts with 60, add +
        if (str_starts_with($phoneNumber, '60')) {
            return '+' . $phoneNumber;
        }
        
        // If already starts with +, return as is
        if (str_starts_with($phoneNumber, '+')) {
            return $phoneNumber;
        }
        
        // Default: assume Malaysian number and add +60
        return '+60' . $phoneNumber;
    }
}