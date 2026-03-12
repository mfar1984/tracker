<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class OTPService
{
    /**
     * Cache TTL for OTP (5 minutes)
     */
    const OTP_TTL = 300;
    
    /**
     * Resend cooldown (60 seconds)
     */
    const RESEND_COOLDOWN = 60;
    
    /**
     * Rate limit (10 requests per 5 minutes for development)
     */
    const RATE_LIMIT = 10;
    const RATE_LIMIT_TTL = 300;
    
    /**
     * Max attempts before clearing OTP
     */
    const MAX_ATTEMPTS = 3;

    /**
     * Generate OTP for phone number or email
     * 
     * @param string $identifier Phone number or email
     * @param string $type 'phone' or 'email'
     * @return string Generated OTP code
     */
    public function generate(string $identifier, string $type = 'phone'): string
    {
        // Generate random 6-digit code
        $code = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        
        $cacheKey = "otp:{$type}:" . md5($identifier);
        $now = Carbon::now();
        
        $otpData = [
            'code' => $code,
            'attempts' => 0,
            'created_at' => $now->toDateTimeString(),
            'expires_at' => $now->addSeconds(self::OTP_TTL)->toDateTimeString(),
            'last_sent_at' => $now->toDateTimeString(),
            'type' => $type,
            'identifier' => $identifier
        ];
        
        // Store OTP data in cache
        Cache::put($cacheKey, $otpData, self::OTP_TTL);
        
        return $code;
    }
    
    /**
     * Verify OTP code
     * 
     * @param string $identifier Phone number or email
     * @param string $code OTP code to verify
     * @param string $type 'phone' or 'email'
     * @return bool True if valid, false otherwise
     */
    public function verify(string $identifier, string $code, string $type = 'phone'): bool
    {
        $cacheKey = "otp:{$type}:" . md5($identifier);
        $otpData = Cache::get($cacheKey);
        
        if (!$otpData) {
            return false;
        }
        
        // Check if expired
        if (Carbon::parse($otpData['expires_at'])->isPast()) {
            Cache::forget($cacheKey);
            return false;
        }
        
        // Increment attempts
        $otpData['attempts']++;
        
        // Check if max attempts reached
        if ($otpData['attempts'] >= self::MAX_ATTEMPTS) {
            Cache::forget($cacheKey);
            return false;
        }
        
        // Verify code using constant-time comparison
        $isValid = hash_equals($otpData['code'], $code);
        
        if ($isValid) {
            // Clear OTP on successful verification
            Cache::forget($cacheKey);
            return true;
        } else {
            // Update attempts in cache
            Cache::put($cacheKey, $otpData, Carbon::parse($otpData['expires_at'])->diffInSeconds());
            return false;
        }
    }
    
    /**
     * Check if rate limit is exceeded
     * 
     * @param string $identifier Phone number or email
     * @param string $type 'phone' or 'email'
     * @return bool True if rate limit exceeded
     */
    public function checkRateLimit(string $identifier, string $type = 'phone'): bool
    {
        $rateLimitKey = "otp:rate:{$type}:" . md5($identifier);
        $count = Cache::get($rateLimitKey, 0);
        
        return $count >= self::RATE_LIMIT;
    }
    
    /**
     * Increment rate limit counter
     * 
     * @param string $identifier Phone number or email
     * @param string $type 'phone' or 'email'
     * @return void
     */
    public function incrementRateLimit(string $identifier, string $type = 'phone'): void
    {
        $rateLimitKey = "otp:rate:{$type}:" . md5($identifier);
        $count = Cache::get($rateLimitKey, 0);
        Cache::put($rateLimitKey, $count + 1, self::RATE_LIMIT_TTL);
    }
    
    /**
     * Check if resend is allowed (cooldown check)
     * 
     * @param string $identifier Phone number or email
     * @param string $type 'phone' or 'email'
     * @return bool True if can resend
     */
    public function canResend(string $identifier, string $type = 'phone'): bool
    {
        $cacheKey = "otp:{$type}:" . md5($identifier);
        $otpData = Cache::get($cacheKey);
        
        if (!$otpData) {
            return true;
        }
        
        $lastSentAt = Carbon::parse($otpData['last_sent_at']);
        return $lastSentAt->copy()->addSeconds(self::RESEND_COOLDOWN)->isPast();
    }
    
    /**
     * Get time remaining for resend cooldown
     * 
     * @param string $identifier Phone number or email
     * @param string $type 'phone' or 'email'
     * @return int Seconds remaining, 0 if can resend
     */
    public function getTimeRemaining(string $identifier, string $type = 'phone'): int
    {
        $cacheKey = "otp:{$type}:" . md5($identifier);
        $otpData = Cache::get($cacheKey);
        
        if (!$otpData) {
            return 0;
        }
        
        $lastSentAt = Carbon::parse($otpData['last_sent_at']);
        $canResendAt = $lastSentAt->copy()->addSeconds(self::RESEND_COOLDOWN);
        
        return $canResendAt->isFuture() ? $canResendAt->diffInSeconds(Carbon::now()) : 0;
    }
    
    /**
     * Clear OTP for identifier
     * 
     * @param string $identifier Phone number or email
     * @param string $type 'phone' or 'email'
     * @return void
     */
    public function clear(string $identifier, string $type = 'phone'): void
    {
        $cacheKey = "otp:{$type}:" . md5($identifier);
        Cache::forget($cacheKey);
    }
    
    /**
     * Get OTP expiry time remaining
     * 
     * @param string $identifier Phone number or email
     * @param string $type 'phone' or 'email'
     * @return int Seconds remaining, 0 if expired
     */
    public function getExpiryTimeRemaining(string $identifier, string $type = 'phone'): int
    {
        $cacheKey = "otp:{$type}:" . md5($identifier);
        $otpData = Cache::get($cacheKey);
        
        if (!$otpData) {
            return 0;
        }
        
        $expiresAt = Carbon::parse($otpData['expires_at']);
        return $expiresAt->isFuture() ? $expiresAt->diffInSeconds() : 0;
    }
    
    /**
     * Get remaining attempts
     * 
     * @param string $identifier Phone number or email
     * @param string $type 'phone' or 'email'
     * @return int Remaining attempts
     */
    public function getRemainingAttempts(string $identifier, string $type = 'phone'): int
    {
        $cacheKey = "otp:{$type}:" . md5($identifier);
        $otpData = Cache::get($cacheKey);
        
        if (!$otpData) {
            return self::MAX_ATTEMPTS;
        }
        
        return max(0, self::MAX_ATTEMPTS - $otpData['attempts']);
    }
}