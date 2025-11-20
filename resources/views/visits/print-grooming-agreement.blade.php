<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Grooming Agreement - {{ $visit->pet->pet_name ?? 'Pet' }}</title>
    <style>
        @page { size: legal; margin: 0.4in; }
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 11px;
            color: #1f2937;
        }
        .document-wrapper {
            width: 8.5in;
            min-height: 13in;
            margin: 0 auto;
            border: 2px solid #111;
            padding: 0.35in;
            box-sizing: border-box;
        }
        .header-banner {
            text-align: center;
            margin-bottom: 12px;
            background-color: #f38d35;
        }
        .header-banner img {
            max-width: 7.5in;
            height: 85px;
            object-fit: contain;
        }
        h1 {
            text-align: center;
            font-size: 18px;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid #111;
            padding-bottom: 6px;
            margin: 10px 0 16px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 12px;
        }
        .grid label {
            display: block;
            font-size: 9px;
            text-transform: uppercase;
            color: #4b5563;
            margin-bottom: 2px;
            font-weight: 600;
        }
        .grid .value {
            border-bottom: 1px solid #111;
            min-height: 18px;
            padding: 2px 0;
        }
        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0 16px;
        }
        .history-table th,
        .history-table td {
            border: 1px solid #111;
            padding: 8px;
            vertical-align: top;
        }
        .history-table th {
            background: #f3f4f6;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.5px;
        }
        .terms {
            font-size: 10px;
            line-height: 1.4;
            text-align: justify;
            margin-bottom: 16px;
        }
        .signature-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 18px;
            border-top: 2px solid #111;
            padding-top: 12px;
        }
        .signature-box {
            border: 1px solid #111;
            height: 110px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
        }
        .sig-label {
            font-size: 10px;
            text-align: center;
            margin-top: 6px;
            text-transform: uppercase;
            font-weight: 600;
        }
        .meta {
            margin-top: 24px;
            font-size: 10px;
            color: #6b7280;
        }
    </style>
</head>
<body onload="window.print()">
    @php
        $owner = optional(optional($visit)->pet)->owner;
        $pet = optional($visit->pet);
    @endphp
    <div class="document-wrapper">
        <div class="header-banner">
            <img src="{{ asset('images/header.jpg') }}" alt="Clinic Header">
        </div>
        <h1>Grooming Agreement Consent</h1>

        <div class="grid">
            <div>
                <label>Date Signed</label>
                <div class="value">{{ optional($agreement->signed_at)->format('F d, Y g:i A') ?? '—' }}</div>
            </div>
            <div>
                <label>Generated On</label>
                <div class="value">{{ $generatedAt->format('F d, Y g:i A') }}</div>
            </div>
            <div>
                <label>Visit ID</label>
                <div class="value">#{{ $visit->visit_id }}</div>
            </div>
        </div>

        <div class="grid">
            <div>
                <label>Owner Name</label>
                <div class="value">{{ $owner->own_name ?? '—' }}</div>
            </div>
            <div>
                <label>Address</label>
                <div class="value">{{ $owner->own_location ?? '—' }}</div>
            </div>
            <div>
                <label>Contact Number</label>
                <div class="value">{{ $owner->own_contactnum ?? '—' }}</div>
            </div>
        </div>

        <div class="grid">
            <div>
                <label>Pet Name</label>
                <div class="value">{{ $pet->pet_name ?? '—' }}</div>
            </div>
            <div>
                <label>Species / Breed</label>
                <div class="value">
                    {{ $pet->pet_species ?? '—' }}
                    {{ $pet->pet_breed ? ' / '.$pet->pet_breed : '' }}
                </div>
            </div>
            <div>
                <label>Gender / Age</label>
                <div class="value">
                    {{ $pet->pet_gender ?? '—' }}
                    {{ $pet->pet_age ? ' / '.$pet->pet_age : '' }}
                </div>
            </div>
        </div>

        <div class="grid">
            <div>
                <label>Color Markings</label>
                <div class="value">{{ $agreement->color_markings ?? '—' }}</div>
            </div>
            <div>
                <label>Recorded Weight</label>
                <div class="value">
                    @if(!is_null($visit->weight))
                        {{ number_format((float) $visit->weight, 2) }} kg
                    @else
                        —
                    @endif
                </div>
            </div>
            <div>
                <label>Recorded Temperature</label>
                <div class="value">
                    @if(!is_null($visit->temperature))
                        {{ number_format((float) $visit->temperature, 1) }} °C
                    @else
                        —
                    @endif
                </div>
            </div>
        </div>

        <table class="history-table">
            <thead>
                <tr>
                    <th>Before Grooming (Notes)</th>
                    <th>After Grooming (Notes)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{!! nl2br(e($agreement->history_before ?? '—')) !!}</td>
                    <td>{!! nl2br(e($agreement->history_after ?? '—')) !!}</td>
                </tr>
            </tbody>
        </table>

        <div class="terms">
            <p><strong>1.</strong> I certify that I am the owner (or representative) of the pet described above.</p>
            <p><strong>2.</strong> I understand that grooming entails bathing, brushing, hair trimming, ear cleaning, and nail clipping; medical treatment is not included.</p>
            <p><strong>3.</strong> I acknowledge that grooming can be stressful and may expose pre-existing medical conditions. The clinic is authorized to use reasonable precautions and provide necessary treatment at my expense.</p>
            <p><strong>4.</strong> I understand that style requests are subject to my pet’s coat, condition, and temperament, and exact cuts are not guaranteed.</p>
            <p><strong>5.</strong> I agree that grooming may take several hours, and pets are served on a first-come, first-served basis.</p>
        </div>

        <div class="signature-section">
            <div>
                <div class="signature-box">
                    @if($signatureUrl)
                        <img src="{{ $signatureUrl }}" alt="Owner Signature" style="max-width:100%; max-height:100%; object-fit:contain;">
                    @else
                        <span style="color:#9ca3af;">Signature unavailable</span>
                    @endif
                </div>
                <div class="sig-label">Signature of Owner / Representative</div>
            </div>
            <div>
                <label>Signer Name</label>
                <div class="value" style="margin-bottom:10px;">{{ $agreement->signer_name ?? '—' }}</div>
                <label>IP Address</label>
                <div class="value" style="margin-bottom:10px;">{{ $agreement->ip_address ?? '—' }}</div>
                <label>User Agent</label>
                <div class="value" style="min-height:40px;">{{ $agreement->user_agent ?? '—' }}</div>
            </div>
        </div>

        <div class="meta">
            Printed via MBV CMS • Generated {{ $generatedAt->toDayDateTimeString() }}
        </div>
    </div>
</body>
</html>

