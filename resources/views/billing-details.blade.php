@extends('AdminBoard')

@section('content')
@push('styles')
<style>
    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    .product-card {
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        padding: 1rem;
        cursor: pointer;
        transition: all 0.2s;
    }
    .product-card:hover {
        border-color: #4299e1;
        box-shadow: 0 0 0 1px #4299e1;
    }
    .product-card.selected {
        border-color: #48bb78;
        background-color: #f0fff4;
    }
    .product-card .price {
        color: #2d3748;
        font-weight: bold;
    }
    .product-card .stock {
        font-size: 0.875rem;
        color: #718096;
    }
    #selectedProducts {
        margin-top: 1.5rem;
    }
    .selected-product {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem;
        border-bottom: 1px solid #e2e8f0;
    }
    .selected-product:last-child {
        border-bottom: none;
    }
</style>
@endpush
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
<div class="min-h-screen px-2 sm:px-4 md:px-6 py-4" x-data="billingApp()" x-init="init()">
    <!-- Product Selection Modal -->
    <div x-show="showProductModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[80vh] flex flex-col">
            <div class="p-4 border-b flex justify-between items-center">
                <h3 class="text-lg font-semibold">Add Products to Billing</h3>
                <button @click="showProductModal = false" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-4 overflow-y-auto flex-grow">
                <div class="mb-4">
                    <input type="text" x-model="productSearch" placeholder="Search products..." 
                           class="w-full p-2 border rounded-md" @input="searchProducts">
                </div>
                
                <div x-show="loading" class="text-center py-4">
                    <i class="fas fa-spinner fa-spin text-blue-500"></i> Loading products...
                </div>
                
                <div x-show="!loading && products.length === 0" class="text-center py-4 text-gray-500">
                    No products found. Try a different search term.
                </div>
                
                <div class="product-grid" x-show="!loading && products.length > 0">
                    <template x-for="product in products" :key="product.prod_id">
                        <div class="product-card" 
                             :class="{ 'selected': isProductSelected(product.prod_id) }"
                             @click="toggleProduct(product)">
                            <div class="font-medium" x-text="product.prod_name"></div>
                            <div class="price" x-text="'₱' + product.prod_price.toFixed(2)"></div>
                            <div class="stock" x-text="'Stock: ' + product.prod_stocks"></div>
                        </div>
                    </template>
                </div>
            </div>
            <div class="p-4 border-t flex justify-end space-x-2">
                <button @click="showProductModal = false" 
                        class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-100">
                    Cancel
                </button>
                <button @click="addSelectedProducts" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                        :disabled="selectedProducts.length === 0">
                    Add Selected Products
                </button>
            </div>
        </div>
    </div>
    <div class="max-w-6xl mx-auto bg-white p-4 sm:p-6 rounded-lg shadow">
        <!-- Add Products Button -->
        <div class="mb-6 flex justify-between items-center">
            <h2 class="text-xl font-bold text-gray-800">Billing Details</h2>
            <button @click="showProductModal = true" 
                    class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 flex items-center">
                <i class="fas fa-plus-circle mr-2"></i> Add Products
            </button>
        </div>
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
                        <input type="hidden" name="payment_type" id="payment_type_field" value="full">
                    </form>
                    @php
                        // Check if there is a pending partial placeholder created for boarding
                        $pendingPartial = $billing->payments()->where('payment_type', 'partial')->where('status', 'pending')->first();
                    @endphp
                    @if($pendingPartial)
                        <button type="button" id="payPartialBtn" class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-2 rounded text-xs sm:text-sm" title="Pay 50% partial">Pay Partial (50%)</button>
                    @endif
                @else
                    <button onclick="openReceiptPopup({{ $billing->bill_id }})" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded text-xs sm:text-sm">
                        <i class="fas fa-receipt mr-1"></i> Receipt
                    </button>
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

            // Calculate prescription total and items - Only include available products with valid prices
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
                            // Only include products that are active, in stock, and have a valid price
                            $product = \DB::table('tbl_prod')
                                ->where('prod_id', $medication['product_id'])
                                ->where('prod_status', 'active')
                                ->where('prod_stock', '>', 0)
                                ->where('prod_price', '>', 0)
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
            const paymentTypeField = document.getElementById('payment_type_field');
            // Only set to full if not intentionally set to partial by a pre-fill
            if (paymentTypeField && paymentTypeField.value !== 'partial') paymentTypeField.value = 'full';
            payForm.submit();
        });

        // Pay Partial button handler: open the same modal pre-filled with placeholder amount
        const payPartialBtn = document.getElementById('payPartialBtn');
        if (payPartialBtn) {
            payPartialBtn.addEventListener('click', ()=>{
                const pendingAmount = {{ $pendingPartial ? ($pendingPartial->pay_total + 0) : 0 }};
                // Open modal and pre-fill cash input
                modal.classList.remove('hidden');
                cashInput.value = parseFloat(pendingAmount).toFixed(2);
                // Trigger input event to update change and enable confirm
                cashInput.dispatchEvent(new Event('input'));
                const paymentTypeField = document.getElementById('payment_type_field');
                if (paymentTypeField) paymentTypeField.value = 'partial';
                // focus confirm button to allow user to confirm
                setTimeout(()=> confirmBtn.focus(), 150);
            });
        }
    })();
    </script>
</div>

<!-- Hidden Billing Print Container -->
<div id="billingPrintContainer" style="display: none;">
    <div id="billingPrintContent" class="billing-container bg-white p-10">
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
    #billingPrintContainer,
    #billingPrintContainer *,
    #billingPrintContent,
    #billingPrintContent * {
        visibility: visible !important;
    }
    #billingPrintContainer {
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

function printBilling() {
    const logoUrl = '{{ asset("images/pets2go.png") }}';
    
    // Services content (pre-rendered by Blade)
    const servicesContent = `@if($billing->appointment && $billing->appointment->services && $billing->appointment->services->count() > 0)@foreach($billing->appointment->services as $service)<div class="service-item"><div class="text-sm font-medium">{{ $loop->iteration }}. {{ $service->serv_name ?? 'Service' }} - ₱{{ number_format($service->serv_price ?? 0, 2) }}</div></div>@endforeach @else<div class="service-item text-gray-500">No services provided</div>@endif`;

    // Medications content (pre-rendered by Blade)
    const medicationsContent = `@if(count($prescriptionItems) > 0)@foreach($prescriptionItems as $item)<div class="medication-item"><div class="text-sm font-medium">{{ $loop->iteration }}. {{ $item['name'] }}</div>@if($item['price'] > 0)<div class="text-xs text-gray-600 ml-4">₱{{ number_format($item['price'], 2) }}</div>@endif @if($item['instructions'])<div class="text-xs text-gray-500 ml-4 italic">{{ $item['instructions'] }}</div>@endif</div>@endforeach @else<div class="medication-item text-gray-500">No medications provided</div>@endif`;

    const billId = '{{ $billing->bill_id }}';
    const branchName = '{{ $billing->appointment?->branch?->branch_name ?? "MAIN BRANCH" }}';
    const branchAddress = '{{ $billing->appointment?->branch?->branch_address ?? "Branch Address" }}';
    const branchContact = '{{ $billing->appointment?->branch?->branch_contactNum ?? "Contact Number" }}';
    const billDate = '{{ \Carbon\Carbon::parse($billing->bill_date)->format("F d, Y") }}';
    const ownerName = '{{ $billing->appointment?->pet?->owner?->own_name ?? "N/A" }}';
    const petName = '{{ $billing->appointment?->pet?->pet_name ?? "N/A" }}';
    const petSpecies = '{{ $billing->appointment?->pet?->pet_species ?? "N/A" }}';
    const petBreed = '{{ $billing->appointment?->pet?->pet_breed ?? "N/A" }}';
    const statusBadge = '@if($billingStatus === "paid")<span class="status-badge status-paid">PAID</span>@else<span class="status-badge status-pending">PENDING</span>@endif';
    const servicesTotal = '{{ number_format($servicesTotal, 2) }}';
    const prescriptionTotal = '{{ number_format($prescriptionTotal, 2) }}';
    const grandTotal = '{{ number_format($grandTotal, 2) }}';
    const currentDateTime = '{{ \Carbon\Carbon::now()->format("F d, Y h:i A") }}';

    const billingHTML = '<!DOCTYPE html><html><head><title>Billing Statement #' + billId + '</title>' +
'<style>* { margin: 0; padding: 0; box-sizing: border-box; }' +
'body { font-family: Arial, sans-serif; padding: 20px; background: white; }' +
'.container { max-width: 900px; margin: 0 auto; padding: 30px; background: white; }' +
'.header { display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid black; padding-bottom: 20px; margin-bottom: 20px; }' +
'.header img { width: 7rem; height: 7rem; object-fit: contain; }' +
'.header .clinic-info { text-align: center; flex-grow: 1; }' +
'.clinic-name { font-size: 24px; font-weight: bold; color: #a86520; letter-spacing: 1px; }' +
'.branch-name { font-size: 18px; font-weight: bold; text-decoration: underline; margin-top: 5px; }' +
'.clinic-details { font-size: 14px; color: #333; margin-top: 5px; }' +
'h2 { text-align: center; font-size: 20px; margin-bottom: 20px; color: #333; }' +
'.customer-info { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; font-size: 14px; }' +
'.customer-info div { margin-bottom: 8px; }' +
'.section-title { font-size: 16px; font-weight: bold; border-bottom: 1px solid #333; padding-bottom: 8px; margin: 20px 0 15px 0; }' +
'.service-item, .medication-item { padding: 8px 0; }' +
'.subtotal-section { margin-top: 15px; padding-top: 10px; border-top: 1px solid #ddd; text-align: right; }' +
'.total-section { margin-top: 20px; padding-top: 15px; border-top: 2px solid #333; text-align: right; }' +
'.total-section .total { font-size: 20px; font-weight: bold; color: #0f7ea0; }' +
'.footer { text-align: center; padding-top: 20px; border-top: 2px solid black; margin-top: 30px; font-size: 14px; }' +
'.status-badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 500; }' +
'.status-paid { background: #dcfce7; color: #166534; }' +
'.status-pending { background: #fee2e2; color: #991b1b; }' +
'.buttons { text-align: center; margin-top: 30px; }' +
'.btn { padding: 12px 30px; margin: 0 10px; font-size: 16px; cursor: pointer; border: none; border-radius: 5px; }' +
'.btn-print { background: #0f7ea0; color: white; }' +
'.btn-close { background: #6b7280; color: white; }' +
'.btn:hover { opacity: 0.9; }' +
'@media print { .buttons { display: none; } body { padding: 0; } }' +
'</style></head><body>' +
'<div class="container">' +
'<div class="header">' +
'<img src="' + logoUrl + '" alt="Pets2GO Logo">' +
'<div class="clinic-info">' +
'<div class="clinic-name">PETS 2GO VETERINARY CLINIC</div>' +
'<div class="branch-name">' + branchName + '</div>' +
'<div class="clinic-details">' +
'<div>Address: ' + branchAddress + '</div>' +
'<div>Contact No: ' + branchContact + '</div>' +
'</div></div></div>' +
'<h2>BILLING STATEMENT</h2>' +
'<div class="customer-info"><div>' +
'<div><strong>DATE:</strong> ' + billDate + '</div>' +
'<div><strong>OWNER:</strong> ' + ownerName + '</div>' +
'<div><strong>PET NAME:</strong> ' + petName + '</div>' +
'</div><div>' +
'<div><strong>BILL ID:</strong> ' + billId + '</div>' +
'<div><strong>PET SPECIES:</strong> ' + petSpecies + '</div>' +
'<div><strong>PET BREED:</strong> ' + petBreed + '</div>' +
'<div><strong>STATUS:</strong> ' + statusBadge + '</div>' +
'</div></div>' +
'<div class="section-title" style="color: #2563eb;">SERVICES PROVIDED</div>' +
'<div>' + servicesContent + '</div>' +
'<div class="subtotal-section"><strong>Services Subtotal: ₱' + servicesTotal + '</strong></div>' +
'<div class="section-title" style="color: #16a34a;">MEDICATIONS PROVIDED</div>' +
'<div>' + medicationsContent + '</div>' +
'<div class="subtotal-section"><strong>Medications Subtotal: ₱' + prescriptionTotal + '</strong></div>' +
'<div class="total-section"><div class="total">TOTAL AMOUNT: ₱' + grandTotal + '</div></div>' +
'<div class="footer">' +
'<div style="font-weight: bold; margin-bottom: 8px;">Thank you for choosing Pets2GO Veterinary Clinic!</div>' +
'<div style="color: #666;">Your pet\'s health is our priority</div>' +
'<div style="margin-top: 8px;">' + currentDateTime + '</div>' +
'</div>' +
'<div class="buttons">' +
'<button class="btn btn-print" onclick="printStatement()">Print</button>' +
'<button class="btn btn-close" onclick="closeWindow()">Close</button>' +
'</div></div>' +
'<script>' +
'function printStatement() { window.print(); }' +
'function closeWindow() { window.close(); }' +
'document.addEventListener("keydown", function(e) {' +
'if (e.ctrlKey && e.key === "p") { e.preventDefault(); printStatement(); }' +
'if (e.key === "Escape") { closeWindow(); }' +
'});' +
'</script></body></html>';

    const printWindow = window.open('', '_blank', 'width=950,height=900');
    printWindow.document.write(billingHTML);
    printWindow.document.close();
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

// Alpine.js component for billing functionality
function billingApp() {
    return {
        showProductModal: false,
        products: [],
        selectedProducts: [],
        productSearch: '',
        loading: false,
        
        init() {
            // Initialize any required data
            this.loadProducts();
        },
        
        loadProducts() {
            this.loading = true;
            fetch('{{ route("sales.products.available") }}')
                .then(response => response.json())
                .then(data => {
                    this.products = data.products || [];
                    this.loading = false;
                })
                .catch(error => {
                    console.error('Error loading products:', error);
                    this.loading = false;
                });
        },
        
        searchProducts: _.debounce(function() {
            if (this.productSearch.length < 2) {
                this.loadProducts();
                return;
            }
            
            this.loading = true;
            fetch(`{{ route("sales.products.available") }}?search=${encodeURIComponent(this.productSearch)}`)
                .then(response => response.json())
                .then(data => {
                    this.products = data.products || [];
                    this.loading = false;
                })
                .catch(error => {
                    console.error('Error searching products:', error);
                    this.loading = false;
                });
        }, 300),
        
        isProductSelected(productId) {
            return this.selectedProducts.some(p => p.prod_id === productId);
        },
        
        toggleProduct(product) {
            const index = this.selectedProducts.findIndex(p => p.prod_id === product.prod_id);
            if (index === -1) {
                // Add product with default quantity of 1
                this.selectedProducts.push({
                    ...product,
                    quantity: 1,
                    price: product.prod_price
                });
            } else {
                // Remove product
                this.selectedProducts.splice(index, 1);
            }
        },
        
        updateQuantity(productId, value) {
            const product = this.selectedProducts.find(p => p.prod_id === productId);
            if (product) {
                product.quantity = Math.max(1, parseInt(value) || 1);
            }
        },
        
        removeProduct(index) {
            this.selectedProducts.splice(index, 1);
        },
        
        addSelectedProducts() {
            if (this.selectedProducts.length === 0) return;
            
            const productsToAdd = this.selectedProducts.map(product => ({
                product_id: product.prod_id,
                quantity: product.quantity,
                price: product.price
            }));
            
            fetch(`/sales/billing/{{ $billing->bill_id }}/add-product`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ products: productsToAdd })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed to add products');
                }
            })
            .catch(error => {
                console.error('Error adding products:', error);
                alert('An error occurred while adding products');
            });
        },
        
        formatPrice(price) {
            return '₱' + parseFloat(price || 0).toFixed(2);
        },
        
        get totalSelected() {
            return this.selectedProducts.reduce((sum, product) => {
                return sum + (product.price * product.quantity);
            }, 0);
        }
    };
}
</script>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js"></script>
@endpush

@endsection