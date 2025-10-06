<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #333;
        }
        
        .header {
            background-color: #f88e28;
            padding: 15px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .header img {
            max-height: 80px;
            width: auto;
        }
        
        .report-title {
            text-align: center;
            font-size: 18pt;
            font-weight: bold;
            margin: 20px 0;
            color: #1f2937;
        }
        
        .section {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
        }
        
        .section-title {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 10px;
            color: #1f2937;
            border-bottom: 2px solid #f88e28;
            padding-bottom: 5px;
        }
        
        .info-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-label {
            display: table-cell;
            font-weight: bold;
            color: #6b7280;
            padding: 5px 10px 5px 0;
            width: 30%;
            font-size: 9pt;
            text-transform: uppercase;
        }
        
        .info-value {
            display: table-cell;
            padding: 5px 0;
            color: #1f2937;
            font-size: 10pt;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 9pt;
            font-weight: bold;
        }
        
        .status-completed, .status-paid, .status-good {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .status-cancelled, .status-overdue {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 40px;
            border-top: 2px solid #f88e28;
            padding: 10px;
            font-size: 8pt;
            color: #6b7280;
            text-align: center;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        @page {
            margin: 20mm;
            margin-bottom: 25mm;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <img src="{{ public_path('images/header.jpg') }}" alt="Pets2GO Veterinary Clinic">
    </div>
    
    <!-- Report Title -->
    <div class="report-title">{{ $title }}</div>
    
    <!-- Content based on report type -->
    @if($reportType === 'appointments')
        <div class="section">
            <div class="section-title">Patient & Owner Information</div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Owner Name:</div>
                    <div class="info-value">{{ $data['own_name'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Contact Number:</div>
                    <div class="info-value">{{ $data['own_contactnum'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Pet Name:</div>
                    <div class="info-value">{{ $data['pet_name'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Breed/Species:</div>
                    <div class="info-value">{{ $data['pet_breed'] ?? 'N/A' }} - {{ $data['pet_species'] ?? 'N/A' }}</div>
                </div>
            </div>
        </div>
        
        <div class="section">
            <div class="section-title">Appointment Information</div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Appointment ID:</div>
                    <div class="info-value">{{ $data['appoint_id'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Type:</div>
                    <div class="info-value">{{ $data['appoint_type'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Date:</div>
                    <div class="info-value">{{ isset($data['appoint_date']) ? \Carbon\Carbon::parse($data['appoint_date'])->format('F d, Y') : 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Time:</div>
                    <div class="info-value">{{ isset($data['appoint_time']) ? \Carbon\Carbon::parse($data['appoint_time'])->format('h:i A') : 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Veterinarian:</div>
                    <div class="info-value">{{ $data['user_name'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Branch:</div>
                    <div class="info-value">{{ $data['branch_name'] ?? $branch->branch_name }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Status:</div>
                    <div class="info-value">
                        <span class="status-badge status-{{ strtolower($data['appoint_status'] ?? 'pending') }}">
                            {{ strtoupper($data['appoint_status'] ?? 'N/A') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        @if(isset($data['appoint_description']) && $data['appoint_description'])
            <div class="section">
                <div class="section-title">Description/Notes</div>
                <div style="padding: 10px; white-space: pre-wrap;">{{ $data['appoint_description'] }}</div>
            </div>
        @endif
        
    @elseif($reportType === 'billing')
        <div class="section">
            <div class="section-title">Customer Information</div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Customer Name:</div>
                    <div class="info-value">{{ $data['own_name'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Contact:</div>
                    <div class="info-value">{{ $data['own_contactnum'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Pet Name:</div>
                    <div class="info-value">{{ $data['pet_name'] ?? 'N/A' }}</div>
                </div>
            </div>
        </div>
        
        <div class="section">
            <div class="section-title">Bill Information</div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Bill ID:</div>
                    <div class="info-value">{{ $data['bill_id'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Service Date:</div>
                    <div class="info-value">{{ isset($data['appoint_date']) ? \Carbon\Carbon::parse($data['appoint_date'])->format('F d, Y') : 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Billing Date:</div>
                    <div class="info-value">{{ isset($data['bill_date']) ? \Carbon\Carbon::parse($data['bill_date'])->format('F d, Y') : 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Services:</div>
                    <div class="info-value">{{ $data['services'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Branch:</div>
                    <div class="info-value">{{ $branch->branch_name }}</div>
                </div>
            </div>
        </div>
        
        <div class="section">
            <div class="section-title">Payment Summary</div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Total Amount:</div>
                    <div class="info-value" style="font-size: 14pt; font-weight: bold; color: #059669;">
                        ₱{{ number_format($data['pay_total'] ?? 0, 2) }}
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Payment Status:</div>
                    <div class="info-value">
                        <span class="status-badge status-{{ strtolower($data['bill_status'] ?? 'pending') }}">
                            {{ strtoupper($data['bill_status'] ?? 'N/A') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
    @elseif($reportType === 'sales')
        <div class="section">
            <div class="section-title">Transaction Information</div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Order ID:</div>
                    <div class="info-value">{{ $data['ord_id'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Sale Date:</div>
                    <div class="info-value">{{ isset($data['ord_date']) ? \Carbon\Carbon::parse($data['ord_date'])->format('F d, Y') : 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Customer:</div>
                    <div class="info-value">{{ $data['own_name'] ?? 'Walk-in Customer' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Cashier:</div>
                    <div class="info-value">{{ $data['user_name'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Branch:</div>
                    <div class="info-value">{{ $branch->branch_name }}</div>
                </div>
            </div>
        </div>
        
        <div class="section">
            <div class="section-title">Product Details</div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Product Name:</div>
                    <div class="info-value" style="font-weight: bold;">{{ $data['prod_name'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Unit Price:</div>
                    <div class="info-value">₱{{ number_format($data['prod_price'] ?? 0, 2) }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Quantity:</div>
                    <div class="info-value">{{ $data['ord_quantity'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Total Amount:</div>
                    <div class="info-value" style="font-size: 14pt; font-weight: bold; color: #059669;">
                        ₱{{ number_format($data['ord_total'] ?? 0, 2) }}
                    </div>
                </div>
            </div>
        </div>
        
    @elseif($reportType === 'pets')
        <div class="section">
            <div class="section-title">Owner Information</div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Owner Name:</div>
                    <div class="info-value">{{ $data['own_name'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Contact Number:</div>
                    <div class="info-value">{{ $data['own_contactnum'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Address:</div>
                    <div class="info-value">{{ $data['own_location'] ?? 'N/A' }}</div>
                </div>
            </div>
        </div>
        
        <div class="section">
            <div class="section-title">Pet Profile</div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Pet ID:</div>
                    <div class="info-value">{{ $data['pet_id'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Pet Name:</div>
                    <div class="info-value" style="font-weight: bold;">{{ $data['pet_name'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Species:</div>
                    <div class="info-value">{{ $data['pet_species'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Breed:</div>
                    <div class="info-value">{{ $data['pet_breed'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Gender:</div>
                    <div class="info-value">{{ ucfirst($data['pet_gender'] ?? 'N/A') }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Age:</div>
                    <div class="info-value">{{ $data['pet_age'] ?? 'N/A' }} years</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Date of Birth:</div>
                    <div class="info-value">{{ isset($data['pet_birthdate']) ? \Carbon\Carbon::parse($data['pet_birthdate'])->format('F d, Y') : 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Weight:</div>
                    <div class="info-value">{{ $data['pet_weight'] ?? 'N/A' }} kg</div>
                </div>
                @if(isset($data['pet_temperature']))
                <div class="info-row">
                    <div class="info-label">Temperature:</div>
                    <div class="info-value">{{ $data['pet_temperature'] }} °C</div>
                </div>
                @endif
                <div class="info-row">
                    <div class="info-label">Registration Date:</div>
                    <div class="info-value">{{ isset($data['pet_registration']) ? \Carbon\Carbon::parse($data['pet_registration'])->format('F d, Y') : 'N/A' }}</div>
                </div>
            </div>
        </div>
        
    @elseif($reportType === 'inventory')
        <div class="section">
            <div class="section-title">Product Information</div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Product ID:</div>
                    <div class="info-value">{{ $data['prod_id'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Product Name:</div>
                    <div class="info-value" style="font-weight: bold;">{{ $data['prod_name'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Description:</div>
                    <div class="info-value">{{ $data['prod_description'] ?? 'No description available' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Branch:</div>
                    <div class="info-value">{{ $branch->branch_name }}</div>
                </div>
            </div>
        </div>
        
        <div class="section">
            <div class="section-title">Stock Information</div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Current Stock:</div>
                    <div class="info-value" style="font-size: 14pt; font-weight: bold;">{{ $data['prod_quantity'] ?? '0' }} units</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Unit Price:</div>
                    <div class="info-value">₱{{ number_format($data['prod_price'] ?? 0, 2) }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Total Value:</div>
                    <div class="info-value" style="font-weight: bold; color: #059669;">
                        ₱{{ number_format(($data['prod_quantity'] ?? 0) * ($data['prod_price'] ?? 0), 2) }}
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Stock Status:</div>
                    <div class="info-value">
                        <span class="status-badge status-{{ strtolower(str_replace(' ', '-', $data['stock_status'] ?? 'unknown')) }}">
                            {{ strtoupper($data['stock_status'] ?? 'N/A') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
    @elseif($reportType === 'referrals')
        <div class="section">
            <div class="section-title">Basic Information</div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Referral ID:</div>
                    <div class="info-value">{{ $data['ref_id'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Referral Date:</div>
                    <div class="info-value">{{ isset($data['ref_date']) ? \Carbon\Carbon::parse($data['ref_date'])->format('F d, Y') : 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Owner Name:</div>
                    <div class="info-value">{{ $data['own_name'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Contact:</div>
                    <div class="info-value">{{ $data['own_contactnum'] ?? 'N/A' }}</div>
                </div>
            </div>
        </div>
        
        <div class="section">
            <div class="section-title">Pet Information</div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Pet Name:</div>
                    <div class="info-value" style="font-weight: bold;">{{ $data['pet_name'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Species:</div>
                    <div class="info-value">{{ $data['pet_species'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Breed:</div>
                    <div class="info-value">{{ $data['pet_breed'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Gender:</div>
                    <div class="info-value">{{ ucfirst($data['pet_gender'] ?? 'N/A') }}</div>
                </div>
            </div>
        </div>
        
        <div class="section">
            <div class="section-title">Referral Details</div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Referring From:</div>
                    <div class="info-value">{{ $data['ref_by'] ?? $branch->branch_name }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Veterinarian:</div>
                    <div class="info-value">{{ $data['user_name'] ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Referred To:</div>
                    <div class="info-value" style="font-weight: bold;">{{ $data['ref_to'] ?? 'N/A' }}</div>
                </div>
            </div>
        </div>
        
        @if(isset($data['ref_description']) && $data['ref_description'])
            <div class="section">
                <div class="section-title">Reason for Referral</div>
                <div style="padding: 10px; white-space: pre-wrap;">{{ $data['ref_description'] }}</div>
            </div>
        @endif
        
        @if(isset($data['medical_history']) && $data['medical_history'])
            <div class="section">
                <div class="section-title">Medical History</div>
                <div style="padding: 10px; white-space: pre-wrap;">{{ $data['medical_history'] }}</div>
            </div>
        @endif
        
        @if(isset($data['tests_conducted']) && $data['tests_conducted'])
            <div class="section">
                <div class="section-title">Tests Conducted</div>
                <div style="padding: 10px; white-space: pre-wrap;">{{ $data['tests_conducted'] }}</div>
            </div>
        @endif
        
        @if(isset($data['medications_given']) && $data['medications_given'])
            <div class="section">
                <div class="section-title">Medications Given</div>
                <div style="padding: 10px; white-space: pre-wrap;">{{ $data['medications_given'] }}</div>
            </div>
        @endif
    @endif
    
    <!-- Footer -->
    <div class="footer">
        <strong>{{ $branch->branch_name }}</strong> | Generated: {{ \Carbon\Carbon::now()->format('F d, Y - h:i A') }}
    </div>
</body>
</html>