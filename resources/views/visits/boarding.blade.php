@extends('AdminBoard')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-teal-50 to-cyan-50 p-4 sm:p-6">
    <div class="max-w-7xl mx-auto space-y-6">
        <div class="flex items-center justify-between bg-white p-4 rounded-xl shadow-sm border">
            <h2 class="text-3xl font-bold text-gray-800 flex items-center"><i class="fas fa-hotel text-teal-600 mr-2"></i> Pet Boarding</h2>
            <a href="{{ route('medical.index', ['tab' => 'boarding']) }}" 
               class="px-4 py-2 bg-gray-200 border-2 border-gray-300 rounded-lg hover:bg-gray-300 font-medium shadow-sm transition">← Back</a>
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
               <div class="bg-white rounded-xl shadow-sm border p-4 cursor-pointer hover:shadow-md transition" onclick="openVisitHistoryModal()">
                <div class="font-semibold text-gray-900 mb-2">Recent Visit History</div>
                <div class="space-y-2 max-h-40 overflow-y-auto text-xs">
                    @forelse($boardingHistory as $record)
                      @php 
                        $status = strtolower($record->status ?? 'pending');
                        $border = $status === 'check out' ? 'border-green-400' : ($status === 'check in' ? 'border-blue-400' : 'border-gray-300');
                      @endphp
                      <div class="border-l-2 pl-2 {{ $border }}">
                        <div class="font-medium flex justify-between items-center">
                          {{ \Carbon\Carbon::parse($record->check_in_date)->format('M j, Y') }}
                          <span class="text-xs font-semibold px-1 py-0.5 rounded 
                            {{ $status === 'check out' ? 'bg-green-100 text-green-700' : ($status === 'check in' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700') }}">
                            {{ ucfirst($status) }}
                          </span>
                        </div>
                        <div class="text-gray-700 truncate" title="{{ $record->room_no }}">Room: {{ $record->room_no ?? '--' }}</div>
                      </div>
                    @empty
                      <p class="text-gray-500 italic">No previous boarding records found.</p>
                    @endforelse
                </div>
                <div class="mt-3 inline-flex items-center gap-2 text-indigo-600 text-sm font-medium">View Full Service History <i class="fas fa-arrow-right"></i></div>
            </div>
            </div>

            {{-- Row 3+: Main Content (full width) --}}
            <div class="space-y-6">
                @php
            $__details = json_decode($visit->details_json ?? '[]', true) ?: [];
            $__board = [];
            if (isset($serviceData) && $serviceData) {
                $__board = [
                    'checkin' => $serviceData->check_in_date ? \Carbon\Carbon::parse($serviceData->check_in_date)->format('Y-m-d\\TH:i') : null,
                    'checkout' => $serviceData->check_out_date ? \Carbon\Carbon::parse($serviceData->check_out_date)->format('Y-m-d\TH:i') : null,
                    'room' => $serviceData->room_no ?? null,
                    'care_instructions' => $serviceData->feeding_schedule ?? null,
                    'monitoring_notes' => $serviceData->daily_notes ?? null,
                    // Billing fields removed
                    'total_days' => $__details['total_days'] ?? null,
                    'weight' => $visit->weight,
                ];
            }
            // Determine the selected service ID (priority: boarding record > old input > pivot)
            $selectedServiceId = null;
            if (isset($serviceData) && isset($serviceData->serv_id)) {
              $selectedServiceId = $serviceData->serv_id;
            } elseif (old('service_id')) {
              $selectedServiceId = old('service_id');
            } else {
              $boardingService = $visit->services()->where('serv_type', 'boarding')->latest('pivot_updated_at')->first();
              $selectedServiceId = $boardingService ? $boardingService->serv_id : null;
            }
            // Get pet species for filtering boarding services
            $petSpecies = strtolower($visit->pet->pet_species ?? '');
            @endphp
            <form action="{{ route('medical.visits.boarding.save', $visit->visit_id) }}" method="POST" class="space-y-6" id="boardingForm">
                @csrf

                {{-- Boarding Details Card --}}
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center"><i class="fas fa-calendar-alt mr-2 text-teal-600"></i> Reservation & Service Details</h2>
                    
                    {{-- Service Selection & Dates --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                        <div class="sm:col-span-3">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Boarding Service Type <span class="text-red-500">*</span></label>
                            <select name="service_id" id="boarding_service_id" class="w-full border border-gray-300 p-2 rounded-lg focus:border-teal-500 focus:ring-teal-500" required>
                                <option value="">-- Select Boarding Package --</option>
                                @forelse($availableServices as $service)
                                  @php
                                    $isSelected = $selectedServiceId == $service->serv_id;
                                  @endphp
                                  <option value="{{ $service->serv_id }}" data-price="{{ $service->serv_price ?? 0 }}" {{ $isSelected ? 'selected' : '' }}>
                                    {{ $service->serv_name ?? 'N/A' }}
                                    @if(isset($service->serv_price))
                                      ({{ number_format($service->serv_price, 2) }} / day)
                                    @endif
                                  </option>
                                @empty
                                  <option value="" disabled>No Boarding Services Available</option>
                                @endforelse
                            </select>
                            @error('service_id')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Check-in Date/Time <span class="text-red-500">*</span></label>
                            <input type="datetime-local" name="checkin" id="checkin_date" class="w-full border border-gray-300 p-2 rounded-lg focus:border-teal-500 focus:ring-teal-500" required value="{{ old('checkin', $__board['checkin'] ?? ($__details['checkin'] ?? '')) }}" />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Check-out Date/Time</label>
                            <input type="datetime-local" name="checkout" id="checkout_date" class="w-full border border-gray-300 p-2 rounded-lg focus:border-teal-500 focus:ring-teal-500" value="{{ old('checkout', $__board['checkout'] ?? ($__details['checkout'] ?? '')) }}" />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Cage / Room <span class="text-red-500">*</span></label>
                            <select name="equipment_id" id="equipment_id" class="w-full border border-gray-300 p-2 rounded-lg focus:border-teal-500 focus:ring-teal-500" required>
                                <option value="">-- Select Cage/Room --</option>
                                {{-- Equipment will be loaded dynamically based on selected service --}}
                            </select>
                            <input type="hidden" name="room" id="room_hidden" value="{{ old('room', $__board['room'] ?? ($__details['room'] ?? '')) }}" />
                            <small class="text-gray-500 text-xs">Only available cages/rooms from selected service</small>
                        </div>
                        
                        {{-- Kept for Auto Calculation Basis --}}
                        <div>
                          <label class="block text-sm font-semibold text-gray-700 mb-1">Total Days / Hours</label>
                          <input type="text" id="total_time_display" readonly class="w-full border border-gray-300 p-2 rounded-lg bg-gray-100 text-gray-600" placeholder="Calculated automatically" value="{{ old('total_days', $__board['total_days'] ?? ($__details['total_days'] ?? '')) }}" />
                          <input type="hidden" name="total_days" id="total_days_hidden" value="{{ old('total_days', $__board['total_days'] ?? ($__details['total_days'] ?? '')) }}" />
                          <input type="hidden" name="status" value="Check In" />
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-1">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Feeding & Care Instructions</label>
                            <textarea name="care_instructions" rows="3" class="w-full border border-gray-300 p-3 rounded-lg focus:border-teal-500 focus:ring-teal-500" placeholder="Diet, meds times, play time requests...">{{ old('care_instructions', $__board['care_instructions'] ?? ($__details['care_instructions'] ?? '')) }}</textarea>
                        </div>
                        <div class="sm:col-span-1">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Monitoring Notes / Daily Logs</label>
                            <textarea name="monitoring_notes" rows="3" class="w-full border border-gray-300 p-3 rounded-lg focus:border-teal-500 focus:ring-teal-500" placeholder="Daily observations (appetite, mood, eliminations)...">{{ old('monitoring_notes', $__board['monitoring_notes'] ?? ($__details['monitoring_notes'] ?? '')) }}</textarea>
                        </div>
                    </div>
                    
                    {{-- Billing Information --}}
                    <h3 class="text-lg font-semibold text-gray-700 mt-6 mb-4 flex items-center border-t pt-4"><i class="fas fa-calculator mr-2 text-blue-600"></i> Billing Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-700">Daily Rate:</span>
                                <span id="dailyRateDisplay" class="font-semibold text-blue-700">₱0.00</span>
                            </div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-700">Duration:</span>
                                <span id="durationDisplay" class="font-semibold text-blue-700">0 days</span>
                            </div>
                            <div class="border-t border-blue-200 my-2"></div>
                            <div class="flex justify-between items-center">
                                <span class="text-base font-bold text-gray-800">Total Amount:</span>
                                <span id="totalAmountDisplay" class="text-xl font-bold text-green-600">₱0.00</span>
                            </div>
                        </div>
                        <div class="col-span-2">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Pet Weight (kg)</label>
                                    <input type="text" readonly class="w-full border border-gray-300 p-2 rounded-lg bg-gray-100 text-gray-600" value="{{ $visit->weight ? number_format($visit->weight, 2) . ' kg' : 'N/A' }}" />
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Check-in Date</label>
                                    <input type="text" id="checkinDisplay" readonly class="w-full border border-gray-300 p-2 rounded-lg bg-gray-100 text-gray-600" />
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Check-out Date</label>
                                    <input type="text" id="checkoutDisplay" readonly class="w-full border border-gray-300 p-2 rounded-lg bg-gray-100 text-gray-600" />
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Service Type</label>
                                    <input type="text" id="serviceTypeDisplay" readonly class="w-full border border-gray-300 p-2 rounded-lg bg-gray-100 text-gray-600" />
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Consumable Products Section --}}
                    <h3 class="text-lg font-semibold text-gray-700 mt-6 mb-4 flex items-center border-t pt-4">
                        <i class="fas fa-pills mr-2 text-purple-600"></i> Consumable Products
                        <span class="ml-2 text-sm font-normal text-gray-500">(Deducted from inventory on Check-In)</span>
                    </h3>
                    <div id="consumableProductsContainer" class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                        <div id="noProductsMessage" class="text-center text-gray-500 py-4">
                            <i class="fas fa-info-circle mr-1"></i> Select a boarding package to view linked consumable products
                        </div>
                        <div id="consumableProductsList" class="hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="bg-purple-100">
                                        <tr>
                                            <th class="p-2 text-left font-semibold text-gray-700">Product Name</th>
                                            <th class="p-2 text-center font-semibold text-gray-700">Qty/Day</th>
                                            <th class="p-2 text-center font-semibold text-gray-700">Total Days</th>
                                            <th class="p-2 text-center font-semibold text-gray-700">Total to Deduct</th>
                                            <th class="p-2 text-center font-semibold text-gray-700">Current Stock</th>
                                            <th class="p-2 text-center font-semibold text-gray-700">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="consumableProductsBody">
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3 p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                                <p class="text-sm text-yellow-800">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    <strong>Note:</strong> These products will be deducted from inventory when the pet is <strong>Checked In</strong>.
                                    Total deduction = Qty/Day × Total Days.
                                </p>
                            </div>
                        </div>
                    </div>
                    {{-- End Consumable Products Section --}}
                    {{-- End Pet Weight --}}
                </div>
                
                {{-- Action Buttons --}}
                <div class="flex justify-between items-end pt-4">
                  
                    
                    <div class="flex gap-3">
                        <a href="{{ route('medical.index', ['tab' => 'boarding']) }}" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-semibold transition">Cancel</a>
                        <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold shadow-md transition">
                            <i class="fas fa-save mr-1"></i> Save Record & Complete
                        </button>
                    </div>
                </div>

                <input type="hidden" name="visit_id" value="{{ $visit->visit_id }}">
<input type="hidden" name="pet_id" value="{{ $visit->pet_id }}">
{{-- 💥 FIX 1: ADD HIDDEN FIELD FOR DAILY RATE --}}
<input type="hidden" name="daily_rate" id="daily_rate_hidden" value="{{
  old('daily_rate',
    (isset($boardingService) && isset($boardingService->serv_price)) ? number_format($boardingService->serv_price, 2, '.', '') : ''
  )
}}" />
<input type="hidden" name="redirect_to" value="perform" />
            </form>
            </div>
        </div>
    </div>
</div>

{{-- Modals remain the same --}}

{{-- Pet Profile Modal (Photo + Pet & Owner Info Only) --}}
<div id="petProfileModal" class="fixed inset-0 bg-black/60 z-50 hidden">
  <div class="w-full h-full flex items-center justify-center p-4" onclick="if(event.target===this){closePetProfileModal()}">
    <div class="bg-white rounded-xl shadow-2xl w-[600px] max-w-[95vw] max-h-[95vh] overflow-auto" onclick="event.stopPropagation()">
      <div class="flex items-center justify-between px-4 py-3 border-b">
        <h3 class="font-bold text-lg text-gray-800">Pet Profile</h3>
        <button type="button" onclick="closePetProfileModal()" class="px-3 py-1.5 text-sm bg-red-600 text-white hover:bg-red-700 rounded-md"><i class="fas fa-times mr-1"></i>Close</button>
      </div>
      <div class="p-6 space-y-4">
        <div class="w-full rounded-lg border bg-gray-50 flex items-center justify-center overflow-hidden">
          @if(!empty($visit->pet->pet_photo))
            <img src="{{ asset('storage/'.$visit->pet->pet_photo) }}" alt="{{ $visit->pet->pet_name ?? 'Pet' }}" class="w-full h-80 object-cover"/>
          @else
            <div class="h-80 w-full flex items-center justify-center text-gray-400 text-lg">
              <i class="fas fa-paw text-6xl"></i>
            </div>
          @endif
        </div>

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

    // JAVASCRIPT FOR AUTO-CALCULATING DURATION AND BILLING
    document.addEventListener('DOMContentLoaded', function () {
      
      // ========================================
      // CONSUMABLE PRODUCTS LOADING LOGIC
      // ========================================
      let currentServiceProducts = [];
      
      function loadConsumableProducts(serviceId) {
          const noProductsMessage = document.getElementById('noProductsMessage');
          const consumableProductsList = document.getElementById('consumableProductsList');
          const consumableProductsBody = document.getElementById('consumableProductsBody');
          
          if (!serviceId) {
              noProductsMessage.classList.remove('hidden');
              consumableProductsList.classList.add('hidden');
              currentServiceProducts = [];
              return;
          }
          
          // Show loading state
          noProductsMessage.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Loading consumable products...';
          noProductsMessage.classList.remove('hidden');
          consumableProductsList.classList.add('hidden');
          
          fetch(`/services/${serviceId}/products`)
              .then(response => response.json())
              .then(data => {
                  if (data.success && data.products && data.products.length > 0) {
                      currentServiceProducts = data.products;
                      updateConsumableProductsDisplay();
                      noProductsMessage.classList.add('hidden');
                      consumableProductsList.classList.remove('hidden');
                  } else {
                      currentServiceProducts = [];
                      noProductsMessage.innerHTML = '<i class="fas fa-info-circle mr-1"></i> No consumable products linked to this boarding package';
                      noProductsMessage.classList.remove('hidden');
                      consumableProductsList.classList.add('hidden');
                  }
              })
              .catch(error => {
                  console.error('Error loading consumable products:', error);
                  currentServiceProducts = [];
                  noProductsMessage.innerHTML = '<i class="fas fa-exclamation-circle mr-1 text-red-500"></i> Error loading consumable products';
                  noProductsMessage.classList.remove('hidden');
                  consumableProductsList.classList.add('hidden');
              });
      }
      
      function updateConsumableProductsDisplay() {
          const consumableProductsBody = document.getElementById('consumableProductsBody');
          const totalDays = parseInt(document.getElementById('total_days_hidden').value) || 0;
          
          if (!currentServiceProducts || currentServiceProducts.length === 0) {
              consumableProductsBody.innerHTML = '<tr><td colspan="6" class="p-4 text-center text-gray-500">No products linked</td></tr>';
              return;
          }
          
          let html = '';
          currentServiceProducts.forEach(product => {
              const qtyPerDay = parseFloat(product.quantity_used) || 0;
              const totalToDeduct = qtyPerDay * totalDays;
              const currentStock = parseInt(product.current_stock) || 0;
              const hasEnoughStock = currentStock >= totalToDeduct;
              
              const statusClass = hasEnoughStock ? 'text-green-600 bg-green-100' : 'text-red-600 bg-red-100';
              const statusText = hasEnoughStock ? '✓ Sufficient' : '⚠ Low Stock';
              
              html += `
                  <tr class="border-b border-purple-200">
                      <td class="p-2 font-medium text-gray-800">${product.product_name}</td>
                      <td class="p-2 text-center">${qtyPerDay}</td>
                      <td class="p-2 text-center font-semibold">${totalDays}</td>
                      <td class="p-2 text-center font-bold text-purple-700">${totalToDeduct.toFixed(2)}</td>
                      <td class="p-2 text-center ${currentStock < totalToDeduct ? 'text-red-600 font-bold' : 'text-green-600'}">${currentStock}</td>
                      <td class="p-2 text-center">
                          <span class="px-2 py-1 rounded text-xs font-semibold ${statusClass}">${statusText}</span>
                      </td>
                  </tr>
              `;
          });
          
          consumableProductsBody.innerHTML = html;
      }
      // ========================================
      // END CONSUMABLE PRODUCTS LOGIC
      // ========================================
      
      // ========================================
      // EQUIPMENT (CAGE/ROOM) LOADING LOGIC
      // ========================================
      const boardingServiceSelect = document.getElementById('boarding_service_id');
      const equipmentSelect = document.getElementById('equipment_id');
      const roomHidden = document.getElementById('room_hidden');
      
      // Store equipment data from PHP - convert to proper array format
      @php
          $equipmentDataForJs = [];
          foreach ($availableServices as $service) {
              $equipment = $service->equipment ?? collect();
              $equipmentDataForJs[$service->serv_id] = $equipment->map(function($eq) {
                  return [
                      'equipment_id' => $eq->equipment_id,
                      'equipment_name' => $eq->equipment_name,
                      'equipment_status' => $eq->equipment_status ?? 'available',
                      'equipment_available' => $eq->equipment_available ?? 0
                  ];
              })->values()->toArray();
          }
      @endphp
      const serviceEquipmentData = @json($equipmentDataForJs);
      
      // Current equipment ID from existing boarding record (if any)
      const currentEquipmentId = {{ $serviceData->equipment_id ?? 'null' }};
      
      function loadEquipmentForService(serviceId) {
          equipmentSelect.innerHTML = '';
          
          if (!serviceId || !serviceEquipmentData[serviceId]) {
              equipmentSelect.innerHTML = '<option value="">-- Select a service first --</option>';
              return;
          }
          
          const equipment = serviceEquipmentData[serviceId];
          
          if (equipment.length === 0) {
              equipmentSelect.innerHTML = '<option value="">-- No cages/rooms available --</option>';
              return;
          }
          
          let firstAvailableSet = false;
          
          equipment.forEach(function(eq) {
              const availableCount = parseInt(eq.equipment_available) || 0;
              const isAvailable = availableCount > 0;
              const isCurrentlyAssigned = eq.equipment_id == currentEquipmentId;
              
              // Show available equipment OR the currently assigned one (even if in use)
              if (isAvailable || isCurrentlyAssigned) {
                  const option = document.createElement('option');
                  option.value = eq.equipment_id;
                  option.textContent = eq.equipment_name + (isCurrentlyAssigned && !isAvailable ? ' (Currently Assigned)' : ' (' + availableCount + ' Available)');
                  option.dataset.name = eq.equipment_name;
                  
                  // Pre-select current equipment if exists, otherwise select first available
                  if (isCurrentlyAssigned) {
                      option.selected = true;
                      firstAvailableSet = true;
                  } else if (!firstAvailableSet && isAvailable) {
                      option.selected = true;
                      firstAvailableSet = true;
                  }
                  
                  equipmentSelect.appendChild(option);
              }
          });
          
          // Update room hidden field with selected equipment name
          updateRoomHidden();
      }
      
      function updateRoomHidden() {
          const selected = equipmentSelect.options[equipmentSelect.selectedIndex];
          if (selected && selected.dataset.name) {
              roomHidden.value = selected.dataset.name;
          } else {
              roomHidden.value = '';
          }
      }
      
      // Load equipment when service changes
      if (boardingServiceSelect) {
          boardingServiceSelect.addEventListener('change', function() {
              loadEquipmentForService(this.value);
              loadConsumableProducts(this.value);
              calculateBilling();
          });
          
          // Initial load for selected service
          if (boardingServiceSelect.value) {
              loadEquipmentForService(boardingServiceSelect.value);
              loadConsumableProducts(boardingServiceSelect.value);
          }
      }
      
      // Update room hidden when equipment changes
      if (equipmentSelect) {
          equipmentSelect.addEventListener('change', updateRoomHidden);
      }
      // ========================================
      // END EQUIPMENT LOGIC
      // ========================================
      
      // Ensure daily_rate is set before submit (fallback for JS-disabled or race conditions)
      const form = document.getElementById('boardingForm');
      if (form) {
        form.addEventListener('submit', function(e) {
          const dailyRateHidden = document.getElementById('daily_rate_hidden');
          if (dailyRateHidden && boardingServiceSelect) {
            const selected = boardingServiceSelect.options[boardingServiceSelect.selectedIndex];
            if (selected && selected.dataset.price) {
              dailyRateHidden.value = parseFloat(selected.dataset.price).toFixed(2);
            }
          }
        });
      }
        const checkinDate = document.getElementById('checkin_date');
        const checkoutDate = document.getElementById('checkout_date');
        const totalTimeDisplay = document.getElementById('total_time_display');
        const totalDaysHidden = document.getElementById('total_days_hidden');
        
        // Billing display elements
        const dailyRateDisplay = document.getElementById('dailyRateDisplay');
        const durationDisplay = document.getElementById('durationDisplay');
        const totalAmountDisplay = document.getElementById('totalAmountDisplay');
        const checkinDisplay = document.getElementById('checkinDisplay');
        const checkoutDisplay = document.getElementById('checkoutDisplay');
        const serviceTypeDisplay = document.getElementById('serviceTypeDisplay');
        
        // Hidden input for the daily rate
        const dailyRateHidden = document.getElementById('daily_rate_hidden');


        // Format date for display
        function formatDateForDisplay(dateString) {
            if (!dateString) return 'N/A';
            try {
                const options = { 
                    year: 'numeric', 
                    month: 'short', 
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                };
                return new Date(dateString).toLocaleString('en-US', options);
            } catch (e) {
                return 'Invalid Date';
            }
        }
        
        // Calculate duration and update billing
        function calculateBilling() {
    const checkin = checkinDate.value ? new Date(checkinDate.value) : null;
    const checkout = checkoutDate.value ? new Date(checkoutDate.value) : null;
    const selectedService = boardingServiceSelect ? boardingServiceSelect.options[boardingServiceSelect.selectedIndex] : null;
    
    // Get price from the selected option's data attribute
    const servicePrice = selectedService && selectedService.dataset.price ? parseFloat(selectedService.dataset.price) : 0;
    const serviceName = selectedService ? selectedService.text.split('(')[0].trim() : '';

    // Update service type display
    serviceTypeDisplay.value = serviceName || 'Not selected';
    
    // Update check-in/out displays
    checkinDisplay.value = checkinDate.value ? formatDateForDisplay(checkinDate.value) : 'Not set';
    checkoutDisplay.value = checkoutDate.value ? formatDateForDisplay(checkoutDate.value) : 'Not set';
    
    // Set initial state for calculations
    let totalDays = 0;
    let totalAmount = 0;
    
    // Calculate duration
    if (checkin && checkout && checkout > checkin) {
        const diffMs = checkout.getTime() - checkin.getTime();
        const diffDays = diffMs / (1000 * 60 * 60 * 24);
        // CRITICAL FIX: Use Math.ceil to round up to the next full day for billing
        totalDays = Math.ceil(diffDays); 
        
        // Calculate total amount: Daily Rate * Total Days
        totalAmount = servicePrice * totalDays;
        
        // Update duration display
        const daysText = totalDays === 1 ? 'day' : 'days';
        totalTimeDisplay.value = `${totalDays} ${daysText}`;
        
    } else {
        totalTimeDisplay.value = 'N/A';
        // If dates are invalid, days are 0, amount is 0
    }

    // Update hidden inputs for form submission (CRITICAL FIX)
    totalDaysHidden.value = totalDays;
    if (dailyRateHidden) {
        dailyRateHidden.value = servicePrice.toFixed(2);
    }
    
    // Update billing display
    dailyRateDisplay.textContent = `₱${servicePrice.toFixed(2)}`;
    durationDisplay.textContent = `${totalDays} ${totalDays === 1 ? 'day' : 'days'}`;
    totalAmountDisplay.textContent = `₱${totalAmount.toFixed(2)}`;
    
    // Update consumable products display with new total days
    updateConsumableProductsDisplay();
}

        // --- Event Listeners ---
        
        // Function to handle date changes consistently
        const handleDateChange = (event) => {
            const currentTarget = event.currentTarget;
            if (currentTarget.id === 'checkout_date' && checkinDate.value && new Date(currentTarget.value) < new Date(checkinDate.value)) {
                alert('Check-out date cannot be before check-in date');
                currentTarget.value = checkinDate.value; // Reset to checkin date
            } else if (currentTarget.id === 'checkin_date' && checkoutDate.value && new Date(checkoutDate.value) < new Date(currentTarget.value)) {
                // If check-in is moved forward past checkout, adjust checkout too
                checkoutDate.value = currentTarget.value;
            }
            calculateBilling();
        };

        // 1. Service Selection Change
        if (boardingServiceSelect) {
            boardingServiceSelect.addEventListener('change', calculateBilling);
        }
        
        // 2. Date Changes (Unified Listener)
        if (checkinDate) {
            checkinDate.addEventListener('change', handleDateChange);
        }
        
        if (checkoutDate) {
            checkoutDate.addEventListener('change', handleDateChange);
        }

        // Initial calculation on page load
        calculateBilling();
    });
</script>

@endsection