@extends('AdminBoard')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-5xl mx-auto px-4">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">Referral Details</h1>
                    <p class="text-sm text-gray-600">Referral ID: #{{ $referral->ref_id }}</p>
                    <p class="text-sm text-gray-600">Date: {{ \Carbon\Carbon::parse($referral->ref_date)->format('F d, Y') }}</p>
                </div>
                <div class="flex space-x-2">
                    @if($referral->ref_type === 'external')
                        <a href="{{ route('medical.referrals.print', $referral->ref_id) }}" 
                           target="_blank"
                           class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            <i class="fas fa-print mr-2"></i>Print Referral
                        </a>
                    @endif
                    <a href="{{ route('medical.index', ['tab' => 'referrals']) }}" 
                       class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                        <i class="fas fa-arrow-left mr-2"></i>Back
                    </a>
                </div>
            </div>
        </div>

        <!-- Status Badge -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold mb-2">Referral Status</h3>
                    <span class="px-4 py-2 rounded-full text-sm font-semibold
                        {{ $referral->ref_status === 'attended' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                        {{ ucfirst($referral->ref_status) }}
                    </span>
                </div>
                <div>
                    <span class="px-4 py-2 rounded-full text-sm font-semibold
                        {{ $referral->ref_type === 'interbranch' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                        {{ ucfirst($referral->ref_type) }} Referral
                    </span>
                </div>
            </div>
        </div>

        <!-- Patient Information -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h3 class="text-lg font-semibold mb-4 border-b pb-2">Patient Information</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-600">Pet Name</p>
                    <p class="font-semibold">{{ $referral->pet->pet_name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Species/Breed</p>
                    <p class="font-semibold">{{ $referral->pet->pet_type }} - {{ $referral->pet->pet_breed }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Owner</p>
                    <p class="font-semibold">{{ $referral->pet->owner->owner_fname }} {{ $referral->pet->owner->owner_lname }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Contact</p>
                    <p class="font-semibold">{{ $referral->pet->owner->owner_phone }}</p>
                </div>
            </div>
        </div>

        <!-- Referral Information -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h3 class="text-lg font-semibold mb-4 border-b pb-2">Referral Information</h3>
            
            <!-- From -->
            <div class="mb-4">
                <p class="text-sm text-gray-600 mb-2">Referred From</p>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="font-semibold">{{ $referral->refByBranch->branch->branch_name ?? 'N/A' }}</p>
                    <p class="text-sm text-gray-600">{{ $referral->refByBranch->branch->branch_location ?? '' }}</p>
                    <p class="text-sm text-gray-600">Dr. {{ $referral->refByBranch->fname }} {{ $referral->refByBranch->lname }}</p>
                </div>
            </div>

            <!-- To -->
            <div class="mb-4">
                <p class="text-sm text-gray-600 mb-2">Referred To</p>
                <div class="bg-gray-50 p-4 rounded-lg">
                    @if($referral->ref_type === 'interbranch')
                        <p class="font-semibold">{{ $referral->refToBranch->branch_name ?? 'N/A' }}</p>
                        <p class="text-sm text-gray-600">{{ $referral->refToBranch->branch_location ?? '' }}</p>
                    @elseif($referral->external_clinic_name)
                        <p class="font-semibold">{{ $referral->external_clinic_name }}</p>
                    @elseif($referral->referralCompany)
                        <p class="font-semibold">{{ $referral->referralCompany->name }}</p>
                        <p class="text-sm text-gray-600">{{ $referral->referralCompany->address }}</p>
                        <p class="text-sm text-gray-600">{{ $referral->referralCompany->contact_number }}</p>
                    @else
                        <p class="text-gray-500">External Veterinary Clinic</p>
                    @endif
                </div>
            </div>

            <!-- Reason -->
            <div>
                <p class="text-sm text-gray-600 mb-2">Reason for Referral</p>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <p>{{ $referral->ref_description }}</p>
                </div>
            </div>
        </div>

        <!-- Original Visit Information -->
        @if($referral->visit)
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h3 class="text-lg font-semibold mb-4 border-b pb-2">Original Visit Information</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-600">Visit Date</p>
                    <p class="font-semibold">{{ \Carbon\Carbon::parse($referral->visit->visit_date)->format('F d, Y') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Visit Status</p>
                    <p class="font-semibold">{{ ucfirst($referral->visit->visit_status) }}</p>
                </div>
                @if($referral->visit->services->count() > 0)
                <div class="col-span-2">
                    <p class="text-sm text-gray-600">Services</p>
                    <p class="font-semibold">{{ $referral->visit->services->pluck('serv_name')->join(', ') }}</p>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Referred Visit Information (For Interbranch) -->
        @if($referral->ref_type === 'interbranch' && $referral->referredVisit)
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h3 class="text-lg font-semibold mb-4 border-b pb-2">Referred Branch Visit</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-600">Visit ID</p>
                    <p class="font-semibold">#{{ $referral->referredVisit->visit_id }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Visit Date</p>
                    <p class="font-semibold">{{ \Carbon\Carbon::parse($referral->referredVisit->visit_date)->format('F d, Y') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Status</p>
                    <p class="font-semibold">{{ ucfirst($referral->referredVisit->visit_status) }}</p>
                </div>
                <div>
                    <a href="{{ route('visits.show', $referral->referredVisit->visit_id) }}" 
                       class="text-blue-600 hover:underline">
                        View Visit Details →
                    </a>
                </div>
            </div>
        </div>
        @endif

        <!-- Medical History (From Completed Services) -->
        @if(isset($medicalHistory) && count($medicalHistory) > 0)
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h3 class="text-lg font-semibold mb-4 border-b pb-2">Medical History (Completed Services)</h3>
            <div class="space-y-2">
                @foreach($medicalHistory as $record)
                    <p class="text-sm text-gray-700" style="white-space: pre-wrap; word-break: keep-all;">{{ $record['formatted'] }}</p>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Tests Conducted (Diagnostics) -->
        @if(isset($diagnosticTests) && count($diagnosticTests) > 0)
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h3 class="text-lg font-semibold mb-4 border-b pb-2">Tests Conducted (Diagnostics)</h3>
            <div class="space-y-2">
                @foreach($diagnosticTests as $test)
                    <p class="text-sm text-gray-700" style="white-space: pre-wrap; word-break: keep-all;">{{ $test->formatted }}</p>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Medications Given (Prescriptions) -->
        @if(isset($prescriptions) && count($prescriptions) > 0)
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h3 class="text-lg font-semibold mb-4 border-b pb-2">Medications Given</h3>
            <div class="space-y-2">
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
                        <p class="text-sm text-gray-700" style="white-space: pre-wrap; word-break: keep-all;">{{ $displayText }}</p>
                    @endif
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
