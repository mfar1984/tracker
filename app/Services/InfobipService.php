<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\SettingsService;

class InfobipService
{
    protected $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * Send SMS via Infobip API
     * 
     * @param string $phoneNumber Phone number in international format
     * @param string $message SMS message content
     * @return array Response with success status and message ID
     */
    public function sendSMS(string $phoneNumber, string $message): array
    {
        try {
            $credentials = $this->getCredentials();
            
            Log::info('Infobip credentials check', [
                'api_key_exists' => !empty($credentials['api_key']),
                'base_url_exists' => !empty($credentials['base_url']),
                'sender_number_exists' => !empty($credentials['sender_number']),
                'api_key_length' => strlen($credentials['api_key'] ?? ''),
                'base_url' => $credentials['base_url'] ?? 'null',
                'sender_number' => $credentials['sender_number'] ?? 'null'
            ]);
            
            if (!$credentials['api_key'] || !$credentials['base_url'] || !$credentials['sender_number']) {
                return [
                    'success' => false,
                    'error' => 'Infobip credentials not configured',
                    'code' => 'CREDENTIALS_MISSING'
                ];
            }

            // Format phone number (convert Malaysian 0xxx to +60xxx)
            $formattedPhone = $this->formatPhoneNumber($phoneNumber);
            
            // Build request
            $requestData = $this->buildRequest($formattedPhone, $message, $credentials['sender_number']);
            
            // Clean base URL - remove https:// if present
            $cleanBaseUrl = str_replace(['https://', 'http://'], '', $credentials['base_url']);
            $url = "https://{$cleanBaseUrl}/sms/2/text/advanced";
            
            Log::info('Infobip SMS API Request', [
                'url' => $url,
                'phone_number' => $formattedPhone,
                'sender_number' => $credentials['sender_number'],
                'request_data' => $requestData
            ]);
            
            // Send request with retry logic
            $response = $this->sendWithRetry($url, $requestData, $credentials['api_key']);
            
            if ($response['success']) {
                $responseData = $response['data'];
                
                Log::info('Infobip SMS API Success', [
                    'phone_number' => $formattedPhone,
                    'response' => $responseData
                ]);
                
                return [
                    'success' => true,
                    'message_id' => $responseData['messages'][0]['messageId'] ?? null,
                    'status' => $responseData['messages'][0]['status']['name'] ?? 'SENT',
                    'phone_number' => $formattedPhone
                ];
            } else {
                Log::error('Infobip SMS API failed', [
                    'error' => $response['error'],
                    'phone_number' => $formattedPhone,
                    'url' => $url
                ]);
                
                return [
                    'success' => false,
                    'error' => $response['error'],
                    'code' => $response['code'] ?? 'API_ERROR'
                ];
            }

        } catch (\Exception $e) {
            Log::error('Infobip SMS Service Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'phone_number' => $phoneNumber ?? 'unknown'
            ]);
            
            return [
                'success' => false,
                'error' => 'SMS service error: ' . $e->getMessage(),
                'code' => 'SERVICE_ERROR'
            ];
        }
    }

    /**
     * Send test SMS
     * 
     * @param string $phoneNumber Phone number to send test SMS
     * @return array Response with delivery status
     */
    public function sendTestSMS(string $phoneNumber): array
    {
        $message = 'This is a test SMS from GPS Tracker Admin Panel. If you receive this SMS, your Infobip configuration is working correctly!';
        
        return $this->sendSMS($phoneNumber, $message);
    }

    /**
     * Send OTP SMS
     * 
     * @param string $phoneNumber Phone number to send OTP
     * @param string $otpCode OTP code
     * @return array Response with delivery status
     */
    public function sendOTP(string $phoneNumber, string $otpCode): array
    {
        $message = "Your GPS Tracker verification code is: {$otpCode}. This code will expire in 5 minutes. Do not share this code with anyone.";
        
        return $this->sendSMS($phoneNumber, $message);
    }

    /**
     * Send request with retry logic
     * 
     * @param string $url API endpoint URL
     * @param array $data Request payload
     * @param string $apiKey Infobip API key
     * @return array Response data
     */
    protected function sendWithRetry(string $url, array $data, string $apiKey): array
    {
        $maxRetries = 3;
        $baseDelay = 1; // seconds
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'App ' . $apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])->withOptions([
                    'verify' => false, // Disable SSL verification for development
                    'timeout' => 10
                ])->post($url, $data);

                if ($response->successful()) {
                    return [
                        'success' => true,
                        'data' => $response->json()
                    ];
                } else {
                    $errorMessage = 'HTTP ' . $response->status();
                    if ($response->status() == 401) {
                        $errorMessage = 'Invalid Infobip API Key or unauthorized';
                    } elseif ($response->status() == 400) {
                        $errorMessage = 'Invalid request parameters (check phone number or sender)';
                    } elseif ($response->status() == 429) {
                        $errorMessage = 'Rate limit exceeded';
                    }
                    
                    // Don't retry on client errors (4xx)
                    if ($response->status() >= 400 && $response->status() < 500) {
                        return [
                            'success' => false,
                            'error' => $errorMessage . ': ' . $response->body(),
                            'code' => 'CLIENT_ERROR'
                        ];
                    }
                    
                    // Retry on server errors (5xx) or network issues
                    if ($attempt === $maxRetries) {
                        return [
                            'success' => false,
                            'error' => $errorMessage . ': ' . $response->body(),
                            'code' => 'SERVER_ERROR'
                        ];
                    }
                }
            } catch (\Exception $e) {
                if ($attempt === $maxRetries) {
                    return [
                        'success' => false,
                        'error' => 'Network error: ' . $e->getMessage(),
                        'code' => 'NETWORK_ERROR'
                    ];
                }
            }
            
            // Exponential backoff delay
            if ($attempt < $maxRetries) {
                sleep($baseDelay * pow(2, $attempt - 1));
            }
        }
        
        return [
            'success' => false,
            'error' => 'Max retries exceeded',
            'code' => 'MAX_RETRIES_EXCEEDED'
        ];
    }

    /**
     * Build request payload for Infobip API
     * 
     * @param string $phoneNumber Formatted phone number
     * @param string $message SMS message
     * @param string $senderNumber Sender number
     * @return array Request payload
     */
    protected function buildRequest(string $phoneNumber, string $message, string $senderNumber): array
    {
        return [
            'messages' => [
                [
                    'from' => $senderNumber,
                    'destinations' => [
                        [
                            'to' => $phoneNumber
                        ]
                    ],
                    'text' => $message
                ]
            ]
        ];
    }

    /**
     * Get Infobip credentials from settings
     * 
     * @return array Credentials array
     */
    protected function getCredentials(): array
    {
        return [
            'api_key' => $this->settingsService->get('infobip_api_key'),
            'base_url' => $this->settingsService->get('infobip_base_url'),
            'sender_number' => $this->settingsService->get('infobip_sender_number')
        ];
    }

    /**
     * Format phone number for Malaysian numbers
     * Convert 0xxx to +60xxx
     * 
     * @param string $phoneNumber Raw phone number
     * @return string Formatted phone number
     */
    protected function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove all spaces and dashes
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

    /**
     * Get message status (placeholder for future implementation)
     * 
     * @param string $messageId Message ID from Infobip
     * @return array Message status
     */
    public function getMessageStatus(string $messageId): array
    {
        // This can be implemented later if needed
        return [
            'message_id' => $messageId,
            'status' => 'UNKNOWN'
        ];
    }
}