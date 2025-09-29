@extends('AdminBoard')

@section('content')
  <div class="min-h-screen">
    <div class="max-w-7xl mx-auto bg-white p-6 rounded-lg shadow">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-[#0f7ea0] font-bold text-xl">Branches</h2>
      <button onclick="openAddModal()" class="bg-[#0f7ea0] text-white text-sm px-4 py-2 rounded hover:bg-[#0c6a86]">
      + Add Branches
      </button>
    </div>

    {{-- Success Message --}}
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
        <th class="border px-2 py-1">#</th>
        <th class="border px-2 py-1">Branch Name</th>
        <th class="border px-2 py-1">Address</th>
        <th class="border px-2 py-1">Contact</th>
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
        <div class="flex justify-center items-center gap-1">
        <button
        class="editBranchBtn bg-[#0f7ea0] text-white px-2 py-1 rounded hover:bg-[#0c6a86] flex items-center gap-1 text-xs"
        data-id="{{ $branch->branch_id }}" data-name="{{ $branch->branch_name }}"
        data-address="{{ $branch->branch_address }}" data-contact="{{ $branch->branch_contactNum }}">
        <i class="fas fa-pen"></i> 
        </button>
        <button onclick='openViewModal(@json($branch))'
        class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 flex items-center gap-1 text-xs">
        <i class="fas fa-eye"></i> 
        </button>
        <form action="{{ route('branches-destroy', $branch->branch_id) }}" method="POST"
        onsubmit="return confirm('Delete this branch?')">
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
      @endforeach
      </tbody>
      </table>
    </div>
    </div>
  </div>

  <!-- Branch Modal Form -->
  <div id="branchModal" class="fixed inset-0 bg-black bg-opacity-30 flex justify-center items-center hidden z-50">
    <div class="bg-white w-full max-w-lg p-6 rounded shadow-lg relative">
    <form id="branchForm" method="POST">
      @csrf
      <input type="hidden" name="_method" value="POST">

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

  <div id="editBranchModal" class="fixed inset-0 bg-black bg-opacity-40 flex justify-center items-center hidden z-50">
    <div class="bg-white p-6 rounded shadow w-full max-w-md relative">
    <form id="editBranchForm" method="POST">
      @csrf
      @method('PUT')
      <input type="hidden" name="branch_id" id="edit_branch_id">
      <div>
      <label>Name:</label>
      <input type="text" name="name" id="edit_name" class="w-full border p-2 rounded">
      </div>
      <div>
      <label>Address:</label>
      <input type="text" name="address" id="edit_address" class="w-full border p-2 rounded">
      </div>
      <div>
      <label>Contact:</label>
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

  <div id="viewBranchModal" class="fixed inset-0 bg-black bg-opacity-40 flex justify-center items-center hidden z-50">
    <div class="bg-white p-6 rounded shadow w-full max-w-md relative">
    <h2 class="text-lg font-semibold text-[#0f7ea0] mb-4">Branch Details</h2>
    <div class="space-y-2 text-sm">
      <p><strong>Name:</strong> <span id="view_branch_name"></span></p>
      <p><strong>Address:</strong> <span id="view_branch_address"></span></p>
      <p><strong>Contact:</strong> <span id="view_branch_contact"></span></p>
    </div>
    <div class="flex justify-end mt-4">
      <button onclick="closeViewModal()" class="px-4 py-1 bg-gray-300 rounded text-sm hover:bg-gray-400">Close</button>
    </div>
    </div>
  </div>



  <script>
    function openViewModal(branch) {
    document.getElementById('view_branch_name').innerText = branch.branch_name;
    document.getElementById('view_branch_address').innerText = branch.branch_address;
    document.getElementById('view_branch_contact').innerText = branch.branch_contactNum;
    document.getElementById('viewBranchModal').classList.remove('hidden');
    }

    function closeViewModal() {
    document.getElementById('viewBranchModal').classList.add('hidden');
    }

    function openAddModal() {
    document.getElementById('branchModal').classList.remove('hidden');
    }

    function closeBranchModal() {
    document.getElementById('branchModal').classList.add('hidden');
    }
    function closeBranchModal() {
    document.getElementById('branchModal').classList.add('hidden');
    }

    document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('branchForm');

    form.addEventListener('submit', function (e) {
      e.preventDefault();

      const formData = new FormData(form);

      fetch('{{ route("branches.store") }}', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value
      },
      body: formData
      })
      .then(async response => {
        const responseText = await response.text();

        try {
        const data = JSON.parse(responseText);
        alert('Branch saved successfully!');
        location.reload(); // optional: refresh the page
        } catch (err) {
        console.error('Not JSON:', responseText);
        alert('Failed to save branch. Please check server logs.');
        }
      })
      .catch(error => {
        console.error('Request failed:', error);
        alert('Error saving branch: ' + error.message);
      });
    });
    });

    document.querySelectorAll('.editBranchBtn').forEach(btn => {
    btn.addEventListener('click', function () {
      const id = this.dataset.id;
      const name = this.dataset.name;
      const address = this.dataset.address;
      const contact = this.dataset.contact;

      // Fill modal form
      document.getElementById('edit_branch_id').value = id;
      document.getElementById('edit_name').value = name;
      document.getElementById('edit_address').value = address;
      document.getElementById('edit_contact').value = contact;

      // Set form action
      document.getElementById('editBranchForm').action = `/branches/${id}`;

      // Show modal
      document.getElementById('editBranchModal').classList.remove('hidden');
    });
    });

    function closeEditModal() {
    document.getElementById('editBranchModal').classList.add('hidden');
    }
  </script>
@endsection