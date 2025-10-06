<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title }} - {{ $branch->branch_name }}</title>
    <style>
        @page {
            size: letter;
            margin: 15mm 20mm 25mm 20mm;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #1f2937;
        }

        .first-page-header {
            background-color: #f88e28;
            padding: 12px 15px;
            margin: 0 0 20px 0;
            text-align: center;
            border-radius: 4px;
        }

        .first-page-header img {
            max-height: 70px;
            width: 100%;
            object-fit: contain;
        }

        .page-footer {
            position: fixed;
            bottom: 0;
            left: 20mm;
            right: 20mm;
            height: 18mm;
            padding: 8px 0;
            border-top: 2px solid #f88e28;
            font-size: 8pt;
            color: #6b7280;
            background-color: white;
        }

        .footer-content {
            display: table;
            width: 100%;
        }

        .footer-left, .footer-center, .footer-right {
            display: table-cell;
            vertical-align: middle;
        }

        .footer-left {
            text-align: left;
            width: 33%;
        }

        .footer-center {
            text-align: center;
            width: 34%;
        }

        .footer-right {
            text-align: right;
            width: 33%;
        }

        .page-number:before {
            content: counter(page);
        }

        .page-total:before {
            content: counter(pages);
        }

        .content-wrapper {
            margin-bottom: 22mm;
        }

        .report-title-section {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 3px solid #f88e28;
            page-break-after: avoid;
        }

        .report-title {
            font-size: 18pt;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .report-subtitle {
            font-size: 11pt;
            color: #6b7280;
            margin-bottom: 3px;
        }

        .report-id {
            font-size: 9pt;
            color: #9ca3af;
            font-family: 'Courier New', monospace;
        }

        .section-header {
            background-color: #f88e28;
            color: white;
            padding: 10px 14px;
            margin-top: 18px;
            margin-bottom: 10px;
            border-radius: 3px;
            font-weight: 600;
            font-size: 11pt;
            page-break-after: avoid;
        }

        .section-header:first-of-type {
            margin-top: 0;
        }

        .info-row {
            display: table;
            width: 100%;
            margin-bottom: 6px;
            padding: 7px 10px;
            background-color: #f9fafb;
            border-radius: 3px;
            page-break-inside: avoid;
        }

        .info-label {
            display: table-cell;
            font-weight: 600;
            color: #4b5563;
            width: 180px;
            font-size: 9.5pt;
        }

        .info-value {
            display: table-cell;
            color: #1f2937;
            font-size: 9.5pt;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 8.5pt;
            font-weight: 600;
        }

        .status-completed, .status-paid, .status-arrived, .status-active { 
            background-color: #d1fae5; 
            color: #065f46; 
        }
        
        .status-pending, .status-rescheduled { 
            background-color: #fef3c7; 
            color: #92400e; 
        }
        
        .status-cancelled, .status-missed { 
            background-color: #fee2e2; 
            color: #991b1b; 
        }

        .text-content-box {
            padding: 10px;
            background-color: #f9fafb;
            border-radius: 4px;
            border-left: 3px solid #3b82f6;
            margin-bottom: 10px;
            line-height: 1.6;
            white-space: pre-wrap;
            page-break-inside: avoid;
            font-size: 9.5pt;
        }

        .signature-section {
            margin-top: 40px;
            page-break-inside: avoid;
        }

        .signature-grid {
            display: table;
            width: 100%;
            margin-top: 15px;
        }

        .signature-box {
            display: table-cell;
            width: 50%;
            text-align: center;
            padding: 0 15px;
        }

        .signature-line {
            border-bottom: 2px solid #1f2937;
            min-height: 35px;
            margin-bottom: 8px;
        }

        .end-notice {
            margin-top: 25px;
            padding: 12px;
            background-color: #f3f4f6;
            border-radius: 5px;
            text-align: center;
            page-break-inside: avoid;
        }

        .referral-info-grid {
            margin-top: 10px;
        }

        .referral-boxes {
            display: table;
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px 0;
        }

        .referral-box {
            display: table-cell;
            width: 50%;
            padding: 10px;
            border-radius: 4px;
            border: 2px solid;
            font-size: 9pt;
        }

        .referral-box.from {
            background-color: #fff7ed;
            border-color: #fb923c;
        }

        .referral-box.to {
            background-color: #eff6ff;
            border-color: #60a5fa;
        }

        .referral-box h4 {
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 10pt;
        }

        .referral-box.from h4 { color: #ea580c; }
        .referral-box.to h4 { color: #2563eb; }

        .ref-detail {
            margin-bottom: 6px;
        }

        .ref-detail-label {
            font-size: 8pt;
            color: #6b7280;
            margin-bottom: 2px;
        }

        .ref-detail-value {
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="page-footer">
        <div class="footer-content">
            <div class="footer-left">
                <strong>{{ $branch->branch_name }}</strong>
            </div>
            <div class="footer-center">
                Generated: {{ \Carbon\Carbon::now()->format('M d, Y - h:i A') }}
            </div>
            <div class="footer-right">
                Page <span class="page-number"></span> of <span class="page-total"></span>
            </div>
        </div>
    </div>

    <div class="content-wrapper">
        <div class="first-page-header">
            <img src="{{ public_path('images/header.jpg') }}" alt="Pets2GO Veterinary Clinic Header">
        </div>

        <div class="report-title-section">
            <div class="report-title">{{ $title }}</div>
            <div class="report-subtitle">{{ $branch->branch_name }}</div>
            <div class="report-id">
                @php
                    $idField = match($reportType) {
                        'appointments' => 'appoint_id',
                        'pets' => 'pet_id',
                        'referrals' => 'ref_id',
                        'billing' => 'bill_id',
                        'sales' => 'ord_id',
                        'equipment' => 'equipment_id',
                        'services' => 'service_id',
                        'inventory' => 'prod_id',
                        default => 'id'
                    };
                @endphp
                Report ID: {{ strtoupper($reportType) }}-{{ str_pad($data->{$idField} ?? '000', 6, '0', STR_PAD_LEFT) }}
            </div>
        </div>

        @if($reportType === 'appointments')
            <div class="section-header">Patient & Owner Information</div>
            <div class="info-row">
                <div class="info-label">Appointment ID:</div>
                <div class="info-value"><strong>{{ $data->appoint_id }}</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Owner Name:</div>
                <div class="info-value">{{ $data->pet->owner->own_name ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Contact Number:</div>
                <div class="info-value">{{ $data->pet->owner->own_contactnum ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Email Address:</div>
                <div class="info-value">{{ $data->pet->owner->own_email ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Address:</div>
                <div class="info-value">{{ $data->pet->owner->own_location ?? 'N/A' }}</div>
            </div>

            <div class="section-header">Pet Information</div>
            <div class="info-row">
                <div class="info-label">Pet Name:</div>
                <div class="info-value"><strong>{{ $data->pet->pet_name ?? 'N/A' }}</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Species:</div>
                <div class="info-value">{{ $data->pet->pet_species ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Breed:</div>
                <div class="info-value">{{ $data->pet->pet_breed ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Age:</div>
                <div class="info-value">{{ $data->pet->pet_age ?? 'N/A' }} years old</div>
            </div>
            <div class="info-row">
                <div class="info-label">Gender:</div>
                <div class="info-value">{{ ucfirst($data->pet->pet_gender ?? 'N/A') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Weight:</div>
                <div class="info-value">{{ $data->pet->pet_weight ?? 'N/A' }} kg</div>
            </div>
            @if($data->pet->pet_temperature)
            <div class="info-row">
                <div class="info-label">Temperature:</div>
                <div class="info-value">{{ $data->pet->pet_temperature }} °C</div>
            </div>
            @endif

            <div class="section-header">Appointment Details</div>
            <div class="info-row">
                <div class="info-label">Appointment Date:</div>
                <div class="info-value"><strong>{{ \Carbon\Carbon::parse($data->appoint_date)->format('l, F d, Y') }}</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Appointment Time:</div>
                <div class="info-value"><strong>{{ \Carbon\Carbon::parse($data->appoint_time)->format('h:i A') }}</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Appointment Type:</div>
                <div class="info-value">{{ ucfirst($data->appoint_type ?? 'N/A') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Veterinarian:</div>
                <div class="info-value">{{ $data->user->name ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Branch Location:</div>
                <div class="info-value">{{ $data->user->branch->branch_name ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Status:</div>
                <div class="info-value">
                    <span class="status-badge status-{{ strtolower($data->appoint_status) }}">
                        {{ strtoupper($data->appoint_status) }}
                    </span>
                </div>
            </div>

            @if($data->appoint_description)
            <div class="section-header">Notes & Description</div>
            <div class="text-content-box">{{ $data->appoint_description }}</div>
            @endif

        @elseif($reportType === 'pets')
            <div class="section-header">Owner Information</div>
            <div class="info-row">
                <div class="info-label">Owner Name:</div>
                <div class="info-value"><strong>{{ $data->owner->own_name ?? 'N/A' }}</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Contact Number:</div>
                <div class="info-value">{{ $data->owner->own_contactnum ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Email Address:</div>
                <div class="info-value">{{ $data->owner->own_email ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Address:</div>
                <div class="info-value">{{ $data->owner->own_location ?? 'N/A' }}</div>
            </div>

            <div class="section-header">Pet Registration Details</div>
            <div class="info-row">
                <div class="info-label">Pet ID:</div>
                <div class="info-value"><strong>{{ $data->pet_id }}</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Pet Name:</div>
                <div class="info-value"><strong>{{ $data->pet_name }}</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Species:</div>
                <div class="info-value">{{ $data->pet_species }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Breed:</div>
                <div class="info-value">{{ $data->pet_breed }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Date of Birth:</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($data->pet_birthdate)->format('F d, Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Age:</div>
                <div class="info-value">{{ $data->pet_age }} years old</div>
            </div>
            <div class="info-row">
                <div class="info-label">Gender:</div>
                <div class="info-value">{{ ucfirst($data->pet_gender) }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Weight:</div>
                <div class="info-value">{{ $data->pet_weight ?? 'N/A' }} kg</div>
            </div>
            @if(isset($data->pet_temperature))
            <div class="info-row">
                <div class="info-label">Temperature:</div>
                <div class="info-value">{{ $data->pet_temperature }} °C</div>
            </div>
            @endif
            <div class="info-row">
                <div class="info-label">Registration Date:</div>
                <div class="info-value"><strong>{{ \Carbon\Carbon::parse($data->pet_registration)->format('l, F d, Y') }}</strong></div>
            </div>

        @elseif($reportType === 'referrals')
            <div class="section-header">Basic Information</div>
            <div class="info-row">
                <div class="info-label">Referral ID:</div>
                <div class="info-value"><strong>{{ $data->ref_id }}</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Referral Date:</div>
                <div class="info-value"><strong>{{ \Carbon\Carbon::parse($data->ref_date)->format('l, F d, Y') }}</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Owner Name:</div>
                <div class="info-value">{{ $data->appointment->pet->owner->own_name ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Contact Number:</div>
                <div class="info-value">{{ $data->appointment->pet->owner->own_contactnum ?? 'N/A' }}</div>
            </div>

            <div class="section-header">Pet Information</div>
            <div class="info-row">
                <div class="info-label">Pet Name:</div>
                <div class="info-value"><strong>{{ $data->appointment->pet->pet_name ?? 'N/A' }}</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Species:</div>
                <div class="info-value">{{ $data->appointment->pet->pet_species ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Breed:</div>
                <div class="info-value">{{ $data->appointment->pet->pet_breed ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Date of Birth:</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($data->appointment->pet->pet_birthdate)->format('F d, Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Gender:</div>
                <div class="info-value">{{ ucfirst($data->appointment->pet->pet_gender ?? 'N/A') }}</div>
            </div>

            <div class="section-header">Medical History</div>
            <div class="text-content-box">{{ $data->medical_history ?? 'No medical history provided' }}</div>

            <div class="section-header">Tests Conducted</div>
            <div class="text-content-box" style="border-left-color: #8b5cf6;">{{ $data->tests_conducted ?? 'No tests documented' }}</div>

            <div class="section-header">Medications Given</div>
            <div class="text-content-box" style="border-left-color: #10b981;">{{ $data->medications_given ?? 'No medications documented' }}</div>

            <div class="section-header">Reason for Referral</div>
            <div class="text-content-box" style="background-color: #fef3c7; border-left-color: #f59e0b;">
                <strong>{{ $data->ref_description ?? 'No reason provided' }}</strong>
            </div>

            <div class="section-header">Referral Information</div>
            <div class="referral-info-grid">
                <div class="referral-boxes">
                    <div class="referral-box from">
                        <h4>Referring Veterinarian</h4>
                        <div class="ref-detail">
                            <div class="ref-detail-label">Veterinarian:</div>
                            <div class="ref-detail-value">{{ $data->appointment->user->name ?? 'N/A' }}</div>
                        </div>
                        <div class="ref-detail">
                            <div class="ref-detail-label">From Branch:</div>
                            <div class="ref-detail-value">{{ $data->appointment->user->branch->branch_name ?? 'N/A' }}</div>
                        </div>
                    </div>

                    <div class="referral-box to">
                        <h4>Referred To</h4>
                        <div class="ref-detail">
                            <div class="ref-detail-label">Branch/Facility:</div>
                            <div class="ref-detail-value" style="color: #2563eb;">{{ $data->ref_to ?? 'N/A' }}</div>
                        </div>
                        <div class="ref-detail">
                            <div class="ref-detail-label">Purpose:</div>
                            <div class="ref-detail-value">Specialist Veterinary Care</div>
                        </div>
                    </div>
                </div>
            </div>

        @elseif($reportType === 'billing')
            <div class="section-header">Customer Information</div>
            <div class="info-row">
                <div class="info-label">Bill ID:</div>
                <div class="info-value"><strong>{{ $data->bill_id }}</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Customer Name:</div>
                <div class="info-value">{{ $data->own_name ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Contact Number:</div>
                <div class="info-value">{{ $data->own_contactnum ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Pet Name:</div>
                <div class="info-value">{{ $data->pet_name ?? 'N/A' }}</div>
            </div>

            <div class="section-header">Billing Details</div>
            <div class="info-row">
                <div class="info-label">Service Date:</div>
                <div class="info-value"><strong>{{ \Carbon\Carbon::parse($data->appoint_date)->format('l, F d, Y') }}</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Bill Date:</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($data->bill_date ?? $data->appoint_date)->format('l, F d, Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Total Amount:</div>
                <div class="info-value"><strong style="color: #059669;">₱{{ number_format($data->pay_total ?? 0, 2) }}</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Payment Status:</div>
                <div class="info-value">
                    <span class="status-badge status-{{ strtolower($data->bill_status ?? 'pending') }}">
                        {{ strtoupper($data->bill_status ?? 'PENDING') }}
                    </span>
                </div>
            </div>

        @elseif($reportType === 'sales')
            <div class="section-header">Customer Information</div>
            <div class="info-row">
                <div class="info-label">Order ID:</div>
                <div class="info-value"><strong>{{ $data->ord_id }}</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Customer Name:</div>
                <div class="info-value">{{ $data->owner->own_name ?? 'Walk-in Customer' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Contact Number:</div>
                <div class="info-value">{{ $data->owner->own_contactnum ?? 'N/A' }}</div>
            </div>

            <div class="section-header">Sales Details</div>
            <div class="info-row">
                <div class="info-label">Sale Date:</div>
                <div class="info-value"><strong>{{ \Carbon\Carbon::parse($data->ord_date)->format('l, F d, Y') }}</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Product Name:</div>
                <div class="info-value">{{ $data->product->prod_name ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Product Description:</div>
                <div class="info-value">{{ $data->product->prod_description ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Quantity Sold:</div>
                <div class="info-value">{{ $data->ord_quantity }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Unit Price:</div>
                <div class="info-value">₱{{ number_format($data->product->prod_price ?? 0, 2) }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Total Amount:</div>
                <div class="info-value"><strong style="color: #059669;">₱{{ number_format($data->ord_total, 2) }}</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Cashier:</div>
                <div class="info-value">{{ $data->user->name ?? 'N/A' }}</div>
            </div>

        @elseif($reportType === 'equipment')
            <div class="section-header">Equipment Details</div>
            <div class="info-row">
                <div class="info-label">Equipment ID:</div>
                <div class="info-value"><strong>{{ $data->equipment_id }}</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Equipment Name:</div>
                <div class="info-value"><strong>{{ $data->equipment_name }}</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Description:</div>
                <div class="info-value">{{ $data->equipment_description ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Quantity:</div>
                <div class="info-value">{{ $data->equipment_quantity }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Branch:</div>
                <div class="info-value">{{ $branch->branch_name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Stock Status:</div>
                <div class="info-value">
                    @php
                        $qty = $data->equipment_quantity;
                        $status = $qty > 10 ? 'Good Stock' : ($qty > 0 ? 'Low Stock' : 'Out of Stock');
                        $statusClass = $qty > 10 ? 'completed' : ($qty > 0 ? 'pending' : 'cancelled');
                    @endphp
                    <span class="status-badge status-{{ $statusClass }}">
                        {{ strtoupper($status) }}
                    </span>
                </div>
            </div>

        @elseif($reportType === 'services')
            <div class="section-header">Service Details</div>
            <div class="info-row">
                <div class="info-label">Service ID:</div>
                <div class="info-value"><strong>{{ $data->service_id }}</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Service Name:</div>
                <div class="info-value"><strong>{{ $data->service_name }}</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Description:</div>
                <div class="info-value">{{ $data->service_description }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Price:</div>
                <div class="info-value"><strong style="color: #059669;">₱{{ number_format($data->service_price, 2) }}</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Branch:</div>
                <div class="info-value">{{ $data->branch->branch_name ?? $branch->branch_name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Status:</div>
                <div class="info-value">
                    <span class="status-badge status-active">ACTIVE</span>
                </div>
            </div>

        @elseif($reportType === 'inventory')
            <div class="section-header">Product Details</div>
            <div class="info-row">
                <div class="info-label">Product ID:</div>
                <div class="info-value"><strong>{{ $data->prod_id }}</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Product Name:</div>
                <div class="info-value"><strong>{{ $data->prod_name }}</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Description:</div>
                <div class="info-value">{{ $data->prod_description }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Quantity in Stock:</div>
                <div class="info-value">{{ $data->prod_quantity }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Unit Price:</div>
                <div class="info-value"><strong style="color: #059669;">₱{{ number_format($data->prod_price, 2) }}</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Total Value:</div>
                <div class="info-value"><strong style="color: #059669;">₱{{ number_format($data->prod_quantity * $data->prod_price, 2) }}</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Stock Status:</div>
                <div class="info-value">
                    @php
                        $qty = $data->prod_quantity;
                        $status = $qty > 20 ? 'Good Stock' : ($qty > 0 ? 'Low Stock' : 'Out of Stock');
                        $statusClass = $qty > 20 ? 'completed' : ($qty > 0 ? 'pending' : 'cancelled');
                    @endphp
                    <span class="status-badge status-{{ $statusClass }}">
                        {{ strtoupper($status) }}
                    </span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Branch:</div>
                <div class="info-value">{{ $data->branch->branch_name ?? $branch->branch_name }}</div>
            </div>
    @endif

    <div class="signature-section">
        <div class="signature-grid">
            <div class="signature-box">
                <div class="signature-line"></div>
                <p style="font-weight: 600; margin-bottom: 3px; font-size: 9.5pt;">Prepared By</p>
                <p style="font-size: 8.5pt; color: #6b7280;">{{ auth()->user()->name }}</p>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <p style="font-weight: 600; margin-bottom: 3px; font-size: 9.5pt;">Verified By</p>
                <p style="font-size: 8.5pt; color: #6b7280;">Branch Manager</p>
            </div>
        </div>
    </div>

    <div class="end-notice">
        <p style="font-size: 10pt; color: #6b7280; font-weight: 600;">— END OF REPORT —</p>
        <p style="font-size: 8pt; color: #9ca3af; margin-top: 5px;">
            This is a computer-generated document. No signature is required.
        </p>
    </div>
</div>
</body>
</html>
