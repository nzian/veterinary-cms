@extends('AdminBoard')

@section('content')
    <div class="min-h-screen">
        <div class="max-w-7xl mx-auto bg-white p-6 rounded-lg shadow">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-[#0f7ea0] font-bold text-lg">User Management</h2>

                <button onclick="openAddUserModal()"
                    class="bg-[#0f7ea0] text-white text-sm px-4 py-2 rounded hover:bg-[#0c6a86]">
                    + Add User
                </button>
            </div>

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-4 text-sm">
                    {{ session('success') }}
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
                            <th class="border px-2 py-2">Name</th>
                            <th class="border px-2 py-2">Email</th>
                            <th class="border px-2 py-2">Role</th>
                            <th class="border px-2 py-2">Branch</th>
                            <th class="border px-2 py-2">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $index => $user)
                            <tr class="hover:bg-gray-50">
                                <td class="border px-2 py-2">{{ $index + 1 }}</td>
                                <td class="border px-2 py-2">{{ $user->user_name }}</td>
                                <td class="border px-2 py-2">{{ $user->user_email }}</td>
                                <td class="border px-2 py-2 capitalize">{{ $user->user_role }}</td>
                                <td class="border px-2 py-2">{{ $user->branch->branch_name ?? 'All Branches'}}</td>
                                <td class="border px-2 py-1">
                                    <div class="flex justify-center items-center gap-1">
                                        <button
                                            class="editUserBtn bg-[#0f7ea0] text-white px-2 py-1 rounded hover:bg-[#0c6a86] flex items-center gap-1 text-xs"
                                            data-id="{{ $user->user_id }}" data-name="{{ $user->user_name }}"
                                            data-email="{{ $user->user_email }}" data-role="{{ $user->user_role }}"
                                            data-branch="{{ $user->branch_id }}">
                                            <i class="fas fa-pen"></i> 
                                        </button>
                                        <form action="{{ route('userManagement.destroy', $user->user_id) }}" method="POST"
                                            onsubmit="return confirm('Delete this user?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="bg-[#f44336] text-white px-2 py-1 rounded hover:bg-red-600 flex items-center gap-1 text-xs">
                                                <i class="fas fa-trash"></i> 
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-gray-500 py-4">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- User Modal --}}
        <div id="userModal" class="fixed inset-0 bg-black bg-opacity-30 flex justify-center items-center hidden z-50">
            <div class="bg-white w-full max-w-lg p-6 rounded shadow-lg relative">
                <form action="{{ route('userManagement.store') }}" method="POST">
                    @csrf


                    <div class="mb-3">
                        <label class="block text-sm font-medium mb-1">Full Name</label>
                        <input type="text" name="user_name" required class="w-full border rounded px-2 py-1 text-sm">
                    </div>

                    <div class="mb-3">
                        <label class="block text-sm font-medium mb-1">Email</label>
                        <input type="email" name="user_email" required class="w-full border rounded px-2 py-1 text-sm">
                    </div>

                    <div class="mb-3">
                        <label class="block text-sm font-medium mb-1">Password</label>
                        <input type="password" name="user_password" required class="w-full border rounded px-2 py-1 text-sm">
                    </div>

                    <div class="mb-3">
                        <label class="block text-sm font-medium mb-1">Confirm Password</label>
                        <input type="password" name="user_password_confirmation" required
                            class="w-full border rounded px-2 py-1 text-sm">
                    </div>

                    <div class="mb-3">
                        <label class="block text-sm font-medium mb-1">Role</label>
                        <select name="user_role" required class="w-full border rounded px-2 py-1 text-sm">
                            <option value="">Select Role</option>
                            <option value="veterinarian">Veterinarian</option>
                            <option value="receptionist">Receptionist</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium mb-1">Branch</label>
                        <select name="branch_id" id="branch_id" required class="w-full border rounded px-2 py-1 text-sm">
                            <option value="">Select Branch</option>
                            @forelse($branches as $branch)
                                <option value="{{ $branch->branch_id }}">{{ $branch->branch_name }}</option>
                            @empty
                                <option disabled>No branches found</option>
                            @endforelse
                        </select>
                    </div>

                    <div class="flex justify-end space-x-2 mt-4">
                        <button type="button" class="px-4 py-1 bg-gray-300 rounded text-sm hover:bg-gray-400"
                            onclick="closeUserModal()">Cancel</button>
                        <button type="submit"
                            class="px-4 py-1 bg-[#0f7ea0] text-white rounded text-sm hover:bg-[#0d6b85]">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit User Modal --}}
    <div id="editUserModal" class="fixed inset-0 bg-black bg-opacity-30 flex justify-center items-center hidden z-50">
        <div class="bg-white w-full max-w-lg p-6 rounded shadow-lg relative">
           <form id="editUserForm" method="POST">
    @csrf
    @method('PUT')

    <input type="hidden" name="user_id" id="edit_user_id">

    <div class="mb-3">
        <label class="block text-sm font-medium mb-1">Full Name</label>
        <input type="text" name="user_name" id="edit_user_name" required
            class="w-full border rounded px-2 py-1 text-sm">
    </div>

    <div class="mb-3">
        <label class="block text-sm font-medium mb-1">Email</label>
        <input type="email" name="user_email" id="edit_user_email" required
            class="w-full border rounded px-2 py-1 text-sm">
    </div>

    <div class="mb-3">
        <label class="block text-sm font-medium mb-1">Password (leave blank to keep current)</label>
        <input type="password" name="user_password" id="edit_user_password"
            class="w-full border rounded px-2 py-1 text-sm">
    </div>

    <div class="mb-3">
        <label class="block text-sm font-medium mb-1">Confirm Password</label>
        <input type="password" name="user_password_confirmation" id="edit_user_password_confirmation"
            class="w-full border rounded px-2 py-1 text-sm">
    </div>

    <div class="mb-3">
        <label class="block text-sm font-medium mb-1">Role</label>
        <select name="user_role" id="edit_user_role" required class="w-full border rounded px-2 py-1 text-sm">
            <option value="">Select Role</option>
            <option value="veterinarian">Veterinarian</option>
            <option value="receptionist">Receptionist</option>
        </select>
    </div>

    <div class="mb-3">
        <label class="block text-sm font-medium mb-1">Branch</label>
        <select name="branch_id" id="edit_user_branch" required class="w-full border rounded px-2 py-1 text-sm">
            <option value="">Select Branch</option>
            @foreach($branches as $branch)
                <option value="{{ $branch->branch_id }}">{{ $branch->branch_name }}</option>
            @endforeach
        </select>
    </div>

    <div class="flex justify-end space-x-2 mt-4">
        <button type="button" class="px-4 py-1 bg-gray-300 rounded text-sm hover:bg-gray-400"
            onclick="closeEditUserModal()">Cancel</button>
        <button type="submit" class="px-4 py-1 bg-[#0f7ea0] text-white rounded text-sm hover:bg-[#0d6b85]">
            Save
        </button>
    </div>
</form>

        </div>
    </div>


    {{-- Modal Script --}}
    <script>
        document.querySelectorAll('.editUserBtn').forEach(button => {
    button.addEventListener('click', function () {
        const userId = this.dataset.id;
        const user_name = this.dataset.name;
        const user_email = this.dataset.email;
        const user_role = this.dataset.role;
        const branch = this.dataset.branch;

        // Fill form
        document.getElementById('edit_user_id').value = userId;
        document.getElementById('edit_user_name').value = user_name;
        document.getElementById('edit_user_email').value = user_email;
        document.getElementById('edit_user_role').value = user_role;
        document.getElementById('edit_user_branch').value = branch;

        // Reset password fields (so they’re blank when modal opens)
        document.getElementById('edit_user_password').value = "";
        document.getElementById('edit_user_password_confirmation').value = "";

        // Set dynamic action
        document.getElementById('editUserForm').action = "{{ url('user-management') }}/" + userId;

        // Show modal
        document.getElementById('editUserModal').classList.remove('hidden');
    });
});

function closeEditUserModal() {
    document.getElementById('editUserModal').classList.add('hidden');
}

        function closeEditUserModal() {
            document.getElementById('editUserModal').classList.add('hidden');
        }
        function openAddUserModal() {
            document.getElementById('userModal').classList.remove('hidden');
        }

        function closeUserModal() {
            document.getElementById('userModal').classList.add('hidden');
        }
    </script>
@endsection