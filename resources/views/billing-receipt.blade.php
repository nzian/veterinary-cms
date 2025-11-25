<!DOCTYPE html>
<html>
<head>
    <title>Billing Receipt - {{ $billing->bill_id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            padding: 20px;
        }
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            border: 2px solid #000;
            padding: 30px;
            background: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #000;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .logo {
            width: 100px;
            height: 100px;
            object-fit: contain;
        }
        .clinic-info {
            text-align: center;
            flex: 1;
        }
        .clinic-name {
            font-size: 24px;
            font-weight: bold;
            color: #a86520;
            margin-bottom: 5px;
        }
        .branch-name {
            font-size: 18px;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 5px;
        }
        .clinic-details {
            font-size: 14px;
            color: #666;
        }
        .receipt-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin: 20px 0;
        }
        .info-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .info-item {
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: bold;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #0f7ea0;
            border-bottom: 2px solid #0f7ea0;
            padding-bottom: 5px;
            margin: 20px 0 10px 0;
        }
        .item-list {
            margin-bottom: 20px;
        }
        .item {
            padding: 8px;
            border-left: 3px solid #3b82f6;
            background-color: #eff6ff;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .medication-item {
            border-left-color: #10b981;
            background-color: #f0fdf4;
        }
        .product-item {
            border-left-color: #f59e0b;
            background-color: #fff7ed;
        }
        .totals {
            text-align: right;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #000;
        }
        .total-row {
            margin: 5px 0;
            font-size: 14px;
        }
        .grand-total {
            font-size: 20px;
            font-weight: bold;
            color: #0f7ea0;
            margin-top: 10px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #000;
            font-size: 12px;
            color: #666;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-paid {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-pending {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .print-buttons {
            text-align: center;
            margin: 20px 0;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
            margin: 0 5px;
        }
        .btn-print {
            background: #0f7ea0;
            color: white;
        }
        .btn-print:hover {
            background: #0c6a86;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(15, 126, 160, 0.3);
        }
        .btn-close {
            background: #6b7280;
            color: white;
        }
        .btn-close:hover {
            background: #4b5563;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(107, 114, 128, 0.3);
        }
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .receipt-container {
                box-shadow: none;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="header">
            <img src="{{ asset('images/pets2go.png') }}" alt="Pets2GO Logo" class="logo">
            <div class="clinic-info">
                <div class="clinic-name">PETS 2GO VETERINARY CLINIC</div>
                <div class="branch-name">{{ $branch->branch_name ?? 'MAIN BRANCH' }}</div>
                <div class="clinic-details">
                    <div>{{ $branch->branch_address ?? 'Branch Address' }}</div>
                    <div>Contact: {{ $branch->branch_contactNum ?? 'Contact Number' }}</div>
                </div>
            </div>
        </div>

        <div class="receipt-title">BILLING RECEIPT</div>

        <div class="info-section">
            <div>
                <div class="info-item">
                    <span class="info-label">Bill ID:</span> {{ $billing->bill_id }}
                </div>
                <div class="info-item">
                    <span class="info-label">Visit ID:</span> {{ $billing->visit->visit_id ?? 'N/A' }}
                </div>
                <div class="info-item">
                    <span class="info-label">Date:</span> {{ \Carbon\Carbon::parse($billing->bill_date)->format('F d, Y') }}
                </div>
                <div class="info-item">
                    <span class="info-label">Owner:</span> {{ $billing->visit->pet->owner->own_name ?? 'N/A' }}
                </div>
            </div>
            <div>
                <div class="info-item">
                    <span class="info-label">Pet:</span> {{ $billing->visit->pet->pet_name ?? 'N/A' }}
                </div>
                <div class="info-item">
                    <span class="info-label">Species:</span> {{ $billing->visit->pet->pet_species ?? 'N/A' }}
                </div>
                <div class="info-item">
                    <span class="info-label">Breed:</span> {{ $billing->visit->pet->pet_breed ?? 'N/A' }}
                </div>
                <div class="info-item">
                    <span class="info-label">Status:</span> 
                    <span class="status-badge {{ strtolower($billing->bill_status) === 'paid' ? 'status-paid' : 'status-pending' }}">
                        {{ strtoupper($billing->bill_status) }}
                    </span>
                </div>
            </div>
        </div>

        @if($billing->visit && $billing->visit->services && $billing->visit->services->count() > 0)
        <div class="section-title">SERVICES PROVIDED (Visit Services)</div>
        <div class="item-list">
            @foreach($billing->visit->services as $service)
            <div class="item">
                {{ $loop->iteration }}. {{ $service->serv_name ?? 'Service' }} - ₱{{ number_format($service->serv_price ?? 0, 2) }}
            </div>
            @endforeach
        </div>
        @endif

        @if(count($prescriptionItems) > 0)
        <div class="section-title">MEDICATIONS PROVIDED</div>
        <div class="item-list">
            @foreach($prescriptionItems as $item)
            <div class="item medication-item">
                <div>{{ $loop->iteration }}. {{ $item['name'] }}</div>
                @if($item['price'] > 0)
                    <div style="margin-left: 20px; font-size: 12px; color: #666;">₱{{ number_format($item['price'], 2) }}</div>
                @endif
                @if($item['instructions'])
                    <div style="margin-left: 20px; font-size: 12px; color: #666; font-style: italic;">{{ $item['instructions'] }}</div>
                @endif
            </div>
            @endforeach
        </div>
        @endif

        @if($billing->orders && $billing->orders->count() > 0)
        <div class="section-title">PRODUCTS PURCHASED</div>
        <div class="item-list">
            @foreach($billing->orders as $order)
            <div class="item product-item">
                {{ $loop->iteration }}. {{ $order->product->prod_name ?? 'Product' }} 
                ({{ $order->ord_quantity }} × ₱{{ number_format($order->product->prod_price ?? 0, 2) }})
                - ₱{{ number_format($order->ord_quantity * ($order->product->prod_price ?? 0), 2) }}
            </div>
            @endforeach
        </div>
        @endif

        <div class="totals">
            @if($servicesTotal > 0)
            <div class="total-row">Services Subtotal: ₱{{ number_format($servicesTotal, 2) }}</div>
            @endif
            @if($prescriptionTotal > 0)
            <div class="total-row">Medications Subtotal: ₱{{ number_format($prescriptionTotal, 2) }}</div>
            @endif
            @if($productsTotal > 0)
            <div class="total-row">Products Subtotal: ₱{{ number_format($productsTotal, 2) }}</div>
            @endif
            <div class="grand-total">TOTAL AMOUNT: ₱{{ number_format($grandTotal, 2) }}</div>
        </div>

        <div class="footer">
            <div style="font-weight: bold; margin-bottom: 10px;">Thank you for choosing Pets2GO Veterinary Clinic!</div>
            <div>Your pet's health is our priority</div>
            <div style="margin-top: 10px;">{{ \Carbon\Carbon::now()->format('F d, Y h:i A') }}</div>
        </div>
    </div>

    <div class="print-buttons no-print">
        <button onclick="printReceipt()" class="btn btn-print">
            <i class="fas fa-print"></i> Print Receipt
        </button>
        <button onclick="closeWindow()" class="btn btn-close">
            <i class="fas fa-times"></i> Close
        </button>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        function printReceipt() {
            window.print();
        }

        function closeWindow() {
            // Try to close the window
            window.close();
            
            // If window.close() doesn't work (some browsers block it), redirect back
            setTimeout(function() {
                if (!window.closed) {
                    window.history.back();
                }
            }, 100);
        }

        // Optional: Add keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+P or Cmd+P for print
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                printReceipt();
            }
            // ESC to close
            if (e.key === 'Escape') {
                closeWindow();
            }
        });
    </script>
</body>
</html>
