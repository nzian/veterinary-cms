@extends('AdminBoard')

@section('content')
<div class="min-h-screen px-2 sm:px-4 md:px-6 py-4">
    <div class="max-w-6xl mx-auto bg-white p-4 sm:p-6 rounded-lg shadow">
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 md:gap-0">
            <div>
                <h2 class="text-[#0f7ea0] font-bold text-xl sm:text-2xl">Transaction Details</h2>
                <p class="text-gray-600 text-sm">Transaction ID: #{{ $transactionId }}</p>
                <p class="text-xs sm:text-sm text-gray-500">
                    {{ $orders->count() }} item(s) | Total: ₱{{ number_format($transactionTotal, 2) }}
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('sales.index') }}" 
                   onclick="sessionStorage.setItem('activeTab', 'orders'); return true;"
                   class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-2 rounded text-xs sm:text-sm">
                   <i class="fa-solid fa-arrow-left"></i>
                </a>
                <button onclick="printReceipt()" class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-2 rounded text-xs sm:text-sm">
                    <i class="fas fa-print mr-1"></i>
                </button>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-100 text-green-700 px-3 py-2 mb-4 rounded text-sm">
                {{ session('success') }}
            </div>
        @endif

        @php $firstOrder = $orders->first(); @endphp

        <!-- Transaction & Customer Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <!-- Transaction Details -->
            <div class="bg-gray-50 p-3 sm:p-4 rounded-lg border">
                <h3 class="font-semibold text-gray-800 text-sm sm:text-lg mb-2">Transaction Information</h3>
                <div class="space-y-1 text-xs sm:text-sm">
                    <div class="flex justify-between"><span class="text-gray-600">Transaction ID:</span><span class="font-medium font-mono">#{{ $transactionId }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-600">Date & Time:</span><span class="font-medium">{{ \Carbon\Carbon::parse($firstOrder->ord_date)->format('M d, Y h:i A') }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-600">Total Items:</span><span class="font-medium">{{ $totalItems }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-600">Payment Method:</span><span class="font-medium">Cash</span></div>
                    <div class="flex justify-between border-t pt-1 mt-1"><span class="text-gray-600 font-semibold">Total Amount:</span><span class="font-bold text-green-600">₱{{ number_format($transactionTotal, 2) }}</span></div>
                </div>
            </div>

            <!-- Customer & Staff -->
            <div class="bg-gray-50 p-3 sm:p-4 rounded-lg border">
                <h3 class="font-semibold text-gray-800 text-sm sm:text-lg mb-2">Customer & Staff</h3>
                <div class="space-y-1 text-xs sm:text-sm">
                    <div class="flex justify-between"><span class="text-gray-600">Customer:</span>
                        <span class="font-medium">
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full
                                {{ $firstOrder->owner ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $firstOrder->owner?->own_name ?? 'Walk-in Customer' }}
                            </span>
                        </span>
                    </div>
                    @if($firstOrder->owner && $firstOrder->owner->own_email)
                        <div class="flex justify-between"><span class="text-gray-600">Email:</span><span class="font-medium">{{ $firstOrder->owner->own_email }}</span></div>
                    @endif
                    @if($firstOrder->owner && $firstOrder->owner->own_phone)
                        <div class="flex justify-between"><span class="text-gray-600">Phone:</span><span class="font-medium">{{ $firstOrder->owner->own_phone }}</span></div>
                    @endif
                    <div class="flex justify-between"><span class="text-gray-600">Cashier:</span><span class="font-medium">{{ $firstOrder->user->user_name ?? 'N/A' }}</span></div>
                    @if($firstOrder->user && $firstOrder->user->user_email)
                        <div class="flex justify-between"><span class="text-gray-600">Staff Email:</span><span class="font-medium">{{ $firstOrder->user->user_email }}</span></div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Products Table -->
        <div class="bg-white border rounded-lg overflow-x-auto mb-6">
            <table class="w-full min-w-[600px] text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="text-left px-3 py-2 font-medium text-gray-700">#</th>
                        <th class="text-left px-3 py-2 font-medium text-gray-700">Product</th>
                        <th class="text-center px-3 py-2 font-medium text-gray-700">Unit Price</th>
                        <th class="text-center px-3 py-2 font-medium text-gray-700">Qty</th>
                        <th class="text-right px-3 py-2 font-medium text-gray-700">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="px-3 py-2">{{ $loop->iteration }}</td>
                        <td class="px-3 py-2">
                            <p class="font-medium text-gray-800">{{ $order->product->prod_name ?? 'Product Not Found' }}</p>
                            @if($order->product && $order->product->prod_description)
                                <p class="text-xs text-gray-500">{{ Str::limit($order->product->prod_description, 50) }}</p>
                            @endif
                            <p class="text-xs text-gray-400">ID: {{ $order->product->prod_id ?? 'N/A' }}</p>
                        </td>
                        <td class="px-3 py-2 text-center">₱{{ number_format($order->product->prod_price ?? 0, 2) }}</td>
                        <td class="px-3 py-2 text-center">
                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-medium">{{ $order->ord_quantity }}</span>
                        </td>
                        <td class="px-3 py-2 text-right font-semibold">₱{{ number_format(($order->product->prod_price ?? 0) * $order->ord_quantity, 2) }}</td>
                    </tr>
                    @endforeach
                    <tr class="bg-green-50 border-t-2 border-green-200">
                        <td colspan="4" class="px-3 py-3 text-right font-semibold text-gray-800">Transaction Total:</td>
                        <td class="px-3 py-3 text-right font-bold text-green-600">₱{{ number_format($transactionTotal, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Payment Info -->
        @if($firstOrder->payment)
        <div class="bg-white border rounded-lg p-3 sm:p-4 mb-6">
            <h3 class="font-semibold text-gray-800 text-sm sm:text-lg mb-2">Payment Information</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs sm:text-sm">
                <div><span class="text-gray-600">Payment ID:</span><p class="font-medium">{{ $firstOrder->payment->payment_id ?? 'N/A' }}</p></div>
                <div><span class="text-gray-600">Amount Paid:</span><p class="font-medium text-green-600">₱{{ number_format($firstOrder->payment->payment_amount ?? $transactionTotal, 2) }}</p></div>
                <div><span class="text-gray-600">Status:</span>
                    <p class="font-medium"><span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">{{ $firstOrder->payment->payment_status ?? 'Completed' }}</span></p>
                </div>
            </div>
        </div>
        @endif

        <!-- Transaction Summary -->
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 sm:p-6">
            <h3 class="font-semibold text-green-800 text-sm sm:text-lg mb-3">Transaction Summary</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-center text-xs sm:text-sm">
                <div><p class="text-lg sm:text-2xl font-bold text-green-900">{{ $orders->count() }}</p><p class="text-green-600">Products</p></div>
                <div><p class="text-lg sm:text-2xl font-bold text-green-900">{{ $totalItems }}</p><p class="text-green-600">Total Items</p></div>
                <div><p class="text-lg sm:text-2xl font-bold text-green-900">₱{{ number_format($transactionTotal, 2) }}</p><p class="text-green-600">Total Amount</p></div>
            </div>
            <div class="mt-3 pt-3 border-t border-green-200 text-center text-green-700 text-xs sm:text-sm">
                <i class="fas fa-calendar-alt mr-1"></i>
                {{ \Carbon\Carbon::parse($firstOrder->ord_date)->format('l, F j, Y \a\t g:i A') }}
            </div>
        </div>
    </div>
</div>
<!-- Hidden Print Container -->
<div id="printContainer" style="display: none;">
    <div id="printContent" style="font-family: Arial, sans-serif; max-width: 400px; margin: 0 auto; padding: 20px;">
        <!-- Content will be populated by JavaScript -->
    </div>
</div>

<!-- Print Receipt Styles -->
<style>
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
}
</style>

<script>
function printReceipt() {
    const printContent = `
        <div style="text-align: center; margin-bottom: 20px;">
           <img src="{{ asset('images/pets2go.png') }}" alt="Pets2GO Logo" style="width: 60px; height: 60px; object-fit: contain; margin: 0 auto 5px auto; display: block;">
            
            <div style="font-size: 18px; font-weight: bold; color: #a86520; margin-bottom: 5px;">PETS 2GO VETERINARY CLINIC</div>
        </div>
        
        <div style="text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px;">
            <h2 style="margin: 0; font-size: 16px;">TRANSACTION RECEIPT</h2>
            <p style="margin: 5px 0; font-size: 13px;">Transaction ID: #{{ $transactionId }}</p>
            <p style="margin: 5px 0; font-size: 12px;">{{ \Carbon\Carbon::parse($firstOrder->ord_date)->format('M d, Y h:i A') }}</p>
        </div>
        
        <div style="margin-bottom: 15px; font-size: 13px;">
            <p style="margin: 3px 0;"><strong>Customer:</strong> {{ $firstOrder->owner?->own_name ?? 'Walk-in Customer' }}</p>
            <p style="margin: 3px 0;"><strong>Cashier:</strong> {{ $firstOrder->user->user_name ?? 'N/A' }}</p>
        </div>
        
        <div style="border-top: 1px solid #ccc; border-bottom: 1px solid #ccc; padding: 10px 0; margin-bottom: 15px;">
            @foreach($orders as $order)
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 12px;">
                <div style="flex: 1;">
                    <strong>{{ $order->product->prod_name ?? 'Product' }}</strong><br>
                    <small style="color: #666;">{{ $order->ord_quantity }} × ₱{{ number_format($order->product->prod_price ?? 0, 2) }}</small>
                </div>
                <div style="text-align: right; font-weight: bold;">
                    ₱{{ number_format(($order->product->prod_price ?? 0) * $order->ord_quantity, 2) }}
                </div>
            </div>
            @endforeach
        </div>
        
        <div style="text-align: center; font-size: 16px; font-weight: bold; border-top: 2px solid #000; padding-top: 10px; margin-bottom: 10px;">
            <p style="margin: 5px 0;">TOTAL: ₱{{ number_format($transactionTotal, 2) }}</p>
            <p style="font-size: 13px; font-weight: normal; margin: 5px 0;">Payment: CASH</p>
            <p style="font-size: 12px; font-weight: normal; margin: 5px 0;">Total Items: {{ $totalItems }}</p>
        </div>
        
        <div style="text-align: center; margin-top: 20px; padding-top: 15px; border-top: 1px dashed #999; font-size: 11px; color: #666;">
            <p style="margin: 5px 0; font-weight: bold;">Thank you for your purchase!</p>
            <p style="margin: 5px 0;">Your pet's health is our priority</p>
            <p style="margin: 10px 0 5px 0;">{{ \Carbon\Carbon::now()->format('F d, Y h:i A') }}</p>
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