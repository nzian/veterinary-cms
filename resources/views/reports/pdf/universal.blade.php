<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
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
            padding: 20px;
            text-align: center;
            margin-bottom: 30px;
        }
        .header img {
            max-height: 80px;
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
        }
        td {
            padding: 8px;
            border: 1px solid #e5e7eb;
            vertical-align: top;
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
            foreach ($fields as $field) {
                if (isset($record->$field) && $record->$field !== null && $record->$field !== '') {
                    return $record->$field;
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
                return '<span class="amount">₱' . number_format($value, 2) . '</span>';
            } elseif (str_contains($key, 'status')) {
                return '<span class="status-badge ' . getStatusClass($value) . '">' . ucfirst($value) . '</span>';
            }
            return $value;
        }
    @endphp

    <!-- Header -->
    <div class="header">
        <img src="{{ public_path('images/header.jpg') }}" alt="Pets2GO Veterinary Clinic">
    </div>

    <div class="title">{{ $title ?? 'REPORT DETAILS' }}</div>

    {{-- APPOINTMENTS REPORT --}}
    @if(in_array($reportType, ['appointments', 'branch_appointments']))
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
                    <td class="value">{{ $record->pet_name ?? 'N/A' }}</td>
                    <td class="label">Species</td>
                    <td class="value">{{ $record->pet_species ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Breed</td>
                    <td class="value" colspan="3">{{ $record->pet_breed ?? 'N/A' }}</td>
                </tr>
            </table>
        </div>

        <!-- Appointment Information -->
        <div class="section">
            <div class="section-header">Appointment Information</div>
            <table>
                <tr>
                    <td class="label">Appointment ID</td>
                    <td class="value">{{ $record->appoint_id ?? 'N/A' }}</td>
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
                    <td class="value">{{ $record->branch_name ?? 'N/A' }}</td>
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
                    <td class="value">{{ $record->ref_id ?? 'N/A' }}</td>
                    <td class="label">Date</td>
                    <td class="value">{{ formatDate($record->ref_date) }}</td>
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
                    <td class="value">{{ $record->pet_name ?? 'N/A' }}</td>
                    <td class="label">Date of Birth</td>
                    <td class="value">{{ formatDate($record->pet_birthdate) }}</td>
                </tr>
                <tr>
                    <td class="label">Species</td>
                    <td class="value">{{ $record->pet_species ?? 'N/A' }}</td>
                    <td class="label">Breed</td>
                    <td class="value">{{ $record->pet_breed ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Gender</td>
                    <td class="value" colspan="3">
                        <span class="status-badge">{{ ucfirst($record->pet_gender ?? 'N/A') }}</span>
                    </td>
                </tr>
            </table>
        </div>

        @if($record->medical_history ?? false)
        <div class="section">
            <div class="section-header">Medical History</div>
            <div class="text-area">{{ $record->medical_history }}</div>
        </div>
        @endif

        @if($record->tests_conducted ?? false)
        <div class="section">
            <div class="section-header">Tests Conducted</div>
            <div class="text-area">{{ $record->tests_conducted }}</div>
        </div>
        @endif

        @if($record->medications_given ?? false)
        <div class="section">
            <div class="section-header">Medications Given</div>
            <div class="text-area">{{ $record->medications_given }}</div>
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
            <div class="section-header">Owner Information</div>
            <table>
                <tr>
                    <td class="label">Owner Name</td>
                    <td class="value">{{ getField($record, 'own_name', 'owner_name') ?? 'N/A' }}</td>
                    <td class="label">Contact</td>
                    <td class="value">{{ getField($record, 'own_contactnum', 'owner_contact') ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Total Pets</td>
                    <td class="value" colspan="3">{{ $record->total_pets ?? 'N/A' }}</td>
                </tr>
            </table>
        </div>

        @if(getField($record, 'pet_names'))
        <div class="section">
            <div class="section-header">Pets Information</div>
            <div class="text-area">
                <strong>Pet Names:</strong> {{ $record->pet_names }}<br>
                <strong>Species:</strong> {{ $record->pet_species ?? 'N/A' }}<br>
                <strong>Breeds:</strong> {{ $record->pet_breeds ?? 'N/A' }}<br>
                <strong>Ages:</strong> {{ $record->pet_ages ?? 'N/A' }}
            </div>
        </div>
        @endif

    {{-- BILLING & PAYMENT REPORTS --}}
    @elseif(in_array($reportType, ['appointment_billing', 'billing_orders', 'payment_collection', 'branch_payments']))
        <div class="section">
            <div class="section-header">Customer Information</div>
            <table>
                <tr>
                    <td class="label">Customer/Owner</td>
                    <td class="value">{{ getField($record, 'own_name', 'owner_name', 'customer_name') ?? 'N/A' }}</td>
                    <td class="label">Pet Name</td>
                    <td class="value">{{ $record->pet_name ?? 'N/A' }}</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-header green">Billing Information</div>
            <table>
                @if(getField($record, 'bill_id'))
                <tr>
                    <td class="label">Bill ID</td>
                    <td class="value" colspan="3">{{ $record->bill_id }}</td>
                </tr>
                @endif
                <tr>
                    <td class="label">Date</td>
                    <td class="value">{{ formatDate(getField($record, 'appoint_date', 'service_date', 'bill_date')) }}</td>
                    <td class="label">Branch</td>
                    <td class="value">{{ $record->branch_name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Total Amount</td>
                    <td class="value"><span class="amount">₱{{ number_format(getField($record, 'pay_total', 'bill_amount', 'total_amount') ?? 0, 2) }}</span></td>
                    <td class="label">Status</td>
                    <td class="value">
                        <span class="status-badge {{ getStatusClass(getField($record, 'bill_status', 'payment_status')) }}">
                            {{ ucfirst(getField($record, 'bill_status', 'payment_status') ?? 'N/A') }}
                        </span>
                    </td>
                </tr>
                @if(getField($record, 'pay_cashAmount'))
                <tr>
                    <td class="label">Cash Received</td>
                    <td class="value">₱{{ number_format($record->pay_cashAmount, 2) }}</td>
                    <td class="label">Change</td>
                    <td class="value">₱{{ number_format(getField($record, 'pay_change') ?? 0, 2) }}</td>
                </tr>
                @endif
            </table>
        </div>

    {{-- PRODUCT PURCHASE/SALES REPORTS --}}
    @elseif(in_array($reportType, ['product_purchases', 'product_sales', 'damaged_products']))
        <div class="section">
            <div class="section-header">Transaction Information</div>
            <table>
                <tr>
                    <td class="label">Order ID</td>
                    <td class="value">{{ getField($record, 'ord_id', 'ord_id') ?? 'N/A' }}</td>
                    <td class="label">Date</td>
                    <td class="value">{{ formatDate(getField($record, 'ord_date', 'sale_date', 'order_date')) }}</td>
                </tr>
                <tr>
                    <td class="label">Customer</td>
                    <td class="value">{{ getField($record, 'own_name', 'customer_name') ?? 'Walk-in' }}</td>
                    <td class="label">Handled By</td>
                    <td class="value">{{ getField($record, 'user_name', 'handled_by', 'cashier') ?? 'N/A' }}</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-header">Product Details</div>
            <table>
                <tr>
                    <td class="label">Product Name</td>
                    <td class="value" colspan="3">{{ getField($record, 'prod_name', 'product_name') ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Quantity</td>
                    <td class="value">{{ getField($record, 'ord_quantity', 'quantity_sold', 'quantity') ?? 0 }}</td>
                    <td class="label">Unit Price</td>
                    <td class="value">₱{{ number_format(getField($record, 'prod_price', 'unit_price') ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Total Amount</td>
                    <td class="value" colspan="3"><span class="amount">₱{{ number_format(getField($record, 'ord_total', 'total_amount') ?? 0, 2) }}</span></td>
                </tr>
            </table>
        </div>

    {{-- MEDICAL HISTORY REPORT --}}
    @elseif($reportType == 'medical_history')
        <div class="section">
            <div class="section-header">Patient Information</div>
            <table>
                <tr>
                    <td class="label">Pet Name</td>
                    <td class="value">{{ $record->pet_name ?? 'N/A' }}</td>
                    <td class="label">Owner</td>
                    <td class="value">{{ getField($record, 'own_name', 'owner_name') ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Visit Date</td>
                    <td class="value">{{ formatDate($record->visit_date) }}</td>
                    <td class="label">Veterinarian</td>
                    <td class="value">{{ getField($record, 'user_name', 'veterinarian') ?? 'N/A' }}</td>
                </tr>
            </table>
        </div>

        @if(getField($record, 'diagnosis'))
        <div class="section">
            <div class="section-header">Diagnosis</div>
            <div class="text-area">{{ $record->diagnosis }}</div>
        </div>
        @endif

        @if(getField($record, 'treatment'))
        <div class="section">
            <div class="section-header green">Treatment</div>
            <div class="text-area">{{ $record->treatment }}</div>
        </div>
        @endif

        @if(getField($record, 'medication'))
        <div class="section">
            <div class="section-header purple">Medication</div>
            <div class="text-area">{{ $record->medication }}</div>
        </div>
        @endif

    {{-- PRESCRIPTIONS REPORT --}}
    @elseif($reportType == 'prescriptions')
        <div class="section">
            <div class="section-header">Patient Information</div>
            <table>
                <tr>
                    <td class="label">Prescription ID</td>
                    <td class="value">{{ getField($record, 'prescription_id', 'presc_id') ?? 'N/A' }}</td>
                    <td class="label">Pet Name</td>
                    <td class="value">{{ $record->pet_name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Owner</td>
                    <td class="value">{{ getField($record, 'own_name', 'owner_name') ?? 'N/A' }}</td>
                    <td class="label">Date Issued</td>
                    <td class="value">{{ formatDate(getField($record, 'prescription_date', 'presc_date')) }}</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-header purple">Prescription Details</div>
            <table>
                <tr>
                    <td class="label">Medication</td>
                    <td class="value" colspan="3">{{ getField($record, 'medication', 'medicine_name') ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Veterinarian</td>
                    <td class="value">{{ getField($record, 'user_name', 'veterinarian') ?? 'N/A' }}</td>
                    <td class="label">Branch</td>
                    <td class="value">{{ $record->branch_name ?? 'N/A' }}</td>
                </tr>
            </table>
        </div>

        @if(getField($record, 'differential_diagnosis', 'diagnosis', 'notes'))
        <div class="section">
            <div class="section-header yellow">Notes/Diagnosis</div>
            <div class="text-area">{{ getField($record, 'differential_diagnosis', 'diagnosis', 'notes') }}</div>
        </div>
        @endif

    {{-- STAFF/USERS REPORT --}}
    @elseif($reportType == 'branch_users')
        <div class="section">
            <div class="section-header">Staff Information</div>
            <table>
                <tr>
                    <td class="label">Name</td>
                    <td class="value">{{ getField($record, 'user_name', 'staff_name') ?? 'N/A' }}</td>
                    <td class="label">Role/Position</td>
                    <td class="value">{{ getField($record, 'user_role', 'user_type', 'position') ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Branch</td>
                    <td class="value">{{ $record->branch_name ?? 'N/A' }}</td>
                    <td class="label">Status</td>
                    <td class="value">
                        <span class="status-badge {{ getStatusClass($record->user_status ?? $record->status) }}">
                            {{ ucfirst(getField($record, 'user_status', 'status') ?? 'Active') }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>

        @if(getField($record, 'branch_address'))
        <div class="section">
            <div class="section-header">Branch Details</div>
            <div class="text-area">{{ $record->branch_address }}</div>
        </div>
        @endif

    {{-- EQUIPMENT REPORT --}}
    @elseif($reportType == 'branch_equipment')
        <div class="section">
            <div class="section-header">Equipment Information</div>
            <table>
                <tr>
                    <td class="label">Equipment ID</td>
                    <td class="value">{{ $record->equipment_id ?? 'N/A' }}</td>
                    <td class="label">Category</td>
                    <td class="value">{{ $record->equipment_category ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Branch</td>
                    <td class="value">{{ $record->branch_name ?? 'N/A' }}</td>
                    <td class="label">Quantity</td>
                    <td class="value">{{ getField($record, 'equipment_quantity', 'total_quantity') ?? 0 }}</td>
                </tr>
            </table>
        </div>

    {{-- SERVICE APPOINTMENTS/UTILIZATION --}}
    @elseif(in_array($reportType, ['service_appointments', 'multi_service_appointments', 'service_utilization']))
        <div class="section">
            <div class="section-header">Service Information</div>
            <table>
                <tr>
                    <td class="label">Service Name</td>
                    <td class="value">{{ getField($record, 'serv_name', 'service_name', 'services') ?? 'N/A' }}</td>
                    <td class="label">Price</td>
                    <td class="value"><span class="amount">₱{{ number_format(getField($record, 'serv_price', 'service_price', 'total_service_price') ?? 0, 2) }}</span></td>
                </tr>
                <tr>
                    <td class="label">Branch</td>
                    <td class="value">{{ $record->branch_name ?? 'N/A' }}</td>
                    <td class="label">Date</td>
                    <td class="value">{{ formatDate(getField($record, 'appoint_date', 'service_date')) }}</td>
                </tr>
                @if(getField($record, 'total_used'))
                <tr>
                    <td class="label">Times Used</td>
                    <td class="value" colspan="3">{{ $record->total_used }}</td>
                </tr>
                @endif
            </table>
        </div>

        @if(getField($record, 'own_name', 'owner_name'))
        <div class="section">
            <div class="section-header">Customer Information</div>
            <table>
                <tr>
                    <td class="label">Customer</td>
                    <td class="value">{{ getField($record, 'own_name', 'owner_name') }}</td>
                    <td class="label">Pet</td>
                    <td class="value">{{ $record->pet_name ?? 'N/A' }}</td>
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
                    $recordArray = (array) $record;
                    $excludeFields = ['created_at', 'updated_at', 'deleted_at', 'password'];
                    $filteredArray = array_filter($recordArray, function($value, $key) use ($excludeFields) {
                        return !in_array($key, $excludeFields) && $value !== null && $value !== '';
                    }, ARRAY_FILTER_USE_BOTH);
                    $chunks = array_chunk($filteredArray, 2, true);
                @endphp
                
                @foreach($chunks as $chunk)
                    <tr>
                        @foreach($chunk as $key => $value)
                            <td class="label">{{ ucwords(str_replace('_', ' ', $key)) }}</td>
                            <td class="value">{!! renderValue($key, $value) !!}</td>
                        @endforeach
                        @if(count($chunk) == 1)
                            <td class="label"></td>
                            <td class="value"></td>
                        @endif
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        Generated on {{ date('F d, Y h:i A') }} | Pets2GO Veterinary Clinic<br>
        For inquiries, please contact your assigned branch.
        Page <span class="page-number"></span> of <span class="page-total"></span>
    </div>
</body>
</html>

