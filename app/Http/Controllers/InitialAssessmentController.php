<?php

namespace App\Http\Controllers;

use App\Models\InitialAssessment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InitialAssessmentController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'visit_id' => 'required|exists:tbl_visit_record,visit_id',
                'pet_id' => 'required|exists:tbl_pet,pet_id',
                'is_sick' => 'required|string',
                'been_treated' => 'nullable|string',
                'table_food' => 'nullable|string',
                'feeding_frequency' => 'nullable|string',
                'heartworm_preventative' => 'nullable|string',
                'injury_accident' => 'nullable|string',
                'allergies' => 'nullable|string',
                'surgery_past_30' => 'nullable|string',
                'current_meds' => 'nullable|string',
                'appetite_normal' => 'nullable|string',
                'diarrhoea' => 'nullable|string',
                'vomiting' => 'nullable|string',
                'drinking_unusual' => 'nullable|string',
                'weakness' => 'nullable|string',
                'gagging' => 'nullable|string',
                'coughing' => 'nullable|string',
                'sneezing' => 'nullable|string',
                'scratching' => 'nullable|string',
                'shaking_head' => 'nullable|string',
                'urinating_unusual' => 'nullable|string',
                'limping' => 'nullable|string',
                'scooting' => 'nullable|string',
                'seizures' => 'nullable|string',
                'bad_breath' => 'nullable|string',
                'discharge' => 'nullable|string',
                'ate_this_morning' => 'nullable|string',
            ]);

            // Check for existing assessment
            $existingAssessment = InitialAssessment::where('visit_id', $request->visit_id)->first();
            
            if ($existingAssessment) {
                // Update existing
                $existingAssessment->update($validated);
                $assessment = $existingAssessment;
            } else {
                // Create new
                $assessment = InitialAssessment::create($validated);
            }

            return response()->json([
                'success' => true,
                'message' => 'Initial assessment saved successfully!',
                'data' => $assessment
            ]);

        } catch (\Exception $e) {
            Log::error('Initial Assessment Save Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to save initial assessment: ' . $e->getMessage()
            ], 500);
        }
    }
}