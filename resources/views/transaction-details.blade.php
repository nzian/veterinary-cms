@extends('AdminBoard')
@section('content')
<div class="max-w-3xl mx-auto bg-white rounded-lg shadow p-8 mt-8">
    <h2 class="text-2xl font-bold mb-4">Transaction Details</h2>
    <div class="mb-4">
        <span class="font-semibold">Transaction Type:</span> {{ $transactionType }}<br>
        @if($billId)
            <span class="font-semibold">Billing ID:</span> {{ $billId }}<br>
        @endif
        <span class="font-semibold">Transaction ID:</span> {{ $id }}
    </div>
    <table class="table-auto w-full border-collapse border text-sm mb-6">
        <thead class="bg-gray-100">
            <tr>
                <th class="border px-4 py-2">Product</th>
                <th class="border px-4 py-2">Quantity</th>
                <th class="border px-4 py-2">Unit Price</th>
                <th class="border px-4 py-2">Total</th>
                <th class="border px-4 py-2">Customer</th>
                <th class="border px-4 py-2">Cashier</th>
                <th class="border px-4 py-2">Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orders as $order)
            <tr>
                <td class="border px-4 py-2">{{ $order->product->prod_name ?? 'N/A' }}</td>
                <td class="border px-4 py-2">{{ $order->ord_quantity }}</td>
                <td class="border px-4 py-2">₱{{ number_format($order->product->prod_price ?? 0, 2) }}</td>
                <td class="border px-4 py-2">₱{{ number_format($order->ord_quantity * ($order->product->prod_price ?? 0), 2) }}</td>
                <td class="border px-4 py-2">{{ $order->owner->own_name ?? 'Walk-in Customer' }}</td>
                <td class="border px-4 py-2">{{ $order->user->user_name ?? 'N/A' }}</td>
                <td class="border px-4 py-2">{{ \Carbon\Carbon::parse($order->ord_date)->format('M d, Y h:i A') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <a href="{{ url()->previous() }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Back</a>
</div>
@endsection
