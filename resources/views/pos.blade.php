@extends('AdminBoard')

@section('content')
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        /* Modern animations and effects */
        .fade-in { animation: fadeIn 0.3s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .slide-in { animation: slideIn 0.5s ease-out; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }

        /* Glassmorphism */
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        /* Buttons */
        .modern-btn {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        .modern-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15); }
        .modern-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        .modern-btn:hover::before { left: 100%; }

        /* Product grid */
        .product-card, .billing-card {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .product-card:hover, .billing-card:hover { 
            transform: translateY(-4px) scale(1.02); 
            box-shadow: 0 15px 35px rgba(15, 126, 160, 0.2); 
        }
        .product-card::after, .billing-card::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(135deg, rgba(15, 126, 160, 0.1), rgba(15, 126, 160, 0.05));
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .product-card:hover::after, .billing-card:hover::after { opacity: 1; }

        /* Tab styles */
        .tab-button {
            transition: all 0.3s ease;
            position: relative;
        }
        .tab-button.active {
            color: #ff8c42;
            background: rgba(232, 187, 55, 0.1);
        }
        .tab-button.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #ff8c42, #ff8c42);
            border-radius: 2px 2px 0 0;
        }

        /* Quantity input */
        .qty-control { transition: all 0.2s ease; }
        .qty-control:hover { background: #ff8c42; color: white; transform: scale(1.1); }

        /* Scrollbar */
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: linear-gradient(135deg, #ff8c42, #ff8c42); border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: linear-gradient(135deg, #ff8c42, #ff8c42); }

        /* Ripple effect */
        .ripple { position: relative; overflow: hidden; }
        .ripple::before {
            content: '';
            position: absolute;
            top: 50%; left: 50%;
            width: 0; height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        .ripple:active::before { width: 300px; height: 300px; }

        @keyframes fadeOut { to { opacity: 0; transform: translateX(100%); } }
    </style>
</head>

<div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 p-4">
    <div class="max-w-7xl mx-auto">
        <!-- Header Section -->
        <div class="glass-card rounded-2xl p-6 mb-6 shadow-xl">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-[#ff8c42] to-[#875e0cff] rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-cash-register text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Point of Sale System</h1>
                        <p class="text-sm text-gray-500">Process billing payments and direct product sales</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="px-4 py-2 bg-green-100 text-green-800 rounded-xl font-medium text-sm">
                        <i class="fas fa-circle text-green-500 mr-2 animate-pulse"></i> System Online
                    </div>
                </div>
            </div>
            <!-- Pet Owner Selection (for direct sales) -->
            <div class="bg-white/70 rounded-xl p-4 border border-white/50">
                <label for="petOwner" class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-user mr-2 text-[#ff8c42]"></i>Select Customer (for direct sales)
                </label>
                <select id="petOwner" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#ff8c42] focus:border-transparent transition-all duration-300 bg-white shadow-sm">
                    @foreach ($owners as $owner)
                        <option value="{{ $owner->own_id }}">{{ $owner->own_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Cart Section -->
            <div class="lg:col-span-2">
                <div class="glass-card rounded-2xl shadow-xl overflow-hidden">
                    <div class="bg-gradient-to-r from-[#ff8c42] to-[#875e0cff] text-white p-6">
                        <h2 class="text-xl font-bold flex items-center gap-3">
                            <i class="fas fa-shopping-cart"></i> Shopping Cart
                        </h2>
                    </div>
                    <div class="bg-gray-50 border-b border-gray-200">
                        <div class="grid grid-cols-[50px_1fr_120px_100px_120px_60px] items-center text-center py-4 font-semibold text-gray-700 text-sm">
                            <div>#</div><div class="text-left">Item</div><div>Quantity</div><div>Price</div><div>Total</div><div>Action</div>
                        </div>
                    </div>
                    <div id="posItems" class="custom-scrollbar max-h-96 overflow-y-auto bg-white"></div>
                    <div class="bg-gray-50 border-t border-gray-200 p-4">
                        <div class="flex justify-between items-center mb-4">
                            <div class="text-lg font-semibold text-gray-700">
                                Total Items: <span id="totalQty" class="text-[#ff8c42]">0</span>
                            </div>
                            <div class="text-2xl font-bold text-gray-800">
                                Total: <span id="grandTotal" class="text-[#ff8c42]">₱0.00</span>
                            </div>
                        </div>
                        <div class="flex gap-3 justify-end">
                            <button id="clearCart" class="modern-btn ripple px-6 py-3 bg-red-500 text-white rounded-xl font-semibold shadow-lg hover:bg-red-600 flex items-center gap-2">
                                <i class="fas fa-trash"></i> Clear Cart
                            </button>
                            <button id="payNow" class="modern-btn ripple px-8 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl font-semibold shadow-lg hover:from-green-600 hover:to-green-700 flex items-center gap-2">
                                <i class="fas fa-credit-card"></i> Pay
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Panel with Tabs -->
            <div class="lg:col-span-1">
                <div class="glass-card rounded-2xl shadow-xl overflow-hidden">
                    <!-- Tab Headers -->
                    <div class="bg-gradient-to-r from-[#ff8c42] to-[#875e0cff] text-white p-4">
                        <div class="flex gap-2">
                            <button id="productsTab" class="tab-button active flex-1 px-4 py-2 text-sm font-semibold rounded-lg transition-all duration-300">
                                <i class="fas fa-box-open mr-2"></i>Products
                            </button>
                            <button id="billingsTab" class="tab-button flex-1 px-4 py-2 text-sm font-semibold rounded-lg transition-all duration-300">
                                <i class="fas fa-file-invoice mr-2"></i>Pending Bills
                            </button>
                        </div>
                    </div>

                    <!-- Products Tab Content -->
                    <div id="productsContent" class="tab-content">
                        <div class="p-4 border-b border-gray-200">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                <input type="search" id="searchItem" placeholder="Search products..." 
                                    class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#ff8c42] focus:border-transparent transition-all duration-300 bg-white shadow-sm" />
                            </div>
                        </div>
                        <div id="productContainer" class="p-4 grid grid-cols-1 gap-3 custom-scrollbar max-h-96 overflow-y-auto bg-white">
                            @forelse ($items as $item)
                                <button type="button"
                                    class="product-card product-btn bg-white border-2 border-gray-100 rounded-xl p-4 text-left hover:border-[#ff8c42] transition-all duration-300 shadow-sm"
                                    data-id="{{ $item->prod_id }}"
                                    data-name="{{ $item->prod_name }}"
                                    data-price="{{ $item->prod_price }}"
                                    data-stock="{{ $item->prod_stocks }}"
                                    data-type="{{ $item->prod_category }}">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="font-semibold text-gray-800 text-sm mb-1">{{ $item->prod_name }}</h4>
                                            <p class="text-[#ff8c42] font-bold">₱{{ number_format($item->prod_price, 2) }}</p>
                                            <p class="text-xs text-gray-500">Stock: {{ $item->prod_stocks }}</p>
                                        </div>
                                        <div class="w-10 h-10 bg-gradient-to-br from-[#ff8c42] to-[#875e0cff] rounded-lg flex items-center justify-center">
                                            <i class="fas fa-plus text-white text-sm"></i>
                                        </div>
                                    </div>
                                </button>
                            @empty
                                <div class="text-center py-8 text-gray-500">
                                    <i class="fas fa-box-open text-4xl mb-4 opacity-50"></i>
                                    <p>No products available</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Billings Tab Content -->
                    <div id="billingsContent" class="tab-content hidden">
                        <div class="p-4 border-b border-gray-200">
                            <h3 class="font-semibold text-gray-800">Pending Billings</h3>
                            <p class="text-xs text-gray-500">Click to pay existing bills</p>
                        </div>
                        <div id="billingContainer" class="p-4 grid grid-cols-1 gap-3 max-h-96 overflow-y-auto bg-white custom-scrollbar">
                            @forelse ($billings as $bill)
                                <button type="button"
                                    class="billing-card bg-white border-2 border-gray-100 rounded-xl p-4 text-left hover:border-[#ff8c42] transition-all duration-300 shadow-sm"
                                    data-id="{{ $bill->bill_id }}"
                                    data-total="{{ $bill->calculated_total }}"
                                    data-owner="{{ $bill->appointment?->pet?->owner?->own_name ?? 'N/A' }}"
                                    data-pet="{{ $bill->appointment?->pet?->pet_name ?? 'N/A' }}">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="font-semibold text-gray-800 text-sm mb-1">Bill #{{ $bill->bill_id }}</h4>
                                            <p class="text-[#ff8c42] font-bold">₱{{ number_format($bill->calculated_total, 2) }}</p>
                                            <p class="text-xs text-gray-500">{{ $bill->appointment?->pet?->owner?->own_name ?? 'N/A' }} - {{ $bill->appointment?->pet?->pet_name ?? 'N/A' }}</p>
                                        </div>
                                        <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-dollar-sign text-white text-sm"></i>
                                        </div>
                                    </div>
                                </button>
                            @empty
                                <div class="text-center py-8 text-gray-500">
                                    <i class="fas fa-file-invoice text-4xl mb-4 opacity-50"></i>
                                    <p>No pending bills</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div id="paymentModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center hidden z-50">
    <div class="glass-card rounded-2xl p-8 w-96 shadow-2xl slide-in">
        <div class="text-center mb-6">
            <div class="w-16 h-16 bg-gradient-to-br from-green-400 to-green-600 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                <i class="fas fa-money-bill-wave text-white text-xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800" id="paymentModalTitle">Process Payment</h2>
            <p class="text-gray-500" id="paymentModalSubtitle">Enter the cash amount received</p>
        </div>
        <div class="space-y-4">
            <div class="bg-blue-50 rounded-xl p-4 border border-blue-200" id="paymentDetails">
                <div class="text-sm text-gray-600 mb-2">Payment Details</div>
                <div id="paymentItemsList"></div>
                <div class="border-t border-blue-300 mt-2 pt-2">
                    <div class="font-bold text-lg">Total: <span id="paymentTotal">₱0.00</span></div>
                </div>
            </div>
            <div>
                <label for="cashAmount" class="block text-sm font-semibold text-gray-700 mb-2">Cash Amount</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">₱</span>
                    <input type="number" id="cashAmount" min="0" step="0.01"
                        class="w-full pl-8 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-300 bg-white shadow-sm text-lg font-semibold" />
                </div>
            </div>
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-4 border-2 border-green-200">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Change</label>
                <div id="changeAmount" class="text-3xl font-bold text-green-600">₱0.00</div>
            </div>
        </div>
        <div class="flex gap-3 mt-8">
            <button id="cancelPayment" class="flex-1 modern-btn ripple px-6 py-3 bg-gray-500 text-white rounded-xl font-semibold shadow-lg hover:bg-gray-600">Cancel</button>
            <button id="confirmPayment" class="flex-1 modern-btn ripple px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl font-semibold shadow-lg hover:from-green-600 hover:to-green-700">Confirm Payment</button>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div id="successModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center hidden z-50">
    <div class="glass-card rounded-2xl p-8 w-96 shadow-2xl slide-in">
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
            <button id="closeSuccess" class="modern-btn ripple px-8 py-3 bg-gradient-to-r from-[#ff8c42] to-[#f88e28] text-white rounded-xl font-semibold shadow-lg">Continue</button>
        </div>
    </div>
</div>

<<script>
document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("searchItem");
    const productButtons = document.querySelectorAll(".product-btn");
    const billingButtons = document.querySelectorAll(".billing-card");
    const posItems = document.getElementById("posItems");
    const totalQtyEl = document.getElementById("totalQty");
    const grandTotalEl = document.getElementById("grandTotal");
    const paymentModal = document.getElementById("paymentModal");
    const successModal = document.getElementById("successModal");
    const cashInput = document.getElementById("cashAmount");
    const changeAmountDisplay = document.getElementById("changeAmount");
    const payNowBtn = document.getElementById("payNow");
    const clearCartBtn = document.getElementById("clearCart");
    const cancelPaymentBtn = document.getElementById("cancelPayment");
    const confirmPaymentBtn = document.getElementById("confirmPayment");
    const closeSuccessBtn = document.getElementById("closeSuccess");

    // Tab switching
    const productsTab = document.getElementById("productsTab");
    const billingsTab = document.getElementById("billingsTab");
    const productsContent = document.getElementById("productsContent");
    const billingsContent = document.getElementById("billingsContent");

    let isPayingBill = false;
    let currentBillId = null;
    let paymentInProgress = false; // Add this flag

    // Tab functionality
    productsTab.addEventListener("click", () => {
        productsTab.classList.add("active");
        billingsTab.classList.remove("active");
        productsContent.classList.remove("hidden");
        billingsContent.classList.add("hidden");
        isPayingBill = false;
    });

    billingsTab.addEventListener("click", () => {
        billingsTab.classList.add("active");
        productsTab.classList.remove("active");
        billingsContent.classList.remove("hidden");
        productsContent.classList.add("hidden");
    });

    function updateTotals() {
        let totalQty = 0, grandTotal = 0;
        const rows = document.querySelectorAll(".pos-row");
        
        rows.forEach((row, index) => {
            row.querySelector(".row-number").textContent = index + 1;
            const qty = parseInt(row.querySelector(".qty").value) || 0;
            const price = parseFloat(row.querySelector(".price").dataset.price) || 0;
            const total = qty * price;
            row.querySelector(".total").textContent = `₱${total.toFixed(2)}`;
            totalQty += qty;
            grandTotal += total;
        });
        
        totalQtyEl.textContent = totalQty;
        grandTotalEl.textContent = `₱${grandTotal.toFixed(2)}`;
    }

    function rebindButtons() {
        // Minus buttons
        document.querySelectorAll(".btn-minus").forEach(btn => {
            btn.onclick = function () {
                const input = this.nextElementSibling;
                if (parseInt(input.value) > 1) {
                    input.value = parseInt(input.value) - 1;
                    updateTotals();
                }
            };
        });

        // Plus buttons
        document.querySelectorAll(".btn-plus").forEach(btn => {
            btn.onclick = function () {
                const input = this.previousElementSibling;
                const maxStock = parseInt(input.dataset.stock) || 999;
                const currentValue = parseInt(input.value);
                
                if (currentValue < maxStock) {
                    input.value = currentValue + 1;
                    updateTotals();
                } else {
                    alert(`Maximum stock available: ${maxStock}`);
                }
            };
        });

        // Remove buttons
        document.querySelectorAll(".btn-remove").forEach(btn => {
            btn.onclick = function () {
                const row = this.closest(".pos-row");
                row.style.animation = 'fadeOut 0.3s ease-out';
                setTimeout(() => {
                    row.remove();
                    updateTotals();
                }, 300);
            };
        });

        // Quantity input change
        document.querySelectorAll(".qty").forEach(input => {
            input.onchange = function() {
                const maxStock = parseInt(this.dataset.stock) || 999;
                const value = parseInt(this.value);
                
                if (value > maxStock) {
                    this.value = maxStock;
                    alert(`Maximum stock available: ${maxStock}`);
                } else if (value < 1) {
                    this.value = 1;
                }
                updateTotals();
            };
        });
    }

    function createPOSRow(name, price, id, type, stock, qty = 1) {
        // Check if item already exists in cart
        let exists = false;
        document.querySelectorAll(".pos-row").forEach(row => {
            if (row.dataset.id === id.toString()) {
                const qtyInput = row.querySelector(".qty");
                const currentQty = parseInt(qtyInput.value);
                const newQty = currentQty + qty;
                
                if (newQty <= stock) {
                    qtyInput.value = newQty;
                    updateTotals();
                } else {
                    alert(`Cannot add more. Maximum stock: ${stock}`);
                }
                exists = true;
            }
        });
        
        if (exists) return;

        // Create new row
        const row = document.createElement("div");
        row.className = "grid grid-cols-[50px_1fr_120px_100px_120px_60px] items-center text-center py-4 border-b border-gray-100 hover:bg-gray-50 transition-colors duration-200 pos-row fade-in";
        row.setAttribute("data-id", id);
        row.setAttribute("data-type", type);
        row.innerHTML = `
            <div class="row-number text-gray-500 font-medium">#</div>
            <div class="text-left px-3 item-name">
                <div class="font-semibold text-gray-800">${name}</div>
                <div class="text-xs text-gray-500">${type} (Stock: ${stock})</div>
            </div>
            <div class="flex justify-center items-center gap-2">
                <button class="qty-control btn-minus w-8 h-8 rounded-lg bg-gray-200 hover:bg-red-500 hover:text-white flex items-center justify-center text-sm font-bold transition-all duration-200">
                    <i class="fas fa-minus text-xs"></i>
                </button>
                <input type="number" value="${qty}" data-stock="${stock}" class="qty w-16 text-center border border-gray-200 rounded-lg h-8 font-semibold focus:ring-2 focus:ring-[#0f7ea0] focus:border-transparent" min="1" max="${stock}" />
                <button class="qty-control btn-plus w-8 h-8 rounded-lg bg-gray-200 hover:bg-green-500 hover:text-white flex items-center justify-center text-sm font-bold transition-all duration-200">
                    <i class="fas fa-plus text-xs"></i>
                </button>
            </div>
            <div class="price font-semibold text-gray-700" data-price="${price}">₱${parseFloat(price).toFixed(2)}</div>
            <div class="font-bold text-[#ff8c42] total">₱${(price*qty).toFixed(2)}</div>
            <div class="flex justify-center">
                <button class="btn-remove w-8 h-8 rounded-lg bg-red-100 hover:bg-red-500 hover:text-white flex items-center justify-center transition-all duration-200 group">
                    <i class="fas fa-trash text-xs text-red-500 group-hover:text-white"></i>
                </button>
            </div>
        `;
        posItems.appendChild(row);
        rebindButtons();
        updateTotals();
    }

    function printReceipt(transactionData) {
        const receiptContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Receipt</title>
                <style>
                    body { font-family: 'Courier New', monospace; margin: 0; padding: 20px; background: white; font-size: 12px; }
                    .receipt { max-width: 300px; margin: 0 auto; border: 1px solid #ddd; padding: 15px; background: white; }
                    .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
                    .header h1 { margin: 0; font-size: 16px; font-weight: bold; }
                    .header p { margin: 3px 0; font-size: 11px; }
                    .customer-info { margin-bottom: 15px; font-size: 11px; border-bottom: 1px dashed #999; padding-bottom: 10px; }
                    .items { border-bottom: 1px dashed #999; padding-bottom: 10px; margin-bottom: 15px; }
                    .item { display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 11px; }
                    .item-details { flex: 1; }
                    .item-name { font-weight: bold; margin-bottom: 2px; }
                    .item-qty-price { font-size: 10px; color: #666; }
                    .item-total { text-align: right; min-width: 60px; font-weight: bold; }
                    .totals { font-size: 12px; margin-bottom: 15px; }
                    .total-row { display: flex; justify-content: space-between; margin-bottom: 5px; padding: 2px 0; }
                    .total-row.grand-total { font-weight: bold; font-size: 14px; border-top: 2px solid #000; border-bottom: 1px solid #000; padding: 8px 0; margin-top: 10px; }
                    .payment-info { text-align: center; margin: 15px 0; font-size: 12px; }
                    .payment-row { display: flex; justify-content: space-between; margin-bottom: 5px; }
                    .change-amount { font-size: 16px; font-weight: bold; color: #000; }
                    .footer { text-align: center; margin-top: 20px; font-size: 10px; color: #666; }
                    .divider { border-top: 1px dashed #999; margin: 10px 0; }
                    @media print { body { padding: 0; } .receipt { border: none; box-shadow: none; max-width: none; margin: 0; } }
                </style>
            </head>
            <body onload="window.print(); window.close();">
                <div class="receipt">
                    <div class="header">
                        <h1>VETERINARY CLINIC</h1>
                        <p>Point of Sale System</p>
                        <p>Receipt #${transactionData.receiptNumber || new Date().getTime()}</p>
                        <p>${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}</p>
                    </div>
                    <div class="customer-info">
                        <div><strong>Customer:</strong> ${transactionData.customerName || 'Walk-in Customer'}</div>
                        <div><strong>Cashier:</strong> ${transactionData.cashier || 'System'}</div>
                        <div><strong>Transaction Type:</strong> ${transactionData.type || 'Direct Sale'}</div>
                    </div>
                    <div class="items">
                        ${transactionData.items.map(item => `
                            <div class="item">
                                <div class="item-details">
                                    <div class="item-name">${item.name}</div>
                                    <div class="item-qty-price">${item.quantity} × ₱${item.price.toFixed(2)}</div>
                                </div>
                                <div class="item-total">₱${(item.quantity * item.price).toFixed(2)}</div>
                            </div>
                        `).join('')}
                    </div>
                    <div class="totals">
                        <div class="total-row"><span>Subtotal:</span><span>₱${transactionData.subtotal.toFixed(2)}</span></div>
                        <div class="total-row"><span>Total Items:</span><span>${transactionData.totalItems}</span></div>
                        <div class="total-row grand-total"><span>TOTAL:</span><span>₱${transactionData.total.toFixed(2)}</span></div>
                    </div>
                    <div class="payment-info">
                        <div class="payment-row"><span>Cash Received:</span><span>₱${transactionData.cash.toFixed(2)}</span></div>
                        <div class="payment-row"><span>Change:</span><span class="change-amount">₱${transactionData.change.toFixed(2)}</span></div>
                    </div>
                    <div class="divider"></div>
                    <div class="footer">
                        <p>Thank you for your purchase!</p>
                        <p>Please keep this receipt for your records</p>
                        <div class="divider"></div>
                        <p>This serves as your official receipt</p>
                        <p>For inquiries, please contact us</p>
                    </div>
                </div>
            </body>
            </html>
        `;
        
        const printWindow = window.open('', '_blank', 'width=400,height=600,scrollbars=yes');
        printWindow.document.write(receiptContent);
        printWindow.document.close();
    }

    // Add product to cart
    productButtons.forEach(btn => {
        btn.addEventListener("click", function () {
            const { id, name, price, type, stock } = this.dataset;
            const stockNum = parseInt(stock);
            
            if (stockNum <= 0) {
                alert("This item is out of stock!");
                return;
            }
            
            this.style.transform = 'scale(0.95)';
            setTimeout(() => this.style.transform = '', 150);
            
            createPOSRow(name, parseFloat(price), id, type, stockNum);
            isPayingBill = false;
        });
    });

    // Handle billing payment
    billingButtons.forEach(btn => {
        btn.addEventListener("click", function () {
            const billId = this.dataset.id;
            const total = parseFloat(this.dataset.total);
            const owner = this.dataset.owner;
            const pet = this.dataset.pet;
            
            posItems.innerHTML = "";
            updateTotals();
            
            isPayingBill = true;
            currentBillId = billId;
            
            document.getElementById("paymentModalTitle").textContent = "Pay Billing";
            document.getElementById("paymentModalSubtitle").textContent = `Bill #${billId} - ${owner} (${pet})`;
            document.getElementById("paymentItemsList").innerHTML = `
                <div class="text-sm">
                    <div><strong>Owner:</strong> ${owner}</div>
                    <div><strong>Pet:</strong> ${pet}</div>
                    <div><strong>Bill ID:</strong> #${billId}</div>
                </div>
            `;
            document.getElementById("paymentTotal").textContent = `₱${total.toFixed(2)}`;
            
            cashInput.value = "";
            changeAmountDisplay.textContent = "₱0.00";
            changeAmountDisplay.className = "text-3xl font-bold text-green-600";
            paymentModal.classList.remove("hidden");
            setTimeout(() => cashInput.focus(), 300);
        });
    });

    // Search functionality
    searchInput.addEventListener("input", function () {
        const keyword = this.value.toLowerCase();
        productButtons.forEach(btn => {
            const name = btn.dataset.name.toLowerCase();
            btn.style.display = name.includes(keyword) ? "block" : "none";
        });
    });

    // Clear cart
    clearCartBtn.addEventListener("click", function() {
        if (confirm("Are you sure you want to clear all items from the cart?")) {
            posItems.innerHTML = "";
            updateTotals();
            isPayingBill = false;
            currentBillId = null;
        }
    });

    // Pay Now button
    payNowBtn.addEventListener("click", () => {
        const grandTotal = parseFloat(grandTotalEl.textContent.replace("₱", "").replace(",", ""));
        
        if (isPayingBill && currentBillId) {
            alert("Please use the billing payment option from the Pending Bills tab.");
            return;
        }
        
        if (grandTotal === 0) {
            alert("Please add items to cart first.");
            return;
        }
        
        isPayingBill = false;
        
        document.getElementById("paymentModalTitle").textContent = "Process Payment";
        document.getElementById("paymentModalSubtitle").textContent = "Enter the cash amount received";
        
        const cartItems = Array.from(document.querySelectorAll(".pos-row")).map(row => {
            const name = row.querySelector(".item-name div").textContent;
            const qty = row.querySelector(".qty").value;
            const price = parseFloat(row.querySelector(".price").dataset.price);
            return `<div class="text-sm">${name} x${qty} = ₱${(price * qty).toFixed(2)}</div>`;
        }).join('');
        
        document.getElementById("paymentItemsList").innerHTML = cartItems;
        document.getElementById("paymentTotal").textContent = `₱${grandTotal.toFixed(2)}`;
        
        cashInput.value = "";
        changeAmountDisplay.textContent = "₱0.00";
        changeAmountDisplay.className = "text-3xl font-bold text-green-600";
        paymentModal.classList.remove("hidden");
        setTimeout(() => cashInput.focus(), 300);
    });

    // Cancel payment
    cancelPaymentBtn.addEventListener("click", () => {
        paymentModal.classList.add("hidden");
        paymentInProgress = false;
        isPayingBill = false;
        currentBillId = null;
    });

    // Calculate change
    cashInput.addEventListener("input", () => {
        const total = parseFloat(document.getElementById("paymentTotal").textContent.replace("₱", "").replace(",", ""));
        const cash = parseFloat(cashInput.value) || 0;
        const change = cash - total;
        
        if (cash >= total && total > 0) {
            changeAmountDisplay.textContent = `₱${change.toFixed(2)}`;
            changeAmountDisplay.className = "text-3xl font-bold text-green-600";
        } else {
            changeAmountDisplay.textContent = cash > 0 ? `₱${change.toFixed(2)}` : "₱0.00";
            changeAmountDisplay.className = "text-3xl font-bold text-red-500";
        }
    });

    // SINGLE Confirm Payment Handler
    confirmPaymentBtn.addEventListener("click", function() {
        // Prevent double submission
        if (paymentInProgress) {
            console.log("Payment already in progress");
            return false;
        }

        const total = parseFloat(document.getElementById("paymentTotal").textContent.replace("₱", "").replace(",", ""));
        const cash = parseFloat(cashInput.value) || 0;
        const change = cash - total;
        
        if (cash < total) {
            alert("Insufficient cash amount. Please enter enough to cover the total.");
            cashInput.focus();
            return false;
        }

        // Lock immediately
        paymentInProgress = true;
        confirmPaymentBtn.disabled = true;
        cancelPaymentBtn.disabled = true;
        confirmPaymentBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';

        // Prepare receipt data
        let receiptData = {
            receiptNumber: new Date().getTime(),
            cash: cash,
            total: total,
            change: change,
            customerName: 'Walk-in Customer',
            cashier: 'POS System',
            items: [],
            subtotal: total,
            totalItems: 0,
            type: 'Direct Sale'
        };

        if (isPayingBill && currentBillId) {
            // Billing payment
            receiptData.type = 'Bill Payment';
            receiptData.items = [{ name: `Bill #${currentBillId}`, quantity: 1, price: total }];
            receiptData.totalItems = 1;
            receiptData.customerName = document.querySelector(`[data-id="${currentBillId}"]`).dataset.owner;

            fetch(`/pos/pay-billing/${currentBillId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ cash: cash, total: total })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    paymentModal.classList.add("hidden");
                    document.getElementById("successMessage").textContent = `Bill #${currentBillId} paid successfully!`;
                    document.getElementById("successChange").textContent = `₱${change.toFixed(2)}`;
                    successModal.classList.remove("hidden");
                    printReceipt(receiptData);
                    document.querySelector(`[data-id="${currentBillId}"]`)?.remove();
                } else {
                    throw new Error(data.message || 'Payment failed');
                }
            })
            .catch(error => {
                console.error("Billing payment error:", error);
                alert(error.message || "Payment failed. Please try again.");
            })
            .finally(() => {
                confirmPaymentBtn.innerHTML = '<i class="fas fa-credit-card mr-2"></i>Confirm Payment';
                confirmPaymentBtn.disabled = false;
                cancelPaymentBtn.disabled = false;
                paymentInProgress = false;
            });
        } else {
            // Direct sale
            const items = Array.from(document.querySelectorAll(".pos-row")).map(row => ({
                product_id: parseInt(row.dataset.id),
                type: row.dataset.type,
                name: row.querySelector(".item-name div").textContent,
                quantity: parseInt(row.querySelector(".qty").value),
                price: parseFloat(row.querySelector(".price").dataset.price)
            }));

            if (items.length === 0) {
                alert("No items in cart.");
                confirmPaymentBtn.innerHTML = '<i class="fas fa-credit-card mr-2"></i>Confirm Payment';
                confirmPaymentBtn.disabled = false;
                cancelPaymentBtn.disabled = false;
                paymentInProgress = false;
                return;
            }

            receiptData.items = items.map(item => ({ name: item.name, quantity: item.quantity, price: item.price }));
            receiptData.totalItems = items.reduce((sum, item) => sum + item.quantity, 0);
            
            const selectedOwner = document.getElementById("petOwner");
            if (selectedOwner.value !== "0") {
                receiptData.customerName = selectedOwner.options[selectedOwner.selectedIndex].text;
            }

            const ownerId = document.getElementById("petOwner").value;

            fetch("/pos", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    items: items,
                    cash: cash,
                    total: total,
                    owner_id: ownerId !== "0" ? parseInt(ownerId) : null
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    paymentModal.classList.add("hidden");
                    document.getElementById("successMessage").textContent = "Direct sale completed successfully!";
                    document.getElementById("successChange").textContent = `₱${data.change.toFixed(2)}`;
                    successModal.classList.remove("hidden");
                    receiptData.change = data.change;
                    printReceipt(receiptData);
                } else {
                    throw new Error(data.message || 'Payment failed');
                }
            })
            .catch(error => {
                console.error("Payment error:", error);
                alert(error.message || "Payment failed. Please try again.");
            })
            .finally(() => {
                confirmPaymentBtn.innerHTML = '<i class="fas fa-credit-card mr-2"></i>Confirm Payment';
                confirmPaymentBtn.disabled = false;
                cancelPaymentBtn.disabled = false;
                paymentInProgress = false;
            });
        }
        
        return false;
    });

    // Close success modal
    closeSuccessBtn.addEventListener("click", () => {
        successModal.classList.add("hidden");
        posItems.innerHTML = "";
        updateTotals();
        document.getElementById("petOwner").selectedIndex = 0;
        isPayingBill = false;
        currentBillId = null;
    });

    // Close modals when clicking outside
    [paymentModal, successModal].forEach(modal => {
        modal.addEventListener("click", (e) => {
            if (e.target === modal) {
                modal.classList.add("hidden");
            }
        });
    });

    updateTotals();
});
</script>
@endsection