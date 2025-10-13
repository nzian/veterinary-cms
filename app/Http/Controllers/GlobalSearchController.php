<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pet;
use App\Models\Owner;
use App\Models\Appointment;
use App\Models\Product;
use App\Models\Service;
use App\Models\Branch;
use App\Models\Equipment;
use App\Models\Prescription;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class GlobalSearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->input('search');
        $results = collect();
        
        if (empty($query)) {
            $resultCounts = $this->getEmptyCounts();
            return view('global-search-results', compact('query', 'results', 'resultCounts'));
        }

        // Apply branch filter if user is in branch mode
        $branchId = session('branch_mode') === 'active' ? session('active_branch_id') : null;

        // Search Pets
        $pets = Pet::where(function($q) use ($query) {
                    $q->where('pet_name', 'like', "%{$query}%")
                      ->orWhere('pet_species', 'like', "%{$query}%")
                      ->orWhere('pet_breed', 'like', "%{$query}%")
                      ->orWhere('pet_registration', 'like', "%{$query}%");
                })
                ->with('owner')
                ->limit(10)
                ->get()
                ->map(function($pet) {
                    return [
                        'type' => 'Pet',
                        'icon' => 'fa-paw',
                        'color' => 'blue',
                        'title' => $pet->pet_name,
                        'subtitle' => $pet->pet_species . ' - ' . $pet->pet_breed,
                        'description' => 'Owner: ' . ($pet->owner->own_name ?? 'N/A') . ' | Registration: ' . ($pet->pet_registration ?? 'N/A'),
                        'route' => route('pet-management.index') . '?search=' . urlencode($pet->pet_name),
                        'id' => $pet->pet_id
                    ];
                });

        // Search Owners - HAS branch_id according to your fillable
        $owners = Owner::where(function($q) use ($query) {
                        $q->where('own_name', 'like', "%{$query}%")
                          ->orWhere('own_contactnum', 'like', "%{$query}%")
                          ->orWhere('own_location', 'like', "%{$query}%");
                    })
                    ->when($branchId && Schema::hasColumn('tbl_owner', 'branch_id'), function($q) use ($branchId) {
                        $q->where('branch_id', $branchId);
                    })
                    ->limit(10)
                    ->get()
                    ->map(function($owner) {
                        return [
                            'type' => 'Owner',
                            'icon' => 'fa-user',
                            'color' => 'green',
                            'title' => $owner->own_name,
                            'subtitle' => $owner->own_contactnum,
                            'description' => $owner->own_location ?? 'No location',
                            'route' => route('pet-management.index') . '?owner=' . urlencode($owner->own_name),
                            'id' => $owner->own_id
                        ];
                    });

        // Search Appointments - NO branch_id in your fillable
        $appointments = Appointment::where(function($q) use ($query) {
                            $q->where('appoint_description', 'like', "%{$query}%")
                              ->orWhere('appoint_status', 'like', "%{$query}%")
                              ->orWhere('appoint_type', 'like', "%{$query}%");
                        })
                        ->orWhereHas('pet', function($q) use ($query) {
                            $q->where('pet_name', 'like', "%{$query}%");
                        })
                        ->with('pet')
                        ->limit(10)
                        ->get()
                        ->map(function($appointment) {
                            return [
                                'type' => 'Appointment',
                                'icon' => 'fa-calendar-check',
                                'color' => 'purple',
                                'title' => 'Appointment #' . $appointment->appoint_id,
                                'subtitle' => ($appointment->appoint_status ?? 'Pending') . ' - ' . date('M d, Y', strtotime($appointment->appoint_date)),
                                'description' => ($appointment->pet->pet_name ?? 'Unknown Pet') . ' | ' . ($appointment->appoint_description ?? 'No description'),
                                'route' => route('medical.index') . '?search=' . $appointment->appoint_id,
                                'id' => $appointment->appoint_id
                            ];
                        });

        // Search Products - HAS branch_id according to your fillable
        $products = Product::where(function($q) use ($query) {
                        $q->where('prod_name', 'like', "%{$query}%")
                          ->orWhere('prod_description', 'like', "%{$query}%")
                          ->orWhere('prod_category', 'like', "%{$query}%");
                    })
                    ->when($branchId && Schema::hasColumn('tbl_product', 'branch_id'), function($q) use ($branchId) {
                        $q->where('branch_id', $branchId);
                    })
                    ->limit(10)
                    ->get()
                    ->map(function($product) {
                        return [
                            'type' => 'Product',
                            'icon' => 'fa-box',
                            'color' => 'orange',
                            'title' => $product->prod_name,
                            'subtitle' => ($product->prod_category ?? 'Uncategorized') . ' - ₱' . number_format($product->prod_price, 2),
                            'description' => 'Stock: ' . $product->prod_stocks . ' | ' . ($product->prod_description ?? 'No description'),
                            'route' => route('prodservequip.index') . '?search=' . urlencode($product->prod_name),
                            'id' => $product->prod_id
                        ];
                    });

        // Search Services - HAS branch_id according to your fillable
        $services = Service::where(function($q) use ($query) {
                        $q->where('serv_name', 'like', "%{$query}%")
                          ->orWhere('serv_description', 'like', "%{$query}%")
                          ->orWhere('serv_type', 'like', "%{$query}%");
                    })
                    ->when($branchId && Schema::hasColumn('tbl_service', 'branch_id'), function($q) use ($branchId) {
                        $q->where('branch_id', $branchId);
                    })
                    ->limit(10)
                    ->get()
                    ->map(function($service) {
                        return [
                            'type' => 'Service',
                            'icon' => 'fa-concierge-bell',
                            'color' => 'teal',
                            'title' => $service->serv_name,
                            'subtitle' => ($service->serv_type ?? 'General') . ' - ₱' . number_format($service->serv_price, 2),
                            'description' => $service->serv_description ?? 'No description',
                            'route' => route('prodservequip.index') . '?search=' . urlencode($service->serv_name),
                            'id' => $service->serv_id
                        ];
                    });

        // Search Branches (only for superadmin)
        $branches = collect();
        if (auth()->user()->user_role === 'superadmin') {
            $branches = Branch::where('branch_name', 'like', "%{$query}%")
                        ->orWhere('branch_address', 'like', "%{$query}%")
                        ->limit(10)
                        ->get()
                        ->map(function($branch) {
                            return [
                                'type' => 'Branch',
                                'icon' => 'fa-building',
                                'color' => 'indigo',
                                'title' => $branch->branch_name,
                                'subtitle' => 'Branch Location',
                                'description' => $branch->branch_address ?? 'No location specified',
                                'route' => route('branch-management.index') . '?search=' . urlencode($branch->branch_name),
                                'id' => $branch->branch_id
                            ];
                        });
        }

        // Search Equipment - likely HAS branch_id
        $equipment = Equipment::where(function($q) use ($query) {
                        $q->where('equipment_name', 'like', "%{$query}%")
                          ->orWhere('equipment_description', 'like', "%{$query}%")
                          ->orWhere('equipment_category', 'like', "%{$query}%");
                    })
                    ->when($branchId && Schema::hasColumn('tbl_equipment', 'branch_id'), function($q) use ($branchId) {
                        $q->where('branch_id', $branchId);
                    })
                    ->limit(10)
                    ->get()
                    ->map(function($equip) {
                        return [
                            'type' => 'Equipment',
                            'icon' => 'fa-tools',
                            'color' => 'gray',
                            'title' => $equip->equip_name,
                            'subtitle' => $equip->equip_category ?? 'Uncategorized',
                            'description' => $equip->equip_description ?? 'No description',
                            'route' => route('prodservequip.index') . '?search=' . urlencode($equip->equip_name),
                            'id' => $equip->equip_id
                        ];
                    });

        // Search Prescriptions - HAS branch_id according to your fillable
        $prescriptions = Prescription::where(function($q) use ($query) {
                            $q->where('medication', 'like', "%{$query}%")
                              ->orWhere('notes', 'like', "%{$query}%")
                              ->orWhere('differential_diagnosis', 'like', "%{$query}%");
                        })
                        ->when($branchId && Schema::hasColumn('tbl_prescription', 'branch_id'), function($q) use ($branchId) {
                            $q->where('branch_id', $branchId);
                        })
                        ->with('pet')
                        ->limit(10)
                        ->get()
                        ->map(function($prescription) {
                            return [
                                'type' => 'Prescription',
                                'icon' => 'fa-prescription',
                                'color' => 'pink',
                                'title' => 'Prescription #' . $prescription->prescription_id,
                                'subtitle' => 'Date: ' . date('M d, Y', strtotime($prescription->prescription_date)),
                                'description' => 'Pet: ' . ($prescription->pet->pet_name ?? 'Unknown') . ' | ' . ($prescription->medication ?? 'No medication'),
                                'route' => route('medical.index') . '?search=' . $prescription->prescription_id,
                                'id' => $prescription->prescription_id
                            ];
                        });

        // Search Referrals - NO branch_id in your fillable
        $referrals = Referral::where(function($q) use ($query) {
                        $q->where('ref_description', 'like', "%{$query}%")
                          ->orWhere('ref_by', 'like', "%{$query}%")
                          ->orWhere('ref_to', 'like', "%{$query}%")
                          ->orWhere('medical_history', 'like', "%{$query}%");
                    })
                    ->limit(10)
                    ->get()
                    ->map(function($referral) {
                        return [
                            'type' => 'Referral',
                            'icon' => 'fa-file-medical',
                            'color' => 'cyan',
                            'title' => 'Referral #' . $referral->ref_id,
                            'subtitle' => 'From: ' . ($referral->ref_by ?? 'Unknown') . ' → To: ' . ($referral->ref_to ?? 'Unknown'),
                            'description' => $referral->ref_description ?? 'No description',
                            'route' => route('medical.index') . '?search=' . $referral->ref_id,
                            'id' => $referral->ref_id
                        ];
                    });

        // Search Users - HAS branch_id according to your fillable (only for superadmin and receptionist)
        $users = collect();
        if (in_array(auth()->user()->user_role, ['superadmin', 'receptionist'])) {
            $users = User::where(function($q) use ($query) {
                        $q->where('user_name', 'like', "%{$query}%")
                          ->orWhere('user_email', 'like', "%{$query}%")
                          ->orWhere('user_contactNum', 'like', "%{$query}%")
                          ->orWhere('user_licenseNum', 'like', "%{$query}%")
                          ->orWhere('user_role', 'like', "%{$query}%");
                    })
                    ->when($branchId && Schema::hasColumn('tbl_user', 'branch_id'), function($q) use ($branchId) {
                        $q->where('branch_id', $branchId);
                    })
                    ->limit(10)
                    ->get()
                    ->map(function($user) {
                        return [
                            'type' => 'User',
                            'icon' => 'fa-user-md',
                            'color' => 'yellow',
                            'title' => $user->user_name,
                            'subtitle' => ucfirst($user->user_role) . ' | ' . $user->user_email,
                            'description' => 'Contact: ' . ($user->user_contactNum ?? 'N/A') . ' | License: ' . ($user->user_licenseNum ?? 'N/A'),
                            'route' => route('branch-management.index') . '?search=' . urlencode($user->user_name),
                            'id' => $user->user_id
                        ];
                    });
        }

        // Merge all results
        $results = $pets->concat($owners)
                       ->concat($appointments)
                       ->concat($products)
                       ->concat($services)
                       ->concat($branches)
                       ->concat($equipment)
                       ->concat($prescriptions)
                       ->concat($referrals)
                       ->concat($users);

        // Count by type
        $resultCounts = [
            'pets' => $pets->count(),
            'owners' => $owners->count(),
            'appointments' => $appointments->count(),
            'products' => $products->count(),
            'services' => $services->count(),
            'branches' => $branches->count(),
            'equipment' => $equipment->count(),
            'prescriptions' => $prescriptions->count(),
            'referrals' => $referrals->count(),
            'users' => $users->count(),
            'total' => $results->count()
        ];

        return view('global-search.results', compact('query', 'results', 'resultCounts'));
    }

    private function getEmptyCounts()
    {
        return [
            'pets' => 0,
            'owners' => 0,
            'appointments' => 0,
            'products' => 0,
            'services' => 0,
            'branches' => 0,
            'equipment' => 0,
            'prescriptions' => 0,
            'referrals' => 0,
            'users' => 0,
            'total' => 0
        ];
    }
}