@extends('AdminBoard')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-50 p-4 sm:p-6">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <div class="max-w-7xl mx-auto space-y-6">
        <div class="flex items-center justify-between bg-white p-4 rounded-xl shadow-sm border">
            <h2 class="text-3xl font-bold text-gray-800">Grooming Service Workspace</h2>
            <a href="{{ route('medical.index', ['tab' => 'grooming']) }}" 
               class="px-4 py-2 bg-gray-200 border-2 border-gray-300 rounded-lg hover:bg-gray-300 font-medium shadow-sm transition">
                ← Back 
            </a>
        </div>
        <div class="space-y-6">

        {{-- Row 2: Pet Info (left) + Recent History (right) --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl shadow-sm border p-4 cursor-pointer hover:shadow-md transition" onclick="openPetProfileModal()">
                <div class="font-semibold text-gray-900 mb-1">{{ $visit->pet->pet_name ?? 'Pet' }} <span class="text-gray-500">({{ $visit->pet->pet_species ?? '—' }})</span></div>
                <div class="text-sm text-gray-700">Owner: <span class="font-medium">{{ $visit->pet->owner->own_name ?? '—' }}</span></div>
                <div class="text-xs text-gray-500 mt-1">Breed: {{ $visit->pet->pet_breed ?? '—' }}</div>
                <div class="text-xs text-gray-500">Weight: {{ $visit->weight ? number_format($visit->weight, 2).' kg' : '—' }} • Temp: {{ $visit->temperature ? number_format($visit->temperature, 1).' °C' : '—' }}</div>
                <div class="mt-3 inline-flex items-center gap-2 text-indigo-600 text-sm font-medium">View Full Profile <i class="fas fa-arrow-right"></i></div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border p-4 cursor-pointer hover:shadow-md transition" onclick="openMedicalHistoryModal()">
                <div class="font-semibold text-gray-900 mb-2">Recent Medical History</div>
                <div class="space-y-2 max-h-40 overflow-y-auto text-xs">
                    @forelse($petMedicalHistory as $record)
                        <div class="border-l-2 pl-2 {{ $record->diagnosis ? 'border-red-400' : 'border-gray-300' }}">
                            <div class="font-medium">{{ \Carbon\Carbon::parse($record->visit_date)->format('M j, Y') }}</div>
                            <div class="text-gray-700 truncate">{{ $record->diagnosis ?? $record->treatment ?? 'Routine Visit' }}</div>
                        </div>
                    @empty
                        <p class="text-gray-500 italic">No history</p>
                    @endforelse
                </div>
                <div class="mt-3 inline-flex items-center gap-2 text-indigo-600 text-sm font-medium">View Full History <i class="fas fa-arrow-right"></i></div>
            </div>
        </div>

        {{-- Row 3+: Main Content (full width) --}}
        <div class="space-y-6">
            {{-- Grooming Agreement Card (Fixed Structure) --}}
            <div id="agreement-card" class="bg-white rounded-xl shadow-lg p-6 border-l-4 {{ $visit->groomingAgreement ? 'border-green-500' : 'border-red-500' }} print:border-0 print:shadow-none print:p-0">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-gray-800">Grooming Agreement Consent</h3>
                    @if($visit->groomingAgreement)
                        <span class="text-sm px-3 py-1 rounded-full bg-green-100 text-green-800 font-semibold flex items-center gap-1">
                            <i class="fas fa-check-circle"></i> Signed
                        </span>
                    @else
                        <span class="text-sm px-3 py-1 rounded-full bg-red-100 text-red-800 font-semibold flex items-center gap-1">
                            <i class="fas fa-exclamation-triangle"></i> PENDING
                        </span>
                    @endif
                </div>
                
                {{-- AGREEMENT PENDING: Show the form to be signed --}}
                @if(!$visit->groomingAgreement)
                
                    <div class="p-4 border border-red-300 bg-red-50 rounded-lg text-sm mb-4">
                        <p class="font-semibold text-red-800">Action Required:</p>
                        <p class="text-gray-700">The Grooming Agreement must be viewed and electronically signed by the owner/representative before proceeding.</p>
                        <button type="button" onclick="openAgreementModal()" class="mt-3 px-4 py-1 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-medium">
                            <i class="fas fa-signature mr-1"></i> Open and Sign Agreement
                        </button>
                    </div>

                    {{-- Hidden Form: This is the actual form that gets populated and submitted --}}
                    <form id="agreement-form-data" action="{{ route('medical.visits.grooming.agreement.store', $visit->visit_id) }}" method="POST" class="hidden">
                        @csrf
                        <input type="hidden" name="signature_data" id="signature_data">
                        <input type="hidden" name="signer_name" id="signer_name_hidden">
                        <input type="hidden" name="color_markings" id="color_markings_hidden">
                        <input type="hidden" name="history_before" id="history_before_hidden">
                        <input type="hidden" name="history_after" id="history_after_hidden">
                        <input type="hidden" name="checkbox_acknowledge" value="1">
                        <button type="submit" id="finalSubmitAgreementBtn"></button>
                    </form>
                
                @else
                    {{-- AGREEMENT SIGNED: Display the signed details --}}
                    <div class="p-6">
                        <div class="grid grid-cols-3 gap-4 items-start print:gap-2 border p-4 rounded-lg bg-green-50 border-green-300">
                            <div>
                                <div class="text-sm text-gray-600">Signer</div>
                                <div class="font-semibold">{{ $visit->groomingAgreement->signer_name }}</div>
                                <div class="text-xs text-gray-500">{{ optional($visit->groomingAgreement->signed_at)->format('M d, Y h:i A') }}</div>
                            </div>
                            <div class="col-span-2">
                                <div class="text-sm text-gray-600">Signature:</div>
                                <img src="{{ asset('storage/'.$visit->groomingAgreement->signature_path) }}" alt="Signature" class="h-24 border rounded bg-white"/>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <div class="text-gray-700 font-semibold mb-1">Before Grooming Notes</div>
                                <div class="min-h-24 border rounded p-3 whitespace-pre-wrap bg-gray-50">{{ $visit->groomingAgreement->history_before ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="text-gray-700 font-semibold mb-1">After Grooming Notes</div>
                                <div class="min-h-24 border rounded p-3 whitespace-pre-wrap bg-gray-50">{{ $visit->groomingAgreement->history_after ?? '—' }}</div>
                            </div>
                        </div>

                        <div class="mt-4 flex justify-end">
                            <button type="button" onclick="window.print()" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">Print Agreement</button>
                        </div>
                    </div>
                @endif
            </div>

            @php
                $__groom = [];
                if (isset($serviceData) && $serviceData) {
                    $__groom = [
                        'grooming_type' => $serviceData->service_package ?? null,
                        'additional_services' => $serviceData->add_ons ?? null,
                        'instructions' => $serviceData->remarks ?? null,
                        'start_time' => $serviceData->start_time ? \Carbon\Carbon::parse($serviceData->start_time)->format('Y-m-d\TH:i') : null,
                        'end_time' => $serviceData->end_time ? \Carbon\Carbon::parse($serviceData->end_time)->format('Y-m-d\TH:i') : null,
                        'assigned_groomer' => $serviceData->groomer_name ?? (auth()->user()->user_name ?? null),
                    ];
                }
            @endphp
            
            <form action="{{ route('medical.visits.grooming.save', $visit->visit_id) }}" method="POST" class="space-y-6">
                @csrf
                <input type="hidden" name="visit_id" value="{{ $visit->visit_id }}">
                <input type="hidden" name="pet_id" value="{{ $visit->pet_id }}">
                <input type="hidden" name="weight" value="{{ $visit->weight }}">

                {{-- Grooming Details Card --}}
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center"><i class="fas fa-file-medical-alt mr-2 text-blue-600"></i> Service Record</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Assigned Groomer</label>
                            <input type="text" name="assigned_groomer" value="{{ old('assigned_groomer', $__groom['assigned_groomer'] ?? (auth()->user()->user_name ?? '')) }}" 
                                class="w-full border border-gray-300 p-3 rounded-lg" required/>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Grooming Type / Package <span class="text-red-500">*</span></label>
                            <select name="grooming_type" class="w-full border border-gray-300 p-3 rounded-lg" required>
                                <option value="">Select service type</option>
                                @foreach($availableServices as $service)
                                    <option value="{{ $service->serv_name }}" {{ ($__groom['grooming_type'] ?? '') === $service->serv_name ? 'selected' : '' }}>
                                        {{ $service->serv_name }} (₱{{ number_format($service->serv_price, 2) }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Pricing based on pet size/weight: <strong>{{ $visit->weight ?? 'N/A' }} kg</strong>.</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Add-ons</label>
                            <input type="text" name="additional_services" placeholder="Ear cleaning, nail trim, etc." 
                                value="{{ old('additional_services', $__groom['additional_services'] ?? '') }}"
                                class="w-full border border-gray-300 p-3 rounded-lg"/>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Start Time</label>
                                <input type="datetime-local" name="start_time" class="w-full border border-gray-300 p-3 rounded-lg" 
                                    value="{{ old('start_time', $__groom['start_time'] ?? '') }}"/>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">End Time</label>
                                <input type="datetime-local" name="end_time" class="w-full border border-gray-300 p-3 rounded-lg" 
                                    value="{{ old('end_time', $__groom['end_time'] ?? '') }}"/>
                            </div>
                        </div>
                        
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Notes / Observations</label>
                            <textarea name="instructions" rows="3" class="w-full border border-gray-300 p-3 rounded-lg" 
                                placeholder="Matting status, behavior during groom, any adverse findings.">{{ old('instructions', $__groom['instructions'] ?? '') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex justify-between items-center pt-4">
                    <button type="button" 
                            onclick="openActivityModal('{{ $visit->pet_id }}', '{{ $visit->pet->owner->own_id ?? 'N/A' }}', 'Grooming')"
                            class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-semibold shadow-md transition flex items-center gap-2">
                        <i class="fas fa-tasks"></i> Service Actions
                    </button>
                    
                    <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold shadow-md transition">
                        <i class="fas fa-save mr-1"></i> Save Grooming Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Pet Profile Modal (Photo + Pet & Owner Info Only) -->
<div id="petProfileModal" class="fixed inset-0 bg-black/60 z-50 hidden">
  <div class="w-full h-full flex items-center justify-center p-4" onclick="if(event.target===this){closePetProfileModal()}">
    <div class="bg-white rounded-xl shadow-2xl w-[600px] max-w-[95vw] max-h-[95vh] overflow-auto" onclick="event.stopPropagation()">
      <div class="flex items-center justify-between px-4 py-3 border-b">
        <h3 class="font-bold text-lg text-gray-800">Pet Profile</h3>
        <button type="button" onclick="closePetProfileModal()" class="px-3 py-1.5 text-sm bg-red-600 text-white hover:bg-red-700 rounded-md"><i class="fas fa-times mr-1"></i>Close</button>
      </div>
      <div class="p-6 space-y-4">
        <!-- Pet Photo -->
        <div class="w-full rounded-lg border bg-gray-50 flex items-center justify-center overflow-hidden">
          @if(!empty($visit->pet->pet_photo))
            <img src="{{ asset('storage/'.$visit->pet->pet_photo) }}" alt="{{ $visit->pet->pet_name ?? 'Pet' }}" class="w-full h-80 object-cover"/>
          @else
            <div class="h-80 w-full flex items-center justify-center text-gray-400 text-lg">
              <i class="fas fa-paw text-6xl"></i>
            </div>
          @endif
        </div>

        <!-- Pet Information -->
        <div class="bg-white rounded-lg border p-4">
          <div class="font-semibold text-gray-800 text-lg mb-3 flex items-center gap-2">
            <i class="fas fa-dog text-blue-600"></i> Pet Information
          </div>
          <div class="grid grid-cols-2 gap-3 text-sm">
            <div>
              <span class="text-gray-500">Name:</span>
              <div class="font-medium text-gray-800">{{ $visit->pet->pet_name ?? '—' }}</div>
            </div>
            <div>
              <span class="text-gray-500">Species:</span>
              <div class="font-medium text-gray-800">{{ $visit->pet->pet_species ?? '—' }}</div>
            </div>
            <div>
              <span class="text-gray-500">Breed:</span>
              <div class="font-medium text-gray-800">{{ $visit->pet->pet_breed ?? '—' }}</div>
            </div>
            <div>
              <span class="text-gray-500">Gender:</span>
              <div class="font-medium text-gray-800">{{ $visit->pet->pet_gender ?? '—' }}</div>
            </div>
            <div>
              <span class="text-gray-500">Age:</span>
              <div class="font-medium text-gray-800">{{ $visit->pet->pet_age ?? '—' }}</div>
            </div>
            <div>
              <span class="text-gray-500">Weight:</span>
              <div class="font-medium text-gray-800">{{ $visit->weight ? number_format($visit->weight, 2).' kg' : '—' }}</div>
            </div>
            <div class="col-span-2">
              <span class="text-gray-500">Temperature:</span>
              <div class="font-medium text-gray-800">{{ $visit->temperature ? number_format($visit->temperature, 1).' °C' : '—' }}</div>
            </div>
          </div>
        </div>

        <!-- Owner Information -->
        <div class="bg-white rounded-lg border p-4">
          <div class="font-semibold text-gray-800 text-lg mb-3 flex items-center gap-2">
            <i class="fas fa-user text-green-600"></i> Owner Information
          </div>
          <div class="space-y-2 text-sm">
            <div>
              <span class="text-gray-500">Name:</span>
              <div class="font-medium text-gray-800">{{ $visit->pet->owner->own_name ?? '—' }}</div>
            </div>
            <div>
              <span class="text-gray-500">Contact:</span>
              <div class="font-medium text-gray-800">{{ $visit->pet->owner->own_contactnum ?? '—' }}</div>
            </div>
            <div>
              <span class="text-gray-500">Location:</span>
              <div class="font-medium text-gray-800">{{ $visit->pet->owner->own_location ?? '—' }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Medical History Modal (History Only) -->
<div id="medicalHistoryModal" class="fixed inset-0 bg-black/60 z-50 hidden">
  <div class="w-full h-full flex items-center justify-center p-4" onclick="if(event.target===this){closeMedicalHistoryModal()}">
    <div class="bg-white rounded-xl shadow-2xl w-[900px] max-w-[95vw] max-h-[95vh] overflow-auto" onclick="event.stopPropagation()">
      <div class="flex items-center justify-between px-4 py-3 border-b">
        <h3 class="font-bold text-lg text-gray-800 flex items-center gap-2">
          <i class="fas fa-history text-orange-600"></i> 
          Complete Medical History - {{ $visit->pet->pet_name ?? 'Pet' }}
        </h3>
        <button type="button" onclick="closeMedicalHistoryModal()" class="px-3 py-1.5 text-sm bg-red-600 text-white hover:bg-red-700 rounded-md"><i class="fas fa-times mr-1"></i>Close</button>
      </div>
      <div class="p-6">
        <div class="space-y-4 max-h-[75vh] overflow-y-auto">
          @forelse($petMedicalHistory as $record)
            <div class="border-l-4 pl-4 py-3 {{ $record->diagnosis ? 'border-red-400 bg-red-50' : 'border-gray-300 bg-gray-50' }} rounded-r-lg">
              <div class="flex items-center justify-between mb-2">
                <div class="font-semibold text-gray-800 text-base">
                  {{ \Carbon\Carbon::parse($record->visit_date)->format('F j, Y') }}
                </div>
                @if(!empty($record->service_type))
                  <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-700 rounded">{{ $record->service_type }}</span>
                @endif
              </div>
              
              @if($record->diagnosis)
                <div class="mb-2">
                  <span class="text-xs font-semibold text-gray-600">Diagnosis:</span>
                  <div class="text-sm text-gray-800">{{ $record->diagnosis }}</div>
                </div>
              @endif

              @if($record->treatment)
                <div class="mb-2">
                  <span class="text-xs font-semibold text-gray-600">Treatment:</span>
                  <div class="text-sm text-gray-800">{{ $record->treatment }}</div>
                </div>
              @endif

              @if($record->medication)
                <div class="mb-2">
                  <span class="text-xs font-semibold text-gray-600">Medication:</span>
                  <div class="text-sm text-blue-700">{{ $record->medication }}</div>
                </div>
              @endif

              @if(!$record->diagnosis && !$record->treatment)
                <div class="text-sm text-gray-600 italic">Routine Visit</div>
              @endif
            </div>
          @empty
            <div class="text-center py-8">
              <i class="fas fa-clipboard-list text-gray-300 text-5xl mb-3"></i>
              <p class="text-gray-500 italic">No medical history on record.</p>
            </div>
          @endforelse
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  function openPetProfileModal() { 
    const m = document.getElementById('petProfileModal'); 
    if(m){ m.classList.remove('hidden'); } 
  }
  
  function closePetProfileModal() { 
    const m = document.getElementById('petProfileModal'); 
    if(m){ m.classList.add('hidden'); } 
  }

  function openMedicalHistoryModal() { 
    const m = document.getElementById('medicalHistoryModal'); 
    if(m){ m.classList.remove('hidden'); } 
  }
  
  function closeMedicalHistoryModal() { 
    const m = document.getElementById('medicalHistoryModal'); 
    if(m){ m.classList.add('hidden'); } 
  }
</script>

{{-- Grooming Agreement Modal (CRITICAL: Full HTML and JS required for interaction) --}}
<div id="groomingAgreementModal" class="hidden fixed inset-0 z-[70] flex items-center justify-center bg-black bg-opacity-70">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[95vh] overflow-y-auto p-6 relative">
        <div class="flex justify-between items-center mb-4 sticky top-0 bg-white z-10 border-b pb-2">
            <h3 class="text-xl font-bold text-red-600">Grooming Agreement & Liability Waiver</h3>
            <button type="button" onclick="closeAgreementModal()" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
        </div>
        
        {{-- Agreement Form Container --}}
        <form id="agreementForm" action="{{ route('medical.visits.grooming.agreement.store', $visit->visit_id) }}" method="POST" class="space-y-4">
            @csrf
            {{-- Hidden fields for data submission --}}
            <input type="hidden" name="signature_data" id="modal_signature_data">
            <input type="hidden" name="signer_name" value="{{ $visit->pet->owner->own_name ?? 'Guest Owner' }}">

            {{-- CSS for the document structure (Inline for exact replication) --}}
            <style>
                .doc-container{max-width:900px;margin:0 auto;background:#fff;border:2px solid #333;padding:32px; font-family: sans-serif;}
                .doc-header{border-bottom:2px solid #333;text-align:center;padding-bottom:16px;margin-bottom:20px}
                .doc-header h1{font-size:22px;font-weight:800;letter-spacing:1px}
                .doc-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:10px}
                .doc-label{font-weight:700;font-size:11px;text-transform:uppercase;margin-bottom:4px}
                .doc-input{border:none;padding:4px 0;font-size:14px;font-family:Courier New,monospace;width:100%;background:transparent}
                .doc-input[readonly]{color:#111}
                .doc-3col-owner{display:grid;grid-template-columns:1fr 2fr 1fr;gap:12px}
                .doc-history{width:100%;border-collapse:collapse;border:1px solid #333}
                .doc-history th{background:#f0f0f0;padding:10px;border:1px solid #333;text-align:left;font-size:12px}
                .doc-history td{border:1px solid #333;padding:8px;vertical-align:top}
                .doc-terms{margin-top:16px;line-height:1.7}
                .doc-term{font-size:13px;text-align:justify;margin-bottom:10px}
                .doc-sign{display:flex;gap:24px;align-items:flex-end;margin-top:20px;border-top:2px solid #333;padding-top:16px}
                .doc-sigbox{flex:1}
                .doc-siglabel{text-align:center;font-size:12px;font-weight:700;margin-top:4px}
            </style>

            <div class="doc-container">
                <div class="header mb-4 w-full">
                    <div class="p-4 rounded-lg w-full" style="background-color: #f88e28;">
                        <img src="{{ asset('images/header.jpg') }}" alt="Pets2GO Veterinary Clinic Header" class="w-full h-auto object-contain" style="max-height: 120px; min-height: 80px;">
                    </div>
                </div>
                <div class="doc-header"><h1>GROOMING AGREEMENT CONSENT</h1></div>

                <div class="doc-grid">
                    <div>
                        <div class="doc-label">Date and Time</div>
                        <input class="doc-input" type="text" readonly value="{{ now()->format('F j, Y g:i A') }}">
                    </div>
                </div>

                <div class="doc-3col-owner" style="margin-top:6px">
                    <div>
                        <div class="doc-label">Owner's Name</div>
                        <input class="doc-input" type="text" readonly value="{{ $visit->pet->owner->own_name ?? '' }}">
                    </div>
                    <div>
                        <div class="doc-label">Address</div>
                        <div class="doc-input">{{ $visit->pet->owner->own_location ?? '' }}</div>
                    </div>
                    <div>
                        <div class="doc-label">Phone Number</div>
                        <input class="doc-input" type="text" readonly value="{{ $visit->pet->owner->own_contactnum ?? '' }}">
                    </div>
                </div>

                <div class="doc-3col-owner">
                    <div>
                        <div class="doc-label">Name of Pet</div>
                        <input class="doc-input" type="text" readonly value="{{ $visit->pet->pet_name ?? '' }}">
                    </div>
                    <div>
                        <div class="doc-label">Species</div>
                        <input class="doc-input" type="text" readonly value="{{ $visit->pet->pet_species ?? '' }}">
                    </div>
                    <div>
                        <div class="doc-label">Gender</div>
                        <input class="doc-input" type="text" readonly value="{{ $visit->pet->pet_gender ?? '' }}">
                    </div>
                </div>

                <div class="doc-3col-owner mb-4">
                    <div>
                        <div class="doc-label">Pet Age</div>
                        <input class="doc-input" type="text" readonly value="{{ $visit->pet->pet_age ?? '' }}">
                    </div>
                    <div>
                        <div class="doc-label">Breed</div>
                        <input class="doc-input" type="text" readonly value="{{ $visit->pet->pet_breed ?? '' }}">
                    </div>
                    <div>
                        <div class="doc-label">Color Markings</div>
                        <input class="doc-input" type="text" name="color_markings" id="modal_color_markings" placeholder="e.g. Black with white paws">
                    </div>
                </div>

                <div style="text-align:center;font-weight:800;margin:16px 0;text-transform:uppercase">History</div>
                <table class="doc-history">
                    <thead>
                        <tr>
                            <th style="width:50%">Before Grooming (Notes)</th>
                            <th style="width:50%">After Grooming (Notes)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <textarea name="history_before" id="modal_history_before" rows="6" style="width:100%;border:none;outline:none;resize:vertical;font-family: sans-serif;" placeholder="E.g., Severe matting, aggressive behavior, pre-existing warts/lumps"></textarea>
                            </td>
                            <td>
                                <textarea name="history_after" id="modal_history_after" rows="6" style="width:100%;border:none;outline:none;resize:vertical;font-family: sans-serif;" placeholder="E.g., No reaction, shaved cleanly, needed sedation (not applicable for agreement)"></textarea>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="doc-terms">
                    <div class="doc-term">1. I certify that I am the owner of (or person responsible for) the pet described above.</div>
                    <div class="doc-term">2. I understand that grooming entails hair trimming, bathing, nail clipping and ear cleaning. No physical examination or check up is included in the process of grooming. All pets shall be presented healthy and regular handling procedures will be instituted, unless I inform the staff beforehand of any pre-existing medical conditions.</div>
                    <div class="doc-term">3. Grooming can be stressful to animals; however, the grooming staff will use reasonable precautions against injury, escape or death of my pet. I am aware that sometimes skin reactions may arise due to my pet's skin sensitivity. Therefore, the establishment shall not be held liable for any problem that may transpire from either stress or reaction brought about by grooming of my pet, provided reasonable care and precautions were strictly followed. I understand that any problem that may develop with my pet will be treated as deemed best by the staff veterinarian and I assume full responsibility for the treatment expense involved.</div>
                    <div class="doc-term">4. The groomers make no claim of expertise in grooming any particular breed. Groomers will make reasonable effort to conform to my grooming requests; however, no guarantees are made that the exact grooming cut can be followed.</div>
                    <div class="doc-term">5. Grooming may take a few hours to complete and pets will be served on a FIRST COME FIRST SERVED basis.</div>
                    <div class="doc-term" style="text-align:center;font-weight:800;margin-top:10px">After carefully reading the above, I have signed an agreement.</div>
                </div>

                <div class="doc-sign">
                    <div class="doc-sigbox">
                        <div class="doc-label">Signature</div>
                        <div class="bg-white border rounded">
                            <canvas id="modal-signature-pad" class="w-full" style="height: 140px;"></canvas>
                        </div>
                        <div class="doc-siglabel">Signature of Owner/Representative</div>
                    </div>
                    <div class="doc-sigbox">
                        <div class="doc-label">Signer Name</div>
                        <input class="doc-input" type="text" id="modal_signer_name" value="{{ $visit->pet->owner->own_name ?? '' }}" placeholder="Owner / Representative">
                        <div class="doc-label" style="margin-top:16px">Date</div>
                        <input class="doc-input" type="text" readonly value="{{ now()->format('F j, Y') }}">
                    </div>
                </div>

                <div class="mt-4 flex items-center gap-2">
                    <input type="checkbox" name="checkbox_acknowledge" id="modal_checkbox_acknowledge" value="1" required>
                    <span class="text-sm">I have carefully read the above and agree.</span>
                </div>
            </div>

            <div class="flex justify-between mt-4 print-hide">
                <button type="button" onclick="closeAgreementModal()" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">Cancel</button>
                <button type="button" id="modal-sig-clear" class="px-4 py-2 bg-gray-200 rounded">Clear Signature</button>
                <button type="button" id="submitAgreementBtn" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Sign Agreement</button>
            </div>
        </form>
    </div>
</div>

@include('modals.service_activity_modal', [
    'allPets' => $allPets, 
    'allBranches' => $allBranches, 
    'allProducts' => $allProducts,
])

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const agreementModal = document.getElementById('groomingAgreementModal');
        const submitBtn = document.getElementById('submitAgreementBtn');
        const canvas = document.getElementById('modal-signature-pad');
        
        let signaturePad = null;

        // --- Signature Pad Setup ---
        
        if (canvas) {
            function resizeCanvas() {
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                const rect = canvas.getBoundingClientRect();
                canvas.width = rect.width * ratio;
                canvas.height = rect.height * ratio;
                if (signaturePad) {
                    signaturePad.clear();
                    const ctx = canvas.getContext('2d');
                    ctx.scale(ratio, ratio);
                }
            }
            
            window.initializeSignaturePad = function() {
                if (!signaturePad) {
                    signaturePad = new SignaturePad(canvas, {
                        backgroundColor: 'rgba(255,255,255,1)',
                        throttle: 10
                    });
                } else {
                    signaturePad.clear();
                }
                resizeCanvas();
            };

            document.getElementById('modal-sig-clear').addEventListener('click', function() {
                if (signaturePad) signaturePad.clear();
            });
            
            // Re-initialize pad when the modal is opened
            agreementModal.addEventListener('transitionend', function() {
                if (!agreementModal.classList.contains('hidden')) {
                    initializeSignaturePad();
                }
            });
            window.addEventListener('resize', resizeCanvas);
            
            // Initial call if the script loads late
            initializeSignaturePad();
        }

        // --- Modal Control Functions ---
        
        window.openAgreementModal = function() {
            agreementModal.classList.remove('hidden');
            agreementModal.classList.add('flex');
            
            // Pre-populate fields from the main form (assuming they exist)
            document.getElementById('modal_history_before').value = document.querySelector('textarea[name="instructions"]').value || '';
            document.getElementById('modal_signer_name').value = '{{ $visit->pet->owner->own_name ?? 'Guest Owner' }}';
            
            // Re-initialize pad when opening
            if (canvas) initializeSignaturePad(); 
        };

        window.closeAgreementModal = function() {
            agreementModal.classList.add('hidden');
            agreementModal.classList.remove('flex');
            if (signaturePad) signaturePad.clear();
        };

        // --- Form Submission Logic for Agreement ---

        if (submitBtn) {
            submitBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                if (signaturePad && signaturePad.isEmpty()) {
                    alert('Please provide a signature before submitting the agreement.');
                    return;
                }
                if (!document.getElementById('modal_checkbox_acknowledge').checked) {
                    alert('You must acknowledge and agree to the terms.');
                    return;
                }
                
                // 1. Capture signature and other required fields into hidden fields
                document.getElementById('signature_data').value = signaturePad.toDataURL('image/png');
                
                // 2. Map textarea content to hidden fields for backend consumption
                document.getElementById('history_before_hidden').value = document.getElementById('modal_history_before').value;
                document.getElementById('history_after_hidden').value = document.getElementById('modal_history_after').value;
                document.getElementById('color_markings_hidden').value = document.getElementById('modal_color_markings').value;

                // 3. Optional: Disable button and submit
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
                
                // Submit the actual form that holds the hidden inputs
                document.getElementById('agreementForm').submit();
            });
        }
    });
</script>
@endpush
@endsection