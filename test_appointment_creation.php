<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Testing Appointment Creation ===\n";

// Test creating an appointment with "General Follow-up"
$testAppointment = DB::table('tbl_appoint')->insertGetId([
    'appoint_time' => '09:00:00',
    'appoint_status' => 'scheduled',
    'appoint_date' => '2025-12-10',
    'appoint_description' => 'Test appointment',
    'appoint_type' => 'General Follow-up',
    'pet_id' => 1, // Adjust as needed
    'user_id' => 1, // Adjust as needed
    'created_at' => now(),
    'updated_at' => now()
]);

echo "Created test appointment with ID: {$testAppointment}\n";

// Now check what was actually saved
$savedAppointment = DB::table('tbl_appoint')->where('appoint_id', $testAppointment)->first();

echo "Saved appointment type: [{$savedAppointment->appoint_type}]\n";

// Clean up the test record
DB::table('tbl_appoint')->where('appoint_id', $testAppointment)->delete();
echo "Test appointment deleted\n";

?>