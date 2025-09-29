<?php

namespace App\Http\Controllers;

use App\Models\Billing;
use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillingController extends Controller
{
    public function index()
    {
        $billings = Billing::with([
            'appointment.pet.owner', 
            'appointment.services',
            'branch'
        ])
        ->orderBy('bill_date', 'desc')
        ->paginate(10);

        return view('billing', ['billings' => $billings]);
    }

    public function show($id)
    {
        $billing = Billing::with([
            'appointment.pet.owner', 
            'appointment.services',
            'branch'
        ])->findOrFail($id);
        
        // Calculate services total
        $servicesTotal = 0;
        if ($billing->appointment && $billing->appointment->services) {
            $servicesTotal = $billing->appointment->services->sum('serv_price');
        }
        
        // Calculate prescription products total
        $prescriptionTotal = 0;
        $prescriptionItems = [];
        
        if ($billing->appointment && $billing->appointment->pet) {
            // Get prescriptions for this pet around the appointment date
            $prescriptions = Prescription::where('pet_id', $billing->appointment->pet->pet_id)
                ->whereDate('prescription_date', '<=', $billing->bill_date)
                ->whereDate('prescription_date', '>=', date('Y-m-d', strtotime($billing->bill_date . ' -7 days')))
                ->get();
            
            foreach ($prescriptions as $prescription) {
                $medications = json_decode($prescription->medication, true) ?? [];
                foreach ($medications as $medication) {
                    if (isset($medication['product_id']) && $medication['product_id']) {
                        // Get product details from database
                        $product = DB::table('tbl_prod')
                            ->where('prod_id', $medication['product_id'])
                            ->first();
                        
                        if ($product) {
                            $prescriptionItems[] = [
                                'name' => $product->prod_name,
                                'price' => $product->prod_price,
                                'instructions' => $medication['instructions'] ?? '',
                                'type' => 'prescription'
                            ];
                            $prescriptionTotal += $product->prod_price;
                        }
                    } else {
                        // Manual entry - we'll need to estimate or set a default price
                        $prescriptionItems[] = [
                            'name' => $medication['product_name'] ?? 'Unknown medication',
                            'price' => 0, // Manual entries don't have prices
                            'instructions' => $medication['instructions'] ?? '',
                            'type' => 'prescription_manual'
                        ];
                    }
                }
            }
        }
        
        $grandTotal = $servicesTotal + $prescriptionTotal;
        
        return response()->json([
            'billing' => $billing,
            'owner' => $billing->appointment?->pet?->owner?->own_name ?? 'N/A',
            'services' => $billing->appointment?->services ?? collect([]),
            'prescription_items' => $prescriptionItems,
            'services_total' => $servicesTotal,
            'prescription_total' => $prescriptionTotal,
            'grand_total' => $grandTotal,
        ]);
    }

    public function destroy($id)
    {
        $billing = Billing::findOrFail($id);
        $billing->delete();

        return back()->with('success', 'Billing record deleted.');
    }

     public function markAsPaid($billId)
    {
        $billing = Billing::findOrFail($billId);
        $billing->bill_status = 'paid';
        $billing->save();

        return response()->json([
            'success' => true,
            'message' => 'Billing marked as paid',
            'billing_id' => $billId,
            'status' => 'paid'
        ]);
    }

    /**
     * Mark billing as pending - call this if payment fails or is cancelled
     */
    public function markAsPending($billId)
    {
        $billing = Billing::findOrFail($billId);
        $billing->bill_status = 'pending';
        $billing->save();

        return response()->json([
            'success' => true,
            'message' => 'Billing marked as pending',
            'billing_id' => $billId,
            'status' => 'pending'
        ]);
    }

    /**
     * Get billing status
     */
    public function getStatus($id)
    {
        $billing = Billing::findOrFail($id);
        
        return response()->json([
            'billing_id' => $id,
            'status' => $billing->bill_status 
        ]);
    }

    /**
     * Calculate total cost including services and prescription items
     */
    public function calculateTotal($billing)
    {
        $servicesTotal = 0;
        $prescriptionTotal = 0;
        
        // Calculate services total
        if ($billing->appointment && $billing->appointment->services) {
            $servicesTotal = $billing->appointment->services->sum('serv_price');
        }
        
        // Calculate prescription products total
        if ($billing->appointment && $billing->appointment->pet) {
            $prescriptions = Prescription::where('pet_id', $billing->appointment->pet->pet_id)
                ->whereDate('prescription_date', '<=', $billing->bill_date)
                ->whereDate('prescription_date', '>=', date('Y-m-d', strtotime($billing->bill_date . ' -7 days')))
                ->get();
            
            foreach ($prescriptions as $prescription) {
                $medications = json_decode($prescription->medication, true) ?? [];
                foreach ($medications as $medication) {
                    if (isset($medication['product_id']) && $medication['product_id']) {
                        $product = DB::table('tbl_prod')
                            ->where('prod_id', $medication['product_id'])
                            ->first();
                        
                        if ($product) {
                            $prescriptionTotal += $product->prod_price;
                        }
                    }
                }
            }
        }
        
        return [
            'services_total' => $servicesTotal,
            'prescription_total' => $prescriptionTotal,
            'grand_total' => $servicesTotal + $prescriptionTotal
        ];
    }
}