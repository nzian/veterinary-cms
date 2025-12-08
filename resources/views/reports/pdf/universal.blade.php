<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>{{ $title ?? 'Report Details' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
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
        
        /* Landscape for PDF view to fit all details */
        @page {
            size: letter landscape;
            margin: 8mm 10mm 15mm 10mm;
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
        .title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
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
        }
        .section-header.orange { background-color: #f88e28; }
        .section-header.green { background-color: #059669; }
        .section-header.purple { background-color: #7c3aed; }
        .section-header.yellow { background-color: #eab308; }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 7px;
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
        }
        /* Allow text wrapping for longer content */
        td {
            white-space: normal;
            word-break: break-word;
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
        }
        .status-completed, .status-paid, .status-active, .status-good {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-pending, .status-processing {
            background-color: #fef3c7;
            color: #92400e;
        }
        .status-cancelled, .status-expired, .status-inactive {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .status-low {
            background-color: #ffedd5;
            color: #9a3412;
        }
        .amount {
            font-size: 18px;
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
        .highlight-box {
            background-color: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            margin-bottom: 15px;
        }
        .highlight-box.orange {
            background-color: #fff7ed;
            border-left-color: #f88e28;
        }
        .highlight-box.green {
            background-color: #ecfdf5;
            border-left-color: #059669;
        }
        .grid-2 {
            width: 100%;
        }
        .grid-2 table {
            width: 100%;
        }
        .grid-2 .col {
            width: 48%;
            padding: 10px;
            vertical-align: top;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
        }
        h4 {
            margin: 0 0 10px 0;
            color: #1f2937;
            font-size: 14px;
        }
    </style>
</head>
<body>
    @php
        // Helper function to format status
        function getStatusClass($status) {
            if (!$status) return '';
            $statusLower = strtolower($status);
            if (in_array($statusLower, ['completed', 'paid', 'active', 'good', 'good stock'])) {
                return 'status-completed';
            } elseif (in_array($statusLower, ['pending', 'processing'])) {
                return 'status-pending';
            } elseif (in_array($statusLower, ['cancelled', 'expired', 'inactive', 'out of stock'])) {
                return 'status-cancelled';
            } elseif (in_array($statusLower, ['low stock', 'expiring soon'])) {
                return 'status-low';
            }
            return '';
        }

        // Helper function to format date
        function formatDate($date) {
            if (!$date) return 'N/A';
            try {
                return \Carbon\Carbon::parse($date)->format('F d, Y');
            } catch (\Exception $e) {
                return 'N/A';
            }
        }

        // Helper function to format time
        function formatTime($time) {
            if (!$time) return 'N/A';
            try {
                return \Carbon\Carbon::parse($time)->format('h:i A');
            } catch (\Exception $e) {
                return 'N/A';
            }
        }

        // Helper function to check if field exists
        function getField($record, ...$fields) {
            if (!$record) {
                return null;
            }
            
            foreach ($fields as $field) {
                // Handle dot notation (e.g., 'user.branch.branch_name')
                if (strpos($field, '.') !== false) {
                    $parts = explode('.', $field);
                    $value = $record;
                    foreach ($parts as $part) {
                        if (is_object($value) && isset($value->$part)) {
                            $value = $value->$part;
                        } elseif (is_array($value) && isset($value[$part])) {
                            $value = $value[$part];
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
                
                // Handle object
                if (is_object($record) && isset($record->$field) && $record->$field !== null && $record->$field !== '') {
                    return $record->$field;
                }
                
                // Handle array
                if (is_array($record) && isset($record[$field]) && $record[$field] !== null && $record[$field] !== '') {
                    return $record[$field];
                }
            }
            return null;
        }

        // Helper to render field value
        function renderValue($key, $value) {
            if (str_contains($key, 'date')) {
                return formatDate($value);
            } elseif (str_contains($key, 'time')) {
                return formatTime($value);
            } elseif (str_contains($key, 'price') || str_contains($key, 'amount') || str_contains($key, 'total') || str_contains($key, 'revenue')) {
                return '<span class="amount">PHP ' . number_format($value, 2) . '</span>';
            } elseif (str_contains($key, 'status')) {
                return '<span class="status-badge ' . getStatusClass($value) . '">' . ucfirst($value) . '</span>';
            }
            return $value;
        }
    @endphp

    <!-- Header -->
    <div class="header">
        @php
            $headerImage = public_path('images/header.jpg');
            $headerExists = file_exists($headerImage);
        @endphp
        @if($headerExists)
        <img src="{{ $headerImage }}" alt="Pets2GO Veterinary Clinic">
        @else
        <div style="color: white; font-size: 24px; font-weight: bold;">Pets2GO Veterinary Clinic</div>
        @endif
    </div>

    <div class="title">{{ $title ?? 'REPORT DETAILS' }}</div>
    <p style="text-align: center; font-size: 12px; color: #6b7280; margin-top: -15px;">
        <strong>Generated:</strong> {{ \Carbon\Carbon::now()->format('F d, Y h:i A') }}
    </p>
    
    @if(!isset($record) || !$record)
        <div class="section">
            <div class="section-header">Error</div>
            <p>Record not found or data unavailable.</p>
        </div>
    @elseif(in_array($reportType, ['visits', 'branch_visits', 'multi_service_visits']))
        <div class="section">
            <div class="section-header">{{ $title ?? 'Visit Details' }}</div>
            <table>
                <thead>
                    <tr>
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
                </thead>
                <tbody>
                    <tr>
                        <td>{{ formatDate(getField($record, 'visit_date')) }}</td>
                        <td>{{ getField($record, 'owner_name', 'own_name') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'owner_contact', 'own_contactnum') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'owner_location', 'own_location') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'pet_name') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'pet_species') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'pet_breed') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'pet_age') ?? 'N/A' }}</td>
                        <td>{{ ucfirst(getField($record, 'pet_gender') ?? 'N/A') }}</td>
                        <td>{{ getField($record, 'patient_type') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'veterinarian', 'user_name') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'branch_name') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'services') ?? 'No services' }}</td>
                        <td>{{ getField($record, 'weight') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'temperature') ?? 'N/A' }}</td>
                        <td>
                            <span class="status-badge {{ getStatusClass(getField($record, 'status', 'visit_status')) }}">
                                {{ ucfirst(getField($record, 'status', 'visit_status') ?? 'N/A') }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    @elseif(in_array($reportType, ['appointments', 'branch_appointments']))
        <!-- Patient & Owner Information -->
        <div class="section">
            <div class="section-header">Patient & Owner Information</div>
            <table>
                <tr>
                    <td class="label">Owner Name</td>
                    <td class="value">{{ getField($record, 'own_name', 'owner_name') ?? 'N/A' }}</td>
                    <td class="label">Contact Number</td>
                    <td class="value">{{ getField($record, 'own_contactnum', 'owner_contact') ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Pet Name</td>
                    <td class="value">{{ getField($record, 'pet_name') ?? 'N/A' }}</td>
                    <td class="label">Species</td>
                    <td class="value">{{ getField($record, 'pet_species') ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Breed</td>
                    <td class="value" colspan="3">{{ getField($record, 'pet_breed') ?? 'N/A' }}</td>
                </tr>
            </table>
        </div>

        <!-- Appointment Information -->
        <div class="section">
            <div class="section-header">Appointment Information</div>
            <table>
                <tr>
                    <td class="label">Appointment ID</td>
                    <td class="value">{{ getField($record, 'appoint_id') ?? 'N/A' }}</td>
                    <td class="label">Type</td>
                    <td class="value">{{ getField($record, 'appoint_type', 'appointment_type') ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Date</td>
                    <td class="value">{{ formatDate(getField($record, 'appoint_date', 'appointment_date')) }}</td>
                    <td class="label">Time</td>
                    <td class="value">{{ formatTime(getField($record, 'appoint_time', 'appointment_time')) }}</td>
                </tr>
                <tr>
                    <td class="label">Branch</td>
                    <td class="value">{{ getField($record, 'branch_name') ?? 'N/A' }}</td>
                    <td class="label">Veterinarian</td>
                    <td class="value">{{ getField($record, 'user_name', 'handled_by', 'veterinarian') ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Status</td>
                    <td class="value" colspan="3">
                        <span class="status-badge {{ getStatusClass(getField($record, 'appoint_status', 'status')) }}">
                            {{ ucfirst(getField($record, 'appoint_status', 'status') ?? 'Pending') }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>

        @if(getField($record, 'appoint_description', 'description'))
        <div class="section">
            <div class="section-header">Notes</div>
            <div class="text-area">{{ getField($record, 'appoint_description', 'description') }}</div>
        </div>
        @endif

    {{-- REFERRALS REPORT --}}
    @elseif(in_array($reportType, ['referrals', 'referral_medical']))
        <!-- Basic Information -->
        <div class="section">
            <div class="section-header">Basic Information</div>
            <table>
                <tr>
                    <td class="label">Referral ID</td>
                    <td class="value">{{ getField($record, 'ref_id') ?? 'N/A' }}</td>
                    <td class="label">Date</td>
                    <td class="value">{{ formatDate(getField($record, 'ref_date')) }}</td>
                </tr>
                <tr>
                    <td class="label">Owner Name</td>
                    <td class="value">{{ getField($record, 'own_name', 'owner_name') ?? 'N/A' }}</td>
                    <td class="label">Contact</td>
                    <td class="value">{{ getField($record, 'own_contactnum', 'owner_contact') ?? 'N/A' }}</td>
                </tr>
            </table>
        </div>

        <!-- Pet Information -->
        <div class="section">
            <div class="section-header">Pet Information</div>
            <table>
                <tr>
                    <td class="label">Pet Name</td>
                    <td class="value">{{ getField($record, 'pet_name') ?? 'N/A' }}</td>
                    <td class="label">Date of Birth</td>
                    <td class="value">{{ formatDate(getField($record, 'pet_birthdate')) }}</td>
                </tr>
                <tr>
                    <td class="label">Species</td>
                    <td class="value">{{ getField($record, 'pet_species') ?? 'N/A' }}</td>
                    <td class="label">Breed</td>
                    <td class="value">{{ getField($record, 'pet_breed') ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Gender</td>
                    <td class="value" colspan="3">
                        <span class="status-badge">{{ ucfirst(getField($record, 'pet_gender') ?? 'N/A') }}</span>
                    </td>
                </tr>
            </table>
        </div>

        @if(getField($record, 'medical_history'))
        <div class="section">
            <div class="section-header">Medical History</div>
            <div class="text-area">{{ getField($record, 'medical_history') }}</div>
        </div>
        @endif

        @if(getField($record, 'tests_conducted'))
        <div class="section">
            <div class="section-header">Tests Conducted</div>
            <div class="text-area">{{ getField($record, 'tests_conducted') }}</div>
        </div>
        @endif

        @if(getField($record, 'medications_given'))
        <div class="section">
            <div class="section-header">Medications Given</div>
            <div class="text-area">{{ getField($record, 'medications_given') }}</div>
        </div>
        @endif

        <div class="section">
            <div class="section-header orange">Reason for Referral</div>
            <div class="text-area">{{ getField($record, 'ref_description', 'referral_reason') ?? 'No reason provided' }}</div>
        </div>

        <!-- Referral Details -->
        <div class="section">
            <div class="section-header">Referral Information</div>
            <table class="grid-2">
                <tr>
                    <td class="col">
                        <div class="highlight-box orange">
                            <h4>Referred By</h4>
                            <strong>{{ getField($record, 'ref_by', 'referred_by') ?? 'N/A' }}</strong>
                        </div>
                    </td>
                    <td class="col">
                        <div class="highlight-box green">
                            <h4>Referred To</h4>
                            <strong>{{ getField($record, 'ref_to', 'referred_to') ?? 'N/A' }}</strong>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

    {{-- OWNER & PETS REPORT --}}
    @elseif($reportType == 'owner_pets')
        <div class="section">
            <div class="section-header">{{ $title ?? 'Owner & Pets Details' }}</div>
            <table>
                <thead>
                    <tr>
                        <th>Owner Name</th>
                        <th>Contact</th>
                        <th>Location</th>
                        <th>Total Pets</th>
                        <th>Pet Names</th>
                        <th>Species</th>
                        <th>Breeds</th>
                        <th>Ages</th>
                        <th>Genders</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ getField($record, 'own_name', 'owner_name') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'own_contactnum', 'owner_contact') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'own_location', 'owner_location') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'total_pets') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'pet_names') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'pet_species') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'pet_breeds') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'pet_ages') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'pet_genders') ?? 'N/A' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

    {{-- VISIT BILLING REPORT --}}
    @elseif($reportType == 'visit_billing')
        <div class="section">
            <div class="section-header">{{ $title ?? 'Visit Billing Details' }}</div>
            <table>
                <thead>
                    <tr>
                        <th>Visit Date</th>
                        <th>Owner Name</th>
                        <th>Contact</th>
                        <th>Pet Name</th>
                        <th>Species</th>
                        <th>Veterinarian</th>
                        <th>Branch</th>
                        <th>Patient Type</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        @if(getField($record, 'pay_cashAmount'))
                        <th>Cash Received</th>
                        <th>Change</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ formatDate(getField($record, 'visit_date', 'service_date')) }}</td>
                        <td>{{ getField($record, 'own_name', 'owner_name', 'customer_name') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'own_contactnum', 'owner_contact') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'pet_name') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'pet_species') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'user_name', 'veterinarian') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'branch_name') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'patient_type') ?? 'N/A' }}</td>
                        <td><span class="amount">PHP {{ number_format(getField($record, 'pay_total', 'bill_amount', 'total_amount') ?? 0, 2) }}</span></td>
                        <td>
                            <span class="status-badge {{ getStatusClass(getField($record, 'bill_status', 'payment_status')) }}">
                                {{ ucfirst(getField($record, 'bill_status', 'payment_status') ?? 'N/A') }}
                            </span>
                        </td>
                        @if(getField($record, 'pay_cashAmount'))
                        <td>PHP {{ number_format(getField($record, 'pay_cashAmount') ?? 0, 2) }}</td>
                        <td>PHP {{ number_format(getField($record, 'pay_change') ?? 0, 2) }}</td>
                        @endif
                    </tr>
                </tbody>
            </table>
        </div>

    {{-- BILLING & PAYMENT REPORTS --}}
    @elseif(in_array($reportType, ['appointment_billing', 'billing_orders', 'payment_collection', 'branch_payments']))
        <div class="section">
            <div class="section-header">{{ $title ?? 'Payment Details' }}</div>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Owner/Customer</th>
                        <th>Contact</th>
                        <th>Pet Name</th>
                        <th>Veterinarian/Collected By</th>
                        <th>Branch</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        @if(getField($record, 'pay_cashAmount'))
                        <th>Cash Received</th>
                        <th>Change</th>
                        @endif
                        @if(getField($record, 'total_payments_count'))
                        <th>Total Payments</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ formatDate(getField($record, 'visit_date', 'appoint_date', 'service_date', 'bill_date')) }}</td>
                        <td>{{ getField($record, 'own_name', 'owner_name', 'customer_name') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'own_contactnum', 'owner_contact') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'pet_name') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'user_name', 'veterinarian', 'collected_by') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'branch_name') ?? 'N/A' }}</td>
                        <td><span class="amount">PHP {{ number_format(getField($record, 'pay_total', 'bill_amount', 'total_amount', 'total_amount_collected') ?? 0, 2) }}</span></td>
                        <td>
                            <span class="status-badge {{ getStatusClass(getField($record, 'bill_status', 'payment_status')) }}">
                                {{ ucfirst(getField($record, 'bill_status', 'payment_status') ?? 'N/A') }}
                            </span>
                        </td>
                        @if(getField($record, 'pay_cashAmount'))
                        <td>PHP {{ number_format(getField($record, 'pay_cashAmount') ?? 0, 2) }}</td>
                        <td>PHP {{ number_format(getField($record, 'pay_change') ?? 0, 2) }}</td>
                        @endif
                        @if(getField($record, 'total_payments_count'))
                        <td>{{ getField($record, 'total_payments_count') ?? 0 }}</td>
                        @endif
                    </tr>
                </tbody>
            </table>
        </div>

    {{-- PRODUCT PURCHASE/SALES REPORTS --}}
    @elseif(in_array($reportType, ['product_purchases', 'product_sales']))
        <div class="section">
            <div class="section-header">{{ $title ?? 'Product Transaction Details' }}</div>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Contact</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total Amount</th>
                        <th>Handled By</th>
                        <th>Branch</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ formatDate(getField($record, 'ord_date', 'sale_date', 'order_date')) }}</td>
                        <td>{{ getField($record, 'own_name', 'customer_name') ?? 'Walk-in' }}</td>
                        <td>{{ getField($record, 'own_contactnum', 'owner_contact', 'customer_contact') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'prod_name', 'product_name') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'prod_category', 'product_category') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'prod_description', 'product_description') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'ord_quantity', 'quantity_sold', 'quantity') ?? 0 }}</td>
                        <td>PHP {{ number_format(getField($record, 'prod_price', 'unit_price') ?? 0, 2) }}</td>
                        <td><span class="amount">PHP {{ number_format(getField($record, 'ord_total', 'total_amount') ?? 0, 2) }}</span></td>
                        <td>{{ getField($record, 'user_name', 'handled_by', 'cashier', 'seller') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'branch_name') ?? 'N/A' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @elseif($reportType == 'damaged_products')
        <div class="section">
            <div class="section-header">{{ $title ?? 'Complete Stock Movement History' }}</div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Prod Name</th>
                        <th>Prod Category</th>
                        <th>Prod Type</th>
                        <th>Branch Name</th>
                        <th>User Name</th>
                        <th>Serv Name</th>
                        <th>Reference</th>
                        <th>Type</th>
                        <th>Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ getField($record, 'row_number', 'id') ?? '1' }}</td>
                        <td>{{ getField($record, 'prod_name') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'prod_category') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'prod_type') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'branch_name') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'user_name') ?? 'System' }}</td>
                        <td>{{ getField($record, 'serv_name') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'reference') ?? 'N/A' }}</td>
                        <td>
                            @php
                                $type = getField($record, 'type', 'transaction_type');
                                $typeLabels = [
                                    'restock' => 'Stock Added',
                                    'sale' => 'POS Sale',
                                    'service_usage' => 'Service Usage',
                                    'damage' => 'Damaged',
                                    'pullout' => 'Pull-out',
                                    'adjustment' => 'Adjustment',
                                    'return' => 'Return',
                                ];
                                // If type is already formatted (from type_label), use it, otherwise format
                                if (!in_array($type, array_keys($typeLabels))) {
                                    $typeLabel = $type; // Already formatted
                                } else {
                                    $typeLabel = $typeLabels[$type] ?? ucfirst($type ?? 'N/A');
                                }
                                $typeClasses = [
                                    'Stock Added' => 'bg-green-100 text-green-800',
                                    'POS Sale' => 'bg-blue-100 text-blue-800',
                                    'Service Usage' => 'bg-purple-100 text-purple-800',
                                    'Damaged' => 'bg-red-100 text-red-800',
                                    'Pull-out' => 'bg-orange-100 text-orange-800',
                                    'Adjustment' => 'bg-gray-100 text-gray-800',
                                    'Return' => 'bg-teal-100 text-teal-800',
                                ];
                                $typeClass = $typeClasses[$typeLabel] ?? 'bg-gray-100 text-gray-800';
                            @endphp
                            <span class="status-badge {{ $typeClass }}">
                                {{ $typeLabel }}
                            </span>
                        </td>
                        <td>{{ getField($record, 'quantity', 'quantity_change') ?? 0 }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

    {{-- MEDICAL HISTORY REPORT --}}
    @elseif($reportType == 'medical_history')
        <div class="section">
            <div class="section-header">Patient Information</div>
            <table>
                <tr>
                    <td class="label">Pet Name</td>
                    <td class="value">{{ getField($record, 'pet_name') ?? 'N/A' }}</td>
                    <td class="label">Owner</td>
                    <td class="value">{{ getField($record, 'own_name', 'owner_name') ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Visit Date</td>
                    <td class="value">{{ formatDate(getField($record, 'visit_date')) }}</td>
                    <td class="label">Veterinarian</td>
                    <td class="value">{{ getField($record, 'user_name', 'veterinarian') ?? 'N/A' }}</td>
                </tr>
            </table>
        </div>

        @if(getField($record, 'diagnosis'))
        <div class="section">
            <div class="section-header">Diagnosis</div>
            <div class="text-area">{{ getField($record, 'diagnosis') }}</div>
        </div>
        @endif

        @if(getField($record, 'treatment'))
        <div class="section">
            <div class="section-header green">Treatment</div>
            <div class="text-area">{{ getField($record, 'treatment') }}</div>
        </div>
        @endif

        @if(getField($record, 'medication'))
        <div class="section">
            <div class="section-header purple">Medication</div>
            <div class="text-area">{{ getField($record, 'medication') }}</div>
        </div>
        @endif

    {{-- PRESCRIPTIONS REPORT --}}
    @elseif($reportType == 'prescriptions')
        <div class="section">
            <div class="section-header">{{ $title ?? 'Prescription Details' }}</div>
            <table>
                <thead>
                    <tr>
                        <th>Prescription Date</th>
                        <th>Pet Name</th>
                        <th>Owner Name</th>
                        <th>Contact</th>
                        <th>Species</th>
                        <th>Medications</th>
                        <th>Prescribed By</th>
                        <th>Branch</th>
                        <th>Diagnosis</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ formatDate(getField($record, 'prescription_date', 'presc_date')) }}</td>
                        <td>{{ getField($record, 'pet_name') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'own_name', 'owner_name') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'own_contactnum') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'pet_species') ?? 'N/A' }}</td>
                        <td>
                            @php
                                $medData = getField($record, 'raw_medication_data', 'medication', 'formatted_medication') ?? '';
                                $medications = json_decode($medData, true);
                                $productNames = [];
                                if (is_array($medications)) {
                                    foreach ($medications as $med) {
                                        $name = $med['product_name'] ?? ($med['name'] ?? null);
                                        if ($name) $productNames[] = $name;
                                    }
                                }
                                if (empty($productNames) && $medData && !is_array($medications)) {
                                    $productNames = [$medData];
                                }
                            @endphp
                            {{ count($productNames) ? implode(', ', $productNames) : 'No medication data' }}
                        </td>
                        <td>{{ getField($record, 'prescribed_by', 'user_name') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'branch_name') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'differential_diagnosis', 'diagnosis') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'notes') ?? 'N/A' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

    {{-- VISIT SERVICES REPORT --}}
    @elseif($reportType == 'visit_services')
        <div class="section">
            <div class="section-header">{{ $title ?? 'Visit Service Details' }}</div>
            <table>
                <thead>
                    <tr>
                        <th>Visit Date</th>
                        <th>Service Name</th>
                        <th>Service Type</th>
                        <th>Service Price</th>
                        <th>Owner Name</th>
                        <th>Contact</th>
                        <th>Pet Name</th>
                        <th>Species</th>
                        <th>Breed</th>
                        <th>Patient Type</th>
                        <th>Veterinarian</th>
                        <th>Branch</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ formatDate(getField($record, 'visit_date')) }}</td>
                        <td>{{ getField($record, 'serv_name', 'service_name') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'serv_type', 'service_type') ?? 'N/A' }}</td>
                        <td>PHP {{ number_format(getField($record, 'serv_price', 'service_price') ?? 0, 2) }}</td>
                        <td>{{ getField($record, 'own_name', 'owner_name') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'own_contactnum', 'owner_contact') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'pet_name') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'pet_species') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'pet_breed') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'patient_type') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'user_name', 'veterinarian') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'branch_name') ?? 'N/A' }}</td>
                        <td>
                            <span class="status-badge {{ getStatusClass(getField($record, 'service_status', 'status')) }}">
                                {{ ucfirst(getField($record, 'service_status', 'status') ?? 'N/A') }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

    {{-- STAFF/USERS REPORT --}}
    @elseif($reportType == 'branch_users')
        <div class="section">
            <div class="section-header">{{ $title ?? 'User Details' }}</div>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Role/Position</th>
                        <th>Email</th>
                        <th>Contact Number</th>
                        <th>Branch</th>
                        <th>Branch Address</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ getField($record, 'user_name', 'staff_name') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'user_role', 'user_type', 'position') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'user_email') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'user_contactNum', 'user_contactnum') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'branch_name') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'branch_address') ?? 'N/A' }}</td>
                        <td>
                            <span class="status-badge {{ getStatusClass(getField($record, 'user_status', 'status')) }}">
                                {{ ucfirst(getField($record, 'user_status', 'status') ?? 'Active') }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

    {{-- EQUIPMENT REPORT --}}
    @elseif($reportType == 'branch_equipment')
        <div class="section">
            <div class="section-header">{{ $title ?? 'Equipment Assignment History' }}</div>
            <table>
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Equipment Name</th>
                        <th>Equipment Category</th>
                        <th>Action Type</th>
                        <th>Pet Name</th>
                        <th>Owner Name</th>
                        <th>Species</th>
                        <th>Breed</th>
                        <th>Service Name</th>
                        <th>Service Type</th>
                        <th>Check-in Date</th>
                        <th>Check-out Date</th>
                        <th>Room No</th>
                        <th>Status</th>
                        <th>Handled By</th>
                        <th>Visit Date</th>
                        <th>Branch</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ formatDate(getField($record, 'created_at', 'check_in_date')) }} {{ formatTime(getField($record, 'created_at', 'check_in_date')) }}</td>
                        <td>{{ getField($record, 'equipment_name') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'equipment_category') ?? 'N/A' }}</td>
                        <td>
                            <span class="status-badge {{ getField($record, 'action_type') == 'assigned' || getField($record, 'boarding_status') == 'Checked In' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                {{ ucfirst(getField($record, 'action_type', 'boarding_status') ?? 'Assigned') }}
                            </span>
                        </td>
                        <td>{{ getField($record, 'pet_name') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'owner_name') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'pet_species') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'pet_breed') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'service_name') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'service_type') ?? 'N/A' }}</td>
                        <td>{{ formatDate(getField($record, 'check_in_date')) }}</td>
                        <td>{{ formatDate(getField($record, 'check_out_date')) }}</td>
                        <td>{{ getField($record, 'room_no') ?? 'N/A' }}</td>
                        <td>
                            <span class="status-badge {{ getStatusClass(getField($record, 'boarding_status', 'new_status')) }}">
                                {{ ucfirst(getField($record, 'boarding_status', 'new_status') ?? 'N/A') }}
                            </span>
                        </td>
                        <td>{{ getField($record, 'handled_by', 'performed_by_name', 'vet_name') ?? 'N/A' }}</td>
                        <td>{{ formatDate(getField($record, 'visit_date')) }}</td>
                        <td>{{ getField($record, 'branch_name') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'log_notes', 'reference') ?? 'N/A' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

    {{-- SERVICE UTILIZATION --}}
    @elseif($reportType == 'service_utilization')
        <div class="section">
            <div class="section-header">{{ $title ?? 'Service Utilization per Branch' }}</div>
            <table>
                <thead>
                    <tr>
                        <th>Visit Date</th>
                        <th>Owner Name</th>
                        <th>Pet Name</th>
                        <th>Pet Species</th>
                        <th>Pet Breed</th>
                        <th>Patient Type</th>
                        <th>Serv Name</th>
                        <th>Serv Type</th>
                        <th>Total Price</th>
                        <th>Performed By</th>
                        <th>Branch Name</th>
                        <th>Service Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ formatDate(getField($record, 'visit_date')) }}</td>
                        <td>{{ getField($record, 'owner_name') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'pet_name') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'pet_species') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'pet_breed') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'patient_type') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'serv_name') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'serv_type') ?? 'N/A' }}</td>
                        <td>PHP {{ number_format(getField($record, 'total_price') ?? 0, 2) }}</td>
                        <td>{{ getField($record, 'performed_by') ?? 'N/A' }}</td>
                        <td>{{ getField($record, 'branch_name') ?? 'N/A' }}</td>
                        <td>
                            <span class="status-badge {{ getStatusClass(getField($record, 'service_status')) }}">
                                {{ ucfirst(getField($record, 'service_status') ?? 'Pending') }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    {{-- SERVICE APPOINTMENTS --}}
    @elseif(in_array($reportType, ['service_appointments', 'multi_service_appointments']))
        @if(is_array($record) || (is_object($record) && method_exists($record, 'toArray')))
            <div class="section">
                <div class="section-header">Complete Service Usage History</div>
                <table>
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Service</th>
                            <th>Patient / Owner</th>
                            <th>Visit Type</th>
                            <th>Species / Breed</th>
                            <th>Fee</th>
                            <th>Performed By</th>
                            <th>Status</th>
                            <th>Notes</th>
                            <th>Branch</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($record as $row)
                            <tr>
                                <td>{{ is_array($row) ? ($row['Date & Time'] ?? '') : ($row->{'Date & Time'} ?? '') }}</td>
                                <td>{{ is_array($row) ? ($row['Service'] ?? '') : ($row->Service ?? '') }}</td>
                                <td>{{ is_array($row) ? ($row['Patient / Owner'] ?? '') : ($row->{'Patient / Owner'} ?? '') }}</td>
                                <td>{{ is_array($row) ? ($row['Visit Type'] ?? '') : ($row->{'Visit Type'} ?? '') }}</td>
                                <td>{{ is_array($row) ? ($row['Species / Breed'] ?? '') : ($row->{'Species / Breed'} ?? '') }}</td>
                                <td>{!! is_array($row) ? ($row['Fee'] ?? '') : ($row->Fee ?? '') !!}</td>
                                <td>{{ is_array($row) ? ($row['Performed By'] ?? '') : ($row->{'Performed By'} ?? '') }}</td>
                                <td>{!! is_array($row) ? ($row['Status'] ?? '') : ($row->Status ?? '') !!}</td>
                                <td>{{ is_array($row) ? ($row['Notes'] ?? '') : ($row->Notes ?? '') }}</td>
                                <td>{{ is_array($row) ? ($row['Branch'] ?? '') : ($row->Branch ?? '') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="section">
                <div class="section-header">Service Utilization Details</div>
                <table>
                    <tr>
                        <td class="label">Service Name</td>
                        <td class="value">{{ getField($record, 'serv_name', 'service_name') ?? 'N/A' }}</td>
                        <td class="label">Branch</td>
                        <td class="value">{{ getField($record, 'branch_name') ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Total Used</td>
                        <td class="value">{{ getField($record, 'total_used_count') ?? 0 }}</td>
                        <td class="label">Branch Address</td>
                        <td class="value">{{ getField($record, 'branch_address') ?? 'N/A' }}</td>
                    </tr>
                </table>
            </div>
        @endif

    {{-- GENERIC/FALLBACK FOR ANY OTHER REPORT --}}
    @else
        <div class="section">
            <div class="section-header">Report Details</div>
            <table>
                @php
                    if (!isset($record) || !$record) {
                        echo '<tr><td colspan="4">No data available</td></tr>';
                    } else {
                        $recordArray = (array) $record;
                        // Exclude technical fields and IDs that are not user-friendly
                        $excludeFields = [
                            'id','created_at','updated_at','deleted_at','password',
                            'visit_id','pet_id','user_id','own_id','bill_id','branch_id','ref_id','appoint_id','prescription_id','presc_id','equipment_id','orderId','prod_id','service_id','serv_id','ref_to','ref_by','referred_by','referred_to','referred_to_name','pivot','pay_id','_id'
                        ];
                        $filteredArray = array_filter($recordArray, function($value, $key) use ($excludeFields) {
                            // Hide IDs and technical fields from non-technical PDF view
                            return !in_array($key, $excludeFields) && $value !== null && $value !== '';
                        }, ARRAY_FILTER_USE_BOTH);
                        $chunks = array_chunk($filteredArray, 2, true);
                    }
                @endphp
                
                @if(isset($chunks) && count($chunks) > 0)
                    @foreach($chunks as $chunk)
                        <tr>
                            @foreach($chunk as $key => $value)
                                <td class="label">{{ ucwords(str_replace('_', ' ', $key)) }}</td>
                                <td class="value">
                                    @if(in_array($key, ['services', 'visit_services']) && ($value instanceof \Illuminate\Support\Collection || is_array($value)))
                                        @php
                                            $serviceList = [];
                                            foreach ($value as $service) {
                                                $name = isset($service->serv_name) ? $service->serv_name : (isset($service['serv_name']) ? $service['serv_name'] : (isset($service['service_name']) ? $service['service_name'] : 'Service'));
                                                $status = isset($service->pivot) && isset($service->pivot->status) ? $service->pivot->status : (isset($service['pivot']['status']) ? $service['pivot']['status'] : null);
                                                $serviceList[] = $name . ($status ? ' (' . ucfirst($status) . ')' : '');
                                            }
                                            echo count($serviceList) ? implode(', ', $serviceList) : 'No services';
                                        @endphp
                                    @else
                                        {!! renderValue($key, $value) !!}
                                    @endif
                                </td>
                            @endforeach
                            @if(count($chunk) == 1)
                                <td class="label"></td>
                                <td class="value"></td>
                            @endif
                        </tr>
                    @endforeach
                @else
                    <tr><td colspan="4" class="value">No data available for this record</td></tr>
                @endif
            </table>
        </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        Generated on {{ date('F d, Y h:i A') }} | Pets2GO Veterinary Clinic<br>
    </div>
</body>
</html>

