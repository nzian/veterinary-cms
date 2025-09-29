@extends('AdminBoard')

@section('content')
  <div class="min-h-screen">
    <div class="max-w-7xl mx-auto bg-white p-6 rounded-lg shadow">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-[#0f7ea0] font-bold text-xl">Pets</h2>
      <button onclick="openAddModal()" class="bg-[#0f7ea0] text-white text-sm px-4 py-2 rounded hover:bg-[#0c6a86]">
      + Add Pets
      </button>
    </div>
    {{-- Success Message --}}
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

    <div class="flex justify-between items-center mt-4 text-sm font-semibold text-black">
      {{-- Show Entries Dropdown --}}
      <form method="GET" action="{{ request()->url() }}" class="flex items-center space-x-2">
      <label for="perPage" class="text-sm text-black">Show</label>
      <select name="perPage" id="perPage" onchange="this.form.submit()"
        class="border border-gray-400 rounded px-2 py-1 text-sm">
        @foreach ([10, 20, 50, 100, 'all'] as $limit)
      <option value="{{ $limit }}" {{ request('perPage') == $limit ? 'selected' : '' }}>
      {{ $limit === 'all' ? 'All' : $limit }}
      </option>
      @endforeach
      </select>
      <span>entries</span>
      </form>
    </div>
    <br>

    {{-- Table --}}
    <div class="overflow-x-auto">
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
        <th class="border px-2 py-2">Age</th>
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
      <td class="border px-2 py-2">{{ $pet->pet_species}}</td>
      <td class="border px-2 py-2">{{ $pet->pet_breed }}</td>
      <td class="border px-2 py-2">{{ $pet->pet_age }}</td>
      <td class="border px-2 py-1">
        <div class="flex justify-center items-center gap-1">
       <button
  class="bg-[#0f7ea0] text-white px-2 py-1 rounded hover:bg-[#0c6a86] flex items-center gap-1 text-xs rounded editPetBtn"
  data-id="{{ $pet->pet_id }}"
  data-name="{{ $pet->pet_name }}"
  data-gender="{{ $pet->pet_gender }}"
  data-age="{{ $pet->pet_age }}"
  data-species="{{ $pet->pet_species }}"
  data-breed="{{ $pet->pet_breed }}"
  data-weight="{{ $pet->pet_weight }}"
  data-temperature="{{ $pet->pet_temperature }}"
  data-registration="{{ $pet->pet_registration }}"
  data-owner="{{ $pet->own_id }}"
  data-photo="{{ $pet->pet_photo }}"
>
  <i class="fas fa-pen"></i>
</button>

        <button
  onclick="viewPetDetails(this)"
  class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 flex items-center gap-1 text-xs"
  data-name="{{ $pet->pet_name }}"
  data-gender="{{ $pet->pet_gender }}"
  data-age="{{ $pet->pet_age }}"
  data-species="{{ $pet->pet_species }}"
  data-breed="{{ $pet->pet_breed }}"
  data-weight="{{ $pet->pet_weight }}"
  data-temperature="{{ $pet->pet_temperature }}"
  data-registration="{{ \Carbon\Carbon::parse($pet->pet_registration)->format('F d, Y') }}"
  data-photo="{{ $pet->pet_photo }}"
>
  <i class="fas fa-eye"></i>
</button>

        <!-- Delete Button -->
        <form action="{{ route('pets.destroy', $pet->pet_id) }}" method="POST"
        onsubmit="return confirm('Are you sure you want to delete this pet?');" class="inline">
        @csrf
        @method('DELETE')
        <button type="submit"
        class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 flex items-center gap-1 text-xs">
        <i class="fas fa-trash"></i> 
        </button>
        </form>
        </div>
      </td>
      </tr>
      @empty
      <tr>
      <td colspan="10" class="text-center text-gray-500 py-4">No pets found.</td>
      </tr>
      @endforelse
      </tbody>
      </table>
    </div>

    {{-- Pagination --}}
    <div class="flex justify-between items-center mt-4 text-sm font-semibold text-black">
      <div>Showing {{ $pets->firstItem() }} to {{ $pets->lastItem() }} of {{ $pets->total() }} entries</div>
      <div class="inline-flex border border-gray-400 rounded overflow-hidden">
      @if ($pets->onFirstPage())
      <button disabled class="px-3 py-1 text-gray-400 cursor-not-allowed border-r">Previous</button>
    @else
      <a href="{{ $pets->previousPageUrl() }}" class="px-3 py-1 text-black hover:bg-gray-200 border-r">Previous</a>
    @endif

      @for ($i = 1; $i <= $pets->lastPage(); $i++)
      @if ($i == $pets->currentPage())
      <button aria-current="page" class="px-3 py-1 bg-[#0f7ea0] text-white border-r">{{ $i }}</button>
      @else
      <a href="{{ $pets->url($i) }}" class="px-3 py-1 hover:bg-gray-200 border-r">{{ $i }}</a>
      @endif
    @endfor

      @if ($pets->hasMorePages())
      <a href="{{ $pets->nextPageUrl() }}" class="px-3 py-1 text-black hover:bg-gray-200">Next</a>
    @else
      <button disabled class="px-3 py-1 text-gray-400 cursor-not-allowed">Next</button>
    @endif
      </div>
    </div>
    </div>

{{-- Pet Modal --}}
<div id="petModal" class="fixed inset-0 bg-black bg-opacity-30 flex justify-center items-center hidden z-50">
  <div class="bg-white w-full max-w-4xl p-6 rounded shadow-lg relative max-h-[90vh] overflow-y-auto">
     <h2 class="text-lg font-semibold text-[#0f7ea0] mb-4" id="modalTitle">Add Pet</h2>
    <form id="petForm" method="POST" enctype="multipart/form-data">
      @csrf
      <input type="hidden" name="_method" id="formMethod" value="POST">
      <input type="hidden" name="pet_id" id="pet_id">

{{-- Photo Upload Section --}}
<div class="mb-4">
    <label class="block text-sm font-medium mb-2">Pet Photo</label>
    <div class="flex items-center space-x-4">
        <div class="relative">
            <input type="file" name="pet_photo" id="pet_photo" accept="image/*" 
                   class="hidden" onchange="previewImage(this)">
            <label for="pet_photo" 
                   class="cursor-pointer bg-gray- 20 border-2 border-dashed border-gray-100 rounded-lg p-4 hover:bg-gray-50 transition-colors">
                <div class="text-center">
                    <i class="fas fa-camera text-gray-400 text-2xl mb-2"></i>
                    <p class="text-sm text-gray-600">Click to upload photo</p>
                </div>
            </label>
        </div>
        
        <!-- Image Preview -->
        <div id="imagePreview" class="hidden">
            <img id="previewImg" src="" alt="Preview" 
                 class="w-20 h-20 object-cover rounded-lg border">
            <button type="button" onclick="removeImage()" 
                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600">
                ×
            </button>
        </div>
        
        <!-- Current Image (for edit mode) -->
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
    @error('pet_photo')
        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
    @enderror
</div>

{{-- Row 1: Pet Name --}}
<div class="mb-4">
    <label class="block text-sm">Pet Name</label>
    <input type="text" name="pet_name" id="pet_name"
           class="w-full border px-2 py-1 rounded @error('pet_name') border-red-500 @enderror"
           value="{{ old('pet_name') }}">
    @error('pet_name')
        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
    @enderror
</div>

{{-- Row 2: Weight & Species --}}
<div class="flex gap-4 mb-4">
    <div class="w-1/2">
        <label class="block text-sm">Weight (kg)</label>
        <input type="number" step="0.1" name="pet_weight" id="pet_weight"
               class="w-full border px-2 py-1 rounded @error('pet_weight') border-red-500 @enderror"
               value="{{ old('pet_weight') }}">
        @error('pet_weight')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>
    <div class="w-1/2">
        <label class="block text-sm">Species</label>
        <select name="pet_species" id="pet_species"
                class="w-full border px-2 py-1 rounded @error('pet_species') border-red-500 @enderror">
            <option value="">Select Species</option>
            <option value="Dog" {{ old('pet_species') == 'Dog' ? 'selected' : '' }}>Dog</option>
            <option value="Cat" {{ old('pet_species') == 'Cat' ? 'selected' : '' }}>Cat</option>
        </select>
        @error('pet_species')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>

{{-- Row 3: Breed & Age --}}
<div class="flex gap-4 mb-4">
    <div class="w-1/2">
        <label class="block text-sm">Breed</label>
        <input type="text" name="pet_breed" id="pet_breed"
               class="w-full border px-2 py-1 rounded @error('pet_breed') border-red-500 @enderror"
               value="{{ old('pet_breed') }}">
        @error('pet_breed')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>
    <div class="w-1/2">
        <label class="block text-sm">Age</label>
        <input type="text" name="pet_age" id="pet_age"
               placeholder="e.g. 3 months, 1 year 2 months"
               class="w-full border px-2 py-1 rounded @error('pet_age') border-red-500 @enderror"
               value="{{ old('pet_age') }}">
        @error('pet_age')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>

{{-- Row 4: Gender & Temperature --}}
<div class="flex gap-4 mb-4">
    <div class="w-1/2">
        <label class="block text-sm">Gender</label>
        <select name="pet_gender" id="pet_gender"
                class="w-full border px-2 py-1 rounded @error('pet_gender') border-red-500 @enderror">
            <option value="">Select Gender</option>
            <option value="Male" {{ old('pet_gender') == 'Male' ? 'selected' : '' }}>Male</option>
            <option value="Female" {{ old('pet_gender') == 'Female' ? 'selected' : '' }}>Female</option>
        </select>
        @error('pet_gender')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>
    <div class="w-1/2">
        <label class="block text-sm">Temperature (°C)</label>
        <input type="number" step="0.1" name="pet_temperature" id="pet_temperature"
               class="w-full border px-2 py-1 rounded @error('pet_temperature') border-red-500 @enderror"
               value="{{ old('pet_temperature') }}">
        @error('pet_temperature')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>

{{-- Row 5: Registration Date & Owner --}}
<div class="flex gap-4 mb-4">
    <div class="w-1/2">
        <label class="block text-sm">Registration Date</label>
        <input type="date" name="pet_registration" id="pet_registration"
               class="w-full border px-2 py-1 rounded @error('pet_registration') border-red-500 @enderror"
               value="{{ old('pet_registration') }}">
        @error('pet_registration')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>
    <div class="w-1/2">
        <label class="block text-sm">Owner</label>
        <select name="own_id" id="own_id"
                class="w-full border px-2 py-1 rounded @error('own_id') border-red-500 @enderror">
            <option value="">Select Owner</option>
            @foreach ($owners as $owner)
                <option value="{{ $owner->own_id }}" {{ old('own_id') == $owner->own_id ? 'selected' : '' }}>
                    {{ $owner->own_name }}
                </option>
            @endforeach
        </select>
        @error('own_id')
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>

      {{-- Buttons --}}
      <div class="flex justify-end space-x-2 mt-6">
  <button type="button" class="px-4 py-2 bg-gray-300 rounded text-sm hover:bg-gray-400" onclick="closeModal()">Cancel</button>
  <button type="submit" class="px-4 py-2 bg-[#0f7ea0] text-white rounded text-sm hover:bg-[#0d6b85]">Save</button>
</div>

    </form>
  </div>
</div>

{{-- View Pet Modal --}}
<div id="viewPetModal" class="fixed inset-0 bg-black bg-opacity-40 flex justify-center items-center z-50 hidden">
  <div class="bg-white w-full max-w-5xl p-6 rounded-lg shadow-lg relative max-h-[90vh] overflow-y-auto">
    <div class="flex justify-between items-center border-b pb-3">
      <h2 class="text-xl font-semibold text-[#0f7ea0]">Pet Details</h2>
      <button onclick="document.getElementById('viewPetModal').classList.add('hidden')" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
    </div>

    <div class="mt-4">
        <!-- Pet Photo -->
        <div class="flex justify-center mb-6" id="viewPetPhotoContainer">
            <img id="viewPetPhoto" src="" alt="Pet Photo" class="w-32 h-32 object-cover rounded-full border-4 border-gray-200 hidden">
            <div id="viewPetNoPhoto" class="w-32 h-32 bg-gray-200 rounded-full flex items-center justify-center border-4 border-gray-300 hidden">
                <i class="fas fa-paw text-gray-400 text-3xl"></i>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <p><strong>Pet Name:</strong> <span id="viewPetName"></span></p>
            <p><strong>Gender:</strong> <span id="viewPetGender"></span></p>
            <p><strong>Age:</strong> <span id="viewPetAge"></span></p>
            <p><strong>Species:</strong> <span id="viewPetSpecies"></span></p>
            <p><strong>Breed:</strong> <span id="viewPetBreed"></span></p>
            <p><strong>Weight:</strong> <span id="viewPetWeight"></span> kg</p>
            <p><strong>Temperature:</strong> <span id="viewPetTemperature"></span>°C</p>
            <p><strong>Registration Date:</strong> <span id="viewPetRegistration"></span></p>
        </div>
    </div>

    <div class="flex justify-end mt-6">
      <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 rounded text-sm" onclick="document.getElementById('viewPetModal').classList.add('hidden')">Close</button>
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

  {{-- Modal Scripts --}}
  <script>
    // Image preview function
    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('previewImg').src = e.target.result;
                document.getElementById('imagePreview').classList.remove('hidden');
                document.getElementById('imagePreview').classList.add('relative');
                // Hide current image if editing
                document.getElementById('currentImage').classList.add('hidden');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Remove new image preview
    function removeImage() {
        document.getElementById('pet_photo').value = '';
        document.getElementById('imagePreview').classList.add('hidden');
        document.getElementById('previewImg').src = '';
        // Show current image again if editing
        const currentImg = document.getElementById('currentImg');
        if (currentImg.src) {
            document.getElementById('currentImage').classList.remove('hidden');
        }
    }

    // Remove current image (for edit mode)
    function removeCurrentImage() {
        document.getElementById('remove_photo').value = '1';
        document.getElementById('currentImage').classList.add('hidden');
    }

    // Show image in modal
    function showImageModal(src, caption) {
        document.getElementById('modalImage').src = src;
        document.getElementById('modalImageCaption').textContent = caption;
        document.getElementById('imageModal').classList.remove('hidden');
    }

    // View pet details
    function viewPetDetails(button) {
        document.getElementById('viewPetName').innerText = button.dataset.name;
        document.getElementById('viewPetGender').innerText = button.dataset.gender;
        document.getElementById('viewPetAge').innerText = button.dataset.age;
        document.getElementById('viewPetSpecies').innerText = button.dataset.species;
        document.getElementById('viewPetBreed').innerText = button.dataset.breed;
        document.getElementById('viewPetWeight').innerText = button.dataset.weight || 'N/A';
        document.getElementById('viewPetTemperature').innerText = button.dataset.temperature || 'N/A';
        document.getElementById('viewPetRegistration').innerText = button.dataset.registration;

        // Handle pet photo
        const photo = button.dataset.photo;
        if (photo) {
            document.getElementById('viewPetPhoto').src = `{{ asset('storage') }}/${photo}`;
            document.getElementById('viewPetPhoto').classList.remove('hidden');
            document.getElementById('viewPetNoPhoto').classList.add('hidden');
        } else {
            document.getElementById('viewPetPhoto').classList.add('hidden');
            document.getElementById('viewPetNoPhoto').classList.remove('hidden');
        }

        document.getElementById('viewPetModal').classList.remove('hidden');
    }

    // Edit pet functionality
    document.querySelectorAll('.editPetBtn').forEach(button => {
        button.addEventListener('click', () => {
            // Reset form first
            document.getElementById('petForm').reset();
            document.getElementById('imagePreview').classList.add('hidden');
            document.getElementById('currentImage').classList.add('hidden');
            document.getElementById('remove_photo').value = '0';

            // Fill form data
            document.getElementById('pet_id').value = button.dataset.id;
            document.getElementById('pet_name').value = button.dataset.name;
            document.getElementById('pet_gender').value = button.dataset.gender;
            document.getElementById('pet_age').value = button.dataset.age;
            document.getElementById('pet_species').value = button.dataset.species;
            document.getElementById('pet_breed').value = button.dataset.breed;
            document.getElementById('pet_weight').value = button.dataset.weight;
            document.getElementById('pet_temperature').value = button.dataset.temperature;
            document.getElementById('pet_registration').value = button.dataset.registration;
            document.getElementById('own_id').value = button.dataset.owner;

            // Handle current photo
            const photo = button.dataset.photo;
            if (photo) {
                document.getElementById('currentImg').src = `{{ asset('storage') }}/${photo}`;
                document.getElementById('currentImage').classList.remove('hidden');
            }

            // Set form for editing
            const petId = button.dataset.id;
            document.getElementById('petForm').action = `/pets/${petId}`;
            document.getElementById('formMethod').value = 'PUT';
            document.getElementById('modalTitle').textContent = 'Edit Pet';

            // Show modal
            document.getElementById('petModal').classList.remove('hidden');
        });
    });

    // Open add modal
    function openAddModal() {
        const form = document.getElementById('petForm');
        form.reset();
        form.action = `{{ route('pets.store') }}`;
        document.getElementById('formMethod').value = 'POST';
        document.getElementById('modalTitle').textContent = 'Add Pet';
        document.getElementById('imagePreview').classList.add('hidden');
        document.getElementById('currentImage').classList.add('hidden');
        document.getElementById('remove_photo').value = '0';
        document.getElementById('petModal').classList.remove('hidden');
    }

    // Close modal
    function closeModal() {
        document.getElementById('petModal').classList.add('hidden');
        document.getElementById('petForm').reset();
        document.getElementById('imagePreview').classList.add('hidden');
        document.getElementById('currentImage').classList.add('hidden');
    }

    // Close modals when clicking outside
    document.getElementById('petModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    document.getElementById('viewPetModal').addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.add('hidden');
        }
    });

    document.getElementById('imageModal').addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.add('hidden');
        }
    });
  </script>
@endsection