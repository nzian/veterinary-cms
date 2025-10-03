<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AutoCancelOldAppointments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:auto-cancel-old-appointments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark missed appointments, reschedule to next available time slot for up to 2 weeks, then cancel';

    /**
     * Available appointment time slots
     */
    private $availableTimeSlots = [
        '09:00:00', // 09:00 AM
        '10:00:00', // 10:00 AM
        '11:00:00', // 11:00 AM
        '13:00:00', // 01:00 PM
        '14:00:00', // 02:00 PM
        '15:00:00', // 03:00 PM
        '16:00:00', // 04:00 PM
        '17:00:00', // 05:00 PM
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();
        $this->info("Running auto-cancel/reschedule for appointments as of {$today->format('Y-m-d')}");
        
        // Find appointments that are YESTERDAY or older and not arrived/completed/cancelled
        $missedAppointments = Appointment::with('pet.owner')
            ->where('appoint_date', '<', $today)
            ->whereNotIn('appoint_status', ['arrived', 'completed', 'cancelled'])
            ->get();

        if ($missedAppointments->isEmpty()) {
            $this->info('No missed appointments found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$missedAppointments->count()} missed appointment(s) to process.");

        $markedMissed = 0;
        $rescheduled = 0;
        $cancelled = 0;

        foreach ($missedAppointments as $appointment) {
            $petName = $appointment->pet->pet_name ?? 'Unknown';
            $ownerName = $appointment->pet->owner->own_name ?? 'Unknown';
            $currentDate = Carbon::parse($appointment->appoint_date);
            $currentTime = $appointment->appoint_time;

            // Set original_date if not set (first time being processed as missed)
            if (!$appointment->original_date) {
                $appointment->original_date = $appointment->appoint_date;
                $appointment->save();
            }

            $originalDate = Carbon::parse($appointment->original_date);
            $daysSinceOriginal = $today->diffInDays($originalDate);

            // First, mark as missed if not already
            if ($appointment->appoint_status !== 'missed' && $appointment->appoint_status !== 'rescheduled') {
                $appointment->update([
                    'appoint_status' => 'missed',
                ]);
                
                $this->warn("⚠ Marked as Missed: Appointment #{$appointment->appoint_id} - {$petName} ({$ownerName})");
                $this->line("  Date: {$currentDate->format('Y-m-d')} at {$currentTime}");
                
                Log::info("Appointment {$appointment->appoint_id} marked as missed", [
                    'pet' => $petName,
                    'owner' => $ownerName,
                    'appointment_date' => $currentDate->format('Y-m-d'),
                    'appointment_time' => $currentTime,
                ]);
                
                $markedMissed++;
            }

           if ($daysSinceOriginal >= 14) {
    $appointment->update([
        'appoint_status' => 'cancelled',
    ]);
    
    $this->error("✗ Cancelled: Appointment #{$appointment->appoint_id} - {$petName} ({$ownerName})");
    $this->line("  Original date: {$originalDate->format('Y-m-d')} ({$daysSinceOriginal} days ago)");
    $this->line("  Reason: Exceeded 2-week grace period");
    
    // **ADD: Send SMS notification for auto-cancellation**
    try {
        $smsService = new \App\Services\DynamicSMSService();
        $smsResult = $smsService->sendAutoCancelSMS($appointment, 'exceeded 2-week grace period');
        
        if ($smsResult) {
            $this->info("  ✓ Cancellation SMS notification sent");
            Log::info("Auto-cancel SMS sent for appointment {$appointment->appoint_id}");
        } else {
            $this->warn("  ⚠ Cancellation SMS notification failed");
            Log::warning("Auto-cancel SMS failed for appointment {$appointment->appoint_id}");
        }
    } catch (\Exception $e) {
        $this->error("  ✗ SMS error: " . $e->getMessage());
        Log::error("Auto-cancel SMS error for appointment {$appointment->appoint_id}: " . $e->getMessage());
    }
    
    Log::info("Appointment {$appointment->appoint_id} cancelled - exceeded 2 week grace period", [
        'pet' => $petName,
        'owner' => $ownerName,
        'original_date' => $originalDate->format('Y-m-d'),
        'last_scheduled_date' => $appointment->appoint_date,
        'days_since_original' => $daysSinceOriginal,
    ]);
    
    $cancelled++;
    continue;
}

            // Find next available time slot
            $nextAvailable = $this->findNextAvailableSlot($today);
            
            if (!$nextAvailable) {
                $this->warn("  No available slots found for next 7 days, skipping for now.");
                continue;
            }

            $appointment->update([
                'appoint_date' => $nextAvailable['date'],
                'appoint_time' => $nextAvailable['time'],
                'appoint_status' => 'rescheduled',
                'reschedule_count' => $appointment->reschedule_count + 1,
                'last_rescheduled_at' => now(),
            ]);

            $this->info("✓ Rescheduled: Appointment #{$appointment->appoint_id} - {$petName} ({$ownerName})");
            $this->line("  From: {$currentDate->format('Y-m-d')} at {$currentTime}");
            $this->line("  To:   {$nextAvailable['date']} at " . Carbon::parse($nextAvailable['time'])->format('h:i A'));
            $this->line("  Original date: {$originalDate->format('Y-m-d')} ({$daysSinceOriginal} days ago)");
            $this->line("  Reschedule count: {$appointment->reschedule_count} | Days remaining: " . (14 - $daysSinceOriginal));

            Log::info("Appointment {$appointment->appoint_id} rescheduled", [
                'pet' => $petName,
                'owner' => $ownerName,
                'from_date' => $currentDate->format('Y-m-d'),
                'from_time' => $currentTime,
                'to_date' => $nextAvailable['date'],
                'to_time' => $nextAvailable['time'],
                'original_date' => $originalDate->format('Y-m-d'),
                'reschedule_count' => $appointment->reschedule_count,
                'days_since_original' => $daysSinceOriginal,
                'days_remaining_before_cancel' => 14 - $daysSinceOriginal,
            ]);

            $rescheduled++;
        }

        $this->newLine();
        $this->info("═══════════════════════════════════════");
        $this->info("Summary:");
        $this->info("  Marked as Missed: {$markedMissed} appointment(s)");
        $this->info("  Rescheduled:      {$rescheduled} appointment(s)");
        $this->info("  Cancelled:        {$cancelled} appointment(s)");
        $this->info("═══════════════════════════════════════");
        
        return Command::SUCCESS;
    }

    /**
     * Find the next available time slot
     * 
     * @param Carbon $startDate
     * @return array|null ['date' => 'Y-m-d', 'time' => 'H:i:s']
     */
    private function findNextAvailableSlot(Carbon $startDate)
    {
        // Check up to 7 days ahead
        for ($dayOffset = 0; $dayOffset < 7; $dayOffset++) {
            $checkDate = $startDate->copy()->addDays($dayOffset);
            
            // Skip weekends (optional - remove if you want to include weekends)
            if ($checkDate->isWeekend()) {
                continue;
            }

            // Check each time slot for this date
            foreach ($this->availableTimeSlots as $timeSlot) {
                // Check if this time slot is already booked
                $isBooked = Appointment::where('appoint_date', $checkDate->format('Y-m-d'))
                    ->where('appoint_time', $timeSlot)
                    ->whereNotIn('appoint_status', ['cancelled'])
                    ->exists();

                if (!$isBooked) {
                    // Found an available slot!
                    return [
                        'date' => $checkDate->format('Y-m-d'),
                        'time' => $timeSlot,
                    ];
                }
            }
        }

        // No available slots found in the next 7 days
        return null;
    }
}