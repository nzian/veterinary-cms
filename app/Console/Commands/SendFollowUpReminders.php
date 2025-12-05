<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Appointment;
use App\Services\DynamicSMSService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendFollowUpReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-followup-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send SMS reminders for follow-up appointments scheduled tomorrow';

    protected $smsService;

    /**
     * Create a new command instance.
     */
    public function __construct(DynamicSMSService $smsService)
    {
        parent::__construct();
        $this->smsService = $smsService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tomorrow = Carbon::tomorrow()->toDateString();
        
        // Get all scheduled follow-up appointments for tomorrow
        $appointments = Appointment::with(['pet.owner'])
            ->where('appoint_date', $tomorrow)
            ->where('appoint_status', 'scheduled')
            ->whereIn('appoint_type', ['General Follow-up', 'Vaccination Follow-up', 'Deworming Follow-up', 'Post-Surgical Recheck'])
            ->get();

        if ($appointments->isEmpty()) {
            $this->info('No follow-up appointments scheduled for tomorrow.');
            Log::info('Follow-up reminder check: No appointments found for ' . $tomorrow);
            return 0;
        }

        $successCount = 0;
        $failCount = 0;

        foreach ($appointments as $appointment) {
            try {
                $result = $this->smsService->sendFollowUpSMS($appointment);
                
                if ($result) {
                    $successCount++;
                    $this->info("✓ SMS sent for appointment #{$appointment->appoint_id} - {$appointment->pet->pet_name}");
                    Log::info("Follow-up reminder sent for appointment #{$appointment->appoint_id}");
                } else {
                    $failCount++;
                    $this->warn("✗ Failed to send SMS for appointment #{$appointment->appoint_id}");
                    Log::warning("Follow-up reminder failed for appointment #{$appointment->appoint_id}");
                }
            } catch (\Exception $e) {
                $failCount++;
                $this->error("✗ Error sending SMS for appointment #{$appointment->appoint_id}: " . $e->getMessage());
                Log::error("Follow-up reminder error for appointment #{$appointment->appoint_id}: " . $e->getMessage());
            }
        }

        $this->info("\nSummary:");
        $this->info("Total appointments: " . $appointments->count());
        $this->info("SMS sent successfully: {$successCount}");
        $this->info("Failed: {$failCount}");

        Log::info("Follow-up reminder batch completed", [
            'date' => $tomorrow,
            'total' => $appointments->count(),
            'success' => $successCount,
            'failed' => $failCount
        ]);

        return 0;
    }
}
