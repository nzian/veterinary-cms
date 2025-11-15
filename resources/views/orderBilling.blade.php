@extends('AdminBoard')
@php
    $userRole = strtolower(auth()->user()->user_role ?? '');
    
    // Define permissions for each role
    $permissions = [
        'superadmin' => [
            'view_billing' => false,
            'print_billing' => true,
            'delete_billing' => true,
            'view_pos_sales' => true,
            'print_pos_sales' => true,
        ],
        'veterinarian' => [
            'view_billing' => false,
            'print_billing' => true,
            'delete_billing' => false,
            'view_pos_sales' => true,
            'print_pos_sales' => true,
        ],
        'receptionist' => [
            'view_billing' => false,
            'print_billing' => true,
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
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4 w-full">
                <div class="relative w-full sm:w-auto">
                    <input type="search" id="billingSearch" placeholder="Search billing..." class="w-full sm:w-64 border border-gray-300 rounded px-3 py-2 text-sm pl-8">
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
        $servicesTotal = $billing->visit->services->sum('serv_price');
    }

    // FIX: RESTORE PRESCRIPTION TOTAL CALCULATION (PRICE IS INCLUDED)
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
                if (isset($medication['product_id']) && $medication['product_id']) {
                    $product = \DB::table('tbl_prod')
                        ->where('prod_id', $medication['product_id'])
                        ->first();
                    
                    if ($product) {
                        $productPrice = (float) $product->prod_price;
                        $prescriptionTotal += $productPrice; // Add to total
                    }
                }
                
                $prescriptionItems[] = [
                    'name' => $medication['product_name'] ?? 'Unknown medication',
                    'price' => $productPrice, // Store price for display/print
                    'instructions' => $medication['instructions'] ?? ''
                ];
            }
        }
    }
    
    // Add-on products
    $addOnProducts = $billing->orders->where('source', 'Billing Add-on')->sum('ord_total');
    
    // Grand Total now includes services, prescriptions, AND add-ons
    $grandTotal = $servicesTotal + $prescriptionTotal + $addOnProducts;
    
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
                                <td class="px-4 py-2 border">
                                    {{-- Services --}}
                                    @if($billing->visit && $billing->visit->services && $billing->visit->services->count() > 0)
                                        <div class="mb-2">
                                            <div class="font-semibold text-blue-600 text-xs mb-1">SERVICES:</div>
                                            @foreach($billing->visit->services as $service)
                                                <span class="block text-xs">
                                                    {{ $service->serv_name ?? 'N/A' }} - ₱{{ number_format($service->serv_price ?? 0, 2) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                    
                                    {{-- Prescription Products (Now includes price) --}}
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
                                    
                                    {{-- Add-on Products --}}
                                    @if($billing->orders->where('source', 'Billing Add-on')->count() > 0)
                                        <div class="mb-2">
                                            <div class="font-semibold text-purple-600 text-xs mb-1">ADD-ONS:</div>
                                            @foreach($billing->orders->where('source', 'Billing Add-on') as $order)
                                                <span class="block text-xs">
                                                    {{ $order->product->prod_name ?? 'N/A' }} (x{{ $order->ord_quantity }}) - ₱{{ number_format($order->ord_total ?? 0, 2) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if(count($prescriptionItems) == 0 && (!$billing->visit || !$billing->visit->services || $billing->visit->services->count() == 0) && $billing->orders->where('source', 'Billing Add-on')->count() == 0)
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
                                        @if($billingStatus !== 'paid')
                                            {{-- Add Products Button --}}
                                            <button onclick="openAddProducts({{ $billing->bill_id }}, {{ $grandTotal }}, {{ $paidAmount }})" 
                                                class="bg-purple-600 text-white px-2 py-1 rounded hover:bg-purple-700 text-xs" title="Add Products">
                                                <i class="fas fa-plus-circle"></i>
                                            </button>
                                            
                                            {{-- Pay Button (Dropdown) --}}
                                            <div class="relative inline-block">
                                                <button onclick="togglePaymentOptions({{ $billing->bill_id }})" 
                                                    class="bg-blue-600 text-white px-2 py-1 rounded hover:bg-blue-700 text-xs" title="Pay">
                                                    <i class="fas fa-money-bill"></i> <i class="fas fa-caret-down"></i>
                                                </button>
                                                <div id="paymentOptions{{ $billing->bill_id }}" class="hidden absolute right-0 mt-1 w-40 bg-white rounded-md shadow-lg z-10 border">
                                                    {{-- Full payment opens modal with full amount pre-filled --}}
                                                    <button onclick="initiatePayment({{ $billing->bill_id }}, {{ $balance }}, 'full')" 
                                                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                        <i class="fas fa-check-double mr-2"></i>Full Payment
                                                    </button>
                                                    {{-- Initial payment opens modal for partial input --}}
                                                    <button onclick="initiatePayment({{ $billing->bill_id }}, {{ $balance }}, 'partial')" 
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
                                                data-prescription-total="{{ $prescriptionTotal }}" {{-- Pass prescription total for print --}}
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

        {{-- Orders Tab Content --}}
        <div id="ordersContent" class="tab-content hidden">
            <div class="flex justify-between items-center mb-4 gap-2 flex-wrap">
                <div class="relative">
                    <input type="search" id="ordersSearch" placeholder="Search sales..." class="border border-gray-300 rounded px-3 py-2 text-sm pl-8">
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
            <div id="filterSection" class="mb-4 p-4 bg-gray-100 rounded-lg hidden">
                <form method="GET" class="flex gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Start Date</label>
                        <input type="date" name="start_date" value="{{ request('start_date') }}" 
                               class="mt-1 block w-full border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">End Date</label>
                        <input type="date" name="end_date" value="{{ request('end_date') }}" 
                               class="mt-1 block w-full border-gray-300 rounded-md">
                    </div>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                        Apply Filter
                    </button>
                    <a href="{{ request()->url() }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded">
                        Reset
                    </a>
                </form>
            </div>
               <!-- Sales Table -->
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

            <!-- Pagination -->
            <div class="mt-6">
                {{ $paginator->appends(request()->query())->links() }}
            </div>

            <!-- Additional Info -->
            @if($paginatedTransactions->count() > 0)
                <div class="mt-4 text-sm text-gray-600 text-center">
                    Showing {{ $paginator->firstItem() }} to {{ $paginator->lastItem() }} of {{ $paginator->total() }} transactions
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Add Products Modal (No change) --}}
<div id="addProductsModal" class="fixed inset-0 flex items-center justify-center hidden z-50 bg-black bg-opacity-50">
    <div class="bg-white rounded-2xl w-11/12 max-w-4xl max-h-[90vh] overflow-hidden shadow-2xl">
        <div class="bg-gradient-to-r from-purple-600 to-purple-700 text-white p-6">
            <h2 class="text-2xl font-bold">Add Products to Billing</h2>
            <p class="text-sm opacity-90">Select products to add before payment</p>
        </div>
        
        <div class="p-6 overflow-y-auto max-h-[60vh]">
            {{-- Search Products --}}
            <div class="mb-4">
                <input type="search" id="productSearch" placeholder="Search products..." 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
            
            {{-- Products Grid --}}
            <div id="productsGrid" class="grid grid-cols-2 md:grid-cols-3 gap-3">
                </div>
            
            {{-- Selected Products Cart --}}
            <div class="mt-6 border-t pt-4">
                <h3 class="font-bold text-lg mb-3">Selected Products</h3>
                <div id="selectedProducts" class="space-y-2">
                    <p class="text-gray-500 text-sm">No products selected</p>
                </div>
                <div class="mt-4 flex justify-between items-center bg-gray-100 p-3 rounded-lg">
                    <span class="font-semibold">Additional Total:</span>
                    <span id="additionalTotal" class="text-xl font-bold text-purple-600">₱0.00</span>
                </div>
            </div>
        </div>
        
        <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
    <button onclick="closeAddProducts()" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
        Cancel
    </button>
    <button onclick="saveProducts()" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
        Save Products
    </button>
</div>
    </div>
</div>

{{-- Payment Modal (POS-style) (No change) --}}
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

{{-- Success Modal (No change) --}}
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

/* Animation for payment modal */
.animate-slideIn {
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        transform: translateY(-20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Custom styles for POS-style modal uniformity */
.billing-modal-style {
    background-color: white; 
}
.billing-gradient-icon {
     background-image: linear-gradient(to bottom right, #10b981, #059669); 
}
.btn-cancel {
    background-color: #6b7280; /* Tailwind Gray-500 */
    color: white;
    font-weight: 600;
}
.btn-confirm {
    background-image: linear-gradient(to right, #10b981, #059669); /* Tailwind Green-500/600 */
    color: white;
    font-weight: 600;
    transition: all 0.3s;
}
.btn-confirm:hover {
    background-image: linear-gradient(to right, #059669, #047857);
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
    #printBillingContent * {
        visibility: visible !important;
    }
    
    #printBillingContainer {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        height: 100% !important;
        display: block !important;
        background: white !important;
    }
    
    #printBillingContent {
        position: relative !important;
        width: 100% !important;
        height: auto !important;
        background: white !important;
        border: 2px solid #000 !important;
        padding: 30px !important;
        margin: 0 !important;
        box-sizing: border-box !important;
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
let selectedProducts = [];
let availableProducts = [];

// Tab switching (kept existing implementation)
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


// Toggle payment options dropdown (kept existing implementation)
function togglePaymentOptions(billId) {
    const dropdown = document.getElementById('paymentOptions' + billId);
    
    document.querySelectorAll('[id^="paymentOptions"]').forEach(el => {
        if (el.id !== 'paymentOptions' + billId) {
            el.classList.add('hidden');
        }
    });
    
    dropdown.classList.toggle('hidden');
}

// Close dropdowns when clicking outside (kept existing implementation)
document.addEventListener('click', function(event) {
    if (!event.target.closest('[onclick^="togglePaymentOptions"]')) {
        document.querySelectorAll('[id^="paymentOptions"]').forEach(el => {
            el.classList.add('hidden');
        });
    }
});

// Load available products (kept existing implementation)
async function loadProducts() {
    try {
        const response = await fetch('/api/products/available');
        const data = await response.json();
        availableProducts = data.products || [];
        renderProductsGrid();
    } catch (error) {
        console.error('Error loading products:', error);
        alert('Failed to load products for selection.');
    }
}

// Render products grid (kept existing implementation)
function renderProductsGrid() {
    const grid = document.getElementById('productsGrid');
    const keyword = document.getElementById('productSearch')?.value?.toLowerCase() || '';

    const filtered = availableProducts.filter(p => 
        p.prod_name.toLowerCase().includes(keyword)
    );

    if (filtered.length === 0) {
        grid.innerHTML = `<p class="text-gray-500 text-center py-4">No products found matching "${keyword}".</p>`;
        return;
    }

    grid.innerHTML = filtered.map(product => `
        <button onclick="addProductToSelection(${product.prod_id})" 
            class="border-2 border-gray-200 rounded-lg p-3 hover:border-purple-500 transition-all text-left">
            <div class="font-semibold text-sm">${product.prod_name}</div>
            <div class="text-purple-600 font-bold">₱${parseFloat(product.prod_price).toFixed(2)}</div>
            <div class="text-xs text-gray-500">Stock: ${product.prod_stocks}</div>
        </button>
    `).join('');
}

// Product search event listener
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('productSearch');
    if (searchInput) {
        searchInput.addEventListener('input', renderProductsGrid);
    }
    // Setup client-side filters
    setupFilter('billingSearch', '#billingContent table.w-full');
    setupFilter('ordersSearch', '#ordersContent table');
});

// Add product to selection (kept existing implementation)
function addProductToSelection(productId) {
    const product = availableProducts.find(p => p.prod_id === productId);
    if (!product) return;
    
    const existing = selectedProducts.find(p => p.prod_id === productId);
    if (existing) {
        if (existing.quantity < product.prod_stocks) {
            existing.quantity++;
        } else {
            alert('Maximum stock reached');
            return;
        }
    } else {
        selectedProducts.push({
            prod_id: product.prod_id,
            prod_name: product.prod_name,
            prod_price: parseFloat(product.prod_price),
            quantity: 1,
            max_stock: product.prod_stocks
        });
    }
    
    renderSelectedProducts();
}

// Render selected products (kept existing implementation)
function renderSelectedProducts() {
    const container = document.getElementById('selectedProducts');
    
    if (selectedProducts.length === 0) {
        container.innerHTML = '<p class="text-gray-500 text-sm">No products selected</p>';
        document.getElementById('additionalTotal').textContent = '₱0.00';
        return;
    }
    
    container.innerHTML = selectedProducts.map(product => `
        <div class="flex items-center justify-between bg-gray-50 p-3 rounded-lg">
            <div class="flex-1">
                <div class="font-semibold text-sm">${product.prod_name}</div>
                <div class="text-xs text-gray-600">₱${product.prod_price.toFixed(2)} each</div>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="updateProductQuantity(${product.prod_id}, -1)" 
                    class="w-7 h-7 bg-red-100 hover:bg-red-200 rounded text-red-600 flex items-center justify-center">
                    <i class="fas fa-minus text-xs"></i>
                </button>
                <span class="w-8 text-center font-semibold">${product.quantity}</span>
                <button onclick="updateProductQuantity(${product.prod_id}, 1)" 
                    class="w-7 h-7 bg-green-100 hover:bg-green-200 rounded text-green-600 flex items-center justify-center">
                    <i class="fas fa-plus text-xs"></i>
                </button>
                <button onclick="removeProduct(${product.prod_id})" 
                    class="w-7 h-7 bg-gray-200 hover:bg-red-500 hover:text-white rounded flex items-center justify-center ml-2">
                    <i class="fas fa-trash text-xs"></i>
                </button>
            </div>
        </div>
    `).join('');
    
    const total = selectedProducts.reduce((sum, p) => sum + (p.prod_price * p.quantity), 0);
    document.getElementById('additionalTotal').textContent = '₱' + total.toFixed(2);
}

// Update product quantity (kept existing implementation)
function updateProductQuantity(productId, change) {
    const product = selectedProducts.find(p => p.prod_id === productId);
    if (!product) return;
    
    const newQty = product.quantity + change;
    
    if (newQty <= 0) {
        removeProduct(productId);
        return;
    }
    
    if (newQty > product.max_stock) {
        alert('Maximum stock reached');
        return;
    }
    
    product.quantity = newQty;
    renderSelectedProducts();
}

// Remove product (kept existing implementation)
function removeProduct(productId) {
    selectedProducts = selectedProducts.filter(p => p.prod_id !== productId);
    renderSelectedProducts();
}

// Open add products modal (kept existing implementation)
function openAddProducts(billId, total, paidAmount) {
    currentBillId = billId;
    currentTotalAmount = total; 
    currentBalance = total - paidAmount;
    selectedProducts = [];
    
    loadProducts();
    document.getElementById('addProductsModal').classList.remove('hidden');
}

// Close add products modal (kept existing implementation)
function closeAddProducts() {
    document.getElementById('addProductsModal').classList.add('hidden');
    selectedProducts = [];
}

/**
 * Saves products to the bill via API without payment.
 */
async function saveProducts() {
    if (selectedProducts.length === 0) {
        alert('Please select products to add.');
        return;
    }

    const saveButton = document.querySelector('#addProductsModal button.bg-green-600');
    saveButton.disabled = true;
    saveButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';

    const productsToSave = selectedProducts.map(p => ({
        prod_id: p.prod_id,
        quantity: p.quantity
    }));

    try {
        const response = await fetch(`/sales/billing/${currentBillId}/add-products`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                additional_products: productsToSave
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(data.message);
            closeAddProducts();
            location.reload(); // Reload to show new total and products in the table
        } else {
            alert(data.message || 'Failed to save products.');
        }
    } catch (error) {
        console.error('Save products error:', error);
        alert('An error occurred while saving products.');
    } finally {
        saveButton.disabled = false;
        saveButton.innerHTML = 'Save Products';
    }
}

// Initiate payment (from dropdown)
function initiatePayment(billId, balance, type) {
    currentBillId = billId;
    
    // Close dropdown
    document.getElementById('paymentOptions' + billId).classList.add('hidden');

    // Get the base total amount from the row (for display purposes in the modal)
    const row = document.querySelector(`button[onclick="togglePaymentOptions(${billId})"]`).closest('tr');
    // Total Amount is in the 4th column (index 3)
    const totalAmountText = row ? row.querySelector('td:nth-child(4) .font-bold').textContent : '₱0.00';
    // Paid Amount is in the 5th column (index 4)
    const paidAmountText = row ? row.querySelector('td:nth-child(5) .font-semibold').textContent : '₱0.00';
    
    const totalAmount = parseFloat(totalAmountText.replace(/[^0-9.]/g, '')) || 0;
    const paidAmount = parseFloat(paidAmountText.replace(/[^0-9.]/g, '')) || 0;

    // Open payment modal with the existing total and balance
    // The balance is correctly passed in from the PHP Blade logic.
    openPaymentModal(totalAmount, balance, paidAmount, type);
}

// Consolidated Open Payment Modal
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

// Cancel payment
document.getElementById('cancelPayment').addEventListener('click', function() {
    document.getElementById('billingPaymentModal').classList.add('hidden');
});

// Confirm payment
document.getElementById('confirmPayment').addEventListener('click', async function() {
    const cashAmount = parseFloat(document.getElementById('cashAmount').value) || 0;
    
    if (!currentPaymentType || cashAmount < 0.01) {
        alert('Please enter a valid amount.');
        return;
    }

    if (currentPaymentType === 'full' && cashAmount < currentBalance) {
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

// Close success modal
document.getElementById('closeSuccess').addEventListener('click', function() {
    document.getElementById('successModal').classList.add('hidden');
    location.reload(); // Reload to show updated billing status
});

// Print billing (kept existing implementation)
function directPrintBilling(button) {
    const data = populateBillingData(button);
    currentBillingData = data;
    
    updateBillingContent('printBillingContent', data);
    
    const printContainer = document.getElementById('printBillingContainer');
    printContainer.style.display = 'block';
    
    setTimeout(() => {
        window.print();
        printContainer.style.display = 'none';
    }, 200);
}

// Populate billing data (kept existing implementation)
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
        grandTotal: parseFloat(button.dataset.grandTotal) || 0,
        status: button.dataset.status || 'pending',
        services: button.dataset.services,
        prescriptionItems: prescriptionItems,
        branchName: button.dataset.branchName.toUpperCase(),
        branchAddress: 'Address: ' + button.dataset.branchAddress,
        branchContact: "Contact No: " + button.dataset.branchContact
    };
    
    return billingData;
}

// Update billing content (kept existing implementation)
function updateBillingContent(targetId, data) {
    // Keep your existing implementation
}

// Simple client-side table filters
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
</script>
@endsection