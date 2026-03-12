<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'encrypted'];
    
    protected $casts = [
        'encrypted' => 'boolean',
    ];

    /**
     * Get the value attribute with automatic decryption
     */
    public function getValueAttribute($value)
    {
        if ($this->encrypted && $value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return $value;
            }
        }
        return $value;
    }

    /**
     * Set the value attribute with automatic encryption
     */
    public function setValueAttribute($value)
    {
        if ($this->encrypted && $value) {
            $this->attributes['value'] = Crypt::encryptString($value);
        } else {
            $this->attributes['value'] = $value;
        }
    }
}
