<!DOCTYPE html>
<html>
<head>
    <title>Receipt</title>
    <style>
        /* Add your styles here */
    </style>
</head>
<body>
    <h2>Receipt #{{ $transaction->id }}</h2>
    <p>Customer: {{ $transaction->customer_name }}</p>
    <p>Date: {{ \Carbon\Carbon::parse($transaction->created_at)->format('F d, Y') }}</p>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Qty</th>
                <th>Price</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transaction->items as $item)
            <tr>
                <td>{{ $item->name }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format($item->price, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p>Total: {{ number_format($transaction->total, 2) }}</p>
</body>
</html>
