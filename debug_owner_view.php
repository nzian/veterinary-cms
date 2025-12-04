<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\PetManagementController;
use Illuminate\Support\Facades\DB;

// Find a pet with service records and get its owner
$record = DB::table('tbl_grooming_record')->first() ?: DB::table('tbl_boarding_record')->first() ?: DB::table('tbl_checkup_record')->first();
if (!$record) {
    echo "No service records found to test.\n";
    exit(0);
}
$petId = $record->pet_id;
$pet = DB::table('tbl_pet')->where('pet_id', $petId)->first();
if (!$pet) {
    echo "Pet not found for pet_id: $petId\n";
    exit(0);
}
$owner = DB::table('tbl_own')->where('own_id', $pet->own_id)->first();
if (!$owner) {
    echo "Owner not found for owner id: " . ($pet->own_id ?? 'NULL') . "\n";
    exit(0);
}

echo "Testing owner: " . ($owner->own_name ?? 'N/A') . " (ID: " . $owner->own_id . ") for pet: " . ($pet->pet_name ?? $petId) . " (ID: $petId)\n\n";

$controller = new PetManagementController();
$response = $controller->getOwnerDetails($owner->own_id);
$data = json_decode($response->getContent(), true);

if (isset($data['error'])) {
    echo "ERROR: " . $data['error'] . "\n";
    exit(1);
}

echo "Visits returned: " . count($data['visits']) . "\n\n";
foreach ($data['visits'] as $v) {
    echo "Visit ID: " . ($v['id'] ?? 'N/A') . " | Date: " . ($v['date'] ?? 'N/A') . "\n";
    echo "  type field: " . ($v['type'] ?? 'NULL') . "\n";
    echo "  service_type field: " . ($v['service_type'] ?? 'NULL') . "\n";
    echo "  raw array keys: " . implode(', ', array_keys($v)) . "\n\n";
}

echo "Done.\n";