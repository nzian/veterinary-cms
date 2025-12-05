<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== ALL Appointment Records (No Filters) ===\n";

// Get ALL records without any filters
$allAppointments = DB::table('tbl_appoint')->orderBy('appoint_date', 'desc')->get();

echo "Total records found: " . count($allAppointments) . "\n\n";

foreach ($allAppointments as $appointment) {
    echo "ID: {$appointment->appoint_id}\n";
    echo "Date: {$appointment->appoint_date}\n";
    echo "Type: [{$appointment->appoint_type}]\n";
    echo "Status: {$appointment->appoint_status}\n";
    echo "Pet ID: {$appointment->pet_id}\n";
    echo "Created: {$appointment->created_at}\n";
    echo "Updated: {$appointment->updated_at}\n";
    echo "===================\n";
}

// Specifically look for appointments with "Follow-up" (exact match)
$followUpRecords = DB::table('tbl_appoint')
    ->where('appoint_type', 'Follow-up')
    ->get();

echo "\n=== Records with EXACTLY 'Follow-up' type ===\n";
echo "Count: " . count($followUpRecords) . "\n";

foreach ($followUpRecords as $record) {
    echo "ID: {$record->appoint_id} - Date: {$record->appoint_date} - Type: [{$record->appoint_type}]\n";
}

?>