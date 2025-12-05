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
            padding: 20px;
            text-align: center;
            margin-bottom: 30px;
        }
        .header img {
            max-height: 80px;
            /* Ensure image path is correct, use public_path() for dompdf */
            content: url("{{ public_path('images/header.jpg') }}");
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
        }
        td {
            padding: 8px;
            border: 1px solid #e5e7eb;
            vertical-align: top;
            /* Added float for layout fix, necessary when mixing tables and text */
            float: none !important; 
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
        /* Page numbering for consistency */
        @page {
            size: letter;
            margin: 15mm 20mm 25mm 20mm;
            counter-increment: page;
            @bottom-right {
                content: "Page " counter(page) " of " counter(pages);
                font-size: 10px;
                color: #6b7280;
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
        <strong>Branch:</strong> {{ $branch->branch_name ?? 'N/A' }} | <strong>Generated:</strong> {{ \Carbon\Carbon::now()->format('F d, Y h:i A') }}
    </p>

    {{-- Enhanced Table for All Report Types --}}
    @if(in_array($reportType, ['visits', 'pets', 'billing', 'sales', 'referrals', 'equipment', 'services', 'inventory']))
    <div class="section">
        <div class="section-header">{{ $title ?? 'Report' }}</div>
        <table>
            <thead>
                <tr>
                    @if($reportType === 'visits')
                        <th>#</th>
                        <th>Visit Date</th>
                        <th>Owner Name</th>
                        <th>Contact</th>
                        <th>Pet Name</th>
                        <th>Patient Type</th>
                        <th>Services</th>
                        <th>Status</th>
                    @elseif($reportType === 'pets')
                        <th>#</th>
                        <th>Registration Date</th>
                        <th>Owner Name</th>
                        <th>Contact</th>
                        <th>Pet Name</th>
                        <th>Species</th>
                        <th>Breed</th>
                        <th>Age</th>
                        <th>Gender</th>
                    @elseif($reportType === 'billing')
                        <th>#</th>
                        <th>Service Date</th>
                        <th>Pet Owner</th>
                        <th>Pet Name</th>
                        <th>Amount</th>
                        <th>Status</th>
                    @elseif($reportType === 'sales')
                        <th>#</th>
                        <th>Sale Date</th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total Amount</th>
                        <th>Cashier</th>
                    @elseif($reportType === 'referrals')
                        <th>#</th>
                        <th>Date</th>
                        <th>Owner</th>
                        <th>Pet</th>
                        <th>Reason</th>
                        <th>Referred By</th>
                        <th>Referred To</th>
                    @elseif($reportType === 'equipment')
                        <th>#</th>
                        <th>Equipment Name</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Total In Use</th>
                        <th>Total Maintenance</th>
                        <th>Total Available</th>
                        <th>Total Out of Service</th>
                    @elseif($reportType === 'services')
                        <th>#</th>
                        <th>Service Name</th>
                        <th>Service Type</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Status</th>
                    @elseif($reportType === 'inventory')
                        <th>#</th>
                        <th>Product Name</th>
                        <th>Product Type</th>
                        <th>Description</th>
                        <th>Total Pull Out</th>
                        <th>Total Damage</th>
                        <th>Total Stocks</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($data as $i => $row)
                    <tr>
                        @if($reportType === 'visits')
                            <td>{{ $i+1 }}</td>
                            <td>{{ formatDate($row->visit_date) }}</td>
                            <td>{{ $row->owner_name }}</td>
                            <td>{{ $row->owner_contact }}</td>
                            <td>{{ $row->pet_name }}</td>
                            <td>{{ $row->patient_type }}</td>
                            <td>{{ $row->services }}</td>
                            <td>{{ $row->status }}</td>
                        @elseif($reportType === 'pets')
                            <td>{{ $i+1 }}</td>
                            <td>{{ formatDate($row->registration_date) }}</td>
                            <td>{{ $row->owner_name }}</td>
                            <td>{{ $row->owner_contact }}</td>
                            <td>{{ $row->pet_name }}</td>
                            <td>{{ $row->pet_species }}</td>
                            <td>{{ $row->pet_breed }}</td>
                            <td>{{ $row->pet_age }}</td>
                            <td>{{ $row->pet_gender }}</td>
                        @elseif($reportType === 'billing')
                            <td>{{ $i+1 }}</td>
                            <td>{{ formatDate($row->service_date) }}</td>
                            <td>{{ $row->customer_name }}</td>
                            <td>{{ $row->pet_name }}</td>
                            <td>{{ $row->pay_total }}</td>
                            <td>{{ $row->payment_status }}</td>
                        @elseif($reportType === 'sales')
                            <td>{{ $i+1 }}</td>
                            <td>{{ formatDate($row->sale_date) }}</td>
                            <td>{{ $row->customer_name }}</td>
                            <td>{{ $row->product_name }}</td>
                            <td>{{ $row->quantity_sold }}</td>
                            <td>{{ $row->unit_price }}</td>
                            <td>{{ $row->total_amount }}</td>
                            <td>{{ $row->cashier }}</td>
                        @elseif($reportType === 'referrals')
                            <td>{{ $i+1 }}</td>
                            <td>{{ formatDate($row->ref_date) }}</td>
                            <td>{{ $row->owner_name }}</td>
                            <td>{{ $row->pet_name }}</td>
                            <td>{{ $row->referral_reason }}</td>
                            <td>{{ $row->referred_by }}</td>
                            <td>{{ $row->referred_to }}</td>
                        @elseif($reportType === 'equipment')
                            <td>{{ $i+1 }}</td>
                            <td>{{ $row->equipment_name }}</td>
                            <td>{{ $row->equipment_category }}</td>
                            <td>{{ $row->equipment_description }}</td>
                            <td>{{ $row->total_in_use }}</td>
                            <td>{{ $row->total_maintenance }}</td>
                            <td>{{ $row->total_available }}</td>
                            <td>{{ $row->total_out_of_service }}</td>
                        @elseif($reportType === 'services')
                            <td>{{ $i+1 }}</td>
                            <td>{{ $row->service_name }}</td>
                            <td>{{ $row->service_type }}</td>
                            <td>{{ $row->service_description }}</td>
                            <td>{{ $row->service_price }}</td>
                            <td>{{ $row->status }}</td>
                        @elseif($reportType === 'inventory')
                            <td>{{ $i+1 }}</td>
                            <td>{{ $row->product_name }}</td>
                            <td>{{ $row->product_type }}</td>
                            <td>{{ $row->product_description }}</td>
                            <td>{{ $row->total_pull_out }}</td>
                            <td>{{ $row->total_damage }}</td>
                            <td>{{ $row->total_stocks }}</td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
        ...existing code...
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
    {{-- 3. PETS REPORT --}}
    @elseif($reportType === 'pets')
        <div class="section">
            <div class="section-header">Owner Information</div>
            <table>
                <tr>
                    <td class="label">Owner Name</td>
                    <td class="value">{{ getField($record, 'owner.own_name') ?? 'N/A' }}</td>
                    <td class="label">Contact</td>
                    <td class="value">{{ getField($record, 'owner.own_contactnum') ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Address</td>
                    <td class="value" colspan="3">{{ getField($record, 'owner.own_location') ?? 'N/A' }}</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-header orange">Pet Profile</div>
            <table>
                <tr>
                    <td class="label">Pet ID</td>
                    <td class="value">{{ getField($record, 'pet_id') ?? 'N/A' }}</td>
                    <td class="label">Name</td>
                    <td class="value">{{ getField($record, 'pet_name') ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Species</td>
                    <td class="value">{{ getField($record, 'pet_species') ?? 'N/A' }}</td>
                    <td class="label">Breed</td>
                    <td class="value">{{ getField($record, 'pet_breed') ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Gender</td>
                    <td class="value">{{ ucfirst(getField($record, 'pet_gender') ?? 'N/A') }}</td>
                    <td class="label">Date of Birth</td>
                    <td class="value">{{ formatDate(getField($record, 'pet_birthdate')) }}</td>
                </tr>
                <tr>
                    <td class="label">Weight</td>
                    <td class="value">{{ getField($record, 'pet_weight') ?? 'N/A' }} kg</td>
                    <td class="label">Registration Date</td>
                    <td class="value">{{ formatDate(getField($record, 'pet_registration')) }}</td>
                </tr>
            </table>
        </div>
    {{-- 3. BILLING REPORT (DB Query result) --}}
    @elseif($reportType === 'billing')
        <div class="section">
            <div class="section-header">Customer & Branch Information</div>
            <table>
                <tr>
                    <td class="label">Bill ID</td>
                    <td class="value">{{ getField($record, 'bill_id') ?? 'N/A' }}</td>
                    <td class="label">Customer Name</td>
                    <td class="value">{{ getField($record, 'own_name') ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Pet Name</td>
                    <td class="value">{{ getField($record, 'pet_name') ?? 'N/A' }}</td>
                    <td class="label">Contact Number</td>
                    <td class="value">{{ getField($record, 'own_contactnum') ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Service Date</td>
                    <td class="value">{{ formatDate(getField($record, 'appoint_date')) }}</td>
                    <td class="label">Billing Date</td>
                    <td class="value">{{ formatDate(getField($record, 'bill_date')) }}</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-header green">Payment Summary</div>
            <table>
                <tr>
                    <td class="label">Total Amount</td>
                    <td class="value" colspan="3"><span class="amount">₱{{ number_format(getField($record, 'pay_total') ?? 0, 2) }}</span></td>
                </tr>
                <tr>
                    <td class="label">Payment Status</td>
                    <td class="value" colspan="3">
                        <span class="status-badge {{ getStatusClass(getField($record, 'bill_status')) }}">
                            {{ ucfirst(getField($record, 'bill_status') ?? 'N/A') }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>

    {{-- 4. SALES REPORT --}}
    @elseif($reportType === 'sales')
        <div class="section">
            <div class="section-header">Transaction Information</div>
            <table>
                <tr>
                    <td class="label">Order ID</td>
                    <td class="value">{{ getField($record, 'ord_id') ?? 'N/A' }}</td>
                    <td class="label">Sale Date</td>
                    <td class="value">{{ formatDate(getField($record, 'ord_date')) }}</td>
                </tr>
                <tr>
                    <td class="label">Customer</td>
                    <td class="value">{{ getField($record, 'owner.own_name') ?? 'Walk-in' }}</td>
                    <td class="label">Cashier</td>
                    <td class="value">{{ getField($record, 'user.name') ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Branch</td>
                    <td class="value" colspan="3">{{ getField($record, 'user.branch.branch_name') ?? 'N/A' }}</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-header orange">Product Details</div>
            <table>
                <tr>
                    <td class="label">Product Name</td>
                    <td class="value">{{ getField($record, 'product.prod_name') ?? 'N/A' }}</td>
                    <td class="label">Unit Price</td>
                    <td class="value">₱{{ number_format(getField($record, 'product.prod_price') ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Quantity Sold</td>
                    <td class="value">{{ getField($record, 'ord_quantity') ?? 0 }}</td>
                    <td class="label">Total Amount</td>
                    <td class="value"><span class="amount">₱{{ number_format(getField($record, 'ord_total') ?? 0, 2) }}</span></td>
                </tr>
            </table>
            @if(getField($record, 'product.prod_description'))
            <div class="section">
                <div class="section-header">Product Description</div>
                <div class="text-area">{{ getField($record, 'product.prod_description') }}</div>
            </div>
            @endif
        </div>
    {{-- 5. REFERRALS REPORT --}}
    @elseif($reportType === 'referrals')
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
                    <td class="label">Pet Name</td>
                    <td class="value">{{ getField($record, 'appointment.pet.pet_name') ?? 'N/A' }}</td>
                    <td class="label">Owner Name</td>
                    <td class="value">{{ getField($record, 'appointment.pet.owner.own_name') ?? 'N/A' }}</td>
                </tr>
            </table>
        </div>
        <div class="section">
            <div class="section-header orange">Referral Details</div>
            <table>
                <tr>
                    <td class="label">Referred By (Staff)</td>
                    <td class="value">{{ getField($record, 'appointment.user.name') ?? 'N/A' }}</td>
                    <td class="label">From Branch</td>
                    <td class="value">{{ getField($record, 'appointment.user.branch.branch_name') ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Referred To (Facility)</td>
                    <td class="value" colspan="3">{{ getField($record, 'ref_to') ?? 'N/A' }}</td>
                </tr>
            </table>
        </div>
        <div class="section">
            <div class="section-header">Reason for Referral</div>
            <div class="text-area">{{ getField($record, 'ref_description') ?? 'No reason provided' }}</div>
        </div>
    {{-- 6. EQUIPMENT REPORT --}}
    @elseif($reportType === 'equipment')
        <div class="section">
            <div class="section-header">Equipment Details</div>
            <table>
                <tr>
                    <td class="label">Equipment ID</td>
                    <td class="value">{{ getField($record, 'equipment_id') ?? 'N/A' }}</td>
                    <td class="label">Name</td>
                    <td class="value">{{ getField($record, 'equipment_name') ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Branch</td>
                    <td class="value">{{ $branch->branch_name ?? 'N/A' }}</td>
                    <td class="label">Quantity</td>
                    <td class="value">{{ getField($record, 'equipment_quantity') ?? 0 }}</td>
                </tr>
                <tr>
                    <td class="label">Status</td>
                    <td class="value" colspan="3">
                         @php 
                            $qty = getField($record, 'equipment_quantity');
                            $status = $qty > 10 ? 'Good Stock' : ($qty > 0 ? 'Low Stock' : 'Out of Stock');
                        @endphp
                        <span class="status-badge {{ getStatusClass($status) }}">{{ $status }}</span>
                    </td>
                </tr>
            </table>
        </div>
        @if(getField($record, 'equipment_description'))
        <div class="section">
            <div class="section-header">Description</div>
            <div class="text-area">{{ getField($record, 'equipment_description') }}</div>
        </div>
        @endif
    {{-- 7. SERVICES REPORT --}}
    @elseif($reportType === 'services')
        <div class="section">
            <div class="section-header">Service Details</div>
            <table>
                <tr>
                    <td class="label">Service ID</td>
                    <td class="value">{{ getField($record, 'serv_id') ?? 'N/A' }}</td>
                    <td class="label">Name</td>
                    <td class="value">{{ getField($record, 'serv_name') ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Branch</td>
                    <td class="value">{{ $branch->branch_name ?? 'N/A' }}</td>
                    <td class="label">Price</td>
                    <td class="value"><span class="amount">₱{{ number_format(getField($record, 'serv_price') ?? 0, 2) }}</span></td>
                </tr>
                <tr>
                    <td class="label">Status</td>
                    <td class="value" colspan="3">
                        <span class="status-badge status-active">ACTIVE</span>
                    </td>
                </tr>
            </table>
        </div>
        @if(getField($record, 'serv_description'))
        <div class="section">
            <div class="section-header">Description</div>
            <div class="text-area">{{ getField($record, 'serv_description') }}</div>
        </div>
        @endif
    {{-- 8. INVENTORY REPORT --}}
    @elseif($reportType === 'inventory')
        <div class="section">
            <div class="section-header">Product Details</div>
            <table>
                <tr>
                    <td class="label">Product ID</td>
                    <td class="value">{{ getField($record, 'prod_id') ?? 'N/A' }}</td>
                    <td class="label">Name</td>
                    <td class="value">{{ getField($record, 'prod_name') ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Branch</td>
                    <td class="value">{{ $branch->branch_name ?? 'N/A' }}</td>
                    <td class="label">Unit Price</td>
                    <td class="value">₱{{ number_format(getField($record, 'prod_price') ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Quantity in Stock</td>
                    <td class="value">{{ getField($record, 'prod_quantity', 'prod_stocks') ?? 0 }}</td>
                    <td class="label">Stock Status</td>
                    <td class="value">
                        @php 
                            $qty = getField($record, 'prod_quantity', 'prod_stocks');
                            $status = $qty > 20 ? 'Good Stock' : ($qty > 0 ? 'Low Stock' : 'Out of Stock');
                        @endphp
                        <span class="status-badge {{ getStatusClass($status) }}">{{ $status }}</span>
                    </td>
                </tr>
            </table>
        </div>
        @if(getField($record, 'prod_description'))
        <div class="section">
            <div class="section-header">Description</div>
            <div class="text-area">{{ getField($record, 'prod_description') }}</div>
        </div>
        @endif
    {{-- 9. FALLBACK / REVENUE --}}
    @else
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