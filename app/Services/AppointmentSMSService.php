<?php
namespace App\Services;

use App\Models\Appointment;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AppointmentSMSService
{
    private $philSMS;
    
    public function __construct(PhilSMSService $philSMS)
    {
        $this->philSMS = $philSMS;
    }
    
    /**
     * Handle SMS notification for newly created appointment
     */
    public function sendAppointmentCreatedSMS($appointmentId)
    {
        $appointment = Appointment::with(['pet.owner', 'user'])->find($appointmentId);
        
        if (!$appointment) {
            Log::warning("Appointment not found for SMS: {$appointmentId}");
            return false;
        }
        
        $phoneNumber = $appointment->pet->owner->own_contactnum ?? null;
        
        if (!$phoneNumber) {
            Log::warning("No phone number found for appointment ID: {$appointmentId}");
            return false;
        }
        
        $message = $this->getCreationTemplate($appointment);
        return $this->sendSMS($phoneNumber, $message, $appointmentId, 'creation');
    }
    
    /**
     * Handle SMS notification for rescheduled appointment
     */
    public function sendAppointmentRescheduledSMS($appointmentId, $oldDate = null, $oldTime = null)
    {
        $appointment = Appointment::with(['pet.owner', 'user'])->find($appointmentId);
        
        if (!$appointment) {
            Log::warning("Appointment not found for reschedule SMS: {$appointmentId}");
            return false;
        }
        
        $phoneNumber = $appointment->pet->owner->own_contactnum ?? null;
        
        if (!$phoneNumber) {
            Log::warning("No phone number found for appointment ID: {$appointmentId}");
            return false;
        }
        
        $message = $this->getRescheduleTemplate($appointment, $oldDate, $oldTime);
        return $this->sendSMS($phoneNumber, $message, $appointmentId, 'reschedule');
    }
    
    /**
     * Legacy method for backward compatibility
     */
    public function handleFollowUpAppointment($appointmentId)
    {
        return $this->sendAppointmentCreatedSMS($appointmentId);
    }
    
    /**
     * Send SMS helper method
     */
    private function sendSMS($phoneNumber, $message, $appointmentId, $type = 'general')
    {
        try {
            $result = $this->philSMS->send($phoneNumber, $message);
            
            if ($result['success']) {
                Log::info("SMS ({$type}) sent successfully for appointment ID: {$appointmentId}");
                return true;
            } else {
                Log::error("SMS ({$type}) failed for appointment ID: {$appointmentId} - " . ($result['error'] ?? 'Unknown error'));
                return false;
            }
        } catch (\Exception $e) {
            Log::error("SMS ({$type}) exception for appointment ID: {$appointmentId} - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get SMS template for appointment creation
     */
    private function getCreationTemplate($appointment)
    {
        $doctorName = $appointment->user->name ?? 'Doctor';
        $petName = $appointment->pet->pet_name ?? 'Pet';
        $ownerName = $appointment->pet->owner->own_name ?? 'Pet Owner';
        $appointType = $appointment->appoint_type ?? 'appointment';
        $branchName = $appointment->user->branch->branch_name ?? 'our clinic';
        
        $dateTime = Carbon::parse($appointment->appoint_date . ' ' . $appointment->appoint_time)->format('M j, Y \a\t g:i A');
        
        return "Hello {$ownerName}, your {$appointType} for {$petName} has been confirmed for {$dateTime} with Dr. {$doctorName} at {$branchName}. Please arrive 15 minutes early. Thank you!";
    }
    
    /**
     * Get SMS template for appointment rescheduling
     */
    private function getRescheduleTemplate($appointment, $oldDate = null, $oldTime = null)
    {
        $doctorName = $appointment->user->name ?? 'Doctor';
        $petName = $appointment->pet->pet_name ?? 'Pet';
        $ownerName = $appointment->pet->owner->own_name ?? 'Pet Owner';
        $appointType = $appointment->appoint_type ?? 'appointment';
        $branchName = $appointment->user->branch->branch_name ?? 'our clinic';
        
        $newDateTime = Carbon::parse($appointment->appoint_date . ' ' . $appointment->appoint_time)->format('M j, Y \a\t g:i A');
        
        if ($oldDate && $oldTime) {
            $oldDateTime = Carbon::parse($oldDate . ' ' . $oldTime)->format('M j, Y \a\t g:i A');
            return "Hello {$ownerName}, your {$appointType} for {$petName} has been rescheduled from {$oldDateTime} to {$newDateTime} with Dr. {$doctorName} at {$branchName}. Please arrive 15 minutes early. Thank you!";
        }
        
        return "Hello {$ownerName}, your {$appointType} for {$petName} has been rescheduled to {$newDateTime} with Dr. {$doctorName} at {$branchName}. Please arrive 15 minutes early. Thank you!";
    }
}