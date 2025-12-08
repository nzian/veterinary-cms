<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Report Details' }} - {{ $branch->branch_name ?? 'Branch Report' }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .header {
            background-color: #f88e28;
            padding: 15px;
            text-align: center;
            margin-bottom: 20px;
            width: 100%;
            box-sizing: border-box;
        }
        .header img {
            max-height: 70px;
            max-width: 100%;
            height: auto;
            width: auto;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }
        .title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 15px;
            text-align: center;
            color: #1f2937;
            text-transform: uppercase;
        }
        .section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        .section-header {
            background-color: #3b82f6;
            color: white;
            padding: 10px;
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 14px;
            page-break-after: avoid; /* Keep header with content */
        }
        .section-header.orange { background-color: #f88e28; }
        .section-header.green { background-color: #059669; }
        .section-header.purple { background-color: #7c3aed; }
        .section-header.yellow { background-color: #eab308; }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 8px;
            table-layout: fixed;
        }
        th {
            padding: 4px 2px;
            border: 1px solid #e5e7eb;
            background-color: #f3f4f6;
            font-weight: bold;
            text-align: left;
            font-size: 7px;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        td {
            padding: 4px 2px;
            border: 1px solid #e5e7eb;
            vertical-align: top;
            font-size: 7px;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        /* Flexible column widths for landscape - auto-size with constraints */
        th, td {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        /* Allow text wrapping for longer content */
        td {
            white-space: normal;
            word-break: break-word;
        }
        /* Number column is always small */
        th:first-child, td:first-child {
            width: 2.5%;
            min-width: 25px;
        }
        /* Date columns */
        th:nth-child(2), td:nth-child(2) {
            width: 6%;
            min-width: 70px;
        }
        /* Name/Owner columns */
        th:nth-child(3), td:nth-child(3) {
            width: 8%;
            min-width: 90px;
        }
        /* Contact/Location columns */
        th:nth-child(4), td:nth-child(4),
        th:nth-child(5), td:nth-child(5) {
            width: 6.5%;
            min-width: 75px;
        }
        /* Pet Name */
        th:nth-child(6), td:nth-child(6) {
            width: 7%;
            min-width: 80px;
        }
        /* Species/Breed */
        th:nth-child(7), td:nth-child(7),
        th:nth-child(8), td:nth-child(8) {
            width: 5.5%;
            min-width: 60px;
        }
        /* Age/Gender */
        th:nth-child(9), td:nth-child(9),
        th:nth-child(10), td:nth-child(10) {
            width: 4.5%;
            min-width: 50px;
        }
        /* Patient Type/Veterinarian/Branch */
        th:nth-child(11), td:nth-child(11),
        th:nth-child(12), td:nth-child(12),
        th:nth-child(13), td:nth-child(13) {
            width: 7%;
            min-width: 80px;
        }
        /* Services - needs more space */
        th:nth-child(14), td:nth-child(14) {
            width: 11%;
            min-width: 120px;
            white-space: normal;
        }
        /* Weight/Temperature */
        th:nth-child(15), td:nth-child(15),
        th:nth-child(16), td:nth-child(16) {
            width: 4.5%;
            min-width: 50px;
        }
        /* Status */
        th:last-child, td:last-child {
            width: 5%;
            min-width: 60px;
        }
        .label {
            font-weight: 600;
            color: #6b7280;
            width: 35%;
            background-color: #f9fafb;
            text-transform: uppercase;
            font-size: 11px;
        }
        .value {
            color: #1f2937;
            font-size: 13px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
             /* Force print colors */
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }
        .status-completed, .status-paid, .status-active, .status-good {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-pending, .status-processing {
            background-color: #fef3c7;
            color: #92400e;
        }
        .status-cancelled, .status-expired, .status-inactive, .status-out {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .status-low {
            background-color: #ffedd5;
            color: #9a3412;
        }
        .amount {
            font-size: 16px; /* Slightly smaller for table cell */
            font-weight: bold;
            color: #059669;
        }
        .text-area {
            padding: 12px;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            min-height: 50px;
            white-space: pre-wrap;
            font-size: 12px;
            line-height: 1.5;
        }
        /* Page numbering for consistency - Landscape for PDF view to fit all details */
        @page {
            size: letter landscape;
            margin: 8mm 10mm 15mm 10mm;
            counter-increment: page;
            @bottom-right {
                content: "Page " counter(page) " of " counter(pages);
                font-size: 9px;
                color: #6b7280;
            }
        }
        
        /* Landscape for print */
        @media print {
            @page {
                size: letter landscape;
                margin: 8mm;
            }
            .header img {
                max-width: 100%;
                height: auto;
            }
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    @php
        // Map data structure from eloquent objects/db result to unified variable $record
        $record = $data;

        // Helper function definitions from your desired template:
        function getStatusClass($status) {
            if (!$status) return '';
            $statusLower = strtolower($status);
            if (in_array($statusLower, ['completed', 'paid', 'active', 'good', 'good stock'])) {
                return 'status-completed';
            } elseif (in_array($statusLower, ['pending', 'processing', 'low stock'])) {
                return 'status-pending';
            } elseif (in_array($statusLower, ['cancelled', 'expired', 'inactive', 'out of stock'])) {
                return 'status-cancelled';
            } elseif (in_array($statusLower, ['low stock', 'expiring soon'])) {
                return 'status-low';
            }
            return '';
        }

        function formatDate($date) {
            if (!$date) return 'N/A';
            try {
                return \Carbon\Carbon::parse($date)->format('F d, Y');
            } catch (\Exception $e) {
                return 'N/A';
            }
        }

        function formatTime($time) {
            if (!$time) return 'N/A';
            try {
                return \Carbon\Carbon::parse($time)->format('h:i A');
            } catch (\Exception $e) {
                return 'N/A';
            }
        }

        function getField($record, ...$fields) {
            foreach ($fields as $field) {
                // Handle dot notation (e.g., 'user.branch.branch_name')
                if (strpos($field, '.') !== false) {
                    $parts = explode('.', $field);
                    $value = $record;
                    foreach ($parts as $part) {
                        if (is_object($value) && isset($value->$part)) {
                            $value = $value->$part;
                        } else {
                            $value = null;
                            break;
                        }
                    }
                    if ($value !== null && $value !== '') {
                        return $value;
                    }
                    continue;
                }
                
                // Direct field access
                if (is_object($record) && isset($record->$field) && $record->$field !== null && $record->$field !== '') {
                    return $record->$field;
                }
            }
            return null;
        }
    @endphp

    <div class="header">
        <img src="{{ public_path('images/header.jpg') }}" alt="Pets2GO Veterinary Clinic">
    </div>

    <div class="title">{{ $title ?? 'REPORT DETAILS' }}</div>
    <p style="text-align: center; font-size: 12px; color: #6b7280; margin-top: -15px;">
        <strong>Branch:</strong> {{ isset($branch) && $branch ? ($branch->branch_name ?? 'N/A') : 'N/A' }} | <strong>Generated:</strong> {{ \Carbon\Carbon::now()->format('F d, Y h:i A') }}
    </p>

    {{-- Enhanced Table for All Report Types --}}
    @if(in_array($reportType, ['visits', 'pets', 'billing', 'sales', 'referrals', 'equipment', 'services', 'inventory']))
    <div class="section">
        <div class="section-header">{{ $title ?? 'Report' }}</div>
        <table>
            <thead>
                @if($reportType === 'visits')
                <tr>
                    <th>#</th>
                    <th>Visit Date</th>
                    <th>Owner Name</th>
                    <th>Contact</th>
                    <th>Location</th>
                    <th>Pet Name</th>
                    <th>Species</th>
                    <th>Breed</th>
                    <th>Age</th>
                    <th>Gender</th>
                    <th>Patient Type</th>
                    <th>Veterinarian</th>
                    <th>Branch</th>
                    <th>Services</th>
                    <th>Weight</th>
                    <th>Temperature</th>
                    <th>Status</th>
                </tr>
                @elseif($reportType === 'pets')
                <tr>
                    <th>#</th>
                    <th>Registration Date</th>
                    <th>Owner Name</th>
                    <th>Contact</th>
                    <th>Location</th>
                    <th>Pet Name</th>
                    <th>Species</th>
                    <th>Breed</th>
                    <th>Age</th>
                    <th>Gender</th>
                    <th>Birthdate</th>
                </tr>
                @elseif($reportType === 'billing')
                <tr>
                    <th>#</th>
                    <th>Service Date</th>
                    <th>Bill Date</th>
                    <th>Pet Owner</th>
                    <th>Contact</th>
                    <th>Pet Name</th>
                    <th>Species</th>
                    <th>Veterinarian</th>
                    <th>Branch</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
                @elseif($reportType === 'sales')
                <tr>
                    <th>#</th>
                    <th>Sale Date</th>
                    <th>Customer</th>
                    <th>Contact</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Total Amount</th>
                    <th>Cashier</th>
                    <th>Branch</th>
                </tr>
                @elseif($reportType === 'referrals')
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Owner</th>
                    <th>Contact</th>
                    <th>Pet</th>
                    <th>Species</th>
                    <th>Breed</th>
                    <th>Reason</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Referred By</th>
                    <th>Referred To</th>
                </tr>
                @elseif($reportType === 'equipment')
                <tr>
                    <th>#</th>
                    <th>Equipment Name</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Branch</th>
                    <th>Total In Use</th>
                    <th>Total Maintenance</th>
                    <th>Total Available</th>
                    <th>Total Out of Service</th>
                </tr>
                @elseif($reportType === 'services')
                <tr>
                    <th>#</th>
                    <th>Service Name</th>
                    <th>Service Type</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Branch</th>
                    <th>Branch Address</th>
                    <th>Status</th>
                </tr>
                @elseif($reportType === 'inventory')
                <tr>
                    <th>#</th>
                    <th>Product Name</th>
                    <th>Product Type</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Total Pull Out</th>
                    <th>Total Damage</th>
                    <th>Total Stocks</th>
                    <th>Unit Price</th>
                    <th>Branch</th>
                    <th>Branch Address</th>
                    <th>Stock Status</th>
                </tr>
                @endif
            </thead>
            <tbody>
                @if(is_iterable($data))
                    @foreach($data as $i => $row)
                        @if(is_object($row))
                        <tr>
                            @if($reportType === 'visits')
                                <td>{{ ((int) $i) + 1 }}</td>
                                <td>{{ $row->visit_date ? \Carbon\Carbon::parse($row->visit_date)->format('M d, Y') : 'N/A' }}</td>
                                <td>{{ $row->owner_name ?? ($row->pet->owner->own_name ?? 'N/A') }}</td>
                                <td>{{ $row->owner_contact ?? ($row->pet->owner->own_contactnum ?? 'N/A') }}</td>
                                <td>{{ $row->owner_location ?? ($row->pet->owner->own_location ?? 'N/A') }}</td>
                                <td>{{ $row->pet_name ?? ($row->pet->pet_name ?? 'N/A') }}</td>
                                <td>{{ $row->pet_species ?? ($row->pet->pet_species ?? 'N/A') }}</td>
                                <td>{{ $row->pet_breed ?? ($row->pet->pet_breed ?? 'N/A') }}</td>
                                <td>{{ $row->pet_age ?? ($row->pet->pet_age ?? 'N/A') }}</td>
                                <td>{{ ucfirst($row->pet_gender ?? ($row->pet->pet_gender ?? 'N/A')) }}</td>
                                <td>{{ is_string($row->patient_type) ? ucfirst($row->patient_type) : ($row->patient_type->value ?? 'N/A') }}</td>
                                <td>{{ $row->veterinarian ?? ($row->user->user_name ?? 'N/A') }}</td>
                                <td>{{ $row->branch_name ?? ($row->user->branch->branch_name ?? 'N/A') }}</td>
                                <td>{{ $row->services ?? 'No services' }}</td>
                                <td>{{ $row->weight ?? 'N/A' }}</td>
                                <td>{{ $row->temperature ?? 'N/A' }}</td>
                                <td>{{ ucfirst($row->status ?? 'N/A') }}</td>
                            @elseif($reportType === 'pets')
                                <td>{{ ((int) $i) + 1 }}</td>
                                <td>{{ $row->registration_date ? \Carbon\Carbon::parse($row->registration_date)->format('M d, Y') : 'N/A' }}</td>
                                <td>{{ $row->owner_name ?? ($row->owner->own_name ?? 'N/A') }}</td>
                                <td>{{ $row->owner_contact ?? ($row->owner->own_contactnum ?? 'N/A') }}</td>
                                <td>{{ $row->owner_location ?? ($row->owner->own_location ?? 'N/A') }}</td>
                                <td>{{ $row->pet_name ?? 'N/A' }}</td>
                                <td>{{ $row->pet_species ?? 'N/A' }}</td>
                                <td>{{ $row->pet_breed ?? 'N/A' }}</td>
                                <td>{{ $row->pet_age ?? 'N/A' }}</td>
                                <td>{{ ucfirst($row->pet_gender ?? 'N/A') }}</td>
                                <td>{{ $row->pet_birthdate ? \Carbon\Carbon::parse($row->pet_birthdate)->format('M d, Y') : 'N/A' }}</td>
                            @elseif($reportType === 'billing')
                                <td>{{ ((int) $i) + 1 }}</td>
                                <td>{{ formatDate($row->service_date) }}</td>
                                <td>{{ formatDate($row->bill_date ?? null) }}</td>
                                <td>{{ $row->customer_name ?? 'N/A' }}</td>
                                <td>{{ $row->owner_contact ?? 'N/A' }}</td>
                                <td>{{ $row->pet_name ?? 'N/A' }}</td>
                                <td>{{ $row->pet_species ?? 'N/A' }}</td>
                                <td>{{ $row->veterinarian ?? 'N/A' }}</td>
                                <td>{{ $row->branch_name ?? 'N/A' }}</td>
                                <td>₱{{ number_format($row->pay_total ?? 0, 2) }}</td>
                                <td>{{ ucfirst($row->payment_status ?? 'N/A') }}</td>
                            @elseif($reportType === 'sales')
                                <td>{{ ((int) $i) + 1 }}</td>
                                <td>{{ formatDate($row->sale_date) }}</td>
                                <td>{{ $row->customer_name ?? 'Walk-in' }}</td>
                                <td>{{ $row->customer_contact ?? 'N/A' }}</td>
                                <td>{{ $row->product_name ?? 'N/A' }}</td>
                                <td>{{ $row->product_category ?? 'N/A' }}</td>
                                <td>{{ $row->product_description ?? 'N/A' }}</td>
                                <td>{{ $row->quantity_sold ?? 0 }}</td>
                                <td>₱{{ number_format($row->unit_price ?? 0, 2) }}</td>
                                <td>₱{{ number_format($row->total_amount ?? 0, 2) }}</td>
                                <td>{{ $row->cashier ?? 'N/A' }}</td>
                                <td>{{ $row->branch_name ?? 'N/A' }}</td>
                            @elseif($reportType === 'referrals')
                                <td>{{ ((int) $i) + 1 }}</td>
                                <td>{{ formatDate($row->ref_date) }}</td>
                                <td>{{ $row->owner_name ?? 'N/A' }}</td>
                                <td>{{ $row->owner_contact ?? 'N/A' }}</td>
                                <td>{{ $row->pet_name ?? 'N/A' }}</td>
                                <td>{{ $row->pet_species ?? 'N/A' }}</td>
                                <td>{{ $row->pet_breed ?? 'N/A' }}</td>
                                <td>{{ $row->referral_reason ?? 'N/A' }}</td>
                                <td>{{ $row->ref_type ?? 'N/A' }}</td>
                                <td>{{ $row->ref_status ?? 'N/A' }}</td>
                                <td>{{ $row->referred_by ?? 'N/A' }}</td>
                                <td>{{ $row->referred_to ?? 'N/A' }}</td>
                            @elseif($reportType === 'equipment')
                                <td>{{ ((int) $i) + 1 }}</td>
                                <td>{{ $row->equipment_name ?? 'N/A' }}</td>
                                <td>{{ $row->equipment_category ?? 'N/A' }}</td>
                                <td>{{ $row->equipment_description ?? 'N/A' }}</td>
                                <td>{{ $row->branch_name ?? 'N/A' }}</td>
                                <td>{{ $row->total_in_use ?? 0 }}</td>
                                <td>{{ $row->total_maintenance ?? 0 }}</td>
                                <td>{{ $row->total_available ?? 0 }}</td>
                                <td>{{ $row->total_out_of_service ?? 0 }}</td>
                            @elseif($reportType === 'services')
                                <td>{{ ((int) $i) + 1 }}</td>
                                <td>{{ $row->service_name ?? 'N/A' }}</td>
                                <td>{{ $row->service_type ?? 'General' }}</td>
                                <td>{{ $row->service_description ?? 'N/A' }}</td>
                                <td>₱{{ number_format($row->service_price ?? 0, 2) }}</td>
                                <td>{{ $row->branch_name ?? 'N/A' }}</td>
                                <td>{{ $row->branch_address ?? 'N/A' }}</td>
                                <td>{{ $row->status ?? 'Active' }}</td>
                            @elseif($reportType === 'inventory')
                                <td>{{ ((int) $i) + 1 }}</td>
                                <td>{{ $row->product_name ?? 'N/A' }}</td>
                                <td>{{ $row->product_type ?? 'N/A' }}</td>
                                <td>{{ $row->product_category ?? 'N/A' }}</td>
                                <td>{{ $row->product_description ?? 'N/A' }}</td>
                                <td>{{ $row->total_pull_out ?? 0 }}</td>
                                <td>{{ $row->total_damage ?? 0 }}</td>
                                <td>{{ $row->total_stocks ?? 0 }}</td>
                                <td>{{ $row->unit_price ? '₱' . number_format($row->unit_price, 2) : 'N/A' }}</td>
                                <td>{{ $row->branch_name ?? 'N/A' }}</td>
                                <td>{{ $row->branch_address ?? 'N/A' }}</td>
                                <td>{{ $row->stock_status ?? 'N/A' }}</td>
                            @endif
                        </tr>
                        @endif
                    @endforeach
                @elseif(is_object($data))
                    <tr>
                        @if($reportType === 'visits')
                            <td>1</td>
                            <td>{{ $data->visit_date ? \Carbon\Carbon::parse($data->visit_date)->format('M d, Y') : 'N/A' }}</td>
                            <td>{{ $data->owner_name ?? 'N/A' }}</td>
                            <td>{{ $data->owner_contact ?? 'N/A' }}</td>
                            <td>{{ $data->owner_location ?? 'N/A' }}</td>
                            <td>{{ $data->pet_name ?? 'N/A' }}</td>
                            <td>{{ $data->pet_species ?? 'N/A' }}</td>
                            <td>{{ $data->pet_breed ?? 'N/A' }}</td>
                            <td>{{ $data->pet_age ?? 'N/A' }}</td>
                            <td>{{ ucfirst($data->pet_gender ?? 'N/A') }}</td>
                            <td>{{ is_string($data->patient_type) ? ucfirst($data->patient_type) : ($data->patient_type ?? 'N/A') }}</td>
                            <td>{{ $data->veterinarian ?? 'N/A' }}</td>
                            <td>{{ $data->branch_name ?? 'N/A' }}</td>
                            <td>{{ $data->services ?? 'No services' }}</td>
                            <td>{{ $data->weight ?? 'N/A' }}</td>
                            <td>{{ $data->temperature ?? 'N/A' }}</td>
                            <td>{{ ucfirst($data->status ?? 'N/A') }}</td>
                        @elseif($reportType === 'pets')
                            <td>1</td>
                            <td>{{ $data->registration_date ? \Carbon\Carbon::parse($data->registration_date)->format('M d, Y') : 'N/A' }}</td>
                            <td>{{ $data->owner_name ?? 'N/A' }}</td>
                            <td>{{ $data->owner_contact ?? 'N/A' }}</td>
                            <td>{{ $data->owner_location ?? 'N/A' }}</td>
                            <td>{{ $data->pet_name ?? 'N/A' }}</td>
                            <td>{{ $data->pet_species ?? 'N/A' }}</td>
                            <td>{{ $data->pet_breed ?? 'N/A' }}</td>
                            <td>{{ $data->pet_age ?? 'N/A' }}</td>
                            <td>{{ ucfirst($data->pet_gender ?? 'N/A') }}</td>
                            <td>{{ $data->pet_birthdate ? \Carbon\Carbon::parse($data->pet_birthdate)->format('M d, Y') : 'N/A' }}</td>
                        @elseif($reportType === 'billing')
                            <td>1</td>
                            <td>{{ formatDate($data->service_date) }}</td>
                            <td>{{ formatDate($data->bill_date ?? null) }}</td>
                            <td>{{ $data->customer_name ?? 'N/A' }}</td>
                            <td>{{ $data->owner_contact ?? 'N/A' }}</td>
                            <td>{{ $data->pet_name ?? 'N/A' }}</td>
                            <td>{{ $data->pet_species ?? 'N/A' }}</td>
                            <td>{{ $data->veterinarian ?? 'N/A' }}</td>
                            <td>{{ $data->branch_name ?? 'N/A' }}</td>
                            <td>₱{{ number_format($data->pay_total ?? 0, 2) }}</td>
                            <td>{{ ucfirst($data->payment_status ?? 'N/A') }}</td>
                        @elseif($reportType === 'sales')
                            <td>1</td>
                            <td>{{ formatDate($data->sale_date) }}</td>
                            <td>{{ $data->customer_name ?? 'Walk-in' }}</td>
                            <td>{{ $data->customer_contact ?? 'N/A' }}</td>
                            <td>{{ $data->product_name ?? 'N/A' }}</td>
                            <td>{{ $data->product_category ?? 'N/A' }}</td>
                            <td>{{ $data->product_description ?? 'N/A' }}</td>
                            <td>{{ $data->quantity_sold ?? 0 }}</td>
                            <td>₱{{ number_format($data->unit_price ?? 0, 2) }}</td>
                            <td>₱{{ number_format($data->total_amount ?? 0, 2) }}</td>
                            <td>{{ $data->cashier ?? 'N/A' }}</td>
                            <td>{{ $data->branch_name ?? 'N/A' }}</td>
                        @elseif($reportType === 'referrals')
                            <td>1</td>
                            <td>{{ formatDate($data->ref_date) }}</td>
                            <td>{{ $data->owner_name ?? 'N/A' }}</td>
                            <td>{{ $data->owner_contact ?? 'N/A' }}</td>
                            <td>{{ $data->pet_name ?? 'N/A' }}</td>
                            <td>{{ $data->pet_species ?? 'N/A' }}</td>
                            <td>{{ $data->pet_breed ?? 'N/A' }}</td>
                            <td>{{ $data->referral_reason ?? 'N/A' }}</td>
                            <td>{{ $data->ref_type ?? 'N/A' }}</td>
                            <td>{{ $data->ref_status ?? 'N/A' }}</td>
                            <td>{{ $data->referred_by ?? 'N/A' }}</td>
                            <td>{{ $data->referred_to ?? 'N/A' }}</td>
                        @elseif($reportType === 'equipment')
                            <td>1</td>
                            <td>{{ $data->equipment_name ?? 'N/A' }}</td>
                            <td>{{ $data->equipment_category ?? 'N/A' }}</td>
                            <td>{{ $data->equipment_description ?? 'N/A' }}</td>
                            <td>{{ $data->branch_name ?? 'N/A' }}</td>
                            <td>{{ $data->total_in_use ?? 0 }}</td>
                            <td>{{ $data->total_maintenance ?? 0 }}</td>
                            <td>{{ $data->total_available ?? 0 }}</td>
                            <td>{{ $data->total_out_of_service ?? 0 }}</td>
                        @elseif($reportType === 'services')
                            <td>1</td>
                            <td>{{ $data->service_name ?? 'N/A' }}</td>
                            <td>{{ $data->service_type ?? 'General' }}</td>
                            <td>{{ $data->service_description ?? 'N/A' }}</td>
                            <td>₱{{ number_format($data->service_price ?? 0, 2) }}</td>
                            <td>{{ $data->branch_name ?? 'N/A' }}</td>
                            <td>{{ $data->branch_address ?? 'N/A' }}</td>
                            <td>{{ $data->status ?? 'Active' }}</td>
                        @elseif($reportType === 'inventory')
                            <td>1</td>
                            <td>{{ $data->product_name ?? 'N/A' }}</td>
                            <td>{{ $data->product_type ?? 'N/A' }}</td>
                            <td>{{ $data->product_category ?? 'N/A' }}</td>
                            <td>{{ $data->product_description ?? 'N/A' }}</td>
                            <td>{{ $data->total_pull_out ?? 0 }}</td>
                            <td>{{ $data->total_damage ?? 0 }}</td>
                            <td>{{ $data->total_stocks ?? 0 }}</td>
                            <td>{{ $data->unit_price ? '₱' . number_format($data->unit_price, 2) : 'N/A' }}</td>
                            <td>{{ $data->branch_name ?? 'N/A' }}</td>
                            <td>{{ $data->branch_address ?? 'N/A' }}</td>
                            <td>{{ $data->stock_status ?? 'N/A' }}</td>
                        @endif
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
    @else
        {{-- Appointment & Handler Information --}}
        <div class="section">
            <div class="section-header">Appointment & Handler Information</div>
            <table>
                <tr>
                    <td class="label">Appointment ID</td>
                    <td class="value">{{ getField($record, 'appoint_id') ?? 'N/A' }}</td>
                    <td class="label">Branch</td>
                    <td class="value">{{ getField($record, 'branch_name', 'user.branch.branch_name') ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Date</td>
                    <td class="value">{{ formatDate(getField($record, 'appoint_date')) }}</td>
                    <td class="label">Time</td>
                    <td class="value">{{ formatTime(getField($record, 'appoint_time')) }}</td>
                </tr>
                <tr>
                    <td class="label">Status</td>
                    <td class="value">{{ ucfirst(getField($record, 'appoint_status')) }}</td>
                    <td class="label">Veterinarian</td>
                    <td class="value">{{ getField($record, 'user.name') ?? 'N/A' }}</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-header orange">Patient & Owner Information</div>
            <table>
                <tr>
                    <td class="label">Owner Name</td>
                    <td class="value">{{ getField($record, 'pet.owner.own_name') ?? 'N/A' }}</td>
                    <td class="label">Contact Number</td>
                    <td class="value">{{ getField($record, 'pet.owner.own_contactnum') ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Pet Name</td>
                    <td class="value">{{ getField($record, 'pet.pet_name') ?? 'N/A' }}</td>
                    <td class="label">Species</td>
                    <td class="value">{{ getField($record, 'pet.pet_species') ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Breed</td>
                    <td class="value">{{ getField($record, 'pet.pet_breed') ?? 'N/A' }}</td>
                    <td class="label">Age</td>
                    <td class="value">{{ getField($record, 'pet.pet_age') ?? 'N/A' }} years</td>
                </tr>
            </table>
        </div>

        @if(getField($record, 'appoint_description'))
        <div class="section">
            <div class="section-header">Description/Notes</div>
            <div class="text-area">{{ getField($record, 'appoint_description') }}</div>
        </div>
        @endif
    @endif

    {{-- Fallback for unknown report types --}}
    @if(!in_array($reportType, ['visits', 'pets', 'billing', 'sales', 'referrals', 'equipment', 'services', 'inventory']))
        <div class="section">
            <div class="section-header orange">Report Details ({{ ucfirst($reportType) }})</div>
            <div class="text-area" style="border-left-color: #f88e28;">
                <p>No specific layout is defined for this report type. Displaying raw data:</p>
                <pre style="font-size: 10px; line-height: 1.4;">{{ print_r($record, true) }}</pre>
            </div>
        </div>
    @endif

    <div class="footer">
        Generated by Multi-Branch Veterinary Clinic Management System | Pets2GO Veterinary Clinic<br>
    </div>
</body>
</html>