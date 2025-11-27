<?php

namespace App\Services;

use App\Models\Billing;
use App\Models\Visit;
use App\Models\Owner;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;

class GroupedBillingService
{
    /**
     * Generate grouped billing for multiple pets with the same owner on the same day
     * 
     * @param array $visitIds Array of visit IDs to include in the billing
     * @return Billing The parent billing record
     */
    public function generateGroupedBilling(array $visitIds)
    {
        DB::beginTransaction();
        
        try {
            // Get all visits
            $visits = Visit::with(['pet.owner', 'services', 'user'])->whereIn('visit_id', $visitIds)->get();
            
            if ($visits->isEmpty()) {
                throw new \Exception('No visits found for billing generation');
            }
            
            // Validate all visits belong to the same owner
            $ownerId = $visits->first()->pet->owner->own_id;
            foreach ($visits as $visit) {
                if ($visit->pet->owner->own_id !== $ownerId) {
                    throw new \Exception('All visits must belong to the same owner for grouped billing');
                }
                
                if ($visit->workflow_status !== 'Completed') {
                    throw new \Exception("Visit {$visit->visit_id} is not completed yet (workflow_status: {$visit->workflow_status})");
                }
                
                if ($visit->billing) {
                    throw new \Exception("Visit {$visit->visit_id} already has a billing record");
                }
            }
            
            // Generate unique billing group ID
            $billingGroupId = 'BG-' . $ownerId . '-' . Carbon::now()->format('YmdHis');
            $branchId = $visits->first()->user->branch_id ?? session('active_branch_id');
            
            $billings = [];
            $totalGroupAmount = 0;
            
            // Create individual billing for each visit
            foreach ($visits as $index => $visit) {
                $visitTotal = $this->calculateVisitTotal($visit);
                $totalGroupAmount += $visitTotal;
                
                $billing = Billing::create([
                    'bill_date' => Carbon::now()->toDateString(),
                    'visit_id' => $visit->visit_id,
                    'bill_status' => 'unpaid',
                    'branch_id' => $branchId,
                    'billing_group_id' => $billingGroupId,
                    'owner_id' => $ownerId,
                    'total_amount' => $visitTotal,
                    'paid_amount' => 0,
                    'is_group_parent' => ($index === 0), // First billing is the parent
                ]);
                
                $billings[] = $billing;
            }
            
            DB::commit();
            
            Log::info("Grouped billing created: {$billingGroupId} for owner {$ownerId} with " . count($billings) . " visits");
            
            return $billings[0]; // Return parent billing
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Grouped billing generation failed: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Generate individual billing for a single visit
     */
    public function generateSingleBilling($visitId, $force = false)
    {
        DB::beginTransaction();
        
        try {
            $visit = Visit::with(['pet.owner', 'services', 'user'])->findOrFail($visitId);
            
            if (!$force && $visit->workflow_status !== 'Completed') {
                throw new \Exception("Visit is not completed yet (workflow_status: {$visit->workflow_status})");
            }
            
            if ($visit->billing) {
                throw new \Exception('Visit already has a billing record');
            }
            
            $visitTotal = $this->calculateVisitTotal($visit);
            $branchId = $visit->user->branch_id ?? session('active_branch_id');
            
            $billing = Billing::create([
                'bill_date' => Carbon::now()->toDateString(),
                'visit_id' => $visit->visit_id,
                'bill_status' => 'unpaid',
                'branch_id' => $branchId,
                'owner_id' => $visit->pet->owner->own_id,
                'total_amount' => $visitTotal,
                'paid_amount' => 0,
                'is_group_parent' => true, // Single billing is its own parent
            ]);

            // If this visit includes a boarding service, create a partial-payment placeholder
            // so front-end can offer a half-payment option. This placeholder is created with
            // status 'pending' and payment_type 'partial' and SHOULD NOT be counted as paid
            // until an actual payment transaction is recorded (status => 'paid').
            try {
                $hasBoarding = $visit->services->contains(function ($s) {
                    $servType = strtolower($s->serv_type ?? '');
                    $servName = strtolower($s->serv_name ?? '');
                    return $servType === 'boarding' || strpos($servName, 'boarding') === 0;
                });

                if ($hasBoarding && $billing && $billing->total_amount > 0) {
                    $partialAmount = round($billing->total_amount / 2, 2);
                    Payment::create([
                        'bill_id' => $billing->bill_id,
                        'pay_total' => $partialAmount,
                        'pay_cashAmount' => 0,
                        'pay_change' => 0,
                        'payment_type' => 'partial',
                        'payment_date' => now(),
                        'transaction_id' => 'PARTIAL-' . $billing->bill_id . '-' . time(),
                        'status' => 'pending'
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to create partial-payment placeholder: ' . $e->getMessage());
            }
            
            DB::commit();
            
            Log::info("Single billing created: {$billing->bill_id} for visit {$visitId}");
            
            return $billing;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Single billing generation failed: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Auto-generate billings for completed visits of the same owner today
     */
    public function autoGenerateGroupedBillingForOwner($ownerId, $date = null)
    {
        $date = $date ?? Carbon::today()->toDateString();
        
        // Find all completed visits for this owner on the given date without billing
        $visits = Visit::with(['pet.owner', 'services'])
            ->whereHas('pet', function($query) use ($ownerId) {
                $query->where('own_id', $ownerId);
            })
            ->whereDate('visit_date', $date)
            ->where('workflow_status', 'Completed')
            ->whereDoesntHave('billing')
            ->get();
        
        if ($visits->isEmpty()) {
            return null;
        }
        
        if ($visits->count() === 1) {
            // Single visit - create individual billing
            return $this->generateSingleBilling($visits->first()->visit_id);
        }
        
        // Multiple visits - create grouped billing
        return $this->generateGroupedBilling($visits->pluck('visit_id')->toArray());
    }
    
    /**
     * Calculate total amount for a visit (services + products/medications)
     */
    protected function calculateVisitTotal(Visit $visit)
    {
        // Calculate services total - use pivot price if > 0, otherwise use service base price
        $servicesTotal = $visit->services->sum(function ($service) {
            $quantity = $service->pivot->quantity ?? 1;
            
            // If pivot has a total_price > 0, use it
            if (isset($service->pivot->total_price) && $service->pivot->total_price > 0) {
                return $service->pivot->total_price;
            }
            
            // If pivot has unit_price > 0, calculate from it
            if (isset($service->pivot->unit_price) && $service->pivot->unit_price > 0) {
                return $service->pivot->unit_price * $quantity;
            }
            
            // Otherwise, use service base price
            $basePrice = $service->serv_price ?? 0;
            return $basePrice * $quantity;
        });
        
        // Calculate products/medications total from tbl_service_products (billable consumables)
        $productsTotal = DB::table('tbl_visit_service')
            ->join('tbl_service_products', 'tbl_visit_service.id', '=', 'tbl_service_products.serv_id')
            ->join('tbl_prod', 'tbl_service_products.prod_id', '=', 'tbl_prod.prod_id')
            ->where('tbl_visit_service.visit_id', $visit->visit_id)
            ->where('tbl_service_products.is_billable', true)
            ->sum(DB::raw('tbl_service_products.quantity_used * tbl_prod.prod_price'));
        
        // Calculate prescription medications total
        // Get prescriptions for this visit's pet on the same date
        $prescriptionTotal = 0;
        $prescriptions = DB::table('tbl_prescription')
            ->where('pet_id', $visit->pet_id)
            ->whereDate('prescription_date', $visit->visit_date)
            ->get();
        
        foreach ($prescriptions as $prescription) {
            if (!empty($prescription->medication)) {
                $medications = json_decode($prescription->medication, true);
                if (is_array($medications)) {
                    foreach ($medications as $med) {
                        if (isset($med['product_id'])) {
                            $product = DB::table('tbl_prod')
                                ->where('prod_id', $med['product_id'])
                                ->first();
                            if ($product) {
                                $quantity = $med['quantity'] ?? 1;
                                $prescriptionTotal += $product->prod_price * $quantity;
                            }
                        }
                    }
                }
            }
        }
        
        return $servicesTotal + ($productsTotal ?? 0) + $prescriptionTotal;
    }
    
    /**
     * Get all pending billings grouped by owner for today
     */
    public function getTodayGroupedBillings($branchId = null)
    {
        $query = Billing::with(['visit.pet', 'owner', 'groupedBillings'])
            ->where('is_group_parent', true)
            ->whereDate('bill_date', Carbon::today())
            ->where('bill_status', 'pending');
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        return $query->orderBy('bill_date', 'desc')->get();
    }
    
    /**
     * Get billing group summary
     */
    public function getGroupSummary($billingGroupId)
    {
        $billings = Billing::with(['visit.pet', 'owner'])
            ->where('billing_group_id', $billingGroupId)
            ->get();
        
        return [
            'group_id' => $billingGroupId,
            'owner' => $billings->first()->owner,
            'total_amount' => $billings->sum('total_amount'),
            'paid_amount' => $billings->sum('paid_amount'),
            'balance' => $billings->sum('total_amount') - $billings->sum('paid_amount'),
            'status' => $billings->first()->bill_status,
            'billings' => $billings,
            'pet_count' => $billings->count(),
        ];
    }
}
