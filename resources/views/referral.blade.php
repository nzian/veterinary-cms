@extends('AdminBoard')

@section('content')
    <div class="min-h-screen">
        <div class="max-w-7xl mx-auto bg-white p-6 rounded-lg shadow">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-[#0f7ea0] font-bold text-xl">Referrals</h2>
                <button onclick="openAddReferralModal()"
                    class="bg-[#0f7ea0] text-white text-sm px-4 py-2 rounded hover:bg-[#0c6a86]">+Add Referral</button>
            </div>

            <!-- Referral Table -->
            <table class="w-full table-auto text-sm border text-center">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border px-2 py-2">Referral ID</th>
                        <th class="border px-2 py-2">Date</th>
                        <th class="border px-2 py-2">Description</th>
                        <th class="border px-2 py-2">Appointment ID</th>
                        <th class="border px-2 py-2">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($referrals as $ref)
                        <tr>
                            <td class="border px-2 py-2">{{ $ref->ref_id }}</td>
                            <td class="border px-2 py-2">{{ $ref->ref_date }}</td>
                            <td class="border px-2 py-2">{{ $ref->ref_description }}</td>
                            <td class="border px-2 py-2">{{ $ref->appoint_id }}</td>
                            <td class="border px-2 py-1">
                                <div class="flex justify-center items-center gap-1">
                                <button
                                    class="editReferralBtn bg-[#0f7ea0] text-white px-2 py-1 rounded hover:bg-[#0c6a86] flex items-center gap-1 text-xs rounded "
                                    data-id="{{ $ref->ref_id }}" data-date="{{ $ref->ref_date }}"
                                    data-description="{{ $ref->ref_description }}" data-appoint_id="{{ $ref->appoint_id }}">
                                    <i class="fas fa-pen"></i>Edit
                                </button>
                                <form action="{{ route('referrals.destroy', $ref->ref_id) }}" method="POST"
                                    onsubmit="return confirm('Are you sure you want to delete this referral?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 flex items-center gap-1 text-xs">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="referralModal" class="fixed inset-0 hidden bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded shadow-md w-[400px]">
            <form id="referralForm" method="POST">
                @csrf
                <input type="hidden" name="ref_id" id="ref_id">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Referral Date</label>
                    <input type="date" name="ref_date" id="ref_date" class="w-full border px-3 py-2 rounded" required>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="ref_description" id="ref_description" rows="3" class="w-full border px-3 py-2 rounded"
                        required></textarea>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Appointment ID</label>
                    <input type="text" name="appoint_id" id="appoint_id" class="w-full border px-3 py-2 rounded" required>
                </div>

                <div class="flex justify-end">
                    <button type="button" onclick="closeReferralModal()" class="mr-2 text-gray-600">Cancel</button>
                    <button type="submit" class="bg-[#0f7ea0] text-white px-4 py-2 rounded">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddReferralModal() {
            document.getElementById('referralForm').reset();
            document.getElementById('ref_id').value = '';
            document.getElementById('referralModal').classList.remove('hidden');
        }

        function closeReferralModal() {
            document.getElementById('referralModal').classList.add('hidden');
        }

        document.querySelectorAll('.editReferralBtn').forEach(button => {
            button.addEventListener('click', () => {
                document.getElementById('ref_id').value = button.getAttribute('data-id');
                document.getElementById('ref_date').value = button.getAttribute('data-date');
                document.getElementById('ref_description').value = button.getAttribute('data-description');
                document.getElementById('appoint_id').value = button.getAttribute('data-appoint_id');
                document.getElementById('referralModal').classList.remove('hidden');
            });
        });
    </script>
@endsection