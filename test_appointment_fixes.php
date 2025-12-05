<?php
/**
 * Test script to verify appointment duplicate prevention fixes
 * Run with: php test_appointment_fixes.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Visit;
use App\Models\Appointment;
use App\Models\Pet;
use Carbon\Carbon;

// Initialize Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->boot();

echo "🔧 Testing Appointment Duplicate Prevention Fixes\n";
echo "================================================\n\n";

// Test 1: Check for existing duplicate appointments
echo "📊 Test 1: Checking for existing duplicate appointments...\n";

$duplicates = DB::table('tbl_appoint as a1')
    ->join('tbl_appoint as a2', function($join) {
        $join->on('a1.pet_id', '=', 'a2.pet_id')
             ->on('a1.appoint_date', '=', 'a2.appoint_date')
             ->on('a1.appoint_type', '=', 'a2.appoint_type')
             ->where('a1.appoint_id', '<', DB::raw('a2.appoint_id'));
    })
    ->whereIn('a1.appoint_status', ['scheduled', 'confirmed', 'pending'])
    ->whereIn('a2.appoint_status', ['scheduled', 'confirmed', 'pending'])
    ->select('a1.appoint_id', 'a2.appoint_id', 'a1.pet_id', 'a1.appoint_date', 'a1.appoint_type')
    ->get();

if ($duplicates->count() > 0) {
    echo "⚠️  Found {$duplicates->count()} duplicate appointment pairs:\n";
    foreach ($duplicates as $dup) {
        echo "   - Pet {$dup->pet_id}: Appointments {$dup->appoint_id} & {$dup->appoint_id} on {$dup->appoint_date} ({$dup->appoint_type})\n";
    }
} else {
    echo "✅ No duplicate appointments found.\n";
}

echo "\n";

// Test 2: Check appointment status distribution
echo "📊 Test 2: Checking appointment status distribution...\n";

$statusCounts = DB::table('tbl_appoint')
    ->select('appoint_status', DB::raw('COUNT(*) as count'))
    ->groupBy('appoint_status')
    ->orderBy('count', 'desc')
    ->get();

foreach ($statusCounts as $status) {
    echo "   - {$status->appoint_status}: {$status->count} appointments\n";
}

echo "\n";

// Test 3: Check recent vaccination/deworming visits without follow-up appointments
echo "📊 Test 3: Checking recent vaccination/deworming visits...\n";

$recentVisits = DB::table('tbl_visit_record as v')
    ->join('tbl_visit_service as vs', 'v.visit_id', '=', 'vs.visit_id')
    ->join('tbl_serv as s', 'vs.serv_id', '=', 's.serv_id')
    ->where('v.visit_status', 'completed')
    ->where('v.visit_date', '>=', Carbon::now()->subDays(7))
    ->whereIn(DB::raw('LOWER(s.serv_type)'), ['vaccination', 'deworming'])
    ->select('v.visit_id', 'v.pet_id', 'v.visit_date', 's.serv_type', 's.serv_name')
    ->distinct()
    ->get();

echo "Found {$recentVisits->count()} recent vaccination/deworming visits\n";

foreach ($recentVisits as $visit) {
    // Check if follow-up appointment exists
    $followUp = DB::table('tbl_appoint')
        ->where('pet_id', $visit->pet_id)
        ->where('appoint_date', '>', $visit->visit_date)
        ->where('appoint_type', 'LIKE', '%Follow-up%')
        ->whereIn('appoint_status', ['scheduled', 'confirmed', 'pending'])
        ->first();
    
    $hasFollowUp = $followUp ? '✅ Has follow-up' : '❌ No follow-up';
    echo "   - Visit {$visit->visit_id} (Pet {$visit->pet_id}): {$visit->serv_type} on {$visit->visit_date} - {$hasFollowUp}\n";
}

echo "\n";

// Test 4: Check if status normalization is working
echo "📊 Test 4: Checking appointment status normalization...\n";

$capitalizedStatuses = DB::table('tbl_appoint')
    ->whereRaw('appoint_status REGEXP "^[A-Z]"')
    ->select('appoint_status', DB::raw('COUNT(*) as count'))
    ->groupBy('appoint_status')
    ->get();

if ($capitalizedStatuses->count() > 0) {
    echo "⚠️  Found appointments with capitalized statuses:\n";
    foreach ($capitalizedStatuses as $status) {
        echo "   - '{$status->appoint_status}': {$status->count} appointments\n";
    }
    echo "   💡 These should be lowercase for consistency.\n";
} else {
    echo "✅ All appointment statuses are properly normalized.\n";
}

echo "\n";

// Test 5: Suggest cleanup actions
echo "🔧 Test 5: Cleanup suggestions...\n";

if ($duplicates->count() > 0) {
    echo "   1. Review and merge duplicate appointments\n";
    echo "   2. Update appointment creation logic to prevent future duplicates\n";
}

if ($capitalizedStatuses->count() > 0) {
    echo "   3. Normalize appointment statuses to lowercase\n";
}

echo "   4. Ensure all vaccination/deworming visits have proper follow-up appointments\n";
echo "   5. Verify SMS notifications are sent for scheduled appointments\n";

echo "\n✅ Appointment fix verification completed!\n";
echo "================================================\n";

// Optional: Show recent appointments for verification
echo "\n📋 Recent appointments (last 10):\n";
$recentAppointments = DB::table('tbl_appoint as a')
    ->join('tbl_pet as p', 'a.pet_id', '=', 'p.pet_id')
    ->select('a.appoint_id', 'p.pet_name', 'a.appoint_date', 'a.appoint_type', 'a.appoint_status', 'a.created_at')
    ->orderBy('a.created_at', 'desc')
    ->limit(10)
    ->get();

foreach ($recentAppointments as $apt) {
    echo "   - #{$apt->appoint_id}: {$apt->pet_name} - {$apt->appoint_type} on {$apt->appoint_date} ({$apt->appoint_status})\n";
}

echo "\n🎉 Testing completed!\n";