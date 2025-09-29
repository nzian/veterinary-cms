<?php

namespace App\Http\Controllers;

use App\Models\SmsSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class SMSSettingsController extends Controller
{
    public function index()
    {
        $settings = SmsSettings::first();
        
        if (!$settings) {
            $settings = SmsSettings::create([
                'sms_provider' => 'philsms',
                'sms_api_key' => '',
                'sms_sender_id' => 'YourClinic',
                'sms_api_url' => 'https://app.philsms.com/api/v3/sms/send',
                'sms_twilio_token' => '',
                'sms_is_active' => true
            ]);
        }
        
        return view('sms-settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $rules = [
            'provider' => 'required|string',
            'sender_id' => 'required|string|max:20',
            'api_url' => 'required|url'
        ];

        // Different validation based on provider
        if ($request->provider === 'twilio') {
            $rules['twilio_sid'] = 'required|string';
            $rules['twilio_token'] = 'required|string';
        } else {
            $rules['api_key'] = 'required|string';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = [
            'sms_provider' => $request->provider,
            'sms_sender_id' => $request->sender_id,
            'sms_api_url' => $request->api_url,
            'sms_is_active' => true
        ];

        // Handle different providers
        if ($request->provider === 'twilio') {
            $data['sms_api_key'] = $request->twilio_sid;
            $data['sms_twilio_token'] = $request->twilio_token;
        } else {
            $data['sms_api_key'] = $request->api_key;
            $data['sms_twilio_token'] = null;
        }

        SmsSettings::updateOrCreate(
            ['sms_id' => 1],
            $data
        );

        Log::info('SMS settings updated', [
            'provider' => $request->provider,
            'has_api_key' => !empty($data['sms_api_key']),
            'sender_id' => $request->sender_id
        ]);

        return redirect()->back()->with('success', 'SMS settings updated successfully!');
    }

    public function testSMS(Request $request)
    {
        try {
            $request->validate([
                'test_number' => 'required|string'
            ]);

            $settings = SmsSettings::getActiveConfig();
            
            if (!$settings) {
                Log::error('No SMS settings found in database');
                return response()->json([
                    'success' => false, 
                    'message' => 'No SMS configuration found. Please configure SMS settings first.'
                ]);
            }

            // Check if required credentials are set
            if ($settings->sms_provider === 'twilio') {
                if (empty($settings->sms_api_key) || empty($settings->sms_twilio_token)) {
                    return response()->json([
                        'success' => false, 
                        'message' => 'Twilio credentials incomplete. Please add both SID and Auth Token.'
                    ]);
                }
            } else {
                if (empty($settings->sms_api_key)) {
                    return response()->json([
                        'success' => false, 
                        'message' => 'API Key not configured. Please add your SMS API key first.'
                    ]);
                }
            }

            Log::info("Testing SMS with settings", [
                'provider' => $settings->sms_provider,
                'api_url' => $settings->sms_api_url,
                'sender_id' => $settings->sms_sender_id,
                'has_api_key' => !empty($settings->sms_api_key),
                'has_token' => !empty($settings->sms_twilio_token),
                'test_number' => $request->test_number
            ]);

            // Use the dynamic SMS service
            $smsService = app(\App\Services\DynamicSMSService::class);
            $result = $smsService->sendTestSMS($request->test_number);

            Log::info("SMS Test Result", $result);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error("SMS Test Exception", [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Test failed: ' . $e->getMessage()
            ]);
        }
    }
}