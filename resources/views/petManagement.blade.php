@extends('AdminBoard')
@php
    $userRole = strtolower(auth()->user()->user_role ?? '');
    
    // Define permissions for each role
    $permissions = [
        'superadmin' => [
            'view_owners' => true,
            'add_owner' => false,
            'edit_owner' => false,
            'delete_owner' => false,
            'view_pets' => true,
            'add_pet' => false,
            'edit_pet' => false,
            'delete_pet' => false,
            'view_medical' => true,
            'add_medical' => false,
            'edit_medical' => false,
            'delete_medical' => false
        ],
        'veterinarian' => [
            'view_owners' => true,
            'add_owner' => false,
            'edit_owner' => false,
            'delete_owner' => false,
            'view_pets' => true,
            'add_pet' => true,
            'edit_pet' => true,
            'delete_pet' => false,
            'view_medical' => true,
            'add_medical' => true,
            'edit_medical' => true,
            'delete_medical' => true,
        ],
        'receptionist' => [
            'view_owners' => true,
            'add_owner' => true,
            'edit_owner' => true,
            'delete_owner' => true,
            'view_pets' => true,
            'add_pet' => true,
            'edit_pet' => true,
            'delete_pet' => true,
            'view_medical' => true,  // Can only VIEW medical
            'add_medical' => false,
            'edit_medical' => false,
            'delete_medical' => false,
        ],
    ];
    
    // Get permissions for current user
    $can = $permissions[$userRole] ?? [];
    
    // Helper function to check permission
    function hasPermission($permission, $can) {
        return $can[$permission] ?? false;
    }
@endphp

@section('content')
<div class="min-h-screen">
    <div class="max-w-7xl mx-auto bg-white p-6 rounded-lg shadow">

        {{-- Tab Navigation --}}
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8">
                <button onclick="switchTab('owners')" id="owners-tab" 
                    class="tab-button py-2 px-1 border-b-2 font-medium text-sm {{ request('tab', 'owners') == 'owners' ? 'active' : '' }}">
                <h2 class="font-bold text-xl">Pet Owners</h2>
                </button>
                <button onclick="switchTab('pets')" id="pets-tab" 
                    class="tab-button py-2 px-1 border-b-2 font-medium text-sm {{ request('tab') == 'pets' ? 'active' : '' }}">
                <h2 class="font-bold text-xl">Pets</h2>
                </button>
                <!--<button onclick="switchTab('medical')" id="medical-tab" 
              <button onclick="switchTab('medical')" id="medical-tab" 
                    class="tab-button py-2 px-1 border-b-2 font-medium text-sm {{ request('tab') == 'medical' ? 'active' : '' }}">
                <h2 class="font-bold text-xl">Medical History</h2>
                </button>
                 <button onclick="switchTab('health-card')" id="health-card-tab" 
            class="tab-button py-2 px-1 border-b-2 font-medium text-sm {{ request('tab') == 'health-card' ? 'active' : '' }}">
            <h2 class="font-bold text-xl">Pet Health Card</h2>
        </button>
                <button onclick="switchTab('visit-record')" id="visit-record-tab" 
                    class="tab-button py-2 px-1 border-b-2 font-medium text-sm {{ request('tab') == 'visit-record' ? 'active' : '' }}">
                    <h2 class="font-bold text-xl">Visit Record</h2>
                </button>-->
            </nav>
        </div>

        {{-- Success/Error Messages --}}
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-4 text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-500 text-white p-2 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        
       {{-- Pet Owners Tab Content (Now First) --}}
<div id="owners-content" class="tab-content {{ request('tab', 'owners') != 'owners' ? 'hidden' : '' }}">
    <div class="flex flex-nowrap items-center justify-between gap-3 mt-4 text-sm font-semibold text-black w-full overflow-x-auto pb-2">
        <form method="GET" action="{{ request()->url() }}" class="flex-shrink-0 flex items-center space-x-2">
            <input type="hidden" name="tab" value="owners">
            <label for="ownersPerPage" class="whitespace-nowrap text-sm text-black">Show</label>
            <select name="ownersPerPage" id="ownersPerPage" onchange="this.form.submit()" 
                class="border border-gray-400 rounded px-2 py-1.5 text-sm">
                @foreach ([10, 20, 50, 100, 'all'] as $limit)
                    <option value="{{ $limit }}" {{ request('ownersPerPage') == $limit ? 'selected' : '' }}>
                        {{ $limit === 'all' ? 'All' : $limit }}
                    </option>
                @endforeach
            </select>
            <span class="whitespace-nowrap">entries</span>
        </form>
        <div class="relative flex-1 min-w-[200px] max-w-xs">
            <input type="search" id="ownersSearch" placeholder="Search owners..." class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm pl-8">
            <i class="fas fa-search absolute left-2 top-1/2 -translate-y-1/2 text-gray-400"></i>
        </div>
        @if(hasPermission('add_owner', $can))
            <button onclick="openAddOwnerModal()" class="bg-[#0f7ea0] text-white text-sm px-4 py-2 rounded hover:bg-[#0c6a86] whitespace-nowrap">
                + Add Pet Owner
            </button>
        @endif
</div>

            <div class="overflow-x-auto mt-4">
                <table class="w-full table-auto text-sm border text-center">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-2 py-2">#</th>
                            <th class="border px-2 py-2">Pet's Owner</th>
                            <th class="border px-2 py-2">Contact Number</th>
                            <th class="border px-2 py-2">Location</th>
                            <th class="border px-2 py-2">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($owners as $index => $owner)
                            <tr>
                                <td class="border px-2 py-2">{{ $owners->firstItem() + $index }}</td>
                                <td class="border px-2 py-2">{{ $owner->own_name }}</td>
                                <td class="border px-2 py-2">{{ $owner->own_contactnum }}</td>
                                <td class="border px-2 py-2">{{ $owner->own_location }}</td>
                                <td class="border px-2 py-1">
                                <div class="flex justify-center items-center gap-1">
                                    @if(hasPermission('edit_owner', $can))
                                        <button onclick="openEditOwnerModal({{ json_encode($owner) }})" 
                                            class="bg-[#0f7ea0] text-white px-2 py-1 rounded hover:bg-[#0c6a86] flex items-center gap-1 text-xs" title="edit">
                                            <i class="fas fa-pen"></i> 
                                        </button>
                                    @endif
                                    
                                    <button onclick="viewOwnerDetails(this)" 
                                        class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 flex items-center gap-1 text-xs" title="view"
                                        data-owner-id="{{ $owner->own_id }}"
                                        data-name="{{ $owner->own_name }}" 
                                        data-contact="{{ $owner->own_contactnum }}"
                                        data-location="{{ $owner->own_location }}">
                                        <i class="fas fa-eye"></i> 
                                    </button>
                                    
                                    @if(hasPermission('add_pet', $can))
                                        <button onclick="openAddPetForOwner({{ $owner->own_id }}, '{{ $owner->own_name }}')"
                                            class="bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600 flex items-center gap-1 text-xs" title="add pet">
                                            <i class="fa-solid fa-paw"></i>
                                        </button>
                                    @endif

                                    @if(hasPermission('delete_owner', $can))
                                        <form action="{{ route('pet-management.destroyOwner', $owner->own_id) }}" method="POST"
                                            onsubmit="return confirm('Are you sure you want to delete this owner?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 flex items-center gap-1 text-xs" title="delete">
                                                <i class="fas fa-trash"></i> 
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-gray-500 py-4">No Pet Owners found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Owners Pagination --}}
            <div class="flex justify-between items-center mt-4 text-sm font-semibold text-black">
                <div>Showing {{ $owners->firstItem() }} to {{ $owners->lastItem() }} of {{ $owners->total() }} entries</div>
                <div class="inline-flex border border-gray-400 rounded overflow-hidden">
                    @if ($owners->onFirstPage())
                        <button disabled class="px-3 py-1 text-gray-400 cursor-not-allowed border-r">Previous</button>
                    @else
                        <a href="{{ $owners->appends(array_merge(request()->query(), ['tab' => 'owners']))->previousPageUrl() }}" class="px-3 py-1 text-black hover:bg-gray-200 border-r">Previous</a>
                    @endif

                    @for ($i = 1; $i <= $owners->lastPage(); $i++)
                        @if ($i == $owners->currentPage())
                            <button class="px-3 py-1 bg-[#0f7ea0] text-white border-r">{{ $i }}</button>
                        @else
                            <a href="{{ $owners->appends(array_merge(request()->query(), ['tab' => 'owners']))->url($i) }}" class="px-3 py-1 hover:bg-gray-200 border-r">{{ $i }}</a>
                        @endif
                    @endfor

                    @if ($owners->hasMorePages())
                        <a href="{{ $owners->appends(array_merge(request()->query(), ['tab' => 'owners']))->nextPageUrl() }}" class="px-3 py-1 text-black hover:bg-gray-200">Next</a>
                    @else
                        <button disabled class="px-3 py-1 text-gray-400 cursor-not-allowed">Next</button>
                    @endif
                </div>
            </div>
        </div>
        

        {{-- Pets Tab Content (Now Second) --}}
        <div id="pets-content" class="tab-content {{ request('tab') != 'pets' ? 'hidden' : '' }}">
            <div class="flex flex-nowrap items-center justify-between gap-3 mt-4 text-sm font-semibold text-black w-full overflow-x-auto pb-2">
                <form method="GET" action="{{ request()->url() }}" class="flex-shrink-0 flex items-center space-x-2">
                    <input type="hidden" name="tab" value="pets">
                    <label for="perPage" class="whitespace-nowrap text-sm text-black">Show</label>
                    <select name="perPage" id="perPage" onchange="this.form.submit()" class="border border-gray-400 rounded px-2 py-1.5 text-sm">
                        @foreach ([10, 20, 50, 100, 'all'] as $limit)
                            <option value="{{ $limit }}" {{ request('perPage') == $limit ? 'selected' : '' }}>
                                {{ $limit === 'all' ? 'All' : $limit }}
                            </option>
                        @endforeach
                    </select>
                    <span class="whitespace-nowrap">entries</span>
                </form>
                <div class="relative flex-1 min-w-[200px] max-w-xs">
                    <input type="search" id="petsSearch" placeholder="Search pets..." class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm pl-8">
                    <i class="fas fa-search absolute left-2 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>
                @if(hasPermission('add_pet', $can))
                    <button onclick="openAddPetModal()" class="bg-[#0f7ea0] text-white text-sm px-4 py-2 rounded hover:bg-[#0c6a86] whitespace-nowrap">
                        + Add Pet
                    </button>
                @endif
            </div>

            <div class="overflow-x-auto mt-4">
                <table class="w-full table-auto text-sm border text-center">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-2 py-2">#</th>
                            <th class="border px-2 py-2">Photo</th>
                            <th class="border px-2 py-2">Registration</th>
                            <th class="border px-2 py-2">Pet's Name</th>
                            <th class="border px-2 py-2">Gender</th>
                            <th class="border px-2 py-2">Type</th>
                            <th class="border px-2 py-2">Breed</th>
                            <th class="border px-2 py-2">Birth Date</th>
                            <th class="border px-2 py-2">Age</th>
                            <th class="border px-2 py-2">Owner</th>
                            <th class="border px-2 py-2">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pets as $index => $pet)
                            <tr class="hover:bg-gray-50">
                                <td class="border px-2 py-2">{{ $pets->firstItem() + $index }}</td>
                                <td class="border px-2 py-2">
                                    @if($pet->pet_photo)
                                        <img src="{{ asset('storage/' . $pet->pet_photo) }}" alt="{{ $pet->pet_name }}" 
                                             class="w-12 h-12 object-cover rounded-full mx-auto cursor-pointer"
                                             onclick="showImageModal('{{ asset('storage/' . $pet->pet_photo) }}', '{{ $pet->pet_name }}')">
                                    @else
                                        <div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center mx-auto">
                                            <i class="fas fa-paw text-gray-400"></i>
                                        </div>
                                    @endif
                                </td>
                                <td class="border px-2 py-2">{{ \Carbon\Carbon::parse($pet->pet_registration)->format('F d, Y') }}</td>
                                <td class="border px-2 py-2">{{ $pet->pet_name }}</td>
                                <td class="border px-2 py-2">{{ $pet->pet_gender }}</td>
                                <td class="border px-2 py-2">{{ $pet->pet_species }}</td>
                                <td class="border px-2 py-2">{{ $pet->pet_breed }}</td>
                                <td class="border px-2 py-2">{{ isset($pet->pet_birthdate) && $pet->pet_birthdate ? \Carbon\Carbon::parse($pet->pet_birthdate)->format('M d, Y') : 'Not Set' }}</td>
                                <td class="border px-2 py-2">{{ $pet->pet_age }}</td>
                                <td class="border px-2 py-2">{{ $pet->owner ? $pet->owner->own_name : 'N/A' }}</td>
                                <td class="border px-2 py-1">
    <div class="flex justify-center items-center gap-1">
        @if(hasPermission('edit_pet', $can))
            <button class="bg-[#0f7ea0] text-white px-2 py-1 rounded hover:bg-[#0c6a86] flex items-center gap-1 text-xs editPetBtn" title="edit"
                data-id="{{ $pet->pet_id }}"
                data-name="{{ $pet->pet_name }}"
                data-gender="{{ $pet->pet_gender }}"
                data-age="{{ $pet->pet_age }}"
                data-birthdate="{{ $pet->pet_birthdate }}"
                data-species="{{ $pet->pet_species }}"
                data-breed="{{ $pet->pet_breed }}"
                data-weight="{{ $pet->pet_weight }}"
                data-temperature="{{ $pet->pet_temperature }}"
                data-registration="{{ $pet->pet_registration }}"
              data-owner-id="{{ $pet->owner ? $pet->owner->own_id : '' }}"** data-owner-name="{{ $pet->owner ? $pet->owner->own_name : 'N/A' }}"
                data-photo="{{ $pet->pet_photo }}">
                <i class="fas fa-pen"></i>
            </button>
        @endif
        
        <button onclick="viewPetDetails(this)" 
            class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 flex items-center gap-1 text-xs" title="view"
            data-pet-id="{{ $pet->pet_id }}"
            data-name="{{ $pet->pet_name }}"
            data-gender="{{ $pet->pet_gender }}"
            data-age="{{ $pet->pet_age }}"
            data-birthdate="{{ $pet->pet_birthdate ? \Carbon\Carbon::parse($pet->pet_birthdate)->format('F d, Y') : 'N/A' }}"
            data-species="{{ $pet->pet_species }}"
            data-breed="{{ $pet->pet_breed }}"
            data-weight="{{ $pet->pet_weight }}"
            data-temperature="{{ $pet->pet_temperature }}"
            data-registration="{{ \Carbon\Carbon::parse($pet->pet_registration)->format('F d, Y') }}"
            data-photo="{{ $pet->pet_photo }}"
            data-owner-id="{{ $pet->owner ? $pet->owner->own_id : '' }}"
            data-owner="{{ $pet->owner ? $pet->owner->own_name : 'N/A' }}">
            <i class="fas fa-eye"></i>
        </button>

         <a href="{{ route('pet-management.healthCard', ['id' => $pet->pet_id]) }}" target="_blank"
                                        class="bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600 text-xs">
                                       <i class="fa-solid fa-hospital-user"></i>
                                    </a>

        @if(hasPermission('add_medical', $can))
            <button onclick="openAddMedicalForPet({{ $pet->pet_id }}, '{{ $pet->pet_name }}')"
                class="bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600 flex items-center gap-1 text-xs" title="add medical">
               <i class="fa-solid fa-notes-medical"></i>
            </button>
        @endif
        @if(hasPermission('delete_pet', $can))
            <form action="{{ route('pet-management.destroyPet', $pet->pet_id) }}" method="POST"
                onsubmit="return confirm('Are you sure you want to delete this pet?');" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 flex items-center gap-1 text-xs" title="delete">
                    <i class="fas fa-trash"></i> 
                </button>
            </form>
        @endif
    </div>
</td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-gray-500 py-4">No pets found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pets Pagination --}}
            <div class="flex justify-between items-center mt-4 text-sm font-semibold text-black">
                <div>Showing {{ $pets->firstItem() }} to {{ $pets->lastItem() }} of {{ $pets->total() }} entries</div>
                <div class="inline-flex border border-gray-400 rounded overflow-hidden">
                    @if ($pets->onFirstPage())
                        <button disabled class="px-3 py-1 text-gray-400 cursor-not-allowed border-r">Previous</button>
                    @else
                        <a href="{{ $pets->appends(array_merge(request()->query(), ['tab' => 'pets']))->previousPageUrl() }}" class="px-3 py-1 text-black hover:bg-gray-200 border-r">Previous</a>
                    @endif

                    @for ($i = 1; $i <= $pets->lastPage(); $i++)
                        @if ($i == $pets->currentPage())
                            <button class="px-3 py-1 bg-[#0f7ea0] text-white border-r">{{ $i }}</button>
                        @else
                            <a href="{{ $pets->appends(array_merge(request()->query(), ['tab' => 'pets']))->url($i) }}" class="px-3 py-1 hover:bg-gray-200 border-r">{{ $i }}</a>
                        @endif
                    @endfor

                    @if ($pets->hasMorePages())
                        <a href="{{ $pets->appends(array_merge(request()->query(), ['tab' => 'pets']))->nextPageUrl() }}" class="px-3 py-1 text-black hover:bg-gray-200">Next</a>
                    @else
                        <button disabled class="px-3 py-1 text-gray-400 cursor-not-allowed">Next</button>
                    @endif
                </div>
            </div>
        </div>

      <!--  {{-- Medical History Tab Content (Now Third) --}}
        <div id="medical-content" class="tab-content {{ request('tab') != 'medical' ? 'hidden' : '' }}">
            <div class="flex justify-between items-center mt-4 text-sm font-semibold text-black">
                <form method="GET" action="{{ request()->url() }}" class="flex items-center space-x-2">
                    <input type="hidden" name="tab" value="medical">
                    <label for="medicalPerPage" class="text-sm text-black">Show</label>
                    <select name="medicalPerPage" id="medicalPerPage" onchange="this.form.submit()"
                        class="border border-gray-400 rounded px-2 py-1 text-sm">
                        @foreach ([10, 20, 50, 100, 'all'] as $limit)
                            <option value="{{ $limit }}" {{ request('medicalPerPage') == $limit ? 'selected' : '' }}>
                                {{ $limit === 'all' ? 'All' : $limit }}
                            </option>
                        @endforeach
                    </select>
                    <span>entries</span>
                </form>
                {{--  
                 <button onclick="openAddMedicalModal()" class="bg-[#0f7ea0] text-white text-sm px-4 py-2 rounded hover:bg-[#0c6a86]">
                    + Add Medical Record
                </button>--}}
            </div>

            <div class="overflow-x-auto mt-4">
                <table class="w-full table-auto text-sm border text-center">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-2 py-2">#</th>
                            <th class="border px-2 py-2">Pet Name</th>
                            <th class="border px-2 py-2">Visit Date</th>
                            <th class="border px-2 py-2">Diagnosis</th>
                            <th class="border px-2 py-2">Treatment</th>
                            <th class="border px-2 py-2">Veterinarian</th>
                            <th class="border px-2 py-2">Follow-up Date</th>
                            <th class="border px-2 py-2">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($medicalHistories as $index => $medical)
                            <tr>
                                <td class="border px-2 py-2">{{ $medicalHistories->firstItem() + $index }}</td>
                                <td class="border px-2 py-2">{{ $medical->pet ? $medical->pet->pet_name : 'N/A' }}</td>
                                <td class="border px-2 py-2">{{ \Carbon\Carbon::parse($medical->visit_date)->format('M d, Y') }}</td>
                                <td class="border px-2 py-2">{{ Str::limit($medical->diagnosis, 30) }}</td>
                                <td class="border px-2 py-2">{{ Str::limit($medical->treatment, 30) }}</td>
                                <td class="border px-2 py-2">{{ $medical->veterinarian_name }}</td>
                                <td class="border px-2 py-2">
                                    {{ $medical->follow_up_date ? \Carbon\Carbon::parse($medical->follow_up_date)->format('M d, Y') : 'N/A' }}
                                </td>
                               <td class="border px-2 py-1">
    <div class="flex justify-center items-center gap-1">
        @if(hasPermission('edit_medical', $can))
            <button onclick="openEditMedicalModal({{ json_encode($medical) }})" 
                class="bg-[#0f7ea0] text-white px-2 py-1 rounded hover:bg-[#0c6a86] flex items-center gap-1 text-xs" title="edit">
                <i class="fas fa-pen"></i> 
            </button>
        @endif

        <button onclick="viewMedicalDetails(this)" 
            class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 flex items-center gap-1 text-xs" title="view"
            data-medical-id="{{ $medical->id }}"
            data-pet="{{ $medical->pet ? $medical->pet->pet_name : 'N/A' }}"
            data-visit="{{ \Carbon\Carbon::parse($medical->visit_date)->format('F d, Y') }}"
            data-diagnosis="{{ $medical->diagnosis }}"
            data-treatment="{{ $medical->treatment }}"
            data-medication="{{ $medical->medication }}"
            data-veterinarian="{{ $medical->veterinarian_name }}"
            data-followup="{{ $medical->follow_up_date ? \Carbon\Carbon::parse($medical->follow_up_date)->format('F d, Y') : 'N/A' }}"
            data-notes="{{ $medical->notes }}">
            <i class="fas fa-eye"></i> 
        </button>

        @if(hasPermission('delete_medical', $can))
            <form action="{{ route('pet-management.destroyMedicalHistory', $medical->id) }}" method="POST"
                onsubmit="return confirm('Are you sure you want to delete this medical record?');" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 flex items-center gap-1 text-xs" title="delete">
                    <i class="fas fa-trash"></i> 
                </button>
            </form>
        @endif
    </div>
</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-gray-500 py-4">No medical records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Medical History Pagination --}}
            <div class="flex justify-between items-center mt-4 text-sm font-semibold text-black">
                <div>Showing {{ $medicalHistories->firstItem() }} to {{ $medicalHistories->lastItem() }} of {{ $medicalHistories->total() }} entries</div>
                <div class="inline-flex border border-gray-400 rounded overflow-hidden">
                    @if ($medicalHistories->onFirstPage())
                        <button disabled class="px-3 py-1 text-gray-400 cursor-not-allowed border-r">Previous</button>
                    @else
                        <a href="{{ $medicalHistories->appends(array_merge(request()->query(), ['tab' => 'medical']))->previousPageUrl() }}" class="px-3 py-1 text-black hover:bg-gray-200 border-r">Previous</a>
                    @endif

                    @for ($i = 1; $i <= $medicalHistories->lastPage(); $i++)
                        @if ($i == $medicalHistories->currentPage())
                            <button class="px-3 py-1 bg-[#0f7ea0] text-white border-r">{{ $i }}</button>
                        @else
                            <a href="{{ $medicalHistories->appends(array_merge(request()->query(), ['tab' => 'medical']))->url($i) }}" class="px-3 py-1 hover:bg-gray-200 border-r">{{ $i }}</a>
                        @endif
                    @endfor

                    @if ($medicalHistories->hasMorePages())
                        <a href="{{ $medicalHistories->appends(array_merge(request()->query(), ['tab' => 'medical']))->nextPageUrl() }}" class="px-3 py-1 text-black hover:bg-gray-200">Next</a>
                    @else
                        <button disabled class="px-3 py-1 text-gray-400 cursor-not-allowed">Next</button>
                    @endif
                </div>
            </div>
        </div>-->

        <!--<div id="health-card-content" class="tab-content {{ request('tab') != 'health-card' ? 'hidden' : '' }}">
            
            {{-- 1. START: Add Pagination Controls and Table structure --}}
            <div class="flex justify-between items-center mt-4 text-sm font-semibold text-black">
                <form method="GET" action="{{ request()->url() }}" class="flex items-center space-x-2">
                    <input type="hidden" name="tab" value="health-card">
                    <label for="healthCardPerPage" class="text-sm text-black">Show</label>
                    <select name="healthCardPerPage" id="healthCardPerPage" onchange="this.form.submit()"
                        class="border border-gray-400 rounded px-2 py-1 text-sm">
                        @foreach ([10, 20, 50, 100, 'all'] as $limit)
                            <option value="{{ $limit }}" {{ request('healthCardPerPage', 10) == $limit ? 'selected' : '' }}>
                                {{ $limit === 'all' ? 'All' : $limit }}
                            </option>
                        @endforeach
                    </select>
                    <span>entries</span>
                </form>
            </div>
            <br>
            <div class="overflow-x-auto">
                <table class="w-full table-auto text-sm border text-center">
                    <thead class="bg-gray-100">
                        <tr>
                            {{-- ADDED: # column header --}}
                            <th class="border px-2 py-2">#</th> 
                            <th class="border px-2 py-2">Pet Name</th>
                            <th class="border px-2 py-2">Owner</th>
                            <th class="border px-2 py-2">Species</th>
                            <th class="border px-2 py-2">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- FIXED: Use standard Laravel pagination loop --}}
                        @forelse ($pets as $index => $pet)
                            <tr>
                                {{-- FIXED: Row numbering using pagination offset --}}
                                <td class="border px-2 py-2">{{ $pets->firstItem() + $index }}</td>
                                <td class="border px-2 py-2">{{ $pet->pet_name }}</td>
                                <td class="border px-2 py-2">{{ $pet->owner ? $pet->owner->own_name : 'N/A' }}</td>
                                <td class="border px-2 py-2">{{ $pet->pet_species }}</td>
                                <td class="border px-2 py-1">
                                    <a href="{{ route('pet-management.healthCard', ['id' => $pet->pet_id]) }}" target="_blank"
                                        class="bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600 text-xs">
                                       <i class="fa-solid fa-hospital-user"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-gray-500 py-4">No pets available to print health cards.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination Links for the Health Card tab --}}
            <div class="flex justify-between items-center mt-4 text-sm font-semibold text-black">
                <div>Showing {{ $pets->firstItem() }} to {{ $pets->lastItem() }} of {{ $pets->total() }} entries</div>
                <div class="inline-flex border border-gray-400 rounded overflow-hidden">
                    {{-- Previous Button --}}
                    @if ($pets->onFirstPage())
                        <button disabled class="px-3 py-1 text-gray-400 cursor-not-allowed border-r">Previous</button>
                    @else
                        <a href="{{ $pets->appends(array_merge(request()->query(), ['tab' => 'health-card']))->previousPageUrl() }}" class="px-3 py-1 text-black hover:bg-gray-200 border-r">Previous</a>
                    @endif

                    {{-- Page Numbers --}}
                    @for ($i = 1; $i <= $pets->lastPage(); $i++)
                        @if ($i == $pets->currentPage())
                            <button class="px-3 py-1 bg-[#0f7ea0] text-white border-r">{{ $i }}</button>
                        @else
                            <a href="{{ $pets->appends(array_merge(request()->query(), ['tab' => 'health-card']))->url($i) }}" class="px-3 py-1 hover:bg-gray-200 border-r">{{ $i }}</a>
                        @endif
                    @endfor

                    {{-- Next Button --}}
                    @if ($pets->hasMorePages())
                        <a href="{{ $pets->appends(array_merge(request()->query(), ['tab' => 'health-card']))->nextPageUrl() }}" class="px-3 py-1 text-black hover:bg-gray-200">Next</a>
                    @else
                        <button disabled class="px-3 py-1 text-gray-400 cursor-not-allowed">Next</button>
                    @endif
                </div>
            </div>
        </div>
        {{-- END CORRECTED TAB CONTENT --}}
    </div> -->
    
    <script>
    document.addEventListener('DOMContentLoaded', function(){
        function persistKey(tab){ return `pm_search_${tab}`; }
        function setPersist(tab, val){ try{ localStorage.setItem(persistKey(tab), val); }catch(e){} }
        function getPersist(tab){ try{ return localStorage.getItem(persistKey(tab)) || ''; }catch(e){ return ''; } }

        function filterBody(tbody, q){
            const needle = String(q || '').toLowerCase();
            tbody.querySelectorAll('tr').forEach(tr => {
                const text = tr.textContent.toLowerCase();
                tr.style.display = !needle || text.includes(needle) ? '' : 'none';
            });
        }

        function setupFilter({inputId, tableSelector, tab, perPageSelectId, formSelector}){
            const input = document.getElementById(inputId);
            const table = document.querySelector(tableSelector);
            const tbody = table ? table.querySelector('tbody') : null;
            const sel = document.getElementById(perPageSelectId);
            const form = formSelector ? document.querySelector(formSelector) : (sel ? sel.form : null);
            if(!input || !tbody) return;

            const last = getPersist(tab);
            if(last){
                input.value = last;
                if (sel && sel.value !== 'all') {
                    sel.value = 'all';
                    if (form) form.submit();
                    return;
                }
                filterBody(tbody, last);
            }

            input.addEventListener('input', function(){
                const q = this.value.trim();
                setPersist(tab, q);
                if (q && sel && sel.value !== 'all') {
                    sel.value = 'all';
                    if (form) form.submit();
                    return;
                }
                if (!tbody) return;
                filterBody(tbody, q);
            });
        }

        // Owners
        setupFilter({
            inputId: 'ownersSearch',
            tableSelector: '#owners-content table',
            tab: 'owners',
            perPageSelectId: 'ownersPerPage',
            formSelector: '#owners-content form[action]'
        });
        // Pets
        setupFilter({
            inputId: 'petsSearch',
            tableSelector: '#pets-content table',
            tab: 'pets',
            perPageSelectId: 'perPage',
            formSelector: '#pets-content form[action]'
        });
    });
    </script>
    

    

    {{-- Pet Modal --}}
    <div id="petModal" class="fixed inset-0 bg-black bg-opacity-30 flex justify-center items-center hidden z-50">
        <div class="bg-white w-full max-w-4xl p-6 rounded shadow-lg relative max-h-[90vh] overflow-y-auto">
            <h2 class="text-lg font-semibold text-[#0f7ea0] mb-4" id="petModalTitle">Add Pet</h2>
            <form id="petForm" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" id="petFormMethod" value="POST">
                <input type="hidden" name="pet_id" id="pet_id">
                <input type="hidden" name="tab" value="pets">

                {{-- Pet form fields with camera and upload --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">Pet Photo</label>
                    
                    {{-- Photo Options Buttons --}}
                    <div class="flex gap-2 mb-4">
                        <button type="button" onclick="triggerFileUpload()" 
                                class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 flex items-center gap-2">
                            <i class="fas fa-upload"></i>
                            Upload Photo
                        </button>
                        <button type="button" onclick="showCameraOption()" 
                                class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 flex items-center gap-2">
                            <i class="fas fa-camera"></i>
                            Take Photo
                        </button>
                    </div>

                    {{-- Hidden file input --}}
                    <input type="file" name="pet_photo" id="pet_photo" accept="image/*" 
                           class="hidden" onchange="previewImage(this)">

                    {{-- Photo Preview Section --}}
                    <div class="flex items-center space-x-4 mb-4">
                        <div id="imagePreview" class="hidden relative">
                            <img id="previewImg" src="" alt="Preview" 
                                 class="w-20 h-20 object-cover rounded-lg border">
                            <button type="button" onclick="removeImage()" 
                                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600">
                                ×
                            </button>
                        </div>
                        
                        <div id="currentImage" class="hidden">
                            <div class="text-center">
                                <img id="currentImg" src="" alt="Current" 
                                     class="w-20 h-20 object-cover rounded-lg border mb-2">
                                <p class="text-xs text-gray-600">Current Photo</p>
                                <button type="button" onclick="removeCurrentImage()" 
                                        class="text-red-500 text-xs hover:text-red-700">Remove</button>
                                <input type="hidden" name="remove_photo" id="remove_photo" value="0">
                            </div>
                        </div>
                    </div>

                    {{-- Camera Section --}}
                    <div id="cameraSection" class="hidden">
                        <div class="border rounded-lg p-4 bg-gray-50">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="font-medium">Camera</h4>
                                <button type="button" onclick="hideCameraOption()" 
                                        class="text-gray-500 hover:text-gray-700">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            
                            {{-- Camera Controls --}}
                            <div class="text-center mb-4">
                                <button type="button" id="startCameraBtn" onclick="startCamera()" 
                                        class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 mr-2">
                                    <i class="fas fa-video"></i> Start Camera
                                </button>
                                <button type="button" id="captureBtn" onclick="capturePhoto()" 
                                        class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 mr-2 hidden">
                                    <i class="fas fa-camera"></i> Capture Photo
                                </button>
                                <button type="button" id="stopCameraBtn" onclick="stopCamera()" 
                                        class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 hidden">
                                    <i class="fas fa-stop"></i> Stop Camera
                                </button>
                            </div>

                            {{-- Camera Video Stream --}}
                            <div class="flex justify-center mb-4">
                                <video id="cameraVideo" width="300" height="200" class="border rounded hidden"></video>
                                <canvas id="cameraCanvas" width="300" height="200" class="border rounded hidden"></canvas>
                            </div>

                            {{-- Captured Photo Preview --}}
                            <div id="capturedImagePreview" class="hidden text-center">
                                <img id="capturedImg" src="" alt="Captured" 
                                     class="w-32 h-32 object-cover rounded-lg border mx-auto mb-2">
                                <div class="flex justify-center gap-2">
                                    <button type="button" onclick="retakePhoto()" 
                                            class="px-3 py-1 bg-orange-500 text-white rounded hover:bg-orange-600 text-sm">
                                        <i class="fas fa-redo"></i> Retake
                                    </button>
                                    <button type="button" onclick="useCapturedPhoto()" 
                                            class="px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600 text-sm">
                                        <i class="fas fa-check"></i> Use Photo
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Hidden input to store captured photo data --}}
                    <input type="hidden" id="capturedPhotoData" name="captured_photo" value="">
                </div>

                <div class="mb-4">
                    <label class="block text-sm">Pet Name</label>
                    <input type="text" name="pet_name" id="pet_name" class="w-full border px-2 py-1 rounded" required>
                </div>

                <div class="flex gap-4 mb-4">
                    <div class="w-1/2">
                        <label class="block text-sm">Weight (kg)</label>
                        <input type="number" step="0.1" name="pet_weight" id="pet_weight" class="w-full border px-2 py-1 rounded">
                    </div>
                    <div class="w-1/2">
                        <label class="block text-sm">Species</label>
                        <select name="pet_species" id="pet_species" class="w-full border px-2 py-1 rounded" required onchange="handleSpeciesChange()">
                            <option value="">Select Species</option>
                            <option value="Dog">Dog</option>
                            <option value="Cat">Cat</option>
                        </select>
                    </div>
                </div>

                <div class="flex gap-4 mb-4">
                    <div class="w-1/2">
                        <label class="block text-sm">Breed <span class="text-red-500">*</span></label>
                        
                        <!-- Breed Search Input -->
                        <div class="relative">
                            <input type="text" 
                                   name="pet_breed" 
                                   id="pet_breed" 
                                   class="w-full border px-2 py-1 rounded pr-8" 
                                   placeholder="Search or select breed..." 
                                   required
                                   onkeyup="filterBreeds(this.value)"
                                   onfocus="showBreedDropdown()"
                                   autocomplete="off">
                            
                            <!-- Dropdown Toggle Button -->
                            <button type="button" 
                                    onclick="toggleBreedDropdown()" 
                                    class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <i class="fas fa-chevron-down" id="breedChevron"></i>
                            </button>
                            
                            <!-- Breed Dropdown -->
                            <div id="breedDropdown" 
                                 class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-48 overflow-y-auto hidden">
                                <div id="breedList" class="py-1">
                                    <!-- Breeds will be populated here -->
                                </div>
                                <div id="noBreeds" class="px-3 py-2 text-gray-500 text-sm text-center hidden">
                                    Please select a species first
                                </div>
                            </div>
                        </div>
                        
                        <!-- Hidden input to store selected breed for validation -->
                        <input type="hidden" id="selected_breed" name="selected_breed" value="">
                    </div>
                    <div class="w-1/2">
                        <label class="block text-sm">Birth Date</label>
                        <input type="date" name="pet_birthdate" id="pet_birthdate" class="w-full border px-2 py-1 rounded" onchange="calculateAge()" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm">Calculated Age</label>
                    <input type="text" name="pet_age" id="pet_age" class="w-full border px-2 py-1 rounded bg-gray-100" readonly placeholder="Age will be calculated automatically">
                </div>

                <div class="flex gap-4 mb-4">
                    <div class="w-1/2">
                        <label class="block text-sm">Gender</label>
                        <select name="pet_gender" id="pet_gender" class="w-full border px-2 py-1 rounded" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="w-1/2">
                        <label class="block text-sm">Temperature (°C)</label>
                        <input type="number" step="0.1" name="pet_temperature" id="pet_temperature" class="w-full border px-2 py-1 rounded">
                    </div>
                </div>

                <div class="flex gap-4 mb-4">
                    <div class="w-1/2">
    <label class="block text-sm">Registration Date</label>
    <input 
        type="date" 
        name="pet_registration" 
        id="pet_registration" 
        class="w-full border px-2 py-1 rounded" 
        value="{{ now()->format('Y-m-d') }}" {{-- 💥 AUTO-SET TO TODAY'S DATE 💥 --}}
        required>
</div>
                    <div class="w-1/2">
                    <label class="block text-sm">Owner</label>
                    <input type="text" 
                        id="owner_name_display" 
                        class="w-full border px-2 py-1 rounded bg-gray-100 cursor-not-allowed" 
                        readonly 
                        placeholder="Owner name will be pre-filled">

                    <input type="hidden" 
                        name="own_id" 
                        id="own_id">
                </div>
                </div>
               

                <div class="flex justify-end space-x-2 mt-6">
                    <button type="button" class="px-4 py-2 bg-gray-300 rounded text-sm hover:bg-gray-400" onclick="closePetModal()">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-[#0f7ea0] text-white rounded text-sm hover:bg-[#0d6b85]">Save</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Owner Modal --}}
    <div id="ownerModal" class="fixed inset-0 bg-black bg-opacity-30 flex justify-center items-center hidden z-50">
        <div class="bg-white w-full max-w-lg p-6 rounded shadow-lg relative">
            <h2 class="text-lg font-semibold text-[#0f7ea0] mb-4" id="ownerModalTitle">Add Pet Owner</h2>
            <form id="ownerForm" method="POST">
                @csrf
                <input type="hidden" name="_method" id="ownerFormMethod" value="POST">
                <input type="hidden" name="own_id" id="owner_id">
                <input type="hidden" name="tab" value="owners">

                <div class="grid grid-cols-1 gap-4 text-sm">
                    <div>
                        <label class="block font-medium">Pet Owner's Name</label>
                        <input type="text" name="own_name" id="owner_name" class="w-full border rounded px-2 py-1" required>
                    </div>
                    <div>
                        <label class="block font-medium">Contact Number</label>
                        <input type="text" name="own_contactnum" id="owner_contact" class="w-full border rounded px-2 py-1" required>
                    </div>
                    <div>
                        <label class="block font-medium">Location</label>
                        <input type="text" name="own_location" id="owner_location" class="w-full border rounded px-2 py-1" required>
                    </div>
                </div>

                <div class="flex justify-end space-x-2 mt-4">
                    <button type="button" class="px-4 py-1 bg-gray-300 rounded text-sm hover:bg-gray-400" onclick="closeOwnerModal()">Cancel</button>
                    <button type="submit" class="px-4 py-1 bg-[#0f7ea0] text-white rounded text-sm hover:bg-[#0d6b85]">Save</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Medical History Modal --}}
    <div id="medicalModal" class="fixed inset-0 bg-black bg-opacity-30 flex justify-center items-center hidden z-50">
        <div class="bg-white w-full max-w-3xl p-6 rounded shadow-lg relative max-h-[90vh] overflow-y-auto">
            <h2 class="text-lg font-semibold text-[#0f7ea0] mb-4" id="medicalModalTitle">Add Medical Record</h2>
            <form id="medicalForm" method="POST">
                @csrf
                <input type="hidden" name="_method" id="medicalFormMethod" value="POST">
                <input type="hidden" name="medical_id" id="medical_id">
                <input type="hidden" name="tab" value="medical">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <label class="block font-medium">Pet</label>
                        <select name="pet_id" id="medical_pet_id" class="w-full border rounded px-2 py-1" required>
                            <option value="">Select Pet</option>
                            @foreach ($allPets as $pet)
                                <option value="{{ $pet->pet_id }}">{{ $pet->pet_name }} ({{ $pet->owner ? $pet->owner->own_name : 'No Owner' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-medium">Visit Date</label>
                        <input type="date" name="visit_date" id="medical_visit_date" class="w-full border rounded px-2 py-1" required>
                    </div>
                    <div>
                        <label class="block font-medium">Veterinarian Name</label>
                        <input type="text" name="veterinarian_name" id="medical_veterinarian" class="w-full border rounded px-2 py-1" required>
                    </div>
                    <div>
                        <label class="block font-medium">Follow-up Date (Optional)</label>
                        <input type="date" name="follow_up_date" id="medical_followup_date" class="w-full border rounded px-2 py-1">
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block font-medium">Diagnosis</label>
                    <textarea name="diagnosis" id="medical_diagnosis" rows="3" class="w-full border rounded px-2 py-1" required></textarea>
                </div>

                <div class="mt-4">
                    <label class="block font-medium">Treatment</label>
                    <textarea name="treatment" id="medical_treatment" rows="3" class="w-full border rounded px-2 py-1" required></textarea>
                </div>

                <div class="mt-4">
                    <label class="block font-medium">Medication (Optional)</label>
                    <textarea name="medication" id="medical_medication" rows="2" class="w-full border rounded px-2 py-1"></textarea>
                </div>

                <div class="mt-4">
                    <label class="block font-medium">Notes (Optional)</label>
                    <textarea name="notes" id="medical_notes" rows="3" class="w-full border rounded px-2 py-1"></textarea>
                </div>

                <div class="flex justify-end space-x-2 mt-6">
                    <button type="button" class="px-4 py-2 bg-gray-300 rounded text-sm hover:bg-gray-400" onclick="closeMedicalModal()">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-[#0f7ea0] text-white rounded text-sm hover:bg-[#0d6b85]">Save</button>
                </div>
            </form>
        </div>
    </div>

    {{-- View Pet Modal --}}
    <div id="viewPetModal" class="fixed inset-0 bg-black bg-opacity-40 flex justify-center items-center z-50 hidden">
        <div class="bg-white w-full max-w-3xl p-6 rounded-lg shadow-lg relative">
            <div class="flex justify-between items-center border-b pb-3">
                <h2 class="text-xl font-semibold text-[#0f7ea0]">Pet Details</h2>
                <button onclick="document.getElementById('viewPetModal').classList.add('hidden')" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
            </div>
            <div class="mt-4">
                <div class="flex justify-center mb-6" id="viewPetPhotoContainer">
                    <img id="viewPetPhoto" src="" alt="Pet Photo" class="w-32 h-32 object-cover rounded-full border-4 border-gray-200 hidden">
                    <div id="viewPetNoPhoto" class="w-32 h-32 bg-gray-200 rounded-full flex items-center justify-center border-4 border-gray-300 hidden">
                        <i class="fas fa-paw text-gray-400 text-3xl"></i>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <p><strong>Pet Name:</strong> <span id="viewPetName"></span></p>
                    <p><strong>Gender:</strong> <span id="viewPetGender"></span></p>
                    <p><strong>Birth Date:</strong> <span id="viewPetBirthdate"></span></p>
                    <p><strong>Age:</strong> <span id="viewPetAge"></span></p>
                    <p><strong>Species:</strong> <span id="viewPetSpecies"></span></p>
                    <p><strong>Breed:</strong> <span id="viewPetBreed"></span></p>
                    <p><strong>Weight:</strong> <span id="viewPetWeight"></span> kg</p>
                    <p><strong>Temperature:</strong> <span id="viewPetTemperature"></span>°C</p>
                    <p><strong>Registration Date:</strong> <span id="viewPetRegistration"></span></p>
                    <p><strong>Owner:</strong> <span id="viewPetOwner"></span></p>
                </div>
            </div>
            <div class="flex justify-end mt-6">
                <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 rounded text-sm" onclick="document.getElementById('viewPetModal').classList.add('hidden')">Close</button>
            </div>
        </div>
    </div>

    
{{-- Enhanced View Owner Modal --}}
<div id="enhancedViewOwnerModal" class="fixed inset-0 bg-black bg-opacity-40 flex justify-center items-center z-50 hidden">
    <div class="bg-white w-full max-w-5xl p-6 rounded-lg shadow-lg relative max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center border-b pb-3">
            <h2 class="text-2xl font-semibold text-[#0f7ea0]">
                <i class="fas fa-user mr-2"></i>Owner Details
            </h2>
            <button onclick="closeEnhancedOwnerModal()" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
        </div>
        
        {{-- Owner Info Header --}}
        <div class="mt-6 bg-gradient-to-r from-blue-50 to-cyan-50 p-6 rounded-lg">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800" id="enhancedOwnerName"></h3>
                    <p class="text-gray-600">
                        <i class="fas fa-phone mr-2"></i><span id="enhancedOwnerContact"></span>
                    </p>
                    <p class="text-gray-600">
                        <i class="fas fa-map-marker-alt mr-2"></i><span id="enhancedOwnerLocation"></span>
                    </p>
                </div>
                <div class="md:col-span-2">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                        <div class="bg-white p-3 rounded shadow">
                            <div class="text-2xl font-bold text-blue-600" id="ownerStatsAnimals">0</div>
                            <div class="text-sm text-gray-600">Pets</div>
                        </div>
                        <div class="bg-white p-3 rounded shadow">
                            <div class="text-2xl font-bold text-green-600" id="ownerStatsAppointments">0</div>
                            <div class="text-sm text-gray-600">Visits</div>
                        </div>
                        <div class="bg-white p-3 rounded shadow">
                            <div class="text-2xl font-bold text-purple-600" id="ownerStatsMedical">0</div>
                            <div class="text-sm text-gray-600">Medical Records</div>
                        </div>
                        <div class="bg-white p-3 rounded shadow">
                            <div class="text-sm text-gray-600">Last Visit</div>
                            <div class="text-sm font-semibold text-gray-800" id="ownerStatsLastVisit">Never</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="mt-6">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                    <button onclick="switchOwnerTab('pets')" id="owner-pets-tab" 
                        class="owner-tab-button py-2 px-1 border-b-2 font-medium text-sm active">
                        <i class="fas fa-paw mr-2"></i>Pets
                    </button>
                    <button onclick="switchOwnerTab('appointments')" id="owner-appointments-tab" 
                        class="owner-tab-button py-2 px-1 border-b-2 font-medium text-sm">
                        <i class="fas fa-calendar mr-2"></i>Visits
                    </button>
                     <button onclick="switchOwnerTab('purchases')" id="owner-purchases-tab" 
                class="owner-tab-button py-2 px-1 border-b-2 font-medium text-sm">
                <i class="fas fa-shopping-cart mr-2"></i>Purchases
            </button>
                </nav>
            </div>

            {{-- Pets Tab --}}
            <div id="owner-pets-content" class="owner-tab-content mt-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="ownerPetsList">
                    {{-- Pet cards will be populated here --}}
                </div>
            </div>

            {{-- Appointments Tab --}}
            <div id="owner-appointments-content" class="owner-tab-content mt-4 hidden">
                <div class="space-y-3" id="ownerAppointmentsList">
                    {{-- Appointments will be populated here --}}
                </div>
            </div>

            <div id="owner-purchases-content" class="owner-tab-content mt-4 hidden">
        <div class="space-y-3" id="ownerPurchasesList">
            {{-- Purchases will be populated here --}}
        </div>
    </div>
        </div>
    </div>
</div>

{{-- Enhanced View Pet Modal --}}
<div id="enhancedViewPetModal" class="fixed inset-0 bg-black bg-opacity-40 flex justify-center items-center z-50 hidden">
    <div class="bg-white w-full max-w-5xl p-6 rounded-lg shadow-lg relative max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center border-b pb-3">
            <h2 class="text-2xl font-semibold text-[#0f7ea0]">
                <i class="fas fa-paw mr-2"></i>Pet Details
            </h2>
            <button onclick="closeEnhancedPetModal()" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
        </div>
        
        {{-- Pet Info Header --}}
        <div class="mt-6 bg-gradient-to-r from-green-50 to-blue-50 p-6 rounded-lg">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- Pet Photo --}}
                <div class="flex justify-center">
                    <div class="relative">
                        <img id="enhancedPetPhoto" src="" alt="Pet Photo" 
                             class="w-32 h-32 object-cover rounded-full border-4 border-white shadow-lg hidden">
                        <div id="enhancedPetNoPhoto" class="w-32 h-32 bg-gray-200 rounded-full flex items-center justify-center border-4 border-white shadow-lg hidden">
                            <i class="fas fa-paw text-gray-400 text-3xl"></i>
                        </div>
                    </div>
                </div>
                
                {{-- Pet Basic Info --}}
                <div>
                    <h3 class="text-2xl font-bold text-gray-800" id="enhancedPetName"></h3>
                    <p class="text-lg text-gray-600" id="enhancedPetBreed"></p>
                    <div class="mt-2 space-y-1">
                        <p class="text-sm text-gray-600">
                            <i class="fas fa-birthday-cake mr-2"></i><span id="enhancedPetAge"></span>
                        </p>
                        <p class="text-sm text-gray-600">
                            <i class="fas fa-venus-mars mr-2"></i><span id="enhancedPetGender"></span>
                        </p>
                        <p class="text-sm text-gray-600">
                            <i class="fas fa-user mr-2"></i><span id="enhancedPetOwner"></span>
                        </p>
                    </div>
                </div>
                
                {{-- Pet Stats --}}
                <div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-white p-3 rounded shadow">
                            <div class="text-xl font-bold text-blue-600" id="petStatsVisits">0</div>
                            <div class="text-xs text-gray-600">Total Visits</div>
                        </div>
                        <div class="bg-white p-3 rounded shadow">
                            <div class="text-xs text-gray-600">Last Visit</div>
                            <div class="text-sm font-semibold text-gray-800" id="petStatsLastVisit">Never</div>
                        </div>
                        <div class="bg-white p-3 rounded shadow">
                            <div class="text-sm font-semibold text-green-600" id="enhancedPetWeight">-- kg</div>
                            <div class="text-xs text-gray-600">Weight</div>
                        </div>
                        <div class="bg-white p-3 rounded shadow">
                            <div class="text-sm font-semibold text-red-600" id="enhancedPetTemperature">--°C</div>
                            <div class="text-xs text-gray-600">Temperature</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

      {{-- NEW: Service-Type Tabs for Visits --}}
        <div class="mt-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">
                <i class="fas fa-history mr-2"></i>Visit History
            </h3>
            
            <div id="petVisitsTabsContainer">
                {{-- The content will be dynamically generated by JS --}}
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-2 overflow-x-auto" id="petVisitsTabBar">
                        {{-- Tab Buttons populated by JS --}}
                    </nav>
                </div>
                <div id="petVisitsContent" class="mt-4 space-y-4 max-h-96 overflow-y-auto pr-2">
                    {{-- Tab Contents populated by JS --}}
                </div>
            </div>

            {{-- REMOVED: The old medical history list/section is now replaced by the tabs above --}}
            {{-- <div class="space-y-3" id="petMedicalHistoryList"></div> --}}
        </div>
    </div>
</div>
        </div>
    </div>
</div>

{{-- Enhanced View Medical Modal --}}
<div id="enhancedViewMedicalModal" class="fixed inset-0 bg-black bg-opacity-40 flex justify-center items-center z-50 hidden">
    <div class="bg-white w-full max-w-6xl p-6 rounded-lg shadow-lg relative max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center border-b pb-3">
            <h2 class="text-2xl font-semibold text-[#0f7ea0]">
                <i class="fas fa-notes-medical mr-2"></i>Medical Record Details
            </h2>
            <button onclick="closeEnhancedMedicalModal()" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
            {{-- Left Column: Pet & Owner Info --}}
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="font-semibold text-gray-800 mb-3">
                    <i class="fas fa-paw mr-2"></i>Pet Information
                </h3>
                
                <div class="flex justify-center mb-4">
                    <img id="medicalPetPhoto" src="" alt="Pet Photo" 
                         class="w-20 h-20 object-cover rounded-full border-2 border-gray-300 hidden">
                    <div id="medicalPetNoPhoto" class="w-20 h-20 bg-gray-200 rounded-full flex items-center justify-center border-2 border-gray-300 hidden">
                        <i class="fas fa-paw text-gray-400 text-xl"></i>
                    </div>
                </div>
                
                <div class="space-y-2 text-sm">
                    <p><strong>Name:</strong> <span id="medicalPetName"></span></p>
                    <p><strong>Species:</strong> <span id="medicalPetSpecies"></span></p>
                    <p><strong>Breed:</strong> <span id="medicalPetBreed"></span></p>
                    <p><strong>Age:</strong> <span id="medicalPetAge"></span></p>
                    <p><strong>Gender:</strong> <span id="medicalPetGender"></span></p>
                </div>
                
                <div class="mt-4 pt-4 border-t">
                    <h4 class="font-semibold text-gray-800 mb-2">
                        <i class="fas fa-user mr-2"></i>Owner
                    </h4>
                    <div class="space-y-1 text-sm">
                        <p><strong>Name:</strong> <span id="medicalOwnerName"></span></p>
                        <p><strong>Contact:</strong> <span id="medicalOwnerContact"></span></p>
                        <p><strong>Location:</strong> <span id="medicalOwnerLocation"></span></p>
                    </div>
                </div>
            </div>
            
            {{-- Middle Column: Current Record Details --}}
            <div class="bg-blue-50 p-4 rounded-lg">
                <h3 class="font-semibold text-gray-800 mb-3">
                    <i class="fas fa-clipboard-list mr-2"></i>Current Record
                </h3>
                
                <div class="space-y-4">
                    <div class="bg-white p-3 rounded shadow-sm">
                        <div class="text-sm text-gray-600">Visit Date</div>
                        <div class="font-semibold" id="medicalVisitDate"></div>
                    </div>
                    
                    <div class="bg-white p-3 rounded shadow-sm">
                        <div class="text-sm text-gray-600">Veterinarian</div>
                        <div class="font-semibold" id="medicalVeterinarian"></div>
                    </div>
                    
                    <div class="bg-white p-3 rounded shadow-sm">
                        <div class="text-sm text-gray-600">Follow-up Date</div>
                        <div class="font-semibold" id="medicalFollowUpDate"></div>
                    </div>
                    
                    <div class="bg-white p-3 rounded shadow-sm">
                        <div class="text-sm text-gray-600 mb-2">Diagnosis</div>
                        <div class="text-sm bg-gray-50 p-2 rounded" id="medicalDiagnosis"></div>
                    </div>
                    
                    <div class="bg-white p-3 rounded shadow-sm">
                        <div class="text-sm text-gray-600 mb-2">Treatment</div>
                        <div class="text-sm bg-gray-50 p-2 rounded" id="medicalTreatment"></div>
                    </div>
                    
                    <div class="bg-white p-3 rounded shadow-sm">
                        <div class="text-sm text-gray-600 mb-2">Medication</div>
                        <div class="text-sm bg-gray-50 p-2 rounded" id="medicalMedication"></div>
                    </div>
                    
                    <div class="bg-white p-3 rounded shadow-sm">
                        <div class="text-sm text-gray-600 mb-2">Notes</div>
                        <div class="text-sm bg-gray-50 p-2 rounded" id="medicalNotes"></div>
                    </div>
                </div>
            </div>
            
            {{-- Right Column: Medical Timeline --}}
            <div class="bg-green-50 p-4 rounded-lg">
                <h3 class="font-semibold text-gray-800 mb-3">
                    <i class="fas fa-history mr-2"></i>Medical Timeline
                </h3>
                <div class="space-y-3 max-h-96 overflow-y-auto" id="medicalTimeline">
                    {{-- Timeline will be populated here --}}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add this CSS to your existing style section -->
<style>
/* Add these styles to your existing style section */
.owner-tab-button {
    border-bottom-color: transparent;
    color: #6B7280;
}

.owner-tab-button.active {
    border-bottom-color: #0f7ea0;
    color: #0f7ea0;
}

.owner-tab-content {
    display: block;
}

.owner-tab-content.hidden {
    display: none;
}
</style>

    {{-- View Owner Modal --}}
    <div id="viewOwnerModal" class="fixed inset-0 bg-black bg-opacity-40 flex justify-center items-center z-50 hidden">
        <div class="bg-white w-full max-w-lg p-6 rounded-lg shadow-lg relative">
            <div class="flex justify-between items-center border-b pb-3">
                <h2 class="text-xl font-semibold text-[#0f7ea0]">Owner Details</h2>
                <button onclick="document.getElementById('viewOwnerModal').classList.add('hidden')" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
            </div>
            <div class="mt-4 space-y-3 text-sm">
                <p><strong>Owner Name:</strong> <span id="viewOwnerName"></span></p>
                <p><strong>Contact Number:</strong> <span id="viewOwnerContact"></span></p>
                <p><strong>Location:</strong> <span id="viewOwnerLocation"></span></p>
            </div>
            <div class="flex justify-end mt-6">
                <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 rounded text-sm" onclick="document.getElementById('viewOwnerModal').classList.add('hidden')">Close</button>
            </div>
        </div>
    </div>

    {{-- View Medical Modal --}}
    <div id="viewMedicalModal" class="fixed inset-0 bg-black bg-opacity-40 flex justify-center items-center z-50 hidden">
        <div class="bg-white w-full max-w-3xl p-6 rounded-lg shadow-lg relative">
            <div class="flex justify-between items-center border-b pb-3">
                <h2 class="text-xl font-semibold text-[#0f7ea0]">Medical Record Details</h2>
                <button onclick="document.getElementById('viewMedicalModal').classList.add('hidden')" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
            </div>
            <div class="mt-4 space-y-4 text-sm">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <p><strong>Pet:</strong> <span id="viewMedicalPet"></span></p>
                    <p><strong>Visit Date:</strong> <span id="viewMedicalVisit"></span></p>
                    <p><strong>Veterinarian:</strong> <span id="viewMedicalVeterinarian"></span></p>
                    <p><strong>Follow-up Date:</strong> <span id="viewMedicalFollowup"></span></p>
                </div>
                <div>
                    <p><strong>Diagnosis:</strong></p>
                    <p class="bg-gray-100 p-2 rounded mt-1" id="viewMedicalDiagnosis"></p>
                </div>
                <div>
                    <p><strong>Treatment:</strong></p>
                    <p class="bg-gray-100 p-2 rounded mt-1" id="viewMedicalTreatment"></p>
                </div>
                <div>
                    <p><strong>Medication:</strong></p>
                    <p class="bg-gray-100 p-2 rounded mt-1" id="viewMedicalMedication"></p>
                </div>
                <div>
                    <p><strong>Notes:</strong></p>
                    <p class="bg-gray-100 p-2 rounded mt-1" id="viewMedicalNotes"></p>
                </div>
            </div>
            <div class="flex justify-end mt-6">
                <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 rounded text-sm" onclick="document.getElementById('viewMedicalModal').classList.add('hidden')">Close</button>
            </div>
        </div>
    </div>

    {{-- Image Preview Modal --}}
    <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 flex justify-center items-center z-[60] hidden">
        <div class="relative max-w-4xl max-h-[90vh] p-4">
            <button onclick="document.getElementById('imageModal').classList.add('hidden')" 
                    class="absolute top-2 right-2 text-white bg-black bg-opacity-50 rounded-full w-8 h-8 flex items-center justify-center hover:bg-opacity-75">
                &times;
            </button>
            <img id="modalImage" src="" alt="" class="max-w-full max-h-full object-contain rounded">
            <p id="modalImageCaption" class="text-white text-center mt-2"></p>
        </div>
    </div>
</div>

<style>
.tab-button {
    border-bottom-color: transparent;
    color: #6B7280;
}

.tab-button.active {
    border-bottom-color: #0f7ea0;
    color: #0f7ea0;
}

.tab-content {
    display: block;
}

.tab-content.hidden {
    display: none;
}

.rotate-180 {
    transform: rotate(180deg);
}

#breedDropdown {
    z-index: 1000;
}

#breedDropdown button:hover {
    background-color: #eff6ff;
    color: #1d4ed8;
}

.relative {
    position: relative;
}
</style>

<script>
// Camera variables
let cameraStream = null;
let cameraVideo = null;
let cameraCanvas = null;

// Initialize tab state on page load
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const activeTab = urlParams.get('tab') || 'owners';
    switchTab(activeTab);
});

// Tab switching functionality
function switchTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Remove active class from all tab buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active');
    });
    
    // Show selected tab content
    document.getElementById(tabName + '-content').classList.remove('hidden');
    
    // Add active class to selected tab button
    document.getElementById(tabName + '-tab').classList.add('active');
    
    // Update URL parameter without page reload
    const url = new URL(window.location);
    url.searchParams.set('tab', tabName);
    window.history.replaceState({}, '', url);
}

// Pet functionality
function openAddPetModal() {
    const form = document.getElementById('petForm');
    form.reset();
    form.action = `{{ route('pet-management.storePet') }}`;
    document.getElementById('petFormMethod').value = 'POST';
    document.getElementById('petModalTitle').textContent = 'Add Pet';
    resetPhotoSections();
    
    // Reset breed selection
    currentBreeds = [];
    document.getElementById('selected_breed').value = '';
    hideBreedDropdown();
    
    document.getElementById('petModal').classList.remove('hidden');
}

// Edit pet functionality - using event delegation
document.addEventListener('click', function(e) {
    const editBtn = e.target.closest('.editPetBtn');
    if (!editBtn) return;
    
    const button = editBtn;
    
    document.getElementById('petForm').reset();
    resetPhotoSections();

    document.getElementById('pet_id').value = button.dataset.id;
    document.getElementById('pet_name').value = button.dataset.name;
    document.getElementById('pet_gender').value = button.dataset.gender;
    document.getElementById('pet_birthdate').value = button.dataset.birthdate;
    document.getElementById('pet_age').value = button.dataset.age;
    document.getElementById('pet_species').value = button.dataset.species;
    document.getElementById('pet_weight').value = button.dataset.weight;
    document.getElementById('pet_temperature').value = button.dataset.temperature;
    document.getElementById('pet_registration').value = button.dataset.registration;
    document.getElementById('own_id').value = button.dataset.ownerId;
    document.getElementById('owner_name_display').value = button.dataset.ownerName; 

    // Handle breed selection for edit
    const species = button.dataset.species;
    const breed = button.dataset.breed;
    
    if (species && breedData[species]) {
        currentBreeds = breedData[species];
        document.getElementById('pet_breed').value = breed;
        document.getElementById('selected_breed').value = breed;
        document.getElementById('pet_breed').placeholder = 'Search ' + species.toLowerCase() + ' breeds...';
    }

    const photo = button.dataset.photo;
    if (photo) {
        document.getElementById('currentImg').src = '/storage/' + photo;
        document.getElementById('currentImage').classList.remove('hidden');
    }

    const petId = button.dataset.id;
    document.getElementById('petForm').action = '/pet-management/pets/' + petId;
    document.getElementById('petFormMethod').value = 'PUT';
    document.getElementById('petModalTitle').textContent = 'Edit Pet';

    document.getElementById('petModal').classList.remove('hidden');
});

// NEW FUNCTION: Add pet for specific owner
function openAddPetForOwner(ownerId, ownerName) {
    const form = document.getElementById('petForm');
    form.reset();
    form.action = `{{ route('pet-management.storePet') }}`;
    document.getElementById('petFormMethod').value = 'POST';
    document.getElementById('petModalTitle').textContent = `Add Pet for ${ownerName}`;
    resetPhotoSections();
    
    // Reset breed selection
    currentBreeds = [];
    document.getElementById('selected_breed').value = '';
    hideBreedDropdown();
    
    // *** NEW LOGIC: Set read-only fields ***
    document.getElementById('own_id').value = ownerId; // Hidden ID for submission
    document.getElementById('owner_name_display').value = ownerName; // Displayed Name
    // ***************************************
    
    document.getElementById('petModal').classList.remove('hidden');
}

function closePetModal() {
    document.getElementById('petModal').classList.add('hidden');
    stopCamera(); // Stop camera if it's running
}

// Photo section management
function resetPhotoSections() {
    document.getElementById('imagePreview').classList.add('hidden');
    document.getElementById('currentImage').classList.add('hidden');
    document.getElementById('cameraSection').classList.add('hidden');
    document.getElementById('capturedImagePreview').classList.add('hidden');
    document.getElementById('remove_photo').value = '0';
    document.getElementById('capturedPhotoData').value = '';
    stopCamera();
}

function triggerFileUpload() {
    document.getElementById('pet_photo').click();
}

function showCameraOption() {
    document.getElementById('cameraSection').classList.remove('hidden');
    // Clear any uploaded photo
    document.getElementById('pet_photo').value = '';
    document.getElementById('imagePreview').classList.add('hidden');
    document.getElementById('capturedPhotoData').value = '';
}

function hideCameraOption() {
    document.getElementById('cameraSection').classList.add('hidden');
    stopCamera();
}

// Camera functionality
async function startCamera() {
    try {
        cameraVideo = document.getElementById('cameraVideo');
        cameraCanvas = document.getElementById('cameraCanvas');
        
        // Request camera access
        cameraStream = await navigator.mediaDevices.getUserMedia({ 
            video: { 
                width: 300, 
                height: 200,
                facingMode: 'environment' // Use back camera by default, falls back to front
            } 
        });
        
        cameraVideo.srcObject = cameraStream;
        cameraVideo.play();
        
        // Show/hide appropriate elements
        cameraVideo.classList.remove('hidden');
        document.getElementById('startCameraBtn').classList.add('hidden');
        document.getElementById('captureBtn').classList.remove('hidden');
        document.getElementById('stopCameraBtn').classList.remove('hidden');
        
    } catch (error) {
        console.error('Error accessing camera:', error);
        alert('Unable to access camera. Please ensure you have granted camera permissions and try again.');
    }
}

function stopCamera() {
    if (cameraStream) {
        cameraStream.getTracks().forEach(track => {
            track.stop();
        });
        cameraStream = null;
    }
    
    if (cameraVideo) {
        cameraVideo.srcObject = null;
        cameraVideo.classList.add('hidden');
    }
    
    // Reset camera buttons
    document.getElementById('startCameraBtn').classList.remove('hidden');
    document.getElementById('captureBtn').classList.add('hidden');
    document.getElementById('stopCameraBtn').classList.add('hidden');
    document.getElementById('capturedImagePreview').classList.add('hidden');
}

function capturePhoto() {
    if (!cameraVideo || !cameraCanvas) return;
    
    const canvas = cameraCanvas;
    const video = cameraVideo;
    const context = canvas.getContext('2d');
    
    // Set canvas size to match video
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    
    // Draw the video frame to canvas
    context.drawImage(video, 0, 0, canvas.width, canvas.height);
    
    // Convert canvas to data URL
    const photoData = canvas.toDataURL('image/jpeg', 0.8);
    
    // Show captured image preview
    document.getElementById('capturedImg').src = photoData;
    document.getElementById('capturedImagePreview').classList.remove('hidden');
    
    // Hide video and show canvas
    cameraVideo.classList.add('hidden');
    cameraCanvas.classList.remove('hidden');
}

function retakePhoto() {
    // Hide captured preview and show video again
    document.getElementById('capturedImagePreview').classList.add('hidden');
    cameraVideo.classList.remove('hidden');
    cameraCanvas.classList.add('hidden');
    document.getElementById('capturedPhotoData').value = '';
}

function useCapturedPhoto() {
    // Store the captured photo data
    const photoData = document.getElementById('capturedImg').src;
    document.getElementById('capturedPhotoData').value = photoData;
    
    // Hide camera section and show the captured photo in preview
    document.getElementById('cameraSection').classList.add('hidden');
    
    // Show captured image in the main preview area
    document.getElementById('previewImg').src = photoData;
    document.getElementById('imagePreview').classList.remove('hidden');
    document.getElementById('imagePreview').classList.add('relative');
    
    // Clear file input since we're using captured photo
    document.getElementById('pet_photo').value = '';
    
    stopCamera();
}

// Age calculation function
function calculateAge() {
    const birthdateInput = document.getElementById('pet_birthdate');
    const ageInput = document.getElementById('pet_age');
    
    if (!birthdateInput.value) {
        ageInput.value = '';
        return;
    }
    
    const birthDate = new Date(birthdateInput.value);
    const today = new Date();
    
    // Check if birth date is in the future
    if (birthDate > today) {
        ageInput.value = 'Invalid birth date';
        return;
    }
    
    // Calculate age
    let years = today.getFullYear() - birthDate.getFullYear();
    let months = today.getMonth() - birthDate.getMonth();
    
    // Adjust for negative months
    if (months < 0) {
        years--;
        months += 12;
    }
    
    // Adjust if the day hasn't occurred yet this month
    if (today.getDate() < birthDate.getDate()) {
        months--;
        if (months < 0) {
            years--;
            months += 12;
        }
    }
    
    // Format the age string
    let ageString = '';
    if (years > 0) {
        ageString += years + (years === 1 ? ' year' : ' years');
    }
    if (months > 0) {
        if (ageString) ageString += ' ';
        ageString += months + (months === 1 ? ' month' : ' months');
    }
    if (!ageString) {
        ageString = 'Less than 1 month';
    }
    
    ageInput.value = ageString;
}

// Breed data
const breedData = {
    Dog: [
        'Labrador Retriever', 'Golden Retriever', 'German Shepherd', 'Bulldog', 'Poodle',
        'Beagle', 'Rottweiler', 'Yorkshire Terrier', 'Dachshund', 'Siberian Husky',
        'Boxer', 'Border Collie', 'Boston Terrier', 'Shih Tzu', 'Chihuahua',
        'Great Dane', 'Doberman Pinscher', 'Australian Shepherd', 'Cocker Spaniel',
        'Pomeranian', 'Jack Russell Terrier', 'Maltese', 'Basset Hound'
    ],
    Cat: [
        'Persian', 'Maine Coon', 'Siamese', 'Ragdoll', 'British Shorthair',
        'Abyssinian', 'Russian Blue', 'Bengal', 'Scottish Fold', 'Sphynx',
        'American Shorthair', 'Birman', 'Oriental Shorthair', 'Devon Rex',
        'Norwegian Forest Cat', 'Munchkin', 'Exotic Shorthair', 'Turkish Angora'
    ]
};

let isBreedDropdownOpen = false;
let currentBreeds = [];

// Handle species change
function handleSpeciesChange() {
    const species = document.getElementById('pet_species').value;
    const breedInput = document.getElementById('pet_breed');
    const selectedBreed = document.getElementById('selected_breed');
    
    // Clear current breed selection
    breedInput.value = '';
    selectedBreed.value = '';
    
    // Update available breeds
    if (species && breedData[species]) {
        currentBreeds = breedData[species];
        breedInput.placeholder = `Search ${species.toLowerCase()} breeds...`;
        populateBreedDropdown(currentBreeds);
    } else {
        currentBreeds = [];
        breedInput.placeholder = 'Select species first...';
        showNoBreeds();
    }
    
    hideBreedDropdown();
}

// Filter breeds based on search input
function filterBreeds(searchTerm) {
    if (!currentBreeds.length) {
        showNoBreeds();
        return;
    }
    
    const filteredBreeds = currentBreeds.filter(breed => 
        breed.toLowerCase().includes(searchTerm.toLowerCase())
    );
    
    populateBreedDropdown(filteredBreeds);
    
    if (searchTerm && filteredBreeds.length > 0) {
        showBreedDropdown();
    }
}

// Populate breed dropdown with breeds
function populateBreedDropdown(breeds) {
    const breedList = document.getElementById('breedList');
    const noBreeds = document.getElementById('noBreeds');
    
    breedList.innerHTML = '';
    noBreeds.classList.add('hidden');
    
    if (breeds.length === 0) {
        const noResults = document.createElement('div');
        noResults.className = 'px-3 py-2 text-gray-500 text-sm text-center';
        noResults.textContent = 'No breeds found';
        breedList.appendChild(noResults);
        return;
    }
    
    breeds.forEach(breed => {
        const breedOption = document.createElement('button');
        breedOption.type = 'button';
        breedOption.className = 'w-full px-3 py-2 text-left hover:bg-blue-50 hover:text-blue-700 transition-colors border-b border-gray-100 last:border-b-0 text-sm';
        breedOption.textContent = breed;
        breedOption.onclick = () => selectBreed(breed);
        breedList.appendChild(breedOption);
    });
}

// Show no breeds message
function showNoBreeds() {
    const breedList = document.getElementById('breedList');
    const noBreeds = document.getElementById('noBreeds');
    
    breedList.innerHTML = '';
    noBreeds.classList.remove('hidden');
}

// Select a breed
function selectBreed(breed) {
    document.getElementById('pet_breed').value = breed;
    document.getElementById('selected_breed').value = breed;
    hideBreedDropdown();
}

// Show breed dropdown
function showBreedDropdown() {
    if (currentBreeds.length > 0) {
        document.getElementById('breedDropdown').classList.remove('hidden');
        document.getElementById('breedChevron').classList.add('rotate-180');
        isBreedDropdownOpen = true;
    }
}

// Hide breed dropdown
function hideBreedDropdown() {
    document.getElementById('breedDropdown').classList.add('hidden');
    document.getElementById('breedChevron').classList.remove('rotate-180');
    isBreedDropdownOpen = false;
}

// Toggle breed dropdown
function toggleBreedDropdown() {
    if (isBreedDropdownOpen) {
        hideBreedDropdown();
    } else {
        if (currentBreeds.length > 0) {
            populateBreedDropdown(currentBreeds);
            showBreedDropdown();
        }
    }
}

// File upload functionality
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewImg').src = e.target.result;
            document.getElementById('imagePreview').classList.remove('hidden');
            document.getElementById('imagePreview').classList.add('relative');
            document.getElementById('currentImage').classList.add('hidden');
            // Clear captured photo data when uploading file
            document.getElementById('capturedPhotoData').value = '';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function removeImage() {
    document.getElementById('pet_photo').value = '';
    document.getElementById('imagePreview').classList.add('hidden');
    document.getElementById('previewImg').src = '';
    document.getElementById('capturedPhotoData').value = '';
    const currentImg = document.getElementById('currentImg');
    if (currentImg.src) {
        document.getElementById('currentImage').classList.remove('hidden');
    }
}

function removeCurrentImage() {
    document.getElementById('remove_photo').value = '1';
    document.getElementById('currentImage').classList.add('hidden');
}

function showImageModal(src, caption) {
    document.getElementById('modalImage').src = src;
    document.getElementById('modalImageCaption').textContent = caption;
    document.getElementById('imageModal').classList.remove('hidden');
}

function viewPetDetails(button) {
    const petId = button.dataset.petId;
    
    if (!petId) {
        alert('Pet ID not found');
        return;
    }
    
    // Try to use enhanced modal with AJAX
    fetch('/pet-management/pet/' + petId + '/details')
        .then(response => {
            if (!response.ok) throw new Error('Failed to load');
            return response.json();
        })
        .then(data => {
            // Use enhanced modal
            showEnhancedPetView(data);
        })
        .catch(error => {
            // Fallback to simple modal if AJAX fails
            console.log('Using simple view:', error);
            showSimplePetView(button);
        });
}

function showEnhancedPetView(data) {
    const pet = data.pet;
    const modal = document.getElementById('enhancedViewPetModal');
    
    // Helper to safely format numbers for display
    const formatNumber = (num) => num ? (parseFloat(num) ? parseFloat(num).toFixed(2) : num) : '--';
    
    // 1. Update Pet Header Info
    document.getElementById('enhancedPetName').textContent = pet.pet_name;
    document.getElementById('enhancedPetBreed').textContent = pet.pet_species + ' - ' + pet.pet_breed;
    document.getElementById('enhancedPetAge').textContent = pet.pet_age;
    document.getElementById('enhancedPetGender').textContent = pet.pet_gender;
    document.getElementById('enhancedPetOwner').textContent = pet.owner ? pet.owner.own_name : 'N/A';
    
    // Update stats
    const weight = formatNumber(pet.pet_weight);
    const temperature = formatNumber(pet.pet_temperature);
    document.getElementById('enhancedPetWeight').textContent = weight + ' kg';
    document.getElementById('enhancedPetTemperature').textContent = temperature + '°C';
    document.getElementById('petStatsVisits').textContent = data.stats.visits;
    document.getElementById('petStatsLastVisit').textContent = data.stats.lastVisit;
    
    // Update photo
    const photoImg = document.getElementById('enhancedPetPhoto');
    const noPhotoDiv = document.getElementById('enhancedPetNoPhoto');
    if (pet.pet_photo) {
        photoImg.src = '/storage/' + pet.pet_photo;
        photoImg.classList.remove('hidden');
        noPhotoDiv.classList.add('hidden');
    } else {
        photoImg.classList.add('hidden');
        noPhotoDiv.classList.remove('hidden');
    }

    // 2. Build Service-Type Tabs (NEW LOGIC)
    
    const visits = data.visits || [];
    const tabBar = document.getElementById('petVisitsTabBar');
    const contentContainer = document.getElementById('petVisitsContent');

    // Clear existing content
    tabBar.innerHTML = '';
    contentContainer.innerHTML = '';
    
    // Define the tab mapping (key is data property, value is display name)
    const serviceTabs = {
        'all': 'All Records',
        'checkup': 'Checkup',
        'vaccination': 'Vaccination',
        'deworming': 'Deworming',
        'grooming': 'Grooming',
        'boarding': 'Boarding',
        'diagnostic': 'Diagnostic',
        'surgical': 'Surgical',
        'emergency': 'Emergency',
    };

    // Helper to render the detailed visit card
    function renderDetailedVisitCard(v) {
        // Main Info
        const svc = (v.services && v.services.length) ? v.services.map(s => s.serv_name).join(', ') : (v.visit_service_type || 'General');
        const weight = formatNumber(v.weight) + ' kg';
        const temp = formatNumber(v.temperature) + '°C';
        const vetName = v.veterinarian.name;
        const vetLicense = v.veterinarian.license;

        let detailsHtml = '';

        // Initial Assessment
        if (v.initial_assessment) {
            const ia = v.initial_assessment;
            const hasSymptom = Object.values(ia).some(val => val === 1 || (typeof val === 'string' && val.length > 0));
            detailsHtml += `<div class="bg-gray-100 p-3 rounded mt-2 border-l-4 border-yellow-500">
                <h5 class="font-semibold text-sm mb-1 text-yellow-700"><i class="fas fa-clipboard-list mr-1"></i> Initial Assessment:</h5>
                <ul class="list-disc list-inside ml-2 text-xs space-y-0.5">
                    ${ia.is_sick ? `<li>Sick: Yes</li>` : ''}
                    ${ia.been_treated ? `<li>Treated Before: Yes</li>` : ''}
                    ${ia.table_food ? `<li>Table Food: Yes</li>` : ''}
                    ${ia.feeding_frequency ? `<li>Feeding Freq: ${ia.feeding_frequency}</li>` : ''}
                    ${ia.allergies ? `<li>Allergies: ${ia.allergies}</li>` : ''}
                    ${ia.current_meds ? `<li>Meds: ${ia.current_meds}</li>` : ''}
                    ${hasSymptom ? `<li class="font-medium text-red-700">Symptoms: ${ia.diarrhoea ? 'Diarrhoea, ' : ''}${ia.vomiting ? 'Vomiting, ' : ''}${ia.coughing ? 'Coughing, ' : ''}${ia.scratching ? 'Scratching, ' : ''}...</li>` : ''}
                </ul>
            </div>`;
        }
        
        // Service Specific Records (Checkup, Vaccination, etc.)
        Object.keys(serviceTabs).forEach(key => {
            if (key !== 'all' && v[key] && Object.keys(v[key]).length > 1) { // Check if a service-specific record exists
                const rec = v[key];
                let content = '';
                // Customize content based on service type
                switch(key) {
                    case 'checkup':
                        content = `<p><strong>Findings:</strong> ${rec.findings || 'N/A'}</p><p><strong>Diagnosis:</strong> ${rec.diagnosis || 'N/A'}</p><p><strong>Treatment:</strong> ${rec.treatment || 'N/A'}</p>`;
                        break;
                    case 'vaccination':
                        content = `<p><strong>Vaccine:</strong> ${rec.vaccine_name || 'N/A'}</p><p><strong>Administered:</strong> ${rec.date_administered}</p><p><strong>Next Due:</strong> ${rec.next_due_date || 'N/A'}</p><p><strong>Batch:</strong> ${rec.batch_no || 'N/A'}</p>`;
                        break;
                    case 'deworming':
                        content = `<p><strong>Dewormer:</strong> ${rec.dewormer_name || 'N/A'}</p><p><strong>Dosage:</strong> ${rec.dosage || 'N/A'}</p><p><strong>Next Due:</strong> ${rec.next_due_date || 'N/A'}</p>`;
                        break;
                    // Add more cases for Grooming, Boarding, Diagnostic, Surgical, Emergency
                    case 'grooming':
                        content = `<p><strong>Notes:</strong> ${rec.grooming_notes || 'N/A'}</p><p><strong>Groomer:</strong> ${rec.assigned_groomer || 'N/A'}</p>`;
                        break;
                    case 'boarding':
                        content = `<p><strong>Check-in:</strong> ${rec.start_date || 'N/A'}</p><p><strong>Check-out:</strong> ${rec.end_date || 'N/A'}</p><p><strong>Notes:</strong> ${rec.notes || 'N/A'}</p>`;
                        break;
                    case 'diagnostic':
                        content = `<p><strong>Test:</strong> ${rec.test_name || 'N/A'}</p><p><strong>Result:</strong> ${rec.result || 'N/A'}</p><p><strong>Interpretation:</strong> ${rec.interpretation || 'N/A'}</p>`;
                        break;
                    case 'surgical':
                        content = `<p><strong>Procedure:</strong> ${rec.procedure || 'N/A'}</p><p><strong>Surgeon:</strong> ${rec.surgeon_name || 'N/A'}</p><p><strong>Post-Op:</strong> ${rec.post_op_care || 'N/A'}</p>`;
                        break;
                    case 'emergency':
                        content = `<p><strong>Complaint:</strong> ${rec.presenting_complaint || 'N/A'}</p><p><strong>Outcome:</strong> ${rec.outcome || 'N/A'}</p>`;
                        break;
                    default:
                        content = 'Service-specific details available.';
                }

                detailsHtml += `<div class="bg-blue-50 p-3 rounded mt-2 border-l-4 border-blue-500">
                    <h5 class="font-semibold text-sm mb-1 text-blue-700"><i class="fas fa-info-circle mr-1"></i> ${serviceTabs[key]} Details:</h5>
                    <div class="text-xs space-y-0.5">${content}</div>
                </div>`;
            }
        });
        
        // Prescriptions
        if (v.prescriptions && v.prescriptions.length > 0) {
            detailsHtml += `<div class="bg-pink-50 p-3 rounded mt-2 border-l-4 border-pink-500">
                <h5 class="font-semibold text-sm mb-1 text-pink-700"><i class="fas fa-pills mr-1"></i> Prescriptions:</h5>
                ${v.prescriptions.map(p => `
                    <div class="text-xs mt-1">
                        <p class="font-medium">${p.medications.map(m => `${m.name} (${m.dosage})`).join(', ')}</p>
                        ${p.differential_diagnosis ? `<p><strong>Diagnosis:</strong> ${p.differential_diagnosis}</p>` : ''}
                        ${p.notes ? `<p><strong>Notes:</strong> ${p.notes}</p>` : ''}
                    </div>
                `).join('<hr class="my-1 border-pink-200">')}
            </div>`;
        }
        
        // Referrals
        if (v.referrals && v.referrals.length > 0) {
            detailsHtml += `<div class="bg-orange-50 p-3 rounded mt-2 border-l-4 border-orange-500">
                <h5 class="font-semibold text-sm mb-1 text-orange-700"><i class="fas fa-exchange-alt mr-1"></i> Referrals:</h5>
                ${v.referrals.map(r => `
                    <div class="text-xs mt-1">
                        <p class="font-medium">To: ${r.ref_to_branch || 'N/A'} (From: ${r.ref_by_branch || 'N/A'})</p>
                        <p><strong>Reason:</strong> ${r.ref_description}</p>
                        ${r.tests_conducted ? `<p><strong>Tests:</strong> ${r.tests_conducted}</p>` : ''}
                    </div>
                `).join('<hr class="my-1 border-orange-200">')}
            </div>`;
        }

        return `
            <div data-visit-id="${v.visit_id}" class="visit-card bg-white p-4 rounded-lg shadow border-l-4 border-[#0f7ea0]">
                <div class="flex justify-between items-center mb-2">
                    <div class="text-sm font-semibold text-gray-800"><i class="fas fa-calendar mr-1"></i> ${v.visit_date}</div>
                    <span class="text-xs bg-gray-200 text-gray-800 px-2 py-1 rounded">${svc}</span>
                </div>
                <div class="grid grid-cols-3 gap-2 text-sm text-gray-600 border-b pb-2 mb-2">
                    <p><i class="fas fa-user-md mr-1"></i> ${vetName}</p>
                    <p><i class="fas fa-weight-hanging mr-1"></i> ${weight}</p>
                    <p><i class="fas fa-thermometer-half mr-1"></i> ${temp}</p>
                    <p class="col-span-3 text-xs text-gray-500">License: ${vetLicense}</p>
                </div>
                ${detailsHtml}
            </div>
        `;
    }

    // Function to populate the content for the active tab
    function populateTabContent(tabKey) {
        contentContainer.innerHTML = '';
        let filteredVisits = [];

        if (tabKey === 'all') {
            filteredVisits = visits;
        } else {
            // Filter logic: Check if the specific service record exists for the visit
            filteredVisits = visits.filter(v => v[tabKey] && Object.keys(v[tabKey]).length > 1);

            // Special case: Checkup should also include records with initial_assessment, as these are related
            if (tabKey === 'checkup') {
                filteredVisits = visits.filter(v => 
                    (v['checkup'] && Object.keys(v['checkup']).length > 1) || // Has a dedicated checkup record
                    v.initial_assessment // Has an initial assessment record
                );
            }
        }

        if (filteredVisits.length === 0) {
            contentContainer.innerHTML = `<div class="text-center text-gray-500 py-8">No ${serviceTabs[tabKey]} records found.</div>`;
        } else {
            filteredVisits.forEach(v => {
                contentContainer.innerHTML += renderDetailedVisitCard(v);
            });
        }
    }

    // Build Tab Buttons and Attach Events
    Object.entries(serviceTabs).forEach(([key, name], index) => {
        const button = document.createElement('button');
        button.className = 'pet-visit-tab-button py-2 px-1 border-b-2 font-medium text-sm text-gray-500 hover:text-[#0f7ea0] hover:border-[#0f7ea0] transition-colors whitespace-nowrap';
        button.textContent = name;
        button.dataset.key = key;
        
        button.addEventListener('click', function() {
            // Deactivate all buttons
            tabBar.querySelectorAll('.pet-visit-tab-button').forEach(btn => {
                btn.classList.remove('active', 'border-[#0f7ea0]', 'text-[#0f7ea0]');
                btn.classList.add('text-gray-500', 'border-transparent');
            });
            // Activate current button
            this.classList.add('active', 'border-[#0f7ea0]', 'text-[#0f7ea0]');
            this.classList.remove('text-gray-500', 'border-transparent');
            
            // Populate content
            populateTabContent(this.dataset.key);
        });

        tabBar.appendChild(button);
    });

    // Select the 'All Records' tab by default
    const allTabButton = tabBar.querySelector('[data-key="all"]');
    if (allTabButton) {
        allTabButton.click();
    } else {
        // Fallback if 'All' tab doesn't exist
        populateTabContent(Object.keys(serviceTabs)[0]);
        tabBar.querySelector('.pet-visit-tab-button').classList.add('active', 'border-[#0f7ea0]', 'text-[#0f7ea0]');
    }
    
    // Show the modal
    modal.classList.remove('hidden');
}
    // --- Build visits tabs for service types (All / Checkup / Vaccination / Deworming / Grooming / Boarding / Diagnostic / Surgical / Emergency)
    try {
        const visits = data.visits || [];
        const parent = document.getElementById('petMedicalHistoryList').parentNode;

        // Remove existing tabs container if any
        const existing = document.getElementById('petVisitsTabs');
        if (existing) existing.remove();

        const tabsContainer = document.createElement('div');
        tabsContainer.id = 'petVisitsTabs';
        tabsContainer.className = 'mt-6';

        const tabNames = ['All','Checkup','Vaccination','Deworming','Grooming','Boarding','Diagnostic','Surgical','Emergency'];
        const tabsBar = document.createElement('div');
        tabsBar.className = 'flex gap-2 mb-3 flex-wrap';

        const contentContainer = document.createElement('div');
        contentContainer.id = 'petVisitsContent';

        // helper to render visit card
        function renderVisitCard(v) {
            const svc = (v.services && v.services.length) ? v.services.map(s=>s.serv_name).join(', ') : (v.visit_service_type || 'General');
            const date = v.visit_date || '';
            const weight = v.weight ? (v.weight + ' kg') : '--';
            const temp = v.temperature ? (v.temperature + '°C') : '--';
            const patientType = v.patient_type || '--';
            let initial = '';
            if (v.checkup) {
                initial += '<div class="text-sm text-gray-700"><strong>Initial assessment:</strong> ' + (v.checkup.findings || v.checkup.symptoms || '') + '</div>';
            }
            let presHtml = '';
            if (v.prescriptions && v.prescriptions.length) {
                presHtml = '<div class="mt-2 text-sm text-gray-700"><strong>Prescription:</strong> ' + v.prescriptions.map(p=> (p.medication || '') + (p.notes ? ' ('+p.notes+')' : '')).join('; ') + '</div>';
            }
            let followUp = '';
            if (v.medical_history && v.medical_history.follow_up_date) {
                followUp = '<div class="mt-2 text-sm text-gray-700"><strong>Follow-up:</strong> ' + v.medical_history.follow_up_date + '</div>';
            } else if (v.vaccination && v.vaccination.next_due_date) {
                followUp = '<div class="mt-2 text-sm text-gray-700"><strong>Follow-up:</strong> ' + v.vaccination.next_due_date + '</div>';
            }

                // service-specific details
                let serviceDetails = '';
                // Vaccination details
                if (v.vaccination) {
                    const vac = v.vaccination;
                    serviceDetails += '<div class="mt-2 text-sm text-gray-700"><strong>Vaccination:</strong> ' + (vac.vaccine_name || vac.vaccine || vac.vaccine_name || 'Vaccine') + (vac.remarks ? ' — ' + vac.remarks : '') + '</div>';
                    serviceDetails += '<div class="text-xs text-gray-500">Manufacturer: ' + (vac.manufacturer || '--') + ' • Batch: ' + (vac.batch_no || vac.batch_number || '--') + '</div>';
                }
                // Deworming details
                if (v.deworming) {
                    const dw = v.deworming;
                    serviceDetails += '<div class="mt-2 text-sm text-gray-700"><strong>Deworming:</strong> ' + (dw.dewormer_name || dw.treatment || '--') + (dw.dosage ? ' ('+dw.dosage+')' : '') + '</div>';
                }
                // Grooming details
                if (v.grooming) {
                    const g = v.grooming;
                    serviceDetails += '<div class="mt-2 text-sm text-gray-700"><strong>Grooming:</strong> ' + (g.grooming_notes || g.remarks || '--') + '</div>';
                    if (g.assigned_groomer) serviceDetails += '<div class="text-xs text-gray-500">Groomer: ' + g.assigned_groomer + '</div>';
                }
                // Boarding details
                if (v.boarding) {
                    const b = v.boarding;
                    serviceDetails += '<div class="mt-2 text-sm text-gray-700"><strong>Boarding:</strong> ' + (b.notes || b.boarding_notes || '--') + '</div>';
                    serviceDetails += '<div class="text-xs text-gray-500">From: ' + (b.start_date || b.check_in || '--') + ' • To: ' + (b.end_date || b.check_out || '--') + '</div>';
                }
                // Diagnostic details
                if (v.diagnostic) {
                    const d = v.diagnostic;
                    serviceDetails += '<div class="mt-2 text-sm text-gray-700"><strong>Diagnostic:</strong> ' + (d.test_name || d.test || '--') + '</div>';
                    if (d.interpretation) serviceDetails += '<div class="text-xs text-gray-500">Interpretation: ' + d.interpretation + '</div>';
                }
                // Surgical details
                if (v.surgical) {
                    const s = v.surgical;
                    serviceDetails += '<div class="mt-2 text-sm text-gray-700"><strong>Surgery:</strong> ' + (s.procedure || s.operation || '--') + '</div>';
                    if (s.surgeon_name) serviceDetails += '<div class="text-xs text-gray-500">Surgeon: ' + s.surgeon_name + '</div>';
                }
                // Emergency details
                if (v.emergency) {
                    const e = v.emergency;
                    serviceDetails += '<div class="mt-2 text-sm text-gray-700"><strong>Emergency:</strong> ' + (e.presenting_complaint || e.treatment || '--') + '</div>';
                }

                return `
                    <div class="bg-white p-4 rounded-lg shadow border mb-3">
                        <div class="flex justify-between items-center mb-2">
                            <div class="text-sm text-gray-600">${date}</div>
                            <div class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">${svc}</div>
                        </div>
                        <div class="text-sm text-gray-800 font-semibold">Weight: ${weight} • Temp: ${temp} • Type: ${patientType}</div>
                        <div class="mt-2 text-sm text-gray-700">${initial || ''}</div>
                        ${serviceDetails}
                        ${presHtml}
                        ${followUp}
                    </div>`;
        }

        // Build tabs and default content
        tabNames.forEach(function(tn, idx) {
            const btn = document.createElement('button');
            btn.className = 'px-3 py-1 border rounded text-sm bg-white';
            btn.textContent = tn;
            btn.dataset.tab = tn.toLowerCase();
            btn.addEventListener('click', function() {
                // highlight active
                tabsBar.querySelectorAll('button').forEach(b=>b.classList.remove('bg-blue-600','text-white'));
                this.classList.add('bg-blue-600','text-white');

                // render content
                const key = this.dataset.tab;
                let filtered = [];
                if (key === 'all') filtered = visits;
                else if (key === 'checkup') filtered = visits.filter(v=> v.checkup || (v.services && v.services.some(s => s.serv_type && s.serv_type.toLowerCase().includes('check'))));
                else filtered = visits.filter(v => (v.services && v.services.some(s => s.serv_type && s.serv_type.toLowerCase().includes(key))) || (v[key]));

                // populate
                contentContainer.innerHTML = '';
                if (filtered.length === 0) {
                    contentContainer.innerHTML = '<div class="text-center text-gray-500 py-8">No records found for '+tn+'.</div>';
                } else {
                    filtered.forEach(function(fv){ contentContainer.innerHTML += renderVisitCard(fv); });
                }
            });
            if (idx === 0) {
                btn.classList.add('bg-blue-600','text-white');
            }
            tabsBar.appendChild(btn);
        });

        tabsContainer.appendChild(tabsBar);
        tabsContainer.appendChild(contentContainer);
        parent.insertBefore(tabsContainer, document.getElementById('petMedicalHistoryList'));

        // Trigger All tab click to render initial content
        tabsBar.querySelector('button').click();
    } catch (e) {
        console.error('Error building visits tabs:', e);
    }

function showSimplePetView(button) {
    // Format weight and temperature with 2 decimal places
    const formatNumber = (num) => num ? parseFloat(num).toFixed(2) : null;
    
    const weight = formatNumber(button.dataset.weight);
    const temperature = formatNumber(button.dataset.temperature);
    
    document.getElementById('viewPetName').innerText = button.dataset.name;
    document.getElementById('viewPetGender').innerText = button.dataset.gender;
    document.getElementById('viewPetBirthdate').innerText = button.dataset.birthdate;
    document.getElementById('viewPetAge').innerText = button.dataset.age;
    document.getElementById('viewPetSpecies').innerText = button.dataset.species;
    document.getElementById('viewPetBreed').innerText = button.dataset.breed;
    document.getElementById('viewPetWeight').innerText = weight ? weight + ' kg' : '-- kg';
    document.getElementById('viewPetTemperature').innerText = temperature ? temperature + '°C' : '--°C';
    document.getElementById('viewPetRegistration').innerText = button.dataset.registration;
    document.getElementById('viewPetOwner').innerText = button.dataset.owner;

    const photo = button.dataset.photo;
    if (photo) {
        document.getElementById('viewPetPhoto').src = '/storage/' + photo;
        document.getElementById('viewPetPhoto').classList.remove('hidden');
        document.getElementById('viewPetNoPhoto').classList.add('hidden');
    } else {
        document.getElementById('viewPetPhoto').classList.add('hidden');
        document.getElementById('viewPetNoPhoto').classList.remove('hidden');
    }

    document.getElementById('viewPetModal').classList.remove('hidden');
}

function closeEnhancedPetModal() {
    document.getElementById('enhancedViewPetModal').classList.add('hidden');
}
// Owner functionality
function openAddOwnerModal() {
    const form = document.getElementById('ownerForm');
    form.reset();
    form.action = `{{ route('pet-management.storeOwner') }}`;
    document.getElementById('ownerFormMethod').value = 'POST';
    document.getElementById('ownerModalTitle').textContent = 'Add Pet Owner';
    document.getElementById('ownerModal').classList.remove('hidden');
}

function openEditOwnerModal(owner) {
    const form = document.getElementById('ownerForm');
    form.action = `/pet-management/owners/${owner.own_id}`;
    document.getElementById('ownerFormMethod').value = 'PUT';
    document.getElementById('owner_id').value = owner.own_id;
    document.getElementById('owner_name').value = owner.own_name;
    document.getElementById('owner_contact').value = owner.own_contactnum;
    document.getElementById('owner_location').value = owner.own_location;
    document.getElementById('ownerModalTitle').textContent = 'Edit Pet Owner';
    document.getElementById('ownerModal').classList.remove('hidden');
}

function closeOwnerModal() {
    document.getElementById('ownerModal').classList.add('hidden');
}

function viewOwnerDetails(button) {
    const ownerId = button.dataset.ownerId;
    
    if (!ownerId) {
        console.error('Owner ID not found');
        // Fallback to simple modal
        document.getElementById('viewOwnerName').innerText = button.dataset.name;
        document.getElementById('viewOwnerContact').innerText = button.dataset.contact;
        document.getElementById('viewOwnerLocation').innerText = button.dataset.location;
        document.getElementById('viewOwnerModal').classList.remove('hidden');
        return;
    }
    
    console.log('Fetching owner details for ID:', ownerId);
    
    fetch('/pet-management/owner/' + ownerId + '/details')
        .then(response => {
            if (!response.ok) throw new Error('Failed to load');
            return response.json();
        })
        .then(data => {
            console.log('Owner data received:', data);
            displayEnhancedOwnerModal(data);
        })
        .catch(error => {
            console.log('Error, using simple view:', error);
            // Fallback to simple modal
            document.getElementById('viewOwnerName').innerText = button.dataset.name;
            document.getElementById('viewOwnerContact').innerText = button.dataset.contact;
            document.getElementById('viewOwnerLocation').innerText = button.dataset.location;
            document.getElementById('viewOwnerModal').classList.remove('hidden');
        });
}

function displayEnhancedOwnerModal(data) {
    const owner = data.owner;
    const modal = document.getElementById('enhancedViewOwnerModal');
    
    // Set owner info
    document.getElementById('enhancedOwnerName').textContent = owner.own_name;
    document.getElementById('enhancedOwnerContact').textContent = owner.own_contactnum;
    document.getElementById('enhancedOwnerLocation').textContent = owner.own_location;
    
    // Set stats
    document.getElementById('ownerStatsAnimals').textContent = data.stats.pets;
    document.getElementById('ownerStatsAppointments').textContent = data.stats.visits;
    document.getElementById('ownerStatsMedical').textContent = data.stats.medicalRecords;
    document.getElementById('ownerStatsLastVisit').textContent = data.stats.lastVisit;
    
    // Display pets
   const petsList = document.getElementById('ownerPetsList');
petsList.innerHTML = '';
 
if (data.pets && data.pets.length > 0) {
    data.pets.forEach(function(pet) {
        const petCard = document.createElement('div');
        // ADDED: cursor-pointer and click event to the pet card
        petCard.className = 'bg-white p-4 rounded-lg shadow border hover:shadow-lg transition-shadow cursor-pointer'; 
        
        // ADDED: Data attributes for the viewPetDetails function
        petCard.dataset.petId = pet.pet_id;
        petCard.dataset.name = pet.pet_name;
        petCard.dataset.gender = pet.pet_gender;
        petCard.dataset.age = pet.pet_age;
        petCard.dataset.species = pet.pet_species;
        petCard.dataset.breed = pet.pet_breed;
        // You'll need to fetch more data if you want to use the simple modal fallback:
        // petCard.dataset.weight = pet.pet_weight; 
        // petCard.dataset.temperature = pet.pet_temperature;
        // petCard.dataset.photo = pet.pet_photo; 
        // petCard.dataset.owner = pet.owner_name; 

        // Important: Use the same logic as the table rows that successfully opens the modal
        petCard.onclick = function() {
            // First, close the current owner modal
            closeEnhancedOwnerModal();
            // Then, call the existing function to open the pet details modal
            viewPetDetails(this); 
        };
        
        const photoHtml = pet.pet_photo 
            ? '<img src="/storage/' + pet.pet_photo + '" alt="' + pet.pet_name + '" class="w-16 h-16 object-cover rounded-full mx-auto mb-3">'
            : '<div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-3"><i class="fas fa-paw text-gray-400"></i></div>';
        
        petCard.innerHTML = photoHtml + 
            '<h4 class="font-semibold text-center text-gray-800">' + pet.pet_name + '</h4>' +
            '<p class="text-sm text-gray-600 text-center">' + pet.pet_species + ' - ' + pet.pet_breed + '</p>' +
            '<p class="text-xs text-gray-500 text-center mt-1">' + pet.pet_age + ' • ' + pet.pet_gender + '</p>';
        
        petsList.appendChild(petCard);
    });
} else {
    petsList.innerHTML = '<div class="col-span-full text-center text-gray-500 py-8"><i class="fas fa-paw text-3xl mb-2"></i><p>No pets registered</p></div>';
}
    
    const appointmentsList = document.getElementById('ownerAppointmentsList');
    appointmentsList.innerHTML = ''; // Clear previous content
    
    if (data.visits && data.visits.length > 0) {
        data.visits.forEach(function(visit) {
            // Determine color based on status
            let statusColor = 'bg-gray-100 text-gray-700';
            if (visit.status.toLowerCase() === 'completed') {
                statusColor = 'bg-green-100 text-green-700';
            } else if (visit.status.toLowerCase() === 'arrived') {
                statusColor = 'bg-yellow-100 text-yellow-700';
            } else if (visit.status.toLowerCase() === 'cancelled') {
                statusColor = 'bg-red-100 text-red-700';
            }
            let speieces = '<i class="fas fa-cat" title="Cat"></i>';
            if(visit.pet_species === 'Dog') {
                speieces = '<i class="fas fa-dog" title="Dog"></i>';
            }

            const appointmentDiv = document.createElement('div');
            appointmentDiv.className = 'bg-white p-4 rounded-lg shadow border-l-4 border-blue-400';
            
            appointmentDiv.innerHTML = `
                <div class="flex justify-between items-center mb-2">
                    <h4 class="font-semibold text-lg text-gray-800">${speieces} ${visit.pet_name}</h4>
                    <span class="text-xs font-medium px-2 py-1 rounded-full ${statusColor}">
                        ${visit.status.charAt(0).toUpperCase() + visit.status.slice(1)}
                    </span>
                </div>
                <div class="grid grid-cols-2 gap-2 text-sm text-gray-600">
                    <p><i class="fas fa-calendar mr-2 text-blue-500"></i>${visit.date}</p>
                    <p><i class="fas fa-syringe mr-2 text-green-500"></i>${visit.type || 'General Checkup'}</p>
                    <p> Weight: ${visit.weight || '--'}</p>
                    <p> Temperature: ${visit.temperature || '--'}</p>
                    <p> Patient Type : ${visit.patient_type || '--'} </p>
                    <p> Service Type: ${visit.service_type || '--'} </p>
                    <p> Workflow Status : ${visit.workflow_status || '--'} </p>
                    <p class="col-span-2"><i class="fas fa-user-md mr-2 text-purple-500"></i>Veterinarian: ${visit.veterinarian || '--'}</p>
                </div>
            `;
            
            appointmentsList.appendChild(appointmentDiv);
        });
    } else {
        appointmentsList.innerHTML = '<div class="text-center text-gray-500 py-8"><i class="fas fa-calendar-times text-3xl mb-2"></i><p>No Visits found for this owner.</p></div>';
    }

    const purchasesList = document.getElementById('ownerPurchasesList');
    purchasesList.innerHTML = ''; // Clear previous content

    if (data.purchases && data.purchases.length > 0) {
        // Group by date to show transactions clearly
        const groupedPurchases = data.purchases.reduce((acc, purchase) => {
            const date = purchase.date;
            if (!acc[date]) {
                acc[date] = [];
            }
            acc[date].push(purchase);
            return acc;
        }, {});

        for (const date in groupedPurchases) {
            const transactionTotal = groupedPurchases[date].reduce((sum, item) => sum + (item.quantity * item.price), 0);
            
            const dateSection = document.createElement('div');
            dateSection.className = 'border-t pt-3 mt-3 first:border-t-0';
            dateSection.innerHTML = `
                <div class="flex justify-between items-center bg-gray-50 p-2 rounded-t font-semibold text-sm">
                    <span><i class="fas fa-calendar-alt mr-2 text-gray-500"></i>${date}</span>
                    <span>Total: ₱${transactionTotal.toFixed(2)}</span>
                </div>
                <ul class="divide-y divide-gray-100"></ul>
            `;
            const ul = dateSection.querySelector('ul');
            
            groupedPurchases[date].forEach(item => {
                const li = document.createElement('li');
                li.className = 'flex justify-between items-center p-2 text-sm';
                li.innerHTML = `
                    <div>
                        <span class="font-medium">${item.product || 'N/A'}</span>
                        <span class="text-xs text-gray-500 ml-2">(${item.quantity} x ₱${item.price.toFixed(2)})</span>
                    </div>
                    <span class="font-semibold">₱${(item.quantity * item.price).toFixed(2)}</span>
                `;
                ul.appendChild(li);
            });

            purchasesList.appendChild(dateSection);
        }

    } else {
        purchasesList.innerHTML = '<div class="text-center text-gray-500 py-8"><i class="fas fa-shopping-basket text-3xl mb-2"></i><p>No purchase history found for this owner.</p></div>';
    }
    modal.classList.remove('hidden');
}

// Medical History functionality
function openAddMedicalModal() {
    const form = document.getElementById('medicalForm');
    form.reset();
    form.action = `{{ route('pet-management.storeMedicalHistory') }}`;
    document.getElementById('medicalFormMethod').value = 'POST';
    document.getElementById('medicalModalTitle').textContent = 'Add Medical Record';
    document.getElementById('medicalModal').classList.remove('hidden');
}

// NEW FUNCTION: Add medical history for specific pet
function openAddMedicalForPet(petId, petName) {
    const form = document.getElementById('medicalForm');
    form.reset();
    form.action = `{{ route('pet-management.storeMedicalHistory') }}`;
    document.getElementById('medicalFormMethod').value = 'POST';
    document.getElementById('medicalModalTitle').textContent = `Add Medical Record for ${petName}`;
    
    // Pre-select the pet
    document.getElementById('medical_pet_id').value = petId;
    
    document.getElementById('medicalModal').classList.remove('hidden');
}

function openEditMedicalModal(medical) {
    const form = document.getElementById('medicalForm');
    form.action = `/pet-management/medical-history/${medical.id}`;
    document.getElementById('medicalFormMethod').value = 'PUT';
    document.getElementById('medical_id').value = medical.id;
    document.getElementById('medical_pet_id').value = medical.pet_id;
    document.getElementById('medical_visit_date').value = medical.visit_date;
    document.getElementById('medical_diagnosis').value = medical.diagnosis;
    document.getElementById('medical_treatment').value = medical.treatment;
    document.getElementById('medical_medication').value = medical.medication || '';
    document.getElementById('medical_veterinarian').value = medical.veterinarian_name;
    document.getElementById('medical_followup_date').value = medical.follow_up_date || '';
    document.getElementById('medical_notes').value = medical.notes || '';
    document.getElementById('medicalModalTitle').textContent = 'Edit Medical Record';
    document.getElementById('medicalModal').classList.remove('hidden');
}

function closeMedicalModal() {
    document.getElementById('medicalModal').classList.add('hidden');
}

function viewMedicalDetails(button) {
    const medicalId = button.dataset.medicalId;
    
    if (!medicalId) {
        // Fallback to simple modal
        document.getElementById('viewMedicalPet').innerText = button.dataset.pet;
        document.getElementById('viewMedicalVisit').innerText = button.dataset.visit;
        document.getElementById('viewMedicalDiagnosis').innerText = button.dataset.diagnosis;
        document.getElementById('viewMedicalTreatment').innerText = button.dataset.treatment;
        document.getElementById('viewMedicalMedication').innerText = button.dataset.medication || 'N/A';
        document.getElementById('viewMedicalVeterinarian').innerText = button.dataset.veterinarian;
        document.getElementById('viewMedicalFollowup').innerText = button.dataset.followup;
        document.getElementById('viewMedicalNotes').innerText = button.dataset.notes || 'N/A';
        document.getElementById('viewMedicalModal').classList.remove('hidden');
        return;
    }
    
    fetch('/pet-management/medical/' + medicalId + '/details')
        .then(response => response.json())
        .then(data => {
            displayEnhancedMedicalModal(data);
        })
        .catch(error => {
            console.log('Using simple view:', error);
            document.getElementById('viewMedicalPet').innerText = button.dataset.pet;
            document.getElementById('viewMedicalVisit').innerText = button.dataset.visit;
            document.getElementById('viewMedicalDiagnosis').innerText = button.dataset.diagnosis;
            document.getElementById('viewMedicalTreatment').innerText = button.dataset.treatment;
            document.getElementById('viewMedicalMedication').innerText = button.dataset.medication || 'N/A';
            document.getElementById('viewMedicalVeterinarian').innerText = button.dataset.veterinarian;
            document.getElementById('viewMedicalFollowup').innerText = button.dataset.followup;
            document.getElementById('viewMedicalNotes').innerText = button.dataset.notes || 'N/A';
            document.getElementById('viewMedicalModal').classList.remove('hidden');
        });
}

function displayEnhancedMedicalModal(data) {
    const medical = data.medical;
    const pet = medical.pet;
    const modal = document.getElementById('enhancedViewMedicalModal');
    
    // Set pet info
    if (pet) {
        document.getElementById('medicalPetName').textContent = pet.pet_name;
        document.getElementById('medicalPetSpecies').textContent = pet.pet_species;
        document.getElementById('medicalPetBreed').textContent = pet.pet_breed;
        document.getElementById('medicalPetAge').textContent = pet.pet_age;
        document.getElementById('medicalPetGender').textContent = pet.pet_gender;
        
        if (pet.pet_photo) {
            document.getElementById('medicalPetPhoto').src = '/storage/' + pet.pet_photo;
            document.getElementById('medicalPetPhoto').classList.remove('hidden');
            document.getElementById('medicalPetNoPhoto').classList.add('hidden');
        } else {
            document.getElementById('medicalPetPhoto').classList.add('hidden');
            document.getElementById('medicalPetNoPhoto').classList.remove('hidden');
        }
        
        if (pet.owner) {
            document.getElementById('medicalOwnerName').textContent = pet.owner.own_name;
            document.getElementById('medicalOwnerContact').textContent = pet.owner.own_contactnum;
            document.getElementById('medicalOwnerLocation').textContent = pet.owner.own_location;
        }
    }
    
    // Set current record details
    document.getElementById('medicalVisitDate').textContent = medical.visit_date;
    document.getElementById('medicalVeterinarian').textContent = medical.veterinarian_name;
    document.getElementById('medicalFollowUpDate').textContent = medical.follow_up_date || 'N/A';
    document.getElementById('medicalDiagnosis').textContent = medical.diagnosis;
    document.getElementById('medicalTreatment').textContent = medical.treatment;
    document.getElementById('medicalMedication').textContent = medical.medication || 'N/A';
    document.getElementById('medicalNotes').textContent = medical.notes || 'N/A';
    
    // Display timeline
    const timeline = document.getElementById('medicalTimeline');
    timeline.innerHTML = '';
    
    if (data.timeline && data.timeline.length > 0) {
        data.timeline.forEach(function(visit) {
            const visitDiv = document.createElement('div');
            visitDiv.className = 'bg-white p-3 rounded shadow-sm border-l-4 ' + 
                (visit.id === medical.id ? 'border-green-500' : 'border-gray-300');
            
            let vitals = '';
            if (visit.weight || visit.temperature) {
                vitals = '<div class="mt-2 text-xs"><span class="text-green-600">⚖️ ' + 
                    (visit.weight || '--') + ' kg</span> <span class="text-red-600">🌡️ ' + 
                    (visit.temperature || '--') + '°C</span></div>';
            }
            
            visitDiv.innerHTML = '<div class="text-xs text-gray-500">' + visit.visit_date + '</div>' +
                '<div class="font-semibold text-sm">' + visit.diagnosis + '</div>' +
                '<div class="text-xs text-gray-600">' + visit.veterinarian_name + '</div>' +
                vitals;
            
            timeline.appendChild(visitDiv);
        });
    } else {
        timeline.innerHTML = '<div class="text-center text-gray-500 py-4 text-sm">No history available</div>';
    }
    
    modal.classList.remove('hidden');
}

// Close modals when clicking outside
document.querySelectorAll('.fixed').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.add('hidden');
        }
    });
});

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const breedContainer = event.target.closest('.relative');
    const breedDropdown = document.getElementById('breedDropdown');
    
    if (!breedContainer || !breedContainer.contains(document.getElementById('pet_breed'))) {
        hideBreedDropdown();
    }
});

// Add form validation to ensure breed is selected from dropdown
document.getElementById('petForm').addEventListener('submit', function(e) {
    const breedInput = document.getElementById('pet_breed').value;
    const selectedBreed = document.getElementById('selected_breed').value;
    const species = document.getElementById('pet_species').value;
    
    // If species is selected and breed is entered, validate it's from the dropdown
    if (species && breedInput && !selectedBreed) {
        // Check if entered breed exists in current species breeds
        const validBreed = currentBreeds.find(breed => 
            breed.toLowerCase() === breedInput.toLowerCase()
        );
        
        if (validBreed) {
            document.getElementById('selected_breed').value = validBreed;
            document.getElementById('pet_breed').value = validBreed;
        } else {
            e.preventDefault();
            alert('Please select a valid breed from the dropdown or clear the field to enter a custom breed.');
            return false;
        }
    }
});

function switchOwnerTab(tabName) {
    document.querySelectorAll('.owner-tab-content').forEach(function(content) {
        content.classList.add('hidden');
    });
    document.querySelectorAll('.owner-tab-button').forEach(function(button) {
        button.classList.remove('active');
    });
    document.getElementById('owner-' + tabName + '-content').classList.remove('hidden');
    document.getElementById('owner-' + tabName + '-tab').classList.add('active');
}

function closeEnhancedOwnerModal() {
    document.getElementById('enhancedViewOwnerModal').classList.add('hidden');
}

function closeEnhancedMedicalModal() {
    document.getElementById('enhancedViewMedicalModal').classList.add('hidden');
}
</script>

@endsection