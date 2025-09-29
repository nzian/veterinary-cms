@extends('AdminBoard')

@section('content')
    <div class="min-h-screen">
        <div class="max-w-7xl mx-auto bg-white p-6 rounded-lg shadow">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-[#0f7ea0] font-bold text-xl">Pet Owners</h2>
                <button onclick="openAddModal()"
                    class="bg-[#0f7ea0] text-white text-sm px-4 py-2 rounded hover:bg-[#0c6a86]">
                    + Add Pet Owners
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
            <div class="overflow-x-auto">
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
                                        <button onclick="openEditModal({{ json_encode($owner) }})"
                                            class="bg-[#0f7ea0] text-white px-2 py-1 rounded hover:bg-[#0c6a86] flex items-center gap-1 text-xs">
                                            <i class="fas fa-pen"></i> 
                                        </button>

                                        <button onclick="viewOwnerDetails(this)"
                                            class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 flex items-center gap-1 text-xs"
                                            data-name="{{ $owner->own_name }}" data-contact="{{ $owner->own_contactnum }}"
                                            data-location="{{ $owner->own_location }}">
                                            <i class="fas fa-eye"></i> 
                                        </button>


                                        <!-- Delete Button -->
                                        <form action="{{ route('owners.destroy', $owner->own_id) }}" method="POST"
                                            onsubmit="return confirm('Are you sure you want to delete this owner?');"
                                            class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 flex items-center gap-1 text-xs">
                                                <i class="fas fa-trash"></i> 
                                            </button>
                                        </form>

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

            <div class="flex justify-between items-center mt-4 text-sm font-semibold text-black">
                <div>Showing {{ $owners->firstItem() }} to {{ $owners->lastItem() }} of {{ $owners->total() }} entries</div>
                <div class="inline-flex border border-gray-400 rounded overflow-hidden">

                    @if ($owners->onFirstPage())
                        <button disabled class="px-3 py-1 text-gray-400 cursor-not-allowed border-r">Previous</button>
                    @else
                        <a href="{{ $owners->previousPageUrl() }}"
                            class="px-3 py-1 text-black hover:bg-gray-200 border-r">Previous</a>
                    @endif

                    @for ($i = 1; $i <= $owners->lastPage(); $i++)
                        @if ($i == $owners->currentPage())
                            <button class="px-3 py-1 bg-[#0f7ea0] text-white border-r">{{ $i }}</button>
                        @else
                            <a href="{{ $owners->url($i) }}" class="px-3 py-1 hover:bg-gray-200 border-r">{{ $i }}</a>
                        @endif
                    @endfor

                    @if ($owners->hasMorePages())
                        <a href="{{ $owners->nextPageUrl() }}" class="px-3 py-1 text-black hover:bg-gray-200">Next</a>
                    @else
                        <button disabled class="px-3 py-1 text-gray-400 cursor-not-allowed">Next</button>
                    @endif

                </div>
            </div>

            {{-- Pet Modal --}}
            <div id="petOwnerModal"
                class="fixed inset-0 bg-black bg-opacity-30 flex justify-center items-center hidden z-50">
                <div class="bg-white w-full max-w-lg p-6 rounded shadow-lg relative">
                    <form id="petOwnerForm" method="POST" action="{{ route('owners.store') }}">
                        @csrf
                        <input type="hidden" name="_method" id="formMethod" value="POST">
                        <input type="hidden" name="own_id" id="petOwner_id">

                        <div class="grid grid-cols-1 gap-4 text-sm">
                            <div>
                                <label class="block font-medium">Pet Owner's Name</label>
                                <input type="text" name="own_name" id="petOwner_name"
                                    class="w-full border rounded px-2 py-1" required>
                            </div>
                            <div>
                                <label class="block font-medium">Contact Number</label>
                                <input type="text" name="own_contactnum" id="petOwner_contact"
                                    class="w-full border rounded px-2 py-1" required>
                            </div>
                            <div>
                                <label class="block font-medium">Location</label>
                                <input type="text" name="own_location" id="petOwner_location"
                                    class="w-full border rounded px-2 py-1" required>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-2 mt-4">
                            <button type="button" class="px-4 py-1 bg-gray-300 rounded text-sm hover:bg-gray-400"
                                onclick="document.getElementById('petOwnerModal').classList.add('hidden')">Cancel</button>
                            <button type="submit"
                                class="px-4 py-1 bg-[#0f7ea0] text-white rounded text-sm hover:bg-[#0d6b85]">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- View Owner Modal --}}
        <div id="viewOwnerModal" class="fixed inset-0 bg-black bg-opacity-40 flex justify-center items-center z-50 hidden">
            <div class="bg-white w-full max-w-lg p-6 rounded-lg shadow-lg relative">
                <div class="flex justify-between items-center border-b pb-3">
                    <h2 class="text-xl font-semibold text-[#0f7ea0]">Owner Details</h2>
                    <button onclick="document.getElementById('viewOwnerModal').classList.add('hidden')"
                        class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
                </div>

                <div class="mt-4 space-y-3 text-sm">
                    <p><strong>Owner Name:</strong> <span id="viewOwnerName"></span></p>
                    <p><strong>Contact Number:</strong> <span id="viewOwnerContact"></span></p>
                    <p><strong>Location:</strong> <span id="viewOwnerLocation"></span></p>
                </div>

                <div class="flex justify-end mt-6">
                    <button type="button" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 rounded text-sm"
                        onclick="document.getElementById('viewOwnerModal').classList.add('hidden')">Close</button>
                </div>
            </div>
        </div>


        {{-- Modal Script --}}
        <script>

            function viewOwnerDetails(button) {
                document.getElementById('viewOwnerName').innerText = button.dataset.name;
                document.getElementById('viewOwnerContact').innerText = button.dataset.contact;
                document.getElementById('viewOwnerLocation').innerText = button.dataset.location;
                document.getElementById('viewOwnerModal').classList.remove('hidden');
            }
            function openAddModal() {
                const form = document.getElementById('petOwnerForm');
                form.reset();
                form.action = `{{ route('owners.store') }}`;
                document.getElementById('formMethod').value = 'POST';
                document.getElementById('petOwnerModal').classList.remove('hidden');
            }

            function openEditModal(owner) {
                const form = document.getElementById('petOwnerForm');
                form.action = `/owners/${owner.own_id}`;
                document.getElementById('formMethod').value = 'PUT';
                document.getElementById('petOwner_id').value = owner.own_id;
                document.getElementById('petOwner_name').value = owner.own_name;
                document.getElementById('petOwner_contact').value = owner.own_contactnum;
                document.getElementById('petOwner_location').value = owner.own_location;
                document.getElementById('petOwnerModal').classList.remove('hidden');
            }
        </script>
@endsection