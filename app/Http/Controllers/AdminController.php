<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\SettingsService;

class AdminController extends Controller
{
    protected $settingsService;
    protected $infobipService;

    public function __construct(SettingsService $settingsService, \App\Services\InfobipService $infobipService)
    {
        $this->settingsService = $settingsService;
        $this->infobipService = $infobipService;
    }

    /**
     * Get all settings (admin only)
     * GET /api/admin/settings
     */
    public function getSettings(): JsonResponse
    {
        try {
            $settings = $this->settingsService->all();
            
            return response()->json([
                'success' => true,
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Failed to retrieve settings',
                'code' => 'SETTINGS_RETRIEVAL_ERROR'
            ], 500);
        }
    }

    /**
     * Update settings (admin only)
     * PUT /api/admin/settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'app_name' => 'nullable|string',
                'map_center_lat' => 'nullable|numeric',
                'map_center_lng' => 'nullable|numeric',
                'smtp_host' => 'nullable|string',
                'smtp_username' => 'nullable|string',
                'smtp_password' => 'nullable|string',
                'smtp_port' => 'nullable|string',
                'smtp_encryption' => 'nullable|string|in:ssl,tls',
                'infobip_api_key' => 'nullable|string',
                'infobip_base_url' => 'nullable|string',
                'infobip_sender_number' => 'nullable|string',
            ]);

            $this->settingsService->bulkUpdate($validated);

            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => true,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Failed to update settings',
                'code' => 'SETTINGS_UPDATE_ERROR'
            ], 500);
        }
    }

    /**
     * Send test email (admin only)
     * POST /api/admin/test-email
     */
    public function testEmail(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email'
            ]);

            // Get SMTP settings
            $host = $this->settingsService->get('smtp_host');
            $username = $this->settingsService->get('smtp_username');
            $password = $this->settingsService->get('smtp_password');
            $port = $this->settingsService->get('smtp_port');
            $encryption = $this->settingsService->get('smtp_encryption');

            // Validate that all required settings are present
            if (!$host || !$username || !$password || !$port) {
                return response()->json([
                    'error' => true,
                    'message' => 'SMTP settings are incomplete. Please configure all SMTP settings first.',
                    'code' => 'SMTP_SETTINGS_INCOMPLETE'
                ], 400);
            }

            // Configure mail dynamically
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.transport' => 'smtp',
                'mail.mailers.smtp.host' => $host,
                'mail.mailers.smtp.port' => (int)$port,
                'mail.mailers.smtp.encryption' => $encryption ?: 'ssl',
                'mail.mailers.smtp.username' => $username,
                'mail.mailers.smtp.password' => $password,
                'mail.from.address' => $username,
                'mail.from.name' => 'GPS Tracker Admin',
            ]);

            // Clear any cached mail manager instance
            app()->forgetInstance('mail.manager');

            // Send test email using specific SMTP mailer
            \Mail::mailer('smtp')->raw('This is a test email from GPS Tracker Admin Panel. If you receive this email, your SMTP configuration is working correctly!', function($message) use ($validated) {
                $message->to($validated['email'])
                        ->subject('Test Email from GPS Tracker - Configuration Success');
            });

            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully to ' . $validated['email']
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => true,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Test email failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'smtp_settings' => [
                    'host' => $this->settingsService->get('smtp_host'),
                    'port' => $this->settingsService->get('smtp_port'),
                    'username' => $this->settingsService->get('smtp_username'),
                    'encryption' => $this->settingsService->get('smtp_encryption'),
                ]
            ]);
            
            return response()->json([
                'error' => true,
                'message' => 'Failed to send test email: ' . $e->getMessage(),
                'code' => 'EMAIL_SEND_ERROR'
            ], 500);
        }
    }

    /**
     * Send test SMS (admin only)
     * POST /api/admin/test-sms
     */
    public function testSMS(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'phone_number' => 'required|string'
            ]);

            // Use InfobipService to send test SMS
            $result = $this->infobipService->sendTestSMS($validated['phone_number']);
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => "Test SMS sent successfully to {$result['phone_number']}",
                    'message_id' => $result['message_id'] ?? null
                ]);
            } else {
                return response()->json([
                    'error' => true,
                    'message' => $result['error'],
                    'code' => $result['code']
                ], 500);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => true,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Test SMS failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => true,
                'message' => 'Failed to send test SMS: ' . $e->getMessage(),
                'code' => 'SMS_SEND_ERROR'
            ], 500);
        }
    }

    /**
     * Format phone number for Malaysian numbers
     * Convert 0xxx to +60xxx
     */

    /**
     * Get all users (admin only)
     * GET /api/admin/users
     */
    public function getUsers(Request $request): JsonResponse
    {
        try {
            $perPage = 10;
            $search = $request->input('search');
            $status = $request->input('status');

            $query = \App\Models\User::query()->withCount('devices');

            // Search by username or email
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('username', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            // Filter by status (active/inactive)
            if ($status === 'active') {
                $query->whereNotNull('email_verified_at');
            } elseif ($status === 'inactive') {
                $query->whereNull('email_verified_at');
            }

            $users = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'users' => $users->items(),
                    'pagination' => [
                        'current_page' => $users->currentPage(),
                        'per_page' => $users->perPage(),
                        'total' => $users->total(),
                        'last_page' => $users->lastPage(),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Failed to retrieve users',
                'code' => 'USERS_RETRIEVAL_ERROR'
            ], 500);
        }
    }

    /**
     * Get all devices from all users (admin only)
     * GET /api/admin/devices
     */
    public function getAllDevices(): JsonResponse
    {
        try {
            $devices = \App\Models\Device::with('user:id,username,name,email')->get();

            return response()->json([
                'success' => true,
                'data' => $devices
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Failed to retrieve devices',
                'code' => 'DEVICES_RETRIEVAL_ERROR'
            ], 500);
        }
    }

    /**
     * Generate license key for user (admin only)
     * POST /api/admin/users/{userId}/generate-license
     */
    public function generateLicenseKey(Request $request, $userId): JsonResponse
    {
        try {
            $user = \App\Models\User::findOrFail($userId);

            // Generate new license key
            $licenseKey = \App\Models\User::generateLicenseKey();
            $user->update(['license_key' => $licenseKey]);

            return response()->json([
                'success' => true,
                'message' => 'License key generated successfully',
                'license_key' => $licenseKey
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => true,
                'message' => 'User not found',
                'code' => 'USER_NOT_FOUND'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Failed to generate license key',
                'code' => 'LICENSE_GENERATION_ERROR'
            ], 500);
        }
    }

    /**
     * Approve user (admin only)
     * POST /api/admin/users/{userId}/approve
     */
    public function approveUser(Request $request, $userId): JsonResponse
    {
        try {
            $user = \App\Models\User::findOrFail($userId);
            $user->approve();

            return response()->json([
                'success' => true,
                'message' => 'User approved successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => true,
                'message' => 'User not found',
                'code' => 'USER_NOT_FOUND'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Failed to approve user',
                'code' => 'USER_APPROVAL_ERROR'
            ], 500);
        }
    }

    /**
     * Unapprove user (admin only)
     * POST /api/admin/users/{userId}/unapprove
     */
    public function unapproveUser(Request $request, $userId): JsonResponse
    {
        try {
            $user = \App\Models\User::findOrFail($userId);
            $user->unapprove();

            return response()->json([
                'success' => true,
                'message' => 'User unapproved successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => true,
                'message' => 'User not found',
                'code' => 'USER_NOT_FOUND'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Failed to unapprove user',
                'code' => 'USER_UNAPPROVAL_ERROR'
            ], 500);
        }
    }

    /**
     * Suspend user (admin only)
     * POST /api/admin/users/{userId}/suspend
     */
    public function suspendUser(Request $request, $userId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'reason' => 'nullable|string|max:500'
            ]);

            $user = \App\Models\User::findOrFail($userId);
            $user->suspend($validated['reason'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'User suspended successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => true,
                'message' => 'User not found',
                'code' => 'USER_NOT_FOUND'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => true,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Failed to suspend user',
                'code' => 'USER_SUSPENSION_ERROR'
            ], 500);
        }
    }

    /**
     * Unsuspend user (admin only)
     * POST /api/admin/users/{userId}/unsuspend
     */
    public function unsuspendUser(Request $request, $userId): JsonResponse
    {
        try {
            $user = \App\Models\User::findOrFail($userId);
            $user->unsuspend();

            return response()->json([
                'success' => true,
                'message' => 'User unsuspended successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => true,
                'message' => 'User not found',
                'code' => 'USER_NOT_FOUND'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Failed to unsuspend user',
                'code' => 'USER_UNSUSPENSION_ERROR'
            ], 500);
        }
    }

    /**
     * Delete user (admin only)
     * DELETE /api/admin/users/{userId}
     */
    public function deleteUser(Request $request, $userId): JsonResponse
    {
        try {
            $user = \App\Models\User::findOrFail($userId);

            // Prevent deletion of admin user (ID 1)
            if ($user->id === 1) {
                return response()->json([
                    'error' => true,
                    'message' => 'Cannot delete the main admin user',
                    'code' => 'ADMIN_USER_DELETION_FORBIDDEN'
                ], 403);
            }

            // Delete user's devices first
            $user->devices()->delete();

            // Delete user
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => true,
                'message' => 'User not found',
                'code' => 'USER_NOT_FOUND'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Failed to delete user',
                'code' => 'USER_DELETION_ERROR'
            ], 500);
        }
    }
}
