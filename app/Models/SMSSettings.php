<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsSettings extends Model
{
    use HasFactory;

    protected $table = 'tbl_sms';
    protected $primaryKey = 'sms_id';
    
    // Define custom timestamp column names
    const CREATED_AT = 'sms_created_at';
    const UPDATED_AT = 'sms_updated_at';

    protected $fillable = [
        'sms_provider',
        'sms_api_key', 
        'sms_sender_id',
        'sms_api_url',
        'sms_twilio_token',  // Add this field
        'sms_is_active',
        'sms_additional_config'
    ];

    protected $casts = [
        'sms_additional_config' => 'array',
        'sms_is_active' => 'boolean'
    ];

    public static function getActiveConfig()
    {
        return self::where('sms_is_active', true)->first();
    }
    
    // Add accessor for Twilio token
    public function getTwilioTokenAttribute()
    {
        return $this->sms_twilio_token;
    }
}