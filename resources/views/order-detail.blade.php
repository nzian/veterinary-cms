<p style="margin:@extends('AdminBoard')

@section('content')
<div class="min-h-screen">
    <div class="max-w-6xl mx-auto bg-white p-6 rounded-lg shadow">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-[#0f7ea0] font-bold text-2xl">Transaction Details</h2>
                <p class="text-gray-600">Transaction ID: #{{ $transactionId }}</p>
                <p class="text-sm text-gray-500">
                    {{ $orders->count() }} item(s) | Total: ₱{{ number_format($transactionTotal, 2) }}
                </p>
            </div>
            <div class="flex gap-2">
                <a href="{{ url('/orders') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded">
                    Back to Sales
                </a>
                <button onclick="printReceipt()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                    Print Receipt
                </button>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-100 text-green-700 px-4 py-2 mb-4 rounded">
                {{ session('success') }}
            </div>
        @endif

        @php
            $firstOrder = $orders->first();
        @endphp

        <!-- Transaction Information Card -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Transaction Details -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="font-semibold text-lg mb-3 text-gray-800">Transaction Information</h3>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Transaction ID:</span>
                        <span class="font-medium font-mono">#{{ $transactionId }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Date & Time:</span>
                        <span class="font-medium">{{ \Carbon\Carbon::parse($firstOrder->ord_date)->format('M d, Y h:i A') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total Items:</span>
                        <span class="font-medium">{{ $totalItems }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Payment Method:</span>
                        <span class="font-medium">Cash</span>
                    </div>
                    <div class="flex justify-between border-t pt-2 mt-2">
                        <span class="text-gray-600 font-semibold">Total Amount:</span>
                        <span class="font-bold text-lg text-green-600">₱{{ number_format($transactionTotal, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Customer & Staff Details -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="font-semibold text-lg mb-3 text-gray-800">Customer & Staff</h3>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Customer:</span>
                        <span class="font-medium">
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full
                                {{ $firstOrder->owner ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $firstOrder->owner?->own_name ?? 'Walk-in Customer' }}
                            </span>
                        </span>
                    </div>
                    @if($firstOrder->owner && $firstOrder->owner->own_email)
                    <div class="flex justify-between">
                        <span class="text-gray-600">Customer Email:</span>
                        <span class="font-medium">{{ $firstOrder->owner->own_email }}</span>
                    </div>
                    @endif
                    @if($firstOrder->owner && $firstOrder->owner->own_phone)
                    <div class="flex justify-between">
                        <span class="text-gray-600">Customer Phone:</span>
                        <span class="font-medium">{{ $firstOrder->owner->own_phone }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-gray-600">Cashier:</span>
                        <span class="font-medium">{{ $firstOrder->user->user_name ?? 'N/A' }}</span>
                    </div>
                    @if($firstOrder->user && $firstOrder->user->user_email)
                    <div class="flex justify-between">
                        <span class="text-gray-600">Staff Email:</span>
                        <span class="font-medium">{{ $firstOrder->user->user_email }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Products in Transaction -->
        <div class="bg-white border rounded-lg overflow-hidden mb-6">
            <div class="bg-gray-200 px-4 py-3">
                <h3 class="font-semibold text-lg text-gray-800">Products in this Transaction</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="text-left px-4 py-3 text-sm font-medium text-gray-700">#</th>
                            <th class="text-left px-4 py-3 text-sm font-medium text-gray-700">Product</th>
                            <th class="text-center px-4 py-3 text-sm font-medium text-gray-700">Unit Price</th>
                            <th class="text-center px-4 py-3 text-sm font-medium text-gray-700">Quantity</th>
                            <th class="text-right px-4 py-3 text-sm font-medium text-gray-700">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $order)
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm">{{ $loop->iteration }}</td>
                            <td class="px-4 py-3">
                                <div>
                                    <p class="font-medium text-gray-800">{{ $order->product->prod_name ?? 'Product Not Found' }}</p>
                                    @if($order->product && $order->product->prod_description)
                                    <p class="text-sm text-gray-500">{{ Str::limit($order->product->prod_description, 50) }}</p>
                                    @endif
                                    <p class="text-xs text-gray-400">Product ID: {{ $order->product->prod_id ?? 'N/A' }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-medium">₱{{ number_format($order->product->prod_price ?? 0, 2) }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-sm font-medium">
                                    {{ $order->ord_quantity }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <span class="font-semibold text-gray-800">
                                    ₱{{ number_format(($order->product->prod_price ?? 0) * $order->ord_quantity, 2) }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                        <!-- Transaction Total Row -->
                        <tr class="bg-green-50 border-t-2 border-green-200">
                            <td colspan="4" class="px-4 py-4 text-right font-semibold text-gray-800">
                                Transaction Total:
                            </td>
                            <td class="px-4 py-4 text-right">
                                <span class="text-xl font-bold text-green-600">
                                    ₱{{ number_format($transactionTotal, 2) }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Payment Information (if available) -->
        @if($firstOrder->payment)
        <div class="bg-white border rounded-lg overflow-hidden mb-6">
            <div class="bg-gray-200 px-4 py-3">
                <h3 class="font-semibold text-lg text-gray-800">Payment Information</h3>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <span class="text-gray-600">Payment ID:</span>
                        <p class="font-medium">{{ $firstOrder->payment->payment_id ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <span class="text-gray-600">Amount Paid:</span>
                        <p class="font-medium text-green-600">₱{{ number_format($firstOrder->payment->payment_amount ?? $transactionTotal, 2) }}</p>
                    </div>
                    <div>
                        <span class="text-gray-600">Status:</span>
                        <p class="font-medium">
                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-sm">
                                {{ $firstOrder->payment->payment_status ?? 'Completed' }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Transaction Summary Card -->
        <div class="bg-green-50 border border-green-200 rounded-lg p-6">
            <h3 class="font-semibold text-lg text-green-800 mb-4">Transaction Summary</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="text-center">
                    <p class="text-2xl font-bold text-green-900">{{ $orders->count() }}</p>
                    <p class="text-sm text-green-600">Products</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-green-900">{{ $totalItems }}</p>
                    <p class="text-sm text-green-600">Total Items</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-green-900">₱{{ number_format($transactionTotal, 2) }}</p>
                    <p class="text-sm text-green-600">Total Amount</p>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-green-200 text-center text-green-700">
                <p class="text-sm">
                    <i class="fas fa-calendar-alt mr-2"></i>
                    {{ \Carbon\Carbon::parse($firstOrder->ord_date)->format('l, F j, Y \a\t g:i A') }}
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Print Receipt Modal/Styles -->
<style>
@media print {
    body * {
        visibility: hidden;
    }
    .printable, .printable * {
        visibility: visible;
    }
    .printable {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
    .no-print {
        display: none !important;
    }
}
</style>

<script>
function printReceipt() {
    // Create a printable receipt for the entire transaction
    const printContent = `
        <div class="printable" style="font-family: Arial, sans-serif; max-width: 400px; margin: 0 auto; padding: 20px;">
            <div style="text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px;">
                <h2 style="margin: 0;">TRANSACTION RECEIPT</h2>
                <p style="margin: 5px 0;">Transaction ID: #{{ $transactionId }}</p>
                <p style="margin: 5px 0;">{{ \Carbon\Carbon::parse($firstOrder->ord_date)->format('M d, Y h:i A') }}</p>
            </div>
            
            <div style="margin-bottom: 15px;">
                <p><strong>Customer:</strong> {{ $firstOrder->owner?->own_name ?? 'Walk-in Customer' }}</p>
                <p><strong>Cashier:</strong> {{ $firstOrder->user->user_name ?? 'N/A' }}</p>
            </div>
            
            <div style="border-top: 1px solid #ccc; border-bottom: 1px solid #ccc; padding: 10px 0; margin-bottom: 15px;">
                @foreach($orders as $order)
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <div>
                        <strong>{{ $order->product->prod_name ?? 'Product' }}</strong><br>
                        <small>{{ $order->ord_quantity }} × ₱{{ number_format($order->product->prod_price ?? 0, 2) }}</small>
                    </div>
                    <div style="text-align: right;">
                        ₱{{ number_format(($order->product->prod_price ?? 0) * $order->ord_quantity, 2) }}
                    </div>
                </div>
                @endforeach
            </div>
            
            <div style="text-align: center; font-size: 18px; font-weight: bold; border-top: 2px solid #000; padding-top: 10px;">
                <p>TOTAL: ₱{{ number_format($transactionTotal, 2) }}</p>
                <p style="font-size: 14px; font-weight: normal;">Payment: CASH</p>
                <p style="font-size: 12px; font-weight: normal;">Items: {{ $totalItems }}</p>
            </div>
            
            <div style="text-align: center; margin-top: 20px; font-size: 12px;">
                <p>Thank you for your purchase!</p>
                <p>{{ date('Y-m-d H:i:s') }}</p>
            </div>
        </div>
    `;
    
    const printWindow = window.open('', '', 'height=600,width=400');
    printWindow.document.write(printContent);
    printWindow.document.close();
    printWindow.print();
    printWindow.close();
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