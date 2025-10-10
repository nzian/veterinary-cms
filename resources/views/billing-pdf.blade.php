<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Billing Statement - {{ $billing->bill_id }}</title>
    <style>
        @page {
            margin: 20px;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.4;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            border: 2px solid #000;
        }
        
        .header {
            display: table;
            width: 100%;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .header-left {
            display: table-cell;
            width: 120px;
            vertical-align: middle;
        }
        
        .header-right {
            display: table-cell;
            text-align: center;
            vertical-align: middle;
        }
        
        .logo {
            width: 100px;
            height: 100px;
        }
        
        .clinic-name {
            font-size: 20px;
            font-weight: bold;
            color: #a86520;
            margin-bottom: 5px;
        }
        
        .branch-name {
            font-size: 16px;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 5px;
        }
        
        .clinic-details {
            font-size: 11px;
            color: #555;
            line-height: 1.3;
        }
        
        .title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin: 20px 0;
            text-transform: uppercase;
        }
        
        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .info-left, .info-right {
            display: table-cell;
            width: 50%;
            padding: 10px;
            vertical-align: top;
        }
        
        .info-row {
            margin-bottom: 8px;
        }
        
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 10px;
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
        
        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin: 20px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 2px solid #ddd;
        }
        
        .services-title {
            color: #2563eb;
        }
        
        .medications-title {
            color: #059669;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        th {
            background-color: #f3f4f6;
            padding: 10px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #d1d5db;
        }
        
        td {
            padding: 8px 10px;
            border: 1px solid #e5e7eb;
        }
        
        tr:hover {
            background-color: #f9fafb;
        }
        
        .item-number {
            width: 40px;
            text-align: center;
        }
        
        .price-column {
            text-align: right;
            font-weight: bold;
        }
        
        .subtotal-row {
            background-color: #eff6ff;
            font-weight: bold;
        }
        
        .medications-subtotal-row {
            background-color: #f0fdf4;
            font-weight: bold;
        }
        
        .total-section {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 3px solid #000;
        }
        
        .grand-total {
            text-align: right;
            font-size: 20px;
            font-weight: bold;
            color: #0f7ea0;
        }
        
        .summary-grid {
            display: table;
            width: 100%;
            margin-top: 20px;
            background-color: #f0fdf4;
            padding: 15px;
            border: 1px solid #86efac;
            border-radius: 5px;
        }
        
        .summary-item {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 10px;
        }
        
        .summary-value {
            font-size: 20px;
            font-weight: bold;
            color: #065f46;
        }
        
        .summary-label {
            font-size: 11px;
            color: #059669;
            margin-top: 5px;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #000;
        }
        
        .thank-you {
            font-size: 12px;
        }
        
        .thank-you-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .date-footer {
            margin-top: 15px;
            font-size: 10px;
            color: #666;
        }
        
        .instructions {
            font-size: 10px;
            font-style: italic;
            color: #666;
            margin-top: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <img src="{{ public_path('images/pets2go.png') }}" alt="Pets2GO Logo" class="logo">
            </div>
            <div class="header-right">
                <div class="clinic-name">PETS 2GO VETERINARY CLINIC</div>
                <div class="branch-name">MAIN BRANCH</div>
                <div class="clinic-details">
                    <div>Address: Your Clinic Address Here</div>
                    <div>Contact No: Your Contact Number Here</div>
                </div>
            </div>
        </div>

        <!-- Title -->
        <div class="title">Billing Statement</div>

        <!-- Customer & Billing Info -->
        <div class="info-section">
            <div class="info-left">
                <div class="info-row">
                    <span class="info-label">DATE:</span>
                    {{ \Carbon\Carbon::parse($billing->bill_date)->format('F d, Y') }}
                </div>
                <div class="info-row">
                    <span class="info-label">OWNER:</span>
                    {{ $billing->appointment?->pet?->owner?->own_name ?? 'N/A' }}
                </div>
                <div class="info-row">
                    <span class="info-label">PET NAME:</span>
                    {{ $billing->appointment?->pet?->pet_name ?? 'N/A' }}
                </div>
            </div>
            <div class="info-right">
                <div class="info-row">
                    <span class="info-label">BILL ID:</span>
                    #{{ $billing->bill_id }}
                </div>
                <div class="info-row">
                    <span class="info-label">PET SPECIES:</span>
                    {{ $billing->appointment?->pet?->pet_species ?? 'N/A' }}
                </div>
                <div class="info-row">
                    <span class="info-label">PET BREED:</span>
                    {{ $billing->appointment?->pet?->pet_breed ?? 'N/A' }}
                </div>
                <div class="info-row">
                    <span class="info-label">STATUS:</span>
                    @if($billingStatus === 'paid')
                        <span class="status-badge status-paid">PAID</span>
                    @else
                        <span class="status-badge status-pending">PENDING</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Services Section -->
        <div class="section-title services-title">SERVICES PROVIDED</div>
        
        @if($billing->appointment && $billing->appointment->services && $billing->appointment->services->count() > 0)
            <table>
                <thead>
                    <tr>
                        <th class="item-number">#</th>
                        <th>Service Name</th>
                        <th>Description</th>
                        <th class="price-column">Price</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($billing->appointment->services as $service)
                    <tr>
                        <td class="item-number">{{ $loop->iteration }}</td>
                        <td>{{ $service->serv_name ?? 'Service Not Found' }}</td>
                        <td>{{ $service->serv_description ?? 'N/A' }}</td>
                        <td class="price-column">₱{{ number_format($service->serv_price ?? 0, 2) }}</td>
                    </tr>
                    @endforeach
                    <tr class="subtotal-row">
                        <td colspan="3" style="text-align: right; padding-right: 10px;">Services Subtotal:</td>
                        <td class="price-column">₱{{ number_format($servicesTotal, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        @else
            <p style="color: #999; font-style: italic; padding: 10px 0;">No services provided</p>
        @endif

        <!-- Medications Section -->
        <div class="section-title medications-title">MEDICATIONS PROVIDED</div>
        
        @if(count($prescriptionItems) > 0)
            <table>
                <thead>
                    <tr>
                        <th class="item-number">#</th>
                        <th>Medication</th>
                        <th>Instructions</th>
                        <th class="price-column">Price</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($prescriptionItems as $item)
                    <tr>
                        <td class="item-number">{{ $loop->iteration }}</td>
                        <td>{{ $item['name'] }}</td>
                        <td>
                            {{ $item['instructions'] ?: 'No instructions provided' }}
                        </td>
                        <td class="price-column">
                            @if($item['price'] > 0)
                                ₱{{ number_format($item['price'], 2) }}
                            @else
                                N/A
                            @endif
                        </td>
                    </tr>
                    @endforeach
                    <tr class="medications-subtotal-row">
                        <td colspan="3" style="text-align: right; padding-right: 10px;">Medications Subtotal:</td>
                        <td class="price-column">₱{{ number_format($prescriptionTotal, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        @else
            <p style="color: #999; font-style: italic; padding: 10px 0;">No medications provided</p>
        @endif

        <!-- Total Section -->
        <div class="total-section">
            <div class="grand-total">TOTAL AMOUNT: ₱{{ number_format($grandTotal, 2) }}</div>
        </div>

        <!-- Summary -->
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-value">{{ $totalItems }}</div>
                <div class="summary-label">Total Items</div>
            </div>
            <div class="summary-item">
                <div class="summary-value">₱{{ number_format($servicesTotal, 2) }}</div>
                <div class="summary-label">Services</div>
            </div>
            <div class="summary-item">
                <div class="summary-value">₱{{ number_format($prescriptionTotal, 2) }}</div>
                <div class="summary-label">Medications</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="thank-you">
                <div class="thank-you-title">Thank you for choosing Pets2GO Veterinary Clinic!</div>
                <div>Your pet's health is our priority</div>
            </div>
            <div class="date-footer">
                Generated: {{ \Carbon\Carbon::now()->format('F d, Y h:i A') }}
            </div>
        </div>
    </div>
</body>
</html>