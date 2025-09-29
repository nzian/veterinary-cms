<?php
// test_sms.php - Run this directly to test PhilSMS

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Log;

echo "Testing PhilSMS API...\n";

// Your PhilSMS credentials
$apiKey = '1460|y6x2ozwUdq2tYYCq1gRr2ltFe42I7sNRYPuDT7wB';
$senderId = 'YourClinic';
$testNumber = '639171234567'; // Replace with a real Philippine number

// Test data
$postData = [
    'recipient' => $testNumber,
    'sender_id' => $senderId,
    'type' => 'plain',
    'message' => 'Direct test from PHP script. PhilSMS working!'
];

$headers = [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json',
    'Accept: application/json'
];

echo "Sending to: {$testNumber}\n";
echo "Using API Key: ***" . substr($apiKey, -8) . "\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://app.philsms.com/api/v3/sms/send',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($postData),
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_VERBOSE => true
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "\n=== RESULTS ===\n";
echo "HTTP Code: {$httpCode}\n";
echo "cURL Error: " . ($curlError ?: 'None') . "\n";
echo "Response: {$response}\n";

// Parse response
$responseData = json_decode($response, true);
if ($responseData) {
    echo "\nParsed Response:\n";
    print_r($responseData);
} else {
    echo "\nFailed to parse JSON response\n";
    echo "JSON Error: " . json_last_error_msg() . "\n";
}

echo "\n=== END TEST ===\n";