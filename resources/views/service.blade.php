@extends('AdminBoard')

@section('content')
<div class="min-h-screen">
    <div class="max-w-7xl mx-auto bg-white p-6 rounded-lg shadow">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-[#0f7ea0] font-bold text-lg">Services</h2>
            <button onclick="openAddModal()"
                class="bg-[#0f7ea0] text-white text-sm px-4 py-2 rounded hover:bg-[#0c6a86]">
                + Add Service
            </button>
        </div>

        <!-- Success Message -->
        @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-4 text-sm">
            {{ session('success') }}
        </div>
        @endif

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full table-auto text-sm border text-center">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2">Service Name</th>
                        <th class="px-4 py-2">Service Type</th>
                        <th class="px-4 py-2">Description</th>
                        <th class="px-4 py-2">Price</th>
                        <th class="px-4 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($services as $service)
                    <tr class="border-b">
                        <td class="px-4 py-2">{{ $service->serv_name }}</td>
                        <td class="px-4 py-2">{{ $service->serv_type }}</td>
                        <td class="px-4 py-2">{{ $service->serv_description }}</td>
                        <td class="px-4 py-2">₱{{ number_format($service->serv_price, 2) }}</td>
                       <td class="border px-2 py-1">
                            <div class="flex gap-2">
                                <button onclick='editService(@json($service))'
                                    class="bg-[#0f7ea0] text-white px-2 py-1 rounded hover:bg-[#0c6a86] flex items-center gap-1 text-xs">
                                    <i class="fas fa-pen"></i> Edit
                                </button>

                                <form action="{{ route('services.destroy', $service->serv_id) }}" method="POST"
                                    onsubmit="return confirm('Delete this service?')">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        class="bg-red-500 text-white px-2 py-1 rounded text-xs hover:bg-red-600">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach

                    @if($services->isEmpty())
                    <tr>
                        <td colspan="6" class="px-4 py-4 text-center text-gray-500">No services found.</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div id="serviceModal"
    class="fixed inset-0 bg-black bg-opacity-30 hidden justify-center items-center z-50">
    <div class="bg-white w-full max-w-md rounded-lg p-6">
        <h2 class="text-lg font-semibold mb-4" id="modalTitle">Add Service</h2>
        <form id="serviceForm" method="POST">
            @csrf
            <input type="hidden" name="_method" value="POST" id="formMethod">
            <input type="hidden" name="serv_id" id="serv_id">

            <div class="flex gap-4 mb-4">
                <!-- Service Name -->
                <div class="w-1/2">
                    <label for="serv_name" class="block text-sm font-medium text-gray-700 mb-1">Service Name</label>
                    <input type="text" name="serv_name" id="serv_name"
                        class="w-full border px-3 py-1 rounded" required>
                </div>

                <!-- Type -->
                <div class="w-1/2">
                    <label for="serv_type" class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <input type="text" name="serv_type" id="serv_type"
                        class="w-full border px-3 py-1 rounded" required>
                </div>
            </div>

            <!-- Description -->
            <div class="mb-4">
                <label for="serv_description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="serv_description" id="serv_description"
                    class="w-full border px-3 py-1 rounded" required></textarea>
            </div>

            <!-- Price -->
            <div class="mb-4">
                <label for="serv_price" class="block text-sm font-medium text-gray-700 mb-1">Price (₱)</label>
                <input type="number" name="serv_price" id="serv_price" step="0.01"
                    class="w-full border px-3 py-1 rounded" required>
            </div>

            <!-- Branch Selection -->
            <div class="mb-4">
                <label for="branch_id" class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
                <select name="branch_id" id="branch_id" class="w-full border px-3 py-1 rounded" required>
                    <option value="">Select Branch</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->branch_id }}">{{ $branch->branch_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeModal()"
                    class="bg-gray-400 text-white px-4 py-1 rounded hover:bg-gray-500">Cancel</button>
                <button type="submit"
                    class="bg-[#0f7ea0] text-white px-4 py-1 rounded hover:bg-[#0d6b85]">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Scripts -->
<script>
    function openAddModal() {
        document.getElementById('modalTitle').innerText = 'Add Service';
        document.getElementById('formMethod').value = 'POST';
        document.getElementById('serviceForm').action = "{{ route('services.store') }}";
        document.getElementById('serv_id').value = '';
        document.getElementById('serv_name').value = '';
        document.getElementById('serv_type').value = '';
        document.getElementById('serv_description').value = '';
        document.getElementById('serv_price').value = '';
        document.getElementById('branch_id').value = '';
        document.getElementById('serviceModal').classList.remove('hidden');
        document.getElementById('serviceModal').classList.add('flex');
    }

    function editService(service) {
        document.getElementById('modalTitle').innerText = 'Edit Service';
        document.getElementById('formMethod').value = 'PUT';
        document.getElementById('serviceForm').action = '/services/' + service.serv_id;
        document.getElementById('serv_id').value = service.serv_id;
        document.getElementById('serv_name').value = service.serv_name;
        document.getElementById('serv_type').value = service.serv_type;
        document.getElementById('serv_description').value = service.serv_description;
        document.getElementById('serv_price').value = service.serv_price;
        document.getElementById('branch_id').value = service.branch_id;
        document.getElementById('serviceModal').classList.remove('hidden');
        document.getElementById('serviceModal').classList.add('flex');
    }

    function closeModal() {
        document.getElementById('serviceModal').classList.add('hidden');
        document.getElementById('serviceModal').classList.remove('flex');
    }
</script>
@endsection