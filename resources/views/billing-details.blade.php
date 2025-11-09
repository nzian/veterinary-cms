@extends('AdminBoard')

@section('content')
@php
    // Calculate everything FIRST before using in the view
    $servicesTotal = 0;
    if ($billing->visit && $billing->visit->services) {
        $servicesTotal = $billing->visit->services->sum('serv_price');
    }

    // Calculate prescription total and items
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
                }
            }
        }
    }
    
    $grandTotal = $servicesTotal + $prescriptionTotal;
    $totalItems = ($billing->visit && $billing->visit->services ? $billing->visit->services->count() : 0) + count($prescriptionItems);
    $billingStatus = strtolower($billing->bill_status ?? 'pending');
@endphp
<div class="min-h-screen px-2 sm:px-4 md:px-6 py-4">
    <div class="max-w-6xl mx-auto bg-white p-4 sm:p-6 rounded-lg shadow">
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 md:gap-0">
            <div>
                <h2 class="text-[#0f7ea0] font-bold text-xl sm:text-2xl">Billing Details</h2>
                <p class="text-gray-600 text-sm">Bill ID: #{{ $billing->bill_id }}</p>
                <p class="text-xs sm:text-sm text-gray-500">
                    {{ $totalItems }} item(s) | Total: ₱{{ number_format($grandTotal, 2) }}
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('sales.index') }}" 
                   onclick="sessionStorage.setItem('activeTab', 'billing'); return true;"
                   class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-2 rounded text-xs sm:text-sm">
                   <i class="fa-solid fa-arrow-left"></i>
                </a>
                <button onclick="printBilling()" class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-2 rounded text-xs sm:text-sm">
                    <i class="fas fa-print mr-1"></i>
                </button>
                @if($billingStatus !== 'paid')
                    <button type="button" id="openPayModal" class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded text-xs sm:text-sm">
                        <i class="fas fa-cash-register mr-1"></i> Pay
                    </button>
                    <form id="payForm" method="POST" action="{{ route('sales.markAsPaid', $billing->bill_id) }}" class="hidden">
                        @csrf
                        <input type="hidden" name="cash_amount" id="cash_amount_field" value="0">
                    </form>
                @else
                    <a href="{{ route('sales.billing.receipt', $billing->bill_id) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded text-xs sm:text-sm">
                        <i class="fas fa-receipt mr-1"></i> Receipt
                    </a>
                @endif
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-100 text-green-700 px-3 py-2 mb-4 rounded text-sm">
                {{ session('success') }}
            </div>
        @endif

        @php
            // Calculate services total
            $servicesTotal = 0;
            if ($billing->visit && $billing->visit->services) {
                $servicesTotal = $billing->visit->services->sum('serv_price');
            }

            // Calculate prescription total and items
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
                        }
                    }
                }
            }
            
            $grandTotal = $servicesTotal + $prescriptionTotal;
            $totalItems = ($billing->visit && $billing->visit->services ? $billing->visit->services->count() : 0) + count($prescriptionItems);
            $billingStatus = strtolower($billing->bill_status ?? 'pending');
        @endphp

        <!-- Billing & Customer Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <!-- Billing Details -->
            <div class="bg-gray-50 p-3 sm:p-4 rounded-lg border">
                <h3 class="font-semibold text-gray-800 text-sm sm:text-lg mb-2">Billing Information</h3>
                <div class="space-y-1 text-xs sm:text-sm">
                    <div class="flex justify-between"><span class="text-gray-600">Bill ID:</span><span class="font-medium font-mono">#{{ $billing->bill_id }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-600">Date & Time:</span><span class="font-medium">{{ \Carbon\Carbon::parse($billing->bill_date)->format('M d, Y h:i A') }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-600">Total Items:</span><span class="font-medium">{{ $totalItems }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-600">Payment Status:</span>
                        <span class="font-medium">
                            @if($billingStatus === 'paid')
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-check-circle mr-1"></i>PAID
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <i class="fas fa-clock mr-1"></i>PENDING
                                </span>
                            @endif
                        </span>
                    </div>
                    <div class="flex justify-between border-t pt-1 mt-1"><span class="text-gray-600 font-semibold">Total Amount:</span><span class="font-bold text-green-600">₱{{ number_format($grandTotal, 2) }}</span></div>
                </div>
            </div>

            <!-- Customer & Pet Info -->
            <div class="bg-gray-50 p-3 sm:p-4 rounded-lg border">
                <h3 class="font-semibold text-gray-800 text-sm sm:text-lg mb-2">Customer & Pet</h3>
                <div class="space-y-1 text-xs sm:text-sm">
                    <div class="flex justify-between"><span class="text-gray-600">Owner:</span>
                        <span class="font-medium">{{ $billing->visit?->pet?->owner?->own_name ?? 'N/A' }}</span>
                    </div>
                    @if($billing->visit?->pet?->owner?->own_email)
                        <div class="flex justify-between"><span class="text-gray-600">Email:</span><span class="font-medium">{{ $billing->visit->pet->owner->own_email }}</span></div>
                    @endif
                    @if($billing->visit?->pet?->owner?->own_phone)
                        <div class="flex justify-between"><span class="text-gray-600">Phone:</span><span class="font-medium">{{ $billing->visit->pet->owner->own_phone }}</span></div>
                    @endif
                    <div class="flex justify-between border-t pt-1 mt-1"><span class="text-gray-600">Pet Name:</span><span class="font-medium">{{ $billing->visit?->pet?->pet_name ?? 'N/A' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-600">Species:</span><span class="font-medium">{{ $billing->visit?->pet?->pet_species ?? 'N/A' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-600">Breed:</span><span class="font-medium">{{ $billing->visit?->pet?->pet_breed ?? 'N/A' }}</span></div>
                </div>
            </div>
        </div>

        <!-- Services Table -->
        @if($billing->appointment && $billing->appointment->services && $billing->appointment->services->count() > 0)
        <div class="bg-white border rounded-lg overflow-x-auto mb-6">
            <div class="px-4 py-2 bg-blue-50 border-b">
                <h3 class="font-semibold text-blue-800 text-sm sm:text-lg">Services Provided</h3>
            </div>
            <table class="w-full min-w-[600px] text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="text-left px-3 py-2 font-medium text-gray-700">#</th>
                        <th class="text-left px-3 py-2 font-medium text-gray-700">Service Name</th>
                        <th class="text-left px-3 py-2 font-medium text-gray-700">Description</th>
                        <th class="text-right px-3 py-2 font-medium text-gray-700">Price</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($billing->appointment->services as $service)
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="px-3 py-2">{{ $loop->iteration }}</td>
                        <td class="px-3 py-2">
                            <p class="font-medium text-gray-800">{{ $service->serv_name ?? 'Service Not Found' }}</p>
                        </td>
                        <td class="px-3 py-2">
                            <p class="text-xs text-gray-500">{{ $service->serv_description ?? 'N/A' }}</p>
                        </td>
                        <td class="px-3 py-2 text-right font-semibold">₱{{ number_format($service->serv_price ?? 0, 2) }}</td>
                    </tr>
                    @endforeach
                    <tr class="bg-blue-50 border-t-2 border-blue-200">
                        <td colspan="3" class="px-3 py-3 text-right font-semibold text-gray-800">Services Subtotal:</td>
                        <td class="px-3 py-3 text-right font-bold text-blue-600">₱{{ number_format($servicesTotal, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        @endif

        <!-- Medications Table -->
        @if(count($prescriptionItems) > 0)
        <div class="bg-white border rounded-lg overflow-x-auto mb-6">
            <div class="px-4 py-2 bg-green-50 border-b">
                <h3 class="font-semibold text-green-800 text-sm sm:text-lg">Medications Provided</h3>
            </div>
            <table class="w-full min-w-[600px] text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="text-left px-3 py-2 font-medium text-gray-700">#</th>
                        <th class="text-left px-3 py-2 font-medium text-gray-700">Medication</th>
                        <th class="text-left px-3 py-2 font-medium text-gray-700">Instructions</th>
                        <th class="text-right px-3 py-2 font-medium text-gray-700">Price</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($prescriptionItems as $item)
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="px-3 py-2">{{ $loop->iteration }}</td>
                        <td class="px-3 py-2">
                            <p class="font-medium text-gray-800">{{ $item['name'] }}</p>
                        </td>
                        <td class="px-3 py-2">
                            <p class="text-xs text-gray-500 italic">{{ $item['instructions'] ?: 'No instructions provided' }}</p>
                        </td>
                        <td class="px-3 py-2 text-right font-semibold">
                            @if($item['price'] > 0)
                                ₱{{ number_format($item['price'], 2) }}
                            @else
                                <span class="text-gray-400">N/A</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                    <tr class="bg-green-50 border-t-2 border-green-200">
                        <td colspan="3" class="px-3 py-3 text-right font-semibold text-gray-800">Medications Subtotal:</td>
                        <td class="px-3 py-3 text-right font-bold text-green-600">₱{{ number_format($prescriptionTotal, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        @endif

        <!-- Billing Summary -->
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 sm:p-6">
            <h3 class="font-semibold text-green-800 text-sm sm:text-lg mb-3">Billing Summary</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-center text-xs sm:text-sm">
                <div><p class="text-lg sm:text-2xl font-bold text-green-900">{{ $totalItems }}</p><p class="text-green-600">Total Items</p></div>
                <div><p class="text-lg sm:text-2xl font-bold text-green-900">₱{{ number_format($servicesTotal, 2) }}</p><p class="text-green-600">Services</p></div>
                <div><p class="text-lg sm:text-2xl font-bold text-green-900">₱{{ number_format($prescriptionTotal, 2) }}</p><p class="text-green-600">Medications</p></div>
            </div>
            <div class="mt-4 pt-4 border-t-2 border-green-300">
                <div class="text-center">
                    <p class="text-xl sm:text-3xl font-bold text-green-900">₱{{ number_format($grandTotal, 2) }}</p>
                    <p class="text-green-600 text-sm">Grand Total</p>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-green-200 text-center text-green-700 text-xs sm:text-sm">
                <i class="fas fa-calendar-alt mr-1"></i>
                {{ \Carbon\Carbon::parse($billing->bill_date)->format('l, F j, Y \a\t g:i A') }}
            </div>
        </div>
    </div>
</div>

<!-- POS-style Payment Modal -->
<div id="billingPayModal" class="fixed inset-0 flex items-center justify-center hidden z-50 bg-black bg-opacity-30">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl">
        <div class="text-center mb-4">
            <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-money-bill-wave text-white text-xl"></i>
            </div>
            <h2 class="text-xl font-bold text-gray-800">Process Billing Payment</h2>
            <p class="text-gray-500 text-sm">Enter the cash amount received</p>
        </div>

        <div class="space-y-3">
            <div class="bg-blue-50 rounded-xl p-3 border border-blue-200">
                <div class="text-sm text-gray-600 mb-2 font-semibold">Items</div>
                <div class="max-h-28 overflow-y-auto space-y-1">
                    @forelse($billing->orders as $o)
                        <div class="flex justify-between text-sm">
                            <span>{{ $o->product->prod_name ?? 'Item' }} x{{ $o->ord_quantity ?? 1 }}</span>
                            @php $line = ($o->ord_price ?? optional($o->product)->prod_price ?? 0) * ($o->ord_quantity ?? 1); @endphp
                            <span class="font-semibold">₱{{ number_format($line, 2) }}</span>
                        </div>
                    @empty
                        <div class="text-center text-gray-500 text-sm">No items</div>
                    @endforelse
                </div>
                <div class="border-t border-blue-200 mt-2 pt-2 flex justify-between font-bold">
                    <span>Total</span>
                    @php $ordersTotal = $billing->orders->sum(fn($o) => ($o->ord_price ?? optional($o->product)->prod_price ?? 0) * ($o->ord_quantity ?? 1)); @endphp
                    <span id="billingTotal">₱{{ number_format($ordersTotal, 2) }}</span>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Cash Amount Received</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">₱</span>
                    <input type="number" id="billingCashInput" min="0" step="0.01"
                        class="w-full pl-8 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent text-lg font-semibold" />
                </div>
            </div>

            <div class="bg-green-50 rounded-xl p-3 border-2 border-green-200">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Change</label>
                <div id="billingChange" class="text-2xl font-bold text-green-600">₱0.00</div>
            </div>
        </div>

        <div class="flex gap-3 mt-6">
            <button type="button" id="cancelBillingPay" class="flex-1 px-4 py-2 bg-gray-500 text-white rounded-xl font-semibold">Cancel</button>
            <button type="button" id="confirmBillingPay" class="flex-1 px-4 py-2 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl font-semibold">Confirm Payment</button>
        </div>
    </div>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
    (function(){
        const openBtn = document.getElementById('openPayModal');
        const modal = document.getElementById('billingPayModal');
        const cancelBtn = document.getElementById('cancelBillingPay');
        const confirmBtn = document.getElementById('confirmBillingPay');
        const cashInput = document.getElementById('billingCashInput');
        const changeEl = document.getElementById('billingChange');
        const totalText = document.getElementById('billingTotal');
        const payForm = document.getElementById('payForm');

        if (!openBtn) return;

        const parsePeso = (txt) => parseFloat((txt||'').replace('₱','').replace(/,/g,'')) || 0;
        const total = parsePeso(totalText?.textContent || '0');

        openBtn.addEventListener('click', ()=>{
            modal.classList.remove('hidden');
            cashInput.value = '';
            changeEl.textContent = '₱0.00';
            changeEl.className = 'text-2xl font-bold text-green-600';
            setTimeout(()=> cashInput.focus(), 150);
        });

        cancelBtn.addEventListener('click', ()=>{
            modal.classList.add('hidden');
        });

        cashInput.addEventListener('input', ()=>{
            const cash = parseFloat(cashInput.value || '0');
            const change = cash - total;
            if (cash >= total && total > 0) {
                changeEl.textContent = `₱${change.toFixed(2)}`;
                changeEl.className = 'text-2xl font-bold text-green-600';
                confirmBtn.disabled = false;
            } else {
                changeEl.textContent = cash > 0 ? `₱${change.toFixed(2)}` : '₱0.00';
                changeEl.className = 'text-2xl font-bold text-red-500';
                confirmBtn.disabled = true;
            }
        });

        let paying = false;
        confirmBtn.addEventListener('click', ()=>{
            if (paying) return;
            const cash = parseFloat(cashInput.value || '0');
            if (cash < total) { cashInput.focus(); return; }
            paying = true; confirmBtn.disabled = true; cancelBtn.disabled = true;
            // Submit to mark as paid (server handles visit completion and redirect)
            const cashField = document.getElementById('cash_amount_field');
            if (cashField) cashField.value = cash.toFixed(2);
            payForm.submit();
        });
    })();
    </script>
</div>

<!-- Hidden Print Container -->
<div id="printContainer" style="display: none;">
    <div id="printContent" class="billing-container bg-white p-10">
        <!-- Content will be populated by JavaScript -->
    </div>
</div>

<!-- Print Billing Styles -->
<style>
.billing-container {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    border: 1px solid #000;
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

@media print {
    body * {
        visibility: hidden;
    }
    #printContainer,
    #printContainer *,
    #printContent,
    #printContent * {
        visibility: visible !important;
    }
    #printContainer {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        display: block !important;
    }
    .no-print {
        display: none !important;
    }
    
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
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
function printBilling() {
    const servicesContent = `
        @if($billing->appointment && $billing->appointment->services && $billing->appointment->services->count() > 0)
            @foreach($billing->appointment->services as $service)
            <div class="service-item">
                <div class="text-sm font-medium">{{ $loop->iteration }}. {{ $service->serv_name ?? 'Service' }} - ₱{{ number_format($service->serv_price ?? 0, 2) }}</div>
            </div>
            @endforeach
        @else
            <div class="service-item text-gray-500">No services provided</div>
        @endif
    `;

    const medicationsContent = `
        @if(count($prescriptionItems) > 0)
            @foreach($prescriptionItems as $item)
            <div class="medication-item">
                <div class="text-sm font-medium">{{ $loop->iteration }}. {{ $item['name'] }}</div>
                @if($item['price'] > 0)
                    <div class="text-xs text-gray-600 ml-4">₱{{ number_format($item['price'], 2) }}</div>
                @endif
                @if($item['instructions'])
                    <div class="text-xs text-gray-500 ml-4 italic">{{ $item['instructions'] }}</div>
                @endif
            </div>
            @endforeach
        @else
            <div class="medication-item text-gray-500">No medications provided</div>
        @endif
    `;

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
                    {{ $billing->appointment?->branch?->branch_name ?? 'MAIN BRANCH' }}
                </div>
                <div class="clinic-details text-sm text-gray-700 mt-1 text-center leading-tight">
                    <div>Address: {{ $billing->appointment?->branch?->branch_address ?? 'Branch Address' }}</div>
                    <div>Contact No: {{ $billing->appointment?->branch?->branch_contactNum ?? 'Contact Number' }}</div>
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
                        <div class="mb-2"><strong>DATE:</strong> {{ \Carbon\Carbon::parse($billing->bill_date)->format('F d, Y') }}</div>
                        <div class="mb-2"><strong>OWNER:</strong> {{ $billing->appointment?->pet?->owner?->own_name ?? 'N/A' }}</div>
                        <div class="mb-2"><strong>PET NAME:</strong> {{ $billing->appointment?->pet?->pet_name ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="mb-2"><strong>BILL ID:</strong> {{ $billing->bill_id }}</div>
                        <div class="mb-2"><strong>PET SPECIES:</strong> {{ $billing->appointment?->pet?->pet_species ?? 'N/A' }}</div>
                        <div class="mb-2"><strong>PET BREED:</strong> {{ $billing->appointment?->pet?->pet_breed ?? 'N/A' }}</div>
                        <div class="mb-2"><strong>STATUS:</strong> 
                            @if($billingStatus === 'paid')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">PAID</span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">PENDING</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="services-section mb-6">
                <div class="section-title text-base font-bold mb-4 border-b pb-2 text-blue-600">SERVICES PROVIDED</div>
                <div class="space-y-2">
                    ${servicesContent}
                </div>
                <div class="subtotal-section mt-4 pt-2 border-t border-gray-200">
                    <div class="text-right text-sm">
                        <div><strong>Services Subtotal: ₱{{ number_format($servicesTotal, 2) }}</strong></div>
                    </div>
                </div>
            </div>

            <div class="medications-section mb-6">
                <div class="section-title text-base font-bold mb-4 border-b pb-2 text-green-600">MEDICATIONS PROVIDED</div>
                <div class="space-y-2">
                    ${medicationsContent}
                </div>
                <div class="subtotal-section mt-4 pt-2 border-t border-gray-200">
                    <div class="text-right text-sm">
                        <div><strong>Medications Subtotal: ₱{{ number_format($prescriptionTotal, 2) }}</strong></div>
                    </div>
                </div>
            </div>

            <div class="total-section mb-8">
                <div class="mt-4 pt-4 border-t-2 border-gray-300">
                    <div class="text-right">
                        <div class="text-xl font-bold text-[#0f7ea0]">TOTAL AMOUNT: ₱{{ number_format($grandTotal, 2) }}</div>
                    </div>
                </div>
            </div>

            <div class="footer text-center pt-8 border-t-2 border-black">
                <div class="thank-you text-sm">
                    <div class="font-bold mb-2">Thank you for choosing Pets2GO Veterinary Clinic!</div>
                    <div class="text-gray-600">Your pet's health is our priority</div>
                    <div class="mt-2">{{ \Carbon\Carbon::now()->format('F d, Y h:i A') }}</div>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('printContent').innerHTML = printContent;
    document.getElementById('printContainer').style.display = 'block';
    
    setTimeout(() => {
        window.print();
        document.getElementById('printContainer').style.display = 'none';
    }, 200);
}

// Auto-hide success message after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const successMessage = document.querySelector('.bg-green-100');
    if (successMessage) {
        setTimeout(() => {
            successMessage.style.transition = 'opacity 0.5s';
            successMessage.style.opacity = '0';
            setTimeout(() => successMessage.remove(), 500);
        }, 5000);
    }
});
</script>
@endsection