@extends('AdminBoard')

@section('content')
<div class="min-h-screen">
    <div class="max-w-7xl mx-auto bg-white p-6 rounded-lg shadow">
        <h2 class="text-[#0f7ea0] font-bold text-xl mb-4">Product & Inventory Management</h2>

        <!-- Tabs -->
        <div class="border-b mb-4 flex space-x-4">
            <button onclick="switchTab('productsTab')" id="productsBtn" 
                class="py-2 px-4 text-sm font-semibold border-b-2 border-[#0f7ea0] text-[#0f7ea0]">
                Products
            </button>
            <button onclick="switchTab('inventoryTab')" id="inventoryBtn"
                class="py-2 px-4 text-sm font-semibold border-b-2 border-transparent hover:border-[#0f7ea0] hover:text-[#0f7ea0]">
                Inventory
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
        <!-- PRODUCTS TAB -->
        <div id="productsTab" class="tab-content">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-[#ffffffff]">Products</h3>
                <button onclick="openAddModal()"
                    class="bg-[#0f7ea0] text-white text-sm px-4 py-2 rounded hover:bg-[#0c6a86]">
                    + Add Product
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full table-auto text-sm border text-center">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-2 py-2">Image</th>
                            <th class="border px-2 py-2">Name</th>
                            <th class="border px-2 py-2">Category</th>
                            <th class="border px-2 py-2">Description</th>
                            <th class="border px-2 py-2">Price</th>
                            <th class="border px-2 py-2">Branch</th>
                            <th class="border px-2 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $product)
                        <tr>
                            <td class="border px-2 py-1">
                                @if($product->prod_image)
                                    <img src="{{ asset('storage/'.$product->prod_image) }}" 
                                         class="h-12 w-12 object-cover mx-auto rounded">
                                @else
                                    <span class="text-gray-400">No Image</span>
                                @endif
                            </td>
                            <td class="border px-2 py-1">{{ $product->prod_name }}</td>
                            <td class="border px-2 py-1">{{ $product->prod_category }}</td>
                            <td class="border px-2 py-1">{{ $product->prod_description }}</td>
                            <td class="border px-2 py-1">₱{{ number_format($product->prod_price, 2) }}</td>
                            <td class="border px-2 py-1">{{ $product->branch->branch_name ?? 'N/A' }}</td>
                            <td class="border px-2 py-1">
                                <div class="flex gap-2">
                                    <button onclick="openEditModal({{ json_encode($product) }})"
                                        class="bg-[#0f7ea0] text-white px-2 py-1 rounded hover:bg-[#0c6a86] text-xs">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <form action="{{ route('products.destroy', $product->prod_id) }}" method="POST"
                                        onsubmit="return confirm('Delete this product?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="bg-red-500 text-white px-2 py-1 rounded text-xs hover:bg-red-600">
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

        <!-- INVENTORY TAB -->
        <div id="inventoryTab" class="tab-content hidden">
            <br>
            <div class="overflow-x-auto">
                <table class="w-full table-auto text-sm border text-center">
                   <thead class="bg-gray-100">
<tr>
    <th class="border px-2 py-2">Product</th>
    <th class="border px-2 py-2">Available Stock</th>
    <th class="border px-2 py-2">Reorder Level</th>
    <th class="border px-2 py-2">Damaged</th>
    <th class="border px-2 py-2">Pull-Out</th>
    <th class="border px-2 py-2">Expiry</th>
    <th class="border px-2 py-2">Last Updated</th>
    <th class="border px-2 py-2">Actions</th>
</tr>
</thead>
<tbody>
@foreach($products as $product)
<tr>
    <td class="border px-2 py-1">{{ $product->prod_name }}</td>
    <td class="border px-2 py-1">{{ $product->prod_stocks }}</td>
    <td class="border px-2 py-1">{{ $product->prod_reorderlevel }}</td>
    <td class="border px-2 py-1">{{ $product->prod_damaged }}</td>
    <td class="border px-2 py-1">{{ $product->prod_pullout }}</td>
    <td class="border px-2 py-1">{{ $product->prod_expiry ? \Carbon\Carbon::parse($product->prod_expiry)->format('M d, Y') : 'N/A' }}</td>
    <td class="border px-2 py-1">{{ optional($product->updated_at)->diffForHumans() ?? 'N/A' }}</td>
    <td class="border px-2 py-1">
        <button onclick="openInventoryEditModal({{ json_encode($product) }})" 
                class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 text-xs">
            <i class="fas fa-pen"></i>
        </button>
    </td>
</tr>
@endforeach
</tbody>

                </table>
            </div>
        </div>

    </div>
</div>

<!-- ADD PRODUCT MODAL -->
<div id="addProductModal" class="hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
        <h3 class="text-lg font-bold mb-4">Add Product</h3>
        <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="text" name="prod_name" placeholder="Product Name" class="border p-2 w-full mb-2" required>
            <textarea name="prod_description" placeholder="Description" class="border p-2 w-full mb-2" required></textarea>
            <input type="text" name="prod_category" placeholder="Category" class="border p-2 w-full mb-2">
            <input type="number" step="0.01" name="prod_price" placeholder="Price" class="border p-2 w-full mb-2" required>
            <input type="number" name="prod_stocks" placeholder="Initial Stock" class="border p-2 w-full mb-2" required>
            <input type="number" name="prod_reorderlevel" placeholder="Reorder Level" class="border p-2 w-full mb-2">
            <input type="file" name="prod_image" class="border p-2 w-full mb-2">
            <select name="branch_id" class="border p-2 w-full mb-2" required>
                @foreach($branches as $branch)
                <option value="{{ $branch->branch_id }}">{{ $branch->branch_name }}</option>
                @endforeach
            </select>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeAddModal()" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-[#0f7ea0] text-white rounded">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT PRODUCT MODAL -->
<div id="editProductModal" class="hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
        <h3 class="text-lg font-bold mb-4">Edit Product</h3>

        <form id="editProductForm" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <input type="text" name="prod_name" placeholder="Product Name" class="border p-2 w-full mb-2" required>
            <textarea name="prod_description" placeholder="Description" class="border p-2 w-full mb-2" required></textarea>
            <input type="text" name="prod_category" placeholder="Category" class="border p-2 w-full mb-2">
            <input type="number" step="0.01" name="prod_price" placeholder="Price" class="border p-2 w-full mb-2" required>
            <input type="number" name="prod_stocks" placeholder="Stock" class="border p-2 w-full mb-2" required>
            <input type="number" name="prod_reorderlevel" placeholder="Reorder Level" class="border p-2 w-full mb-2">
            <input type="file" name="prod_image" class="border p-2 w-full mb-2">

            <select name="branch_id" class="border p-2 w-full mb-2" required>
                @foreach($branches as $branch)
                <option value="{{ $branch->branch_id }}">{{ $branch->branch_name }}</option>
                @endforeach
            </select>

            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-[#0f7ea0] text-white rounded">Update</button>
            </div>
        </form>
    </div>
</div>

<div id="inventoryEditModal" class="hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
        <h3 class="text-lg font-bold mb-4">Update Inventory</h3>

        <form id="inventoryEditForm" method="POST">
            @csrf
            @method('PUT')

            <input type="hidden" name="prod_id">

            <input type="number" name="prod_damaged" placeholder="Damaged" class="border p-2 w-full mb-2">
            <input type="number" name="prod_pullout" placeholder="Pull-Out" class="border p-2 w-full mb-2">
            <input type="date" name="prod_expiry" class="border p-2 w-full mb-2">

            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeInventoryEditModal()" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-[#0f7ea0] text-white rounded">Update</button>
            </div>
        </form>
    </div>
</div>



<script>
    function openInventoryEditModal(product) {
    document.getElementById('inventoryEditModal').classList.remove('hidden');
    const form = document.getElementById('inventoryEditForm');
    form.action = "{{ route('products.inventory.update', ':id') }}".replace(':id', product.prod_id);    
    form.querySelector('input[name="prod_id"]').value = product.prod_id;
    form.querySelector('input[name="prod_damaged"]').value = product.prod_damaged ?? '';
    form.querySelector('input[name="prod_pullout"]').value = product.prod_pullout ?? '';
    form.querySelector('input[name="prod_expiry"]').value = product.prod_expiry ?? '';
}

function closeInventoryEditModal() {
    document.getElementById('inventoryEditModal').classList.add('hidden');
}

    function openEditModal(product) {
    // Open modal and fill fields dynamically
    document.getElementById('editProductModal').classList.remove('hidden');

    document.getElementById('editProductForm').action = "/products/" + product.prod_id;
    document.querySelector('#editProductForm input[name="prod_name"]').value = product.prod_name;
    document.querySelector('#editProductForm textarea[name="prod_description"]').value = product.prod_description;
    document.querySelector('#editProductForm input[name="prod_category"]').value = product.prod_category ?? '';
    document.querySelector('#editProductForm input[name="prod_price"]').value = product.prod_price;
    document.querySelector('#editProductForm input[name="prod_stocks"]').value = product.prod_stocks;
    document.querySelector('#editProductForm input[name="prod_reorderlevel"]').value = product.prod_reorderlevel ?? '';

    // Preselect branch
    document.querySelector('#editProductForm select[name="branch_id"]').value = product.branch_id;
}

function closeEditModal() {
    document.getElementById('editProductModal').classList.add('hidden');
}
    function switchTab(tabId) {
        document.querySelectorAll('.tab-content').forEach(tab => tab.classList.add('hidden'));
        document.getElementById(tabId).classList.remove('hidden');
        document.getElementById('productsBtn').classList.remove('border-[#0f7ea0]', 'text-[#0f7ea0]');
        document.getElementById('inventoryBtn').classList.remove('border-[#0f7ea0]', 'text-[#0f7ea0]');
        if (tabId === 'productsTab') {
            document.getElementById('productsBtn').classList.add('border-[#0f7ea0]', 'text-[#0f7ea0]');
        } else {
            document.getElementById('inventoryBtn').classList.add('border-[#0f7ea0]', 'text-[#0f7ea0]');
        }
    }

    function openAddModal() {
        document.getElementById('addProductModal').classList.remove('hidden');
    }
    function closeAddModal() {
        document.getElementById('addProductModal').classList.add('hidden');
    }

    function openInventoryModal() {
        document.getElementById('inventoryModal').classList.remove('hidden');
    }
    function closeInventoryModal() {
        document.getElementById('inventoryModal').classList.add('hidden');
    }
</script>
@endsection
