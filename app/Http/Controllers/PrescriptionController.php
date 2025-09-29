<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Prescription;
use App\Models\Pet;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PrescriptionController extends Controller
{
    // Show all prescriptions
    public function index()
    {
        $prescriptions = Prescription::with(['pet', 'branch'])->get();
        $pets = Pet::all();
        return view('prescription', compact('prescriptions', 'pets'));
    }

    // Store new prescription
    public function store(Request $request)
    {
        try {
            $request->validate([
                'pet_id' => 'required|exists:tbl_pet,pet_id',
                'prescription_date' => 'required|date',
                'medications_json' => 'required|string',
                'notes' => 'nullable|string'
            ]);

            // Decode medications
            $medications = json_decode($request->medications_json, true);
            
            if (empty($medications)) {
                return redirect()->back()->with('error', 'At least one medication is required');
            }

            // Create prescription
            $prescription = Prescription::create([
                'pet_id' => $request->pet_id,
                'prescription_date' => $request->prescription_date,
                'medication' => json_encode($medications),
                'notes' => $request->notes,
                'branch_id' => auth()->user()->branch_id ?? 1
            ]);

            return redirect()->back()->with('success', 'Prescription created successfully!');
            
        } catch (\Exception $e) {
            Log::error('Prescription creation error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error creating prescription. Please try again.');
        }
    }

    public function edit($id)
    {
        try {
            $prescription = Prescription::with('pet')->findOrFail($id);
            
            // Decode medications from JSON
            $medications = json_decode($prescription->medication, true) ?? [];
            
            return response()->json([
                'prescription_id' => $prescription->prescription_id,
                'pet_id' => $prescription->pet_id,
                'prescription_date' => $prescription->prescription_date,
                'medications' => $medications,
                'notes' => $prescription->notes
            ]);
            
        } catch (\Exception $e) {
            Log::error('Prescription edit error: ' . $e->getMessage());
            return response()->json(['error' => 'Error loading prescription data'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'pet_id' => 'required|exists:tbl_pet,pet_id',
                'prescription_date' => 'required|date',
                'medications_json' => 'required|string',
                'notes' => 'nullable|string'
            ]);

            $prescription = Prescription::findOrFail($id);
            
            // Decode medications
            $medications = json_decode($request->medications_json, true);
            
            if (empty($medications)) {
                return redirect()->back()->with('error', 'At least one medication is required');
            }

            $prescription->update([
                'pet_id' => $request->pet_id,
                'prescription_date' => $request->prescription_date,
                'medication' => json_encode($medications),
                'notes' => $request->notes
            ]);

            return redirect()->back()->with('success', 'Prescription updated successfully!');
            
        } catch (\Exception $e) {
            Log::error('Prescription update error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error updating prescription. Please try again.');
        }
    }

    public function searchProducts(Request $request)
    {
        $query = $request->get('q');
        
        if (!$query || strlen($query) < 2) {
            return response()->json([]);
        }
        
        try {
            $products = DB::table('tbl_prod')
                ->where(function($q) use ($query) {
                    $q->where('prod_name', 'LIKE', "%{$query}%")
                      ->orWhere('prod_description', 'LIKE', "%{$query}%")
                      ->orWhere('prod_category', 'LIKE', "%{$query}%");
                })
                ->select(
                    'prod_id as id',
                    'prod_name as name', 
                    'prod_price as price',
                    'prod_category as type',
                    'prod_description as description'
                )
                ->limit(15)
                ->get();
            
            return response()->json($products);
            
        } catch (\Exception $e) {
            Log::error('Product search error: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    // Delete prescription
    public function destroy($id)
    {
        try {
            $prescription = Prescription::findOrFail($id);
            $prescription->delete();

            return redirect()->back()->with('success', 'Prescription deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Prescription deletion error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error deleting prescription. Please try again.');
        }
    }

    // Generate printable prescription (HTML version)
    public function printPrescription($id)
    {
        $prescription = Prescription::with(['pet', 'branch'])->findOrFail($id);
        
        return view('prescription-print', compact('prescription'));
    }
}