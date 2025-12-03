@php
    // Variables expected: $owner, $billDate, $petBillings, $branch, $totalAmount, $paidAmount, $balance, $services, $prescriptions, $products
    // $petBillings: Collection of all billings for this owner/date (one per pet)
    // $services: array of all services for all pets
    // $prescriptions: array of all prescriptions for all pets
    // $products: array of all products for all pets (if any)
@endphp
<!DOCTYPE html>
<html>
<head>
    <title>POS-Style Grouped Billing Receipt</title>
    <style>
        body { font-family: 'Courier New', monospace; margin: 0; padding: 0; font-size: 12px; }
        .receipt-container { max-width: 320px; margin: 0 auto; padding: 15px; }
        .logo { width: 60px; height: 60px; object-fit: contain; margin: 0 auto 5px auto; display: block; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
        h2 { margin: 0; font-size: 16px; }
        .section-title { font-size: 13px; font-weight: bold; margin: 10px 0 5px 0; border-bottom: 1px dashed #aaa; }
        .item-row { display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 12px; }
        .item-row .desc { flex: 1; }
        .totals { text-align: right; margin-top: 10px; font-size: 13px; }
        .grand-total { font-size: 16px; font-weight: bold; color: #0f7ea0; margin-top: 10px; }
        .footer { text-align: center; margin-top: 20px; padding-top: 10px; border-top: 1px dashed #999; font-size: 11px; color: #666; }
    </style>
</head>
<body>
<div class="receipt-container">
    <img src="{{ asset('images/pets2go.png') }}" alt="Pets2GO Logo" class="logo">
    <div class="header">
        <div style="font-size: 18px; font-weight: bold; color: #a86520; margin-bottom: 5px;">PETS 2GO VETERINARY CLINIC</div>
        <div style="font-size: 13px; font-weight: bold;">{{ $branch->branch_name ?? 'MAIN BRANCH' }}</div>
        <div style="font-size: 11px; color: #666;">{{ $branch->branch_address ?? '' }}</div>
        <div style="font-size: 11px; color: #666;">Contact: {{ $branch->branch_contactNum ?? '' }}</div>
        <h2>TRANSACTION RECEIPT</h2>
        <div style="font-size: 12px; margin: 5px 0;">Bill Date: {{ \Carbon\Carbon::parse($billDate)->format('M d, Y') }}</div>
        <div style="font-size: 12px; margin: 5px 0;">Customer: {{ $owner->own_name ?? 'N/A' }}</div>
    </div>

    <div class="section-title">SERVICES</div>
    @forelse($services as $service)
        <div class="item-row">
            <div class="desc">{{ $service['pet'] }}: {{ $service['name'] }}</div>
            <div>₱{{ number_format($service['price'], 2) }}</div>
        </div>
    @empty
        <div class="item-row"><div class="desc">No services</div></div>
    @endforelse
    @if(!empty($prescriptions))
        <div class="section-title">PRESCRIPTIONS</div>
        @forelse($prescriptions as $presc)
            <div class="item-row">
                <div class="desc">{{ $presc['pet'] }}: {{ $presc['name'] }}</div>
                <div>₱{{ number_format($presc['price'], 2) }}</div>
            </div>
        @empty
            <div class="item-row"><div class="desc">No prescriptions</div></div>
        @endforelse
    @endif

    @if(!empty($products))
        <div class="section-title">PRODUCTS</div>
        @foreach($products as $prod)
            <div class="item-row">
                <div class="desc">{{ is_array($prod) ? $prod['name'] : $prod->name }} x{{ is_array($prod) ? $prod['qty'] : $prod->qty }}</div>
                <div>₱{{ number_format(is_array($prod) ? $prod['subtotal'] : $prod->subtotal, 2) }}</div>
            </div>
        @endforeach
    @endif

    <div class="totals">
        <div>Services: ₱{{ number_format(array_sum(array_column($services, 'price')), 2) }}</div>
        @if(!empty($prescriptions))
        <div>Prescriptions: ₱{{ number_format(array_sum(array_column($prescriptions, 'price')), 2) }}</div>
        @endif
        @if(!empty($products))
            <div>Products: ₱{{ number_format(
                collect($products)->sum(function($prod) {
                    return is_array($prod) ? $prod['subtotal'] : $prod->subtotal;
                }), 2) }}
            </div>
        @endif
        <div class="grand-total">TOTAL: ₱{{ number_format($totalAmount, 2) }}</div>
        <div>Paid: ₱{{ number_format($paidAmount, 2) }}</div>
        <div>Change: ₱{{ number_format(max(0, $paidAmount - $totalAmount), 2) }}</div>
        <div>Status: <b>{{ $balance <= 0.01 ? 'PAID' : 'UNPAID' }}</b></div>
    </div>

    <div class="footer">
        <p style="margin: 5px 0; font-weight: bold;">Thank you for your payment!</p>
        <p style="margin: 5px 0;">Your pet's health is our priority</p>
        <p style="margin: 10px 0 5px 0;">Generated: {{ now()->format('M d, Y h:i A') }}</p>
    </div>
</div>
</body>
</html>
