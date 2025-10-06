<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class DynamicSMSService
{
    // Built-in PhilSMS Configuration - NO DATABASE NEEDED
    private $apiKey = '1460|y6x2ozwUdq2tYYCq1gRr2ltFe42I7sNRYPuDT7wB';
    private $senderId = 'PHILSMS';
    private $apiUrl = 'https://app.philsms.com/api/v3/sms/send';
    private $isActive = true;

    public function __construct()
    {
        // No database lookup needed - everything is built-in!
    }

    /**
     * Test SMS
     */
    public function sendTestSMS($phoneNumber)
    {
        if (!$this->isActive) {
            return ['success' => false, 'message' => 'SMS not configured'];
        }

        $message = "Test SMS from {$this->senderId}. Configuration is working!";
        return $this->sendSMS($phoneNumber, $message, true);
    }

    /**
     * Send SMS for NEW appointment creation
     */
    public function sendNewAppointmentSMS($appointment)
    {
        if (!$this->isActive) {
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

        $message = $this->createNewAppointmentMessage($appointment);
        $result  = $this->sendSMS($phoneNumber, $message);

        return $result['success'] ?? false;
    }

    /**
     * Auto-send for follow-ups
     */
    public function sendFollowUpSMS($appointment)
    {
        if (!$this->isActive) {
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
        if (!$this->isActive) {
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
     * Create SMS message for NEW appointment
     */
    private function createNewAppointmentMessage($appointment)
    {
        $ownerName = $appointment->pet->owner->own_name ?? 'Pet Owner';
        $petName = $appointment->pet->pet_name ?? 'your pet';
        $appointDate = \Carbon\Carbon::parse($appointment->appoint_date)->format('M d, Y');
        $appointTime = \Carbon\Carbon::parse($appointment->appoint_time)->format('g:i A');
        
        return "Hello {$ownerName}, this is from Pets 2Go. Your appointment for {$petName} has been scheduled on {$appointDate} at {$appointTime}. Please arrive 15 minutes early. Thank you!";
    }

    /**
     * Create SMS message for follow-up appointment
     */
    private function createSMSMessage($appointment)
    {
        $ownerName = $appointment->pet->owner->own_name ?? 'Pet Owner';
        $petName = $appointment->pet->pet_name ?? 'your pet';
        $appointDate = \Carbon\Carbon::parse($appointment->appoint_date)->format('M d, Y');
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
        $appointTime = \Carbon\Carbon::parse($appointment->appoint_time)->format('g:i A');
        
        return "Hello {$ownerName}, this is from Pets 2Go. {$petName}'s appointment has been rescheduled to {$appointDate} at {$appointTime}. Please arrive 15 minutes early. Thank you for your understanding!";
    }

    /**
     * Main SMS sender - PhilSMS only
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
                'provider'         => 'PhilSMS',
            ]);

            $result = $this->sendPhilSMS($formattedNumber, $message);

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
     * PhilSMS sender - Built-in credentials
     */
    private function sendPhilSMS($phoneNumber, $message)
    {
        $postData = [
            'recipient' => $phoneNumber,
            'sender_id' => $this->senderId,
            'type' => 'plain',
            'message' => $message
        ];

        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return ['success' => false, 'message' => $curlError];
        }

        $respData = json_decode($response, true);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true, 
                'message' => 'SMS sent via PhilSMS', 
                'response' => $respData,
                'message_id' => $respData['data']['id'] ?? null
            ];
        }

        return [
            'success' => false,
            'message' => $respData['message'] ?? 'PhilSMS error',
            'response' => $respData
        ];
    }

    /**
     * Send SMS when appointment is referred to another branch
     */
    public function sendReferralSMS($appointment, $referral)
    {
        if (!$this->isActive) {
            return false;
        }

        if (!$appointment->relationLoaded('pet') || !$appointment->pet->relationLoaded('owner')) {
            $appointment->load('pet.owner');
        }

        $phoneNumber = $appointment->pet->owner->own_contactnum ?? null;
        if (!$phoneNumber) {
            Log::warning("No phone number found for appointment {$appointment->appoint_id}");
            return false;
        }

        $message = $this->createReferralMessage($appointment, $referral);
        $result  = $this->sendSMS($phoneNumber, $message);

        return $result['success'] ?? false;
    }

    /**
     * Send SMS when appointment is auto-cancelled
     */
    public function sendAutoCancelSMS($appointment, $reason = 'exceeded grace period')
    {
        if (!$this->isActive) {
            return false;
        }

        if (!$appointment->relationLoaded('pet') || !$appointment->pet->relationLoaded('owner')) {
            $appointment->load('pet.owner');
        }

        $phoneNumber = $appointment->pet->owner->own_contactnum ?? null;
        if (!$phoneNumber) {
            Log::warning("No phone number found for appointment {$appointment->appoint_id}");
            return false;
        }

        $message = $this->createAutoCancelMessage($appointment, $reason);
        $result  = $this->sendSMS($phoneNumber, $message);

        return $result['success'] ?? false;
    }

    /**
     * Create SMS message for referral to another branch
     */
    private function createReferralMessage($appointment, $referral)
    {
        $ownerName = $appointment->pet->owner->own_name ?? 'Pet Owner';
        $petName = $appointment->pet->pet_name ?? 'your pet';
        $appointDate = \Carbon\Carbon::parse($appointment->appoint_date)->format('M d, Y');
        $appointTime = \Carbon\Carbon::parse($appointment->appoint_time)->format('g:i A');
        
        $fromBranch = $referral->refByBranch->branch_name ?? 'our clinic';
        $toBranch = $referral->refToBranch->branch_name ?? 'another branch';
        $toBranchAddress = $referral->refToBranch->branch_address ?? 'Please contact us for address';
        $toBranchContact = $referral->refToBranch->branch_contactNum ?? '';
        
        $message = "Hello {$ownerName},\n\n"
                 . "{$petName}'s appointment on {$appointDate} at {$appointTime} has been REFERRED from {$fromBranch} to:\n\n"
                 . "Branch: {$toBranch}\n"
                 . "Address: {$toBranchAddress}\n";
        
        if ($toBranchContact) {
            $message .= "Contact: {$toBranchContact}\n";
        }
        
        $message .= "\nReason: " . ($referral->ref_description ?? 'Specialized care required') . "\n\n"
                  . "Please contact the referred branch to confirm your appointment.\n\n"
                  . "- Pets 2Go Veterinary Clinic";
        
        return $message;
    }

    /**
     * Create SMS message for AUTO appointment reschedule
     */
    private function createAutoRescheduleMessage($appointment, $originalDate, $originalTime)
    {
        $ownerName = $appointment->pet->owner->own_name ?? 'Pet Owner';
        $petName = $appointment->pet->pet_name ?? 'your pet';
        $newDate = \Carbon\Carbon::parse($appointment->appoint_date)->format('M d, Y');
        $newTime = \Carbon\Carbon::parse($appointment->appoint_time)->format('g:i A');
        
        return "Hello {$ownerName},\n\n"
             . "{$petName}'s appointment has been rescheduled to {$newDate} at {$newTime}.\n\n"
             . "Thank you!\n\n"
             . "- Pets 2Go Veterinary Clinic";
    }

    /**
     * Send auto-reschedule notification SMS
     */
    public function sendAutoRescheduleSMS($appointment, $originalDate, $originalTime)
    {
        if (!$this->isActive) {
            return false;
        }

        if (!$appointment->relationLoaded('pet') || !$appointment->pet->relationLoaded('owner')) {
            $appointment->load('pet.owner');
        }

        $phoneNumber = $appointment->pet->owner->own_contactnum ?? null;
        if (!$phoneNumber) {
            Log::warning("No phone number found for appointment {$appointment->appoint_id}");
            return false;
        }

        $message = $this->createAutoRescheduleMessage($appointment, $originalDate, $originalTime);
        $result  = $this->sendSMS($phoneNumber, $message);

        return $result['success'] ?? false;
    }

    /**
     * Create SMS message for auto-cancelled appointment
     */
    private function createAutoCancelMessage($appointment, $reason)
    {
        $ownerName = $appointment->pet->owner->own_name ?? 'Pet Owner';
        $petName = $appointment->pet->pet_name ?? 'your pet';
        $originalDate = \Carbon\Carbon::parse($appointment->original_date ?? $appointment->appoint_date)->format('M d, Y');
        
        return "Hello {$ownerName},\n\n"
             . "{$petName}'s appointment from {$originalDate} has been cancelled.\n\n"
             . "Thank you!\n\n"
             . "- Pets 2Go Veterinary Clinic";
    }

    /**
     * Send SMS when appointment is completed
     */
    public function sendCompletionSMS($appointment)
    {
        if (!$this->isActive) {
            return false;
        }

        if (!$appointment->relationLoaded('pet') || !$appointment->pet->relationLoaded('owner')) {
            $appointment->load('pet.owner');
        }

        $phoneNumber = $appointment->pet->owner->own_contactnum ?? null;
        if (!$phoneNumber) {
            Log::warning("No phone number found for appointment {$appointment->appoint_id}");
            return false;
        }

        $message = $this->createCompletionMessage($appointment);
        $result  = $this->sendSMS($phoneNumber, $message);

        return $result['success'] ?? false;
    }

    /**
     * Create SMS message for completed appointment
     */
    private function createCompletionMessage($appointment)
    {
        $ownerName = $appointment->pet->owner->own_name ?? 'Pet Owner';
        $petName = $appointment->pet->pet_name ?? 'your pet';
        $appointDate = \Carbon\Carbon::parse($appointment->appoint_date)->format('M d, Y');
        
        return "Hello {$ownerName},\n\n"
             . "{$petName}'s appointment on {$appointDate} has been COMPLETED.\n\n"
             . "Thank you for choosing Pets 2Go Veterinary Clinic. We hope to see you again soon!\n\n"
             . "- Pets 2Go Veterinary Clinic";
    }
}