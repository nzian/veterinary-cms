<?php

// 1. CREATE: app/Services/PhilSMSService.php
namespace App\Services;

class PhilSMSService
{
    private $apiKey;
    private $senderId;
    private $apiUrl;
    
    public function __construct()
    {
        $this->apiKey = env('PHILSMS_API_KEY');
        $this->senderId = env('PHILSMS_SENDER_ID', 'YourClinic');
        $this->apiUrl = 'https://app.philsms.com/api/v3/sms/send';
    }
    
    public function send($to, $message)
    {
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
                return ['success' => false, 'error' => 'cURL Error: ' . $curlError];
            }
            
            $responseData = json_decode($response, true);
            
            if ($httpCode === 200 && isset($responseData['data'])) {
                return [
                    'success' => true,
                    'message_id' => $responseData['data']['id'] ?? null
                ];
            } else {
                $error = $responseData['message'] ?? 'Unknown error occurred';
                return ['success' => false, 'error' => $error];
            }
            
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
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
}