<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referral Letter - {{ $referral->pet->pet_name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; padding: 40px; line-height: 1.6; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #333; padding-bottom: 20px; }
        .header h1 { color: #333; font-size: 24px; margin-bottom: 10px; }
        .header p { color: #666; font-size: 14px; }
        .section { margin-bottom: 25px; }
        .section-title { background: #f0f0f0; padding: 10px; font-weight: bold; border-left: 4px solid #333; margin-bottom: 15px; }
        .info-grid { display: grid; grid-template-columns: 150px 1fr; gap: 10px; }
        .info-label { font-weight: bold; color: #555; }
        .info-value { color: #333; }
        .medical-history { margin-top: 20px; }
        .medical-item { background: #f9f9f9; padding: 15px; margin-bottom: 15px; border-left: 3px solid #4CAF50; }
        .medical-item h4 { color: #4CAF50; margin-bottom: 8px; font-size: 14px; }
        .medical-item p { font-size: 13px; color: #666; margin: 5px 0; }
        .no-record { color: #999; font-style: italic; padding: 10px; background: #f9f9f9; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 2px solid #eee; }
        .signature { margin-top: 50px; }
        .signature-line { border-top: 1px solid #333; width: 250px; margin-top: 50px; padding-top: 5px; }
        @media print {
            body { padding: 20px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <!-- Print Button -->
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">
            Print Referral
        </button>
        <button onclick="window.close()" style="background: #f44336; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin-left: 10px;">
            Close
        </button>
    </div>

    <!-- Header -->
    <div class="header">
        <h1>VETERINARY REFERRAL LETTER</h1>
        <p>Date: {{ \Carbon\Carbon::parse($referral->ref_date)->format('F d, Y') }}</p>
        <p>Referral ID: {{ $referral->ref_id }}</p>
    </div>

    <!-- Referring Branch Information -->
    <div class="section">
        <div class="section-title">From (Referring Branch)</div>
        <div class="info-grid">
            <div class="info-label">Branch Name:</div>
            <div class="info-value">{{ $referral->refByBranch->branch->branch_name ?? 'N/A' }}</div>
            
            <div class="info-label">Address:</div>
            <div class="info-value">{{ $referral->refByBranch->branch->branch_location ?? 'N/A' }}</div>
            
            <div class="info-label">Veterinarian:</div>
            <div class="info-value">Dr. {{ $referral->refByBranch->fname ?? '' }} {{ $referral->refByBranch->lname ?? '' }}</div>
            
            <div class="info-label">Date:</div>
            <div class="info-value">{{ \Carbon\Carbon::parse($referral->ref_date)->format('F d, Y') }}</div>
        </div>
    </div>

    <!-- Referred To Information -->
    <div class="section">
        <div class="section-title">To (External Clinic/Hospital)</div>
        <div class="info-grid">
            @if($referral->external_clinic_name)
                <div class="info-label">Clinic Name:</div>
                <div class="info-value">{{ $referral->external_clinic_name }}</div>
            @elseif($referral->referralCompany)
                <div class="info-label">Clinic Name:</div>
                <div class="info-value">{{ $referral->referralCompany->name }}</div>
                
                <div class="info-label">Address:</div>
                <div class="info-value">{{ $referral->referralCompany->address }}</div>
                
                <div class="info-label">Contact:</div>
                <div class="info-value">{{ $referral->referralCompany->contact_number }}</div>
            @else
                <div class="info-value" style="grid-column: span 2;">External Veterinary Clinic</div>
            @endif
        </div>
    </div>

    <!-- Patient Information -->
    <div class="section">
        <div class="section-title">Patient Information</div>
        <div class="info-grid">
            <div class="info-label">Pet Name:</div>
            <div class="info-value">{{ $referral->pet->pet_name }}</div>
            
            <div class="info-label">Species:</div>
            <div class="info-value">{{ $referral->pet->pet_type }}</div>
            
            <div class="info-label">Breed:</div>
            <div class="info-value">{{ $referral->pet->pet_breed }}</div>
            
            <div class="info-label">Age:</div>
            <div class="info-value">{{ $referral->pet->pet_age ?? 'N/A' }}</div>
            
            <div class="info-label">Sex:</div>
            <div class="info-value">{{ $referral->pet->pet_sex }}</div>
            
            <div class="info-label">Weight:</div>
            <div class="info-value">{{ $referral->pet->pet_weight ?? 'N/A' }} kg</div>
            
            <div class="info-label">Owner Name:</div>
            <div class="info-value">{{ $referral->pet->owner->owner_fname }} {{ $referral->pet->owner->owner_lname }}</div>
            
            <div class="info-label">Owner Contact:</div>
            <div class="info-value">{{ $referral->pet->owner->owner_phone }}</div>
        </div>
    </div>

    <!-- Reason for Referral -->
    <div class="section">
        <div class="section-title">Reason for Referral</div>
        <p style="padding: 10px; background: #f9f9f9;">{{ $referral->ref_description }}</p>
    </div>

    <!-- Medical History Section -->
    <div class="section medical-history">
        <div class="section-title">Medical History</div>
        @if(count($medicalHistory) > 0)
            <div style="padding: 10px; background: #f9f9f9;">
                @foreach($medicalHistory as $record)
                    <p style="font-size: 13px; color: #333; margin: 5px 0; white-space: pre-wrap; word-break: keep-all;">{{ $record['formatted'] }}</p>
                @endforeach
            </div>
        @else
            <p class="no-record">No medical history records available</p>
        @endif
    </div>

    <!-- Tests Conducted Section -->
    <div class="section medical-history">
        <div class="section-title">Tests Conducted (Diagnostics/Laboratory)</div>
        @if($diagnosticTests && count($diagnosticTests) > 0)
            <div style="padding: 10px; background: #f9f9f9;">
                @foreach($diagnosticTests as $test)
                    <p style="font-size: 13px; color: #333; margin: 5px 0; white-space: pre-wrap; word-break: keep-all;">{{ $test->formatted }}</p>
                @endforeach
            </div>
        @else
            <p class="no-record">No diagnostic test records available</p>
        @endif
    </div>

    <!-- Medications Given Section -->
    <div class="section medical-history">
        <div class="section-title">Medications Given</div>
        @if($prescriptions && count($prescriptions) > 0)
            <div style="padding: 10px; background: #f9f9f9;">
                @foreach($prescriptions as $prescription)
                    @if($prescription->medication)
                        @php
                            $dateFormatted = \Carbon\Carbon::parse($prescription->prescription_date)->format('M d, Y') . ': ';
                            $medications = json_decode($prescription->medication, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($medications)) {
                                // JSON format
                                $medList = [];
                                foreach ($medications as $med) {
                                    $medStr = $med['product_name'] ?? '';
                                    if (!empty($med['instructions'])) {
                                        $medStr .= ' (' . $med['instructions'] . ')';
                                    }
                                    if ($medStr) {
                                        $medList[] = $medStr;
                                    }
                                }
                                $displayText = $dateFormatted . implode(', ', $medList);
                            } else {
                                // Plain text
                                $displayText = $dateFormatted . $prescription->medication;
                            }
                        @endphp
                        <p style="font-size: 13px; color: #333; margin: 5px 0; white-space: pre-wrap; word-break: keep-all;">{{ $displayText }}</p>
                    @endif
                @endforeach
            </div>
        @else
            <p class="no-record">No prescription records available</p>
        @endif
    </div>

    <!-- Footer & Signature -->
    <div class="footer">
        <p style="font-size: 13px; color: #666; margin-bottom: 20px;">
            This referral letter contains the latest medical history for the above-mentioned patient. 
            Please contact the referring branch if additional information is needed.
        </p>
        
        <div class="signature">
            <p style="margin-bottom: 5px;">Referring Veterinarian:</p>
            <div class="signature-line">
                <p style="text-align: center; font-weight: bold;">
                    Dr. {{ $referral->refByBranch->fname ?? '' }} {{ $referral->refByBranch->lname ?? '' }}
                </p>
                <p style="text-align: center; font-size: 12px; color: #666;">
                    {{ $referral->refByBranch->branch->branch_name ?? '' }}
                </p>
            </div>
        </div>
    </div>
</body>
</html>
