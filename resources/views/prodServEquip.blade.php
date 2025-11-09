@extends('AdminBoard')
@php
    $userRole = strtolower(auth()->user()->user_role ?? '');

    // Define permissions for each role
    $permissions = [
        'superadmin' => [
            'add_product' => true,
            'edit_product' => true,
            'delete_product' => true,
            'update_stock' => true,
            'update_damage' => true,
            'view_details' => true,
            'add_service' => true,
            'edit_service' => true,
            'delete_service' => true,
            'add_equipment' => true,
            'edit_equipment' => true,
            'delete_equipment' => true,
        ],
        'veterinarian' => [
            'add_product' => true,
            'edit_product' => true,
            'delete_product' => true,
            'update_stock' => true,
            'update_damage' => true,
            'view_details' => true,
            'add_service' => true,
            'edit_service' => true,
            'delete_service' => true,
            'add_equipment' => true,
            'edit_equipment' => true,
            'delete_equipment' => true,
        ],
        'receptionist' => [
            'add_product' => false,
            'edit_product' => false,
            'delete_product' => false,
            'update_stock' => true,
            'update_damage' => true,
            'view_details' => true,
            'add_service' => false,
            'edit_service' => false,
            'delete_service' => false,
            'add_equipment' => false,
            'edit_equipment' => false,
            'delete_equipment' => false,
        ],
    ];

    // Get permissions for current user
    $can = $permissions[$userRole] ?? [];

    // Helper function to check permission
    function hasPermission($permission, $can)
    {
        return $can[$permission] ?? false;
    }
@endphp
@section('content')
    <div class="min-h-screen">
        <div class="max-w-7xl mx-auto bg-white p-6 rounded-lg shadow">
            <div class="border-b border-gray-200 mb-6" id="main-tabs-container">
                <button onclick="switchMainTab('productInventoryTab', 'products')" id="productInventoryBtn"
                    class="py-2 px-4 text-sm font-semibold border-b-2 border-transparent hover:border-[#0f7ea0] hover:text-[#0f7ea0]">
                    <h2 class=" text-xl">Products</h2>
                </button>
                <button onclick="switchMainTab('servicesTab', 'services')" id="servicesBtn"
                    class="py-2 px-4 text-sm font-semibold border-b-2 border-transparent hover:border-[#0f7ea0] hover:text-[#0f7ea0]">
                    <h2 class="text-xl">Services</h2>
                </button>
                <button onclick="switchMainTab('equipmentTab', 'equipment')" id="equipmentBtn"
                    class="py-2 px-4 text-sm font-semibold border-b-2 border-transparent hover:border-[#0f7ea0] hover:text-[#0f7ea0]">
                    <h2 class="text-xl">Equipment</h2>
                </button>
            </div>

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-4 text-sm">
                    {{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="bg-red-500 text-white p-2 rounded mb-4">{{ session('error') }}</div>
            @endif

            <div id="productInventoryTab" class="main-tab-content">
                <div class="flex justify-between items-center mb-4 gap-2 flex-wrap">
                    <form method="GET" action="{{ request()->url() }}" class="flex items-center space-x-2">
                        <input type="hidden" name="tab" value="products">
                        <label for="productsPerPage" class="text-sm text-black">Show</label>
                        <select name="productsPerPage" id="productsPerPage" onchange="this.form.submit()"
                            class="border border-gray-400 rounded px-2 py-1 text-sm">
                            @foreach ([10, 20, 50, 100, 'all'] as $limit)
                                <option value="{{ $limit }}" {{ request('productsPerPage') == $limit ? 'selected' : '' }}>
                                    {{ $limit === 'all' ? 'All' : $limit }}
                                </option>
                            @endforeach
                        </select>
                        <span>entries</span>
                    </form>
                    <div class="flex gap-2 items-center flex-wrap">
                        <div class="relative">
                            <input type="search" id="productsSearch" placeholder="Search products..." class="border border-gray-300 rounded px-3 py-2 text-sm pl-8">
                            <i class="fas fa-search absolute left-2 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        </div>
                        <button onclick="openInventoryOverview()"
                            class="bg-purple-600 text-white text-sm px-4 py-2 rounded hover:bg-purple-700">
                            📊 Inventory Overview
                        </button>
                        @if(hasPermission('add_product', $can))
                            <button onclick="openAddProductModal()"
                                class="bg-[#0f7ea0] text-white text-sm px-4 py-2 rounded hover:bg-[#0c6a86]">
                                + Add Product
                            </button>
                        @endif
                    </div>
                </div>


                <div class="overflow-x-auto">
                    <table class="w-full table-auto text-sm border text-center">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="p-2 border">Image</th>
                                <th class="p-2 border">Name</th>
                                <th class="p-2 border">Category</th>
                                <th class="p-2 border">Description</th>
                                <th class="p-2 border">Price</th>
                                <th class="p-2 border">Stock</th>
                                <th class="p-2 border">Reorder Level</th>
                                <th class="p-2 border">Expiry Date</th>
                                <th class="p-2 border">Branch</th>
                                <th class="p-2 border">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $product)
                                <tr>
                                    <td class="p-2 border">
                                        @if($product->prod_image)
                                            <img src="{{ asset('storage/' . $product->prod_image) }}"
                                                class="h-12 w-12 object-cover mx-auto rounded">
                                        @else
                                            <span class="text-gray-400">No Image</span>
                                        @endif
                                    </td>
                                    <td class="p-2 border font-medium">{{ $product->prod_name }}</td>
                                    <td class="p-2 border">{{ $product->prod_category }}</td>
                                    <td class="p-2 border">{{ Str::limit($product->prod_description, 30) }}</td>
                                    <td class="p-2 border">₱{{ number_format($product->prod_price, 2) }}</td>
                                    <td class="p-2 border">
                                        @php
                                            $stockStatus = '';
                                            if ($product->prod_stocks <= ($product->prod_reorderlevel ?? 10)) {
                                                $stockStatus = 'text-red-600 font-bold';
                                            }
                                        @endphp
                                        <div>
                                            <span class="{{ $stockStatus }}">{{ $product->prod_stocks ?? 0 }}</span>
                                            @if(($product->prod_damaged ?? 0) > 0 || ($product->prod_pullout ?? 0) > 0)
                                                <div class="text-xs mt-1">
                                                    @if(($product->prod_damaged ?? 0) > 0)
                                                        <span class="text-red-500">D: {{ $product->prod_damaged }}</span>
                                                    @endif
                                                    @if(($product->prod_pullout ?? 0) > 0)
                                                        <span class="text-orange-500 ml-1">P: {{ $product->prod_pullout }}</span>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="p-2 border">{{ $product->prod_reorderlevel ?? 'N/A' }}</td>
                                    <td class="p-2 border">
                                        @if($product->prod_expiry)
                                            @php
                                                $expiryDate = \Carbon\Carbon::parse($product->prod_expiry);
                                                $daysUntilExpiry = now()->diffInDays($expiryDate, false);
                                            @endphp
                                            <span
                                                class="{{ $daysUntilExpiry < 0 ? 'text-red-600' : ($daysUntilExpiry <= 30 ? 'text-yellow-600' : 'text-green-600') }}">
                                                {{ $expiryDate->format('M d, Y') }}
                                            </span>
                                            @if($daysUntilExpiry < 0)
                                                <div class="text-xs text-red-600">Expired</div>
                                            @elseif($daysUntilExpiry <= 30)
                                                <div class="text-xs text-yellow-600">{{ $daysUntilExpiry }} days left</div>
                                            @endif
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td class="p-2 border">{{ $product->branch->branch_name ?? 'N/A' }}</td>
                                    <td class="p-2 border">
                                        <div class="flex justify-center gap-1 flex-wrap">
                                            @if(hasPermission('view_details', $can))
                                                <button onclick="viewProductDetails({{ $product->prod_id }})"
                                                    class="bg-purple-500 text-white px-2 py-1 rounded hover:bg-purple-600 text-xs"
                                                    title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            @endif

                                            @if(hasPermission('edit_product', $can))
                                                <button onclick="openEditProductModal({{ json_encode($product) }})"
                                                    class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 text-xs"
                                                    title="Edit Product">
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                            @endif

                                            @if(hasPermission('update_stock', $can))
                                                <button onclick="openUpdateStockModal({{ json_encode($product) }})"
                                                    class="bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600 text-xs"
                                                    title="Update Stock">
                                                    <i class="fas fa-boxes"></i>
                                                </button>
                                            @endif

                                            @if(hasPermission('update_damage', $can))
                                                <button onclick="openDamagePulloutModal({{ json_encode($product) }})"
                                                    class="bg-orange-500 text-white px-2 py-1 rounded hover:bg-orange-600 text-xs"
                                                    title="Damage/Pull-out">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                </button>
                                            @endif

                                            @if(hasPermission('delete_product', $can))
                                                <form action="{{ route('products.destroy', $product->prod_id) }}" method="POST"
                                                    onsubmit="return confirm('Delete this product?')" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="tab" value="products">
                                                    <button class="bg-red-500 text-white px-2 py-1 rounded text-xs hover:bg-red-600"
                                                        title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                    <div class="flex justify-between items-center mt-4 text-sm font-semibold text-black">
                <div>Showing {{ $products->firstItem() ?? 0 }} to {{ $products->lastItem() ?? 0 }} of
                    {{ $products->total() }} entries</div>
                <div class="inline-flex border border-gray-400 rounded overflow-hidden">
                    @if ($products->onFirstPage())
                        <button disabled class="px-3 py-1 text-gray-400 cursor-not-allowed border-r">Previous</button>
                    @else
                        <a href="{{ $products->appends(request()->except(['productsPage']))->previousPageUrl() }}"
                            class="px-3 py-1 text-black hover:bg-gray-200 border-r">Previous</a>
                    @endif

                    @for ($i = 1; $i <= $products->lastPage(); $i++)
                        @if ($i == $products->currentPage())
                            <button class="px-3 py-1 bg-[#0f7ea0] text-white border-r">{{ $i }}</button>
                        @else
                            <a href="{{ $products->appends(array_merge(request()->except(['productsPage', 'page']), ['productsPage' => $i, 'tab' => 'products']))->url($i) }}"
                                class="px-3 py-1 hover:bg-gray-200 border-r">{{ $i }}</a>
                        @endif
                    @endfor

                    @if ($products->hasMorePages())
                        <a href="{{ $products->appends(request()->except(['productsPage']))->nextPageUrl() }}"
                            class="px-3 py-1 text-black hover:bg-gray-200">Next</a>
                    @else
                        <button disabled class="px-3 py-1 text-gray-400 cursor-not-allowed">Next</button>
                    @endif
                </div>
            </div>

            
            </div>

            <div id="servicesTab" class="main-tab-content hidden">
                <div class="flex justify-between items-center mt-4 text-sm font-semibold text-black gap-2 flex-wrap">
                    <form method="GET" action="{{ request()->url() }}" class="flex items-center space-x-2">
                        <input type="hidden" name="tab" value="services">
                        <label for="servicesPerPage" class="text-sm text-black">Show</label>
                        <select name="servicesPerPage" id="servicesPerPage" onchange="this.form.submit()"
                            class="border border-gray-400 rounded px-2 py-1 text-sm">
                            @foreach ([10, 20, 50, 100, 'all'] as $limit)
                                <option value="{{ $limit }}" {{ request('servicesPerPage') == $limit ? 'selected' : '' }}>
                                    {{ $limit === 'all' ? 'All' : $limit }}
                                </option>
                            @endforeach
                        </select>
                        <span>entries</span>
                    </form>
                    <div class="flex gap-2 items-center flex-wrap">
                        <div class="relative">
                            <input type="search" id="servicesSearch" placeholder="Search services..." class="border border-gray-300 rounded px-3 py-2 text-sm pl-8">
                            <i class="fas fa-search absolute left-2 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        </div>
                        <button onclick="openServiceInventoryOverview()"
                            class="bg-purple-600 text-white text-sm px-4 py-2 rounded hover:bg-purple-700">
                            <i class="fas fa-pills mr-1"></i> Service Inventory Overview
                        </button>
                        @if(hasPermission('add_service', $can))
                            <button onclick="openAddModal('service')"
                                class="bg-[#0f7ea0] text-white text-sm px-4 py-2 rounded hover:bg-[#0c6a86]">
                                + Add Service
                            </button>
                        @endif
                    </div>
                </div>

                <br>

                <div class="overflow-x-auto">
                    <table class="w-full table-auto text-sm border text-center">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="p-2 border">Name</th>
                                <th class="p-2 border">Type</th>
                                <th class="p-2 border">Description</th>
                                <th class="p-2 border">Price</th>
                                <th class="p-2 border">Actions</th>
                            </tr>
                        </thead>
                       <tbody>
                            @foreach($services as $service)
                                <tr>
                                    <td class="p-2 border">{{ $service->serv_name }}</td>
                                    <td class="p-2 border">{{ $service->serv_type }}</td>
                                    <td class="p-2 border">{{ Str::limit($service->serv_description, 50) }}</td>
                                    <td class="p-2 border">₱{{ number_format($service->serv_price, 2) }}</td>
                                    <td class="p-2 border">
                                        <div class="flex justify-center gap-2">
                                            <button onclick="viewServiceDetails({{ $service->serv_id }})"
                                                class="bg-purple-500 text-white px-2 py-1 rounded hover:bg-purple-600 text-xs"
                                                title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            {{-- ✅ NEW ADDITION: Dedicated button in the actions column --}}
                                            @if(hasPermission('add_product', $can))
                                            <button onclick="openAddProductModal('service_product_inline', {{ $service->serv_id }})"
                                                class="bg-blue-600 text-white px-2 py-1 rounded hover:bg-blue-700 text-xs"
                                                title="Add Consumable Product for Service">
                                                <i class="fas fa-flask"></i>
                                            </button>
                                            @endif
                                            {{-- END NEW ADDITION --}}

                                            <button
                                                onclick="openManageProductsModal({{ $service->serv_id }}, '{{ addslashes($service->serv_name) }}')"
                                                class="bg-indigo-500 text-white px-2 py-1 rounded hover:bg-indigo-600 text-xs"
                                                title="Manage Consumables">
                                                <i class="fas fa-pills"></i>
                                            </button>
                                            
                                            @if(hasPermission('edit_service', $can))
                                                <button onclick="openEditModal('service', {{ json_encode($service) }})"
                                                    class="bg-[#0f7ea0] text-white px-2 py-1 rounded hover:bg-[#0c6a86] text-xs">
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                            @endif

                                            @if(hasPermission('delete_service', $can))
                                                <form action="{{ route('services.destroy', $service->serv_id) }}" method="POST"
                                                    onsubmit="return confirm('Delete this service?')" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    {{-- ✅ FIX: Add tab field --}}
                                                    <input type="hidden" name="tab" value="services">
                                                    <button
                                                        class="bg-red-500 text-white px-2 py-1 rounded text-xs hover:bg-red-600">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="flex justify-between items-center mt-4 text-sm font-semibold text-black">
                        <div>Showing {{ $services->firstItem() ?? 0 }} to {{ $services->lastItem() ?? 0 }} of
                            {{ $services->total() }} entries</div>
                        <div class="inline-flex border border-gray-400 rounded overflow-hidden">
                            @if ($services->onFirstPage())
                                <button disabled class="px-3 py-1 text-gray-400 cursor-not-allowed border-r">Previous</button>
                            @else
                                <a href="{{ $services->appends(request()->except(['servicesPage']))->previousPageUrl() }}"
                                    class="px-3 py-1 text-black hover:bg-gray-200 border-r">Previous</a>
                            @endif

                            @for ($i = 1; $i <= $services->lastPage(); $i++)
                                @if ($i == $services->currentPage())
                                    <button class="px-3 py-1 bg-[#0f7ea0] text-white border-r">{{ $i }}</button>
                                @else
                                    <a href="{{ $services->appends(array_merge(request()->except(['servicesPage', 'page']), ['servicesPage' => $i, 'tab' => 'services']))->url($i) }}"
                                        class="px-3 py-1 hover:bg-gray-200 border-r">{{ $i }}</a>
                                @endif
                            @endfor

                            @if ($services->hasMorePages())
                                <a href="{{ $services->appends(request()->except(['servicesPage']))->nextPageUrl() }}"
                                    class="px-3 py-1 text-black hover:bg-gray-200">Next</a>
                            @else
                                <button disabled class="px-3 py-1 text-gray-400 cursor-not-allowed">Next</button>
                            @endif
                        </div>
                    </div>

                </div>
            </div>

            <div id="equipmentTab" class="main-tab-content hidden">
                <div class="flex justify-between items-center mt-4 text-sm font-semibold text-black gap-2 flex-wrap">
                    <form method="GET" action="{{ request()->url() }}" class="flex items-center space-x-2">
                        <input type="hidden" name="tab" value="equipment">
                        <label for="equipmentPerPage" class="text-sm text-black">Show</label>
                        <select name="equipmentPerPage" id="equipmentPerPage" onchange="this.form.submit()"
                            class="border border-gray-400 rounded px-2 py-1 text-sm">
                            @foreach ([10, 20, 50, 100, 'all'] as $limit)
                                <option value="{{ $limit }}" {{ request('equipmentPerPage') == $limit ? 'selected' : '' }}>
                                    {{ $limit === 'all' ? 'All' : $limit }}
                                </option>
                            @endforeach
                        </select>
                        <span>entries</span>
                    </form>
                    <div class="flex gap-2 items-center flex-wrap">
                        <div class="relative">
                            <input type="search" id="equipmentSearch" placeholder="Search equipment..." class="border border-gray-300 rounded px-3 py-2 text-sm pl-8">
                            <i class="fas fa-search absolute left-2 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        </div>
                        @if(hasPermission('add_equipment', $can))
                            <button onclick="openAddModal('equipment')"
                                class="bg-[#0f7ea0] text-white text-sm px-4 py-2 rounded hover:bg-[#0c6a86]">
                                + Add Equipment
                            </button>
                        @endif
                    </div>
                </div>
                <br>
                <div class="overflow-x-auto">
                    <table class="w-full table-auto text-sm border text-center">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="p-2 border">Image</th>
                                <th class="p-2 border">Name</th>
                                <th class="p-2 border">Category</th>
                                <th class="p-2 border">Description</th>
                                <th class="p-2 border">Quantity</th>
                                <th class="p-2 border">Status</th>
                                <th class="p-2 border">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($equipment as $equip)
                                <tr>
                                    <td class="p-2 border">
                                        @if($equip->equipment_image)
                                            <img src="{{ asset('storage/' . $equip->equipment_image) }}"
                                                class="h-12 w-12 object-cover mx-auto rounded">
                                        @else
                                            <span class="text-gray-400">No Image</span>
                                        @endif
                                    </td>
                                    <td class="p-2 border">{{ $equip->equipment_name }}</td>
                                    <td class="p-2 border">{{ $equip->equipment_category ?? 'N/A'}}</td>
                                    <td class="p-2 border">{{ Str::limit($equip->equipment_description ?? 'N/A', 50) }}</td>
                                    <td class="p-2 border">{{ $equip->equipment_quantity }}</td>
                                    <td class="p-2 border">
                                        @php
                                            $statusClass = match ($equip->equipment_status ?? 'Available') {
                                                'Available' => 'bg-green-100 text-green-800',
                                                'In Use' => 'bg-blue-100 text-blue-800',
                                                'Under Maintenance' => 'bg-yellow-100 text-yellow-800',
                                                'Out of Service' => 'bg-red-100 text-red-800',
                                                default => 'bg-gray-100 text-gray-800'
                                            };
                                        @endphp
                                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $statusClass }}">
                                            {{ $equip->equipment_status ?? 'Available' }}
                                        </span>
                                    </td>
                                    <td class="p-2 border">
                                        <div class="flex justify-center gap-2">
                                            <button onclick="viewEquipmentDetails({{ $equip->equipment_id }})"
                                                class="bg-purple-500 text-white px-2 py-1 rounded hover:bg-purple-600 text-xs"
                                                title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            {{-- ✅ NEW: Update Status Button --}}
                                            <button onclick="openUpdateStatusModal({{ json_encode($equip) }})"
                                                class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 text-xs"
                                                title="Update Status">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>

                                            @if(hasPermission('edit_equipment', $can))
                                                <button onclick="openEditModal('equipment', {{ json_encode($equip) }})"
                                                    class="bg-[#0f7ea0] text-white px-2 py-1 rounded hover:bg-[#0c6a86] text-xs">
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                            @endif

                                            @if(hasPermission('delete_equipment', $can))
                                                <form action="{{ route('equipment.destroy', $equip->equipment_id) }}" method="POST"
                                                    onsubmit="return confirm('Delete this equipment?')" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    {{-- ✅ FIX: Add tab field --}}
                                                    <input type="hidden" name="tab" value="equipment">
                                                    <button type="submit"
                                                        class="bg-red-500 text-white px-2 py-1 rounded text-xs hover:bg-red-600">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex justify-between items-center mt-4 text-sm font-semibold text-black">
            <div>Showing {{ $equipment->firstItem() ?? 0 }} to {{ $equipment->lastItem() ?? 0 }} of
                {{ $equipment->total() }} entries</div>
            <div class="inline-flex border border-gray-400 rounded overflow-hidden">
                @if ($equipment->onFirstPage())
                    <button disabled class="px-3 py-1 text-gray-400 cursor-not-allowed border-r">Previous</button>
                @else
                    <a href="{{ $equipment->appends(request()->except(['equipmentPage']))->previousPageUrl() }}"
                        class="px-3 py-1 text-black hover:bg-gray-200 border-r">Previous</a>
                @endif

                @for ($i = 1; $i <= $equipment->lastPage(); $i++)
                    @if ($i == $equipment->currentPage())
                        <button class="px-3 py-1 bg-[#0f7ea0] text-white border-r">{{ $i }}</button>
                    @else
                        <a href="{{ $equipment->appends(array_merge(request()->except(['equipmentPage', 'page']), ['equipmentPage' => $i, 'tab' => 'equipment']))->url($i) }}"
                            class="px-3 py-1 hover:bg-gray-200 border-r">{{ $i }}</a>
                    @endif
                @endfor

                @if ($equipment->hasMorePages())
                    <a href="{{ $equipment->appends(request()->except(['equipmentPage']))->nextPageUrl() }}"
                        class="px-3 py-1 text-black hover:bg-gray-200">Next</a>
                @else
                    <button disabled class="px-3 py-1 text-gray-400 cursor-not-allowed">Next</button>
                @endif
            </div>
        </div>
            </div>
            
        </div>
        
    </div>

    <div id="updateEquipmentStatusModal" class="hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
    <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
        <h3 class="text-lg font-bold mb-4">Update Equipment Status</h3>
        <form id="updateEquipmentStatusForm" method="POST">
            @csrf
            @method('PUT') <input type="hidden" name="equipment_id" id="statusEquipmentId">
            <input type="hidden" name="tab" value="equipment"> 

                <div class="mb-4 bg-gray-50 p-3 rounded">
                    <div class="text-sm text-gray-600">Equipment: <span id="statusEquipmentName" class="font-bold"></span></div>
                    <div class="text-sm text-gray-600">Current Status: <span id="currentEquipmentStatus" class="font-bold"></span></div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">New Status</label>
                    <select name="equipment_status" id="newEquipmentStatus" class="border p-2 w-full rounded" required>
                        <option value="Available">Available</option>
                        <option value="In Use">In Use</option>
                        <option value="Under Maintenance">Under Maintenance</option>
                        <option value="Out of Service">Out of Service</option>
                    </select>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeUpdateStatusModal()"
                        class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Update Status</button>
                </div>
            </form>
        </div>
    </div>


    <div id="inventoryModal" class="hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-7xl max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold">Inventory Overview</h3>
                <button onclick="closeInventoryModal()" class="text-gray-500 hover:text-gray-700 text-xl">&times;</button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-red-50 p-4 rounded-lg border-l-4 border-red-500">
                    <h4 class="font-semibold text-red-800">Expired Items</h4>
                    <p class="text-2xl font-bold text-red-600">
                        {{ $products->filter(function ($p) {
        return $p->prod_expiry && \Carbon\Carbon::parse($p->prod_expiry)->isPast();
    })->count() }}
                    </p>
                </div>
                <div class="bg-yellow-50 p-4 rounded-lg border-l-4 border-yellow-500">
                    <h4 class="font-semibold text-yellow-800">Expiring Soon</h4>
                    <p class="text-2xl font-bold text-yellow-600">
                        {{ $products->filter(function ($p) {
        return $p->prod_expiry && \Carbon\Carbon::parse($p->prod_expiry)->diffInDays(now()) <= 30 && !\Carbon\Carbon::parse($p->prod_expiry)->isPast();
    })->count() }}
                    </p>
                </div>
                <div class="bg-orange-50 p-4 rounded-lg border-l-4 border-orange-500">
                    <h4 class="font-semibold text-orange-800">Low Stock</h4>
                    <p class="text-2xl font-bold text-orange-600">
                        {{ $products->filter(function ($p) {
        return $p->prod_stocks <= ($p->prod_reorderlevel ?? 10); })->count() }}
                    </p>
                </div>
                <div class="bg-green-50 p-4 rounded-lg border-l-4 border-green-500">
                    <h4 class="font-semibold text-green-800">Good Stock</h4>
                    <p class="text-2xl font-bold text-green-600">
                        {{ $products->filter(function ($p) {
        return $p->prod_stocks > ($p->prod_reorderlevel ?? 10) && (!$p->prod_expiry || \Carbon\Carbon::parse($p->prod_expiry)->diffInDays(now()) > 30);
    })->count() }}
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full table-auto text-sm border text-center">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-2 border">Product Name</th>
                            <th class="p-2 border">Current Stock</th>
                            <th class="p-2 border">Reorder Level</th>
                            <th class="p-2 border">Damaged</th>
                            <th class="p-2 border">Pull-Out</th>
                            <th class="p-2 border">Expiry Date</th>
                            <th class="p-2 border">Status</th>
                            <th class="p-2 border">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $product)
                            @php
                                $status = 'good';
                                $statusText = 'Good Stock';
                                $statusClass = 'bg-green-100 text-green-800';

                                if ($product->prod_expiry && \Carbon\Carbon::parse($product->prod_expiry)->isPast()) {
                                    $status = 'expired';
                                    $statusText = 'Expired';
                                    $statusClass = 'bg-red-100 text-red-800';
                                } elseif ($product->prod_expiry && \Carbon\Carbon::parse($product->prod_expiry)->diffInDays(now()) <= 30) {
                                    $status = 'warning';
                                    $statusText = 'Expiring Soon';
                                    $statusClass = 'bg-yellow-100 text-yellow-800';
                                } elseif ($product->prod_stocks <= ($product->prod_reorderlevel ?? 10)) {
                                    $status = 'low';
                                    $statusText = 'Low Stock';
                                    $statusClass = 'bg-orange-100 text-orange-800';
                                }
                            @endphp
                            <tr>
                                <td class="p-2 border font-medium">{{ $product->prod_name }}</td>
                                <td class="p-2 border">{{ $product->prod_stocks ?? 0 }}</td>
                                <td class="p-2 border">{{ $product->prod_reorderlevel ?? 'N/A' }}</td>
                                <td class="p-2 border">
                                    <span class="{{ ($product->prod_damaged ?? 0) > 0 ? 'text-red-600' : '' }}">
                                        {{ $product->prod_damaged ?? 0 }}
                                    </span>
                                </td>
                                <td class="p-2 border">
                                    <span class="{{ ($product->prod_pullout ?? 0) > 0 ? 'text-orange-600' : '' }}">
                                        {{ $product->prod_pullout ?? 0 }}
                                    </span>
                                </td>
                                <td class="p-2 border">
                                    @if($product->prod_expiry)
                                        @php
                                            $expiryDate = \Carbon\Carbon::parse($product->prod_expiry);
                                            $daysUntilExpiry = now()->diffInDays($expiryDate, false);
                                        @endphp
                                        <span>{{ $expiryDate->format('M d, Y') }}</span>
                                        @if($daysUntilExpiry < 0 || $expiryDate->isToday())
                                            <br><small class="text-red-600">(Expired)</small>
                                        @elseif($daysUntilExpiry <= 30)
                                            <br><small class="text-yellow-600">({{ $daysUntilExpiry }} days left)</small>
                                        @endif
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="p-2 border">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium {{ $statusClass }}">
                                        {{ $statusText }}
                                    </span>
                                </td>
                                <td class="p-2 border">
                                    <button onclick="viewInventoryHistory({{ $product->prod_id }})"
                                        class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 text-xs"
                                        title="View History">
                                        <i class="fas fa-history"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="manageProductsModal" class="hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-4xl max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold">Manage Products for Service</h3>
                <p class="text-sm text-gray-600" id="serviceNameDisplay"></p>
                <button onclick="closeManageProductsModal()"
                    class="text-gray-500 hover:text-gray-700 text-xl">&times;</button>
            </div>

            <input type="hidden" id="currentServiceId">

            <div class="bg-gray-50 p-4 rounded-lg mb-4">
                <h4 class="font-semibold mb-3">Add Product to Service</h4>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Select Product</label>
                        <select id="productSelect" class="border p-2 w-full rounded">
                            <option value="">-- Select Product --</option>
                            @foreach($allProducts as $product)
                                <option value="{{ $product->prod_id }}" data-name="{{ $product->prod_name }}"
                                    data-stock="{{ $product->prod_stocks }}" data-category="{{ $product->prod_category }}">
                                    {{ $product->prod_name }} (Stock: {{ $product->prod_stocks }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Quantity Used</label>
                        <input type="number" id="quantityUsed" step="0.01" min="0.01" value="1.00"
                            class="border p-2 w-full rounded" placeholder="1.00">
                    </div>
                    <div class="flex items-end">
                        <button onclick="addProductToService()"
                            class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 w-full">
                            <i class="fas fa-plus mr-1"></i> Add
                        </button>
                    </div>
                </div>
            </div>

            <div>
                <h4 class="font-semibold mb-3">Products Linked to This Service</h4>
                <div id="serviceProductsList" class="space-y-2">
                    </div>
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <button onclick="closeManageProductsModal()"
                    class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Close</button>
                <button onclick="saveServiceProducts()"
                    class="px-4 py-2 bg-[#0f7ea0] text-white rounded hover:bg-[#0c6a86]">
                    <i class="fas fa-save mr-1"></i> Save Changes
                </button>
            </div>
        </div>
    </div>

    <div id="productDetailsModal" class="hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-6xl max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-white font-bold">Product Details</h3>
                <button onclick="closeProductDetailsModal()"
                    class="text-gray-500 hover:text-gray-700 text-xl">&times;</button>
            </div>
            <div id="productDetailsContent">
                </div>
        </div>
    </div>

    <div id="inventoryHistoryModal"
        class="hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-5xl max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold">Inventory History</h3>
                <button onclick="closeInventoryHistoryModal()"
                    class="text-gray-500 hover:text-gray-700 text-xl">&times;</button>
            </div>
            <div id="inventoryHistoryContent">
                </div>
        </div>
    </div>

    <div id="productModal" class="hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-4xl max-h-[90vh] overflow-y-auto">
            <h3 id="productModalTitle" class="text-lg font-bold mb-4"></h3>
            <form id="productModalForm" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="tab" value="products"> {{-- Always redirects to products tab --}}
                <div id="productModalFields" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>
                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" onclick="closeProductModal()"
                        class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-[#0f7ea0] text-white rounded hover:bg-[#0c6a86]">Save</button>
                </div>
            </form>
        </div>
    </div>

    <div id="updateStockModal" class="hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
            <h3 class="text-lg font-bold mb-4">Update Stock</h3>
            <form id="updateStockForm" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="product_id">
                <input type="hidden" name="tab" value="products"> {{-- Always redirects to products tab --}}

                <div class="mb-4 bg-gray-50 p-3 rounded">
                    <div class="text-sm text-gray-600">Current Stock: <span id="currentStock" class="font-bold"></span>
                    </div>
                    <div class="text-sm text-gray-600">Product: <span id="productName" class="font-bold"></span></div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Add Stock Quantity</label>
                    <input type="number" name="add_stock" placeholder="Enter quantity to add"
                        class="border p-2 w-full rounded" required min="1">
                    <small class="text-gray-500">This will be added to existing stock</small>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">New Stock Expiry Date</label>
                    <input type="date" name="new_expiry" class="border p-2 w-full rounded" required>
                    <small class="text-gray-500">Expiry date for the new stock being added</small>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Update Reorder Level</label>
                    <input type="number" name="reorder_level" placeholder="Enter reorder level"
                        class="border p-2 w-full rounded">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="notes" placeholder="Supplier, batch info, etc." class="border p-2 w-full rounded"
                        rows="2"></textarea>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeUpdateStockModal()"
                        class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Update
                        Stock</button>
                </div>
            </form>
        </div>
    </div>

    <div id="damagePulloutModal" class="hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
            <h3 class="text-lg font-bold mb-4">Update Damage/Pull-out</h3>
            <form id="damagePulloutForm" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="product_id">
                <input type="hidden" name="tab" value="products"> {{-- Always redirects to products tab --}}

                <div class="mb-4 bg-gray-50 p-3 rounded">
                    <div class="text-sm text-gray-600">Product: <span id="damageProductName" class="font-bold"></span></div>
                    <div class="text-sm text-gray-600">Current Stock: <span id="damageCurrentStock"
                                class="font-bold"></span></div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Damaged Quantity</label>
                    <input type="number" name="damaged_qty" placeholder="Enter damaged quantity"
                        class="border p-2 w-full rounded" min="0">
                    <small class="text-gray-500">Total damaged items</small>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pull-out Quantity</label>
                    <input type="number" name="pullout_qty" placeholder="Enter pull-out quantity"
                        class="border p-2 w-full rounded" min="0">
                    <small class="text-gray-500">Items pulled out for quality control</small>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                    <textarea name="reason" placeholder="Reason for damage/pull-out" class="border p-2 w-full rounded"
                        rows="2"></textarea>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeDamagePulloutModal()"
                        class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
                    <button type="submit"
                        class="px-4 py-2 bg-orange-600 text-white rounded hover:bg-orange-700">Update</button>
                </div>
            </form>
        </div>
    </div>

    <div id="generalModal" class="hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-4xl max-h-[90vh] overflow-y-auto">
            <h3 id="generalModalTitle" class="text-lg font-bold mb-4"></h3>
            <form id="generalModalForm" method="POST" enctype="multipart/form-data">
                @csrf
                {{-- FIX: Renamed 'active_tab' to 'tab' for consistency with URL --}}
                <input type="hidden" name="tab" id="active_tab" value=""> 
                <div id="generalModalFields" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>

                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" onclick="closeGeneralModal()"
                        class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-[#0f7ea0] text-white rounded hover:bg-[#0c6a86]">Save</button>
                </div>
            </form>
        </div>
    </div>

    <div id="serviceInventoryModal"
        class="hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-7xl max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold">Service Inventory Overview</h3>
                <button onclick="closeServiceInventoryModal()"
                    class="text-gray-500 hover:text-gray-700 text-xl">&times;</button>
            </div>

            <div id="serviceInventoryContent">
                </div>
        </div>
    </div>

    <script>

        // MAIN TAB SWITCHING
        function switchMainTab(tabId, tabParam) {
            document.querySelectorAll('.main-tab-content').forEach(t => t.classList.add('hidden'));
            document.getElementById(tabId).classList.remove('hidden');

            // Update button styles
            ['productInventoryBtn', 'servicesBtn', 'equipmentBtn'].forEach(id => {
                document.getElementById(id).classList.remove('border-[#0f7ea0]', 'text-[#0f7ea0]');
                document.getElementById(id).classList.add('border-transparent');
            });

            const activeBtn = document.getElementById(tabParam + 'Btn' in window ? tabParam + 'Btn' : tabId.replace('Tab', 'Btn'));
            if (activeBtn) {
                activeBtn.classList.remove('border-transparent');
                activeBtn.classList.add('border-[#0f7ea0]', 'text-[#0f7ea0]');
            }

            // Update URL without reloading (optional but better UX)
            const url = new URL(window.location);
            url.searchParams.set('tab', tabParam);
            window.history.pushState({}, '', url);
        }

        // Ensure modals return to the correct tab on redirect
        document.addEventListener('DOMContentLoaded', function () {
            const urlParams = new URLSearchParams(window.location.search);
            const activeTabParam = urlParams.get('tab') || 'products'; // Default to 'products'

            let tabId = 'productInventoryTab';
            let tabBtnId = 'productInventoryBtn';

            if (activeTabParam === 'services') {
                tabId = 'servicesTab';
                tabBtnId = 'servicesBtn';
            } else if (activeTabParam === 'equipment') {
                tabId = 'equipmentTab';
                tabBtnId = 'equipmentBtn';
            }

            switchMainTab(tabId, activeTabParam);

            // Set the hidden field in the general modal for redirect handling
            // FIX: Renamed here to 'tab' for consistency
            const activeTabInput = document.getElementById('active_tab');
            if (activeTabInput) {
                activeTabInput.value = activeTabParam.replace('Tab', '');
            }
        });

        // ... [MANAGE SERVICE PRODUCTS FUNCTIONS REMAIN UNCHANGED] ...

        // MANAGE SERVICE PRODUCTS
        let serviceProducts = [];

        function openManageProductsModal(serviceId, serviceName) {
            document.getElementById('manageProductsModal').classList.remove('hidden');
            document.getElementById('currentServiceId').value = serviceId;
            document.getElementById('serviceNameDisplay').textContent = serviceName;

            // Load existing products for this service
            loadServiceProducts(serviceId);
        }

        function closeManageProductsModal() {
            document.getElementById('manageProductsModal').classList.add('hidden');
            serviceProducts = [];
            document.getElementById('serviceProductsList').innerHTML = '';
            document.getElementById('productSelect').value = '';
            document.getElementById('quantityUsed').value = '1.00';
        }

        function loadServiceProducts(serviceId) {
            document.getElementById('serviceProductsList').innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Loading products...</div>';

            fetch(`/services/${serviceId}/products`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        serviceProducts = data.products;
                        renderServiceProducts();
                    } else {
                        document.getElementById('serviceProductsList').innerHTML = '<div class="text-red-500">Error loading products</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('serviceProductsList').innerHTML = '<div class="text-red-500">Error loading products</div>';
                });
        }

        function renderServiceProducts() {
            const container = document.getElementById('serviceProductsList');

            if (serviceProducts.length === 0) {
                container.innerHTML = '<div class="text-center text-gray-500 py-4">No products linked to this service yet.</div>';
                return;
            }

            let html = '<div class="space-y-2">';
            serviceProducts.forEach((item, index) => {
                const stockWarning = item.current_stock < item.quantity_used ? 'border-red-300 bg-red-50' : 'border-gray-200';
                html += `
                <div class="flex items-center justify-between p-3 border ${stockWarning} rounded">
                    <div class="flex-1">
                        <div class="font-medium">${item.product_name}</div>
                        <div class="text-sm text-gray-600">
                            Quantity Used: <span class="font-semibold">${item.quantity_used}</span> | 
                            Current Stock: <span class="${item.current_stock < item.quantity_used ? 'text-red-600 font-bold' : 'text-green-600'}">${item.current_stock}</span>
                            ${item.current_stock < item.quantity_used ? '<span class="text-red-600 ml-2"><i class="fas fa-exclamation-triangle"></i> Low Stock!</span>' : ''}
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="number" step="0.01" min="0.01" value="${item.quantity_used}" 
                               onchange="updateProductQuantity(${index}, this.value)"
                               class="border p-1 w-20 rounded text-sm">
                        <button onclick="removeProductFromService(${index})" 
                                class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 text-sm">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>`;
            });
            html += '</div>';

            container.innerHTML = html;
        }

        function addProductToService() {
            const select = document.getElementById('productSelect');
            const prodId = select.value;
            const quantityUsed = parseFloat(document.getElementById('quantityUsed').value);

            if (!prodId) {
                alert('Please select a product');
                return;
            }

            if (!quantityUsed || quantityUsed <= 0) {
                alert('Please enter a valid quantity');
                return;
            }

            // Check if product already exists
            if (serviceProducts.find(p => p.prod_id == prodId)) {
                alert('This product is already linked to this service');
                return;
            }

            const selectedOption = select.options[select.selectedIndex];

            serviceProducts.push({
                prod_id: prodId,
                product_name: selectedOption.dataset.name,
                quantity_used: quantityUsed,
                current_stock: selectedOption.dataset.stock,
                is_billable: false
            });

            // Reset form
            select.value = '';
            document.getElementById('quantityUsed').value = '1.00';

            renderServiceProducts();
        }

        function updateProductQuantity(index, newQuantity) {
            serviceProducts[index].quantity_used = parseFloat(newQuantity);
        }

        function removeProductFromService(index) {
            if (confirm('Remove this product from the service?')) {
                serviceProducts.splice(index, 1);
                renderServiceProducts();
            }
        }

        function saveServiceProducts() {
            const serviceId = document.getElementById('currentServiceId').value;

            if (serviceProducts.length === 0) {
                if (!confirm('No products are linked to this service. Continue?')) {
                    return;
                }
            }

            // Prepare data
            const productsData = serviceProducts.map(p => ({
                prod_id: p.prod_id,
                quantity_used: p.quantity_used,
                is_billable: p.is_billable || false
            }));

            // Show loading
            const saveBtn = event.target;
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Saving...';
            saveBtn.disabled = true;

            fetch(`/services/${serviceId}/products`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    products: productsData
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ Products saved successfully!');
                        closeManageProductsModal();
                    } else {
                        alert('❌ Error: ' + (data.error || 'Failed to save products'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('❌ Error saving products. Please try again.');
                })
                .finally(() => {
                    saveBtn.innerHTML = originalText;
                    saveBtn.disabled = false;
                });
        }
        // MAIN TAB SWITCHING
        function switchMainTab(tabId) {
            document.querySelectorAll('.main-tab-content').forEach(t => t.classList.add('hidden'));
            document.getElementById(tabId).classList.remove('hidden');
            ['productInventoryBtn', 'servicesBtn', 'equipmentBtn'].forEach(id => {
                document.getElementById(id).classList.remove('border-[#0f7ea0]', 'text-[#0f7ea0]');
                document.getElementById(id).classList.add('border-transparent');
            });

            if (tabId === 'productInventoryTab') {
                document.getElementById('productInventoryBtn').classList.remove('border-transparent');
                document.getElementById('productInventoryBtn').classList.add('border-[#0f7ea0]', 'text-[#0f7ea0]');
            }
            if (tabId === 'servicesTab') {
                document.getElementById('servicesBtn').classList.remove('border-transparent');
                document.getElementById('servicesBtn').classList.add('border-[#0f7ea0]', 'text-[#0f7ea0]');
            }
            if (tabId === 'equipmentTab') {
                document.getElementById('equipmentBtn').classList.remove('border-transparent');
                document.getElementById('equipmentBtn').classList.add('border-[#0f7ea0]', 'text-[#0f7ea0]');
            }
        }

        function openServiceInventoryOverview() {
            document.getElementById('serviceInventoryModal').classList.remove('hidden');
            document.getElementById('serviceInventoryContent').innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-3xl text-blue-600"></i><p class="mt-2">Loading service inventory overview...</p></div>';

            fetch('/services/inventory-overview')
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        document.getElementById('serviceInventoryContent').innerHTML = '<div class="text-red-500">Error loading data</div>';
                        return;
                    }

                    const products = data.products;
                    const summary = data.summary;

                    let content = `
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-500">
                            <h4 class="font-semibold text-blue-800">Products in Services</h4>
                            <p class="text-2xl font-bold text-blue-600">${summary.total_products_in_services}</p>
                        </div>
                        <div class="bg-red-50 p-4 rounded-lg border-l-4 border-red-500">
                            <h4 class="font-semibold text-red-800">Low Stock Items</h4>
                            <p class="text-2xl font-bold text-red-600">${summary.low_stock_count}</p>
                        </div>
                        <div class="bg-yellow-50 p-4 rounded-lg border-l-4 border-yellow-500">
                            <h4 class="font-semibold text-yellow-800">Warning Stock</h4>
                            <p class="text-2xl font-bold text-yellow-600">${summary.warning_stock_count}</p>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg border-l-4 border-green-500">
                            <h4 class="font-semibold text-green-800">Good Stock</h4>
                            <p class="text-2xl font-bold text-green-600">${summary.good_stock_count}</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm border">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="p-2 border">Product Name</th>
                                    <th class="p-2 border">Category</th>
                                    <th class="p-2 border">Current Stock</th>
                                    <th class="p-2 border">Reorder Level</th>
                                    <th class="p-2 border">Services Using</th>
                                    <th class="p-2 border">Times Used</th>
                                    <th class="p-2 border">Services Remaining</th>
                                    <th class="p-2 border">Expiry Date</th>
                                    <th class="p-2 border">Status</th>
                                </tr>
                            </thead>
                            <tbody>`;

                    if (products.length === 0) {
                        content += `
                        <tr>
                            <td colspan="9" class="p-8 text-center text-gray-500">
                                <i class="fas fa-info-circle text-3xl mb-2"></i>
                                <p>No products are currently linked to any services.</p>
                                <p class="text-sm mt-2">Use the "Manage Products" button (purple pill icon) next to each service to add products.</p>
                            </td>
                        </tr>`;
                    } else {
                        products.forEach(product => {
                            const stockColorClass = product.current_stock <= product.reorder_level ? 'text-red-600 font-bold' : 'text-green-600';

                            // Build services list
                            let servicesList = '<div class="space-y-1">';
                            product.services_using.forEach(service => {
                                servicesList += `
                                <div class="flex items-center justify-between text-xs bg-purple-50 p-1 rounded">
                                    <span class="font-medium">${service.service_name}</span>
                                    <span class="text-purple-700 font-bold">${service.quantity_used} units</span>
                                </div>`;
                            });
                            servicesList += '</div>';

                            // Build services remaining
                            let remainingList = '<div class="space-y-1">';
                            product.services_remaining.forEach(remaining => {
                                const colorClass = remaining.remaining_count < 5 ? 'text-red-600' : remaining.remaining_count < 20 ? 'text-yellow-600' : 'text-green-600';
                                remainingList += `
                                <div class="text-xs">
                                    <span class="text-gray-600">${remaining.service_name}:</span>
                                    <span class="${colorClass} font-bold">${remaining.remaining_count}x</span>
                                </div>`;
                            });
                            remainingList += '</div>';

                            content += `
                            <tr class="hover:bg-gray-50">
                                <td class="p-2 border font-medium">${product.product_name}</td>
                                <td class="p-2 border text-xs">${product.product_category}</td>
                                <td class="p-2 border">
                                    <span class="${stockColorClass} text-lg font-bold">${product.current_stock}</span>
                                </td>
                                <td class="p-2 border">${product.reorder_level}</td>
                                <td class="p-2 border">
                                    ${servicesList}
                                </td>
                                <td class="p-2 border text-center">
                                    <span class="text-purple-700 font-bold text-lg">${product.actual_usage_count}</span>
                                    <div class="text-xs text-gray-500">total times</div>
                                </td>
                                <td class="p-2 border">
                                    ${remainingList}
                                </td>
                                <td class="p-2 border text-xs">${product.expiry_date}</td>
                                <td class="p-2 border">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium ${product.status_class}">
                                        ${product.stock_status.charAt(0).toUpperCase() + product.stock_status.slice(1)}
                                    </span>
                                </td>
                            </tr>`;
                        });
                    }

                    content += `
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                        <h4 class="font-bold mb-2">Legend:</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2 text-sm">
                            <div><span class="font-medium">Services Using:</span> Shows which services use this product and how much per service</div>
                            <div><span class="font-medium">Times Used:</span> Total number of times this product was actually used in appointments</div>
                            <div><span class="font-medium">Services Remaining:</span> How many more services can be performed with current stock</div>
                        </div>
                    </div>`;

                    document.getElementById('serviceInventoryContent').innerHTML = content;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('serviceInventoryContent').innerHTML = '<div class="text-red-500">Error loading service inventory overview</div>';
                });
        }

        function closeServiceInventoryModal() {
            document.getElementById('serviceInventoryModal').classList.add('hidden');
        }

        // INVENTORY OVERVIEW MODAL
        function openInventoryOverview() {
            document.getElementById('inventoryModal').classList.remove('hidden');
        }

        function closeInventoryModal() {
            document.getElementById('inventoryModal').classList.add('hidden');
        }

        // PRODUCT DETAILS MODAL
        function viewProductDetails(productId) {
            document.getElementById('productDetailsModal').classList.remove('hidden');
            document.getElementById('productDetailsContent').innerHTML = '<div class="text-center py-8"><div class="spinner-border animate-spin inline-block w-8 h-8 border-4 rounded-full" role="status"></div><p>Loading product details...</p></div>';

            fetch(`/products/${productId}/view`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        document.getElementById('productDetailsContent').innerHTML = `<div class="text-red-500">${data.error}</div>`;
                        return;
                    }

                    const product = data.product;
                    const salesData = data.sales_data;
                    const monthlySales = data.monthly_sales;
                    const recentOrders = data.recent_orders;
                    const profitData = data.profit_data;

                    let content = `
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-bold text-lg mb-3">Product Information</h4>
                            <div class="space-y-2">
                                ${product.prod_image ? `<div class="mb-3"><img src="/storage/${product.prod_image}" class="h-32 w-32 object-cover rounded mx-auto"></div>` : ''}
                                <div><span class="font-medium">Name:</span> ${product.prod_name}</div>
                                <div><span class="font-medium">Category:</span> ${product.prod_category || 'N/A'}</div>
                                <div><span class="font-medium">Description:</span> ${product.prod_description || 'N/A'}</div>
                                <div><span class="font-medium">Price:</span> ₱${parseFloat(product.prod_price).toLocaleString(undefined, { minimumFractionDigits: 2 })}</div>
                                <div><span class="font-medium">Branch:</span> ${product.branch ? product.branch.branch_name : 'N/A'}</div>
                            </div>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-bold text-lg mb-3">Inventory Status</h4>
                            <div class="space-y-2">
                                <div><span class="font-medium">Current Stock:</span> <span class="text-lg font-bold ${product.prod_stocks <= (product.prod_reorderlevel || 10) ? 'text-red-600' : 'text-green-600'}">${product.prod_stocks || 0}</span></div>
                                <div><span class="font-medium">Reorder Level:</span> ${product.prod_reorderlevel || 'Not set'}</div>
                                <div><span class="font-medium">Damaged:</span> <span class="text-red-600">${product.prod_damaged || 0}</span></div>
                                <div><span class="font-medium">Pull-out:</span> <span class="text-orange-600">${product.prod_pullout || 0}</span></div>
                                <div><span class="font-medium">Expiry Date:</span> ${product.prod_expiry ? new Date(product.prod_expiry).toLocaleDateString() : 'N/A'}</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <h4 class="font-bold text-lg mb-3">Sales Analytics</h4>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <div class="text-sm text-blue-600">Total Orders</div>
                                <div class="text-2xl font-bold text-blue-800">${salesData.total_orders || 0}</div>
                            </div>
                            <div class="bg-green-50 p-4 rounded-lg">
                                <div class="text-sm text-green-600">Quantity Sold</div>
                                <div class="text-2xl font-bold text-green-800">${salesData.total_quantity_sold || 0}</div>
                            </div>
                            <div class="bg-purple-50 p-4 rounded-lg">
                                <div class="text-sm text-purple-600">Total Revenue</div>
                                <div class="text-2xl font-bold text-purple-800">₱${parseFloat(salesData.total_revenue || 0).toLocaleString(undefined, { minimumFractionDigits: 2 })}</div>
                            </div>
                            <div class="bg-yellow-50 p-4 rounded-lg">
                                <div class="text-sm text-yellow-600">Avg. Order Value</div>
                                <div class="text-2xl font-bold text-yellow-800">₱${parseFloat(salesData.average_order_value || 0).toLocaleString(undefined, { minimumFractionDigits: 2 })}</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <h4 class="font-bold text-lg mb-3">Recent Orders</h4>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm border">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="p-2 border">Date</th>
                                        <th class="p-2 border">Quantity</th>
                                        <th class="p-2 border">Total</th>
                                        <th class="p-2 border">Customer</th>
                                    </tr>
                                </thead>
                                <tbody>`;

                    if (recentOrders && recentOrders.length > 0) {
                        recentOrders.forEach(order => {
                            const orderDate = order.ord_date ? new Date(order.ord_date) : new Date();
                            const customerName = order.customer_name || order.user_name || 'Walk-in Customer';
                            content += `
                            <tr>
                                <td class="p-2 border">${orderDate.toLocaleDateString()}</td>
                                <td class="p-2 border">${order.ord_quantity}</td>
                                <td class="p-2 border">₱${parseFloat(order.ord_total || (order.ord_quantity * order.prod_price) || 0).toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
                                <td class="p-2 border">${customerName}</td>
                            </tr>`;
                        });
                    } else {
                        content += '<tr><td colspan="4" class="p-4 text-center text-gray-500">No recent orders found</td></tr>';
                    }

                    content += `
                                </tbody>
                            </table>
                        </div>
                    </div>`;

                    document.getElementById('productDetailsContent').innerHTML = content;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('productDetailsContent').innerHTML = '<div class="text-red-500">Error loading product details</div>';
                });
        }

        function closeProductDetailsModal() {
            document.getElementById('productDetailsModal').classList.add('hidden');
        }

        // INVENTORY HISTORY MODAL
        function viewInventoryHistory(productId) {
            document.getElementById('inventoryHistoryModal').classList.remove('hidden');
            document.getElementById('inventoryHistoryContent').innerHTML = '<div class="text-center py-8"><div class="spinner-border animate-spin inline-block w-8 h-8 border-4 rounded-full" role="status"></div><p>Loading inventory history...</p></div>';

            // Fetch both inventory history AND service usage
            Promise.all([
                fetch(`/inventory/${productId}/history`).then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                }),
                fetch(`/products/${productId}/service-usage`).then(response => {
                    if (!response.ok) {
                        console.warn('Failed to load service usage data, continuing without it');
                        return { services_using_product: [], recent_service_usage: [], total_used_in_services: 0 };
                    }
                    return response.json();
                })
            ])
                .then(([historyData, serviceData]) => {
                    if (historyData.error) {
                        throw new Error(historyData.error);
                    }
                    
                    // If service data has an error, we'll just log it but continue with empty data
                    if (serviceData.error) {
                        console.warn('Error in service usage data:', serviceData.error);
                        serviceData = { services_using_product: [], recent_service_usage: [], total_used_in_services: 0 };
                    }

                    const product = historyData.product;
                    const stockHistory = historyData.stock_history;
                    const damageAnalysis = historyData.damage_analysis;
                    const expiryData = historyData.expiry_data;
                    const stockAnalytics = historyData.stock_analytics;

                    const servicesUsing = serviceData.services_using_product;
                    const recentServiceUsage = serviceData.recent_service_usage;
                    const totalUsedInServices = serviceData.total_used_in_services;

                    let content = `
                <div class="mb-6">
                    <h4 class="font-bold text-lg mb-3">Product: ${product.prod_name}</h4>

                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <div class="text-sm text-blue-600">Current Stock</div>
                            <div class="text-2xl font-bold text-blue-800">${stockAnalytics.current_stock}</div>
                        </div>
                        <div class="bg-yellow-50 p-4 rounded-lg">
                            <div class="text-sm text-yellow-600">Reorder Level</div>
                            <div class="text-2xl font-bold text-yellow-800">${stockAnalytics.reorder_level || 'N/A'}</div>
                        </div>
                        <div class="bg-red-50 p-4 rounded-lg">
                            <div class="text-sm text-red-600">Damaged Items</div>
                            <div class="text-2xl font-bold text-red-800">${damageAnalysis.total_damaged}</div>
                        </div>
                        <div class="bg-purple-50 p-4 rounded-lg">
                            <div class="text-sm text-purple-600">Used in Services</div>
                            <div class="text-2xl font-bold text-purple-800">${totalUsedInServices}</div>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg">
                            <div class="text-sm text-green-600">Days Until Reorder</div>
                            <div class="text-2xl font-bold text-green-800">${stockAnalytics.days_until_reorder}</div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <h5 class="font-bold mb-3 text-purple-700">
                            <i class="fas fa-pills mr-2"></i>Services Using This Product
                        </h5>
                        ${servicesUsing.length > 0 ? `
                            <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    ${servicesUsing.map(service => `
                                        <div class="flex items-center justify-between p-3 bg-white rounded border border-purple-100">
                                            <div>
                                                <div class="font-semibold text-purple-900">${service.service_name}</div>
                                                <div class="text-sm text-gray-600">${service.service_type}</div>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-lg font-bold text-purple-700">${service.quantity_used}</div>
                                                <div class="text-xs text-gray-500">per service</div>
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        ` : `
                            <div class="bg-gray-50 p-4 rounded-lg text-center text-gray-500">
                                <i class="fas fa-info-circle mr-2"></i>
                                This product is not currently used in any services
                            </div>
                        `}
                    </div>

                    ${recentServiceUsage.length > 0 ? `
                        <div class="mb-6">
                            <h5 class="font-bold mb-3">
                                <i class="fas fa-history mr-2"></i>Recent Service Usage
                            </h5>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm border">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="p-2 border">Date</th>
                                            <th class="p-2 border">Service</th>
                                            <th class="p-2 border">Pet</th>
                                            <th class="p-2 border">Owner</th>
                                            <th class="p-2 border">Quantity Used</th>
                                            <th class="p-2 border">Appointment</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${recentServiceUsage.map(usage => `
                                            <tr>
                                                <td class="p-2 border">${usage.date}</td>
                                                <td class="p-2 border font-medium">${usage.service_name}</td>
                                                <td class="p-2 border">${usage.pet_name}</td>
                                                <td class="p-2 border">${usage.owner_name}</td>
                                                <td class="p-2 border text-red-600 font-semibold">-${usage.quantity_used}</td>
                                                <td class="p-2 border">
                                                    <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">
                                                        #${usage.appointment_id}
                                                    </span>
                                                </td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    ` : ''}

                    <h5 class="font-bold mb-3">Complete Stock Movement History</h5>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm border">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="p-2 border">Date</th>
                                    <th class="p-2 border">Type</th>
                                    <th class="p-2 border">Quantity</th>
                                    <th class="p-2 border">Reference</th>
                                    <th class="p-2 border">User</th>
                                    <th class="p-2 border">Notes</th>
                                </tr>
                            </thead>
                            <tbody>`;

                    stockHistory.forEach(movement => {
                        const typeClass = movement.type === 'restock' ? 'text-green-600' :
                            movement.type === 'sale' ? 'text-blue-600' :
                                movement.type === 'service_usage' ? 'text-purple-600' :
                                    movement.type === 'damage' ? 'text-red-600' :
                                        movement.type === 'pullout' ? 'text-orange-600' : 'text-gray-600';

                        const movementDate = movement.date ? new Date(movement.date) : new Date();
                        // Ensure quantity is treated as a number and handle negative values for pullouts
                        const displayQuantity = typeof movement.quantity === 'number' ? movement.quantity : 
                                             (movement.quantity ? parseFloat(movement.quantity) : 0);

                        // Format the quantity with proper sign for display
                        const quantitySign = displayQuantity > 0 ? '+' : '';
                        const quantityClass = displayQuantity > 0 ? 'text-green-600' : 'text-red-600';
                        const displayType = movement.type === 'pullout' ? 'Pull-out' : 
                                         movement.type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());

                        content += `
                    <tr>
                        <td class="p-2 border">${movementDate.toLocaleDateString()}</td>
                        <td class="p-2 border"><span class="${typeClass} capitalize font-semibold">${displayType}</span></td>
                        <td class="p-2 border ${quantityClass} font-bold">${quantitySign}${displayQuantity}</td>
                        <td class="p-2 border">${movement.reference || 'N/A'}</td>
                        <td class="p-2 border">${movement.user || 'System'}</td>
                        <td class="p-2 border">${movement.notes || 'No notes'}</td>
                    </tr>`;
                    });

                    content += `
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        <h5 class="font-bold mb-3">Expiry Information</h5>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <span class="font-medium">Expiry Date:</span> 
                                    ${expiryData.expiry_date ? new Date(expiryData.expiry_date).toLocaleDateString() : 'N/A'}
                                </div>
                                <div>
                                    <span class="font-medium">Days Until Expiry:</span> 
                                    <span class="${expiryData.expiry_status === 'expired' ? 'text-red-600' : expiryData.expiry_status === 'warning' ? 'text-yellow-600' : 'text-green-600'}">
                                        ${expiryData.days_until_expiry !== null ? Math.abs(expiryData.days_until_expiry) + (expiryData.days_until_expiry < 0 ? ' (Expired)' : ' days') : 'N/A'}
                                    </span>
                                </div>
                                <div>
                                    <span class="font-medium">Status:</span> 
                                    <span class="capitalize ${expiryData.expiry_status === 'expired' ? 'text-red-600' : expiryData.expiry_status === 'warning' ? 'text-yellow-600' : 'text-green-600'}">
                                        ${expiryData.expiry_status}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;

                    document.getElementById('inventoryHistoryContent').innerHTML = content;
                })
                .catch(error => {
                    console.error('Error loading inventory history:', error);
                    const errorMessage = error.message || 'An unknown error occurred while loading inventory history';
                    document.getElementById('inventoryHistoryContent').innerHTML = `
                        <div class="p-4 bg-red-50 border-l-4 border-red-500">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-red-700">
                                        ${errorMessage}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 text-center">
                            <button onclick="viewInventoryHistory(${productId})" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                Retry
                            </button>
                        </div>`;
                });
        }
        function closeInventoryHistoryModal() {
            document.getElementById('inventoryHistoryModal').classList.add('hidden');
        }

        // PRODUCT MODAL FUNCTIONS
        function openAddProductModal() {
            document.getElementById('productModal').classList.remove('hidden');
            document.getElementById('productModalTitle').innerText = 'Add Product';

            let fields = `
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Product Name *</label>
                <input type="text" name="prod_name" placeholder="Enter product name" class="border p-2 w-full rounded" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Product Category</label>
                <select name="prod_category" class="border p-2 w-full rounded">
                    <option value="">Select Product Category</option>
                    <option value="Prescription Medicines">Prescription Medicines</option>
                    <option value="Vaccines">Vaccines</option>
                    <option value="Nutritional Products">Nutritional Products</option>
                    <option value="Parasite Control">Parasite Control</option>
                    <option value="Grooming Products">Grooming Products</option>
                    <option value="Pet Accessories">Pet Accessories</option>
                    <option value="Medical Supplies">Medical Supplies</option>
                    <option value="Pet Food & Treats">Pet Food & Treats</option>
                    <option value="Hygiene & Sanitation">Hygiene & Sanitation</option>
                    <option value="Pet Care Equipment">Pet Care Equipment</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Price *</label>
                <input type="number" step="0.01" name="prod_price" placeholder="Enter price" class="border p-2 w-full rounded" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Initial Stock</label>
                <input type="number" name="prod_stocks" placeholder="Enter initial stock" class="border p-2 w-full rounded" value="0">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Reorder Level</label>
                <input type="number" name="prod_reorderlevel" placeholder="Enter reorder level" class="border p-2 w-full rounded" value="10">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Product Expiry Date *</label>
                <input type="date" name="prod_expiry" class="border p-2 w-full rounded" required>
                <small class="text-gray-500">Required for all products</small>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Branch</label>
                <select name="branch_id" class="border p-2 w-full rounded">
                    <option value="">Select Branch</option>`;

            @foreach($branches as $branch)
                fields += `<option value="{{ $branch->branch_id }}">{{ $branch->branch_name }}</option>`;
            @endforeach

            fields += `
                </select>
            </div>
            <div class="mb-4 md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                <textarea name="prod_description" placeholder="Enter product description" class="border p-2 w-full rounded" rows="3" required></textarea>
            </div>
            <div class="mb-4 md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Product Image</label>
                <input type="file" name="prod_image" accept="image/*" class="border p-2 w-full rounded">
            </div>`;

            document.getElementById('productModalFields').innerHTML = fields;
            document.getElementById('productModalForm').action = '{{ route("products.store") }}';

            // Remove existing method field
            let methodField = document.querySelector('#productModalForm input[name="_method"]');
            if (methodField) methodField.remove();
        }

        function openEditProductModal(data) {
            document.getElementById('productModal').classList.remove('hidden');
            document.getElementById('productModalTitle').innerText = 'Edit Product';

            let fields = `
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Product Name *</label>
                <input type="text" name="prod_name" value="${escapeHtml(data.prod_name)}" class="border p-2 w-full rounded" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Product Category</label>
                <select name="prod_category" class="border p-2 w-full rounded">
                    <option value="">Select Product Category</option>
                    <option value="Prescription Medicines" ${data.prod_category === 'Prescription Medicines' ? 'selected' : ''}>Prescription Medicines</option>
                    <option value="Vaccines" ${data.prod_category === 'Vaccines' ? 'selected' : ''}>Vaccines</option>
                    <option value="Nutritional Products" ${data.prod_category === 'Nutritional Products' ? 'selected' : ''}>Nutritional Products</option>
                    <option value="Parasite Control" ${data.prod_category === 'Parasite Control' ? 'selected' : ''}>Parasite Control</option>
                    <option value="Grooming Products" ${data.prod_category === 'Grooming Products' ? 'selected' : ''}>Grooming Products</option>
                    <option value="Pet Accessories" ${data.prod_category === 'Pet Accessories' ? 'selected' : ''}>Pet Accessories</option>
                    <option value="Medical Supplies" ${data.prod_category === 'Medical Supplies' ? 'selected' : ''}>Medical Supplies</option>
                    <option value="Pet Food & Treats" ${data.prod_category === 'Pet Food & Treats' ? 'selected' : ''}>Pet Food & Treats</option>
                    <option value="Hygiene & Sanitation" ${data.prod_category === 'Hygiene & Sanitation' ? 'selected' : ''}>Hygiene & Sanitation</option>
                    <option value="Pet Care Equipment" ${data.prod_category === 'Pet Care Equipment' ? 'selected' : ''}>Pet Care Equipment</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Price *</label>
                <input type="number" step="0.01" name="prod_price" value="${data.prod_price}" class="border p-2 w-full rounded" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Current Stock</label>
                <input type="number" name="prod_stocks" value="${data.prod_stocks || 0}" class="border p-2 w-full rounded" readonly>
                <small class="text-gray-500">Use "Update Stock" action to modify stock</small>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Reorder Level</label>
                <input type="number" name="prod_reorderlevel" value="${data.prod_reorderlevel || ''}" class="border p-2 w-full rounded">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Current Expiry Date</label>
                <input type="date" name="prod_expiry" value="${data.prod_expiry ? data.prod_expiry.split('T')[0] : ''}" class="border p-2 w-full rounded" readonly>
                <small class="text-gray-500">Use "Update Stock" action to modify expiry date</small>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Branch</label>
                <select name="branch_id" class="border p-2 w-full rounded">
                    <option value="">Select Branch</option>`;

            @foreach($branches as $branch)
                fields += `<option value="{{ $branch->branch_id }}" ${data.branch_id == '{{ $branch->branch_id }}' ? 'selected' : ''}>{{ $branch->branch_name }}</option>`;
            @endforeach

            fields += `
                </select>
            </div>
            <div class="mb-4 md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                <textarea name="prod_description" class="border p-2 w-full rounded" rows="3" required>${escapeHtml(data.prod_description || '')}</textarea>
            </div>
            <div class="mb-4 md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Product Image</label>
                <input type="file" name="prod_image" accept="image/*" class="border p-2 w-full rounded">`;

            if (data.prod_image) {
                fields += `<div class="mt-2 text-sm text-gray-600">Current image: <img src="{{ asset('storage/') }}/${data.prod_image}" class="h-16 w-16 object-cover inline-block ml-2 rounded"></div>`;
            }

            fields += `</div>`;

            document.getElementById('productModalFields').innerHTML = fields;
            document.getElementById('productModalForm').action = `/products/${data.prod_id}`;

            // Add PUT method
            if (!document.querySelector('#productModalForm input[name="_method"]')) {
                document.getElementById('productModalForm').insertAdjacentHTML('afterbegin', '<input type="hidden" name="_method" value="PUT">');
            }
        }

        function closeProductModal() {
            document.getElementById('productModal').classList.add('hidden');
            document.getElementById('productModalForm').reset();

            // Remove method field
            let methodField = document.querySelector('#productModalForm input[name="_method"]');
            if (methodField) methodField.remove();
        }

        // UPDATE STOCK MODAL
        function openUpdateStockModal(data) {
            document.getElementById('updateStockModal').classList.remove('hidden');
            document.getElementById('currentStock').innerText = data.prod_stocks || 0;
            document.getElementById('productName').innerText = data.prod_name;

            let form = document.getElementById('updateStockForm');
            form.action = `/inventory/update-stock/${data.prod_id}`;
            form.querySelector('input[name=product_id]').value = data.prod_id;
            form.querySelector('input[name=reorder_level]').value = data.prod_reorderlevel || '';
        }

        function closeUpdateStockModal() {
            document.getElementById('updateStockModal').classList.add('hidden');
            document.getElementById('updateStockForm').reset();
        }

        // DAMAGE/PULL-OUT MODAL
        function openDamagePulloutModal(data) {
            document.getElementById('damagePulloutModal').classList.remove('hidden');
            document.getElementById('damageProductName').innerText = data.prod_name;
            document.getElementById('damageCurrentStock').innerText = data.prod_stocks || 0;

            let form = document.getElementById('damagePulloutForm');
            form.action = `/inventory/update-damage/${data.prod_id}`;
            form.querySelector('input[name=product_id]').value = data.prod_id;
            form.querySelector('input[name=damaged_qty]').value = data.prod_damaged || 0;
            form.querySelector('input[name=pullout_qty]').value = data.prod_pullout || 0;
        }

        function closeDamagePulloutModal() {
            document.getElementById('damagePulloutModal').classList.add('hidden');
            document.getElementById('damagePulloutForm').reset();
        }
        
        // ===========================================
        // ✅ NEW EQUIPMENT STATUS FUNCTIONS
        // ===========================================
        function openUpdateStatusModal(data) {
            document.getElementById('updateEquipmentStatusModal').classList.remove('hidden');
            document.getElementById('statusEquipmentName').innerText = data.equipment_name;
            // Use the data status or default to 'Available'
            const currentStatus = data.equipment_status || 'Available'; 
            document.getElementById('currentEquipmentStatus').innerText = currentStatus;
            
            let form = document.getElementById('updateEquipmentStatusForm');
            form.action = `/equipment/${data.equipment_id}/update-status`;
            form.querySelector('#statusEquipmentId').value = data.equipment_id;
            
            // Set the dropdown to the current status
            const statusSelect = form.querySelector('#newEquipmentStatus');
            statusSelect.value = currentStatus;
        }

        function closeUpdateStatusModal() {
            document.getElementById('updateEquipmentStatusModal').classList.add('hidden');
            document.getElementById('updateEquipmentStatusForm').reset();
        }

        // SERVICE DETAILS MODAL
        function viewServiceDetails(serviceId) {
            document.getElementById('productDetailsModal').classList.remove('hidden');
            document.getElementById('productDetailsContent').innerHTML = '<div class="text-center py-8"><div class="spinner-border animate-spin inline-block w-8 h-8 border-4 rounded-full" role="status"></div><p>Loading service details...</p></div>';

            fetch(`/services/${serviceId}/view`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        document.getElementById('productDetailsContent').innerHTML = `<div class="text-red-500">${data.error}</div>`;
                        return;
                    }

                    const service = data.service;
                    const revenueData = data.revenue_data;
                    const monthlyRevenue = data.monthly_revenue;
                    const recentAppointments = data.recent_appointments;
                    const utilizationData = data.utilization_data;
                    const peakTimes = data.peak_times;

                    let content = `
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-bold text-lg mb-3">Service Information</h4>
                            <div class="space-y-2">
                                <div><span class="font-medium">Name:</span> ${service.serv_name}</div>
                                <div><span class="font-medium">Type:</span> ${service.serv_type || 'N/A'}</div>
                                <div><span class="font-medium">Description:</span> ${service.serv_description || 'N/A'}</div>
                                <div><span class="font-medium">Price:</span> ₱${parseFloat(service.serv_price).toLocaleString(undefined, { minimumFractionDigits: 2 })}</div>
                                <div><span class="font-medium">Branch:</span> ${service.branch ? service.branch.branch_name : 'N/A'}</div>
                            </div>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-bold text-lg mb-3">Revenue Overview</h4>
                            <div class="space-y-2">
                                <div><span class="font-medium">Total Bookings:</span> <span class="text-lg font-bold text-blue-600">${revenueData.total_bookings || 0}</span></div>
                                <div><span class="font-medium">Total Revenue:</span> <span class="text-lg font-bold text-green-600">₱${parseFloat(revenueData.total_revenue || 0).toLocaleString(undefined, { minimumFractionDigits: 2 })}</span></div>
                                <div><span class="font-medium">Average Booking Value:</span> ₱${parseFloat(revenueData.average_booking_value || 0).toLocaleString(undefined, { minimumFractionDigits: 2 })}</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <h4 class="font-bold text-lg mb-3">Service Utilization</h4>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">`;

                    utilizationData.forEach(status => {
                        const statusColors = {
                            'completed': 'bg-green-50 text-green-600',
                            'pending': 'bg-yellow-50 text-yellow-600',
                            'cancelled': 'bg-red-50 text-red-600'
                        };
                        const colorClass = statusColors[status.appoint_status] || 'bg-gray-50 text-gray-600';
                        content += `
                        <div class="${colorClass} p-4 rounded-lg">
                            <div class="text-sm capitalize">${status.appoint_status}</div>
                            <div class="text-2xl font-bold">${status.count}</div>
                        </div>`;
                    });

                    content += `
                        </div>
                    </div>

                    <div class="mt-6">
                        <h4 class="font-bold text-lg mb-3">Recent Visits</h4>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm border">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="p-2 border">Date</th>
                                        <th class="p-2 border">Pet</th>
                                        <th class="p-2 border">Owner</th>
                                        <th class="p-2 border">Veterinarian</th>
                                        <th class="p-2 border">Status</th>
                                    </tr>
                                </thead>
                                <tbody>`;

                    if (recentAppointments && recentAppointments.length > 0) {
                        recentAppointments.forEach(appt => {
                            const appointDate = appt.appoint_date ? new Date(appt.appoint_date) : new Date();
                            const statusColors = {
                                'completed': 'bg-green-100 text-green-800',
                                'complete': 'bg-green-100 text-green-800',
                                'arrive': 'bg-blue-100 text-blue-800',
                                'pending': 'bg-yellow-100 text-yellow-800',
                                'cancelled': 'bg-red-100 text-red-800',
                                'canceled': 'bg-red-100 text-red-800'
                            };
                            const statusKey = (appt.appoint_status || '').toString().toLowerCase();
                            const statusClass = statusColors[statusKey] || 'bg-gray-100 text-gray-800';

                            content += `
                            <tr>
                                <td class="p-2 border">${appointDate.toLocaleDateString()}</td>
                                <td class="p-2 border">${appt.pet_name || 'N/A'}</td>
                                <td class="p-2 border">${appt.own_name || 'N/A'}</td>
                                <td class="p-2 border">${appt.user_name || 'N/A'}</td>
                                <td class="p-2 border">
                                    <span class="px-2 py-1 rounded text-xs ${statusClass}">${appt.appoint_status}</span>
                                </td>
                            </tr>`;
                        });
                    } else {
                        content += '<tr><td colspan="5" class="p-4 text-center text-gray-500">No recent visits found</td></tr>';
                    }

                    content += `
                                </tbody>
                            </table>
                        </div>
                    </div>`;

                    document.getElementById('productDetailsContent').innerHTML = content;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('productDetailsContent').innerHTML = '<div class="text-red-500">Error loading service details</div>';
                });
        }

        // EQUIPMENT DETAILS MODAL
        function viewEquipmentDetails(equipmentId) {
            document.getElementById('productDetailsModal').classList.remove('hidden');
            document.getElementById('productDetailsContent').innerHTML = '<div class="text-center py-8"><div class="spinner-border animate-spin inline-block w-8 h-8 border-4 rounded-full" role="status"></div><p>Loading equipment details...</p></div>';

            fetch(`/equipment/${equipmentId}/view`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        document.getElementById('productDetailsContent').innerHTML = `<div class="text-red-500">${data.error}</div>`;
                        return;
                    }

                    const equipment = data.equipment;
                    const usageData = data.usage_data;
                    const availabilityStatus = data.availability_status;
                    const conditionData = data.condition_data;

                    const statusColors = {
                        'available': 'text-green-600',
                        'low': 'text-yellow-600',
                        'none': 'text-red-600'
                    };

                    let content = `
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-bold text-lg mb-3">Equipment Information</h4>
                            <div class="space-y-2">
                                ${equipment.equipment_image ? `<div class="mb-3"><img src="/storage/${equipment.equipment_image}" class="h-32 w-32 object-cover rounded mx-auto"></div>` : ''}
                                <div><span class="font-medium">Name:</span> ${equipment.equipment_name}</div>
                                <div><span class="font-medium">Category:</span> ${equipment.equipment_category || 'N/A'}</div>
                                <div><span class="font-medium">Description:</span> ${equipment.equipment_description || 'N/A'}</div>
                                <div><span class="font-medium">Branch:</span> ${usageData.branch}</div>
                            </div>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-bold text-lg mb-3">Availability Status</h4>
                            <div class="space-y-2">
                                <div><span class="font-medium">Total Quantity:</span> <span class="text-lg font-bold ${statusColors[availabilityStatus]}">${usageData.total_quantity}</span></div>
                                <div><span class="font-medium">Available:</span> <span class="text-lg font-bold text-green-600">${usageData.available_quantity}</span></div>
                                <div><span class="font-medium">Status:</span> <span class="capitalize font-bold ${statusColors[availabilityStatus]}">${availabilityStatus}</span></div>
                                <div><span class="font-medium">Last Updated:</span> ${new Date(conditionData.last_updated).toLocaleDateString()}</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 bg-blue-50 p-4 rounded-lg border border-blue-200">
                        <p class="text-sm text-blue-800">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>Note:</strong> Equipment usage tracking is based on available quantity. 
                            Update quantity through the edit function to reflect current availability.
                        </p>
                    </div>`;

                    document.getElementById('productDetailsContent').innerHTML = content;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('productDetailsContent').innerHTML = '<div class="text-red-500">Error loading equipment details</div>';
                });
        }
        function openAddModal(type) {
            document.getElementById('generalModal').classList.remove('hidden');
            document.getElementById('generalModalTitle').innerText = 'Add ' + capitalize(type);

            // FIX: Set 'tab' parameter name here
            document.getElementById('active_tab').value = type;

            let fields = '';

            if (type === 'service') {
                fields = `
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Service Name</label>
                    <input type="text" name="serv_name" placeholder="Enter service name" class="border p-2 w-full rounded" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Service Type</label>
                   <select name="serv_type" class="border p-2 w-full rounded">
                    <option value="">Select Service Type</option>
                    <option value="Vaccination">Vaccination</option>
                    <option value="Deworming">Deworming</option>
                    <option value="Grooming">Grooming</option>
                    <option value="Emergency">Emergency</option>
                    <option value="Check-up">Check-up</option>
                    <option value="Diagnostics">Diagnostics</option>
                    <option value="Surgical">Surgical</option>
                    <option value="Boarding">Boarding</option>
                </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Price</label>
                    <input type="number" step="0.01" name="serv_price" placeholder="Enter service price" class="border p-2 w-full rounded" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Branch</label>
                    <select name="branch_id" class="border p-2 w-full rounded">
                        <option value="">Select Branch</option>`;

                @foreach($branches as $branch)
                    fields += `<option value="{{ $branch->branch_id }}">{{ $branch->branch_name }}</option>`;
                @endforeach

                fields += `
                    </select>
                </div>
                <div class="mb-4 md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="serv_description" placeholder="Enter service description" class="border p-2 w-full rounded" rows="3"></textarea>
                </div>`;

                document.getElementById('generalModalForm').action = '{{ route("services.store") }}';

            } else if (type === 'equipment') {
                fields = `
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Equipment Name</label>
                    <input type="text" name="equipment_name" placeholder="Enter equipment name" class="border p-2 w-full rounded" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Equipment Category</label>
                    <select name="equipment_category" class="border p-2 w-full rounded">
                        <option value="">Select Equipment Category</option>
                        <option value="Diagnostic Equipment">Diagnostic Equipment</option>
                        <option value="Surgical Equipment">Surgical Equipment</option>
                        <option value="Monitoring Equipment">Monitoring Equipment</option>
                        <option value="Treatment & Therapy Equipment">Treatment & Therapy Equipment</option>
                        <option value="Laboratory Equipment">Laboratory Equipment</option>
                        <option value="Grooming & Handling Equipment">Grooming & Handling Equipment</option>
                        <option value="Sanitation & Sterilization Equipment">Sanitation & Sterilization Equipment</option>
                        <option value="Furniture & General Clinic Equipment">Furniture & General Clinic Equipment</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                    <input type="number" name="equipment_quantity" placeholder="Enter quantity" class="border p-2 w-full rounded" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Equipment Image</label>
                    <input type="file" name="equipment_image" accept="image/*" class="border p-2 w-full rounded">
                </div>
                {{-- ✅ UPDATED FIELD: Branch ID for Equipment Add --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Branch *</label>
                    <select name="branch_id" class="border p-2 w-full rounded" required>
                        <option value="">Select Branch</option>`;

                @foreach($branches as $branch)
                    fields += `<option value="{{ $branch->branch_id }}">{{ $branch->branch_name }}</option>`;
                @endforeach

                fields += `
                    </select>
                </div>
                <div class="mb-4 md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="equipment_description" placeholder="Enter equipment description" class="border p-2 w-full rounded" rows="3"></textarea>
                </div>`;

                document.getElementById('generalModalForm').action = '{{ route("equipment.store") }}';
            }

            document.getElementById('generalModalFields').innerHTML = fields;

            // Remove existing method field
            let methodField = document.querySelector('#generalModalForm input[name="_method"]');
            if (methodField) methodField.remove();
        }

        function openEditModal(type, data) {
            document.getElementById('generalModal').classList.remove('hidden');
            document.getElementById('generalModalTitle').innerText = 'Edit ' + capitalize(type);

            // FIX: Set 'tab' parameter name here
            document.getElementById('active_tab').value = type;

            let fields = '';
            if (type === 'service') {
                fields = `
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Service Name</label>
                    <input type="text" name="serv_name" value="${escapeHtml(data.serv_name)}" class="border p-2 w-full rounded" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Service Type</label>
                    <select name="serv_type" class="border p-2 w-full rounded">
                        <option value="">Select Service Type</option>
                        <option value="Preventive Care" ${data.serv_type === 'Preventive Care' ? 'selected' : ''}>Preventive Care</option>
                        <option value="Diagnostic Services" ${data.serv_type === 'Diagnostic Services' ? 'selected' : ''}>Diagnostic Services</option>
                        <option value="Surgical Services" ${data.serv_type === 'Surgical Services' ? 'selected' : ''}>Surgical Services</option>
                        <option value="Emergency & Critical Care" ${data.serv_type === 'Emergency & Critical Care' ? 'selected' : ''}>Emergency & Critical Care</option>
                        <option value="Reproductive & Breeding" ${data.serv_type === 'Reproductive & Breeding' ? 'selected' : ''}>Reproductive & Breeding</option>
                        <option value="Grooming & Hygiene" ${data.serv_type === 'Grooming & Hygiene' ? 'selected' : ''}>Grooming & Hygiene</option>
                        <option value="Wellness & Nutrition" ${data.serv_type === 'Wellness & Nutrition' ? 'selected' : ''}>Wellness & Nutrition</option>
                        <option value="Additional Fees" ${data.serv_type === 'Additional Fees' ? 'selected' : ''}>Additional Fees</option>
                        <option value="Other" ${data.serv_type === 'Other' ? 'selected' : ''}>Other</option>
                        <option value="Boarding & Daycare" ${data.serv_type === 'Boarding & Daycare' ? 'selected' : ''}>Boarding & Daycare</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Price</label>
                    <input type="number" step="0.01" name="serv_price" value="${data.serv_price}" class="border p-2 w-full rounded" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Branch</label>
                    <select name="branch_id" class="border p-2 w-full rounded">
                        <option value="">Select Branch</option>`;

                @foreach($branches as $branch)
                    fields += `<option value="{{ $branch->branch_id }}" ${data.branch_id == '{{ $branch->branch_id }}' ? 'selected' : ''}>{{ $branch->branch_name }}</option>`;
                @endforeach

                fields += `
                    </select>
                </div>
                <div class="mb-4 md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="serv_description" class="border p-2 w-full rounded" rows="3">${escapeHtml(data.serv_description || '')}</textarea>
                </div>`;

                document.getElementById('generalModalForm').action = `/services/${data.serv_id}`;

            } else if (type === 'equipment') {
                fields = `
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Equipment Name</label>
                    <input type="text" name="equipment_name" value="${escapeHtml(data.equipment_name)}" class="border p-2 w-full rounded" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Equipment Category</label>
                    <select name="equipment_category" class="border p-2 w-full rounded">
                        <option value="">Select Equipment Category</option>
                        <option value="Diagnostic Equipment" ${data.equipment_category === 'Diagnostic Equipment' ? 'selected' : ''}>Diagnostic Equipment</option>
                        <option value="Surgical Equipment" ${data.equipment_category === 'Surgical Equipment' ? 'selected' : ''}>Surgical Equipment</option>
                        <option value="Monitoring Equipment" ${data.equipment_category === 'Monitoring Equipment' ? 'selected' : ''}>Monitoring Equipment</option>
                        <option value="Treatment & Therapy Equipment" ${data.equipment_category === 'Treatment & Therapy Equipment' ? 'selected' : ''}>Treatment & Therapy Equipment</option>
                        <option value="Laboratory Equipment" ${data.equipment_category === 'Laboratory Equipment' ? 'selected' : ''}>Laboratory Equipment</option>
                        <option value="Grooming & Handling Equipment" ${data.equipment_category === 'Grooming & Handling Equipment' ? 'selected' : ''}>Grooming & Handling Equipment</option>
                        <option value="Sanitation & Sterilization Equipment" ${data.equipment_category === 'Sanitation & Sterilization Equipment' ? 'selected' : ''}>Sanitation & Sterilization Equipment</option>
                        <option value="Furniture & General Clinic Equipment" ${data.equipment_category === 'Furniture & General Clinic Equipment' ? 'selected' : ''}>Furniture & General Clinic Equipment</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                    <input type="number" name="equipment_quantity" value="${data.equipment_quantity}" class="border p-2 w-full rounded" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Equipment Image</label>
                    <input type="file" name="equipment_image" accept="image/*" class="border p-2 w-full rounded">`;

                if (data.equipment_image) {
                    fields += `<div class="mt-2 text-sm text-gray-600">Current image: <img src="{{ asset('storage/') }}/${data.equipment_image}" class="h-16 w-16 object-cover inline-block ml-2 rounded"></div>`;
                }

                fields += `
                </div>
                {{-- ✅ UPDATED FIELD: Branch ID for Equipment Edit --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Branch *</label>
                    <select name="branch_id" class="border p-2 w-full rounded" required>
                        <option value="">Select Branch</option>`;

                @foreach($branches as $branch)
                    fields += `<option value="{{ $branch->branch_id }}" ${data.branch_id == '{{ $branch->branch_id }}' ? 'selected' : ''}>{{ $branch->branch_name }}</option>`;
                @endforeach

                fields += `
                    </select>
                </div>
                <div class="mb-4 md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="equipment_description" class="border p-2 w-full rounded" rows="3">${escapeHtml(data.equipment_description || '')}</textarea>
                </div>`;

                document.getElementById('generalModalForm').action = `/equipment/${data.equipment_id}`;
            }

            document.getElementById('generalModalFields').innerHTML = fields;

            // Add PUT method
            if (!document.querySelector('#generalModalForm input[name="_method"]')) {
                document.getElementById('generalModalForm').insertAdjacentHTML('afterbegin', '<input type="hidden" name="_method" value="PUT">');
            }
        }

        function closeGeneralModal() {
            document.getElementById('generalModal').classList.add('hidden');
            document.getElementById('generalModalForm').reset();

            // Remove method field
            let methodField = document.querySelector('#generalModalForm input[name="_method"]');
            if (methodField) methodField.remove();
        }

        // UTILITY FUNCTIONS
        function capitalize(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Initialize default tab
        document.addEventListener('DOMContentLoaded', function () {
            // switchMainTab is called in the load event listener above
        });

        // Instant search with auto-expand to 'All' and persistence
        (function(){
            function persistKey(tab){ return `pse_search_${tab}`; }
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

            // Products
            setupTableFilter({
                inputId: 'productsSearch',
                tableSelector: '#productInventoryTab table',
                tab: 'products',
                perPageSelectId: 'productsPerPage',
                formSelector: '#productInventoryTab form[action]'
            });
            // Services
            setupTableFilter({
                inputId: 'servicesSearch',
                tableSelector: '#servicesTab table',
                tab: 'services',
                perPageSelectId: 'servicesPerPage',
                formSelector: '#servicesTab form[action]'
            });
            // Equipment
            setupTableFilter({
                inputId: 'equipmentSearch',
                tableSelector: '#equipmentTab table',
                tab: 'equipment',
                perPageSelectId: 'equipmentPerPage',
                formSelector: '#equipmentTab form[action]'
            });
        })();
    </script>
@endsection