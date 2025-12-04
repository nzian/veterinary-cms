@extends('AdminBoard')
@php
    $userRole = strtolower(auth()->user()->user_role ?? '');
    
    // Define permissions for each role
    $permissions = [
        'superadmin' => [
            'view_billing' => true, // Set to true to make the View button visible
            'print_billing' => true,
            'print_billing_receipt' => true, // Added for clarity for the Receipt link
            'delete_billing' => true,
            'view_pos_sales' => true,
            'print_pos_sales' => true,
        ],
        'veterinarian' => [
            'view_billing' => true, // Set to true to make the View button visible
            'print_billing' => true,
            'print_billing_receipt' => true,
            'delete_billing' => false,
            'view_pos_sales' => true,
            'print_pos_sales' => true,
        ],
        'receptionist' => [
            'view_billing' => true, // Set to true to make the View button visible
            'print_billing' => true,
            'print_billing_receipt' => true,
            'delete_billing' => false,
            'view_pos_sales' => true,
            'print_pos_sales' => true,
        ],
    ];
    
    // Get permissions for current user
    $can = $permissions[$userRole] ?? [];
    
    // Helper function to check permission
    function hasPermission($permission, $can) {
        return $can[$permission] ?? false;
    }
@endphp
@section('content')
<div class="min-h-screen">
    <div class="w-full px-2 sm:px-4 md:px-6 lg:px-8 mx-auto bg-white p-3 sm:p-4 md:p-6 rounded-lg shadow">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Tab Navigation --}}
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8">
                <button id="billingTab" onclick="switchTab('billing')" 
                    class="tab-button active py-2 px-1 border-b-2 border-[#0f7ea0] font-medium text-sm text-[#0f7ea0]">
                    <h2 class="font-bold text-xl">Billing Management</h2>
                </button>
                <button id="ordersTab" onclick="switchTab('orders')" 
                    class="tab-button py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    <h2 class="font-bold text-xl">POS Sales</h2>
                </button>
            </nav>
        </div>

        {{-- Success Message --}}
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-4 text-sm">
                {{ session('success') }}
            </div>
        @endif

        {{-- Billing Tab Content --}}
        <div id="billingContent" class="tab-content">
            {{-- Description --}}
            <div class="bg-blue-50 border border-blue-200 rounded-md px-4 py-2 mb-4">
                <p class="text-sm text-gray-700">
                    <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                    <strong>Visit Service Bills:</strong> Bills generated from completed visit services (grooming, boarding, vaccinations, etc.)
                </p>
            </div>
            
            <div class="flex flex-nowrap items-center justify-between gap-3 mt-4 text-sm font-semibold text-black w-full overflow-x-auto pb-2">
                <form method="GET" action="{{ request()->url() }}" id="billingFiltersForm" class="flex-shrink-0 flex items-center space-x-2">
                    <input type="hidden" name="tab" value="billing">
                    <input type="hidden" name="view_type" id="viewTypeInput" value="{{ request('view_type', 'grouped') }}">
                    <label for="billingPerPage" class="whitespace-nowrap text-sm text-black">Show</label>
                    <select name="perPage" id="billingPerPage" onchange="this.form.submit()" class="border border-gray-400 rounded px-2 py-1.5 text-sm">
                        @foreach ([10, 20, 50, 100, 'all'] as $limit)
                            <option value="{{ $limit }}" {{ request('perPage') == $limit ? 'selected' : '' }}>
                                {{ $limit === 'all' ? 'All' : $limit }}
                            </option>
                        @endforeach
                    </select>
                    <span class="whitespace-nowrap">entries</span>
                    <label class="whitespace-nowrap text-sm text-black ml-2">View</label>
                    <select name="view_type" id="viewTypeSelect" onchange="this.form.submit()" class="border border-gray-400 rounded px-2 py-1.5 text-sm">
                        <option value="grouped" {{ request('view_type', 'grouped') == 'grouped' ? 'selected' : '' }}>By Owner</option>
                        <option value="pet-wise" {{ request('view_type') == 'pet-wise' ? 'selected' : '' }}>By Pet</option>
                    </select>
                    <label class="whitespace-nowrap text-sm text-black ml-2">Filter</label>
                    <select id="billingStatusFilter" onchange="filterBillingByStatus()" class="border border-gray-400 rounded px-2 py-1.5 text-sm">
                        <option value="">All Status</option>
                        <option value="unpaid">Unpaid</option>
                        <option value="paid 50%">Paid 50%</option>
                        <option value="paid">Paid</option>
                    </select>
                </form>
                <div class="flex items-center gap-2 flex-wrap">
                    <div class="relative">
                        <input type="search" id="billingSearch" placeholder="Search billing..." class="border border-gray-300 rounded px-3 py-1.5 text-sm pl-8">
                        <i class="fas fa-search absolute left-2 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                </div>
                <div class="flex gap-2">
                    <form action="{{ route('sales.auto-generate') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-3 py-1.5 rounded whitespace-nowrap">
                            <i class="fas fa-sync-alt mr-1"></i>Auto-Generate Billings
                        </button>
                    </form>
                </div>
            </div>

            {{-- Billing Table --}}
            <div class="w-full overflow-x-auto">
                <table id="billingTable" class="min-w-full table-auto text-sm border text-center">
                    <thead>
                        <tr class="bg-gray-100 text-centered">
                            @if(request('view_type') == 'pet-wise')
                                <th class="px-2 py-2 border whitespace-nowrap">Owner</th>
                                <th class="px-2 py-2 border whitespace-nowrap">Pet Name</th>
                                <th class="px-2 py-2 border whitespace-nowrap">Services</th>
                                <th class="px-4 py-2 border">Total Amount</th>
                                <th class="px-4 py-2 border">Status</th>
                                <th class="px-4 py-2 border">Date</th>
                                <th class="px-4 py-2 border text-center">Actions</th>
                            @else
                                <th class="px-2 py-2 border whitespace-nowrap w-8"></th>
                                <th class="px-2 py-2 border whitespace-nowrap">Owner</th>
                                <th class="px-2 py-2 border whitespace-nowrap">Pets</th>
                                <th class="px-4 py-2 border">Total Amount</th>
                                <th class="px-4 py-2 border">Status</th>
                                <th class="px-4 py-2 border">Date</th>
                                <th class="px-4 py-2 border text-center">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @if(request('view_type') == 'pet-wise')
                            {{-- Pet-wise view: Show individual pet bills directly --}}
                            @forelse($billings as $billing)
                                @php
                                    $petName = $billing->pet?->pet_name ?? 'N/A';
                                    $ownerName = $billing->owner?->own_name ?? 'N/A';
                                    $services = [];
                                    if($billing->visit && $billing->visit->services) {
                                        foreach($billing->visit->services as $service) {
                                            $services[] = $service->serv_name ?? '';
                                        }
                                    }
                                    $servicesText = !empty($services) ? implode(', ', $services) : 'N/A';
                                    $totalAmount = (float) $billing->total_amount;
                                    $paidAmount = (float) $billing->paid_amount;
                                    $balance = $totalAmount - $paidAmount;
                                    $status = $billing->bill_status ?? 'unpaid';
                                @endphp
                                <tr class="hover:bg-gray-50 bg-white border-b">
                                    <td class="px-2 py-2 border">{{ $ownerName }}</td>
                                    <td class="px-2 py-2 border">{{ $petName }}</td>
                                    <td class="px-2 py-2 border text-left">{{ Str::limit($servicesText, 50) }}</td>
                                    <td class="px-4 py-2 border">₱ {{ number_format($totalAmount, 2) }}</td>
                                    <td class="px-4 py-2 border">
                                        <span class="px-2 py-1 text-xs rounded-full
                                            @if(strtolower($status) === 'paid') bg-green-100 text-green-800
                                            @elseif(str_contains(strtolower($status), '50%')) bg-yellow-100 text-yellow-800
                                            @else bg-red-100 text-red-800
                                            @endif">
                                            {{ ucfirst($status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 border">{{ \Carbon\Carbon::parse($billing->bill_date)->format('M d, Y') }}</td>
                                    <td class="px-4 py-2 border text-center">
                                        <div class="flex justify-center gap-2">
                                            <button onclick="viewBill({{ $billing->bill_id }})" class="text-blue-600 hover:underline text-xs">View</button>
                                            @if($balance > 0)
                                                <button onclick="payBill({{ $billing->bill_id }}, {{ max(0, $balance) }})" 
                                                    class="text-green-600 hover:underline text-xs">Pay</button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">No billing records found</td></tr>
                            @endforelse
                        @else
                            {{-- Grouped view: Original expandable owner rows --}}
                            @forelse($billings as $index => $groupedBilling)
                            @php
                                
                                // Support both array and object access
                                $owner = is_array($groupedBilling) ? $groupedBilling['owner'] : $groupedBilling->owner;
                                $totalAmount = is_array($groupedBilling) ? $groupedBilling['total_amount'] : $groupedBilling->total_amount;
                                $paidAmount = is_array($groupedBilling) ? $groupedBilling['paid_amount'] : $groupedBilling->paid_amount;
                                $balance = is_array($groupedBilling) ? $groupedBilling['balance'] : $groupedBilling->balance;
                                $status = is_array($groupedBilling) ? $groupedBilling['status'] : $groupedBilling->status;
                                $petCount = is_array($groupedBilling) ? $groupedBilling['pet_count'] : $groupedBilling->pet_count;
                                $billDate = is_array($groupedBilling) ? $groupedBilling['bill_date'] : $groupedBilling->bill_date;
                                $petBillings = is_array($groupedBilling) ? $groupedBilling['billings'] : $groupedBilling->billings;
                                $ownerId = is_array($groupedBilling) ? $groupedBilling['owner_id'] : $groupedBilling->owner_id;
                                // determine if any billing in this owner group has a boarding service
                                $groupHasBoarding = false;
                                foreach ($petBillings as $pb) {
                                    $b = is_array($pb) ? (object)$pb : $pb;
                                    if (isset($b->visit) && $b->visit && isset($b->visit->services)) {
                                        foreach ($b->visit->services as $s) {
                                            $servType = strtolower($s->serv_type ?? '');
                                            $servName = strtolower($s->serv_name ?? '');
                                            if ($servType === 'boarding' || str_contains($servName, 'boarding')) { $groupHasBoarding = true; break 2; }
                                        }
                                    }
                                }
                                // check if group partial (50%) already paid
                                $groupPartialPaid = false;
                                foreach ($petBillings as $pb) {
                                    $billObj = is_array($pb) ? (object)$pb : $pb;
                                    if ($billObj->payments()->where('payment_type', 'partial')->where('status', 'paid')->exists()) { $groupPartialPaid = true; break; }
                                    if (strtolower($billObj->bill_status ?? '') === 'paid 50%') { $groupPartialPaid = true; break; }
                                }
                            @endphp
                            
                            {{-- Main Owner Row --}}
                            <tr class="hover:bg-gray-50 bg-white border-b-2 border-gray-300">
                                <td class="px-2 py-2 border">
                                    <button onclick="togglePetDetails({{ $index }})" class="text-blue-600 hover:text-blue-800">
                                        <i id="icon-{{ $index }}" class="fas fa-chevron-right transition-transform"></i>
                                    </button>
                                </td>
                                <td class="px-2 py-2 border font-semibold">
                                    {{ $owner->own_name ?? 'N/A' }}
                                </td>
                                <td class="px-2 py-2 border">
                                    <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-medium">
                                        {{ $petCount }} {{ $petCount > 1 ? 'pets' : 'pet' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 border text-left">
                                    <div class="text-sm text-gray-500">Total</div>
                                    <div class="font-bold text-lg text-blue-600">₱{{ number_format($totalAmount, 2) }}</div>
                                    <div class="text-sm text-gray-700 mt-1">Remaining: <span class="font-semibold">₱{{ number_format($balance, 2) }}</span></div>
                                </td>
                                <td class="px-4 py-2 border">
                                    @if($status === 'paid' || $balance <= 0.01)
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i> PAID
                                        </span>
                                    @elseif(strtolower($status) === 'paid 50%')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            <i class="fas fa-hand-holding-dollar mr-1"></i> PAID 50%
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            <i class="fas fa-exclamation-circle mr-1"></i> UNPAID
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 border">{{ \Carbon\Carbon::parse($billDate)->format('M d, Y') }}</td>
                                <td class="px-4 py-2 border text-center">
                                    @php $groupPartialAmount = round(($totalAmount * 0.5), 2); @endphp
                                    @if($groupHasBoarding && !$groupPartialPaid)
                                        {{-- Show owner-level partial (50%) and hide Pay All until partial paid --}}
                                        <button onclick="payPartialForOwner({{ $ownerId }}, '{{ $billDate }}', {{ $groupPartialAmount }}, {{ $petCount }})" 
                                            class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700 text-sm font-bold shadow-md transition-colors">
                                            <i class="fas fa-hand-holding-dollar mr-2"></i>Pay Partial (50%) ₱{{ number_format($groupPartialAmount, 2) }}
                                        </button>
                                    @else
                                        {{-- Show Pay All when no boarding partial required, or after partial paid --}}
                                        @if($status !== 'paid' && $balance > 0.01)
                                            <button onclick="payForOwner({{ $ownerId }}, '{{ $billDate }}', {{ max(0, $balance) }}, {{ $petCount }})" 
                                                class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 text-sm font-bold shadow-md transition-colors">
                                                <i class="fas fa-money-bill-wave mr-2"></i>Pay All
                                            </button>
                                        @else
                                            @if($ownerId)
                                                <a href="{{ route('sales.grouped.billing.receipt', ['owner_id' => $ownerId, 'bill_date' => $billDate]) }}" target="_blank"
                                                    class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded text-sm font-bold shadow-md transition-colors inline-flex items-center">
                                                    <i class="fas fa-receipt mr-2"></i>Receipt
                                                </a>
                                            @else
                                                <span class="bg-gray-400 text-white px-4 py-2 rounded text-sm font-bold shadow-md inline-flex items-center cursor-not-allowed">
                                                    <i class="fas fa-receipt mr-2"></i>Receipt Unavailable
                                                </span>
                                            @endif
                                        @endif
                                    @endif
                                </td>
                            </tr>
                            
                            {{-- Expandable pet details row --}}
                            <tr id="pets-{{ $index }}" class="hidden bg-gray-50">
                                <td colspan="7" class="px-4 py-3">
                                    <div class="bg-white rounded-lg shadow-sm p-4">
                                        <h4 class="font-semibold text-gray-700 mb-3">Pet Details for {{ $owner?->own_name ?? 'N/A' }}</h4>
                                        <table class="w-full">
                                            <thead class="bg-gray-100">
                                                <tr>
                                                    <th class="px-3 py-2 text-left text-xs">Pet Name</th>
                                                    <th class="px-3 py-2 text-left text-xs">Services</th>
                                                    <th class="px-3 py-2 text-right text-xs">Total Amount</th>
                                                    <th class="px-3 py-2 text-center text-xs">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($petBillings as $billing)
                                                    @php
                                                        $petTotal = (float) $billing->total_amount;
                                                        if($petTotal <= 0) {
                                                            // Calculate from visit services and prescriptions if total_amount is zero or not set
                                                            $petTotal = 0;
                                                            if($billing->visit && $billing->visit->services) {
                                                                foreach($billing->visit->services as $service) {
                                                                    $servicePrice = isset($service->pivot->total_price) ? (float)$service->pivot->total_price : 0;
                                                                    $petTotal += $servicePrice;
                                                                }
                                                            }
                                                            // Add prescription costs
                                                            $prescriptions = \App\Models\Prescription::where('pet_id', $billing->visit?->pet_id)
                                                            ->where('pres_visit_id', $billing->visit?->visit_id)
                                                                ->whereDate('prescription_date', $billing->visit?->visit_date)
                                                                ->get();
                                                            foreach($prescriptions as $prescription) {
                                                                $medications = json_decode($prescription->medication, true) ?? [];
                                                                foreach($medications as $med) {
                                                                    $medPrice = isset($med['price']) ? (float)$med['price'] : 0;
                                                                    $petTotal += $medPrice;
                                                                }
                                                            }
                                                        }
                                                        // Fetch prescriptions for this pet on the visit date
                                                        $prescriptions = \App\Models\Prescription::where('pet_id', $billing->visit?->pet_id)
                                                            ->where('pres_visit_id', $billing->visit?->visit_id)
                                                            ->whereDate('prescription_date', $billing->visit?->visit_date)
                                                            ->get();
                                                    @endphp
                                                    <tr class="border-b hover:bg-gray-50">
                                                        <td class="px-3 py-2">
                                                            <div class="font-medium">{{ $billing->visit?->pet?->pet_name ?? 'N/A' }}</div>
                                                            <div class="text-xs text-gray-500">{{ $billing->visit?->pet?->pet_species ?? '' }}</div>
                                                        </td>
                                                        <td class="px-3 py-2 text-left">
                                                            <div class="mb-1">
                                                                <span class="font-semibold text-xs">Service(s)</span>
                                                                @if($billing->visit && $billing->visit->services && $billing->visit->services->count() > 0)
                                                                    @foreach($billing->visit->services as $service)
                                                                        <div class="text-xs">• {{ $service->serv_name }} @if(isset($service->pivot->total_price) && $service->pivot->total_price > 0)- ₱{{ number_format($service->pivot->total_price, 2) }}@endif</div>
                                                                    @endforeach
                                                                @else
                                                                    <span class="text-gray-400 text-xs">No services</span>
                                                                @endif
                                                            </div>
                                                            @if($prescriptions->count() > 0)
                                                                <div class="mt-1">
                                                                    <span class="font-semibold text-xs">Prescription(s)</span>
                                                                    @foreach($prescriptions as $prescription)
                                                                        @php
                                                                            $medications = json_decode($prescription->medication, true) ?? [];
                                                                        @endphp
                                                                        @foreach($medications as $med)
                                                                            <div class="text-xs">• {{ $med['product_name'] ?? $med['name'] ?? 'Medication' }}@if(isset($med['price'])) - ₱{{ number_format($med['price'], 2) }}@endif</div>
                                                                        @endforeach
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                        </td>
                                                        <td class="px-3 py-2 text-right font-semibold text-blue-600">₱{{ number_format($petTotal, 2) }}</td>
                                                        @php
                                                            $pendingPartial = $billing->payments()->where('payment_type', 'partial')->where('status', 'pending')->first();
                                                            $paidPartialExists = $billing->payments()->where('payment_type', 'partial')->where('status', 'paid')->exists();
                                                            // Use billing's stored paid amount so partial payments immediately reflect in the balance
                                                            $paidAmount = (float) $billing->paid_amount;
                                                            $balance = max(0, $petTotal - $paidAmount);
                                                            $hasBoarding = false;
                                                            if ($billing->visit && $billing->visit->services) {
                                                                $hasBoarding = $billing->visit->services->contains(function($s) {
                                                                    $servType = strtolower($s->serv_type ?? '');
                                                                    $servName = strtolower($s->serv_name ?? '');
                                                                    return $servType === 'boarding' || str_contains($servName, 'boarding');
                                                                });
                                                            }
                                                        @endphp
                                                        <td class="px-3 py-2 text-center">
                                                            <div class="flex items-center justify-center gap-2">
                                                                <button onclick="viewSingleBilling({{ $billing->bill_id }})" 
                                                                    class="bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded text-xs" title="View">
                                                                    <i class="fas fa-eye mr-1"></i>View
                                                                </button>
                                                                <button onclick="printSingleBilling({{ $billing->bill_id }})" 
                                                                    class="bg-purple-500 hover:bg-purple-600 text-white px-2 py-1 rounded text-xs" title="Print">
                                                                    <i class="fas fa-print mr-1"></i>Print
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-2 border text-center text-gray-500">
                                    No billing records found.
                                </td>
                            </tr>
                        @endforelse
                        @endif
                    </tbody>
                </table>
            </div>

            {{-- Billing Pagination --}}
            <div id="billingPagination" class="mt-4 flex justify-between items-center">
                <!-- Pagination will be generated by JavaScript -->
            </div>
        </div>

        {{-- Orders Tab Content (Unchanged) --}}
        <!-- Removed duplicate ordersContent tab -->
        <div id="ordersContent" class="tab-content hidden">
            {{-- Description --}}
            <div class="bg-green-50 border border-green-200 rounded-md px-4 py-2 mb-4">
                <p class="text-sm text-gray-700">
                    <i class="fas fa-shopping-cart text-green-500 mr-2"></i>
                    <strong>Direct POS Sales:</strong> Product sales made directly through the Point of Sale system (not linked to visit services)
                </p>
            </div>
            
            <div class="flex flex-nowrap items-center justify-between gap-3 mt-4 text-sm font-semibold text-black w-full overflow-x-auto pb-2">
                <form method="GET" action="{{ request()->url() }}" class="flex-shrink-0 flex items-center space-x-2">
                    <input type="hidden" name="tab" value="orders">
                    <label for="ordersPerPage" class="whitespace-nowrap text-sm text-black">Show</label>
                    <select name="ordersPerPage" id="ordersPerPage" onchange="this.form.submit()" class="border border-gray-400 rounded px-2 py-1.5 text-sm">
                        @foreach ([10, 20, 50, 100, 'all'] as $limit)
                            <option value="{{ $limit }}" {{ request('ordersPerPage') == $limit ? 'selected' : '' }}>
                                {{ $limit === 'all' ? 'All' : $limit }}
                            </option>
                        @endforeach
                    </select>
                    <span class="whitespace-nowrap">entries</span>
                    <label class="whitespace-nowrap text-sm text-black ml-2">Filter</label>
                    <select id="ordersDateFilter" onchange="filterOrdersByDate()" class="border border-gray-400 rounded px-2 py-1.5 text-sm">
                        <option value="">All Time</option>
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                    </select>
                </form>
                <div class="relative flex-1 min-w-[200px] max-w-xs">
                    <input type="search" id="ordersSearch" placeholder="Search orders..." class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm pl-8">
                    <i class="fas fa-search absolute left-2 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table id="ordersTable" class="table-auto w-full border-collapse border text-sm text-center">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="border px-4 py-2">#</th>
                            <th class="border px-4 py-2">Transaction ID</th>
                            <th class="border px-4 py-2">Source</th>
                            <th class="border px-4 py-2">Sale Date</th>
                            <th class="border px-4 py-2">Products</th>
                            <th class="border px-4 py-2">Total Items</th>
                            <th class="border px-4 py-2">Transaction Total</th>
                            <th class="border px-4 py-2">Customer</th>
                            <th class="border px-4 py-2">Cashier</th>
                            <th class="border px-4 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            // Support both paginator and collection for paginatedTransactions
                            $transactionsList = $paginatedTransactions instanceof \Illuminate\Pagination\LengthAwarePaginator
                                ? $paginatedTransactions->items()
                                : (is_array($paginatedTransactions) ? $paginatedTransactions : $paginatedTransactions->all());
                        @endphp
                        @if(!empty($transactionsList))
                            @foreach($transactionsList as $transactionId => $transaction)
                                @php
                                    $orders = $transaction['orders'];
                                    $source = $transaction['source'];
                                    $billId = $transaction['bill_id'] ?? null;
                                    $firstOrder = $orders->first();
                                    $transactionTotal = $orders->sum(function($order) {
                                        return $order->ord_quantity * ($order->product->prod_price ?? 0);
                                    });
                                    $totalItems = $orders->sum('ord_quantity');
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="border px-4 py-2">{{ $loop->iteration + (($paginator ? ($paginator->currentPage() - 1) * $paginator->perPage() : 0)) }}</td>
                                    <td class="border px-4 py-2 font-mono text-blue-600">
                                        #{{ is_string($transactionId) ? $transactionId : (isset($transaction['transaction_id']) ? $transaction['transaction_id'] : 'N/A') }}
                                    </td>
                                    <td class="border px-4 py-2">
                                        @if($source === 'Billing Payment')
                                            <span class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">
                                                <i class="fas fa-file-invoice mr-1"></i>
                                                Billing #{{ $billId }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                                <i class="fas fa-shopping-cart mr-1"></i>
                                                Direct Sale
                                            </span>
                                        @endif
                                    </td>
                                    <td class="border px-4 py-2">
                                        {{ \Carbon\Carbon::parse($firstOrder->ord_date)->format('M d, Y h:i A') }}
                                    </td>
                                    <td class="border px-4 py-2 text-left">
                                        @foreach($orders as $order)
                                            <div class="flex justify-between items-center py-1 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                                                <span class="text-gray-700">{{ $order->product->prod_name ?? 'N/A' }}</span>
                                                <span class="text-sm text-gray-500 ml-2">×{{ $order->ord_quantity }}</span>
                                            </div>
                                        @endforeach
                                    </td>
                                    <td class="border px-4 py-2 font-semibold">
                                        {{ $totalItems }}
                                    </td>
                                    <td class="border px-4 py-2 font-bold text-green-600">
                                        ₱{{ number_format($transactionTotal, 2) }}
                                    </td>
                                    <td class="border px-4 py-2">
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full
                                            {{ $firstOrder->owner ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ $firstOrder->owner->own_name ?? 'Walk-in Customer' }}
                                        </span>
                                    </td>
                                    <td class="border px-4 py-2">
                                        {{ $firstOrder->user->user_name ?? 'N/A' }}
                                    </td>
                                    <td class="border px-4 py-2">
                                        <button onclick="viewTransaction('{{ is_string($transactionId) ? $transactionId : (isset($transaction['transaction_id']) ? $transaction['transaction_id'] : 'N/A') }}')" 
                                                class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 text-xs"
                                                title="View Transaction Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="10" class="py-8 text-gray-500">
                                    <div class="text-center">
                                        <i class="fas fa-receipt text-4xl mb-4 opacity-50"></i>
                                        <p class="text-lg">No transactions found.</p>
                                        <p class="text-sm">Try adjusting your date filters or check back later.</p>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            {{-- Orders Pagination --}}
            <div id="ordersPagination" class="mt-6 flex justify-end">
                <!-- Pagination will be generated by JavaScript -->
            </div>
        </div>
    </div>
</div>

{{-- Transaction Details Modal --}}
<div id="viewTransactionModal" class="fixed inset-0 flex justify-center items-center z-50 hidden no-print bg-black bg-opacity-50">
    <div class="bg-white w-full max-w-3xl p-0 rounded-lg shadow-lg relative max-h-[90vh] overflow-y-auto">
        <div id="modalTransactionContent" class="billing-container bg-white p-10">
            <div class="header flex items-center justify-between border-b-2 border-black pb-6 mb-6">
                <div class="flex-shrink-0">
                    <img src="{{ asset('images/pets2go.png') }}" alt="Pets2GO Logo" class="w-28 h-28 object-contain">
                </div>
                
                <div class="flex-grow text-center">
                    <div class="clinic-name text-2xl font-bold text-[#a86520] tracking-wide">
                        PETS 2GO VETERINARY CLINIC
                    </div>
                    <div class="branch-name text-lg font-bold underline text-center mt-1" id="transactionBranchName">
                        {{ auth()->user()->branch->branch_name ?? 'Main Branch' }}
                    </div>
                    <div class="clinic-details text-sm text-gray-700 mt-1 text-center leading-tight">
                        <div id="transactionBranchAddress">{{ auth()->user()->branch->branch_address ?? '' }}</div>
                        <div id="transactionBranchContact">{{ auth()->user()->branch->branch_contact ?? '' }}</div>
                    </div>
                </div>
            </div>

            <div class="billing-body">
                <div class="text-center mb-6">
                    <h2 class="text-xl font-bold text-gray-800">SALES TRANSACTION RECEIPT</h2>
                </div>

                <div class="customer-info mb-6">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <div class="mb-2"><strong>DATE:</strong> <span id="transactionDate"></span></div>
                            <div class="mb-2"><strong>CUSTOMER:</strong> <span id="transactionCustomer"></span></div>
                            <div class="mb-2"><strong>CASHIER:</strong> <span id="transactionCashier"></span></div>
                        </div>
                        <div>
                            <div class="mb-2"><strong>TRANSACTION ID:</strong> <span id="transactionId"></span></div>
                            <div class="mb-2"><strong>TYPE:</strong> <span id="transactionType"></span></div>
                        </div>
                    </div>
                </div>

                <div class="products-section mb-6">
                    <div class="section-title text-base font-bold mb-4 border-b pb-2 text-green-600">PRODUCTS PURCHASED</div>
                    <table class="w-full text-sm">
                        <thead class="border-b-2 border-gray-300">
                            <tr class="text-left">
                                <th class="py-2 px-2 w-12">#</th>
                                <th class="py-2 px-2">Product</th>
                                <th class="py-2 px-2 text-center w-20">Qty</th>
                                <th class="py-2 px-2 text-right w-28">Unit Price</th>
                                <th class="py-2 px-2 text-right w-32">Total</th>
                            </tr>
                        </thead>
                        <tbody id="transactionOrdersTable" class="divide-y divide-gray-200">
                            <!-- Orders will be populated here -->
                        </tbody>
                    </table>
                </div>

                <div class="total-section mb-8">
                    <div class="mt-4 pt-4 border-t-2 border-gray-300">
                        <div class="text-right">
                            <div class="text-xl font-bold text-[#0f7ea0]">TOTAL AMOUNT: <span id="transactionTotal"></span></div>
                        </div>
                    </div>
                </div>

                <div class="footer text-center pt-8 border-t-2 border-black">
                    <div class="thank-you text-sm">
                        <div class="font-bold mb-2">Thank you for choosing Pets2GO Veterinary Clinic!</div>
                        <div class="text-gray-600">Your pet's health is our priority</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="p-4 bg-gray-50 flex justify-between no-print">
            <button onclick="printTransactionFromModal()" class="px-4 py-2 bg-purple-500 text-white rounded hover:bg-purple-600">
                <i class="fas fa-print mr-2"></i> Print Receipt
            </button>
            <button onclick="closeTransactionModal()" 
                class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                <i class="fas fa-times mr-2"></i> Close
            </button>
        </div>
    </div>
</div>

{{-- Hidden Print Container for Transaction --}}
<div id="printTransactionContainer" style="display: none;">
    <div id="printTransactionContent" class="billing-container bg-white p-10">
    </div>
</div>

{{-- Billing View Modal (Cleaned up: Removed Add-ons) --}}
<div id="viewBillingModal" class="fixed inset-0 flex justify-center items-center z-50 hidden no-print bg-black bg-opacity-50">
    <div class="bg-white w-full max-w-3xl p-0 rounded-lg shadow-lg relative max-h-[90vh] overflow-y-auto">
        <div id="modalBillingContent" class="billing-container bg-white p-10">
            <div class="header flex items-center justify-between border-b-2 border-black pb-6 mb-6">
                <div class="flex-shrink-0">
                    <img src="{{ asset('images/pets2go.png') }}" alt="Pets2GO Logo" class="w-28 h-28 object-contain">
                </div>
                
                <div class="flex-grow text-center">
                    <div class="clinic-name text-2xl font-bold text-[#a86520] tracking-wide">
                        PETS 2GO VETERINARY CLINIC
                    </div>
                    <div class="branch-name text-lg font-bold underline text-center mt-1" id="viewBranchName">
                    
                    </div>
                    <div class="clinic-details text-sm text-gray-700 mt-1 text-center leading-tight">
                        <div id="viewBranchAddress"></div>
                        <div id="viewBranchContact"></div>
                    </div>
                </div>
            </div>

            <div class="billing-body">
                <div class="text-center mb-6">
                    <h2 class="text-xl font-bold text-gray-800">BILLING STATEMENT</h2>
                </div>

                <div class="customer-info mb-6">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <div class="mb-2"><strong>DATE:</strong> <span id="viewBillDate"></span></div>
                            <div class="mb-2"><strong>OWNER:</strong> <span id="viewOwner"></span></div>
                            <div class="mb-2"><strong>PET NAME:</strong> <span id="viewPet"></span></div>
                        </div>
                        <div>
                            <div class="mb-2"><strong>BILL ID:</strong> <span id="viewBillId"></span></div>
                            <div class="mb-2"><strong>PET SPECIES:</strong> <span id="viewPetSpecies"></span></div>
                            <div class="mb-2"><strong>PET BREED:</strong> <span id="viewPetBreed"></span></div>
                            <div class="mb-2"><strong>STATUS:</strong> <span id="viewBillStatus"></span></div>
                        </div>
                    </div>
                </div>

                <div class="services-section mb-6">
                    <div class="section-title text-base font-bold mb-4 border-b pb-2 text-blue-600">SERVICES PROVIDED</div>
                    <div id="servicesList" class="space-y-2"></div>
                    <div class="subtotal-section mt-4 pt-2 border-t border-gray-200">
                        <div class="text-right text-sm">
                            <div><strong>Services Subtotal: <span id="viewServicesTotal"></span></strong></div>
                        </div>
                    </div>
                </div>

                <div class="medications-section mb-6">
                    <div class="section-title text-base font-bold mb-4 border-b pb-2 text-green-600">MEDICATIONS PROVIDED</div>
                    <div id="medicationsList" class="space-y-2"></div>
                    <div class="subtotal-section mt-4 pt-2 border-t border-gray-200">
                        <div class="text-right text-sm">
                            <div><strong>Medications Subtotal: <span id="viewPrescriptionTotal"></span></strong></div>
                        </div>
                    </div>
                </div>
                
                {{-- REMOVED: Add-on Products Section --}}


                <div class="total-section mb-8">
                    <div class="mt-4 pt-4 border-t-2 border-gray-300">
                        <div class="text-right">
                            <div class="text-xl font-bold text-[#0f7ea0]">TOTAL AMOUNT: <span id="viewBillTotal"></span></div>
                            <div class="text-sm font-semibold text-red-500">BALANCE DUE: <span id="viewBalanceDue"></span></div>
                        </div>
                    </div>
                </div>

                <div class="footer text-center pt-8 border-t-2 border-black">
                    <div class="thank-you text-sm">
                        <div class="font-bold mb-2">Thank you for choosing Pets2GO Veterinary Clinic!</div>
                        <div class="text-gray-600">Your pet's health is our priority</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="p-4 bg-gray-50 flex justify-between no-print">
            <button onclick="printBillingFromModal()" class="px-4 py-2 bg-purple-500 text-white rounded hover:bg-purple-600">
                <i class="fas fa-print mr-2"></i> Print Bill
            </button>
            <button onclick="document.getElementById('viewBillingModal').classList.add('hidden')" 
                class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                <i class="fas fa-times mr-2"></i> Close
            </button>
        </div>
    </div>
</div>

{{-- Hidden Print Container for Billing (Unchanged) --}}
<div id="printBillingContainer" style="display: none;">
    <div id="printBillingContent" class="billing-container bg-white p-10">
    </div>
</div>

{{-- Transaction Details Modal --}}
<div id="viewTransactionModal" class="fixed inset-0 flex justify-center items-center z-50 hidden no-print bg-black bg-opacity-50">
    <div class="bg-white w-full max-w-3xl p-0 rounded-lg shadow-lg relative max-h-[90vh] overflow-y-auto">
        <div id="modalTransactionContent" class="billing-container bg-white p-10">
            <div class="header flex items-center justify-between border-b-2 border-black pb-6 mb-6">
                <div class="flex-shrink-0">
                    <img src="{{ asset('images/pets2go.png') }}" alt="Pets2GO Logo" class="w-28 h-28 object-contain">
                </div>
                
                <div class="flex-grow text-center">
                    <div class="clinic-name text-2xl font-bold text-[#a86520] tracking-wide">
                        PETS 2GO VETERINARY CLINIC
                    </div>
                    <div class="branch-name text-lg font-bold underline text-center mt-1" id="transactionBranchName">
                        {{ auth()->user()->branch->branch_name ?? 'Main Branch' }}
                    </div>
                    <div class="clinic-details text-sm text-gray-700 mt-1 text-center leading-tight">
                        <div id="transactionBranchAddress">{{ auth()->user()->branch->branch_address ?? '' }}</div>
                        <div id="transactionBranchContact">{{ auth()->user()->branch->branch_contact ?? '' }}</div>
                    </div>
                </div>
            </div>

            <div class="billing-body">
                <div class="text-center mb-6">
                    <h2 class="text-xl font-bold text-gray-800">SALES TRANSACTION RECEIPT</h2>
                </div>

                <div class="customer-info mb-6">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <div class="mb-2"><strong>DATE:</strong> <span id="transactionDate"></span></div>
                            <div class="mb-2"><strong>CUSTOMER:</strong> <span id="transactionCustomer"></span></div>
                            <div class="mb-2"><strong>CASHIER:</strong> <span id="transactionCashier"></span></div>
                        </div>
                        <div>
                            <div class="mb-2"><strong>TRANSACTION ID:</strong> <span id="transactionId"></span></div>
                            <div class="mb-2"><strong>TYPE:</strong> <span id="transactionType"></span></div>
                        </div>
                    </div>
                </div>

                <div class="products-section mb-6">
                    <div class="section-title text-base font-bold mb-4 border-b pb-2 text-green-600">PRODUCTS PURCHASED</div>
                    <table class="w-full text-sm">
                        <thead class="border-b-2 border-gray-300">
                            <tr class="text-left">
                                <th class="py-2 px-2 w-12">#</th>
                                <th class="py-2 px-2">Product</th>
                                <th class="py-2 px-2 text-center w-20">Qty</th>
                                <th class="py-2 px-2 text-right w-28">Unit Price</th>
                                <th class="py-2 px-2 text-right w-32">Total</th>
                            </tr>
                        </thead>
                        <tbody id="transactionOrdersTable" class="divide-y divide-gray-200">
                            <!-- Orders will be populated here -->
                        </tbody>
                    </table>
                </div>

                <div class="total-section mb-8">
                    <div class="mt-4 pt-4 border-t-2 border-gray-300">
                        <div class="text-right">
                            <div class="text-xl font-bold text-[#0f7ea0]">TOTAL AMOUNT: <span id="transactionTotal"></span></div>
                        </div>
                    </div>
                </div>

                <div class="footer text-center pt-8 border-t-2 border-black">
                    <div class="thank-you text-sm">
                        <div class="font-bold mb-2">Thank you for choosing Pets2GO Veterinary Clinic!</div>
                        <div class="text-gray-600">Your pet's health is our priority</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="p-4 bg-gray-50 flex justify-between no-print">
            <button onclick="printTransactionFromModal()" class="px-4 py-2 bg-purple-500 text-white rounded hover:bg-purple-600">
                <i class="fas fa-print mr-2"></i> Print Receipt
            </button>
            <button onclick="closeTransactionModal()" 
                class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                <i class="fas fa-times mr-2"></i> Close
            </button>
        </div>
    </div>
</div>

{{-- Hidden Print Container for Transaction --}}
<div id="printTransactionContainer" style="display: none;">
    <div id="printTransactionContent" class="billing-container bg-white p-10">
    </div>
</div>

{{-- Payment Modal (POS-style) (Unchanged) --}}
<div id="billingPaymentModal" class="fixed inset-0 flex items-center justify-center hidden z-50 bg-black bg-opacity-50">
    <div class="bg-white rounded-2xl p-8 w-96 shadow-2xl">
        <div class="text-center mb-6">
            <div class="w-16 h-16 bg-gradient-to-br from-green-400 to-green-600 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                <i class="fas fa-money-bill-wave text-white text-xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800" id="paymentModalTitle">Process Payment</h2>
            <p class="text-gray-500" id="paymentModalSubtitle">Enter the cash amount received</p>
        </div>
        
        <div class="space-y-4">
            <div class="bg-blue-50 rounded-xl p-4 border border-blue-200">
                <div class="text-sm text-gray-600 mb-2 font-semibold">Payment Details</div>
                <div id="paymentDetails" class="space-y-1 text-sm">
                    <div class="flex justify-between">
                        <span>Total Amount:</span>
                        <span class="font-bold" id="modalTotalAmount">₱0.00</span>
                    </div>
                    <div class="flex justify-between" id="paidAmountRow" style="display: none;">
                        <span>Already Paid:</span>
                        <span class="font-bold text-green-600" id="modalPaidAmount">₱0.00</span>
                    </div>
                    <div class="flex justify-between border-t pt-2 mt-2">
                        <span class="font-bold">Amount Due:</span>
                        <span class="font-bold text-red-600" id="modalAmountDue">₱0.00</span>
                    </div>
                </div>
            </div>
            
            <div>
                <label for="cashAmount" class="block text-sm font-semibold text-gray-700 mb-2">
                    Cash Amount Received <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">₱</span>
                    <input type="number" id="cashAmount" min="0" step="0.01"
                        class="w-full pl-8 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent text-lg font-semibold" />
                </div>
            </div>
            
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-4 border-2 border-green-200">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Change / Status</label>
                <div id="changeAmount" class="text-3xl font-bold text-green-600">₱0.00</div>
            </div>
        </div>
        
        <div class="flex gap-3 mt-8">
            <button id="cancelPayment" class="flex-1 px-6 py-3 bg-gray-500 text-white rounded-xl font-semibold shadow-lg hover:bg-gray-600">
                Cancel
            </button>
            <button id="confirmPayment" class="flex-1 px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl font-semibold shadow-lg hover:from-green-600 hover:to-green-700">
                Confirm Payment
            </button>
        </div>
    </div>
</div>

{{-- Success Modal (Unchanged) --}}
<div id="successModal" class="fixed inset-0 flex items-center justify-center hidden z-50 bg-black bg-opacity-50">
    <div class="bg-white rounded-2xl p-8 w-96 shadow-2xl">
        <div class="text-center">
            <div class="w-20 h-20 bg-gradient-to-br from-green-400 to-green-600 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                <i class="fas fa-check text-white text-2xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Payment Successful!</h2>
            <p class="text-gray-500 mb-4" id="successMessage">Transaction completed successfully</p>
            <div class="bg-green-50 rounded-xl p-4 border border-green-200 mb-6">
                <div class="text-sm text-gray-600 mb-1">Change Given</div>
                <div class="text-2xl font-bold text-green-600" id="successChange">₱0.00</div>
            </div>
            <button id="closeSuccess" class="w-full px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl font-semibold hover:from-blue-600 hover:to-blue-700">
                <i class="fas fa-check mr-2"></i>Done
            </button>
        </div>
    </div>
</div>


<style>
/* Existing and Custom Styles for UI uniformity */
.tab-button.active {
    border-color: #0f7ea0;
    color: #0f7ea0;
}

.tab-content {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.billing-container {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    border: 1px solid #000;
    background-color: white;
}

.service-item, .medication-item {
    font-size: 14px;
    line-height: 1.5;
    margin-bottom: 8px;
    padding: 8px;
    border-bottom: 1px solid #eee;
}

.service-item:last-child, .medication-item:last-child {
    border-bottom: none;
}

.service-item {
    border-left: 3px solid #3b82f6;
    background-color: #eff6ff;
}

.medication-item {
    border-left: 3px solid #10b981;
    background-color: #f0fdf4;
}

/* Print Styles */
@media print {
    @page {
        margin: 0.5in;
        size: A4 portrait;
    }
    
    body * {
        visibility: hidden;
    }
    
    #printBillingContainer,
    #printBillingContainer *,
    #printBillingContent,
    #printBillingContent *,
    #printTransactionContainer,
    #printTransactionContainer *,
    #printTransactionContent,
    #printTransactionContent * {
        visibility: visible !important;
    }
    
    #printBillingContainer,
    #printTransactionContainer {
        position: fixed !important;
        left: 0 !important;
        top: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        display: block !important;
        visibility: visible !important;
        background: white !important;
        z-index: 99999 !important;
        overflow: visible !important;
    }
    
    #printBillingContent,
    #printTransactionContent {
        position: relative !important;
        width: 7.5in !important;
        min-height: 10in !important;
        height: auto !important;
        background: white !important;
        border: none !important;
        padding: 0.3in !important;
        margin: 0 auto !important;
        box-sizing: border-box !important;
        max-width: 7.5in !important;
        visibility: visible !important;
        overflow: visible !important;
    }
    
    #printBillingContent .footer {
        page-break-inside: avoid;
    }
    
    .no-print {
        display: none !important;
        visibility: hidden !important;
    }
    
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }
    
    .clinic-name {
        color: #a86520 !important;
    }
    
    .service-item {
        border-left: 3px solid #3b82f6 !important;
        background-color: #eff6ff !important;
    }
    
    .medication-item {
        border-left: 3px solid #10b981 !important;
        background-color: #f0fdf4 !important;
    }
    
    /* Ensure all child elements in print containers are visible */
    #printBillingContent *,
    #printTransactionContent * {
        display: revert !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    
    /* Ensure header elements are properly visible and not covered */
    #printBillingContent .header,
    #printTransactionContent .header {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        border-bottom: 2px solid #000 !important;
        padding-bottom: 0.25in !important;
        margin-bottom: 0.25in !important;
        page-break-inside: avoid !important;
    }
    
    #printBillingContent .header img,
    #printTransactionContent .header img {
        width: 1.5in !important;
        height: 1.5in !important;
        object-fit: contain !important;
    }
    
    #printBillingContent .clinic-name,
    #printTransactionContent .clinic-name {
        font-size: 18pt !important;
        font-weight: bold !important;
        color: #a86520 !important;
        margin-bottom: 0.1in !important;
    }
    
    #printBillingContent .branch-name,
    #printTransactionContent .branch-name {
        font-size: 14pt !important;
        font-weight: bold !important;
        text-decoration: underline !important;
        margin-top: 0.05in !important;
    }
    
    #printBillingContent .clinic-details,
    #printTransactionContent .clinic-details {
        font-size: 10pt !important;
        margin-top: 0.05in !important;
    }
    
    /* Footer on separate page if needed */
    #printBillingContent .footer,
    #printTransactionContent .footer {
        page-break-inside: avoid !important;
        margin-top: 0.25in !important;
    }
}
</style>

{{-- SweetAlert2 CDN --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Global variables
let currentBillId = null;
let currentPaymentType = null;
let currentBalance = 0;
let currentTotalAmount = 0; 
let currentBillingData = null; // Important for Print functionality

// Open receipt in popup window
function openReceiptPopup(billId) {
    const receiptUrl = `{{ url('/sales/billing') }}/${billId}/receipt`;
    const popupWidth = 900;
    const popupHeight = 800;
    const left = (screen.width - popupWidth) / 2;
    const top = (screen.height - popupHeight) / 2;
    
    window.open(
        receiptUrl,
        'BillingReceipt',
        `width=${popupWidth},height=${popupHeight},left=${left},top=${top},resizable=yes,scrollbars=yes`
    );
}

// Tab switching (Unchanged)
function switchTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active', 'border-[#0f7ea0]', 'text-[#0f7ea0]');
        button.classList.add('border-transparent', 'text-gray-500');
    });
    
    document.getElementById(tabName + 'Content').classList.remove('hidden');
    
    const activeTab = document.getElementById(tabName + 'Tab');
    activeTab.classList.add('active', 'border-[#0f7ea0]', 'text-[#0f7ea0]');
    activeTab.classList.remove('border-transparent', 'text-gray-500');
}

// Toggle payment options dropdown (Unchanged)
function togglePaymentOptions(billId) {
    const dropdown = document.getElementById('paymentOptions' + billId);
    
    document.querySelectorAll('[id^="paymentOptions"]').forEach(el => {
        if (el.id !== 'paymentOptions' + billId) {
            el.classList.add('hidden');
        }
    });
    
    dropdown.classList.toggle('hidden');
}

// Close dropdowns when clicking outside (Unchanged)
document.addEventListener('click', function(event) {
    if (!event.target.closest('[onclick^="togglePaymentOptions"]')) {
        document.querySelectorAll('[id^="paymentOptions"]').forEach(el => {
            el.classList.add('hidden');
        });
    }
});

// Toggle pet details for grouped billing
function togglePetDetails(index) {
    const row = document.getElementById('pets-' + index);
    const icon = document.getElementById('icon-' + index);
    
    if (row && icon) {
        row.classList.toggle('hidden');
        icon.classList.toggle('fa-chevron-right');
        icon.classList.toggle('fa-chevron-down');
    }
}

// Pay for all pets of an owner (grouped payment - FULL PAYMENT ONLY)
function payForOwner(ownerId, billDate, balance, petCount) {
    if (balance <= 0) {
        Swal.fire({
            icon: 'info',
            title: 'Already Paid',
            text: 'This billing group has been fully paid.',
            confirmButtonColor: '#10b981'
        });
        return;
    }
    
    Swal.fire({
        title: 'Full Payment for All Pets',
        html: `
            <div class="text-center mb-4">
                <p class="text-gray-600 mb-2">Payment for ${petCount} pet(s)</p>
                <p class="text-2xl font-bold text-green-600 mb-4">₱${balance.toFixed(2)}</p>
                <div class="bg-yellow-50 border border-yellow-200 rounded p-3 mb-4">
                    <p class="text-sm text-yellow-800">
                        <i class="fas fa-info-circle mr-1"></i>
                        Full payment only - all pets will be marked as paid
                    </p>
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-left text-gray-700 font-semibold mb-2">Cash Amount</label>
                <input type="number" id="groupCashAmount" class="w-full px-4 py-2 border rounded-lg" 
                       placeholder="Enter cash amount" step="0.01" min="${balance}" value="${balance}">
            </div>
            <div class="text-left bg-gray-50 p-3 rounded">
                <p class="text-gray-700">Change: <span id="groupChange" class="font-bold text-green-600">₱0.00</span></p>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Confirm Full Payment',
        confirmButtonColor: '#10b981',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
            const cashAmount = parseFloat(document.getElementById('groupCashAmount').value);
            if (!cashAmount || cashAmount <= 0) {
                Swal.showValidationMessage('Please enter a valid cash amount');
                return false;
            }
            if (cashAmount < balance) {
                Swal.showValidationMessage('Cash amount must be at least ₱' + balance.toFixed(2) + ' for full payment');
                return false;
            }
            return cashAmount;
        },
        didOpen: () => {
            const input = document.getElementById('groupCashAmount');
            const changeDisplay = document.getElementById('groupChange');
            
            // Set initial value and calculate change
            input.value = balance.toFixed(2);
            
            input.addEventListener('input', function() {
                const cashAmount = parseFloat(this.value) || 0;
                const change = cashAmount - balance;
                changeDisplay.textContent = '₱' + (change >= 0 ? change.toFixed(2) : '0.00');
            });
            
            // Trigger initial calculation
            input.dispatchEvent(new Event('input'));
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const cashAmount = result.value;
            
            // Send payment request
            fetch('/sales/billing-group/mark-paid', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    owner_id: ownerId,
                    bill_date: billDate,
                    cash_amount: cashAmount
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Payment Successful',
                        text: `Payment of ₱${cashAmount.toFixed(2)} has been recorded for all pets.`,
                        confirmButtonColor: '#10b981'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Payment Failed',
                        text: data.message || 'Unable to process payment',
                        confirmButtonColor: '#ef4444'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while processing payment',
                    confirmButtonColor: '#ef4444'
                });
            });
        }
    });
}

// Pay Partial (50%) for owner group — owner-level action
function payPartialForOwner(ownerId, billDate, partialAmount, petCount) {
    if (partialAmount <= 0) {
        Swal.fire({ icon: 'info', title: 'Invalid amount', text: 'Partial amount is invalid.' });
        return;
    }

    Swal.fire({
        title: 'Pay Partial (50%) for All Pets',
        html: `
            <div class="text-center mb-4">
                <p class="text-gray-600 mb-2">Payment for ${petCount} pet(s)</p>
                <p class="text-2xl font-bold text-yellow-600 mb-4">₱${partialAmount.toFixed(2)}</p>
                <div class="bg-yellow-50 border border-yellow-200 rounded p-3 mb-4">
                    <p class="text-sm text-yellow-800">
                        <i class="fas fa-info-circle mr-1"></i>
                        This will mark 50% of each pet's billing as paid ("PAID 50%"). Full payment can be completed later via Pay All.
                    </p>
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-left text-gray-700 font-semibold mb-2">Cash Amount</label>
                <input type="number" id="groupPartialCash" class="w-full px-4 py-2 border rounded-lg" placeholder="Enter cash amount" step="0.01" min="${partialAmount}" value="${partialAmount}">
            </div>
            <div class="text-left bg-gray-50 p-3 rounded">
                <p class="text-gray-700">Change: <span id="groupPartialChange" class="font-bold text-green-600">₱0.00</span></p>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Confirm Partial Payment',
        confirmButtonColor: '#f59e0b',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
            const cash = parseFloat(document.getElementById('groupPartialCash').value || '0');
            if (!cash || cash <= 0) { Swal.showValidationMessage('Please enter a valid cash amount'); return false; }
            if (cash < partialAmount - 0.01) { Swal.showValidationMessage('Cash amount must be at least ₱' + partialAmount.toFixed(2)); return false; }
            return cash;
        },
        didOpen: () => {
            const input = document.getElementById('groupPartialCash');
            const changeDisplay = document.getElementById('groupPartialChange');
            input.value = partialAmount.toFixed(2);
            input.addEventListener('input', function() {
                const cash = parseFloat(this.value) || 0;
                const change = cash - partialAmount;
                changeDisplay.textContent = '₱' + (change >= 0 ? change.toFixed(2) : '0.00');
            });
            input.dispatchEvent(new Event('input'));
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const cashAmount = result.value;
            fetch('/sales/billing-group/mark-paid', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    owner_id: ownerId,
                    bill_date: billDate,
                    cash_amount: cashAmount,
                    payment_type: 'partial'
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Partial Payment Recorded', text: data.message || 'Partial payment applied (PAID 50%)', confirmButtonColor: '#f59e0b' })
                        .then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Payment Failed', text: data.message || 'Unable to process partial payment', confirmButtonColor: '#ef4444' });
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire({ icon: 'error', title: 'Error', text: 'An error occurred while processing payment', confirmButtonColor: '#ef4444' });
            });
        }
    });
}

// View single pet billing details
function viewSingleBilling(billId) {
    fetch(`/sales/billing/${billId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const billing = data.billing;
                Swal.fire({
                    title: 'Billing Details',
                    html: `
                        <div class="text-left space-y-3">
                            <div><strong>Pet:</strong> ${billing.pet_name}</div>
                            <div><strong>Owner:</strong> ${billing.owner_name}</div>
                            <div><strong>Date:</strong> ${billing.bill_date}</div>
                            <div><strong>Services:</strong> ${billing.services}</div>
                            <hr class="my-3">
                            <div><strong>Total Amount:</strong> ₱${parseFloat(billing.total_amount).toFixed(2)}</div>
                            <div><strong>Paid Amount:</strong> ₱${parseFloat(billing.paid_amount).toFixed(2)}</div>
                            <div><strong>Balance:</strong> ₱${parseFloat(billing.balance).toFixed(2)}</div>
                            <div><strong>Status:</strong> <span class="px-2 py-1 rounded ${billing.status === 'Paid' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">${billing.status}</span></div>
                        </div>
                    `,
                    confirmButtonText: 'Close',
                    confirmButtonColor: '#6366f1',
                    width: '500px'
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Unable to load billing details',
                    confirmButtonColor: '#ef4444'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An error occurred while loading billing details',
                confirmButtonColor: '#ef4444'
            });
        });
}

// Print single pet billing
function printSingleBilling(billId) {
    window.open(`/sales/billing/${billId}/receipt`, '_blank', 'width=800,height=600');
}

// Initiate payment (from dropdown)
function initiatePayment(billId, balance, type, totalAmount = null, paidAmount = null) {
    currentBillId = billId;
    
    // Close dropdown if exists
    const paymentOptionsEl = document.getElementById('paymentOptions' + billId);
    if (paymentOptionsEl) paymentOptionsEl.classList.add('hidden');

    // If totals weren't provided, attempt to extract from the table row
    if (totalAmount === null || paidAmount === null) {
        const button = document.querySelector(`button[onclick="togglePaymentOptions(${billId})"]`);
        const row = button ? button.closest('tr') : null;
        const totalAmountText = row ? row.querySelector('td:nth-child(4) .font-bold').textContent : '₱0.00';
        const paidAmountText = row ? row.querySelector('td:nth-child(5) .font-semibold').textContent : '₱0.00';
        totalAmount = parseFloat(totalAmountText.replace(/[^0-9.]/g, '')) || 0;
        paidAmount = parseFloat(paidAmountText.replace(/[^0-9.]/g, '')) || 0;
    }

    // Open payment modal with the existing total and balance
    openPaymentModal(totalAmount, balance, paidAmount, type);
}

// Consolidated Open Payment Modal (Unchanged)
function openPaymentModal(totalAmount, amountDue, paidAmount, initialType) {
    const modal = document.getElementById('billingPaymentModal');
    const cashInput = document.getElementById('cashAmount');
    const changeDisplay = document.getElementById('changeAmount');
    const confirmBtn = document.getElementById('confirmPayment');

    // Set globals for the transaction POST
    currentBalance = amountDue; 
    currentPaymentType = initialType; // Initialize payment type
    currentTotalAmount = totalAmount; 

    // Guardrail: If balance is zero, alert and close modal immediately.
    if (currentBalance <= 0.01) {
        alert("This bill is already fully paid (₱0.00 balance). Cannot process payment.");
        modal.classList.add('hidden');
        return;
    }
    
    // Update modal details
    document.getElementById('paymentModalTitle').textContent = initialType === 'full' ? 'Full Payment' : 'Initial/Partial Payment';
    document.getElementById('paymentModalSubtitle').textContent = 'Enter the amount received';

    document.getElementById('modalTotalAmount').textContent = '₱' + totalAmount.toFixed(2);
    
    const paidAmountRow = document.getElementById('paidAmountRow');
    if (paidAmount > 0) {
        paidAmountRow.style.display = 'flex';
        document.getElementById('modalPaidAmount').textContent = '₱' + paidAmount.toFixed(2);
    } else {
        paidAmountRow.style.display = 'none';
    }
    
    document.getElementById('modalAmountDue').textContent = '₱' + amountDue.toFixed(2);
    
    // Reset inputs
    // Pre-fill full amount if 'full' button was clicked and amount is due
    cashInput.value = (initialType === 'full') ? amountDue.toFixed(2) : '';
    changeDisplay.textContent = '₱0.00';
    changeDisplay.className = 'text-3xl font-bold text-gray-400';
    confirmBtn.disabled = (initialType === 'full' && amountDue > 0) ? false : true;

    // Dynamic Calculation Logic
    cashInput.oninput = function() {
        const cash = parseFloat(this.value) || 0;
        const change = cash - amountDue;

        if (cash < 0.01) {
            changeDisplay.textContent = '₱0.00';
            changeDisplay.className = 'text-3xl font-bold text-gray-400';
            confirmBtn.disabled = true;
            currentPaymentType = null;
        } else if (Math.abs(cash - amountDue) <= 0.01 || cash > amountDue) { // Full payment or overpayment (change)
            currentPaymentType = 'full';
            changeDisplay.textContent = '₱' + change.toFixed(2);
            changeDisplay.className = 'text-3xl font-bold text-green-600';
            confirmBtn.disabled = false;
        } else if (cash < amountDue) { // Partial/Initial Payment
            currentPaymentType = 'partial';
            const remaining = amountDue - cash;
            changeDisplay.textContent = 'Partial Payment (Remaining: ₱' + remaining.toFixed(2) + ')';
            changeDisplay.className = 'text-base font-bold text-yellow-600';
            confirmBtn.disabled = false;
        }
    };

    // Trigger calculation if a value was pre-filled (i.e., full payment button was clicked)
    if (initialType === 'full' && amountDue > 0) {
        cashInput.dispatchEvent(new Event('input'));
    }
    
    modal.classList.remove('hidden');
    setTimeout(() => cashInput.focus(), 300);
}

// Cancel payment (Unchanged)
document.getElementById('cancelPayment').addEventListener('click', function() {
    document.getElementById('billingPaymentModal').classList.add('hidden');
});

// Confirm payment (Unchanged)
document.getElementById('confirmPayment').addEventListener('click', async function() {
    const cashAmount = parseFloat(document.getElementById('cashAmount').value) || 0;
    
    if (!currentPaymentType || cashAmount < 0.01) {
        alert('Please enter a valid amount.');
        return;
    }
    
    // Use currentBalance (which is Amount Due) for validation
    if (currentPaymentType === 'full' && cashAmount < currentBalance - 0.01) { 
        alert('Insufficient cash amount for full payment.');
        return;
    }
    
    // Disable button to prevent double submission
    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
    
    try {
        const response = await fetch(`/sales/billing/${currentBillId}/pay`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                cash_amount: cashAmount,
                payment_type: currentPaymentType, 
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('billingPaymentModal').classList.add('hidden');
            
            // Show success modal
            document.getElementById('successMessage').textContent = 
                data.final_status === 'paid'
                ? 'Payment successful. Status: PAID. Bill is fully settled.'
                : 'Payment successful. Status: PARTIAL.';

            document.getElementById('successChange').textContent = '₱' + data.change.toFixed(2);
            document.getElementById('successModal').classList.remove('hidden');
        } else {
            alert(data.message || 'Payment failed');
        }
    } catch (error) {
        console.error('Payment error:', error);
        alert('An error occurred during payment');
    } finally {
        this.disabled = false;
        this.innerHTML = 'Confirm Payment';
    }
});

// Close success modal (Unchanged)
document.getElementById('closeSuccess').addEventListener('click', function() {
    document.getElementById('successModal').classList.add('hidden');
    location.reload(); // Reload to show updated billing status
});

// --- Billing View/Print Functions (Cleaned up: Removed Add-ons) ---

function populateBillingData(button) {
    const prescriptionItems = JSON.parse(button.dataset.prescriptionItems || '[]');
    
    const billingData = {
        billId: button.dataset.billId,
        owner: button.dataset.owner,
        pet: button.dataset.pet,
        petSpecies: button.dataset.petSpecies,
        petBreed: button.dataset.petBreed,
        date: button.dataset.date,
        servicesTotal: parseFloat(button.dataset.servicesTotal) || 0,
        prescriptionTotal: parseFloat(button.dataset.prescriptionTotal) || 0,
        // Removed addonTotal
        grandTotal: parseFloat(button.dataset.grandTotal) || 0,
        status: button.dataset.status || 'pending',
        services: button.dataset.services,
        prescriptionItems: prescriptionItems,
        // Removed addonOrders
        branchName: button.dataset.branchName.toUpperCase(),
        branchAddress: 'Address: ' + button.dataset.branchAddress,
        branchContact: "Contact No: " + button.dataset.branchContact
    };
    
    // Calculate balance for display
    const paidAmountElement = button.closest('tr').querySelector('td:nth-child(5) .font-semibold');
    const paidAmount = paidAmountElement ? parseFloat(paidAmountElement.textContent.replace(/[^0-9.]/g, '')) || 0 : 0;
    billingData.balanceDue = Math.max(0, billingData.grandTotal - paidAmount);

    return billingData;
}

function updateBillingContent(targetId, data) {
    const container = document.getElementById(targetId);
    
    // Parse services
    const servicesArray = data.services && data.services !== 'No services' ? data.services.split('|') : [];
    
    // Status display
    let statusBadge = '';
    if (data.status === 'paid') {
        statusBadge = '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">PAID</span>';
    } else if (data.status === 'partial') {
        statusBadge = '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">PARTIAL</span>';
    } else {
        statusBadge = '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">PENDING</span>';
    }

    // Build the full HTML content
    container.innerHTML = `
        <div class="header flex items-center justify-between border-b-2 border-black pb-6 mb-6">
            <div class="flex-shrink-0">
                <img src="{{ asset('images/pets2go.png') }}" alt="Pets2GO Logo" class="w-28 h-28 object-contain">
            </div>
            <div class="flex-grow text-center">
                <div class="clinic-name text-2xl font-bold text-[#a86520] tracking-wide">
                    PETS 2GO VETERINARY CLINIC
                </div>
                <div class="branch-name text-lg font-bold underline text-center mt-1">
                    ${data.branchName}
                </div>
                <div class="clinic-details text-sm text-gray-700 mt-1 text-center leading-tight">
                    <div>${data.branchAddress}</div>
                    <div>${data.branchContact}</div>
                </div>
            </div>
        </div>

        <div class="billing-body">
            <div class="text-center mb-6">
                <h2 class="text-xl font-bold text-gray-800">BILLING STATEMENT</h2>
            </div>

            <div class="customer-info mb-6">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <div class="mb-2"><strong>DATE:</strong> ${data.date}</div>
                        <div class="mb-2"><strong>OWNER:</strong> ${data.owner}</div>
                        <div class="mb-2"><strong>PET NAME:</strong> ${data.pet}</div>
                    </div>
                    <div>
                        <div class="mb-2"><strong>BILL ID:</strong> ${data.billId}</div>
                        <div class="mb-2"><strong>PET SPECIES:</strong> ${data.petSpecies}</div>
                        <div class="mb-2"><strong>PET BREED:</strong> ${data.petBreed}</div>
                        <div class="mb-2"><strong>STATUS:</strong> ${statusBadge}</div>
                    </div>
                </div>
            </div>

            <div class="services-section mb-6">
                <div class="section-title text-base font-bold mb-4 border-b pb-2 text-blue-600">SERVICES PROVIDED</div>
                <div class="space-y-2">
                    ${servicesArray.length > 0 ? servicesArray.map((service, index) => `
                        <div class="service-item">
                            <div class="text-sm font-medium">${index+1}. ${service.trim()}</div>
                        </div>
                    `).join('') : '<div class="service-item text-gray-500">No services provided</div>'}
                </div>
                <div class="subtotal-section mt-4 pt-2 border-t border-gray-200">
                    <div class="text-right text-sm">
                        <div><strong>Services Subtotal: ₱${data.servicesTotal.toFixed(2)}</strong></div>
                    </div>
                </div>
            </div>

            <div class="medications-section mb-6">
                <div class="section-title text-base font-bold mb-4 border-b pb-2 text-green-600">MEDICATIONS PROVIDED</div>
                <div class="space-y-2">
                    ${data.prescriptionItems && data.prescriptionItems.length > 0 ? data.prescriptionItems.map((item, index) => `
                        <div class="medication-item">
                            <div class="text-sm font-medium">${index+1}. ${item.name}</div>
                            ${item.price > 0 ? `<div class="text-xs text-gray-600 ml-4">₱${item.price.toFixed(2)}</div>` : ''}
                            ${item.instructions ? `<div class="text-xs text-gray-500 ml-4 italic">${item.instructions}</div>` : ''}
                        </div>
                    `).join('') : '<div class="medication-item text-gray-500">No medications provided</div>'}
                </div>
                <div class="subtotal-section mt-4 pt-2 border-t border-gray-200">
                    <div class="text-right text-sm">
                        <div><strong>Medications Subtotal: ₱${data.prescriptionTotal.toFixed(2)}</strong></div>
                    </div>
                </div>
            </div>
            
            {{-- REMOVED: Add-on Products Section (from previous version of updateBillingContent) --}}


            <div class="total-section mb-8">
                <div class="mt-4 pt-4 border-t-2 border-gray-300">
                    <div class="text-right">
                        <div class="text-xl font-bold text-[#0f7ea0]">TOTAL AMOUNT: ₱${data.grandTotal.toFixed(2)}</div>
                        <div class="text-sm font-semibold text-red-500">BALANCE DUE: ₱${data.balanceDue.toFixed(2)}</div>
                    </div>
                </div>
            </div>

            <div class="footer text-center pt-8 border-t-2 border-black">
                <div class="thank-you text-sm">
                    <div class="font-bold mb-2">Thank you for choosing Pets2GO Veterinary Clinic!</div>
                    <div class="text-gray-600">Your pet's health is our priority</div>
                </div>
            </div>
        </div>
    `;
}

function viewBilling(button) {
    const data = populateBillingData(button);
    currentBillingData = data;
    
    // Update modal content elements directly (as a quicker method for the modal view)
    document.getElementById('viewBillId').innerText = data.billId;
    document.getElementById('viewOwner').innerText = data.owner;
    document.getElementById('viewPet').innerText = data.pet;
    document.getElementById('viewPetSpecies').innerText = data.petSpecies;
    document.getElementById('viewPetBreed').innerText = data.petBreed;
    document.getElementById('viewBillDate').innerText = data.date;
    document.getElementById('viewBillTotal').innerText = `₱${data.grandTotal.toFixed(2)}`;
    document.getElementById('viewBalanceDue').innerText = `₱${data.balanceDue.toFixed(2)}`;
    document.getElementById('viewServicesTotal').innerText = `₱${data.servicesTotal.toFixed(2)}`;
    document.getElementById('viewPrescriptionTotal').innerText = `₱${data.prescriptionTotal.toFixed(2)}`;
    // Removed viewAddonTotal
    document.getElementById('viewBranchName').innerText = data.branchName;
    document.getElementById('viewBranchAddress').innerText = data.branchAddress;
    document.getElementById('viewBranchContact').innerText = data.branchContact;

    // Update status badge
    const statusElement = document.getElementById('viewBillStatus');
    let statusHTML = '';
    if (data.status === 'paid') {
        statusHTML = '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800"><i class="fas fa-check-circle mr-1"></i>PAID</span>';
    } else if (data.status === 'partial') {
        statusHTML = '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"><i class="fas fa-clock mr-1"></i>PARTIAL</span>';
    } else {
        statusHTML = '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800"><i class="fas fa-clock mr-1"></i>PENDING</span>';
    }
    statusElement.innerHTML = statusHTML;

    // Handle services
    const servicesContainer = document.getElementById('servicesList');
    servicesContainer.innerHTML = '';
    const servicesArray = data.services && data.services !== 'No services' ? data.services.split('|') : [];
    if (servicesArray.length > 0) {
        servicesArray.forEach((service, index) => {
            const serviceDiv = document.createElement('div');
            serviceDiv.classList.add('service-item');
            serviceDiv.innerHTML = `<div class="text-sm font-medium">${index+1}. ${service.trim()}</div>`;
            servicesContainer.appendChild(serviceDiv);
        });
    } else {
        servicesContainer.innerHTML = '<div class="service-item text-gray-500">No services provided</div>';
    }

    // Handle medications
    const medicationsContainer = document.getElementById('medicationsList');
    medicationsContainer.innerHTML = '';
    if (data.prescriptionItems && data.prescriptionItems.length > 0) {
        data.prescriptionItems.forEach((item, index) => {
            const medicationDiv = document.createElement('div');
            medicationDiv.classList.add('medication-item');
            medicationDiv.innerHTML = `
                <div class="text-sm font-medium">${index+1}. ${item.name}</div>
                ${item.price > 0 ? `<div class="text-xs text-gray-600 ml-4">₱${item.price.toFixed(2)}</div>` : ''}
                ${item.instructions ? `<div class="text-xs text-gray-500 ml-4 italic">${item.instructions}</div>` : ''}
            `;
            medicationsContainer.appendChild(medicationDiv);
        });
    } else {
        medicationsContainer.innerHTML = '<div class="medication-item text-gray-500">No medications provided</div>';
    }
    
    // Removed: Handle add-ons

    document.getElementById('viewBillingModal').classList.remove('hidden');
}


function directPrintBilling(button) {
    const data = populateBillingData(button);
    currentBillingData = data;
    
    // Update the hidden print container
    updateBillingContent('printBillingContent', data);
    
    // Show the print container temporarily
    const printContainer = document.getElementById('printBillingContainer');
    printContainer.style.display = 'block';
    
    // Small delay to ensure content is rendered, then print
    setTimeout(() => {
        window.print();
        // Clear content and hide after printing
        setTimeout(() => {
            document.getElementById('printBillingContent').innerHTML = '';
            printContainer.style.display = 'none';
        }, 100);
    }, 200);
}

function printBillingFromModal() {
    if (!currentBillingData) return;

    const data = currentBillingData;
    // Use the same structure as the bill view popup
    let statusBadge = '';
    if (data.status === 'paid') {
        statusBadge = '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">PAID</span>';
    } else if (data.status === 'partial') {
        statusBadge = '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">PARTIAL</span>';
    } else {
        statusBadge = '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">PENDING</span>';
    }

    // Services
    const servicesArray = data.services && data.services !== 'No services' ? data.services.split('|') : [];
    let servicesHtml = '';
    if (servicesArray.length > 0) {
        servicesHtml = servicesArray.map((service, index) => `
            <div class="service-item">
                <div class="text-sm font-medium">${index+1}. ${service.trim()}</div>
            </div>
        `).join('');
    } else {
        servicesHtml = '<div class="service-item text-gray-500">No services provided</div>';
    }

    // Medications
    let medicationsHtml = '';
    if (data.prescriptionItems && data.prescriptionItems.length > 0) {
        medicationsHtml = data.prescriptionItems.map((item, index) => `
            <div class="medication-item">
                <div class="text-sm font-medium">${index+1}. ${item.name}</div>
                ${item.price > 0 ? `<div class="text-xs text-gray-600 ml-4">₱${item.price.toFixed(2)}</div>` : ''}
                ${item.instructions ? `<div class="text-xs text-gray-500 ml-4 italic">${item.instructions}</div>` : ''}
            </div>
        `).join('');
    } else {
        medicationsHtml = '<div class="medication-item text-gray-500">No medications provided</div>';
    }

    const logoUrl = "{{ asset('images/pets2go.png') }}";
    const billingHTML = `<!DOCTYPE html><html><head><title>Billing Statement #${data.billId}</title>
    <style>* { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; padding: 20px; background: white; }
    .billing-container { max-width: 800px; margin: 0 auto; border: 1px solid #000; background-color: white; padding: 40px; }
    .header { display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid black; padding-bottom: 24px; margin-bottom: 24px; }
    .header img { width: 7rem; height: 7rem; object-fit: contain; }
    .clinic-name { font-size: 24px; font-weight: bold; color: #a86520; letter-spacing: 1px; }
    .branch-name { font-size: 18px; font-weight: bold; text-decoration: underline; margin-top: 5px; }
    .clinic-details { font-size: 14px; color: #333; margin-top: 5px; }
    h2 { text-align: center; font-size: 20px; margin-bottom: 20px; color: #333; }
    .customer-info { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; font-size: 14px; }
    .customer-info div { margin-bottom: 8px; }
    .section-title { font-size: 16px; font-weight: bold; border-bottom: 1px solid #333; padding-bottom: 8px; margin: 20px 0 15px 0; }
    .service-item, .medication-item { font-size: 14px; line-height: 1.5; margin-bottom: 8px; padding: 8px; border-bottom: 1px solid #eee; }
    .service-item:last-child, .medication-item:last-child { border-bottom: none; }
    .service-item { border-left: 3px solid #3b82f6; background-color: #eff6ff; }
    .medication-item { border-left: 3px solid #10b981; background-color: #f0fdf4; }
    .subtotal-section { margin-top: 15px; padding-top: 10px; border-top: 1px solid #ddd; text-align: right; }
    .total-section { margin-top: 20px; padding-top: 15px; border-top: 2px solid #333; text-align: right; }
    .total-section .total { font-size: 20px; font-weight: bold; color: #0f7ea0; }
    .footer { text-align: center; padding-top: 20px; border-top: 2px solid black; margin-top: 30px; font-size: 14px; }
    .status-badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 500; }
    .status-paid { background: #dcfce7; color: #166534; }
    .status-pending { background: #fee2e2; color: #991b1b; }
    @media print { body { padding: 0; } }
    </style></head><body onload="window.print(); setTimeout(window.close, 500);">
    <div class="billing-container">
        <div class="header">
            <img src="${logoUrl}" alt="Pets2GO Logo">
            <div class="flex-grow text-center">
                <div class="clinic-name">PETS 2GO VETERINARY CLINIC</div>
                <div class="branch-name">${data.branchName}</div>
                <div class="clinic-details">
                    <div>${data.branchAddress}</div>
                    <div>${data.branchContact}</div>
                </div>
            </div>
        </div>
        <div class="billing-body">
            <div class="text-center mb-6">
                <h2 class="text-xl font-bold text-gray-800">BILLING STATEMENT</h2>
            </div>
            <div class="customer-info mb-6">
                <div>
                    <div class="mb-2"><strong>DATE:</strong> ${data.date}</div>
                    <div class="mb-2"><strong>OWNER:</strong> ${data.owner}</div>
                    <div class="mb-2"><strong>PET NAME:</strong> ${data.pet}</div>
                </div>
                <div>
                    <div class="mb-2"><strong>BILL ID:</strong> ${data.billId}</div>
                    <div class="mb-2"><strong>PET SPECIES:</strong> ${data.petSpecies}</div>
                    <div class="mb-2"><strong>PET BREED:</strong> ${data.petBreed}</div>
                    <div class="mb-2"><strong>STATUS:</strong> ${statusBadge}</div>
                </div>
            </div>
            <div class="services-section mb-6">
                <div class="section-title text-base font-bold mb-4 border-b pb-2 text-blue-600">SERVICES PROVIDED</div>
                <div class="space-y-2">${servicesHtml}</div>
                <div class="subtotal-section mt-4 pt-2 border-t border-gray-200">
                    <div class="text-right text-sm">
                        <div><strong>Services Subtotal: ₱${data.servicesTotal.toFixed(2)}</strong></div>
                    </div>
                </div>
            </div>
            <div class="medications-section mb-6">
                <div class="section-title text-base font-bold mb-4 border-b pb-2 text-green-600">MEDICATIONS PROVIDED</div>
                <div class="space-y-2">${medicationsHtml}</div>
                <div class="subtotal-section mt-4 pt-2 border-t border-gray-200">
                    <div class="text-right text-sm">
                        <div><strong>Medications Subtotal: ₱${data.prescriptionTotal.toFixed(2)}</strong></div>
                    </div>
                </div>
            </div>
            <div class="total-section mb-8">
                <div class="mt-4 pt-4 border-t-2 border-gray-300">
                    <div class="text-right">
                        <div class="text-xl font-bold text-[#0f7ea0]">TOTAL AMOUNT: ₱${data.grandTotal.toFixed(2)}</div>
                        <div class="text-sm font-semibold text-red-500">BALANCE DUE: ₱${data.balanceDue.toFixed(2)}</div>
                    </div>
                </div>
            </div>
            <div class="footer text-center pt-8 border-t-2 border-black">
                <div class="thank-you text-sm">
                    <div class="font-bold mb-2">Thank you for choosing Pets2GO Veterinary Clinic!</div>
                    <div class="text-gray-600">Your pet's health is our priority</div>
                </div>
            </div>
        </div>
    </div>
    </body></html>`;
    const printWindow = window.open('', '_blank', 'width=950,height=900');
    printWindow.document.write(billingHTML);
    printWindow.document.close();
    // Robust print trigger: wait for content to load, then print
    printWindow.onload = function() {
        setTimeout(function() {
            printWindow.focus();
            printWindow.print();
            setTimeout(function() { printWindow.close(); }, 500);
        }, 200);
    };
}

// --- Orders Tab Functions (Unchanged) ---
function printTransaction(button) {
    const transactionId = button.getAttribute('data-id');
    const url = `/sales/print-transaction/${transactionId}`;
    window.open(url, '_blank', 'width=800,height=600');
}

function showFilters() {
    const filterSection = document.getElementById('filterSection');
    filterSection.classList.toggle('hidden');
}

function exportSales() {
    const startDate = document.querySelector('input[name="start_date"]')?.value || '';
    const endDate = document.querySelector('input[name="end_date"]')?.value || '';

    let exportUrl = '{{ route("sales.export") }}';
    const params = new URLSearchParams();
    
    if (startDate) params.append('start_date', startDate);
    if (endDate) params.append('end_date', endDate);

    if (params.toString()) {
        exportUrl += '?' + params.toString();
    }

    window.location.href = exportUrl;
}

function viewTransaction(transactionId) {
    // Fetch transaction details via AJAX and show in modal
    fetch('/sales/transaction/' + transactionId + '/json')
        .then(response => response.json())
        .then(data => {
            // Store transaction data globally for printing
            window.currentTransactionData = data;
            window.currentTransactionId = transactionId;
            
            // Populate modal with transaction data
            document.getElementById('transactionId').textContent = transactionId;
            document.getElementById('transactionType').textContent = data.transactionType;
            document.getElementById('transactionDate').textContent = data.date;
            document.getElementById('transactionCustomer').textContent = data.customer;
            document.getElementById('transactionCashier').textContent = data.cashier;
            document.getElementById('transactionTotal').textContent = '₱' + data.total;
            
            // Populate branch info
            document.getElementById('transactionBranchName').textContent = data.branch.name;
            document.getElementById('transactionBranchAddress').textContent = data.branch.address;
            document.getElementById('transactionBranchContact').textContent = data.branch.contact;
            
            // Populate orders table with new layout
            const tbody = document.getElementById('transactionOrdersTable');
            tbody.innerHTML = '';
            data.orders.forEach((order, index) => {
                const row = `
                    <tr>
                        <td class="py-2 px-2">${index + 1}</td>
                        <td class="py-2 px-2">${order.product}</td>
                        <td class="py-2 px-2 text-center">${order.quantity}</td>
                        <td class="py-2 px-2 text-right">₱${order.unitPrice}</td>
                        <td class="py-2 px-2 text-right font-semibold">₱${order.total}</td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
            
            // Show modal
            document.getElementById('viewTransactionModal').classList.remove('hidden');
        })
        .catch(error => {
            console.error('Error fetching transaction:', error);
            alert('Failed to load transaction details');
        });
}

function closeTransactionModal() {
    document.getElementById('viewTransactionModal').classList.add('hidden');
}

function printTransactionFromModal() {
    if (!window.currentTransactionData) {
        alert('No transaction data available');
        return;
    }
    const data = window.currentTransactionData;
    const transactionId = window.currentTransactionId;
    let productsHtml = '';
    if (data.orders && data.orders.length > 0) {
        data.orders.forEach((order, index) => {
            productsHtml += '<tr>' +
                '<td class="py-2 px-2">' + (index + 1) + '</td>' +
                '<td class="py-2 px-2">' + order.product + '</td>' +
                '<td class="py-2 px-2 text-center">' + order.quantity + '</td>' +
                '<td class="py-2 px-2 text-right">₱' + order.unitPrice + '</td>' +
                '<td class="py-2 px-2 text-right font-semibold">₱' + order.total + '</td>' +
            '</tr>';
        });
    }
    const logoUrl = "{{ asset('images/pets2go.png') }}";
    const receiptHTML = '<!DOCTYPE html><html><head><title>Sales Transaction #' + transactionId + '</title>' +
    '<style>* { margin:0; padding:0; box-sizing:border-box; } body { font-family:Arial,sans-serif; padding:20px; background:#fff; } .container { max-width:900px; margin:0 auto; padding:30px; background:#fff; } .header { display:flex; align-items:center; justify-content:space-between; border-bottom:2px solid #000; padding-bottom:20px; margin-bottom:20px; } .header img { width:7rem; height:7rem; object-fit:contain; } .clinic-info { text-align:center; flex-grow:1; } .clinic-name { font-size:24px; font-weight:700; color:#a86520; letter-spacing:1px; } .branch-name { font-size:18px; font-weight:700; text-decoration:underline; margin-top:5px; } .clinic-details { font-size:14px; color:#333; margin-top:5px; } h2 { text-align:center; font-size:20px; margin:20px 0; color:#333; } .customer-info { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px; font-size:14px; } .section-title { font-size:16px; font-weight:700; border-bottom:1px solid #333; padding-bottom:8px; margin:20px 0 15px; } table { width:100%; border-collapse:collapse; } th,td { font-size:12px; } thead { border-bottom:2px solid #d1d5db; } tbody tr { border-bottom:1px solid #e5e7eb; } .total-section { margin-top:20px; padding-top:15px; border-top:2px solid #333; text-align:right; } .total { font-size:20px; font-weight:700; color:#0f7ea0; } .footer { text-align:center; padding-top:20px; border-top:2px solid #000; margin-top:30px; font-size:14px; } .buttons { text-align:center; margin-top:30px; } .btn { padding:12px 30px; margin:0 10px; font-size:16px; cursor:pointer; border:none; border-radius:5px; } .btn-print { background:#0f7ea0; color:#fff; } .btn-close { background:#6b7280; color:#fff; } .btn:hover { opacity:.9; } @media print { .buttons { display:none; } body { padding:0; } }</style></head><body onload="window.print(); setTimeout(window.close, 500);">' +
    '<div class="container">' +
    '<div class="header">' +
    '<img src="' + logoUrl + '" alt="Pets2GO Logo">' +
    '<div class="clinic-info">' +
    '<div class="clinic-name">PETS 2GO VETERINARY CLINIC</div>' +
    '<div class="branch-name">' + document.getElementById('transactionBranchName').textContent + '</div>' +
    '<div class="clinic-details">' +
    '<div>' + document.getElementById('transactionBranchAddress').textContent + '</div>' +
    '<div>' + document.getElementById('transactionBranchContact').textContent + '</div>' +
    '</div></div></div>' +
    '<h2>SALES TRANSACTION RECEIPT</h2>' +
    '<div class="customer-info"><div>' +
    '<div><strong>DATE:</strong> ' + data.date + '</div>' +
    '<div><strong>CUSTOMER:</strong> ' + data.customer + '</div>' +
    '<div><strong>CASHIER:</strong> ' + data.cashier + '</div>' +
    '</div><div>' +
    '<div><strong>TRANSACTION ID:</strong> ' + transactionId + '</div>' +
    '<div><strong>TYPE:</strong> ' + data.transactionType + '</div>' +
    '</div></div>' +
    '<div class="section-title" style="color: #16a34a;">PRODUCTS PURCHASED</div>' +
    '<table class="w-full text-sm"><thead><tr>' +
    '<th class="py-2 px-2 w-12">#</th>' +
    '<th class="py-2 px-2">Product</th>' +
    '<th class="py-2 px-2 text-center w-20">Qty</th>' +
    '<th class="py-2 px-2 text-right w-28">Unit Price</th>' +
    '<th class="py-2 px-2 text-right w-32">Total</th>' +
    '</tr></thead><tbody>' + productsHtml + '</tbody></table>' +
    '<div class="total-section"><div class="total">TOTAL AMOUNT: ₱' + data.total + '</div></div>' +
    '<div class="footer">' +
    '<div style="font-weight: bold; margin-bottom: 8px;">Thank you for choosing Pets2GO Veterinary Clinic!</div>' +
    '<div style="color: #666;">Your pet\'s health is our priority</div>' +
    '</div>' +
    '</div></body></html>';
    const printWindow = window.open('', '_blank', 'width=950,height=900');
    printWindow.document.write(receiptHTML);
    printWindow.document.close();
}


// Simple client-side table filters (Unchanged)
function setupFilter(inputId, tableSelector){
    const input = document.getElementById(inputId);
    const table = document.querySelector(tableSelector);
    const tbody = table ? table.querySelector('tbody') : null;
    if(!input || !tbody) return;
    input.addEventListener('input', function(){
        filterBillingTable();
    });
}

// Filter billing by status
window.filterBillingByStatus = function() {
    filterBillingTable();
}

// Combined filter function for billing (search + status)
function filterBillingTable() {
    const searchInput = document.getElementById('billingSearch');
    const statusFilter = document.getElementById('billingStatusFilter');
    const table = document.querySelector('#billingContent table.min-w-full');
    const tbody = table ? table.querySelector('tbody') : null;
    
    if (!tbody) return;
    
    const searchQuery = searchInput ? searchInput.value.toLowerCase() : '';
    const statusValue = statusFilter ? statusFilter.value.toLowerCase() : '';
    
    // Get all main owner rows and their detail rows
    const allRows = tbody.querySelectorAll('tr');
    
    allRows.forEach(tr => {
        // Skip detail rows (pet-details-*) - they will follow their parent
        if (tr.id && tr.id.startsWith('pet-details-')) {
            return;
        }
        
        const text = tr.textContent.toLowerCase();
        
        // Check search match
        const searchMatch = !searchQuery || text.includes(searchQuery);
        
        // Check status match - look for status badge in the row
        let statusMatch = true;
        if (statusValue) {
            const statusCell = tr.querySelector('td:nth-child(5)'); // Status is 5th column
            if (statusCell) {
                const statusText = statusCell.textContent.toLowerCase().trim();
                statusMatch = statusText.includes(statusValue);
            }
        }
        
        const shouldShow = searchMatch && statusMatch;
        tr.style.display = shouldShow ? '' : 'none';
        
        // Also hide/show the corresponding pet details row
        const rowIndex = tr.querySelector('button[onclick^="togglePetDetails"]');
        if (rowIndex) {
            const onclickAttr = rowIndex.getAttribute('onclick');
            const match = onclickAttr.match(/togglePetDetails\((\d+)\)/);
            if (match) {
                const index = match[1];
                const detailsRow = document.getElementById('pet-details-' + index);
                if (detailsRow) {
                    detailsRow.style.display = shouldShow ? '' : 'none';
                }
            }
        }
    });
}

// Filter orders by date (daily, weekly, monthly)
window.filterOrdersByDate = function() {
    filterOrdersTable();
}

// Combined filter function for orders (search + date)
function filterOrdersTable() {
    const searchInput = document.getElementById('ordersSearch');
    const dateFilter = document.getElementById('ordersDateFilter');
    const table = document.querySelector('#ordersContent table');
    const tbody = table ? table.querySelector('tbody') : null;
    
    if (!tbody) return;
    
    const searchQuery = searchInput ? searchInput.value.toLowerCase() : '';
    const dateValue = dateFilter ? dateFilter.value : '';
    
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    const allRows = tbody.querySelectorAll('tr');
    
    allRows.forEach(tr => {
        const text = tr.textContent.toLowerCase();
        
        // Check search match
        const searchMatch = !searchQuery || text.includes(searchQuery);
        
        // Check date match
        let dateMatch = true;
        if (dateValue) {
            // Find the date cell (4th column - Sale Date)
            const dateCell = tr.querySelector('td:nth-child(4)');
            if (dateCell) {
                const dateText = dateCell.textContent.trim();
                // Parse the date (format: "Dec 03, 2025 10:30 AM")
                const rowDate = new Date(dateText);
                rowDate.setHours(0, 0, 0, 0);
                
                if (dateValue === 'daily') {
                    // Same day as today
                    dateMatch = rowDate.getTime() === today.getTime();
                } else if (dateValue === 'weekly') {
                    // Within last 7 days
                    const weekAgo = new Date(today);
                    weekAgo.setDate(weekAgo.getDate() - 7);
                    dateMatch = rowDate >= weekAgo && rowDate <= today;
                } else if (dateValue === 'monthly') {
                    // Within last 30 days
                    const monthAgo = new Date(today);
                    monthAgo.setDate(monthAgo.getDate() - 30);
                    dateMatch = rowDate >= monthAgo && rowDate <= today;
                }
            }
        }
        
        const shouldShow = searchMatch && dateMatch;
        tr.style.display = shouldShow ? '' : 'none';
    });
}

// Setup orders search to also trigger combined filter
function setupOrdersFilter() {
    const input = document.getElementById('ordersSearch');
    if (input) {
        input.addEventListener('input', function() {
            filterOrdersTable();
        });
    }
}

document.addEventListener('DOMContentLoaded', function(){
    // Check if we should switch to orders tab when coming back from transaction view
    const activeTab = sessionStorage.getItem('activeTab');
    if (activeTab === 'orders') {
        switchTab('orders');
        sessionStorage.removeItem('activeTab'); // Clean up
    }
    
    // Initialize ListFilter for billing table
    new ListFilter({
        tableSelector: '#billingTable',
        searchInputId: 'billingSearch',
        perPageSelectId: 'billingPerPage',
        paginationContainerId: 'billingPagination',
        searchColumns: [1, 2], // Owner, Pets columns
        filterSelects: [
            { selectId: 'billingStatus', columnIndex: 4 } // Status column
        ]
    });
    
    // Initialize ListFilter for orders table if needed
    // Note: Orders search input needs to be added to the view first
    if (document.getElementById('ordersSearch')) {
        new ListFilter({
            tableSelector: '#ordersTable',
            searchInputId: 'ordersSearch', 
            perPageSelectId: 'ordersPerPage',
            paginationContainerId: 'ordersPagination',
            searchColumns: [1, 2, 4, 7, 8] // Transaction ID, Source, Products, Customer, Cashier columns
        });
    }
});
</script>
@endsection