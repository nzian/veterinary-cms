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
    
    public function handleFollowUpAppointment($appointmentId)
    {
        // Get appointment with relationships - matching your existing structure
        $appointment = Appointment::with(['pet.owner', 'user'])->find($appointmentId);
        
        if (!$appointment || strtolower($appointment->appoint_type) !== 'follow-up') {
            return false;
        }
        
        // Get phone number from owner's contact
        $phoneNumber = $appointment->pet->owner->own_contactnum ?? null;
        
        if (!$phoneNumber) {
            Log::warning("No phone number found for appointment ID: {$appointmentId}");
            return false;
        }
        
        // Send immediate creation confirmation SMS
        $this->sendCreationSMS($appointment, $phoneNumber);
        
        // Schedule reminder SMS notifications using Laravel jobs
        $this->scheduleReminderSMS($appointment, $phoneNumber);
        
        return true;
    }
    
    private function sendCreationSMS($appointment, $phoneNumber)
    {
        $message = $this->getCreationTemplate($appointment);
        
        try {
            $result = $this->philSMS->send($phoneNumber, $message);
            
            if ($result['success']) {
                Log::info("Creation SMS sent successfully for appointment ID: {$appointment->appoint_id}");
            } else {
                Log::error("Creation SMS failed for appointment ID: {$appointment->appoint_id} - " . $result['error']);
            }
        } catch (\Exception $e) {
            Log::error("Creation SMS exception for appointment ID: {$appointment->appoint_id} - " . $e->getMessage());
        }
    }
    
    private function scheduleReminderSMS($appointment, $phoneNumber)
{
    // Skip scheduling - just log what would have been scheduled
    Log::info("Would schedule SMS reminders for appointment {$appointment->appoint_id} but queue is disabled");
    
    // Or you could send all SMS immediately (not recommended)
    // $this->philSMS->send($phoneNumber, $this->get24HourTemplate($appointment));
    // $this->philSMS->send($phoneNumber, $this->get1HourTemplate($appointment));
}
    
    private function getCreationTemplate($appointment)
    {
        $doctorName = $appointment->user->name ?? 'Doctor';
        $petName = $appointment->pet->pet_name ?? 'Pet';
        $ownerName = $appointment->pet->owner->own_name ?? 'Pet Owner';
        
        return "Hello {$ownerName}, your follow-up appointment for {$petName} has been confirmed for " .
               Carbon::parse($appointment->appoint_date . ' ' . $appointment->appoint_time)->format('M j, Y \a\t g:i A') .
               " with Dr. {$doctorName}. Thank you for choosing our clinic.";
    }
    
    private function get24HourTemplate($appointment)
    {
        $doctorName = $appointment->user->name ?? 'Doctor';
        $petName = $appointment->pet->pet_name ?? 'Pet';
        $ownerName = $appointment->pet->owner->own_name ?? 'Pet Owner';
        
        return "Reminder: {$ownerName}, you have a follow-up appointment for {$petName} tomorrow, " .
               Carbon::parse($appointment->appoint_date . ' ' . $appointment->appoint_time)->format('M j, Y \a\t g:i A') .
               " with Dr. {$doctorName}. Please arrive 15 minutes early.";
    }
    
    private function get1HourTemplate($appointment)
    {
        $doctorName = $appointment->user->name ?? 'Doctor';
        $petName = $appointment->pet->pet_name ?? 'Pet';
        $ownerName = $appointment->pet->owner->own_name ?? 'Pet Owner';
        
        return "Final reminder: {$ownerName}, your follow-up appointment for {$petName} with Dr. {$doctorName} is in 1 hour at " .
               Carbon::parse($appointment->appoint_time)->format('g:i A') . 
               ". Please arrive on time. Thank you!";
    }
}