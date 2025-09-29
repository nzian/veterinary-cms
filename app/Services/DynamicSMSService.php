<?php

namespace App\Services;

use App\Models\SmsSettings;
use Illuminate\Support\Facades\Log;

class DynamicSMSService
{
    private $config;

    public function __construct()
    {
        $this->config = SmsSettings::getActiveConfig();
    }

    /**
     * Test SMS
     */
    public function sendTestSMS($phoneNumber)
    {
        if (!$this->config) {
            return ['success' => false, 'message' => 'SMS not configured'];
        }

        // Fix: Check for correct Twilio credentials based on provider
        if ($this->config->sms_provider === 'twilio') {
            if (empty($this->config->sms_api_key) || empty($this->config->sms_twilio_token)) {
                Log::error('Twilio credentials check failed', [
                    'has_sid' => !empty($this->config->sms_api_key),
                    'has_token' => !empty($this->config->sms_twilio_token),
                    'sid_length' => strlen($this->config->sms_api_key ?? ''),
                    'token_length' => strlen($this->config->sms_twilio_token ?? '')
                ]);
                return ['success' => false, 'message' => 'Twilio credentials not configured'];
            }
        } else {
            if (empty($this->config->sms_api_key)) {
                return ['success' => false, 'message' => 'API key not configured'];
            }
        }

        $message = "Test SMS from " . ($this->config->sms_sender_id ?? 'YourClinic') . ". Configuration is working!";
        return $this->sendSMS($phoneNumber, $message, true);
    }

    /**
     * Auto-send for follow-ups
     */
    public function sendFollowUpSMS($appointment)
    {
        if (!$this->config || empty($this->config->sms_api_key)) {
            return false;
        }

        if (strtolower($appointment->appoint_type) !== 'follow-up') {
            return false;
        }

        // Load the pet and owner relationships if not already loaded
        if (!$appointment->relationLoaded('pet') || !$appointment->pet->relationLoaded('owner')) {
            $appointment->load('pet.owner');
        }

        $phoneNumber = $appointment->pet->owner->own_contactnum ?? null;
        if (!$phoneNumber) {
            Log::warning("No phone number found for appointment {$appointment->appoint_id}");
            return false;
        }

        $message = $this->createSMSMessage($appointment);
        $result  = $this->sendSMS($phoneNumber, $message);

        return $result['success'] ?? false;
    }

    /**
     * Send reschedule notification SMS
     */
    public function sendRescheduleSMS($appointment)
    {
        if (!$this->config || empty($this->config->sms_api_key)) {
            return false;
        }

        // Load the pet and owner relationships if not already loaded
        if (!$appointment->relationLoaded('pet') || !$appointment->pet->relationLoaded('owner')) {
            $appointment->load('pet.owner');
        }

        $phoneNumber = $appointment->pet->owner->own_contactnum ?? null;
        if (!$phoneNumber) {
            Log::warning("No phone number found for appointment {$appointment->appoint_id}");
            return false;
        }

        $message = $this->createRescheduleMessage($appointment);
        $result  = $this->sendSMS($phoneNumber, $message);

        return $result['success'] ?? false;
    }

    /**
     * Create SMS message for appointment
     */
    private function createSMSMessage($appointment)
    {
        $ownerName = $appointment->pet->owner->own_name ?? 'Pet Owner';
        $petName = $appointment->pet->pet_name ?? 'your pet';
        $appointDate = \Carbon\Carbon::parse($appointment->appoint_date)->format('M d, Y');
        
        // Convert 24-hour time to 12-hour format
        $appointTime = \Carbon\Carbon::parse($appointment->appoint_time)->format('g:i A');
        
        return "Hello {$ownerName}, this is from Pets 2Go. This is a reminder for {$petName}'s follow-up appointment on {$appointDate} at {$appointTime}. Please arrive 15 minutes early. Thank you!";
    }

    /**
     * Create SMS message for appointment reschedule
     */
    private function createRescheduleMessage($appointment)
    {
        $ownerName = $appointment->pet->owner->own_name ?? 'Pet Owner';
        $petName = $appointment->pet->pet_name ?? 'your pet';
        $appointDate = \Carbon\Carbon::parse($appointment->appoint_date)->format('M d, Y');
        
        // Convert 24-hour time to 12-hour format
        $appointTime = \Carbon\Carbon::parse($appointment->appoint_time)->format('g:i A');
        
        return "Hello {$ownerName}, this is from Pets 2Go. {$petName}'s appointment has been rescheduled to {$appointDate} at {$appointTime}. Please arrive 15 minutes early. Thank you for your understanding!";
    }

    /**
     * Main SMS router
     */
    private function sendSMS($phoneNumber, $message, $isTest = false)
    {
        try {
            $formattedNumber = $this->formatNumber($phoneNumber);

            Log::info("SMS: Attempting to send", [
                'original_number'  => $phoneNumber,
                'formatted_number' => $formattedNumber,
                'message_length'   => strlen($message),
                'is_test'          => $isTest,
                'provider'         => $this->config->sms_provider ?? 'unknown',
            ]);

            $provider = strtolower($this->config->sms_provider ?? 'philsms');
            switch ($provider) {
                case 'twilio':
                    $result = $this->sendTwilioSMS($formattedNumber, $message);
                    break;

                case 'philsms':
                    $result = $this->sendPhilSMS($formattedNumber, $message);
                    break;

                case 'semaphore':
                    $result = $this->sendSemaphoreSMS($formattedNumber, $message);
                    break;

                default:
                    return ['success' => false, 'message' => "Invalid SMS provider: $provider"];
            }

            if ($result['success']) {
                $logMessage = $isTest ? "✅ Test SMS sent successfully" : "✅ SMS sent successfully";
                Log::info($logMessage, ['response' => $result]);
            } else {
                Log::error("❌ SMS failed", [
                    'error'       => $result['message'] ?? 'Unknown error',
                    'full_result' => $result
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error("SMS Exception - " . $e->getMessage(), [
                'exception' => $e->getTraceAsString()
            ]);
            return ['success' => false, 'message' => 'SMS sending failed: ' . $e->getMessage()];
        }
    }

    /**
     * Format numbers: PH numbers -> +63..., leave others as-is
     */
    private function formatNumber($number)
    {
        $number = trim($number);

        // Already E.164 format
        if (strpos($number, '+') === 0) {
            return $number;
        }

        // Strip non-digits
        $number = preg_replace('/\D/', '', $number);

        // If starts with 0 and length >= 10, assume PH local number
        if (substr($number, 0, 1) === '0') {
            $number = '63' . substr($number, 1);
        }

        // Default to PH
        if (strpos($number, '63') !== 0) {
            $number = '63' . $number;
        }

        return '+' . $number;
    }

    /**
     * Twilio sender - FIXED
     */
    private function sendTwilioSMS($phoneNumber, $message)
    {
        $sid   = $this->config->sms_api_key;        // Twilio Account SID
        $token = $this->config->sms_twilio_token;   // Twilio Auth Token
        $from  = $this->config->sms_sender_id;      // Sender ID (phone or alphanumeric)

        if (empty($sid) || empty($token) || empty($from)) {
            return ['success' => false, 'message' => 'Twilio credentials missing'];
        }

        $postData = [
            'To'   => $phoneNumber,
            'Body' => $message,
        ];

        // FIXED: Proper handling of alphanumeric vs phone number sender IDs
        if (preg_match('/^\+?[1-9]\d{10,14}$/', $from)) {
            // It's a phone number - ensure E.164 format
            if (strpos($from, '+') !== 0) {
                $from = '+' . preg_replace('/\D/', '', $from);
            }
            $postData['From'] = $from;
        } else {
            // It's alphanumeric - use as-is, don't modify
            $postData['From'] = $from;
        }

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";

        Log::info('Twilio SMS Request Debug', [
            'original_sender' => $this->config->sms_sender_id,
            'processed_sender' => $postData['From'],
            'to_number' => $phoneNumber,
            'is_phone_number' => preg_match('/^\+?[1-9]\d{10,14}$/', $this->config->sms_sender_id) ? 'yes' : 'no'
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_USERPWD, "$sid:$token");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return ['success' => false, 'message' => $curlError];
        }

        $respData = json_decode($response, true);
        
        Log::info('Twilio SMS Response', [
            'httpCode' => $httpCode,
            'response' => $respData
        ]);

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'message' => 'SMS sent via Twilio', 'response' => $respData];
        }

        return [
            'success'  => false,
            'message'  => $respData['message'] ?? 'Twilio error',
            'response' => $respData
        ];
    }

    /**
     * PhilSMS sender
     */
    private function sendPhilSMS($phoneNumber, $message)
    {
        $apiKey = $this->config->sms_api_key;
        $senderId = $this->config->sms_sender_id;
        $url = $this->config->sms_api_url;

        if (empty($apiKey)) {
            return ['success' => false, 'message' => 'PhilSMS API key missing'];
        }

        $postData = [
            'recipient' => $phoneNumber,
            'sender_id' => $senderId,
            'type' => 'plain',
            'message' => $message
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
            'Accept: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return ['success' => false, 'message' => $curlError];
        }

        $respData = json_decode($response, true);
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'message' => 'SMS sent via PhilSMS', 'response' => $respData];
        }

        return [
            'success' => false,
            'message' => $respData['message'] ?? 'PhilSMS error',
            'response' => $respData
        ];
    }

    /**
     * Semaphore sender
     */
    private function sendSemaphoreSMS($phoneNumber, $message)
    {
        $apiKey = $this->config->sms_api_key;
        $senderId = $this->config->sms_sender_id;
        $url = $this->config->sms_api_url;

        if (empty($apiKey)) {
            return ['success' => false, 'message' => 'Semaphore API key missing'];
        }

        $postData = [
            'apikey' => $apiKey,
            'number' => $phoneNumber,
            'message' => $message,
            'sendername' => $senderId
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return ['success' => false, 'message' => $curlError];
        }

        $respData = json_decode($response, true);
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'message' => 'SMS sent via Semaphore', 'response' => $respData];
        }

        return [
            'success' => false,
            'message' => $respData['message'] ?? 'Semaphore error',
            'response' => $respData
        ];
    }
}