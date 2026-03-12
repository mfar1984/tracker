<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'name',
        'email',
        'phone_number',
        'password',
        'license_key',
        'avatar_type',
        'avatar_value',
        'is_admin',
        'suspended',
        'suspended_at',
        'suspended_reason',
        'approved',
        'approved_at',
        'email_verified_at',
        'phone_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'suspended_at' => 'datetime',
            'approved_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'suspended' => 'boolean',
            'approved' => 'boolean',
        ];
    }

    /**
     * Get the devices for the user.
     */
    public function devices()
    {
        return $this->hasMany(Device::class);
    }
    
    /**
     * Check if user is admin
     * 
     * @return bool
     */
    public function isAdmin()
    {
        return $this->is_admin === true;
    }
    
    /**
     * Check if user is suspended
     * 
     * @return bool
     */
    public function isSuspended()
    {
        return $this->suspended === true;
    }
    
    /**
     * Check if user is approved
     * 
     * @return bool
     */
    public function isApproved()
    {
        return $this->approved === true;
    }
    
    /**
     * Check if user can login (approved and not suspended)
     * 
     * @return bool
     */
    public function canLogin()
    {
        return $this->isApproved() && !$this->isSuspended();
    }
    
    /**
     * Suspend the user
     * 
     * @param string $reason
     * @return void
     */
    public function suspend($reason = null)
    {
        $this->update([
            'suspended' => true,
            'suspended_at' => now(),
            'suspended_reason' => $reason
        ]);
    }
    
    /**
     * Unsuspend the user
     * 
     * @return void
     */
    public function unsuspend()
    {
        $this->update([
            'suspended' => false,
            'suspended_at' => null,
            'suspended_reason' => null
        ]);
    }
    
    /**
     * Approve the user
     * 
     * @return void
     */
    public function approve()
    {
        $this->update([
            'approved' => true,
            'approved_at' => now()
        ]);
    }
    
    /**
     * Unapprove the user
     * 
     * @return void
     */
    public function unapprove()
    {
        $this->update([
            'approved' => false,
            'approved_at' => null
        ]);
    }
    
    /**
     * Generate a unique license key in format XXXX-XXXX-XXXX
     * Uses uppercase letters (A-Z) and numbers (0-9) only
     * 
     * @return string
     */
    public static function generateLicenseKey()
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $maxAttempts = 100;
        $attempt = 0;
        
        do {
            $key = '';
            
            // Generate 3 groups of 4 characters
            for ($group = 0; $group < 3; $group++) {
                if ($group > 0) {
                    $key .= '-';
                }
                
                for ($i = 0; $i < 4; $i++) {
                    $key .= $characters[rand(0, strlen($characters) - 1)];
                }
            }
            
            $attempt++;
            
            // Check if key already exists
            $exists = self::where('license_key', $key)->exists();
            
        } while ($exists && $attempt < $maxAttempts);
        
        if ($attempt >= $maxAttempts) {
            throw new \Exception('Failed to generate unique license key after ' . $maxAttempts . ' attempts');
        }
        
        return $key;
    }
}
