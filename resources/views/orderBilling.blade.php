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
                <form method="GET" action="{{ request()->url() }}" class="flex-shrink-0 flex items-center space-x-2">
                    <input type="hidden" name="tab" value="billing">
                    <label for="billingPerPage" class="whitespace-nowrap text-sm text-black">Show</label>
                    <select name="perPage" id="billingPerPage" onchange="this.form.submit()" class="border border-gray-400 rounded px-2 py-1.5 text-sm">
                        @foreach ([10, 20, 50, 100, 'all'] as $limit)
                            <option value="{{ $limit }}" {{ request('perPage') == $limit ? 'selected' : '' }}>
                                {{ $limit === 'all' ? 'All' : $limit }}
                            </option>
                        @endforeach
                    </select>
                    <span class="whitespace-nowrap">entries</span>
                </form>
                <div class="relative flex-1 min-w-[200px] max-w-xs">
                    <input type="search" id="billingSearch" placeholder="Search billing..." class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm pl-8">
                    <i class="fas fa-search absolute left-2 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>

            {{-- Billing Table --}}
            <div class="w-full overflow-x-auto">
                <table class="min-w-full table-auto text-sm border text-center">
                    <thead>
                        <tr class="bg-gray-100 text-centered">
                            <th class="px-2 py-2 border whitespace-nowrap">Owner</th>
                            <th class="px-2 py-2 border whitespace-nowrap">Pet</th>
                            <th class="px-4 py-2 border">Services & Products</th>
                            <th class="px-4 py-2 border">Total Amount</th>
                            <th class="px-4 py-2 border">Paid Amount</th>
                            <th class="px-4 py-2 border">Balance</th>
                            <th class="px-4 py-2 border">Status</th>
                            <th class="px-4 py-2 border">Date</th>
                            <th class="px-4 py-2 border text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($billings as $billing)
                            @php
                                $servicesTotal = 0;
                                if ($billing->visit && $billing->visit->services) {
                                    // Calculate services total from pivot table (quantity * unit_price) or use total_price if available
                                    $servicesTotal = $billing->visit->services->sum(function($service) {
                                        $quantity = $service->pivot->quantity ?? 1;
                                        $unitPrice = $service->pivot->unit_price ?? $service->serv_price ?? 0;
                                        $totalPrice = $service->pivot->total_price ?? ($unitPrice * $quantity);
                                        return $totalPrice;
                                    });
                                    
                                    // Fallback: if pivot data is missing, use orders from billing
                                    if ($servicesTotal == 0 && $billing->orders) {
                                        $servicesTotal = $billing->orders->where('product.prod_category', 'Service')->sum('ord_total');
                                    }
                                }

                                // Calculate prescription total - Only include available products with valid prices
                                $prescriptionTotal = 0;
                                $prescriptionItems = [];
                                
                                if ($billing->visit && $billing->visit->pet) {
                                    $prescriptions = \App\Models\Prescription::where('pet_id', $billing->visit->pet->pet_id)
                                        ->whereDate('prescription_date', '<=', $billing->bill_date)
                                        ->whereDate('prescription_date', '>=', date('Y-m-d', strtotime($billing->bill_date . ' -7 days')))
                                        ->get();
                                    
                                    foreach ($prescriptions as $prescription) {
                                        $medications = json_decode($prescription->medication, true) ?? [];
                                        foreach ($medications as $medication) {
                                            $productPrice = 0;
                                            $productName = $medication['product_name'] ?? 'Unknown medication';
                                            
                                            if (isset($medication['product_id']) && $medication['product_id']) {
                                                // Only include products that are in stock and have a valid price
                                                $product = \DB::table('tbl_prod')
                                                    ->where('prod_id', $medication['product_id'])
                                                    ->where('prod_stocks', '>', 0) 
                                                    ->where('prod_price', '>', 0)
                                                    ->first();
                                                
                                                if ($product) {
                                                    $productPrice = (float) $product->prod_price;
                                                    $prescriptionTotal += $productPrice;
                                                    
                                                    // Add to prescription items if product is available
                                                    $prescriptionItems[] = [
                                                        'name' => $product->prod_name,
                                                        'price' => $productPrice,
                                                        'instructions' => $medication['instructions'] ?? ''
                                                    ];
                                                }
                                            }
                                        }
                                    }
                                }
                                
                                // REMOVED: Add-on products calculation
                                // $addOnOrders = $billing->orders->where('source', 'Billing Add-on');
                                // $addOnTotal = $addOnOrders->sum('ord_total'); 
                                
                                // Grand Total now includes ONLY services and prescriptions
                                $grandTotal = $servicesTotal + $prescriptionTotal;
                                
                                // Use paid_amount from database for balance logic
                                $paidAmount = (float) ($billing->paid_amount ?? 0);
                                $balance = round($grandTotal - $paidAmount, 2);
                                
                                // Status Logic
                                $billingStatus = strtolower($billing->bill_status ?? 'pending');
                                if ($balance <= 0.01 && $grandTotal > 0) {
                                    $billingStatus = 'paid';
                                } elseif ($paidAmount > 0) {
                                    $billingStatus = 'partial';
                                } else {
                                    $billingStatus = 'pending';
                                }
                            @endphp
                            
                            <tr class="hover:bg-gray-50">
                                <td class="px-2 py-2 border">
                                    {{ $billing->visit?->pet?->owner?->own_name ?? 'N/A' }}
                                </td>
                                <td class="px-2 py-2 border">
                                    <div>
                                        <div class="font-medium">{{ $billing->visit?->pet?->pet_name ?? 'N/A' }}</div>
                                        <div class="text-xs text-gray-500">{{ $billing->visit?->pet?->pet_species ?? '' }}</div>
                                    </div>
                                </td>
                                <td class="px-4 py-2 border text-left">
                                    {{-- Services --}}
                                    @if($billing->visit && $billing->visit->services && $billing->visit->services->count() > 0)
                                        <div class="mb-2">
                                            <div class="font-semibold text-blue-600 text-xs mb-1">SERVICES:</div>
                                            @foreach($billing->visit->services as $service)
                                                @php
                                                    $quantity = $service->pivot->quantity ?? 1;
                                                    $unitPrice = $service->pivot->unit_price ?? $service->serv_price ?? 0;
                                                    $totalPrice = $service->pivot->total_price ?? ($unitPrice * $quantity);
                                                @endphp
                                                <span class="block text-xs">
                                                    {{ $service->serv_name ?? 'N/A' }} 
                                                    @if($quantity > 1)
                                                        ({{ $quantity }}x)
                                                    @endif
                                                    - ₱{{ number_format($totalPrice, 2) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @elseif($billing->orders && $billing->orders->where('product.prod_category', 'Service')->count() > 0)
                                        <div class="mb-2">
                                            <div class="font-semibold text-blue-600 text-xs mb-1">SERVICES:</div>
                                            @foreach($billing->orders->where('product.prod_category', 'Service') as $order)
                                                <span class="block text-xs">
                                                    {{ $order->product->prod_name ?? 'N/A' }} 
                                                    @if($order->ord_quantity > 1)
                                                        ({{ $order->ord_quantity }}x)
                                                    @endif
                                                    - ₱{{ number_format($order->ord_total ?? 0, 2) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                    
                                    {{-- Prescription Products --}}
                                    @if(count($prescriptionItems) > 0)
                                        <div class="mb-2">
                                            <div class="font-semibold text-green-600 text-xs mb-1">MEDICATIONS (Prescribed):</div>
                                            @foreach($prescriptionItems as $item)
                                                <span class="block text-xs">
                                                    {{ $item['name'] }}
                                                        @if($item['price'] > 0)
                                                            - ₱{{ number_format($item['price'], 2) }}
                                                        @endif
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                    
                                    {{-- REMOVED: Add-on Products section --}}

                                    @if(count($prescriptionItems) == 0 && (!$billing->visit || !$billing->visit->services || $billing->visit->services->count() == 0))
                                        <span class="text-gray-500 text-xs">No items</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 border">
                                    <div class="font-bold text-lg">₱{{ number_format($grandTotal, 2) }}</div>
                                </td>
                                <td class="px-4 py-2 border">
                                    <div class="font-semibold text-green-600">₱{{ number_format($paidAmount, 2) }}</div>
                                </td>
                                <td class="px-4 py-2 border">
                                    <div class="font-semibold {{ $balance > 0.01 ? 'text-red-600' : 'text-green-600' }}">
                                        ₱{{ number_format(max(0, $balance), 2) }}
                                    </div>
                                </td>

                                <td class="px-4 py-2 border">
                                    @if($billingStatus === 'paid')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            PAID
                                        </span>
                                    @elseif($billingStatus === 'partial')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            <i class="fas fa-clock mr-1"></i>
                                            PARTIAL (₱{{ number_format(max(0, $balance), 2) }} due)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            <i class="fas fa-clock mr-1"></i>
                                            PENDING
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-2 border">{{ \Carbon\Carbon::parse($billing->bill_date)->format('M d, Y') }}</td>
                                <td class="px-4 py-2 border text-center">
                                    <div class="flex flex-wrap justify-center items-center gap-1">
                                        
                                        @if(hasPermission('view_billing', $can))
                                            {{-- View Button --}}
                                            <button onclick="viewBilling(this)" class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 text-xs" title="view"
                                                data-bill-id="{{ $billing->bill_id }}"
                                                data-owner="{{ $billing->visit?->pet?->owner?->own_name ?? 'N/A' }}"
                                                data-pet="{{ $billing->visit?->pet?->pet_name ?? 'N/A' }}"
                                                data-pet-species="{{ $billing->visit?->pet?->pet_species ?? 'N/A' }}"
                                                data-pet-breed="{{ $billing->visit?->pet?->pet_breed ?? 'N/A' }}"
                                                data-date="{{ \Carbon\Carbon::parse($billing->bill_date)->format('F d, Y') }}"
                                                data-services-total="{{ $servicesTotal }}"
                                                data-prescription-total="{{ $prescriptionTotal }}"
                                                data-grand-total="{{ $grandTotal }}"
                                                data-status="{{ $billingStatus }}"
                                                data-services="{{ $billing->visit && $billing->visit->services ? $billing->visit->services->map(function($service) { return $service->serv_name . ' - ₱' . number_format($service->serv_price ?? 0, 2); })->implode('|') : 'No services' }}"
                                                data-prescription-items="{{ json_encode($prescriptionItems) }}"
                                                data-branch-name="{{ $billing->visit?->user?->branch?->branch_name ?? 'Main Branch' }}"
                                                data-branch-address="{{ $billing->visit?->user?->branch?->branch_address ?? 'Branch Address' }}"
                                                data-branch-contact="{{ $billing->visit?->user?->branch?->branch_contactNum ?? 'Contact Number' }}">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        @endif
                                        
                                        @if($billingStatus !== 'paid' && $grandTotal > 0)
                                            {{-- Pay Button (Dropdown) --}}
                                            <div class="relative inline-block">
                                                <button onclick="togglePaymentOptions({{ $billing->bill_id }})" 
                                                    class="bg-blue-600 text-white px-2 py-1 rounded hover:bg-blue-700 text-xs" title="Pay">
                                                    <i class="fas fa-money-bill"></i> <i class="fas fa-caret-down"></i>
                                                </button>
                                                <div id="paymentOptions{{ $billing->bill_id }}" class="hidden absolute right-0 mt-1 w-40 bg-white rounded-md shadow-lg z-10 border">
                                                    {{-- Full payment opens modal with full amount pre-filled --}}
                                                    <button onclick="initiatePayment({{ $billing->bill_id }}, {{ max(0, $balance) }}, 'full')" 
                                                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                        <i class="fas fa-check-double mr-2"></i>Full Payment
                                                    </button>
                                                    {{-- Initial payment opens modal for partial input --}}
                                                    <button onclick="initiatePayment({{ $billing->bill_id }}, {{ max(0, $balance) }}, 'partial')" 
                                                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                        <i class="fas fa-coins mr-2"></i>Initial Payment
                                                    </button>
                                                </div>
                                            </div>
                                        @else
                                            <a href="{{ route('sales.billing.receipt', $billing->bill_id) }}" 
                                                class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 text-xs" title="Receipt">
                                                <i class="fas fa-receipt"></i>
                                            </a>
                                        @endif

                                        @if(hasPermission('print_billing', $can))
                                            <button onclick="directPrintBilling(this)" class="bg-purple-500 text-white px-2 py-1 rounded hover:bg-purple-600 text-xs" title="print"
                                                data-bill-id="{{ $billing->bill_id }}"
                                                data-owner="{{ $billing->visit?->pet?->owner?->own_name ?? 'N/A' }}"
                                                data-pet="{{ $billing->visit?->pet?->pet_name ?? 'N/A' }}"
                                                data-pet-species="{{ $billing->visit?->pet?->pet_species ?? 'N/A' }}"
                                                data-pet-breed="{{ $billing->visit?->pet?->pet_breed ?? 'N/A' }}"
                                                data-date="{{ \Carbon\Carbon::parse($billing->bill_date)->format('F d, Y') }}"
                                                data-services-total="{{ $servicesTotal }}"
                                                data-prescription-total="{{ $prescriptionTotal }}" 
                                                data-grand-total="{{ $grandTotal }}"
                                                data-status="{{ $billingStatus }}"
                                                data-services="{{ $billing->visit && $billing->visit->services ? $billing->visit->services->map(function($service) { return $service->serv_name . ' - ₱' . number_format($service->serv_price ?? 0, 2); })->implode('|') : 'No services' }}"
                                                data-prescription-items="{{ json_encode($prescriptionItems) }}"
                                                data-branch-name="{{ $billing->visit?->user?->branch?->branch_name ?? 'Main Branch' }}"
                                                data-branch-address="{{ $billing->visit?->user?->branch?->branch_address ?? 'Branch Address' }}"
                                                data-branch-contact="{{ $billing->visit?->user?->branch?->branch_contactNum ?? 'Contact Number' }}">
                                                <i class="fas fa-print"></i>
                                            </button>
                                        @endif

                                        @if(hasPermission('delete_billing', $can))
                                            <form method="POST" action="{{ route('sales.destroyBilling', $billing->bill_id) }}"
                                                onsubmit="return confirm('Are you sure you want to delete this billing?');"
                                                class="inline-block" title="delete">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                                class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 flex items-center gap-1 text-xs">
                                                    <i class="fas fa-trash"></i> 
                                                </button>
                                            </form>
                                        @endif
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
                    </tbody>
                </table>
            </div>

            {{-- Billing Pagination --}}
            <div class="mt-4 overflow-x-auto">
                <div class="flex justify-center">
                    {{ $billings->links() }}
                </div>
            </div>
        </div>

        {{-- Orders Tab Content (Unchanged) --}}
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
                    <select name="perPage" id="ordersPerPage" onchange="this.form.submit()" class="border border-gray-400 rounded px-2 py-1.5 text-sm">
                        @foreach ([10, 20, 50, 100, 'all'] as $limit)
                            <option value="{{ $limit }}" {{ request('perPage') == $limit ? 'selected' : '' }}>
                                {{ $limit === 'all' ? 'All' : $limit }}
                            </option>
                        @endforeach
                    </select>
                    <span class="whitespace-nowrap">entries</span>
                </form>
                <div class="relative flex-1 min-w-[200px] max-w-xs">
                    <input type="search" id="ordersSearch" placeholder="Search sales..." class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm pl-8">
                    <i class="fas fa-search absolute left-2 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>
                <div class="flex gap-2">
                    <button onclick="exportSales()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                        Export CSV
                    </button>
                    <button onclick="showFilters()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                        Filter
                    </button>
                </div>
            </div>
            <div id="filterSection" class="mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200 hidden">
                <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div class="col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                        <input type="date" name="start_date" value="{{ request('start_date') }}" 
                               class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                        <input type="date" name="end_date" value="{{ request('end_date') }}" 
                               class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="col-span-1 flex items-end space-x-2">
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium whitespace-nowrap">
                            <i class="fas fa-filter mr-1"></i> Apply Filter
                        </button>
                    </div>
                    <div class="col-span-1 flex items-end">
                        <a href="{{ request()->url() }}" class="w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded text-sm font-medium text-center whitespace-nowrap">
                            <i class="fas fa-undo mr-1"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="table-auto w-full border-collapse border text-sm text-center">
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
                        @forelse($paginatedTransactions as $transactionId => $transaction)
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
                                <td class="border px-4 py-2">{{ $loop->iteration + (($paginator->currentPage() - 1) * $paginator->perPage()) }}</td>
                                <td class="border px-4 py-2 font-mono text-blue-600">
                                    #{{ $transactionId }}
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
                                    <button onclick="viewTransaction('{{ $transactionId }}')" 
                                            class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 text-xs"
                                            title="View Transaction Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="py-8 text-gray-500">
                                    <div class="text-center">
                                        <i class="fas fa-receipt text-4xl mb-4 opacity-50"></i>
                                        <p class="text-lg">No transactions found.</p>
                                        <p class="text-sm">Try adjusting your date filters or check back later.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $paginator->appends(request()->query())->links() }}
            </div>

            @if($paginatedTransactions->count() > 0)
                <div class="mt-4 text-sm text-gray-600 text-center">
                    Showing {{ $paginator->firstItem() }} to {{ $paginator->lastItem() }} of {{ $paginator->total() }} transactions
                </div>
            @endif
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
        size: A4;
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
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        display: block !important;
        background: white !important;
    }
    
    #printBillingContent,
    #printTransactionContent {
        position: relative !important;
        width: 100% !important;
        height: auto !important;
        background: white !important;
        border: 2px solid #000 !important;
        padding: 30px !important;
        margin: 0 auto !important;
        box-sizing: border-box !important;
        max-width: 100%;
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
}
</style>


<script>
// Global variables
let currentBillId = null;
let currentPaymentType = null;
let currentBalance = 0;
let currentTotalAmount = 0; 
let currentBillingData = null; // Important for Print functionality

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

// Initiate payment (from dropdown)
function initiatePayment(billId, balance, type) {
    currentBillId = billId;
    
    // Close dropdown
    document.getElementById('paymentOptions' + billId).classList.add('hidden');

    // Find the current row to extract total and paid amount
    const button = document.querySelector(`button[onclick="togglePaymentOptions(${billId})"]`);
    const row = button ? button.closest('tr') : null;
    
    // Total Amount is in the 4th column (index 3)
    const totalAmountText = row ? row.querySelector('td:nth-child(4) .font-bold').textContent : '₱0.00';
    // Paid Amount is in the 5th column (index 4)
    const paidAmountText = row ? row.querySelector('td:nth-child(5) .font-semibold').textContent : '₱0.00';
    
    const totalAmount = parseFloat(totalAmountText.replace(/[^0-9.]/g, '')) || 0;
    const paidAmount = parseFloat(paidAmountText.replace(/[^0-9.]/g, '')) || 0;
    
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
        // Hide the container again after printing
        printContainer.style.display = 'none';
    }, 200);
}

function printBillingFromModal() {
    if (!currentBillingData) return;
    
    // Hide transaction container to avoid conflicts
    document.getElementById('printTransactionContainer').style.display = 'none';
    
    // Update the hidden print container with current data
    updateBillingContent('printBillingContent', currentBillingData);
    
    // Show the print container temporarily
    const printContainer = document.getElementById('printBillingContainer');
    printContainer.style.display = 'block';
    
    // Trigger print
    setTimeout(() => {
        window.print();
        // Hide the container again after printing
        printContainer.style.display = 'none';
    }, 200);
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
    
    // Build the print content
    let productsHtml = '';
    data.orders.forEach((order, index) => {
        productsHtml += `
            <tr>
                <td class="py-2 px-2">${index + 1}</td>
                <td class="py-2 px-2">${order.product}</td>
                <td class="py-2 px-2 text-center">${order.quantity}</td>
                <td class="py-2 px-2 text-right">₱${order.unitPrice}</td>
                <td class="py-2 px-2 text-right font-semibold">₱${order.total}</td>
            </tr>
        `;
    });
    
    const printContent = `
        <div class="header flex items-center justify-between border-b-2 border-black pb-6 mb-6">
            <div class="flex-shrink-0">
                <img src="{{ asset('images/pets2go.png') }}" alt="Pets2GO Logo" class="w-28 h-28 object-contain">
            </div>
            <div class="flex-grow text-center">
                <div class="clinic-name text-2xl font-bold text-[#a86520] tracking-wide">
                    PETS 2GO VETERINARY CLINIC
                </div>
                <div class="branch-name text-lg font-bold underline text-center mt-1">
                    ${document.getElementById('transactionBranchName').textContent}
                </div>
                <div class="clinic-details text-sm text-gray-700 mt-1 text-center leading-tight">
                    <div>${document.getElementById('transactionBranchAddress').textContent}</div>
                    <div>${document.getElementById('transactionBranchContact').textContent}</div>
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
                        <div class="mb-2"><strong>DATE:</strong> ${data.date}</div>
                        <div class="mb-2"><strong>CUSTOMER:</strong> ${data.customer}</div>
                        <div class="mb-2"><strong>CASHIER:</strong> ${data.cashier}</div>
                    </div>
                    <div>
                        <div class="mb-2"><strong>TRANSACTION ID:</strong> ${transactionId}</div>
                        <div class="mb-2"><strong>TYPE:</strong> ${data.transactionType}</div>
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
                    <tbody class="divide-y divide-gray-200">
                        ${productsHtml}
                    </tbody>
                </table>
            </div>
            
            <div class="total-section mb-8">
                <div class="mt-4 pt-4 border-t-2 border-gray-300">
                    <div class="text-right">
                        <div class="text-xl font-bold text-[#0f7ea0]">TOTAL AMOUNT: ₱${data.total}</div>
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
    
    // Update the hidden print container
    document.getElementById('printTransactionContent').innerHTML = printContent;
    
    // Hide billing container to avoid conflicts
    document.getElementById('printBillingContainer').style.display = 'none';
    
    
    // Show the print container temporarily
    const printContainer = document.getElementById('printTransactionContainer');
    printContainer.style.display = 'block';
    
    // Trigger print
    setTimeout(() => {
        window.print();
        // Hide the container again after printing
        //document.getElementById('viewBillingModal').style.display = 'block';
        printContainer.style.display = 'none';
    }, 200);
}

// Simple client-side table filters (Unchanged)
function setupFilter(inputId, tableSelector){
    const input = document.getElementById(inputId);
    const table = document.querySelector(tableSelector);
    const tbody = table ? table.querySelector('tbody') : null;
    if(!input || !tbody) return;
    input.addEventListener('input', function(){
        const q = this.value.toLowerCase();
        tbody.querySelectorAll('tr').forEach(tr => {
            const text = tr.textContent.toLowerCase();
            tr.style.display = text.includes(q) ? '' : 'none';
        });
    });
}

document.addEventListener('DOMContentLoaded', function(){
    // Check if we should switch to orders tab when coming back from transaction view
    const activeTab = sessionStorage.getItem('activeTab');
    if (activeTab === 'orders') {
        switchTab('orders');
        sessionStorage.removeItem('activeTab'); // Clean up
    }
    
    // Setup client-side filters
    setupFilter('billingSearch', '#billingContent table.min-w-full');
    setupFilter('ordersSearch', '#ordersContent table');
});
</script>
@endsection