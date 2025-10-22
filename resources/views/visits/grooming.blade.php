@extends('AdminBoard')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-50 p-6">
    <div class="max-w-6xl mx-auto">
        <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-4xl font-bold text-gray-800">Grooming</h1>
                    <p class="text-gray-600 mt-1">Track parcel-style workflow and service details</p>
                </div>
                <a href="{{ route('medical.index', ['active_tab' => 'visits']) }}" 
                   class="px-4 py-2 bg-white border-2 border-gray-300 rounded-lg hover:bg-gray-50 font-medium">
                    ← Back
                </a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 mb-6 border-l-4 border-blue-500 print-hide">
            <div class="grid grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Pet Name</label>
                    <div class="bg-blue-50 p-3 rounded-lg font-semibold text-gray-800">
                        @php($s = strtolower($visit->pet->pet_species ?? ''))
                        @if($s === 'cat')
                            <i class="fas fa-cat mr-1" title="Cat"></i>
                        @elseif($s === 'dog')
                            <i class="fas fa-dog mr-1" title="Dog"></i>
                        @endif
                        {{ $visit->pet->pet_name ?? 'N/A' }}
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Owner Name</label>
                    <div class="bg-blue-50 p-3 rounded-lg font-semibold text-gray-800">{{ $visit->pet->owner->own_name ?? 'N/A' }}</div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Visit Date</label>
                    <div class="bg-blue-50 p-3 rounded-lg font-semibold text-gray-800">{{ optional(\Carbon\Carbon::parse($visit->visit_date))->format('F j, Y') }}</div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Assigned Staff</label>
                    <div class="bg-blue-50 p-3 rounded-lg font-semibold text-gray-800">{{ auth()->user()->user_name ?? 'N/A' }}</div>
                </div>
            </div>
        </div>

        {{-- Grooming Agreement Consent --}}
        <div id="agreement-card" class="bg-white rounded-lg shadow p-6 mb-6 border-l-4 border-green-500 print:border-0 print:shadow-none print:p-0">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Grooming Agreement Consent</h3>
                @if($visit->groomingAgreement)
                    <span class="text-sm px-2 py-1 rounded bg-green-100 text-green-800">Signed {{ optional($visit->groomingAgreement->signed_at)->format('M d, Y h:i A') }}</span>
                @else
                    <span class="text-sm px-2 py-1 rounded bg-yellow-100 text-yellow-800">Pending Signature</span>
                @endif
            </div>
            @if($visit->groomingAgreement)
            <div class="grid grid-cols-4 gap-4 mb-4 text-sm">
                <div>
                    <div class="text-gray-500">Owner Name</div>
                    <div class="font-semibold">{{ $visit->pet->owner->own_name ?? 'N/A' }}</div>
                </div>
                <div>
                    <div class="text-gray-500">Owner Contact</div>
                    <div class="font-semibold">{{ $visit->pet->owner->own_contactnum ?? 'N/A' }}</div>
                </div>
                <div>
                    <div class="text-gray-500">Owner Address</div>
                    <div class="font-semibold">{{ $visit->pet->owner->own_location ?? 'N/A' }}</div>
                </div>
                <div>
                    <div class="text-gray-500">Pet Name</div>
                    <div class="font-semibold">{{ $visit->pet->pet_name ?? 'N/A' }}</div>
                </div>
                <div>
                    <div class="text-gray-500">Species / Breed</div>
                    <div class="font-semibold">
                        @php($s = strtolower($visit->pet->pet_species ?? ''))
                        @if($s === 'cat')
                            <i class="fas fa-cat mr-1" title="Cat"></i>
                        @elseif($s === 'dog')
                            <i class="fas fa-dog mr-1" title="Dog"></i>
                        @endif
                        {{ $visit->pet->pet_species ?? 'N/A' }}{{ $visit->pet->pet_breed ? ' • '.$visit->pet->pet_breed : '' }}
                    </div>
                </div>
                <div>
                    <div class="text-gray-500">Age</div>
                    <div class="font-semibold">{{ $visit->pet->pet_age ?? 'N/A' }}</div>
                </div>
                <div>
                    <div class="text-gray-500">Gender</div>
                    <div class="font-semibold">{{ $visit->pet->pet_gender ?? 'N/A' }}</div>
                </div>
                <div>
                    <div class="text-gray-500">Visit Date</div>
                    <div class="font-semibold">{{ optional(\Carbon\Carbon::parse($visit->visit_date))->format('F j, Y') }}</div>
                </div>
            </div>
            @endif

            

            @if(!$visit->groomingAgreement)
            <form id="agreement-form" action="{{ route('medical.visits.grooming.agreement.store', $visit->visit_id) }}" method="POST" class="space-y-4">
                @csrf
                <style>
                    .doc-container{max-width:900px;margin:0 auto;background:#fff;border:2px solid #333;padding:32px}
                    .doc-header{border-bottom:2px solid #333;text-align:center;padding-bottom:16px;margin-bottom:20px}
                    .doc-header h1{font-size:22px;font-weight:800;letter-spacing:1px}
                    .doc-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:10px}
                    .doc-label{font-weight:700;font-size:11px;text-transform:uppercase;margin-bottom:4px}
                    .doc-input{border:none;padding:4px 0;font-size:14px;font-family:Courier New,monospace;width:100%;background:transparent}
                    .doc-input[readonly]{color:#111}
                    .doc-3col{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
                    .doc-3col-owner{display:grid;grid-template-columns:1fr 2fr 1fr;gap:12px}
                    .doc-value{font-size:14px;font-family:Courier New,monospace;white-space:pre-wrap;word-break:break-word}
                    .doc-history{width:100%;border-collapse:collapse;border:1px solid #333}
                    .doc-history th{background:#f0f0f0;padding:10px;border:1px solid #333;text-align:left;font-size:12px}
                    .doc-history td{border:1px solid #333;padding:8px;height:120px;vertical-align:top}
                    .doc-terms{margin-top:16px;line-height:1.7}
                    .doc-term{font-size:13px;text-align:justify;margin-bottom:10px}
                    .doc-sign{display:flex;gap:24px;align-items:flex-end;margin-top:20px;border-top:2px solid #333;padding-top:16px}
                    .doc-sigbox{flex:1}
                    .doc-sigline{border-bottom:1px solid #333;height:100px;background:#fff}
                    .doc-siglabel{text-align:center;font-size:12px;font-weight:700;margin-top:4px}
                    @media print{#agreement-card{border:0;box-shadow:none;padding:0}.print-hide{display:none}}
                </style>

                <div class="doc-container">
                    <!-- Header Section with Full Width Orange Background Container -->
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
                            <div class="doc-value">{{ $visit->pet->owner->own_location ?? '' }}</div>
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

                    <div class="doc-3col-owner">
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
                            <input class="doc-input" type="text" name="color_markings" placeholder="e.g. Black with white paws">
                        </div>
                    </div>

                    <div style="text-align:center;font-weight:800;margin:16px 0;text-transform:uppercase">History</div>
                    <table class="doc-history">
                        <tr>
                            <th style="width:50%">Before Grooming</th>
                            <th style="width:50%">After Grooming</th>
                        </tr>
                        <tr>
                            <td>
                                <textarea name="history_before" rows="6" style="width:100%;border:none;outline:none;resize:vertical"></textarea>
                            </td>
                            <td>
                                <textarea name="history_after" rows="6" style="width:100%;border:none;outline:none;resize:vertical"></textarea>
                            </td>
                        </tr>
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
                                <canvas id="signature-pad" class="w-full" style="height: 140px;"></canvas>
                            </div>
                            <input type="hidden" name="signature_data" id="signature_data" />
                            <div class="doc-siglabel">Signature of Owner/Representative</div>
                        </div>
                        <div class="doc-sigbox">
                            <div class="doc-label">Signer Name</div>
                            <input class="doc-input" type="text" name="signer_name" value="{{ $visit->pet->owner->own_name ?? '' }}" placeholder="Owner / Representative">
                            <div class="doc-label" style="margin-top:16px">Date</div>
                            <input class="doc-input" type="text" readonly value="{{ now()->format('F j, Y') }}">
                        </div>
                    </div>

                    <div class="mt-4 flex items-center gap-2">
                        <input type="checkbox" name="checkbox_acknowledge" value="1" required>
                        <span class="text-sm">I have carefully read the above and agree.</span>
                    </div>
                </div>

                <div class="flex justify-between mt-4">
                    <button type="button" onclick="window.print()" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 print-hide">Print</button>
                    <button type="button" id="sig-clear" class="px-4 py-2 bg-gray-200 rounded print-hide">Clear Signature</button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 print-hide">Sign Agreement</button>
                </div>
            </form>
            @else
                <div class="grid grid-cols-3 gap-4 items-start print:gap-2">
                    <div>
                        <div class="text-sm text-gray-600">Signer</div>
                        <div class="font-semibold">{{ $visit->groomingAgreement->signer_name }}</div>
                        <div class="text-xs text-gray-500">{{ optional($visit->groomingAgreement->signed_at)->format('M d, Y h:i A') }}</div>
                    </div>
                    <div class="col-span-2">
                        <img src="{{ asset('storage/'.$visit->groomingAgreement->signature_path) }}" alt="Signature" class="h-24 border rounded bg-white"/>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-3 gap-4 text-sm">
                    <div>
                        <div class="text-gray-500">Color Markings</div>
                        <div class="font-semibold">{{ $visit->groomingAgreement->color_markings ?? '—' }}</div>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <div class="text-gray-700 font-semibold mb-1">Before Grooming</div>
                        <div class="min-h-24 border rounded p-3 whitespace-pre-wrap">{{ $visit->groomingAgreement->history_before ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-gray-700 font-semibold mb-1">After Grooming</div>
                        <div class="min-h-24 border rounded p-3 whitespace-pre-wrap">{{ $visit->groomingAgreement->history_after ?? '—' }}</div>
                    </div>
                </div>

                <div class="mt-4 flex justify-end">
                    <button type="button" onclick="window.print()" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">Print</button>
                </div>
            @endif
        </div>

        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-700">Status Timeline</h3>
                <form method="POST" action="{{ route('medical.visits.grooming.save', $visit->visit_id) }}" class="flex items-center gap-2 text-xs">
                    @csrf
                    <select name="workflow_status" class="border px-2 py-1 rounded">
                        @php($statuses = ['Waiting','In Grooming','Bathing','Drying','Finishing','Completed','Picked Up'])
                        @foreach($statuses as $s)
                            <option value="{{ $s }}" {{ ($visit->workflow_status ?? 'Waiting') === $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="px-2 py-1 bg-blue-600 text-white rounded">Update</button>
                </form>
            </div>
            <div class="mt-3 flex items-center gap-2 text-xs">
                @php($current = $visit->workflow_status ?? 'Waiting')
                @foreach(['Waiting','In Grooming','Bathing','Drying','Finishing','Completed','Picked Up'] as $i => $label)
                    <span class="px-2 py-1 rounded {{ $current === $label ? 'bg-green-600 text-white' : 'bg-gray-100' }}">{{ $label }}</span>
                    @if($label !== 'Picked Up')<span>→</span>@endif
                @endforeach
            </div>
        </div>

        <form action="{{ route('medical.visits.grooming.save', $visit->visit_id) }}" method="POST" class="space-y-6">
            @csrf

            <div class="bg-white rounded-lg shadow p-6">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Assigned Groomer</label>
                        <input type="text" name="assigned_groomer" value="{{ auth()->user()->user_name ?? '' }}" class="w-full border p-2 rounded"/>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Grooming Type</label>
                        <select name="grooming_type" class="w-full border p-2 rounded" required>
                            <option value="">Select type</option>
                            <option value="Basic grooming">Basic grooming</option>
                            <option value="Tick bath">Tick bath</option>
                            <option value="Haircut">Haircut</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Add-ons</label>
                        <input type="text" name="additional_services" placeholder="Ear cleaning, nail trim, etc." class="w-full border p-2 rounded"/>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium mb-1">Start Time</label>
                            <input type="datetime-local" name="start_time" class="w-full border p-2 rounded"/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">End Time</label>
                            <input type="datetime-local" name="end_time" class="w-full border p-2 rounded"/>
                        </div>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium mb-1">Remarks / Notes</label>
                        <textarea name="instructions" rows="3" class="w-full border p-2 rounded" placeholder="Matting found on tail, etc."></textarea>
                    </div>
                    <div class="col-span-2 grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Products Used</label>
                            <input type="text" name="products_used" class="w-full border p-2 rounded" placeholder="Optional"/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Total Service Price</label>
                            <input type="number" step="0.01" name="total_price" class="w-full border p-2 rounded"/>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" class="px-4 py-2 bg-orange-500 text-white rounded hover:bg-orange-600">Generate/Update Billing</button>
                <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded hover:bg-green-700">Save</button>
            </div>

            <input type="hidden" name="visit_id" value="{{ $visit->visit_id }}">
            <input type="hidden" name="pet_id" value="{{ $visit->pet_id }}">
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
<script>
    (function(){
        const canvas = document.getElementById('signature-pad');
        if (!canvas) return;

        function resizeCanvas() {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            const rect = canvas.getBoundingClientRect();
            canvas.width = rect.width * ratio;
            canvas.height = rect.height * ratio;
            const ctx = canvas.getContext('2d');
            ctx.scale(ratio, ratio);
        }
        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);

        const signaturePad = new SignaturePad(canvas, {backgroundColor: 'rgba(255,255,255,1)'});
        document.getElementById('sig-clear').addEventListener('click', function(){ signaturePad.clear(); });

        document.getElementById('agreement-form').addEventListener('submit', function(e){
            if (signaturePad.isEmpty()) {
                e.preventDefault();
                alert('Please provide a signature.');
                return false;
            }
            document.getElementById('signature_data').value = signaturePad.toDataURL('image/png');
        });
    })();
</script>
@endpush
