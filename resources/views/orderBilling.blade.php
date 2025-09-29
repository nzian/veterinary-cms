@extends('AdminBoard')

@section('content')
<div class="min-h-screen">
    <div class="max-w-7xl mx-auto bg-white p-6 rounded-lg shadow">
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
            <div class="flex justify-between items-center mb-4">
            </div>

            {{-- Billing Table --}}
            <div class="overflow-x-auto">
                <table class="w-full table-auto text-sm border text-center">
                    <thead>
                        <tr class="bg-gray-100 text-centered">
                            <th class="px-4 py-2 border">Owner</th>
                            <th class="px-4 py-2 border">Pet</th>
                            <th class="px-4 py-2 border">Services & Products</th>
                            <th class="px-4 py-2 border">Total Amount</th>
                            <th class="px-4 py-2 border">Status</th>
                            <th class="px-4 py-2 border">Date</th>
                            <th class="px-4 py-2 border text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($billings as $billing)
                            @php
                                $servicesTotal = 0;
                                if ($billing->appointment && $billing->appointment->services) {
                                    $servicesTotal = $billing->appointment->services->sum('serv_price');
                                }

                                $prescriptionTotal = 0;
                                $prescriptionItems = [];
                                
                                if ($billing->appointment && $billing->appointment->pet) {
                                    $prescriptions = \App\Models\Prescription::where('pet_id', $billing->appointment->pet->pet_id)
                                        ->whereDate('prescription_date', '<=', $billing->bill_date)
                                        ->whereDate('prescription_date', '>=', date('Y-m-d', strtotime($billing->bill_date . ' -7 days')))
                                        ->get();
                                    
                                    foreach ($prescriptions as $prescription) {
                                        $medications = json_decode($prescription->medication, true) ?? [];
                                        foreach ($medications as $medication) {
                                            if (isset($medication['product_id']) && $medication['product_id']) {
                                                $product = \DB::table('tbl_prod')
                                                    ->where('prod_id', $medication['product_id'])
                                                    ->first();
                                                
                                                if ($product) {
                                                    $prescriptionItems[] = [
                                                        'name' => $product->prod_name,
                                                        'price' => $product->prod_price,
                                                        'instructions' => $medication['instructions'] ?? ''
                                                    ];
                                                    $prescriptionTotal += $product->prod_price;
                                                }
                                            } else {
                                                $prescriptionItems[] = [
                                                    'name' => $medication['product_name'] ?? 'Unknown medication',
                                                    'price' => 0,
                                                    'instructions' => $medication['instructions'] ?? ''
                                                ];
                                            }
                                        }
                                    }
                                }
                                
                                $grandTotal = $servicesTotal + $prescriptionTotal;
                                $billingStatus = strtolower($billing->bill_status ?? 'pending');
                            @endphp
                            
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 border">
                                    {{ $billing->appointment?->pet?->owner?->own_name ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-2 border">
                                    <div>
                                        <div class="font-medium">{{ $billing->appointment?->pet?->pet_name ?? 'N/A' }}</div>
                                        <div class="text-xs text-gray-500">{{ $billing->appointment?->pet?->pet_species ?? '' }}</div>
                                    </div>
                                </td>
                                <td class="px-4 py-2 border">
                                    {{-- Services --}}
                                    @if($billing->appointment && $billing->appointment->services && $billing->appointment->services->count() > 0)
                                        <div class="mb-2">
                                            <div class="font-semibold text-blue-600 text-xs mb-1">SERVICES:</div>
                                            @foreach($billing->appointment->services as $service)
                                                <span class="block text-xs">
                                                    {{ $service->serv_name ?? 'N/A' }} - ₱{{ number_format($service->serv_price ?? 0, 2) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                    
                                    {{-- Prescription Products --}}
                                    @if(count($prescriptionItems) > 0)
                                        <div class="mb-2">
                                            <div class="font-semibold text-green-600 text-xs mb-1">MEDICATIONS:</div>
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
                                    
                                    @if(count($prescriptionItems) == 0 && (!$billing->appointment || !$billing->appointment->services || $billing->appointment->services->count() == 0))
                                        <span class="text-gray-500 text-xs">No items</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 border">
                                    <div class="font-bold text-lg">₱{{ number_format($grandTotal, 2) }}</div>
                                    @if($servicesTotal > 0 && $prescriptionTotal > 0)
                                        <div class="text-xs text-gray-500">
                                            Services: ₱{{ number_format($servicesTotal, 2) }}<br>
                                            Medications: ₱{{ number_format($prescriptionTotal, 2) }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-2 border">
                                    @if($billingStatus === 'paid')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            PAID
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
                                    <div class="flex justify-center items-center gap-1">
                                        <!-- View Button -->
                                        <button onclick="viewBilling(this)" class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 text-xs"
                                            data-bill-id="{{ $billing->bill_id }}"
                                            data-owner="{{ $billing->appointment?->pet?->owner?->own_name ?? 'N/A' }}"
                                            data-pet="{{ $billing->appointment?->pet?->pet_name ?? 'N/A' }}"
                                            data-pet-species="{{ $billing->appointment?->pet?->pet_species ?? 'N/A' }}"
                                            data-pet-breed="{{ $billing->appointment?->pet?->pet_breed ?? 'N/A' }}"
                                            data-date="{{ \Carbon\Carbon::parse($billing->bill_date)->format('F d, Y') }}"
                                            data-services-total="{{ $servicesTotal }}"
                                            data-prescription-total="{{ $prescriptionTotal }}"
                                            data-grand-total="{{ $grandTotal }}"
                                            data-status="{{ $billingStatus }}"
                                            data-services="{{ $billing->appointment && $billing->appointment->services ? $billing->appointment->services->map(function($service) { return $service->serv_name . ' - ₱' . number_format($service->serv_price ?? 0, 2); })->implode('|') : 'No services' }}"
                                            data-prescription-items="{{ json_encode($prescriptionItems) }}"
                                            data-branch-name="{{ $billing->appointment?->branch?->branch_name ?? 'Main Branch' }}"
                                            data-branch-address="{{ $billing->appointment?->branch?->branch_address ?? 'Branch Address' }}"
                                            data-branch-contact="{{ $billing->appointment?->branch?->branch_contactNum ?? 'Contact Number' }}">
                                            <i class="fas fa-eye"></i>
                                        </button>

                                        <!-- Direct Print Button -->
                                        <button onclick="directPrintBilling(this)" class="bg-purple-500 text-white px-2 py-1 rounded hover:bg-purple-600 text-xs"
                                            data-bill-id="{{ $billing->bill_id }}"
                                            data-owner="{{ $billing->appointment?->pet?->owner?->own_name ?? 'N/A' }}"
                                            data-pet="{{ $billing->appointment?->pet?->pet_name ?? 'N/A' }}"
                                            data-pet-species="{{ $billing->appointment?->pet?->pet_species ?? 'N/A' }}"
                                            data-pet-breed="{{ $billing->appointment?->pet?->pet_breed ?? 'N/A' }}"
                                            data-date="{{ \Carbon\Carbon::parse($billing->bill_date)->format('F d, Y') }}"
                                            data-services-total="{{ $servicesTotal }}"
                                            data-prescription-total="{{ $prescriptionTotal }}"
                                            data-grand-total="{{ $grandTotal }}"
                                            data-status="{{ $billingStatus }}"
                                            data-services="{{ $billing->appointment && $billing->appointment->services ? $billing->appointment->services->map(function($service) { return $service->serv_name . ' - ₱' . number_format($service->serv_price ?? 0, 2); })->implode('|') : 'No services' }}"
                                            data-prescription-items="{{ json_encode($prescriptionItems) }}"
                                            data-branch-name="{{ $billing->appointment?->branch?->branch_name ?? 'Main Branch' }}"
                                            data-branch-address="{{ $billing->appointment?->branch?->branch_address ?? 'Branch Address' }}"
                                            data-branch-contact="{{ $billing->appointment?->branch?->branch_contactNum ?? 'Contact Number' }}">
                                            <i class="fas fa-print"></i>
                                        </button>

                                        <!-- Delete Button -->
                                        <form method="POST" action="{{ route('sales.destroyBilling', $billing->bill_id) }}"
                                              onsubmit="return confirm('Are you sure you want to delete this billing?');"
                                              class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 flex items-center gap-1 text-xs">
                                                <i class="fas fa-trash"></i> 
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-2 border text-center text-gray-500">
                                    No billing records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Billing Pagination --}}
            <div class="mt-4">
                {{ $billings->links() }}
            </div>
        </div>

        {{-- Orders Tab Content --}}
        <div id="ordersContent" class="tab-content hidden">
            <div class="flex justify-between items-center mb-4">
                <!--<div class="flex gap-2">
                    <button onclick="exportSales()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                        Export CSV
                    </button>
                    <button onclick="showFilters()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                        Filter
                    </button>
                </div>-->
            </div>

            <!-- Filter Section (Initially Hidden) 
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
            </div>-->

            <!-- Summary Cards 
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-blue-100 p-4 rounded-lg">
                    <h3 class="text-blue-800 font-semibold">Total Sales</h3>
                    <p class="text-2xl font-bold text-blue-900">
                        ₱{{ number_format($totalSales, 2) }}
                    </p>
                </div>
                <div class="bg-green-100 p-4 rounded-lg">
                    <h3 class="text-green-800 font-semibold">Total Transactions</h3>
                    <p class="text-2xl font-bold text-green-900">
                        {{ $totalTransactions }}
                    </p>
                </div>
                <div class="bg-purple-100 p-4 rounded-lg">
                    <h3 class="text-purple-800 font-semibold">Items Sold</h3>
                    <p class="text-2xl font-bold text-purple-900">
                        {{ $totalItemsSold }}
                    </p>
                </div>
                <div class="bg-yellow-100 p-4 rounded-lg">
                    <h3 class="text-yellow-800 font-semibold">Average Sale</h3>
                    <p class="text-2xl font-bold text-yellow-900">
                        ₱{{ number_format($averageSale, 2) }}
                    </p>
                </div>
            </div>-->

            <!-- Sales Table -->
            <div class="overflow-x-auto">
                <table class="table-auto w-full border-collapse border text-sm text-center">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="border px-4 py-2">#</th>
                            <th class="border px-4 py-2">Transaction ID</th>
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
                        @forelse($paginatedTransactions as $transactionId => $orders)
                            @php
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
                                    <button onclick="printTransaction(this)" 
                                            data-id="{{ $transactionId }}" 
                                            class="bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600 text-xs"
                                            title="Print Receipt">
                                        <i class="fas fa-print"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-8 text-gray-500">
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

{{-- Billing View Modal --}}
<div id="viewBillingModal" class="fixed inset-0 bg-black bg-opacity-40 flex justify-center items-center z-50 hidden no-print">
    <div class="bg-white w-full max-w-3xl p-0 rounded-lg shadow-lg relative max-h-[100vh] overflow-y-auto">
        <div id="billingContent" class="billing-container bg-white p-10">
            <div class="header flex items-center justify-between border-b-2 border-black pb-6 mb-6">
                <!-- Left side: Logo -->
                <div class="flex-shrink-0">
                    <img src="{{ asset('images/pets2go.png') }}" alt="Pets2GO Logo" class="w-28 h-28 object-contain">
                </div>
                
                <!-- Right side: Clinic Information -->
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
                <!-- Bill Header -->
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

                <div class="total-section mb-8">
                    <div class="mt-4 pt-4 border-t-2 border-gray-300">
                        <div class="text-right">
                            <div class="text-xl font-bold text-[#0f7ea0]">TOTAL AMOUNT: <span id="viewBillTotal"></span></div>
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
        <button onclick="document.getElementById('viewBillingModal').classList.add('hidden')" 
                class="absolute top-2 right-2 text-gray-500 hover:text-gray-800 text-2xl no-print">&times;</button>
        
        <!-- Print Button inside the modal -->
        <div class="absolute top-2 left-2 no-print">
            <button onclick="printBillingFromModal()" class="bg-purple-500 text-white px-3 py-1 rounded hover:bg-purple-600 text-sm">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>
</div>

{{-- Hidden Print Container for Billing --}}
<div id="printBillingContainer" style="display: none;">
    <div id="printBillingContent" class="billing-container bg-white p-10">
        <!-- Content will be populated by JavaScript -->
    </div>
</div>

<style>
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
// Tab switching functionality
function switchTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Remove active class from all tab buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active', 'border-[#0f7ea0]', 'text-[#0f7ea0]');
        button.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Show selected tab content
    document.getElementById(tabName + 'Content').classList.remove('hidden');
    
    // Add active class to selected tab button
    const activeTab = document.getElementById(tabName + 'Tab');
    activeTab.classList.add('active', 'border-[#0f7ea0]', 'text-[#0f7ea0]');
    activeTab.classList.remove('border-transparent', 'text-gray-500');
}

let currentBillingData = null;

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

function updateBillingContent(targetId, data) {
    const container = document.getElementById(targetId);
    
    // Parse services
    const servicesArray = data.services && data.services !== 'No services' ? data.services.split('|') : [];
    
    // Status display
    const statusBadge = data.status === 'paid' 
        ? '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">PAID</span>'
        : '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">PENDING</span>';
    
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

            <div class="total-section mb-8">
                <div class="mt-4 pt-4 border-t-2 border-gray-300">
                    <div class="text-right">
                        <div class="text-xl font-bold text-[#0f7ea0]">TOTAL AMOUNT: ₱${data.grandTotal.toFixed(2)}</div>
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
    
    // Update modal content
    document.getElementById('viewBillId').innerText = data.billId;
    document.getElementById('viewOwner').innerText = data.owner;
    document.getElementById('viewPet').innerText = data.pet;
    document.getElementById('viewPetSpecies').innerText = data.petSpecies;
    document.getElementById('viewPetBreed').innerText = data.petBreed;
    document.getElementById('viewBillDate').innerText = data.date;
    document.getElementById('viewBillTotal').innerText = `₱${data.grandTotal.toFixed(2)}`;
    document.getElementById('viewServicesTotal').innerText = `₱${data.servicesTotal.toFixed(2)}`;
    document.getElementById('viewPrescriptionTotal').innerText = `₱${data.prescriptionTotal.toFixed(2)}`;
    document.getElementById('viewBranchName').innerText = data.branchName;
    document.getElementById('viewBranchAddress').innerText = data.branchAddress;
    document.getElementById('viewBranchContact').innerText = data.branchContact;

    // Update status badge
    const statusElement = document.getElementById('viewBillStatus');
    if (data.status === 'paid') {
        statusElement.innerHTML = '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800"><i class="fas fa-check-circle mr-1"></i>PAID</span>';
    } else {
        statusElement.innerHTML = '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800"><i class="fas fa-clock mr-1"></i>PENDING</span>';
    }

    // Handle services
    const servicesContainer = document.getElementById('servicesList');
    servicesContainer.innerHTML = '';
    
    if (data.services && data.services !== 'No services') {
        const servicesArray = data.services.split('|');
        servicesArray.forEach((service, index) => {
            const serviceDiv = document.createElement('div');
            serviceDiv.classList.add('service-item');
            serviceDiv.innerHTML = `
                <div class="text-sm font-medium">${index+1}. ${service.trim()}</div>
            `;
            servicesContainer.appendChild(serviceDiv);
        });
    } else {
        const serviceDiv = document.createElement('div');
        serviceDiv.classList.add('service-item', 'text-gray-500');
        serviceDiv.innerHTML = 'No services provided';
        servicesContainer.appendChild(serviceDiv);
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
        const medicationDiv = document.createElement('div');
        medicationDiv.classList.add('medication-item', 'text-gray-500');
        medicationDiv.innerHTML = 'No medications provided';
        medicationsContainer.appendChild(medicationDiv);
    }

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

// Orders Tab Functions
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
    window.location.href = '/sales/transaction/' + transactionId;
}

// Add event listener for after print to clean up
window.addEventListener('afterprint', function() {
    console.log('Print dialog closed');
});
</script>
@endsection