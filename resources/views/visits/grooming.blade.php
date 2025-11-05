@extends('AdminBoard')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-rose-50 to-pink-50 p-4 sm:p-6">
  <div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center justify-between bg-white p-4 rounded-xl shadow-sm border">
      <h2 class="text-3xl font-bold text-gray-800">Grooming Services Workspace</h2>
      <a href="{{ route('medical.index', ['tab' => 'grooming']) }}" class="px-4 py-2 bg-gray-200 border-2 border-gray-300 rounded-lg hover:bg-gray-300 font-medium">← Back</a>
    </div>
    <div class="bg-white rounded-xl shadow-sm border p-4">
      <div class="font-semibold text-gray-900 mb-1">{{ $visit->pet->pet_name ?? 'Pet' }} <span class="text-gray-500">({{ $visit->pet->pet_species ?? '—' }})</span></div>
      <div class="text-sm text-gray-700">Owner: <span class="font-medium">{{ $visit->pet->owner->own_name ?? '—' }}</span></div>
      <div class="mt-3 inline-flex items-center gap-2">
        <button onclick="openAgreementModal()" class="px-4 py-2 bg-orange-500 text-white rounded-lg">Open Grooming Agreement</button>
        <button onclick="openActivityModal('{{ $visit->pet_id }}', '{{ $visit->pet->owner->own_id ?? 'N/A' }}', 'Grooming')" class="px-4 py-2 bg-indigo-600 text-white rounded-lg">Service Actions</button>
      </div>
    </div>
  </div>

    {{-- Service Record Form --}}
    <div class="max-w-7xl mx-auto mt-6">
      <div class="bg-white rounded-xl shadow-sm border p-6">
        <form id="groomingServiceForm" action="{{ route('medical.visits.grooming.update', $visit->visit_id) }}" method="POST" class="space-y-6">
          @csrf
          @method('PUT')
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-4">
              <h3 class="text-lg font-semibold text-gray-900">General Assessment</h3>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Coat Condition</label>
                <select name="coat_condition" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                  <option value="">Select condition...</option>
                  @php
                    $currentCondition = old('coat_condition', $visit->services->first()?->pivot?->coat_condition ?? '');
                  @endphp
                  <option value="excellent" {{ $currentCondition == 'excellent' ? 'selected' : '' }}>Excellent</option>
                  <option value="good" {{ $currentCondition == 'good' ? 'selected' : '' }}>Good</option>
                  <option value="fair" {{ $currentCondition == 'fair' ? 'selected' : '' }}>Fair</option>
                  <option value="poor" {{ $currentCondition == 'poor' ? 'selected' : '' }}>Poor</option>
                </select>
                @error('coat_condition')
                  <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Skin Issues</label>
                <div class="mt-2 space-y-2">
                  @php
                    $currentIssues = old('skin_issues', $visit->services->first()?->pivot?->skin_issues ?? []);
                    if (!is_array($currentIssues)) {
                        $currentIssues = json_decode($currentIssues, true) ?? [];
                    }
                  @endphp
                  <div class="flex items-start">
                    <input type="checkbox" name="skin_issues[]" value="matting" class="mt-1 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                      {{ in_array('matting', $currentIssues) ? 'checked' : '' }}>
                    <label class="ml-2 block text-sm text-gray-900">Matting</label>
                  </div>
                  <div class="flex items-start">
                    <input type="checkbox" name="skin_issues[]" value="dandruff" class="mt-1 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                      {{ in_array('dandruff', $currentIssues) ? 'checked' : '' }}>
                    <label class="ml-2 block text-sm text-gray-900">Dandruff</label>
                  </div>
                  <div class="flex items-start">
                    <input type="checkbox" name="skin_issues[]" value="fleas" class="mt-1 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                      {{ in_array('fleas', $currentIssues) ? 'checked' : '' }}>
                    <label class="ml-2 block text-sm text-gray-900">Fleas</label>
                  </div>
                </div>
                @error('skin_issues')
                  <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
              </div>
            </div>

            <div class="space-y-4">
              <h3 class="text-lg font-semibold text-gray-900">Service Details</h3>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Services Performed</label>
                <div class="mt-2 space-y-2" id="servicesCheckboxGroup">
                  @php
                    // Normalize selected services to an array of IDs for repopulation
                    $selectedServices = old('services');
                    if (is_null($selectedServices)) {
                        // If visit->services relation is loaded or exists, pluck the primary key (serv_id)
                        $selectedServices = isset($visit) && method_exists($visit, 'services')
                            ? ($visit->services->pluck('serv_id')->toArray() ?? [])
                            : [];
                    }
                  @endphp
                  @foreach($services ?? [] as $service)
                    <div class="flex items-start">
                      <input type="checkbox" name="services[]" value="{{ $service->serv_id ?? $service->id }}" class="mt-1 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        {{ in_array(($service->serv_id ?? $service->id), (array) $selectedServices) ? 'checked' : '' }}>
                      <label class="ml-2 block text-sm text-gray-900">{{ $service->serv_name ?? $service->name }}</label>
                    </div>
                  @endforeach
                </div>

                {{-- Client-side inline error (hidden by default) --}}
                <p id="servicesClientError" class="mt-1 text-sm text-red-600 hidden">Please select at least one service.</p>

                {{-- Server-side validation error --}}
                @error('services')
                  <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Notes</label>
                <textarea name="notes" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $visit->services->first()?->pivot?->notes ?? '') }}</textarea>
                @error('notes')
                  <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
              </div>
            </div>
          </div>

          <div class="flex justify-end space-x-3 pt-5">
            <button type="button" onclick="window.history.back()" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
              Cancel
            </button>
            <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
              Save Record
            </button>
          </div>
        </form>
      </div>
    </div>
</div>

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
            {{-- Hidden fields for data submission (Mapped to external form) --}}
            <input type="hidden" name="signature_data" id="modal_signature_data">
            <input type="hidden" name="signer_name" value="{{ $visit->pet->owner->own_name ?? 'Guest Owner' }}">

            {{-- CSS for the document structure (Original Layout) --}}
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

<form id="agreement-form-data" action="{{ route('medical.visits.grooming.agreement.store', $visit->visit_id) }}" method="POST" style="display:none;">
    @csrf
    <input type="hidden" id="signature_data" name="signature_data">
    <input type="hidden" id="signer_name_hidden" name="signer_name">
    <input type="hidden" id="history_before_hidden" name="history_before">
    <input type="hidden" id="history_after_hidden" name="history_after">
    <input type="hidden" id="color_markings_hidden" name="color_markings">
</form>

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
            const observer = new MutationObserver((mutationsList, observer) => {
                if (!agreementModal.classList.contains('hidden')) {
                    window.requestAnimationFrame(() => {
                        setTimeout(() => {
                            initializeSignaturePad();
                        }, 50);
                    });
                }
            });
            observer.observe(agreementModal, { attributes: true, attributeFilter: ['class'] });

            window.addEventListener('resize', resizeCanvas);
            
            initializeSignaturePad();
        }

        // --- Modal Control Functions ---
        
        window.openAgreementModal = function() {
            // FIX: Ensure 'flex' class is added for visibility
            agreementModal.classList.remove('hidden');
            agreementModal.classList.add('flex');
            
            const notesTextarea = document.querySelector('textarea[name="instructions"]');
            document.getElementById('modal_history_before').value = notesTextarea ? notesTextarea.value : '';
            document.getElementById('modal_signer_name').value = '{{ $visit->pet->owner->own_name ?? 'Guest Owner' }}';
        };

        window.closeAgreementModal = function() {
            // FIX: Ensure 'flex' class is removed
            agreementModal.classList.add('hidden');
            agreementModal.classList.remove('flex');
            if (signaturePad) signaturePad.clear();
        };

        // --- Agreement Submission Logic (Functional Fix) ---

        // The hidden form outside the modal is used for the final submission payload
        const finalSubmitForm = document.getElementById('agreement-form-data'); 

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
                
                // 1. Map data from modal inputs to the hidden submission form fields
                finalSubmitForm.querySelector('#signature_data').value = signaturePad.toDataURL('image/png');
                finalSubmitForm.querySelector('#signer_name_hidden').value = document.getElementById('modal_signer_name').value;
                finalSubmitForm.querySelector('#history_before_hidden').value = document.getElementById('modal_history_before').value;
                finalSubmitForm.querySelector('#history_after_hidden').value = document.getElementById('modal_history_after').value;
                finalSubmitForm.querySelector('#color_markings_hidden').value = document.getElementById('modal_color_markings').value;

                // 2. Disable button and submit the main hidden form
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
                
                // Use the final hidden form for submission
                finalSubmitForm.submit(); 
            });
        }

    // --- Client-side validation for grooming services ---
    const groomingForm = document.getElementById('groomingServiceForm');
    if (groomingForm) {
      groomingForm.addEventListener('submit', function(e) {
        const checked = document.querySelectorAll('input[name="services[]"]:checked');
        const clientErr = document.getElementById('servicesClientError');
        if (checked.length === 0) {
          e.preventDefault();
          if (clientErr) {
            clientErr.classList.remove('hidden');
            // If server rendered an error, prefer that message; otherwise use default
            try {
              const serverMsg = `{!! $errors->first('services') ? e('\'' . addslashes($errors->first('services')) . '\'') : '' !!}`;
            } catch (ex) {
              // ignore blade interpolation issues in JS fallback
            }
            clientErr.textContent = '{{ $errors->first('services', 'Please select at least one service.') }}';
          }
          // Scroll to the services area so user sees the error
          const target = document.getElementById('servicesCheckboxGroup');
          if (target) target.scrollIntoView({ behavior: 'smooth', block: 'center' });
          return false;
        } else if (clientErr) {
          clientErr.classList.add('hidden');
        }
      });
    }
    });
</script>
@endpush
@endsection