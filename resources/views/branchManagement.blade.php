@extends('AdminBoard')

@section('content')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize client-side filtering after page load
        setTimeout(() => {
            if (typeof ListFilter !== 'undefined') {
                window.listFilters = window.listFilters || {};
                
                // Branches filter
                const branchesTable = document.querySelector('#branchesTable tbody');
                if (branchesTable && branchesTable.querySelectorAll('tr').length > 0) {
                    window.listFilters['branches'] = new ListFilter({
                        tableSelector: '#branchesTable tbody',
                        searchInputId: 'branchesSearch',
                        perPageSelectId: 'branchesPerPage', 
                        paginationContainerId: 'branchesPagination',
                        searchColumns: [0, 1, 2], // Name, Address, Contact
                        storageKey: 'branchesFilter',
                        noResultsMessage: 'No branches found.'
                    });
                }
                
                // Users filter  
                const usersTable = document.querySelector('#usersTable tbody');
                if (usersTable && usersTable.querySelectorAll('tr').length > 0) {
                    window.listFilters['users'] = new ListFilter({
                        tableSelector: '#usersTable tbody',
                        searchInputId: 'usersSearch',
                        perPageSelectId: 'usersPerPage',
                        paginationContainerId: 'usersPagination', 
                        searchColumns: [0, 1, 2, 3, 4], // Name, Email, Contact, Role, Branch
                        filterSelects: [
                            { selectId: 'usersRole', columnIndex: 3 }
                        ],
                        storageKey: 'usersFilter',
                        noResultsMessage: 'No users found.'
                    });
                }
            }
        }, 100);
    });
</script>
<div class="min-h-screen">
    <div class="max-w-7xl mx-auto bg-white p-6 rounded-lg shadow">
        {{-- Tab Navigation --}}
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8">
                <button id="branchTab" onclick="switchTab('branch')" 
                    class="tab-button active py-2 px-1 border-b-2 border-[#ff8c42] font-medium text-sm text-[#ff8c42]">
                   <h2 class="font-bold text-xl">Branches</h2>
                </button>
                <button id="userTab" onclick="switchTab('user')" 
                    class="tab-button py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                     <h2 class="font-bold text-xl">Users</h2>
                </button>
               
            </nav>
        </div>

        {{-- Success Message --}}
        @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-4 text-sm">
            {{ session('success') }}
        </div>
        @endif

        {{-- Branch Tab Content --}}
        <div id="branchContent" class="tab-content">
            <div class="flex justify-between items-center mt-4 text-sm font-semibold text-black gap-2 flex-wrap">
                <form method="GET" action="{{ request()->url() }}" class="flex items-center space-x-2">
                    <label for="branchPerPage" class="text-sm text-black">Show</label>
                    <select name="perPage" id="branchPerPage" onchange="this.form.submit()"
                        class="border border-gray-400 rounded px-2 py-1 text-sm">
                        @foreach ([10, 20, 50, 100, 'all'] as $limit)
                        <option value="{{ $limit }}" {{ request('perPage') == $limit ? 'selected' : '' }}>
                            {{ $limit === 'all' ? 'All' : $limit }}
                        </option>
                        @endforeach
                    </select>
                    <span>entries</span>
                </form>
                <div class="flex items-center gap-2 flex-wrap">
                    <div class="relative">
                        <input type="search" id="branchesSearch" placeholder="Search branches..." class="border border-gray-300 rounded px-3 py-1.5 text-sm pl-8">
                        <i class="fas fa-search absolute left-2 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                    <button onclick="openAddModal()" class="bg-[#0f7ea0] text-white text-sm px-4 py-2 rounded hover:bg-[#0c6a86]">
                        + Add Branch
                    </button>
                </div>
            </div>
            <br>

            <div class="overflow-x-auto">
                <table id="branchesTable" class="w-full table-auto text-sm border text-center">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-2 py-1">#</th>
                            <th class="border px-2 py-1">Branch Name</th>
                            <th class="border px-2 py-1">Address</th>
                            <th class="border px-2 py-1">Contact</th>
                            <th class="border px-2 py-1">User Count</th>
                            <th class="border px-2 py-1">Action</th>
                        </tr>
                    </thead>
                    <tbody id="branchTableBody">
                        @foreach($branches as $index => $branch)
                        <tr>
                            <td class="border px-2 py-1">{{ $index + 1 }}</td>
                            <td class="border px-2 py-1">{{ $branch->branch_name }}</td>
                            <td class="border px-2 py-1">{{ $branch->branch_address }}</td>
                            <td class="border px-2 py-1">{{ $branch->branch_contactNum }}</td>
                            <td class="border px-2 py-1">
                                @if($branch->users && $branch->users->count() > 0)
                                    <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full font-medium">
                                        {{ $branch->users->count() }} user{{ $branch->users->count() > 1 ? 's' : '' }}
                                    </span>
                                @else
                                    <span class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-full">0 users</span>
                                @endif
                            </td>
                            <td class="border px-2 py-1">
                                <div class="flex justify-center items-center gap-1">
                                    <button
                                        class="editBranchBtn bg-[#0f7ea0] text-white px-2 py-1 rounded hover:bg-[#0c6a86] flex items-center gap-1 text-xs"
                                        data-id="{{ $branch->branch_id }}" data-name="{{ $branch->branch_name }}"
                                        data-address="{{ $branch->branch_address }}" data-contact="{{ $branch->branch_contactNum }}" title="Edit">
                                        <i class="fas fa-pen"></i> 
                                    </button>
                                    <button
                                        class="addUserToBranchBtn bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600 flex items-center gap-1 text-xs"
                                        data-branch-id="{{ $branch->branch_id }}" data-branch-name="{{ $branch->branch_name }}"title="Add User">
                                        <i class="fas fa-user-plus"></i> 
                                    </button>
                                    <button onclick='openViewModal(@json($branch))'
                                        class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 flex items-center gap-1 text-xs" title="View">
                                        <i class="fas fa-eye"></i> 
                                    </button>
                                    <form action="{{ route('branches-destroy', $branch->branch_id) }}" method="POST"
                                        onsubmit="return confirm('Delete this branch?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="bg-[#f44336] text-white px-2 py-1 rounded hover:bg-red-600 flex items-center gap-1 text-xs" title="Delete">
                                            <i class="fas fa-trash"></i> 
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div id="branchesPagination" class="mt-4"></div>
        </div>

        {{-- User Tab Content --}}
        <div id="userContent" class="tab-content hidden">
            <div class="flex justify-between items-center mt-4 text-sm font-semibold text-black gap-2 flex-wrap">
                <form method="GET" action="{{ request()->url() }}" class="flex items-center space-x-2">
                    <label for="userPerPage" class="text-sm text-black">Show</label>
                    <select name="perPage" id="userPerPage" onchange="this.form.submit()"
                        class="border border-gray-400 rounded px-2 py-1 text-sm">
                        @foreach ([10, 20, 50, 100, 'all'] as $limit)
                        <option value="{{ $limit }}" {{ request('perPage') == $limit ? 'selected' : '' }}>
                            {{ $limit === 'all' ? 'All' : $limit }}
                        </option>
                        @endforeach
                    </select>
                    <span>entries</span>
                </form>
                 <select id="usersRole" class="border border-gray-400 rounded px-2 py-1 text-sm">
                        <option value="All">All Roles</option>
                        <option value="veterinarian">Veterinarian</option>
                        <option value="receptionist">Receptionist</option>
                        <option value="superadmin">Super Admin</option>
                    </select>
                <div class="flex items-center gap-2 flex-wrap">
                    <div class="relative">
                        <input type="search" id="usersSearch" placeholder="Search users..." class="border border-gray-300 rounded px-3 py-1.5 text-sm pl-8">
                        <i class="fas fa-search absolute left-2 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                   
                </div>
            </div>
            <br>

            <div class="overflow-x-auto">
                <table id="usersTable" class="w-full table-auto text-sm border text-center">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-2 py-2">#</th>
                            <th class="border px-2 py-2">Name</th>
                            <th class="border px-2 py-2">Email</th>
                         
                            <th class="border px-2 py-2">Role</th>
                          
                            <th class="border px-2 py-2">Branch</th>
                            <th class="border px-2 py-2">Status</th>
                            <th class="border px-2 py-2">Last Login</th>
                            <th class="border px-2 py-2">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $index => $user)
                        @php
                            $isActive = $user->last_login_at && \Carbon\Carbon::parse($user->last_login_at)->gt(now()->subWeek());
                            $statusClass = $isActive ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                            $statusText = $isActive ? 'Active' : 'Inactive';
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="border px-2 py-2">{{ $index + 1 }}</td>
                            <td class="border px-2 py-2">{{ $user->user_name }}</td>
                            <td class="border px-2 py-2">{{ $user->user_email }}</td>
                          
                            <td class="border px-2 py-2 capitalize">{{ $user->user_role }}</td>
                           
                            <td class="border px-2 py-2">{{ $user->branch->branch_name ?? 'All Branches'}}</td>
                            <td class="border px-2 py-2">
                                <span class="text-xs px-2 py-1 rounded-full font-medium {{ $statusClass }}">
                                    {{ $statusText }}
                                </span>
                            </td>
                            <td class="border px-2 py-2 text-xs">
                                @if($user->last_login_at)
                                    {{ \Carbon\Carbon::parse($user->last_login_at)->format('M d, Y') }}
                                    <br>
                                    <span class="text-gray-500">{{ \Carbon\Carbon::parse($user->last_login_at)->diffForHumans() }}</span>
                                @else
                                    <span class="text-gray-500">Never logged in</span>
                                @endif
                            </td>
                            <td class="border px-2 py-1">
                                <div class="flex justify-center items-center gap-1">
                                    <button
                                        class="editUserBtn bg-[#0f7ea0] text-white px-2 py-1 rounded hover:bg-[#0c6a86] flex items-center gap-1 text-xs"
                                        data-id="{{ $user->user_id }}" data-name="{{ $user->user_name }}"
                                        data-email="{{ $user->user_email }}" data-contact="{{ $user->user_contactNum }}"
                                        data-role="{{ $user->user_role }}" data-license="{{ $user->user_licenseNum }}"
                                        data-branch="{{ $user->branch_id }}"title="Edit">
                                        <i class="fas fa-pen"></i> 
                                    </button>
                                    <form action="{{ route('userManagement.destroy', $user->user_id) }}" method="POST"
                                        onsubmit="return confirm('Delete this user?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="bg-[#f44336] text-white px-2 py-1 rounded hover:bg-red-600 flex items-center gap-1 text-xs"title="Delete">
                                            <i class="fas fa-trash"></i> 
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center text-gray-500 py-4">No users found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div id="usersPagination" class="flex justify-between items-center mt-4">
                <!-- Pagination will be generated by JavaScript -->
            </div>
        </div>
        
            
    </div>
</div>

{{-- Branch Modals --}}
<!-- Add Branch Modal -->
<div id="branchModal" class="fixed inset-0 flex justify-center items-center hidden z-50">
    <div class="bg-white w-full max-w-lg p-6 rounded shadow-lg relative">
        <h3 class="text-lg font-semibold text-[#0f7ea0] mb-4">Add New Branch</h3>
        <form action="{{ route('branches.store') }}" method="POST">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Name</label>
                <input type="text" name="name" required class="w-full border rounded p-2" />
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Address</label>
                <input type="text" name="address" required class="w-full border rounded p-2" />
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Contact</label>
                <input type="text" name="contact" required class="w-full border rounded p-2" />
            </div>

            <div class="flex justify-end space-x-2 mt-4">
                <button type="button" onclick="closeBranchModal()"
                    class="px-4 py-1 bg-gray-300 rounded text-sm hover:bg-gray-400">Cancel</button>
                <button type="submit" class="px-4 py-1 bg-[#0f7ea0] text-white rounded text-sm hover:bg-[#0d6b85]">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Branch Modal -->
<div id="editBranchModal" class="fixed inset-0 flex justify-center items-center hidden z-50">
    <div class="bg-white p-6 rounded shadow w-full max-w-md relative">
        <h3 class="text-lg font-semibold text-[#0f7ea0] mb-4">Edit Branch</h3>
        <form id="editBranchForm" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="branch_id" id="edit_branch_id">
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700">Name:</label>
                <input type="text" name="name" id="edit_name" class="w-full border p-2 rounded">
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700">Address:</label>
                <input type="text" name="address" id="edit_address" class="w-full border p-2 rounded">
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700">Contact:</label>
                <input type="text" name="contact" id="edit_contact" class="w-full border p-2 rounded">
            </div>
            <div class="flex justify-end space-x-2 mt-4">
                <button type="button" class="px-4 py-1 bg-gray-300 rounded text-sm hover:bg-gray-400"
                    onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="px-4 py-1 bg-[#0f7ea0] text-white rounded text-sm hover:bg-[#0d6b85]">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Enhanced View Branch Modal with Users -->
<div id="viewBranchModal" class="fixed inset-0 flex justify-center items-center hidden z-50">
    <div class="bg-white p-6 rounded shadow w-full max-w-6xl max-h-[90vh] overflow-y-auto relative">
        <h2 class="text-lg font-semibold text-[#0f7ea0] mb-4">Branch Complete Overview</h2>
        
        <!-- Branch Information Section -->
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <h3 class="text-md font-semibold text-gray-800 mb-3">Branch Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <p class="text-gray-600">Name:</p>
                    <p class="font-medium" id="view_branch_name"></p>
                </div>
                <div>
                    <p class="text-gray-600">Address:</p>
                    <p class="font-medium" id="view_branch_address"></p>
                </div>
                <div>
                    <p class="text-gray-600">Contact:</p>
                    <p class="font-medium" id="view_branch_contact"></p>
                </div>
            </div>
        </div>

        <!-- Tabs for different sections -->
        <div class="border-b border-gray-200 mb-4">
            <nav class="-mb-px flex space-x-8">
                <button onclick="switchBranchTab('users')" id="branchUsersTabBtn" 
                    class="branch-tab-button active py-2 px-1 border-b-2 border-[#0f7ea0] font-medium text-sm text-[#0f7ea0]">
                    Users <span id="users_count_badge" class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full ml-2"></span>
                </button>
                <button onclick="switchBranchTab('products')" id="branchProductsTabBtn"
                    class="branch-tab-button py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Products <span id="products_count_badge" class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full ml-2"></span>
                </button>
                <button onclick="switchBranchTab('services')" id="branchServicesTabBtn"
                    class="branch-tab-button py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Services <span id="services_count_badge" class="bg-purple-100 text-purple-800 text-xs px-2 py-1 rounded-full ml-2"></span>
                </button>
                <button onclick="switchBranchTab('equipment')" id="branchEquipmentTabBtn"
                    class="branch-tab-button py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Equipment <span id="equipment_count_badge" class="bg-orange-100 text-orange-800 text-xs px-2 py-1 rounded-full ml-2"></span>
                </button>
            </nav>
        </div>

        <!-- Users Tab Content -->
        <div id="branch-users-content" class="branch-tab-content">
            <div class="overflow-x-auto max-h-96">
                <table class="w-full table-auto text-sm border text-center">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-2 py-2">#</th>
                            <th class="border px-2 py-2">Name</th>
                            <th class="border px-2 py-2">Email</th>
                            <th class="border px-2 py-2">Role</th>
                            <th class="border px-2 py-2">Status</th>
                            <th class="border px-2 py-2">Last Login</th>
                        </tr>
                    </thead>
                    <tbody id="view_branch_users_table">
                        <!-- Users will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Products Tab Content -->
        <div id="branch-products-content" class="branch-tab-content hidden">
            <div class="overflow-x-auto max-h-96">
                <table class="w-full table-auto text-sm border text-center">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-2 py-2">Image</th>
                            <th class="border px-2 py-2">Name</th>
                            <th class="border px-2 py-2">Category</th>
                            <th class="border px-2 py-2">Price</th>
                            <th class="border px-2 py-2">Stock</th>
                            <th class="border px-2 py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody id="view_branch_products_table">
                        <!-- Products will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Services Tab Content -->
        <div id="branch-services-content" class="branch-tab-content hidden">
            <div class="overflow-x-auto max-h-96">
                <table class="w-full table-auto text-sm border text-center">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-2 py-2">Name</th>
                            <th class="border px-2 py-2">Type</th>
                            <th class="border px-2 py-2">Description</th>
                            <th class="border px-2 py-2">Price</th>
                        </tr>
                    </thead>
                    <tbody id="view_branch_services_table">
                        <!-- Services will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Equipment Tab Content -->
        <div id="branch-equipment-content" class="branch-tab-content hidden">
            <div class="overflow-x-auto max-h-96">
                <table class="w-full table-auto text-sm border text-center">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-2 py-2">Image</th>
                            <th class="border px-2 py-2">Name</th>
                            <th class="border px-2 py-2">Category</th>
                            <th class="border px-2 py-2">Quantity</th>
                            <th class="border px-2 py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody id="view_branch_equipment_table">
                        <!-- Equipment will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="flex justify-end mt-6 pt-4 border-t">
            <button onclick="closeViewModal()" class="px-4 py-2 bg-gray-300 rounded text-sm hover:bg-gray-400">Close</button>
        </div>
    </div>
</div>

{{-- User Modals --}}
<!-- Add User Modal (From User Tab) -->
<div id="userModal" class="fixed inset-0 flex justify-center items-center hidden z-50">
    <div class="bg-white w-full max-w-lg p-6 rounded shadow-lg relative">
        <h3 class="text-lg font-semibold text-[#0f7ea0] mb-4">Add New User</h3>
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
                <label class="block text-sm font-medium mb-1">Contact Number</label>
                <input type="text" name="user_contactNum" required class="w-full border rounded px-2 py-1 text-sm">
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
                <select name="user_role" required class="w-full border rounded px-2 py-1 text-sm" onchange="toggleLicenseField(this, 'license_field')">
                    <option value="">Select Role</option>
                    <option value="veterinarian">Veterinarian</option>
                    <option value="receptionist">Receptionist</option>
                </select>
            </div>

            <div class="mb-3 hidden" id="license_field">
                <label class="block text-sm font-medium mb-1">License Number</label>
                <input type="text" name="user_licenseNum" class="w-full border rounded px-2 py-1 text-sm"
                    maxlength="7" pattern="[0-9]*" inputmode="numeric"
                    oninput="this.value = this.value.replace(/[^0-9]/g, '')" 
                    placeholder="7 digits only">
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

<!-- Add User to Branch Modal (From Branch Tab) -->
<div id="addUserToBranchModal" class="fixed inset-0 flex justify-center items-center hidden z-50">
    <div class="bg-white w-full max-w-lg p-6 rounded shadow-lg relative">
        <h3 class="text-lg font-semibold text-[#0f7ea0] mb-4">Add User to <span id="selected_branch_name"></span></h3>
        <form action="{{ route('userManagement.addToBranch') }}" method="POST">
            @csrf
            <input type="hidden" name="branch_id" id="selected_branch_id">

            <div class="mb-3">
                <label class="block text-sm font-medium mb-1">Full Name</label>
                <input type="text" name="user_name" required class="w-full border rounded px-2 py-1 text-sm">
            </div>

            <div class="mb-3">
                <label class="block text-sm font-medium mb-1">Email</label>
                <input type="email" name="user_email" required class="w-full border rounded px-2 py-1 text-sm">
            </div>

            <div class="mb-3">
                <label class="block text-sm font-medium mb-1">Contact Number</label>
                <input type="text" name="user_contactNum" required class="w-full border rounded px-2 py-1 text-sm"
                    maxlength="11" pattern="[0-9]*" inputmode="numeric"
                    oninput="this.value = this.value.replace(/[^0-9]/g, '')" 
                    placeholder="Numbers only (max 11 digits)">
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
                <select name="user_role" required class="w-full border rounded px-2 py-1 text-sm" onchange="toggleLicenseField(this, 'license_field_branch')">
                    <option value="">Select Role</option>
                    <option value="veterinarian">Veterinarian</option>
                    <option value="receptionist">Receptionist</option>
                </select>
            </div>

            <div class="mb-3 hidden" id="license_field_branch">
                <label class="block text-sm font-medium mb-1">License Number</label>
                <input type="text" name="user_licenseNum" class="w-full border rounded px-2 py-1 text-sm"
                    maxlength="7" pattern="[0-9]*" inputmode="numeric"
                    oninput="this.value = this.value.replace(/[^0-9]/g, '')" 
                    placeholder="7 digits only">
            </div>

            <div class="flex justify-end space-x-2 mt-4">
                <button type="button" class="px-4 py-1 bg-gray-300 rounded text-sm hover:bg-gray-400"
                    onclick="closeAddUserToBranchModal()">Cancel</button>
                <button type="submit"
                    class="px-4 py-1 bg-[#0f7ea0] text-white rounded text-sm hover:bg-[#0d6b85]">Add User</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="fixed inset-0 flex justify-center items-center hidden z-50">
    <div class="bg-white w-full max-w-lg p-6 rounded shadow-lg relative">
        <h3 class="text-lg font-semibold text-[#0f7ea0] mb-4">Edit User</h3>
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
                <label class="block text-sm font-medium mb-1">Contact Number</label>
                <input type="text" name="user_contactNum" id="edit_user_contact" required
                    class="w-full border rounded px-2 py-1 text-sm"
                    maxlength="11" pattern="[0-9]*" inputmode="numeric"
                    oninput="this.value = this.value.replace(/[^0-9]/g, '')" 
                    placeholder="Numbers only (max 11 digits)">
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
                <select name="user_role" id="edit_user_role" required class="w-full border rounded px-2 py-1 text-sm" onchange="toggleLicenseField(this, 'edit_license_field')">
                    <option value="">Select Role</option>
                    <option value="veterinarian">Veterinarian</option>
                    <option value="receptionist">Receptionist</option>
                </select>
            </div>

            <div class="mb-3 hidden" id="edit_license_field">
                <label class="block text-sm font-medium mb-1">License Number</label>
                <input type="text" name="user_licenseNum" id="edit_user_license" class="w-full border rounded px-2 py-1 text-sm"
                    maxlength="7" pattern="[0-9]*" inputmode="numeric"
                    oninput="this.value = this.value.replace(/[^0-9]/g, '')" 
                    placeholder="7 digits only">
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




<script>
// Tab switching functionality
function switchTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Remove active class from all tab buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active', 'border-[#0f7ea0]', 'text-[#0f7ea0]');
        button.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Show selected tab content
    document.getElementById(tabName + 'Content').classList.remove('hidden');
    
    // Add active class to selected tab button
    const activeTab = document.getElementById(tabName + 'Tab');
    activeTab.classList.add('active', 'border-[#0f7ea0]', 'text-[#0f7ea0]');
    activeTab.classList.remove('border-transparent', 'text-gray-500');
}

// Function to toggle license field based on role selection
function toggleLicenseField(selectElement, fieldId) {
    const licenseField = document.getElementById(fieldId);
    const licenseInput = licenseField.querySelector('input');
    
    if (selectElement.value === 'veterinarian') {
        licenseField.classList.remove('hidden');
        licenseInput.required = true;
    } else {
        licenseField.classList.add('hidden');
        licenseInput.required = false;
        licenseInput.value = '';
    }
}

// Branch Functions
function openViewModal(branch) {
    // Debug: Check if users data is available
    console.log('Branch data:', branch);
    console.log('Users data:', branch.users);
    
    // Set branch information
    document.getElementById('view_branch_name').innerText = branch.branch_name;
    document.getElementById('view_branch_address').innerText = branch.branch_address;
    document.getElementById('view_branch_contact').innerText = branch.branch_contactNum;
    
    // Load all data for the branch
    loadBranchUsers(branch);
    
    // Check if branch already has the related data loaded
    if (branch.products || branch.services || branch.equipment) {
        // Use existing loaded data
        loadBranchProducts(branch.products || []);
        loadBranchServices(branch.services || []);
        loadBranchEquipment(branch.equipment || []);
    } else {
        // Fetch data if not already loaded
        loadBranchData(branch.branch_id);
    }
    
    document.getElementById('viewBranchModal').classList.remove('hidden');
}



function closeViewModal() {
    document.getElementById('viewBranchModal').classList.add('hidden');
}


// Tab switching for branch modal
function switchBranchTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.branch-tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Remove active class from all tab buttons
    document.querySelectorAll('.branch-tab-button').forEach(button => {
        button.classList.remove('active', 'border-[#0f7ea0]', 'text-[#0f7ea0]');
        button.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Show selected tab content
    document.getElementById(`branch-${tabName}-content`).classList.remove('hidden');
    
    // Add active class to selected tab button
    const activeTab = document.getElementById(`branch${tabName.charAt(0).toUpperCase() + tabName.slice(1)}TabBtn`);
    activeTab.classList.add('active', 'border-[#0f7ea0]', 'text-[#0f7ea0]');
    activeTab.classList.remove('border-transparent', 'text-gray-500');
}

function loadBranchUsers(branch) {
    const usersTableBody = document.getElementById('view_branch_users_table');
    const usersCountBadge = document.getElementById('users_count_badge');
    
    usersTableBody.innerHTML = '';
    
    if (branch.users && branch.users.length > 0) {
        branch.users.forEach((user, index) => {
            // Calculate if user is active (logged in within last week)
            const isActive = user.last_login_at && isWithinLastWeek(user.last_login_at);
            const statusClass = isActive ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
            const statusText = isActive ? 'Active' : 'Inactive';
            
            // Format last login
            const lastLoginText = user.last_login_at 
                ? formatDate(user.last_login_at) + '<br><small class="text-gray-500">' + timeAgo(user.last_login_at) + '</small>'
                : '<span class="text-gray-500">Never logged in</span>';
            
            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-50';
            row.innerHTML = `
                <td class="border px-2 py-2">${index + 1}</td>
                <td class="border px-2 py-2">${user.user_name}</td>
                <td class="border px-2 py-2">${user.user_email}</td>
                <td class="border px-2 py-2 capitalize">${user.user_role}</td>
                <td class="border px-2 py-2">
                    <span class="text-xs px-2 py-1 rounded-full font-medium ${statusClass}">
                        ${statusText}
                    </span>
                </td>
                <td class="border px-2 py-2 text-xs">${lastLoginText}</td>
            `;
            usersTableBody.appendChild(row);
        });
        
        usersCountBadge.innerText = branch.users.length;
    } else {
        const emptyRow = document.createElement('tr');
        emptyRow.innerHTML = `
            <td colspan="6" class="text-center text-gray-500 py-4">No users assigned to this branch.</td>
        `;
        usersTableBody.appendChild(emptyRow);
        usersCountBadge.innerText = '0';
    }
}

// Helper functions for date formatting and status calculation
function isWithinLastWeek(dateString) {
    if (!dateString) return false;
    const lastLogin = new Date(dateString);
    const oneWeekAgo = new Date();
    oneWeekAgo.setDate(oneWeekAgo.getDate() - 7);
    return lastLogin > oneWeekAgo;
}

function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        month: 'short', 
        day: 'numeric', 
        year: 'numeric' 
    });
}

function timeAgo(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    if (diffInSeconds < 60) return 'just now';
    if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + ' minutes ago';
    if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + ' hours ago';
    if (diffInSeconds < 604800) return Math.floor(diffInSeconds / 86400) + ' days ago';
    return Math.floor(diffInSeconds / 604800) + ' weeks ago';
}

async function loadBranchData(branchId) {
    try {
        // Fetch real data from your database
        const response = await fetch(`/branches/${branchId}/complete-data`);
        const data = await response.json();
        
        console.log('Fetched branch data:', data); // Debug log
        
        // Load the real data
        loadBranchProducts(data.products || []);
        loadBranchServices(data.services || []);
        loadBranchEquipment(data.equipment || []);
        
    } catch (error) {
        console.error('Error fetching branch data:', error);
        // Load empty data on error
        loadBranchProducts([]);
        loadBranchServices([]);
        loadBranchEquipment([]);
    }
}

function loadBranchProducts(products) {
    const productsTableBody = document.getElementById('view_branch_products_table');
    const productsCountBadge = document.getElementById('products_count_badge');
    
    productsTableBody.innerHTML = '';
    
    if (products.length > 0) {
        products.forEach(product => {
            const stockStatus = product.prod_stocks <= (product.prod_reorderlevel || 10) ? 'Low Stock' : 'In Stock';
            const statusClass = stockStatus === 'Low Stock' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800';
            
            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-50';
            row.innerHTML = `
                <td class="border px-2 py-2">
                    ${product.prod_image 
                        ? `<img src="/storage/${product.prod_image}" class="h-8 w-8 object-cover mx-auto rounded">` 
                        : '<span class="text-gray-400 text-xs">No Image</span>'
                    }
                </td>
                <td class="border px-2 py-2">${product.prod_name}</td>
                <td class="border px-2 py-2">${product.prod_category || 'N/A'}</td>
                <td class="border px-2 py-2">₱${parseFloat(product.prod_price).toLocaleString()}</td>
                <td class="border px-2 py-2">${product.prod_stocks}</td>
                <td class="border px-2 py-2">
                    <span class="text-xs px-2 py-1 rounded-full ${statusClass}">${stockStatus}</span>
                </td>
            `;
            productsTableBody.appendChild(row);
        });
        
        productsCountBadge.innerText = products.length;
    } else {
        const emptyRow = document.createElement('tr');
        emptyRow.innerHTML = `
            <td colspan="6" class="text-center text-gray-500 py-4">No products available at this branch.</td>
        `;
        productsTableBody.appendChild(emptyRow);
        productsCountBadge.innerText = '0';
    }
}

function loadBranchServices(services) {
    const servicesTableBody = document.getElementById('view_branch_services_table');
    const servicesCountBadge = document.getElementById('services_count_badge');
    
    servicesTableBody.innerHTML = '';
    
    if (services.length > 0) {
        services.forEach(service => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-50';
            row.innerHTML = `
                <td class="border px-2 py-2">${service.serv_name}</td>
                <td class="border px-2 py-2">${service.serv_type || 'N/A'}</td>
                <td class="border px-2 py-2">${truncateText(service.serv_description || 'N/A', 50)}</td>
                <td class="border px-2 py-2">₱${parseFloat(service.serv_price).toLocaleString()}</td>
            `;
            servicesTableBody.appendChild(row);
        });
        
        servicesCountBadge.innerText = services.length;
    } else {
        const emptyRow = document.createElement('tr');
        emptyRow.innerHTML = `
            <td colspan="4" class="text-center text-gray-500 py-4">No services available at this branch.</td>
        `;
        servicesTableBody.appendChild(emptyRow);
        servicesCountBadge.innerText = '0';
    }
}

function loadBranchEquipment(equipment) {
    const equipmentTableBody = document.getElementById('view_branch_equipment_table');
    const equipmentCountBadge = document.getElementById('equipment_count_badge');
    
    equipmentTableBody.innerHTML = '';
    
    if (equipment.length > 0) {
        equipment.forEach(equip => {
            const status = equip.equipment_quantity > 0 ? 'Available' : 'Out of Stock';
            const statusClass = status === 'Available' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
            
            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-50';
            row.innerHTML = `
                <td class="border px-2 py-2">
                    ${equip.equipment_image 
                        ? `<img src="/storage/${equip.equipment_image}" class="h-8 w-8 object-cover mx-auto rounded">` 
                        : '<span class="text-gray-400 text-xs">No Image</span>'
                    }
                </td>
                <td class="border px-2 py-2">${equip.equipment_name}</td>
                <td class="border px-2 py-2">${equip.equipment_category || 'N/A'}</td>
                <td class="border px-2 py-2">${equip.equipment_quantity}</td>
                <td class="border px-2 py-2">
                    <span class="text-xs px-2 py-1 rounded-full ${statusClass}">${status}</span>
                </td>
            `;
            equipmentTableBody.appendChild(row);
        });
        
        equipmentCountBadge.innerText = equipment.length;
    } else {
        const emptyRow = document.createElement('tr');
        emptyRow.innerHTML = `
            <td colspan="5" class="text-center text-gray-500 py-4">No equipment available at this branch.</td>
        `;
        equipmentTableBody.appendChild(emptyRow);
        equipmentCountBadge.innerText = '0';
    }
}

function truncateText(text, maxLength) {
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
}

function openAddModal() {
    document.getElementById('branchModal').classList.remove('hidden');
}

function openAddReferralCompanyModal() {
    document.getElementById('referralCompanyModal').classList.remove('hidden');
}
function closeReferralCompanyModal() {
    document.getElementById('referralCompanyModal').classList.add('hidden');
}

function closeEditReferralCompanyModal() {
    document.getElementById('editReferralCompanyModal').classList.add('hidden');
}

function closeBranchModal() {
    document.getElementById('branchModal').classList.add('hidden');
}

function closeEditModal() {
    document.getElementById('editBranchModal').classList.add('hidden');
}

// User Functions
function openAddUserModal() {
    document.getElementById('userModal').classList.remove('hidden');
}

function closeUserModal() {
    document.getElementById('userModal').classList.add('hidden');
}

function closeEditUserModal() {
    document.getElementById('editUserModal').classList.add('hidden');
}

// Add User to Branch Functions
function openAddUserToBranchModal(branchId, branchName) {
    document.getElementById('selected_branch_id').value = branchId;
    document.getElementById('selected_branch_name').innerText = branchName;
    document.getElementById('addUserToBranchModal').classList.remove('hidden');
}

function closeAddUserToBranchModal() {
    document.getElementById('addUserToBranchModal').classList.add('hidden');
    // Reset form
    document.querySelector('#addUserToBranchModal form').reset();
    document.getElementById('license_field_branch').classList.add('hidden');
    document.querySelector('#license_field_branch input').required = false;
}

// DOM Ready Functions
document.addEventListener('DOMContentLoaded', function () {
   
    // Edit branch buttons
    document.querySelectorAll('.editBranchBtn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            const name = this.dataset.name;
            const address = this.dataset.address;
            const contact = this.dataset.contact;

            document.getElementById('edit_branch_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_address').value = address;
            document.getElementById('edit_contact').value = contact;

            document.getElementById('editBranchForm').action = `/branches/${id}`;
            document.getElementById('editBranchModal').classList.remove('hidden');
        });
    });

    // Add user to branch buttons
    document.querySelectorAll('.addUserToBranchBtn').forEach(btn => {
        btn.addEventListener('click', function () {
            const branchId = this.dataset.branchId;
            const branchName = this.dataset.branchName;
            openAddUserToBranchModal(branchId, branchName);
        });
    });

    // Edit user buttons
    document.querySelectorAll('.editUserBtn').forEach(button => {
        button.addEventListener('click', function () {
            const userId = this.dataset.id;
            const user_name = this.dataset.name;
            const user_email = this.dataset.email;
            const user_contact = this.dataset.contact;
            const user_role = this.dataset.role;
            const user_license = this.dataset.license;
            const branch = this.dataset.branch;

            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_user_name').value = user_name;
            document.getElementById('edit_user_email').value = user_email;
            document.getElementById('edit_user_contact').value = user_contact || '';
            document.getElementById('edit_user_role').value = user_role;
            document.getElementById('edit_user_license').value = user_license || '';
            document.getElementById('edit_user_branch').value = branch;

            // Show/hide license field based on role
            const licenseField = document.getElementById('edit_license_field');
            const licenseInput = document.getElementById('edit_user_license');
            if (user_role === 'veterinarian') {
                licenseField.classList.remove('hidden');
                licenseInput.required = true;
            } else {
                licenseField.classList.add('hidden');
                licenseInput.required = false;
            }

            document.getElementById('edit_user_password').value = "";
            document.getElementById('edit_user_password_confirmation').value = "";

            document.getElementById('editUserForm').action = "{{ url('user-management') }}/" + userId;
            document.getElementById('editUserModal').classList.remove('hidden');
        });
    });
});
document.addEventListener('DOMContentLoaded', function () {
    // ... existing DOMContentLoaded code (Edit branch/user buttons) ...

    // --- PERSISTENT TAB LOGIC ---
    
    // 1. Check for active_tab session data from PHP (used after redirect)
    const activeTabFromSession = "{{ session('active_tab') }}";
    
    // 2. Check URL query parameter (optional, if you want direct links to work)
    const urlParams = new URLSearchParams(window.location.search);
    const activeTabFromQuery = urlParams.get('tab');
    
    // Default to 'branch' if no state is found
    let defaultTab = 'branch';

    if (activeTabFromSession && activeTabFromSession !== '') {
        defaultTab = activeTabFromSession;
    } else if (activeTabFromQuery) {
        defaultTab = activeTabFromQuery;
    }
    
    // Switch to the determined tab
    switchTab(defaultTab);
});

// The rest of your existing JS functions must be outside the DOMContentLoaded listener 
// (which they currently are, but the whole block is now wrapped in the init/onload logic).
// Ensure your original switchTab function remains accessible globally.
//
// NOTE: I am assuming your existing global switchTab(tabName) function is correct.

// ... existing switchTab function should look like this:
function switchTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Remove active class from all tab buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        // NOTE: Changed color classes to match your design: #ff8c42
        button.classList.remove('active', 'border-[#ff8c42]', 'text-[#ff8c42]');
        button.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Show selected tab content
    document.getElementById(tabName + 'Content').classList.remove('hidden');
    
    // Add active class to selected tab button
    const activeTab = document.getElementById(tabName + 'Tab');
    activeTab.classList.add('active', 'border-[#ff8c42]', 'text-[#ff8c42]');
    activeTab.classList.remove('border-transparent', 'text-gray-500');
}

// Persistent client-side search for Branches and Users with auto-switch to 'All'
(function(){
    function persistKey(tab){ return `bm_search_${tab}`; }
    function setPersist(tab, val){ try{ localStorage.setItem(persistKey(tab), val); }catch(e){} }
    function getPersist(tab){ try{ return localStorage.getItem(persistKey(tab)) || ''; }catch(e){ return ''; } }

    function filterBody(tbody, q){
        const needle = String(q || '').toLowerCase();
        tbody.querySelectorAll('tr').forEach(tr => {
            const text = tr.textContent.toLowerCase();
            tr.style.display = !needle || text.includes(needle) ? '' : 'none';
        });
    }

    function setupTableFilter({inputId, tableSelector, tab, perPageSelectId, formSelector}){
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

    // Branches
    setupTableFilter({
        inputId: 'branchesSearch',
        tableSelector: '#branchContent table',
        tab: 'branches',
        perPageSelectId: 'branchPerPage',
        formSelector: '#branchContent form[action]'
    });
    // Users
    setupTableFilter({
        inputId: 'usersSearch',
        tableSelector: '#userContent table',
        tab: 'users',
        perPageSelectId: 'userPerPage',
        formSelector: '#userContent form[action]'
    });
})();
</script>

<style>
.tab-button.active {
    border-color: #0f7ea0;
    color: #0f7ea0;
}

.tab-content {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
</style>

@endsection