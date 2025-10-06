<?php

// app/Services/PhilSMSService.php
namespace App\Services;

class PhilSMSService
{
    // Your actual PhilSMS credentials
    private $apiKey = '1460|y6x2ozwUdq2tYYCq1gRr2ltFe42I7sNRYPuDT7wB';
    private $senderId = 'PHILSMS';
    private $apiUrl = 'https://app.philsms.com/api/v3/sms/send';
    private $isActive = true;
    private static $otpStore = []; // In-memory OTP storage
    
    /**
     * Send SMS
     */
    public function send($to, $message)
    {
        if (!$this->isActive) {
            return ['success' => false, 'error' => 'SMS service is disabled'];
        }
        
        try {
            $postData = [
                'recipient' => $this->formatPhilippineNumber($to),
                'sender_id' => $this->senderId,
                'type' => 'plain',
                'message' => $message
            ];
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $this->apiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($postData),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->apiKey,
                    'Content-Type: application/json',
                    'Accept: application/json'
                ],
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                \Log::error('PhilSMS cURL Error: ' . $curlError);
                return ['success' => false, 'error' => 'cURL Error: ' . $curlError];
            }
            
            $responseData = json_decode($response, true);
            
            if ($httpCode === 200 && isset($responseData['data'])) {
                \Log::info('PhilSMS sent successfully', [
                    'to' => $to,
                    'message_id' => $responseData['data']['id'] ?? null
                ]);
                
                return [
                    'success' => true,
                    'message_id' => $responseData['data']['id'] ?? null,
                    'response' => $responseData
                ];
            } else {
                $error = $responseData['message'] ?? 'Unknown error occurred';
                \Log::error('PhilSMS Error: ' . $error, ['response' => $responseData]);
                return ['success' => false, 'error' => $error, 'response' => $responseData];
            }
            
        } catch (\Exception $e) {
            \Log::error('PhilSMS Exception: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Send OTP
     */
    public function sendOTP($to, $expiryMinutes = 5)
    {
        $otp = random_int(100000, 999999);
        $message = "Your verification code is: {$otp}. Valid for {$expiryMinutes} minutes. Do not share this code.";
        
        $result = $this->send($to, $message);
        
        if ($result['success']) {
            // Store OTP in memory
            $this->storeOTP($to, $otp, $expiryMinutes);
            
            \Log::info('OTP sent and stored', [
                'phone' => $to,
                'expires_in' => $expiryMinutes . ' minutes'
            ]);
        }
        
        return [
            'success' => $result['success'],
            'otp' => $otp, // For testing - REMOVE in production
            'message_id' => $result['message_id'] ?? null,
            'error' => $result['error'] ?? null
        ];
    }
    
    /**
     * Verify OTP
     */
    public function verifyOTP($phone, $inputOTP)
    {
        $phone = $this->formatPhilippineNumber($phone);
        
        if (!isset(self::$otpStore[$phone])) {
            \Log::warning('OTP verification failed: Not found', ['phone' => $phone]);
            return ['valid' => false, 'message' => 'OTP not found or expired'];
        }
        
        $stored = self::$otpStore[$phone];
        
        if (time() > $stored['expires']) {
            unset(self::$otpStore[$phone]);
            \Log::warning('OTP verification failed: Expired', ['phone' => $phone]);
            return ['valid' => false, 'message' => 'OTP expired'];
        }
        
        if ($stored['code'] == $inputOTP) {
            unset(self::$otpStore[$phone]);
            \Log::info('OTP verified successfully', ['phone' => $phone]);
            return ['valid' => true, 'message' => 'OTP verified successfully'];
        }
        
        \Log::warning('OTP verification failed: Invalid code', ['phone' => $phone]);
        return ['valid' => false, 'message' => 'Invalid OTP'];
    }
    
    /**
     * Send appointment reminder
     */
    public function sendAppointmentReminder($to, $patientName, $appointmentDate, $appointmentTime)
    {
        $message = "Hi {$patientName}! Reminder: You have an appointment on {$appointmentDate} at {$appointmentTime}. See you!";
        return $this->send($to, $message);
    }
    
    /**
     * Send appointment confirmation
     */
    public function sendAppointmentConfirmation($to, $patientName, $appointmentDate, $appointmentTime)
    {
        $message = "Hi {$patientName}! Your appointment is confirmed for {$appointmentDate} at {$appointmentTime}. Thank you!";
        return $this->send($to, $message);
    }
    
    /**
     * Send appointment cancellation
     */
    public function sendAppointmentCancellation($to, $patientName, $reason = '')
    {
        $message = "Hi {$patientName}! Your appointment has been cancelled.";
        if ($reason) {
            $message .= " Reason: {$reason}";
        }
        $message .= " Please contact us to reschedule.";
        return $this->send($to, $message);
    }
    
    /**
     * Send bulk SMS
     */
    public function sendBulk(array $recipients, $message)
    {
        $formattedNumbers = array_map(function($number) {
            return $this->formatPhilippineNumber($number);
        }, $recipients);
        
        try {
            $postData = [
                'recipient' => implode(',', $formattedNumbers),
                'sender_id' => $this->senderId,
                'type' => 'plain',
                'message' => $message
            ];
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $this->apiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($postData),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->apiKey,
                    'Content-Type: application/json',
                    'Accept: application/json'
                ],
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $responseData = json_decode($response, true);
            
            \Log::info('Bulk SMS sent', [
                'recipient_count' => count($recipients),
                'success' => $httpCode === 200
            ]);
            
            return [
                'success' => $httpCode === 200,
                'response' => $responseData
            ];
            
        } catch (\Exception $e) {
            \Log::error('Bulk SMS Exception: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Format Philippine phone number
     */
    private function formatPhilippineNumber($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (strlen($phone) == 11 && substr($phone, 0, 2) == '09') {
            $phone = '+63' . substr($phone, 1);
        } elseif (strlen($phone) == 10 && substr($phone, 0, 1) == '9') {
            $phone = '+63' . $phone;
        } elseif (strlen($phone) == 12 && substr($phone, 0, 3) == '639') {
            $phone = '+' . $phone;
        }
        
        return $phone;
    }
    
    /**
     * Store OTP in memory
     */
    private function storeOTP($phone, $otp, $expiryMinutes)
    {
        $phone = $this->formatPhilippineNumber($phone);
        
        self::$otpStore[$phone] = [
            'code' => $otp,
            'expires' => time() + ($expiryMinutes * 60),
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Clean expired OTPs
     */
    public function cleanExpiredOTPs()
    {
        $now = time();
        $cleaned = 0;
        
        foreach (self::$otpStore as $phone => $data) {
            if ($data['expires'] < $now) {
                unset(self::$otpStore[$phone]);
                $cleaned++;
            }
        }
        
        if ($cleaned > 0) {
            \Log::info("Cleaned {$cleaned} expired OTPs");
        }
        
        return $cleaned;
    }
    
    /**
     * Get all stored OTPs (for debugging)
     */
    public function getStoredOTPs()
    {
        return self::$otpStore;
    }
}

// ============================================
// READY-TO-USE EXAMPLES
// ============================================

// Example 1: Send OTP for login
/*
$sms = new \App\Services\PhilSMSService();
$result = $sms->sendOTP('09123456789');

if ($result['success']) {
    echo "OTP sent! Check your phone.";
    // For testing: echo "OTP is: " . $result['otp'];
}
*/

// Example 2: Verify OTP
/*
$sms = new \App\Services\PhilSMSService();
$verification = $sms->verifyOTP('09123456789', '123456');

if ($verification['valid']) {
    echo "OTP verified! Proceed with login.";
} else {
    echo "Error: " . $verification['message'];
}
*/

// Example 3: Send appointment reminder
/*
$sms = new \App\Services\PhilSMSService();
$sms->sendAppointmentReminder(
    '09123456789',
    'Juan Dela Cruz',
    'October 10, 2025',
    '2:00 PM'
);
*/

// Example 4: Send custom message
/*
$sms = new \App\Services\PhilSMSService();
$sms->send('09123456789', 'Your lab results are ready for pickup!');
*/

// Example 5: Bulk notification
/*
$sms = new \App\Services\PhilSMSService();
$patients = ['09123456789', '09987654321', '09111222333'];
$sms->sendBulk($patients, 'Clinic will be closed on October 15 for maintenance.');
*/

// Example 6: Use in Laravel routes (API endpoints)
/*
// In routes/api.php:

Route::post('/auth/send-otp', function(Request $request) {
    $sms = new \App\Services\PhilSMSService();
    $result = $sms->sendOTP($request->phone);
    return response()->json($result);
});

Route::post('/auth/verify-otp', function(Request $request) {
    $sms = new \App\Services\PhilSMSService();
    $result = $sms->verifyOTP($request->phone, $request->otp);
    return response()->json($result);
});
*/