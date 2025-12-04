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
        <div class="max-w-full mx-auto bg-white p-6 rounded-lg shadow overflow-x-auto">
            <div class="min-w-[1200px]">
            <div class="border-b border-gray-200 mb-6 overflow-x-auto">
                <div class="flex flex-nowrap min-w-max" id="main-tabs-container">
                    <button onclick="switchMainTab('servicesTab', 'services')" id="servicesBtn"
                        class="py-2 px-4 text-sm font-semibold border-b-2 border-[#0f7ea0] text-[#0f7ea0] whitespace-nowrap">
                        <h2 class="text-lg sm:text-xl">Services</h2>
                    </button>
                    <button onclick="switchMainTab('productInventoryTab', 'products')" id="productInventoryBtn"
                        class="py-2 px-4 text-sm font-semibold border-b-2 border-transparent hover:border-[#0f7ea0] hover:text-[#0f7ea0] whitespace-nowrap">
                        <h2 class="text-lg sm:text-xl">Products</h2>
                    </button>
                    <button onclick="switchMainTab('equipmentTab', 'equipment')" id="equipmentBtn"
                        class="py-2 px-4 text-sm font-semibold border-b-2 border-transparent hover:border-[#0f7ea0] hover:text-[#0f7ea0] whitespace-nowrap">
                        <h2 class="text-lg sm:text-xl">Equipment</h2>
                    </button>
                </div>
            </div>

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-4 text-sm">
                    {{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="bg-red-500 text-white p-2 rounded mb-4">{{ session('error') }}</div>
            @endif

            <div id="productInventoryTab" class="main-tab-content hidden">
                <div class="flex flex-nowrap items-center justify-between gap-3 mt-4 text-sm font-semibold text-black w-full overflow-x-auto pb-2">
                    <div class="flex-shrink-0 flex items-center space-x-2">
                        <label for="productsPerPage" class="whitespace-nowrap text-sm text-black">Show</label>
                        <select name="productsPerPage" id="productsPerPage"
                            class="border border-gray-400 rounded px-2 py-1.5 text-sm">
                            @foreach ([10, 20, 50, 100, 'all'] as $limit)
                                <option value="{{ $limit }}" {{ request('productsPerPage', 10) == $limit ? 'selected' : '' }}>
                                    {{ $limit === 'all' ? 'All' : $limit }}
                                </option>
                            @endforeach
                        </select>
                        <span class="whitespace-nowrap">entries</span>
                        <label for="productsType" class="whitespace-nowrap text-sm text-black ml-2">Filter</label>
                        <select name="productsType" id="productsType"
                            class="border border-gray-400 rounded px-2 py-1.5 text-sm">
                            @foreach (['All', 'Sale', 'Consumable'] as $type)
                                <option value="{{ $type }}" {{ request('productsType', 'All') == $type ? 'selected' : '' }}>
                                    {{ $type }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="relative flex-1 min-w-[200px] max-w-xs">
                        <input type="search" id="productsSearch" placeholder="Search products..." class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm pl-8">
                        <i class="fas fa-search absolute left-2 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="openInventoryOverview()" class="bg-purple-600 text-white text-sm px-3 py-1.5 rounded hover:bg-purple-700 whitespace-nowrap">
                            <i class="fas fa-chart-pie mr-1"></i> Inventory
                        </button>
                        @if(hasPermission('add_product', $can))
                            <button onclick="openManufacturerModal()" class="bg-indigo-600 text-white text-sm px-3 py-1.5 rounded hover:bg-indigo-700 whitespace-nowrap">
                                <i class="fas fa-industry mr-1"></i> Manufacturer
                            </button>
                            <button onclick="openAddProductModal()" class="bg-[#0f7ea0] text-white text-sm px-3 py-1.5 rounded hover:bg-[#0c6a86] whitespace-nowrap">
                                <i class="fas fa-plus mr-1"></i> Add Product
                            </button>
                        @endif
                    </div>
                </div>


                <div class="overflow-x-auto">
                    <table id="productsTable" class="w-full table-auto text-sm border text-center">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="p-2 border">#</th>
                                <th class="p-2 border">Image</th>
                                <th class="p-2 border">Name</th>
                                <th class="p-2 border">Category</th>
                                <th class="p-2 border">Type</th>
                                <th class="p-2 border">Manufacturer</th>
                                <th class="p-2 border">Description</th>
                                <th class="p-2 border">Price</th>
                                <th class="p-2 border">Stock</th>
                                <th class="p-2 border">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $product)
                                @php
                                    $isDisabled = $product->is_disabled;
                                    $isOutOfStock = $product->is_out_of_stock;
                                    $isExpired = $product->all_expired;
                                    $statusLabel = $product->stock_status_label;
                                    $rowClass = $isDisabled ? 'bg-gray-100 opacity-60' : '';
                                @endphp
                                <tr class="{{ $rowClass }}">
                                    <td class="p-2 border">{{ $loop->iteration }}</td>
                                    <td class="p-2 border">
                                        @if($product->prod_image)
                                            <img src="{{ asset('storage/' . $product->prod_image) }}"
                                                class="h-12 w-12 object-cover mx-auto rounded {{ $isDisabled ? 'grayscale' : '' }}">
                                        @else
                                            <span class="text-gray-400">No Image</span>
                                        @endif
                                    </td>
                                    <td class="p-2 border font-medium">
                                        {{ $product->prod_name }}
                                        @if($isExpired)
                                            <br><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                                <i class="fas fa-exclamation-circle mr-1"></i> EXPIRED
                                            </span>
                                        @elseif($isOutOfStock)
                                            <br><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                                <i class="fas fa-box-open mr-1"></i> OUT OF STOCK
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-2 border">{{ $product->prod_category }}</td>
                                    <td class="p-2 border">
                                        <span class="px-2 py-1 text-xs rounded-full {{ $product->prod_type === 'Sale' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }}">
                                            {{ $product->prod_type }}
                                        </span>
                                    </td>
                                    <td class="p-2 border">{{ $product->manufacturer->manufacturer_name ?? 'N/A' }}</td>
                                    <td class="p-2 border">{{ Str::limit($product->prod_description, 30) }}</td>
                                    <td class="p-2 border">
                                        @if($product->prod_type === 'Sale')
                                            ₱{{ number_format($product->prod_price, 2) }}
                                        @else
                                            <span class="text-gray-400 text-xs">N/A (Consumable)</span>
                                        @endif
                                    </td>
                                    <td class="p-2 border">
                                        @php
                                            $stockStatus = '';
                                            if ($product->available_stock - $product->usage_from_inventory_transactions <= ($product->prod_reorderlevel ?? 10)) {
                                                $stockStatus = 'text-red-600 font-bold';
                                            }
                                        @endphp
                                        <div>
                                            <span class="{{ $stockStatus }}">{{ $product->available_stock - $product->usage_from_inventory_transactions ?? 0 }}</span>
                                        </div>
                                    </td>
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
                                                    title="Add Stock">
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

                                            @if($product->prod_type === 'Consumable' && $product->prod_category !== 'Medical Supply' && hasPermission('edit_product', $can))
                                                <button onclick="openLinkedConsumablesModal({{ $product->prod_id }}, '{{ addslashes($product->prod_name) }}')"
                                                    class="bg-cyan-500 text-white px-2 py-1 rounded hover:bg-cyan-600 text-xs"
                                                    title="Link Consumables (e.g., add syringe to vaccine)">
                                                    <i class="fas fa-link"></i>
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
                <div id="productsPagination" class="mt-4"></div>

            
            </div>

            <div id="servicesTab" class="main-tab-content">
                <div class="flex flex-nowrap items-center justify-between gap-3 mt-4 text-sm font-semibold text-black w-full overflow-x-auto pb-2">
                    <div class="flex-shrink-0 flex items-center space-x-2">
                        <label for="servicesPerPage" class="whitespace-nowrap text-sm text-black">Show</label>
                        <select name="servicesPerPage" id="servicesPerPage"
                            class="border border-gray-400 rounded px-2 py-1.5 text-sm">
                            @foreach ([10, 20, 50, 100, 'all'] as $limit)
                                <option value="{{ $limit }}" {{ request('servicesPerPage', 10) == $limit ? 'selected' : '' }}>
                                    {{ $limit === 'all' ? 'All' : $limit }}
                                </option>
                            @endforeach
                        </select>
                        <span class="whitespace-nowrap">entries</span>
                        <label for="servicesCategoryFilter" class="whitespace-nowrap text-sm text-black ml-2">Filter</label>
                        <select id="servicesCategoryFilter" class="border border-gray-400 rounded px-2 py-1.5 text-sm">
                            <option value="">All Categories</option>
                            <option value="boarding">Boarding</option>
                            <option value="check-up">Check-up</option>
                            <option value="deworming">Deworming</option>
                            <option value="diagnostics">Diagnostics</option>
                            <option value="emergency">Emergency</option>
                            <option value="grooming">Grooming</option>
                            <option value="surgical">Surgical</option>
                            <option value="vaccination">Vaccination</option>
                        </select>
                    </div>
                    <div class="relative flex-1 min-w-[200px] max-w-xs">
                        <input type="search" id="servicesSearch" placeholder="Search services..." class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm pl-8">
                        <i class="fas fa-search absolute left-2 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                    
                    <div class="flex gap-2">
                        <button onclick="openServiceInventoryOverview()" class="bg-purple-600 text-white text-sm px-3 py-1.5 rounded hover:bg-purple-700 whitespace-nowrap">
                            <i class="fas fa-pills mr-1"></i> Inventory
                        </button>
                        @if(hasPermission('add_service', $can))
                            <button onclick="openAddModal('service')" class="bg-[#0f7ea0] text-white text-sm px-3 py-1.5 rounded hover:bg-[#0c6a86] whitespace-nowrap">
                                <i class="fas fa-plus mr-1"></i> Add Service
                            </button>
                        @endif
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table id="servicesTable" class="w-full table-auto text-sm border text-center">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="p-2 border">#</th>
                                <th class="p-2 border">Name</th>
                                <th class="p-2 border">Type</th>
                                <th class="p-2 border">Description</th>
                                <th class="p-2 border">Price</th>
                                <th class="p-2 border">Actions</th>
                            </tr>
                        </thead>
                       <tbody>
                            @foreach($services as $index => $service)
                                <tr>
                                    <td class="p-2 border">{{ $loop->iteration }}</td>
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
                                            {{-- Hidden per requirement: "Inventory - hide Add Consumable Product button in Service Tab" --}}
                                            @if(false && hasPermission('add_product', $can))
                                            <button onclick="openAddProductModal('service_product_inline', {{ $service->serv_id }})"
                                                class="bg-blue-600 text-white px-2 py-1 rounded hover:bg-blue-700 text-xs"
                                                title="Add Consumable Product for Service" style="display: none;">
                                                <i class="fas fa-flask"></i>
                                            </button>
                                            @endif
                                            {{-- END NEW ADDITION --}}

                                            {{-- Unified Manage Items Button (Consumable Products & Equipment) - Available for ALL services --}}
                                            <button
                                                onclick="openManageItemsModal({{ $service->serv_id }}, '{{ addslashes($service->serv_name) }}', '{{ $service->serv_type }}', {{ $service->branch_id ?? 'null' }})"
                                                class="bg-indigo-500 text-white px-2 py-1 rounded hover:bg-indigo-600 text-xs"
                                                title="Manage Consumables & Equipment">
                                                <i class="fas fa-boxes"></i>
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

                    <div id="servicesPagination" class="mt-4"></div>

                </div>
            </div>

            <div id="equipmentTab" class="main-tab-content hidden">
                <div class="flex flex-nowrap items-center justify-between gap-3 mt-4 text-sm font-semibold text-black w-full overflow-x-auto pb-2">
                    <div class="flex-shrink-0 flex items-center space-x-2">
                        <label for="equipmentPerPage" class="whitespace-nowrap text-sm text-black">Show</label>
                        <select name="equipmentPerPage" id="equipmentPerPage"
                            class="border border-gray-400 rounded px-2 py-1.5 text-sm">
                            @foreach ([10, 20, 50, 100, 'all'] as $limit)
                                <option value="{{ $limit }}" {{ request('equipmentPerPage', 10) == $limit ? 'selected' : '' }}>
                                    {{ $limit === 'all' ? 'All' : $limit }}
                                </option>
                            @endforeach
                        </select>
                        <span class="whitespace-nowrap">entries</span>
                        <label for="equipmentStatusFilter" class="whitespace-nowrap text-sm text-black ml-2">Filter</label>
                        <select id="equipmentStatusFilter" class="border border-gray-400 rounded px-2 py-1.5 text-sm" onchange="filterEquipmentByStatus()">
                            <option value="">All Status</option>
                            <option value="available">Available</option>
                            <option value="in_use">In Use</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="out_of_service">Out of Service</option>
                        </select>
                    </div>
                    <div class="relative flex-1 min-w-[200px] max-w-xs">
                        <input type="search" id="equipmentSearch" placeholder="Search equipment..." class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm pl-8">
                        <i class="fas fa-search absolute left-2 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="openEquipmentInventoryOverview()" class="bg-purple-600 text-white text-sm px-3 py-1.5 rounded hover:bg-purple-700 whitespace-nowrap">
                            <i class="fas fa-history mr-1"></i> Assignment History
                        </button>
                        @if(hasPermission('add_equipment', $can))
                            <button onclick="openAddModal('equipment')" class="bg-[#0f7ea0] text-white text-sm px-3 py-1.5 rounded hover:bg-[#0c6a86] whitespace-nowrap">
                                <i class="fas fa-plus mr-1"></i> Add Equipment
                            </button>
                        @endif
                    </div>
                </div>
                
                {{-- Status Legend --}}
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-3">
                    <div class="flex items-center gap-4 text-xs flex-wrap">
                        <span class="font-semibold text-blue-800"><i class="fas fa-info-circle mr-1"></i>Status Legend:</span>
                        <span class="inline-flex items-center px-2 py-1 rounded bg-green-100 text-green-800">
                            <i class="fas fa-check-circle mr-1"></i>Available
                        </span>
                        <span class="inline-flex items-center px-2 py-1 rounded bg-purple-100 text-purple-800">
                            <i class="fas fa-hand-holding mr-1"></i>In Use
                        </span>
                        <span class="inline-flex items-center px-2 py-1 rounded bg-yellow-100 text-yellow-800">
                            <i class="fas fa-tools mr-1"></i>Maintenance
                        </span>
                        <span class="inline-flex items-center px-2 py-1 rounded bg-red-100 text-red-800">
                            <i class="fas fa-times-circle mr-1"></i>Out of Service
                        </span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table id="equipmentTable" class="w-full table-auto text-sm border text-center">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="p-2 border">#</th>
                                <th class="p-2 border">Image</th>
                                <th class="p-2 border">Name</th>
                                <th class="p-2 border">Category</th>
                                <th class="p-2 border">Description</th>
                                <th class="p-2 border">Quantity</th>
                                <th class="p-2 border">Status Breakdown</th>
                                <th class="p-2 border">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                       
                            @foreach($equipment as $equip)
                                @php
                                    $eqTotalQty = $equip->equipment_quantity;
                                    $eqAvailable = $equip->equipment_available ?? 0;
                                    $eqMaintenance = $equip->equipment_maintenance ?? 0;
                                    $eqOutOfService = $equip->equipment_out_of_service ?? 0;
                                    $eqInUse = max(0, $eqTotalQty - $eqAvailable - $eqMaintenance - $eqOutOfService);
                                    $eqStatus = 'available';
                                    if ($eqOutOfService > 0) $eqStatus = 'out_of_service';
                                    elseif ($eqMaintenance > 0) $eqStatus = 'maintenance';
                                    elseif ($eqInUse > 0) $eqStatus = 'in_use';
                                @endphp
                                <tr class="equipment-row" data-status="{{ $eqStatus }}" data-available="{{ $eqAvailable }}" data-inuse="{{ $eqInUse }}" data-maintenance="{{ $eqMaintenance }}" data-outofservice="{{ $eqOutOfService }}">
                                    <td class="p-2 border">{{ $loop->iteration }}</td>
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
                                            $totalQty = $equip->equipment_quantity;
                                            $available = $equip->equipment_available ?? 0;
                                            $maintenance = $equip->equipment_maintenance ?? 0;
                                            $outOfService = $equip->equipment_out_of_service ?? 0;
                                            $inUse = max(0, $totalQty - $available - $maintenance - $outOfService);
                                        @endphp
                                        <div class="text-xs space-y-1">
                                            <div class="flex items-center justify-center gap-1">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-green-100 text-green-800">
                                                    <i class="fas fa-check-circle text-xs mr-1"></i>{{ $available }}
                                                </span>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-purple-100 text-purple-800">
                                                    <i class="fas fa-hand-holding text-xs mr-1"></i>{{ $inUse }}
                                                </span>
                                            </div>
                                            <div class="flex items-center justify-center gap-1">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-yellow-100 text-yellow-800">
                                                    <i class="fas fa-tools text-xs mr-1"></i>{{ $maintenance }}
                                                </span>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-red-100 text-red-800">
                                                    <i class="fas fa-times-circle text-xs mr-1"></i>{{ $outOfService }}
                                                </span>
                                            </div>
                                        </div>
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
                <div id="equipmentPagination" class="mt-4"></div>
            </div>
            
        </div>
        
    </div>

    <div id="updateEquipmentStatusModal" class="hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
    <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-2xl">
        <h3 class="text-xl font-bold mb-4 flex items-center">
            <i class="fas fa-clipboard-check text-blue-600 mr-2"></i>
            Update Equipment Status Distribution
        </h3>
        <form id="updateEquipmentStatusForm" method="POST" onsubmit="return validateStatusFormSubmit()">
            @csrf
            @method('PUT') 
            <input type="hidden" name="equipment_id" id="statusEquipmentId">
            <input type="hidden" name="tab" value="equipment">
            <input type="hidden" name="equipment_available" id="statusEquipmentAvailable">
            <input type="hidden" name="equipment_maintenance" id="statusEquipmentMaintenance">
            <input type="hidden" name="equipment_out_of_service" id="statusEquipmentOutOfService">

                <div class="mb-4 bg-gradient-to-r from-blue-50 to-indigo-50 p-4 rounded-lg border border-blue-200">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <div class="text-sm text-gray-600">Equipment Name:</div>
                            <div class="font-bold text-gray-900" id="statusEquipmentName"></div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-600">Total Quantity:</div>
                            <div class="font-bold text-2xl text-blue-600" id="statusTotalQuantity"></div>
                        </div>
                    </div>
                </div>

                <p class="text-sm text-gray-600 mb-4 bg-yellow-50 p-3 rounded border border-yellow-200">
                    <i class="fas fa-info-circle text-yellow-600 mr-2"></i>
                    Distribute equipment units across different status categories. The sum cannot exceed the total quantity.
                </p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <!-- Available -->
                    <div class="border rounded-lg p-4 bg-green-50 border-green-300">
                        <label class="flex items-center text-sm font-semibold text-green-700 mb-2">
                            <i class="fas fa-check-circle mr-2"></i>Available for Use
                        </label>
                        <select id="statusAvailableDropdown" class="border p-3 w-full rounded-lg border-green-400 focus:ring-2 focus:ring-green-300" onchange="updateStatusDistribution('available')">
                            <option value="">-- Select Quantity --</option>
                        </select>
                        <small class="text-xs text-gray-600 mt-1 block">Ready to be used</small>
                    </div>

                    <!-- Under Maintenance -->
                    <div class="border rounded-lg p-4 bg-yellow-50 border-yellow-300">
                        <label class="flex items-center text-sm font-semibold text-yellow-700 mb-2">
                            <i class="fas fa-tools mr-2"></i>Under Maintenance
                        </label>
                        <select id="statusMaintenanceDropdown" class="border p-3 w-full rounded-lg border-yellow-400 focus:ring-2 focus:ring-yellow-300" onchange="updateStatusDistribution('maintenance')">
                            <option value="">-- Select Quantity --</option>
                        </select>
                        <small class="text-xs text-gray-600 mt-1 block">Being serviced/repaired</small>
                    </div>

                    <!-- Out of Service -->
                    <div class="border rounded-lg p-4 bg-red-50 border-red-300">
                        <label class="flex items-center text-sm font-semibold text-red-700 mb-2">
                            <i class="fas fa-times-circle mr-2"></i>Out of Service
                        </label>
                        <select id="statusOutOfServiceDropdown" class="border p-3 w-full rounded-lg border-red-400 focus:ring-2 focus:ring-red-300" onchange="updateStatusDistribution('out_of_service')">
                            <option value="">-- Select Quantity --</option>
                        </select>
                        <small class="text-xs text-gray-600 mt-1 block">Damaged/retired</small>
                    </div>

                    <!-- In Use (Calculated) -->
                    <div class="border rounded-lg p-4 bg-purple-50 border-purple-300">
                        <label class="flex items-center text-sm font-semibold text-purple-700 mb-2">
                            <i class="fas fa-hand-holding mr-2"></i>Currently In Use
                        </label>
                        <div class="text-4xl font-bold text-purple-700 mb-1" id="statusInUseDisplay">0</div>
                        <small class="text-xs text-gray-600">Auto-calculated</small>
                    </div>
                </div>

                <div id="statusValidationMessage" class="mb-4"></div>

                <div class="flex justify-end gap-3 pt-3 border-t">
                    <button type="button" onclick="closeUpdateStatusModal()"
                        class="px-5 py-2 bg-gray-300 rounded-lg hover:bg-gray-400 transition">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </button>
                    <button type="submit" id="statusSubmitBtn"
                        class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-save mr-2"></i>Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>


    <div id="inventoryModal" class="hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-7xl max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold">Complete Stock Movement History</h3>
                <button onclick="closeInventoryModal()" class="text-gray-500 hover:text-gray-700 text-xl">&times;</button>
            </div>
            
            <!-- Summary Cards -->
            <div id="inventorySummary" class="grid grid-cols-2 md:grid-cols-6 gap-3 mb-4">
                <div class="bg-gray-50 p-3 rounded-lg border text-center">
                    <p class="text-xs text-gray-500">Total Movements</p>
                    <p class="text-xl font-bold text-gray-700" id="summaryTotal">-</p>
                </div>
                <div class="bg-green-50 p-3 rounded-lg border border-green-200 text-center">
                    <p class="text-xs text-green-600">Stock Added</p>
                    <p class="text-xl font-bold text-green-700" id="summaryAdded">-</p>
                </div>
                <div class="bg-blue-50 p-3 rounded-lg border border-blue-200 text-center">
                    <p class="text-xs text-blue-600">POS Sales</p>
                    <p class="text-xl font-bold text-blue-700" id="summarySales">-</p>
                </div>
                <div class="bg-purple-50 p-3 rounded-lg border border-purple-200 text-center">
                    <p class="text-xs text-purple-600">Service Usage</p>
                    <p class="text-xl font-bold text-purple-700" id="summaryService">-</p>
                </div>
                <div class="bg-red-50 p-3 rounded-lg border border-red-200 text-center">
                    <p class="text-xs text-red-600">Damaged</p>
                    <p class="text-xl font-bold text-red-700" id="summaryDamaged">-</p>
                </div>
                <div class="bg-orange-50 p-3 rounded-lg border border-orange-200 text-center">
                    <p class="text-xs text-orange-600">Pull-out</p>
                    <p class="text-xl font-bold text-orange-700" id="summaryPullout">-</p>
                </div>
            </div>
            
            <!-- Filter Controls -->
            <div class="flex flex-wrap gap-3 mb-4 items-center">
                <div class="flex-1 min-w-[200px]">
                    <input type="text" id="movementSearchInput" placeholder="Search by product name..." 
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                        onkeyup="filterMovementHistory()">
                </div>
                <div>
                    <select id="movementTypeFilter" class="border border-gray-300 rounded px-3 py-2 text-sm" onchange="filterMovementHistory()">
                        <option value="">All Types</option>
                        <option value="batch_added">Stock Added</option>
                        <option value="restock">Restock</option>
                        <option value="sale">POS Sales</option>
                        <option value="service_usage">Service Usage</option>
                        <option value="damage">Damaged</option>
                        <option value="pullout">Pull-out</option>
                    </select>
                </div>
                <button onclick="loadStockMovementHistory()" class="bg-blue-500 text-white px-4 py-2 rounded text-sm hover:bg-blue-600">
                    <i class="fas fa-sync-alt mr-1"></i> Refresh
                </button>
            </div>
            
            <!-- Movement History Table -->
            <div id="inventoryContent" class="overflow-x-auto">
                <div class="text-center py-8">
                    <i class="fas fa-spinner fa-spin text-3xl text-blue-600"></i>
                    <p class="mt-2">Loading stock movement history...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Unified Manage Items Modal (Consumable Products & Equipment) - Available for ALL services -->
    <div id="manageItemsModal" class="hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-5xl max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold"><i class="fas fa-boxes mr-2"></i>Manage Items for Service</h3>
                <div class="flex items-center gap-4">
                    <p class="text-sm text-gray-600" id="itemsServiceNameDisplay"></p>
                    <button onclick="closeManageItemsModal()"
                        class="text-gray-500 hover:text-gray-700 text-xl">&times;</button>
                </div>
            </div>

            <input type="hidden" id="currentItemsServiceId">
            <input type="hidden" id="currentItemsServiceType">
            <input type="hidden" id="currentItemsBranchId">

            <!-- Tab Navigation -->
            <div class="border-b border-gray-200 mb-4">
                <nav class="flex -mb-px">
                    <button onclick="switchItemsTab('consumable')" id="consumableTabBtn"
                        class="items-tab-btn px-6 py-3 border-b-2 border-indigo-500 text-indigo-600 font-medium text-sm focus:outline-none">
                        <i class="fas fa-pills mr-2"></i>Consumable Products
                    </button>
                    <button onclick="switchItemsTab('equipment')" id="equipmentTabBtn"
                        class="items-tab-btn px-6 py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium text-sm focus:outline-none">
                        <i class="fas fa-tools mr-2"></i>Equipment Items
                    </button>
                </nav>
            </div>

            <!-- Consumable Products Tab Content -->
            <div id="consumableTabContent" class="items-tab-content">
                <div class="bg-gray-50 p-4 rounded-lg mb-4">
                    <h4 class="font-semibold mb-3">Add Consumable Product to Service</h4>
                    <div class="mb-2">
                        <small class="text-blue-600"><i class="fas fa-info-circle mr-1"></i>Showing consumable products matching service type: <strong id="itemsServiceTypeDisplay"></strong> and branch</small>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Select Consumable Product</label>
                            <select id="itemsProductSelect" class="border p-2 w-full rounded">
                                <option value="">-- Select Consumable Product --</option>
                            </select>
                            <small class="text-gray-500">Only consumable products matching this service type and branch are shown</small>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Quantity Used</label>
                            <input type="number" id="itemsQuantityUsed" step="0.01" min="0.01" value="1.00"
                                class="border p-2 w-full rounded" placeholder="1.00">
                        </div>
                        <div class="flex items-end">
                            <button onclick="addItemProductToService()"
                                class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 w-full">
                                <i class="fas fa-plus mr-1"></i> Add
                            </button>
                        </div>
                    </div>
                </div>

                <div>
                    <h4 class="font-semibold mb-3">Products Linked to This Service</h4>
                    <div id="itemsServiceProductsList" class="space-y-2">
                    </div>
                </div>
            </div>

            <!-- Equipment Tab Content -->
            <div id="equipmentTabContent" class="items-tab-content hidden">
                <div class="bg-gray-50 p-4 rounded-lg mb-4">
                    <h4 class="font-semibold mb-3">Add Equipment to Service</h4>
                    <div class="mb-2">
                        <small class="text-blue-600"><i class="fas fa-info-circle mr-1"></i>Showing <strong>Furniture & General Clinic Equipment</strong> from the same branch</small>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Select Equipment</label>
                            <select id="itemsEquipmentSelect" class="border p-2 w-full rounded">
                                <option value="">-- Select Equipment --</option>
                            </select>
                            <small class="text-gray-500">Only Furniture & General Clinic Equipment from this branch is shown</small>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                            <input type="number" id="itemsEquipmentQuantityUsed" min="1" value="1"
                                class="border p-2 w-full rounded" placeholder="1">
                        </div>
                        <div class="flex items-end">
                            <button onclick="addItemEquipmentToService()"
                                class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 w-full">
                                <i class="fas fa-plus mr-1"></i> Add
                            </button>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                        <input type="text" id="itemsEquipmentNotes" class="border p-2 w-full rounded" placeholder="Enter any notes about equipment usage...">
                    </div>
                </div>

                <div>
                    <h4 class="font-semibold mb-3">Equipment Linked to This Service</h4>
                    <div id="itemsServiceEquipmentList" class="space-y-2">
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-6 border-t pt-4">
                <button onclick="closeManageItemsModal()"
                    class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Close</button>
                <button onclick="saveAllServiceItems()"
                    class="px-4 py-2 bg-[#0f7ea0] text-white rounded hover:bg-[#0c6a86]">
                    <i class="fas fa-save mr-1"></i> Save All Changes
                </button>
            </div>
        </div>
    </div>

    <!-- Legacy Manage Products Modal (keeping for backwards compatibility) -->
    <div id="manageProductsModal" class="hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-4xl max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold">Manage Products for Service</h3>
                <p class="text-sm text-gray-600" id="serviceNameDisplay"></p>
                <button onclick="closeManageProductsModal()"
                    class="text-gray-500 hover:text-gray-700 text-xl">&times;</button>
            </div>

            <input type="hidden" id="currentServiceId">
            <input type="hidden" id="currentServiceType">
            <input type="hidden" id="currentServiceBranchId">

            <div class="bg-gray-50 p-4 rounded-lg mb-4">
                <h4 class="font-semibold mb-3">Add Consumable Product to Service</h4>
                <div class="mb-2">
                    <small class="text-blue-600"><i class="fas fa-info-circle mr-1"></i>Showing consumable products matching service type: <strong id="serviceTypeDisplay"></strong> and branch</small>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Select Consumable Product</label>
                        <select id="productSelect" class="border p-2 w-full rounded">
                            <option value="">-- Select Consumable Product --</option>
                            <!-- Products will be loaded dynamically based on service type and branch -->
                        </select>
                        <small class="text-gray-500">Only consumable products matching this service type and branch are shown</small>
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

    <!-- Legacy Manage Equipment Modal (keeping for backwards compatibility) -->
    <div id="manageEquipmentModal" class="hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-4xl max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold"><i class="fas fa-tools mr-2"></i>Manage Equipment for Service</h3>
                <p class="text-sm text-gray-600" id="equipmentServiceNameDisplay"></p>
                <button onclick="closeManageEquipmentModal()"
                    class="text-gray-500 hover:text-gray-700 text-xl">&times;</button>
            </div>

            <input type="hidden" id="currentEquipmentServiceId">
            <input type="hidden" id="currentEquipmentBranchId">

            <div class="bg-gray-50 p-4 rounded-lg mb-4">
                <h4 class="font-semibold mb-3">Add Equipment to Service</h4>
                <div class="mb-2">
                    <small class="text-blue-600"><i class="fas fa-info-circle mr-1"></i>Showing <strong>Furniture & General Clinic Equipment</strong> from the same branch</small>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Select Equipment</label>
                        <select id="equipmentSelect" class="border p-2 w-full rounded">
                            <option value="">-- Select Equipment --</option>
                            <!-- Equipment will be loaded dynamically based on branch -->
                        </select>
                        <small class="text-gray-500">Only Furniture & General Clinic Equipment from this branch is shown</small>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                        <input type="number" id="equipmentQuantityUsed" min="1" value="1"
                            class="border p-2 w-full rounded" placeholder="1">
                    </div>
                    <div class="flex items-end">
                        <button onclick="addEquipmentToService()"
                            class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 w-full">
                            <i class="fas fa-plus mr-1"></i> Add
                        </button>
                    </div>
                </div>
                <div class="mt-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                    <input type="text" id="equipmentNotes" class="border p-2 w-full rounded" placeholder="Enter any notes about equipment usage...">
                </div>
            </div>

            <div>
                <h4 class="font-semibold mb-3">Equipment Linked to This Service</h4>
                <div id="serviceEquipmentList" class="space-y-2">
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <button onclick="closeManageEquipmentModal()"
                    class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Close</button>
                <button onclick="saveServiceEquipment()"
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

    <!-- Linked Consumables Modal -->
    <div id="linkedConsumablesModal" class="hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-3xl max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold">
                    <i class="fas fa-link mr-2 text-cyan-600"></i>
                    Linked Consumables for: <span id="linkedConsumableProductName" class="text-cyan-600"></span>
                </h3>
                <button type="button" onclick="closeLinkedConsumablesModal()" class="text-gray-500 hover:text-gray-700 text-xl">&times;</button>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                <p class="text-sm text-blue-800">
                    <i class="fas fa-info-circle mr-1"></i>
                    <strong>How it works:</strong> When this product is used in a service (e.g., vaccine), all linked consumables (e.g., syringe) will be automatically deducted from inventory.
                </p>
            </div>

            <!-- Add Consumable Form -->
            <div class="bg-gray-50 p-4 rounded-lg mb-4">
                <h4 class="font-semibold mb-3">Add Linked Consumable</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Select Consumable Product *</label>
                        <select id="linkedConsumableSelect" class="border p-2 w-full rounded">
                            <option value="">Select a consumable product...</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
                        <div class="flex gap-2">
                            <input type="number" id="linkedConsumableQty" value="1" min="1" class="border p-2 w-full rounded" placeholder="Qty">
                            <button type="button" onclick="addLinkedConsumable()" class="px-4 py-2 bg-cyan-600 text-white rounded hover:bg-cyan-700 whitespace-nowrap">
                                <i class="fas fa-plus mr-1"></i> Add
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Linked Consumables List -->
            <div>
                <h4 class="font-semibold mb-3">Currently Linked Consumables</h4>
                <div id="linkedConsumablesList" class="space-y-2">
                    <div class="text-center py-4 text-gray-500">
                        <i class="fas fa-spinner fa-spin mr-1"></i> Loading linked consumables...
                    </div>
                </div>
            </div>

            <input type="hidden" id="linkedConsumableProductId" value="">
        </div>
    </div>

    <!-- Manufacturer Management Modal -->
    <div id="manufacturerModal" class="hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold"><i class="fas fa-industry mr-2"></i>Manage Manufacturers</h3>
                <button type="button" onclick="closeManufacturerModal()" class="text-gray-500 hover:text-gray-700 text-xl">&times;</button>
            </div>

            <!-- Add Manufacturer Form -->
            <div class="bg-gray-50 p-4 rounded-lg mb-4">
                <h4 class="font-semibold mb-3">Add New Manufacturer</h4>
                <div class="flex gap-3">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Manufacturer Name *</label>
                        <input type="text" id="newManufacturerName" placeholder="Enter manufacturer name" class="border p-2 w-full rounded" required>
                    </div>
                    <div class="flex items-end">
                        <button type="button" onclick="addManufacturer()" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                            <i class="fas fa-plus mr-1"></i> Add
                        </button>
                    </div>
                </div>
            </div>

            <!-- Manufacturers List -->
            <div>
                <h4 class="font-semibold mb-3">Existing Manufacturers</h4>
                <div id="manufacturersList" class="space-y-2 max-h-64 overflow-y-auto">
                    <div class="text-center py-4 text-gray-500">
                        <i class="fas fa-spinner fa-spin mr-1"></i> Loading manufacturers...
                    </div>
                </div>
            </div>

            <div class="flex justify-end mt-4">
                <button type="button" onclick="closeManufacturerModal()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Close</button>
            </div>
        </div>
    </div>

    <!-- Edit Manufacturer Modal -->
    <div id="editManufacturerModal" class="hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-[60]">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
            <h3 class="text-lg font-bold mb-4"><i class="fas fa-edit mr-2"></i>Edit Manufacturer</h3>
            <input type="hidden" id="editManufacturerId">
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Manufacturer Name *</label>
                    <input type="text" id="editManufacturerName" class="border p-2 w-full rounded" required>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-4">
                <button type="button" onclick="closeEditManufacturerModal()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
                <button type="button" onclick="updateManufacturer()" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Update</button>
            </div>
        </div>
    </div>

    <div id="updateStockModal" class="hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
            <h3 class="text-lg font-bold mb-4">Add Stock Batch</h3>
            <form id="updateStockForm" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="product_id">
                <input type="hidden" name="tab" value="products">

                <div class="mb-4 bg-gray-50 p-3 rounded">
                    <div class="text-sm text-gray-600">Current Available Stock: <span id="currentStock" class="font-bold"></span>
                    </div>
                    <div class="text-sm text-gray-600">Product: <span id="productName" class="font-bold"></span></div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Batch Number/Code *</label>
                    <input type="text" name="batch" id="autoBatchCode" class="border p-2 w-full rounded bg-gray-100 cursor-not-allowed" required maxlength="100" readonly>
                    <small class="text-gray-500">Auto-generated: [Product 3 letters]-[MMDD]-[HMM] (e.g., PAN-1202-451)</small>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
                    <input type="number" name="add_stock" placeholder="Enter quantity to add"
                        class="border p-2 w-full rounded" required min="1">
                    <small class="text-gray-500">This will create a new stock batch</small>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Expiry Date *</label>
                    <input type="date" name="new_expiry" class="border p-2 w-full rounded" required>
                    <small class="text-gray-500">Expiry date for this batch (must be in the future)</small>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="notes" placeholder="Supplier, lot number, purchase order, etc." class="border p-2 w-full rounded"
                        rows="2"></textarea>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeUpdateStockModal()"
                        class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                        <i class="fas fa-plus mr-1"></i> Add Batch
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="damagePulloutModal" class="hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
            <h3 class="text-lg font-bold mb-4">Record Damage/Pull-out</h3>
            <form id="damagePulloutForm" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="product_id">
                <input type="hidden" name="tab" value="products">

                <div class="mb-4 bg-gray-50 p-3 rounded">
                    <div class="text-sm text-gray-600">Product: <span id="damageProductName" class="font-bold"></span></div>
                    <div class="text-sm text-gray-600">Current Available Stock: <span id="damageCurrentStock"
                                class="font-bold"></span></div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Select Stock Batch *</label>
                    <select name="stock_id" id="stockBatchSelect" class="border p-2 w-full rounded" required>
                        <option value="">-- Select Batch --</option>
                    </select>
                    <small class="text-gray-500">Choose which batch to record damage/pullout from</small>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Damaged Quantity</label>
                    <input type="number" name="damaged_qty" placeholder="Enter damaged quantity"
                        class="border p-2 w-full rounded" min="0" value="0">
                    <small class="text-gray-500">Number of damaged items</small>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pull-out Quantity</label>
                    <input type="number" name="pullout_qty" placeholder="Enter pull-out quantity"
                        class="border p-2 w-full rounded" min="0" value="0">
                    <small class="text-gray-500">Items pulled out for quality control</small>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reason *</label>
                    <textarea name="reason" placeholder="Reason for damage/pull-out" class="border p-2 w-full rounded"
                        rows="2" required></textarea>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeDamagePulloutModal()"
                        class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
                    <button type="submit"
                        class="px-4 py-2 bg-orange-600 text-white rounded hover:bg-orange-700">
                        <i class="fas fa-exclamation-triangle mr-1"></i> Record
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="generalModal" class="hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-4xl max-h-[90vh] overflow-y-auto">
            <h3 id="generalModalTitle" class="text-lg font-bold mb-4"></h3>
            <form id="generalModalForm" method="POST" enctype="multipart/form-data" onsubmit="return validateEquipmentFormSubmit()">
                @csrf
                {{-- FIX: Renamed 'active_tab' to 'tab' for consistency with URL --}}
                <input type="hidden" name="tab" id="active_tab" value=""> 
                <div id="generalModalFields" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>

                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" onclick="closeGeneralModal()"
                        class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
                    <button type="submit" id="generalModalSubmitBtn" class="px-4 py-2 bg-[#0f7ea0] text-white rounded hover:bg-[#0c6a86]">Save</button>
                </div>
            </form>
        </div>
    </div>

    <div id="serviceInventoryModal"
        class="hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-7xl max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold"><i class="fas fa-clipboard-list text-purple-600 mr-2"></i>Complete Service Usage History</h3>
                <button onclick="closeServiceInventoryModal()"
                    class="text-gray-500 hover:text-gray-700 text-xl">&times;</button>
            </div>
            
            <!-- Summary Cards -->
            <div id="serviceInventorySummary" class="grid grid-cols-2 md:grid-cols-6 gap-3 mb-4">
                <div class="bg-purple-50 p-3 rounded-lg border border-purple-200 text-center">
                    <p class="text-xs text-purple-600">Total Services</p>
                    <p class="text-xl font-bold text-purple-700" id="serviceSummaryTotal">-</p>
                </div>
                <div class="bg-green-50 p-3 rounded-lg border border-green-200 text-center">
                    <p class="text-xs text-green-600">Completed</p>
                    <p class="text-xl font-bold text-green-700" id="serviceSummaryCompleted">-</p>
                </div>
                <div class="bg-yellow-50 p-3 rounded-lg border border-yellow-200 text-center">
                    <p class="text-xs text-yellow-600">Pending</p>
                    <p class="text-xl font-bold text-yellow-700" id="serviceSummaryPending">-</p>
                </div>
                <div class="bg-blue-50 p-3 rounded-lg border border-blue-200 text-center">
                    <p class="text-xs text-blue-600">Patients Served</p>
                    <p class="text-xl font-bold text-blue-700" id="serviceSummaryPatients">-</p>
                </div>
                <div class="bg-amber-50 p-3 rounded-lg border border-amber-200 text-center">
                    <p class="text-xs text-amber-600">Service Types</p>
                    <p class="text-xl font-bold text-amber-700" id="serviceSummaryTypes">-</p>
                </div>
                <div class="bg-emerald-50 p-3 rounded-lg border border-emerald-200 text-center">
                    <p class="text-xs text-emerald-600">Total Revenue</p>
                    <p class="text-xl font-bold text-emerald-700" id="serviceSummaryRevenue">-</p>
                </div>
            </div>
            
            <!-- Filter Controls -->
            <div class="flex flex-wrap gap-3 mb-4 items-center">
                <div class="flex-1 min-w-[200px]">
                    <input type="text" id="serviceMovementSearchInput" placeholder="Search by service, pet, or owner name..." 
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                        onkeyup="filterServiceMovementHistory()">
                </div>
                <div>
                    <select id="serviceTypeFilter" class="border border-gray-300 rounded px-3 py-2 text-sm" onchange="filterServiceMovementHistory()">
                        <option value="">All Service Types</option>
                        <option value="Vaccination">Vaccination</option>
                        <option value="Deworming">Deworming</option>
                        <option value="Grooming">Grooming</option>
                        <option value="Treatment">Treatment</option>
                        <option value="Surgery">Surgery</option>
                        <option value="Checkup">Checkup</option>
                        <option value="Boarding">Boarding</option>
                        <option value="Diagnostics">Diagnostics</option>
                        <option value="Consultation">Consultation</option>
                    </select>
                </div>
                <div>
                    <select id="serviceStatusFilter" class="border border-gray-300 rounded px-3 py-2 text-sm" onchange="filterServiceMovementHistory()">
                        <option value="">All Status</option>
                        <option value="completed">Completed</option>
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                    </select>
                </div>
                <button onclick="loadServiceMovementHistory()" class="bg-purple-500 text-white px-4 py-2 rounded text-sm hover:bg-purple-600">
                    <i class="fas fa-sync-alt mr-1"></i> Refresh
                </button>
            </div>
            
            <!-- Service Usage History Table -->
            <div id="serviceInventoryContent">
                <div class="text-center py-8">
                    <i class="fas fa-spinner fa-spin text-3xl text-purple-600"></i>
                    <p class="mt-2">Loading service usage history...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Equipment Assignment History Modal -->
    <div id="equipmentInventoryModal"
        class="hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-7xl max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold"><i class="fas fa-history text-purple-600 mr-2"></i>Equipment Assignment History</h3>
                <button onclick="closeEquipmentInventoryModal()"
                    class="text-gray-500 hover:text-gray-700 text-xl">&times;</button>
            </div>
            
            <!-- Summary Cards -->
            <div id="equipmentInventorySummary" class="grid grid-cols-2 md:grid-cols-6 gap-3 mb-4">
                <div class="bg-purple-50 p-3 rounded-lg border border-purple-200 text-center">
                    <p class="text-xs text-purple-600">Total Records</p>
                    <p class="text-xl font-bold text-purple-700" id="equipmentSummaryTotal">-</p>
                </div>
                <div class="bg-green-50 p-3 rounded-lg border border-green-200 text-center">
                    <p class="text-xs text-green-600">Checked In</p>
                    <p class="text-xl font-bold text-green-700" id="equipmentSummaryCheckedIn">-</p>
                </div>
                <div class="bg-blue-50 p-3 rounded-lg border border-blue-200 text-center">
                    <p class="text-xs text-blue-600">Checked Out</p>
                    <p class="text-xl font-bold text-blue-700" id="equipmentSummaryCheckedOut">-</p>
                </div>
                <div class="bg-amber-50 p-3 rounded-lg border border-amber-200 text-center">
                    <p class="text-xs text-amber-600">Active Now</p>
                    <p class="text-xl font-bold text-amber-700" id="equipmentSummaryActive">-</p>
                </div>
                <div class="bg-teal-50 p-3 rounded-lg border border-teal-200 text-center">
                    <p class="text-xs text-teal-600">Equipment Used</p>
                    <p class="text-xl font-bold text-teal-700" id="equipmentSummaryUnique">-</p>
                </div>
                <div class="bg-pink-50 p-3 rounded-lg border border-pink-200 text-center">
                    <p class="text-xs text-pink-600">Pets Served</p>
                    <p class="text-xl font-bold text-pink-700" id="equipmentSummaryPets">-</p>
                </div>
            </div>
            
            <!-- Filter Controls -->
            <div class="flex flex-wrap gap-3 mb-4 items-center">
                <div class="flex-1 min-w-[200px]">
                    <input type="text" id="equipmentMovementSearchInput" placeholder="Search by equipment, pet, or owner name..." 
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                        onkeyup="filterEquipmentMovementHistory()">
                </div>
                <div>
                    <select id="equipmentCategoryFilter" class="border border-gray-300 rounded px-3 py-2 text-sm" onchange="filterEquipmentMovementHistory()">
                        <option value="">All Categories</option>
                        <option value="Cage">Cage</option>
                        <option value="Room">Room</option>
                        <option value="Ward">Ward</option>
                        <option value="Kennel">Kennel</option>
                        <option value="Equipment">Equipment</option>
                    </select>
                </div>
                <div>
                    <select id="equipmentActionFilter" class="border border-gray-300 rounded px-3 py-2 text-sm" onchange="filterEquipmentMovementHistory()">
                        <option value="">All Actions</option>
                        <option value="assigned">Checked In</option>
                        <option value="released">Checked Out</option>
                        <option value="reserved">Reserved</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
                <button onclick="loadEquipmentMovementHistory()" class="bg-purple-500 text-white px-4 py-2 rounded text-sm hover:bg-purple-600">
                    <i class="fas fa-sync-alt mr-1"></i> Refresh
                </button>
            </div>
            
            <!-- Equipment Assignment History Table -->
            <div id="equipmentInventoryContent">
                <div class="text-center py-8">
                    <i class="fas fa-spinner fa-spin text-3xl text-purple-600"></i>
                    <p class="mt-2">Loading equipment assignment history...</p>
                </div>
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
            const activeTabParam = urlParams.get('tab') || 'services'; // Default to 'services'

            let tabId = 'servicesTab';
            let tabBtnId = 'servicesBtn';

            if (activeTabParam === 'products') {
                tabId = 'productInventoryTab';
                tabBtnId = 'productInventoryBtn';
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

            // Initialize ListFilters AFTER tabs are set up
            setTimeout(() => {
                if (typeof ListFilter !== 'undefined') {
                    window.listFilters = window.listFilters || {};
                    
                    // Initialize filter for active tab only
                    if (activeTabParam === 'products') {
                        const tbody = document.querySelector('#productsTable tbody');
                        const rowCount = tbody ? tbody.querySelectorAll('tr').length : 0;
                        console.log('Products tbody found:', !!tbody, 'Row count:', rowCount);
                        
                        if (tbody && rowCount > 0) {
                            window.listFilters['products'] = new ListFilter({
                                tableSelector: '#productsTable tbody',
                                searchInputId: 'productsSearch',
                                perPageSelectId: 'productsPerPage',
                                paginationContainerId: 'productsPagination',
                                searchColumns: [1, 2, 3, 4, 5, 7],
                                filterSelects: [
                                    { selectId: 'productsType', columnIndex: 3 }
                                ],
                                storageKey: 'productsFilter',
                                noResultsMessage: 'No products found.'
                            });
                            console.log('Products filter initialized with', window.listFilters['products'].allRows.length, 'rows');
                        } else {
                            console.error('Products table not ready or empty');
                        }
                    } else if (activeTabParam === 'services') {
                        const tbody = document.querySelector('#servicesTable tbody');
                        const rowCount = tbody ? tbody.querySelectorAll('tr').length : 0;
                        console.log('Services tbody found:', !!tbody, 'Row count:', rowCount);
                        
                        if (tbody && rowCount > 0) {
                            window.listFilters['services'] = new ListFilter({
                                tableSelector: '#servicesTable tbody',
                                searchInputId: 'servicesSearch',
                                perPageSelectId: 'servicesPerPage',
                                paginationContainerId: 'servicesPagination',
                                searchColumns: [0, 1, 2, 3, 4],
                                filterSelects: [
                                    { selectId: 'servicesCategoryFilter', columnIndex: 1 } // Type column
                                ],
                                storageKey: 'servicesFilter',
                                noResultsMessage: 'No services found.'
                            });
                            console.log('Services filter initialized with', window.listFilters['services'].allRows.length, 'rows');
                        } else {
                            console.error('Services table not ready or empty');
                        }
                    } else if (activeTabParam === 'equipment') {
                        const tbody = document.querySelector('#equipmentTable tbody');
                        const rowCount = tbody ? tbody.querySelectorAll('tr').length : 0;
                        
                        if (tbody && rowCount > 0) {
                            window.listFilters['equipment'] = new ListFilter({
                                tableSelector: '#equipmentTable tbody',
                                searchInputId: 'equipmentSearch',
                                perPageSelectId: 'equipmentPerPage',
                                paginationContainerId: 'equipmentPagination',
                                searchColumns: [1, 2, 3, 4, 5],
                                storageKey: 'equipmentFilter',
                                noResultsMessage: 'No equipment found.'
                            });
                        }
                    }
                } else {
                    console.error('ListFilter class not found');
                }
            }, 500);
        });

        // ====================================
        // UNIFIED MANAGE ITEMS MODAL (Consumable Products & Equipment)
        // ====================================
        let itemsProducts = [];
        let itemsEquipment = [];

        function openManageItemsModal(serviceId, serviceName, serviceType, branchId) {
            document.getElementById('manageItemsModal').classList.remove('hidden');
            document.getElementById('currentItemsServiceId').value = serviceId;
            document.getElementById('currentItemsServiceType').value = serviceType || '';
            document.getElementById('currentItemsBranchId').value = branchId || '';
            document.getElementById('itemsServiceNameDisplay').textContent = serviceName;
            document.getElementById('itemsServiceTypeDisplay').textContent = serviceType || 'All';

            // Reset to consumable tab
            switchItemsTab('consumable');

            // Load filtered consumable products for this service type and branch
            loadItemsFilteredProducts(serviceType, branchId);

            // Load filtered equipment for this branch
            loadItemsFilteredEquipment(branchId);

            // Load existing products for this service
            loadItemsServiceProducts(serviceId);

            // Load existing equipment for this service
            loadItemsServiceEquipment(serviceId);
        }

        function switchItemsTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.items-tab-content').forEach(content => {
                content.classList.add('hidden');
            });

            // Reset all tab buttons
            document.querySelectorAll('.items-tab-btn').forEach(btn => {
                btn.classList.remove('border-indigo-500', 'text-indigo-600');
                btn.classList.add('border-transparent', 'text-gray-500');
            });

            // Show selected tab content and activate button
            if (tabName === 'consumable') {
                document.getElementById('consumableTabContent').classList.remove('hidden');
                document.getElementById('consumableTabBtn').classList.add('border-indigo-500', 'text-indigo-600');
                document.getElementById('consumableTabBtn').classList.remove('border-transparent', 'text-gray-500');
            } else if (tabName === 'equipment') {
                document.getElementById('equipmentTabContent').classList.remove('hidden');
                document.getElementById('equipmentTabBtn').classList.add('border-indigo-500', 'text-indigo-600');
                document.getElementById('equipmentTabBtn').classList.remove('border-transparent', 'text-gray-500');
            }
        }

        function loadItemsFilteredProducts(serviceType, branchId) {
            const productSelect = document.getElementById('itemsProductSelect');
            productSelect.innerHTML = '<option value="">-- Loading Products --</option>';
            
            let url = '/products/consumable-by-filter';
            const params = new URLSearchParams();
            if (serviceType) params.append('service_type', serviceType);
            if (branchId) params.append('branch_id', branchId);
            
            if (params.toString()) {
                url += '?' + params.toString();
            }

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    let options = '<option value="">-- Select Consumable Product --</option>';
                    if (data.success && data.products && data.products.length > 0) {
                        data.products.forEach(product => {
                            const stock = product.available_stock || 0;
                            options += `<option value="${product.prod_id}" data-name="${escapeHtml(product.prod_name)}" data-stock="${stock}" data-category="${product.prod_category || ''}">${escapeHtml(product.prod_name)} (Stock: ${stock})</option>`;
                        });
                    } else {
                        options += '<option value="" disabled>-- No matching products found --</option>';
                    }
                    productSelect.innerHTML = options;
                })
                .catch(error => {
                    console.error('Error loading filtered products:', error);
                    productSelect.innerHTML = '<option value="">-- Error loading products --</option>';
                });
        }

        function loadItemsFilteredEquipment(branchId) {
            const equipmentSelect = document.getElementById('itemsEquipmentSelect');
            equipmentSelect.innerHTML = '<option value="">-- Loading Equipment --</option>';
            
            let url = '/equipment/by-branch';
            const params = new URLSearchParams();
            if (branchId) params.append('branch_id', branchId);
            
            if (params.toString()) {
                url += '?' + params.toString();
            }

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    let options = '<option value="">-- Select Equipment --</option>';
                    if (data.success && data.equipment && data.equipment.length > 0) {
                        data.equipment.forEach(eq => {
                            const available = eq.equipment_available || 0;
                            options += `<option value="${eq.equipment_id}" data-name="${escapeHtml(eq.equipment_name)}" data-available="${available}" data-category="${eq.equipment_category || ''}">${escapeHtml(eq.equipment_name)} (Available: ${available})</option>`;
                        });
                    } else {
                        options += '<option value="" disabled>-- No equipment found --</option>';
                    }
                    equipmentSelect.innerHTML = options;
                })
                .catch(error => {
                    console.error('Error loading equipment:', error);
                    equipmentSelect.innerHTML = '<option value="">-- Error loading equipment --</option>';
                });
        }

        function loadItemsServiceProducts(serviceId) {
            document.getElementById('itemsServiceProductsList').innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Loading products...</div>';

            fetch(`/services/${serviceId}/products`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        itemsProducts = data.products;
                        renderItemsServiceProducts();
                    } else {
                        document.getElementById('itemsServiceProductsList').innerHTML = '<div class="text-red-500">Error loading products</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('itemsServiceProductsList').innerHTML = '<div class="text-red-500">Error loading products</div>';
                });
        }

        function loadItemsServiceEquipment(serviceId) {
            document.getElementById('itemsServiceEquipmentList').innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Loading equipment...</div>';

            fetch(`/services/${serviceId}/equipment`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        itemsEquipment = data.equipment;
                        renderItemsServiceEquipment();
                    } else {
                        document.getElementById('itemsServiceEquipmentList').innerHTML = '<div class="text-red-500">Error loading equipment</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('itemsServiceEquipmentList').innerHTML = '<div class="text-red-500">Error loading equipment</div>';
                });
        }

        function renderItemsServiceProducts() {
            const container = document.getElementById('itemsServiceProductsList');

            if (itemsProducts.length === 0) {
                container.innerHTML = '<div class="text-center text-gray-500 py-4">No consumable products linked to this service yet.</div>';
                return;
            }

            let html = '<div class="space-y-2">';
            itemsProducts.forEach((item, index) => {
                const stockWarning = item.current_stock < item.quantity_used ? 'border-red-300 bg-red-50' : 'border-gray-200';
                html += `
                <div class="flex items-center justify-between p-3 border ${stockWarning} rounded">
                    <div class="flex-1">
                        <div class="font-medium">${escapeHtml(item.product_name)}</div>
                        <div class="text-sm text-gray-600">
                            Quantity Used: <span class="font-semibold">${item.quantity_used}</span> | 
                            Current Stock: <span class="${item.current_stock < item.quantity_used ? 'text-red-600 font-bold' : 'text-green-600'}">${item.current_stock}</span>
                            ${item.current_stock < item.quantity_used ? '<span class="text-red-600 ml-2"><i class="fas fa-exclamation-triangle"></i> Low Stock!</span>' : ''}
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="number" step="0.01" min="0.01" value="${item.quantity_used}" 
                               onchange="updateItemsProductQuantity(${index}, this.value)"
                               class="border p-1 w-20 rounded text-sm">
                        <button onclick="removeItemsProductFromService(${index})" 
                                class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 text-sm">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>`;
            });
            html += '</div>';

            container.innerHTML = html;
        }

        function renderItemsServiceEquipment() {
            const container = document.getElementById('itemsServiceEquipmentList');

            if (itemsEquipment.length === 0) {
                container.innerHTML = '<div class="text-center text-gray-500 py-4">No equipment linked to this service yet.</div>';
                return;
            }

            let html = '<div class="space-y-2">';
            itemsEquipment.forEach((item, index) => {
                const availableWarning = item.available_quantity < item.quantity_used ? 'border-red-300 bg-red-50' : 'border-gray-200';
                html += `
                <div class="flex items-center justify-between p-3 border ${availableWarning} rounded">
                    <div class="flex-1">
                        <div class="font-medium">${escapeHtml(item.equipment_name)}</div>
                        <div class="text-sm text-gray-600">
                            Quantity: <span class="font-semibold">${item.quantity_used}</span> | 
                            Available: <span class="${item.available_quantity < item.quantity_used ? 'text-red-600 font-bold' : 'text-green-600'}">${item.available_quantity}</span>
                            ${item.available_quantity < item.quantity_used ? '<span class="text-red-600 ml-2"><i class="fas fa-exclamation-triangle"></i> Low Availability!</span>' : ''}
                        </div>
                        ${item.notes ? `<div class="text-sm text-gray-500 italic mt-1"><i class="fas fa-sticky-note mr-1"></i>${escapeHtml(item.notes)}</div>` : ''}
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="number" min="1" value="${item.quantity_used}" 
                               onchange="updateItemsEquipmentQuantity(${index}, this.value)"
                               class="border p-1 w-20 rounded text-sm">
                        <button onclick="removeItemsEquipmentFromService(${index})" 
                                class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 text-sm">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>`;
            });
            html += '</div>';

            container.innerHTML = html;
        }

        function addItemProductToService() {
            const select = document.getElementById('itemsProductSelect');
            const prodId = select.value;
            const quantityUsed = parseFloat(document.getElementById('itemsQuantityUsed').value);

            if (!prodId) {
                alert('Please select a product');
                return;
            }

            if (!quantityUsed || quantityUsed <= 0) {
                alert('Please enter a valid quantity');
                return;
            }

            // Check if product already exists
            if (itemsProducts.find(p => p.prod_id == prodId)) {
                alert('This product is already linked to this service');
                return;
            }

            const selectedOption = select.options[select.selectedIndex];

            itemsProducts.push({
                prod_id: prodId,
                product_name: selectedOption.dataset.name,
                quantity_used: quantityUsed,
                current_stock: selectedOption.dataset.stock,
                is_billable: false
            });

            // Reset form
            select.value = '';
            document.getElementById('itemsQuantityUsed').value = '1.00';

            renderItemsServiceProducts();
        }

        function addItemEquipmentToService() {
            const select = document.getElementById('itemsEquipmentSelect');
            const equipmentId = select.value;
            const quantityUsed = parseInt(document.getElementById('itemsEquipmentQuantityUsed').value);
            const notes = document.getElementById('itemsEquipmentNotes').value.trim();

            if (!equipmentId) {
                alert('Please select equipment');
                return;
            }

            if (!quantityUsed || quantityUsed <= 0) {
                alert('Please enter a valid quantity');
                return;
            }

            // Check if equipment already exists
            if (itemsEquipment.find(e => e.equipment_id == equipmentId)) {
                alert('This equipment is already linked to this service');
                return;
            }

            const selectedOption = select.options[select.selectedIndex];

            itemsEquipment.push({
                equipment_id: equipmentId,
                equipment_name: selectedOption.dataset.name,
                quantity_used: quantityUsed,
                available_quantity: selectedOption.dataset.available,
                notes: notes
            });

            // Reset form
            select.value = '';
            document.getElementById('itemsEquipmentQuantityUsed').value = '1';
            document.getElementById('itemsEquipmentNotes').value = '';

            renderItemsServiceEquipment();
        }

        function updateItemsProductQuantity(index, newQuantity) {
            itemsProducts[index].quantity_used = parseFloat(newQuantity);
        }

        function updateItemsEquipmentQuantity(index, newQuantity) {
            itemsEquipment[index].quantity_used = parseInt(newQuantity);
        }

        function removeItemsProductFromService(index) {
            if (confirm('Remove this product from the service?')) {
                itemsProducts.splice(index, 1);
                renderItemsServiceProducts();
            }
        }

        function removeItemsEquipmentFromService(index) {
            if (confirm('Remove this equipment from the service?')) {
                itemsEquipment.splice(index, 1);
                renderItemsServiceEquipment();
            }
        }

        function closeManageItemsModal() {
            document.getElementById('manageItemsModal').classList.add('hidden');
            itemsProducts = [];
            itemsEquipment = [];
            document.getElementById('itemsServiceProductsList').innerHTML = '';
            document.getElementById('itemsServiceEquipmentList').innerHTML = '';
            document.getElementById('itemsProductSelect').innerHTML = '<option value="">-- Select Consumable Product --</option>';
            document.getElementById('itemsEquipmentSelect').innerHTML = '<option value="">-- Select Equipment --</option>';
            document.getElementById('itemsQuantityUsed').value = '1.00';
            document.getElementById('itemsEquipmentQuantityUsed').value = '1';
            document.getElementById('itemsEquipmentNotes').value = '';
            document.getElementById('currentItemsServiceType').value = '';
            document.getElementById('currentItemsBranchId').value = '';
            document.getElementById('itemsServiceTypeDisplay').textContent = '';
        }

        function saveAllServiceItems() {
            const serviceId = document.getElementById('currentItemsServiceId').value;

            // Prepare products data
            const productsData = itemsProducts.map(p => ({
                prod_id: p.prod_id,
                quantity_used: p.quantity_used,
                is_billable: p.is_billable || false
            }));

            // Prepare equipment data
            const equipmentData = itemsEquipment.map(e => ({
                equipment_id: e.equipment_id,
                quantity_used: e.quantity_used,
                notes: e.notes || null
            }));

            // Show loading
            const saveBtn = event.target;
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Saving...';
            saveBtn.disabled = true;

            // Save both products and equipment
            Promise.all([
                fetch(`/services/${serviceId}/products`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ products: productsData })
                }).then(response => response.json()),
                
                fetch(`/services/${serviceId}/equipment`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ equipment: equipmentData })
                }).then(response => response.json())
            ])
            .then(([productsResult, equipmentResult]) => {
                if (productsResult.success && equipmentResult.success) {
                    alert('✅ All items saved successfully!');
                    closeManageItemsModal();
                } else {
                    let errorMsg = '';
                    if (!productsResult.success) errorMsg += 'Products: ' + (productsResult.error || 'Failed to save') + '\n';
                    if (!equipmentResult.success) errorMsg += 'Equipment: ' + (equipmentResult.error || 'Failed to save');
                    alert('❌ Error saving items:\n' + errorMsg);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ Error saving items. Please try again.');
            })
            .finally(() => {
                saveBtn.innerHTML = originalText;
                saveBtn.disabled = false;
            });
        }

        // ... [MANAGE SERVICE PRODUCTS FUNCTIONS REMAIN UNCHANGED] ...

        // MANAGE SERVICE PRODUCTS
        let serviceProducts = [];

        function openManageProductsModal(serviceId, serviceName, serviceType, branchId) {
            document.getElementById('manageProductsModal').classList.remove('hidden');
            document.getElementById('currentServiceId').value = serviceId;
            document.getElementById('currentServiceType').value = serviceType || '';
            document.getElementById('currentServiceBranchId').value = branchId || '';
            document.getElementById('serviceNameDisplay').textContent = serviceName;
            document.getElementById('serviceTypeDisplay').textContent = serviceType || 'All';

            // Load filtered consumable products for this service type and branch
            loadFilteredProducts(serviceType, branchId);

            // Load existing products for this service
            loadServiceProducts(serviceId);
        }

        function loadFilteredProducts(serviceType, branchId) {
            const productSelect = document.getElementById('productSelect');
            productSelect.innerHTML = '<option value="">-- Loading Products --</option>';
            
            // Build query params
            let url = '/products/consumable-by-filter';
            const params = new URLSearchParams();
            if (serviceType) params.append('service_type', serviceType);
            if (branchId) params.append('branch_id', branchId);
            
            if (params.toString()) {
                url += '?' + params.toString();
            }

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    let options = '<option value="">-- Select Consumable Product --</option>';
                    if (data.success && data.products && data.products.length > 0) {
                        data.products.forEach(product => {
                            const stock = product.available_stock || 0;
                            options += `<option value="${product.prod_id}" data-name="${escapeHtml(product.prod_name)}" data-stock="${stock}" data-category="${product.prod_category || ''}">${escapeHtml(product.prod_name)} (Stock: ${stock})</option>`;
                        });
                    } else {
                        options += '<option value="" disabled>-- No matching products found --</option>';
                    }
                    productSelect.innerHTML = options;
                })
                .catch(error => {
                    console.error('Error loading filtered products:', error);
                    productSelect.innerHTML = '<option value="">-- Error loading products --</option>';
                });
        }

        function closeManageProductsModal() {
            document.getElementById('manageProductsModal').classList.add('hidden');
            serviceProducts = [];
            document.getElementById('serviceProductsList').innerHTML = '';
            document.getElementById('productSelect').innerHTML = '<option value="">-- Select Consumable Product --</option>';
            document.getElementById('quantityUsed').value = '1.00';
            document.getElementById('currentServiceType').value = '';
            document.getElementById('currentServiceBranchId').value = '';
            document.getElementById('serviceTypeDisplay').textContent = '';
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

        // ====================================
        // MANAGE SERVICE EQUIPMENT (for Boarding services)
        // ====================================
        let serviceEquipment = [];

        function openManageEquipmentModal(serviceId, serviceName, branchId) {
            document.getElementById('manageEquipmentModal').classList.remove('hidden');
            document.getElementById('currentEquipmentServiceId').value = serviceId;
            document.getElementById('currentEquipmentBranchId').value = branchId || '';
            document.getElementById('equipmentServiceNameDisplay').textContent = serviceName;

            // Load equipment filtered by branch
            loadFilteredEquipment(branchId);

            // Load existing equipment for this service
            loadServiceEquipment(serviceId);
        }

        function loadFilteredEquipment(branchId) {
            const equipmentSelect = document.getElementById('equipmentSelect');
            equipmentSelect.innerHTML = '<option value="">-- Loading Equipment --</option>';
            
            // Build query params
            let url = '/equipment/by-branch';
            const params = new URLSearchParams();
            if (branchId) params.append('branch_id', branchId);
            
            if (params.toString()) {
                url += '?' + params.toString();
            }

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    let options = '<option value="">-- Select Equipment --</option>';
                    if (data.success && data.equipment && data.equipment.length > 0) {
                        data.equipment.forEach(eq => {
                            const available = eq.equipment_available || 0;
                            options += `<option value="${eq.equipment_id}" data-name="${escapeHtml(eq.equipment_name)}" data-available="${available}" data-category="${eq.equipment_category || ''}">${escapeHtml(eq.equipment_name)} (Available: ${available})</option>`;
                        });
                    } else {
                        options += '<option value="" disabled>-- No equipment found --</option>';
                    }
                    equipmentSelect.innerHTML = options;
                })
                .catch(error => {
                    console.error('Error loading equipment:', error);
                    equipmentSelect.innerHTML = '<option value="">-- Error loading equipment --</option>';
                });
        }

        function closeManageEquipmentModal() {
            document.getElementById('manageEquipmentModal').classList.add('hidden');
            serviceEquipment = [];
            document.getElementById('serviceEquipmentList').innerHTML = '';
            document.getElementById('equipmentSelect').innerHTML = '<option value="">-- Select Equipment --</option>';
            document.getElementById('equipmentQuantityUsed').value = '1';
            document.getElementById('equipmentNotes').value = '';
            document.getElementById('currentEquipmentBranchId').value = '';
        }

        function loadServiceEquipment(serviceId) {
            document.getElementById('serviceEquipmentList').innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Loading equipment...</div>';

            fetch(`/services/${serviceId}/equipment`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        serviceEquipment = data.equipment;
                        renderServiceEquipment();
                    } else {
                        document.getElementById('serviceEquipmentList').innerHTML = '<div class="text-red-500">Error loading equipment</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('serviceEquipmentList').innerHTML = '<div class="text-red-500">Error loading equipment</div>';
                });
        }

        function renderServiceEquipment() {
            const container = document.getElementById('serviceEquipmentList');

            if (serviceEquipment.length === 0) {
                container.innerHTML = '<div class="text-center text-gray-500 py-4">No equipment linked to this service yet.</div>';
                return;
            }

            let html = '<div class="space-y-2">';
            serviceEquipment.forEach((item, index) => {
                const availableWarning = item.available_quantity < item.quantity_used ? 'border-red-300 bg-red-50' : 'border-gray-200';
                html += `
                <div class="flex items-center justify-between p-3 border ${availableWarning} rounded">
                    <div class="flex-1">
                        <div class="font-medium">${escapeHtml(item.equipment_name)}</div>
                        <div class="text-sm text-gray-600">
                            Quantity: <span class="font-semibold">${item.quantity_used}</span> | 
                            Available: <span class="${item.available_quantity < item.quantity_used ? 'text-red-600 font-bold' : 'text-green-600'}">${item.available_quantity}</span>
                            ${item.available_quantity < item.quantity_used ? '<span class="text-red-600 ml-2"><i class="fas fa-exclamation-triangle"></i> Low Availability!</span>' : ''}
                        </div>
                        ${item.notes ? `<div class="text-sm text-gray-500 italic mt-1"><i class="fas fa-sticky-note mr-1"></i>${escapeHtml(item.notes)}</div>` : ''}
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="number" min="1" value="${item.quantity_used}" 
                               onchange="updateEquipmentQuantity(${index}, this.value)"
                               class="border p-1 w-20 rounded text-sm">
                        <button onclick="removeEquipmentFromService(${index})" 
                                class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 text-sm">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>`;
            });
            html += '</div>';

            container.innerHTML = html;
        }

        function addEquipmentToService() {
            const select = document.getElementById('equipmentSelect');
            const equipmentId = select.value;
            const quantityUsed = parseInt(document.getElementById('equipmentQuantityUsed').value);
            const notes = document.getElementById('equipmentNotes').value.trim();

            if (!equipmentId) {
                alert('Please select equipment');
                return;
            }

            if (!quantityUsed || quantityUsed <= 0) {
                alert('Please enter a valid quantity');
                return;
            }

            // Check if equipment already exists
            if (serviceEquipment.find(e => e.equipment_id == equipmentId)) {
                alert('This equipment is already linked to this service');
                return;
            }

            const selectedOption = select.options[select.selectedIndex];

            serviceEquipment.push({
                equipment_id: equipmentId,
                equipment_name: selectedOption.dataset.name,
                quantity_used: quantityUsed,
                available_quantity: selectedOption.dataset.available,
                notes: notes
            });

            // Reset form
            select.value = '';
            document.getElementById('equipmentQuantityUsed').value = '1';
            document.getElementById('equipmentNotes').value = '';

            renderServiceEquipment();
        }

        function updateEquipmentQuantity(index, newQuantity) {
            serviceEquipment[index].quantity_used = parseInt(newQuantity);
        }

        function removeEquipmentFromService(index) {
            if (confirm('Remove this equipment from the service?')) {
                serviceEquipment.splice(index, 1);
                renderServiceEquipment();
            }
        }

        function saveServiceEquipment() {
            const serviceId = document.getElementById('currentEquipmentServiceId').value;

            if (serviceEquipment.length === 0) {
                if (!confirm('No equipment is linked to this service. Continue?')) {
                    return;
                }
            }

            // Prepare data
            const equipmentData = serviceEquipment.map(e => ({
                equipment_id: e.equipment_id,
                quantity_used: e.quantity_used,
                notes: e.notes || null
            }));

            // Show loading
            const saveBtn = event.target;
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Saving...';
            saveBtn.disabled = true;

            fetch(`/services/${serviceId}/equipment`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    equipment: equipmentData
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ Equipment saved successfully!');
                        closeManageEquipmentModal();
                    } else {
                        alert('❌ Error: ' + (data.error || 'Failed to save equipment'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('❌ Error saving equipment. Please try again.');
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

        // SERVICE INVENTORY OVERVIEW - Complete Service Usage History (Services performed on patients)
        let allServiceMovements = [];
        
        function openServiceInventoryOverview() {
            document.getElementById('serviceInventoryModal').classList.remove('hidden');
            loadServiceMovementHistory();
        }
        
        function loadServiceMovementHistory() {
            document.getElementById('serviceInventoryContent').innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-3xl text-purple-600"></i><p class="mt-2">Loading service usage history...</p></div>';

            fetch('/services/usage-history')
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        document.getElementById('serviceInventoryContent').innerHTML = `<div class="text-red-500 text-center py-8"><i class="fas fa-exclamation-circle text-3xl mb-2"></i><p>Error loading data: ${data.error || 'Unknown error'}</p></div>`;
                        return;
                    }

                    allServiceMovements = data.movements;
                    const summary = data.summary;

                    // Update summary cards
                    document.getElementById('serviceSummaryTotal').textContent = summary.total_services;
                    document.getElementById('serviceSummaryCompleted').textContent = summary.completed_services;
                    document.getElementById('serviceSummaryPending').textContent = summary.pending_services;
                    document.getElementById('serviceSummaryPatients').textContent = summary.unique_patients;
                    document.getElementById('serviceSummaryTypes').textContent = summary.unique_service_types;
                    document.getElementById('serviceSummaryRevenue').textContent = '₱' + parseFloat(summary.total_revenue).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});

                    renderServiceMovementHistory(allServiceMovements);
                })
                .catch(error => {
                    document.getElementById('serviceInventoryContent').innerHTML = `<div class="text-red-500 text-center py-8"><i class="fas fa-exclamation-circle text-3xl mb-2"></i><p>Error: ${error.message}</p></div>`;
                });
        }
        
        function renderServiceMovementHistory(movements) {
            if (movements.length === 0) {
                document.getElementById('serviceInventoryContent').innerHTML = `
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-clipboard-list text-4xl mb-2"></i>
                        <p>No service records found</p>
                        <p class="text-sm mt-2">Services will appear here when they are performed during patient visits.</p>
                    </div>`;
                return;
            }

            let content = `
                <table class="w-full table-auto text-sm border">
                    <thead class="bg-gray-100 sticky top-0">
                        <tr>
                            <th class="p-2 border text-left">Date & Time</th>
                            <th class="p-2 border text-left">Service</th>
                            <th class="p-2 border text-left">Patient / Owner</th>
                            <th class="p-2 border text-left">Visit Type</th>
                            <th class="p-2 border text-center">Fee</th>
                            <th class="p-2 border text-left">Performed By</th>
                            <th class="p-2 border text-center">Status</th>
                            <th class="p-2 border text-left">Notes</th>
                        </tr>
                    </thead>
                    <tbody>`;

            movements.forEach(movement => {
                const date = new Date(movement.date);
                const formattedDate = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                const formattedTime = date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                
                const completedDate = movement.completed_at ? new Date(movement.completed_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) : '';

                const statusColors = {
                    'completed': 'bg-green-100 text-green-800',
                    'pending': 'bg-yellow-100 text-yellow-800',
                    'in_progress': 'bg-blue-100 text-blue-800',
                    'cancelled': 'bg-red-100 text-red-800',
                };
                const statusClass = statusColors[movement.status] || 'bg-gray-100 text-gray-800';

                const patientTypeColors = {
                    'Outpatient': 'bg-blue-50 text-blue-700',
                    'Inpatient': 'bg-purple-50 text-purple-700',
                    'Emergency': 'bg-red-50 text-red-700',
                };
                const patientTypeClass = patientTypeColors[movement.patient_type] || 'bg-gray-50 text-gray-700';

                const petInfo = movement.pet_species ? `${movement.pet_species}${movement.pet_breed ? ' - ' + movement.pet_breed : ''}` : '';

                content += `
                    <tr class="hover:bg-gray-50 service-movement-row" 
                        data-service-type="${movement.service_type || ''}" 
                        data-status="${movement.status || ''}"
                        data-search="${movement.service_name.toLowerCase()} ${movement.pet_name.toLowerCase()} ${movement.owner_name.toLowerCase()}">
                        <td class="p-2 border">
                            <div class="text-sm font-medium">${formattedDate}</div>
                            <div class="text-xs text-gray-500">${formattedTime}</div>
                            ${completedDate ? `<div class="text-xs text-green-600">Done: ${completedDate}</div>` : ''}
                        </td>
                        <td class="p-2 border">
                            <div class="font-medium">${movement.service_name}</div>
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium ${movement.service_type_class}">
                                ${movement.service_type}
                            </span>
                        </td>
                        <td class="p-2 border">
                            <div class="font-medium flex items-center">
                                <i class="fas fa-paw text-amber-500 mr-1"></i>
                                ${movement.pet_name}
                            </div>
                            ${petInfo ? `<div class="text-xs text-gray-500">${petInfo}</div>` : ''}
                            <div class="text-xs text-gray-600 flex items-center mt-1">
                                <i class="fas fa-user text-gray-400 mr-1"></i>
                                ${movement.owner_name}
                            </div>
                        </td>
                        <td class="p-2 border text-xs">
                            <span class="bg-amber-100 text-amber-800 px-2 py-0.5 rounded">Visit #${movement.visit_id}</span>
                            <div class="mt-1">
                                <span class="px-2 py-0.5 rounded text-xs ${patientTypeClass}">${movement.patient_type}</span>
                            </div>
                            <div class="text-gray-500 mt-1">${movement.visit_type || 'Walk-in'}</div>
                        </td>
                        <td class="p-2 border text-center">
                            <span class="font-bold text-emerald-600">₱${parseFloat(movement.total_price).toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
                            ${movement.quantity > 1 ? `<div class="text-xs text-gray-500">${movement.quantity} × ₱${parseFloat(movement.unit_price).toLocaleString()}</div>` : ''}
                        </td>
                        <td class="p-2 border">
                            <div class="flex items-center">
                                <i class="fas fa-user-md text-blue-500 mr-1"></i>
                                <span class="text-sm">${movement.performed_by}</span>
                            </div>
                        </td>
                        <td class="p-2 border text-center">
                            <span class="px-2 py-1 rounded-full text-xs font-medium ${statusClass}">
                                ${movement.status_label}
                            </span>
                        </td>
                        <td class="p-2 border text-xs text-gray-600 max-w-[150px] truncate" title="${movement.notes || 'No notes'}">
                            ${movement.notes || '-'}
                        </td>
                    </tr>`;
            });

            content += '</tbody></table>';
            document.getElementById('serviceInventoryContent').innerHTML = content;
        }

        function filterServiceMovementHistory() {
            const searchTerm = document.getElementById('serviceMovementSearchInput').value.toLowerCase();
            const typeFilter = document.getElementById('serviceTypeFilter').value;
            const statusFilter = document.getElementById('serviceStatusFilter').value;

            const filteredMovements = allServiceMovements.filter(movement => {
                const matchesSearch = !searchTerm || 
                    movement.service_name.toLowerCase().includes(searchTerm) || 
                    movement.pet_name.toLowerCase().includes(searchTerm) ||
                    movement.owner_name.toLowerCase().includes(searchTerm);
                const matchesType = !typeFilter || movement.service_type === typeFilter;
                const matchesStatus = !statusFilter || movement.status === statusFilter;
                return matchesSearch && matchesType && matchesStatus;
            });

            renderServiceMovementHistory(filteredMovements);
        }

        function closeServiceInventoryModal() {
            document.getElementById('serviceInventoryModal').classList.add('hidden');
        }

        // EQUIPMENT INVENTORY OVERVIEW - Equipment Assignment History
        let allEquipmentMovements = [];
        
        function openEquipmentInventoryOverview() {
            document.getElementById('equipmentInventoryModal').classList.remove('hidden');
            loadEquipmentMovementHistory();
        }
        
        function loadEquipmentMovementHistory() {
            document.getElementById('equipmentInventoryContent').innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-3xl text-purple-600"></i><p class="mt-2">Loading equipment assignment history...</p></div>';

            fetch('/equipment/assignment-history')
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        document.getElementById('equipmentInventoryContent').innerHTML = `<div class="text-red-500 text-center py-8"><i class="fas fa-exclamation-circle text-3xl mb-2"></i><p>Error loading data: ${data.error || 'Unknown error'}</p></div>`;
                        return;
                    }

                    allEquipmentMovements = data.movements;
                    const summary = data.summary;

                    // Update summary cards
                    document.getElementById('equipmentSummaryTotal').textContent = summary.total_assignments;
                    document.getElementById('equipmentSummaryCheckedIn').textContent = summary.checked_in;
                    document.getElementById('equipmentSummaryCheckedOut').textContent = summary.checked_out;
                    document.getElementById('equipmentSummaryActive').textContent = summary.active_assignments;
                    document.getElementById('equipmentSummaryUnique').textContent = summary.unique_equipment;
                    document.getElementById('equipmentSummaryPets').textContent = summary.unique_pets;

                    renderEquipmentMovementHistory(allEquipmentMovements);
                })
                .catch(error => {
                    document.getElementById('equipmentInventoryContent').innerHTML = `<div class="text-red-500 text-center py-8"><i class="fas fa-exclamation-circle text-3xl mb-2"></i><p>Error: ${error.message}</p></div>`;
                });
        }
        
        function renderEquipmentMovementHistory(movements) {
            if (movements.length === 0) {
                document.getElementById('equipmentInventoryContent').innerHTML = `
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-box-open text-4xl mb-2"></i>
                        <p>No equipment assignment records found</p>
                        <p class="text-sm mt-2">Equipment assignments will appear here when boarding check-ins occur.</p>
                    </div>`;
                return;
            }

            let content = `
                <table class="w-full table-auto text-sm border">
                    <thead class="bg-gray-100 sticky top-0">
                        <tr>
                            <th class="p-2 border text-left">Date & Time</th>
                            <th class="p-2 border text-left">Equipment</th>
                            <th class="p-2 border text-center">Action</th>
                            <th class="p-2 border text-left">Pet / Owner</th>
                            <th class="p-2 border text-left">Service</th>
                            <th class="p-2 border text-left">Check-in / Check-out</th>
                            <th class="p-2 border text-left">Handled By</th>
                            <th class="p-2 border text-left">Notes</th>
                        </tr>
                    </thead>
                    <tbody>`;

            movements.forEach(movement => {
                const date = new Date(movement.date);
                const formattedDate = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                const formattedTime = date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                
                const checkInDate = movement.check_in_date ? new Date(movement.check_in_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '-';
                const checkOutDate = movement.check_out_date ? new Date(movement.check_out_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '-';

                const actionColors = {
                    'green': 'bg-green-100 text-green-800',
                    'blue': 'bg-blue-100 text-blue-800',
                    'amber': 'bg-amber-100 text-amber-800',
                    'yellow': 'bg-yellow-100 text-yellow-800',
                    'red': 'bg-red-100 text-red-800',
                    'purple': 'bg-purple-100 text-purple-800',
                    'teal': 'bg-teal-100 text-teal-800',
                };
                const actionClass = actionColors[movement.action_color] || 'bg-gray-100 text-gray-800';

                const boardingStatusColors = {
                    'Checked In': 'bg-green-100 text-green-800',
                    'Checked Out': 'bg-blue-100 text-blue-800',
                    'Reserved': 'bg-amber-100 text-amber-800',
                    'Cancelled': 'bg-red-100 text-red-800',
                };
                const boardingStatusClass = boardingStatusColors[movement.boarding_status] || 'bg-gray-100 text-gray-800';

                const petInfo = movement.pet_species ? `${movement.pet_species}${movement.pet_breed ? ' - ' + movement.pet_breed : ''}` : '';

                content += `
                    <tr class="hover:bg-gray-50 equipment-movement-row" 
                        data-category="${movement.equipment_category || ''}" 
                        data-action="${movement.action_type || ''}"
                        data-search="${movement.equipment_name.toLowerCase()} ${movement.pet_name.toLowerCase()} ${movement.owner_name.toLowerCase()}">
                        <td class="p-2 border">
                            <div class="text-sm font-medium">${formattedDate}</div>
                            <div class="text-xs text-gray-500">${formattedTime}</div>
                        </td>
                        <td class="p-2 border">
                            <div class="font-medium flex items-center">
                                <i class="fas fa-box text-purple-500 mr-1"></i>
                                ${movement.equipment_name}
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium ${movement.category_class}">
                                ${movement.equipment_category}
                            </span>
                        </td>
                        <td class="p-2 border text-center">
                            <span class="px-2 py-1 rounded-full text-xs font-medium ${actionClass}">
                                ${movement.action_label}
                            </span>
                            ${movement.boarding_status ? `<div class="mt-1"><span class="px-2 py-0.5 rounded text-xs ${boardingStatusClass}">${movement.boarding_status}</span></div>` : ''}
                        </td>
                        <td class="p-2 border">
                            <div class="font-medium flex items-center">
                                <i class="fas fa-paw text-amber-500 mr-1"></i>
                                ${movement.pet_name}
                            </div>
                            ${petInfo ? `<div class="text-xs text-gray-500">${petInfo}</div>` : ''}
                            <div class="text-xs text-gray-600 flex items-center mt-1">
                                <i class="fas fa-user text-gray-400 mr-1"></i>
                                ${movement.owner_name}
                            </div>
                        </td>
                        <td class="p-2 border">
                            ${movement.service_name ? `
                                <div class="font-medium">${movement.service_name}</div>
                                <div class="text-xs text-gray-500">${movement.service_type || ''}</div>
                            ` : `<span class="text-gray-400">-</span>`}
                            ${movement.visit_id ? `<div class="text-xs mt-1"><span class="bg-amber-100 text-amber-800 px-2 py-0.5 rounded">Visit #${movement.visit_id}</span></div>` : ''}
                        </td>
                        <td class="p-2 border text-xs">
                            <div class="flex flex-col gap-1">
                                <div class="flex items-center">
                                    <i class="fas fa-sign-in-alt text-green-500 mr-1"></i>
                                    <span class="text-green-700">${checkInDate}</span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-sign-out-alt text-blue-500 mr-1"></i>
                                    <span class="text-blue-700">${checkOutDate}</span>
                                </div>
                            </div>
                        </td>
                        <td class="p-2 border">
                            <div class="flex items-center">
                                <i class="fas fa-user-md text-blue-500 mr-1"></i>
                                <span class="text-sm">${movement.handled_by}</span>
                            </div>
                        </td>
                        <td class="p-2 border text-xs text-gray-600 max-w-[150px] truncate" title="${movement.notes || movement.reference || 'No notes'}">
                            ${movement.notes || movement.reference || '-'}
                        </td>
                    </tr>`;
            });

            content += '</tbody></table>';
            document.getElementById('equipmentInventoryContent').innerHTML = content;
        }

        function filterEquipmentMovementHistory() {
            const searchTerm = document.getElementById('equipmentMovementSearchInput').value.toLowerCase();
            const categoryFilter = document.getElementById('equipmentCategoryFilter').value;
            const actionFilter = document.getElementById('equipmentActionFilter').value;

            const filteredMovements = allEquipmentMovements.filter(movement => {
                const matchesSearch = !searchTerm || 
                    movement.equipment_name.toLowerCase().includes(searchTerm) || 
                    movement.pet_name.toLowerCase().includes(searchTerm) ||
                    movement.owner_name.toLowerCase().includes(searchTerm);
                const matchesCategory = !categoryFilter || movement.equipment_category === categoryFilter;
                const matchesAction = !actionFilter || movement.action_type === actionFilter;
                return matchesSearch && matchesCategory && matchesAction;
            });

            renderEquipmentMovementHistory(filteredMovements);
        }

        function closeEquipmentInventoryModal() {
            document.getElementById('equipmentInventoryModal').classList.add('hidden');
        }

        // INVENTORY OVERVIEW MODAL - Complete Stock Movement History
        let allMovements = [];
        
        function openInventoryOverview() {
            document.getElementById('inventoryModal').classList.remove('hidden');
            loadStockMovementHistory();
        }
        
        function loadStockMovementHistory() {
            document.getElementById('inventoryContent').innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-3xl text-blue-600"></i><p class="mt-2">Loading stock movement history...</p></div>';
            
            fetch('/products/stock-movement-history')
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        document.getElementById('inventoryContent').innerHTML = `<div class="text-red-500 text-center py-8"><i class="fas fa-exclamation-circle text-3xl mb-2"></i><p>Error loading data: ${data.error || 'Unknown error'}</p></div>`;
                        return;
                    }
                    
                    allMovements = data.movements;
                    const summary = data.summary;
                    
                    // Update summary cards - whole numbers without +/- signs
                    document.getElementById('summaryTotal').textContent = summary.total_movements;
                    document.getElementById('summaryAdded').textContent = Math.round(summary.stock_added);
                    document.getElementById('summarySales').textContent = Math.round(summary.sales);
                    document.getElementById('summaryService').textContent = Math.round(summary.service_usage);
                    document.getElementById('summaryDamaged').textContent = Math.round(summary.damaged);
                    document.getElementById('summaryPullout').textContent = Math.round(summary.pullout);
                    
                    renderMovementHistory(allMovements);
                })
                .catch(error => {
                    document.getElementById('inventoryContent').innerHTML = `<div class="text-red-500 text-center py-8"><i class="fas fa-exclamation-circle text-3xl mb-2"></i><p>Error: ${error.message}</p></div>`;
                });
        }
        
        function renderMovementHistory(movements) {
            if (movements.length === 0) {
                document.getElementById('inventoryContent').innerHTML = `
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>No stock movements found</p>
                    </div>`;
                return;
            }
            
            let content = `
                <table class="w-full table-auto text-sm border">
                    <thead class="bg-gray-100 sticky top-0">
                        <tr>
                            <th class="p-2 border text-left">Date & Time</th>
                            <th class="p-2 border text-left">Product</th>
                            <th class="p-2 border text-left">Type</th>
                            <th class="p-2 border text-center">Quantity</th>
                            <th class="p-2 border text-left">Reference</th>
                            <th class="p-2 border text-left">Details</th>
                            <th class="p-2 border text-left">User</th>
                        </tr>
                    </thead>
                    <tbody>`;
            
            movements.forEach(movement => {
                const date = new Date(movement.date);
                const formattedDate = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                const formattedTime = date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                
                const colorClasses = {
                    'green': 'bg-green-100 text-green-800',
                    'blue': 'bg-blue-100 text-blue-800',
                    'purple': 'bg-purple-100 text-purple-800',
                    'red': 'bg-red-100 text-red-800',
                    'orange': 'bg-orange-100 text-orange-800',
                    'gray': 'bg-gray-100 text-gray-800',
                    'teal': 'bg-teal-100 text-teal-800',
                };
                
                const typeClass = colorClasses[movement.type_color] || colorClasses['gray'];
                
                // Display whole number without +/- signs, use color to indicate addition (green) or deduction (red)
                const isAddition = movement.quantity_change >= 0;
                const quantityClass = isAddition ? 'text-green-600 font-bold' : 'text-red-600 font-bold';
                const quantityDisplay = Math.round(Math.abs(movement.quantity_change)); // Whole number, no decimal, no sign
                
                content += `
                    <tr class="hover:bg-gray-50 movement-row" data-type="${movement.transaction_type}" data-product="${movement.product_name.toLowerCase()}">
                        <td class="p-2 border">
                            <div class="text-sm font-medium">${formattedDate}</div>
                            <div class="text-xs text-gray-500">${formattedTime}</div>
                        </td>
                        <td class="p-2 border">
                            <div class="font-medium">${movement.product_name}</div>
                            <div class="text-xs text-gray-500">${movement.product_category || 'N/A'}</div>
                        </td>
                        <td class="p-2 border">
                            <span class="px-2 py-1 rounded-full text-xs font-medium ${typeClass}">
                                <i class="fas ${movement.type_icon} mr-1"></i>${movement.type_label}
                            </span>
                        </td>
                        <td class="p-2 border text-center">
                            <span class="${quantityClass}">${quantityDisplay}</span>
                        </td>
                        <td class="p-2 border text-xs">${movement.reference}</td>
                        <td class="p-2 border text-xs">${movement.details}</td>
                        <td class="p-2 border text-xs">${movement.user}</td>
                    </tr>`;
            });
            
            content += '</tbody></table>';
            document.getElementById('inventoryContent').innerHTML = content;
        }
        
        function filterMovementHistory() {
            const searchTerm = document.getElementById('movementSearchInput').value.toLowerCase();
            const typeFilter = document.getElementById('movementTypeFilter').value;
            
            const filteredMovements = allMovements.filter(movement => {
                const matchesSearch = !searchTerm || movement.product_name.toLowerCase().includes(searchTerm);
                const matchesType = !typeFilter || movement.transaction_type === typeFilter;
                return matchesSearch && matchesType;
            });
            
            renderMovementHistory(filteredMovements);
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
                    const stockBatches = data.stock_batches || [];
                    const damagePulloutHistory = data.damage_pullout_history || [];
                    const serviceConsumptionData = data.service_consumption_data;
                    const servicesUsingProduct = data.services_using_product || [];
                    const recentServiceUsage = data.recent_service_usage || [];
                    const isConsumable = product.prod_type === 'Consumable';

                    let content = `
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-bold text-lg mb-3">Product Information</h4>
                            <div class="space-y-2">
                                ${product.prod_image ? `<div class="mb-3"><img src="/storage/${product.prod_image}" class="h-32 w-32 object-cover rounded mx-auto"></div>` : ''}
                                <div><span class="font-medium">Name:</span> ${product.prod_name}</div>
                                <div><span class="font-medium">Category:</span> ${product.prod_category || 'N/A'}</div>
                                <div><span class="font-medium">Type:</span> <span class="px-2 py-1 text-xs rounded-full ${product.prod_type === 'Sale' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'}">${product.prod_type}</span></div>
                                <div><span class="font-medium">Description:</span> ${product.prod_description || 'N/A'}</div>
                                ${product.prod_type === 'Sale' ? `<div><span class="font-medium">Price:</span> ₱${parseFloat(product.prod_price).toLocaleString(undefined, { minimumFractionDigits: 2 })}</div>` : '<div><span class="font-medium">Price:</span> <span class="text-gray-400 text-sm">N/A (Consumable)</span></div>'}
                                <div><span class="font-medium">Branch:</span> ${product.branch ? product.branch.branch_name : 'N/A'}</div>
                            </div>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-bold text-lg mb-3">Inventory Status</h4>
                            <div class="space-y-2">
                                <div><span class="font-medium">Current Stock:</span> <span class="text-lg font-bold ${product.prod_stocks <= (product.prod_reorderlevel || 10) ? 'text-red-600' : 'text-green-600'}">${product.prod_stocks || 0}</span></div>
                                <div><span class="font-medium">Reorder Level:</span> ${product.prod_reorderlevel || 'Not set'}</div>
                                <div><span class="font-medium">Total Damaged:</span> <span class="text-red-600">${product.prod_damaged || 0}</span></div>
                                <div><span class="font-medium">Total Pull-out:</span> <span class="text-orange-600">${product.prod_pullout || 0}</span></div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <h4 class="font-bold text-lg mb-3">Stock Batches</h4>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm border">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="p-2 border">Batch</th>
                                        <th class="p-2 border">Initial Qty</th>
                                        <th class="p-2 border">Service Usage</th>
                                        <th class="p-2 border">Available</th>
                                        <th class="p-2 border">Damaged</th>
                                        <th class="p-2 border">Pullout</th>
                                        <th class="p-2 border">Expiry Date</th>
                                        <th class="p-2 border">Added By</th>
                                        <th class="p-2 border">Date Added</th>
                                    </tr>
                                </thead>
                                <tbody>`;

                    if (stockBatches.length > 0) {
                        stockBatches.forEach(batch => {
                            const expiryDate = batch.expire_date ? new Date(batch.expire_date) : null;
                            const isExpired = batch.is_expired;
                            const addedDate = batch.created_at ? new Date(batch.created_at) : null;
                            const serviceUsage = batch.service_usage || 0;
                            
                            content += `
                            <tr class="${isExpired ? 'bg-red-50' : ''}">
                                <td class="p-2 border font-medium">${batch.batch}</td>
                                <td class="p-2 border text-center">${batch.quantity}</td>
                                <td class="p-2 border text-center">
                                    <span class="font-bold ${serviceUsage > 0 ? 'text-purple-600' : 'text-gray-400'}">${serviceUsage}</span>
                                </td>
                                <td class="p-2 border text-center">
                                    <span class="font-bold ${batch.available_quantity > 0 ? 'text-green-600' : 'text-gray-400'}">${batch.available_quantity}</span>
                                </td>
                                <td class="p-2 border text-center text-red-600">${batch.total_damage}</td>
                                <td class="p-2 border text-center text-orange-600">${batch.total_pullout}</td>
                                <td class="p-2 border ${isExpired ? 'text-red-600 font-bold' : ''}">
                                    ${expiryDate ? expiryDate.toLocaleDateString() : 'N/A'}
                                    ${isExpired ? '<br><span class="text-xs">(EXPIRED)</span>' : ''}
                                </td>
                                <td class="p-2 border">${batch.created_by_name || 'System'}</td>
                                <td class="p-2 border">${addedDate ? addedDate.toLocaleDateString() : 'N/A'}</td>
                            </tr>`;
                        });
                    } else {
                        content += '<tr><td colspan="9" class="p-4 text-center text-gray-500">No stock batches found</td></tr>';
                    }

                    content += `
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-6">
                        <h4 class="font-bold text-lg mb-3">Damage & Pullout History</h4>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm border">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="p-2 border">Date</th>
                                        <th class="p-2 border">Batch</th>
                                        <th class="p-2 border">Damaged</th>
                                        <th class="p-2 border">Pullout</th>
                                        <th class="p-2 border">Reason</th>
                                        <th class="p-2 border">Recorded By</th>
                                    </tr>
                                </thead>
                                <tbody>`;

                    if (damagePulloutHistory.length > 0) {
                        damagePulloutHistory.forEach(record => {
                            const recordDate = record.created_at ? new Date(record.created_at) : null;
                            
                            content += `
                            <tr>
                                <td class="p-2 border">${recordDate ? recordDate.toLocaleDateString() : 'N/A'}</td>
                                <td class="p-2 border font-medium">${record.batch}</td>
                                <td class="p-2 border text-center text-red-600">${record.damage_quantity || 0}</td>
                                <td class="p-2 border text-center text-orange-600">${record.pullout_quantity || 0}</td>
                                <td class="p-2 border">${record.reason || 'N/A'}</td>
                                <td class="p-2 border">${record.created_by_name || 'System'}</td>
                            </tr>`;
                        });
                    } else {
                        content += '<tr><td colspan="6" class="p-4 text-center text-gray-500">No damage or pullout records found</td></tr>';
                    }

                    content += `
                                </tbody>
                            </table>
                        </div>
                    </div>`;

                    // Show different analytics based on product type
                    if (isConsumable && serviceConsumptionData) {
                        // CONSUMABLE PRODUCT ANALYTICS
                        content += `
                        <div class="mt-6">
                            <h4 class="font-bold text-lg mb-3 flex items-center">
                                <i class="fas fa-chart-line text-blue-600 mr-2"></i>
                                Service Consumption Analytics
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                                <div class="bg-blue-50 p-4 rounded-lg">
                                    <div class="text-sm text-blue-600">Services Using</div>
                                    <div class="text-2xl font-bold text-blue-800">${serviceConsumptionData.total_services_using || 0}</div>
                                    <div class="text-xs text-blue-500 mt-1">Active services</div>
                                </div>
                                <div class="bg-green-50 p-4 rounded-lg">
                                    <div class="text-sm text-green-600">Total Consumed</div>
                                    <div class="text-2xl font-bold text-green-800">${parseFloat(serviceConsumptionData.total_quantity_consumed || 0).toLocaleString()}</div>
                                    <div class="text-xs text-green-500 mt-1">All time usage</div>
                                </div>
                                <div class="bg-purple-50 p-4 rounded-lg">
                                    <div class="text-sm text-purple-600">Last 30 Days</div>
                                    <div class="text-2xl font-bold text-purple-800">${parseFloat(serviceConsumptionData.recent_consumption_30days || 0).toLocaleString()}</div>
                                    <div class="text-xs text-purple-500 mt-1">Recent consumption</div>
                                </div>
                                <div class="bg-yellow-50 p-4 rounded-lg">
                                    <div class="text-sm text-yellow-600">Avg. Per Service</div>
                                    <div class="text-2xl font-bold text-yellow-800">${parseFloat(serviceConsumptionData.avg_consumption_per_service || 0).toFixed(1)}</div>
                                    <div class="text-xs text-yellow-500 mt-1">Average usage</div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6">
                            <h4 class="font-bold text-lg mb-3 flex items-center">
                                <i class="fas fa-stethoscope text-purple-600 mr-2"></i>
                                Services Using This Product
                            </h4>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm border">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="p-2 border">Service Name</th>
                                            <th class="p-2 border">Service Type</th>
                                            <th class="p-2 border">Qty Used</th>
                                            <th class="p-2 border">Billable</th>
                                            <th class="p-2 border">Added By</th>
                                            <th class="p-2 border">Date Added</th>
                                        </tr>
                                    </thead>
                                    <tbody>`;
                        
                        if (servicesUsingProduct.length > 0) {
                            servicesUsingProduct.forEach(service => {
                                const addedDate = service.created_at ? new Date(service.created_at).toLocaleDateString() : 'N/A';
                                content += `
                                <tr>
                                    <td class="p-2 border font-medium">${service.serv_name}</td>
                                    <td class="p-2 border">
                                        <span class="px-2 py-1 text-xs rounded ${service.serv_type === 'Grooming' ? 'bg-pink-100 text-pink-700' : 'bg-blue-100 text-blue-700'}">
                                            ${service.serv_type}
                                        </span>
                                    </td>
                                    <td class="p-2 border text-center font-bold">${service.quantity_used}</td>
                                    <td class="p-2 border text-center">
                                        ${service.is_billable ? '<span class="text-green-600"><i class="fas fa-check-circle"></i> Yes</span>' : '<span class="text-gray-400"><i class="fas fa-times-circle"></i> No</span>'}
                                    </td>
                                    <td class="p-2 border">${service.added_by || 'System'}</td>
                                    <td class="p-2 border">${addedDate}</td>
                                </tr>`;
                            });
                        } else {
                            content += '<tr><td colspan="6" class="p-4 text-center text-gray-500">Not currently used in any services</td></tr>';
                        }

                        content += `
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="mt-6">
                            <h4 class="font-bold text-lg mb-3 flex items-center">
                                <i class="fas fa-history text-green-600 mr-2"></i>
                                Recent Service Usage
                            </h4>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm border">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="p-2 border">Date</th>
                                            <th class="p-2 border">Quantity Used</th>
                                            <th class="p-2 border">Visit / Service</th>
                                            <th class="p-2 border">Pet</th>
                                            <th class="p-2 border">Performed By</th>
                                            <th class="p-2 border">Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>`;
                        
                        if (recentServiceUsage.length > 0) {
                            recentServiceUsage.forEach(usage => {
                                const usageDate = usage.transaction_date ? new Date(usage.transaction_date).toLocaleDateString() : 'N/A';
                                const visitInfo = usage.visit_id ? `Visit #${usage.visit_id}` : 'N/A';
                                const serviceName = usage.serv_name || usage.reference || 'Service';
                                content += `
                                <tr>
                                    <td class="p-2 border">${usageDate}</td>
                                    <td class="p-2 border text-center font-bold text-red-600">${Math.abs(usage.quantity)}</td>
                                    <td class="p-2 border">
                                        <div class="font-medium">${visitInfo}</div>
                                        <div class="text-xs text-gray-500">${serviceName}</div>
                                    </td>
                                    <td class="p-2 border">${usage.pet_name || 'N/A'}</td>
                                    <td class="p-2 border">${usage.performed_by || 'System'}</td>
                                    <td class="p-2 border text-xs">${usage.notes || '-'}</td>
                                </tr>`;
                            });
                        } else {
                            content += '<tr><td colspan="6" class="p-4 text-center text-gray-500">No recent service usage recorded</td></tr>';
                        }

                        content += `
                                    </tbody>
                                </table>
                            </div>
                        </div>`;
                    } else {
                        // SALE PRODUCT ANALYTICS
                        content += `
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
                    }

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
                    const stockBatches = historyData.stock_batches || [];
                    const damageAnalysis = historyData.damage_analysis;
                    const expiryData = historyData.expiry_data;
                    const stockAnalytics = historyData.stock_analytics;

                    const servicesUsing = serviceData.services_using_product;
                    const recentServiceUsage = serviceData.recent_service_usage;
                    const totalUsedInServices = serviceData.total_used_in_services;

                    let content = `
                <div class="mb-6">
                    <h4 class="font-bold text-lg mb-3">Product: ${product.prod_name}</h4>

                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
                        <div class="bg-blue-50 p-3 rounded-lg">
                            <div class="text-xs md:text-sm text-blue-600">Current Stock</div>
                            <div class="text-xl md:text-2xl font-bold text-blue-800">${stockAnalytics.current_stock}</div>
                        </div>
                        <div class="bg-yellow-50 p-3 rounded-lg">
                            <div class="text-xs md:text-sm text-yellow-600">Reorder Level</div>
                            <div class="text-xl md:text-2xl font-bold text-yellow-800">${stockAnalytics.reorder_level || 'N/A'}</div>
                        </div>
                        <div class="bg-red-50 p-3 rounded-lg">
                            <div class="text-xs md:text-sm text-red-600">Damaged Items</div>
                            <div class="text-xl md:text-2xl font-bold text-red-800">${damageAnalysis.total_damaged}</div>
                        </div>
                        <div class="bg-orange-50 p-3 rounded-lg">
                            <div class="text-xs md:text-sm text-orange-600">Pullout Items</div>
                            <div class="text-xl md:text-2xl font-bold text-orange-800">${damageAnalysis.total_pullout || 0}</div>
                        </div>
                        <div class="bg-purple-50 p-3 rounded-lg">
                            <div class="text-xs md:text-sm text-purple-600">Used in Services</div>
                            <div class="text-xl md:text-2xl font-bold text-purple-800">${totalUsedInServices}</div>
                        </div>
                        <div class="bg-green-50 p-3 rounded-lg">
                            <div class="text-xs md:text-sm text-green-600">Days Until Reorder</div>
                            <div class="text-xl md:text-2xl font-bold text-green-800">${stockAnalytics.days_until_reorder}</div>
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

                    <h5 class="font-bold mb-3">Stock Batches with Expiry Information</h5>
                    <div class="overflow-x-auto mb-6">
                        <table class="w-full text-sm border">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="p-2 border">Batch</th>
                                    <th class="p-2 border">Initial Qty</th>
                                    <th class="p-2 border">Service Usage</th>
                                    <th class="p-2 border">Available</th>
                                    <th class="p-2 border">Damaged</th>
                                    <th class="p-2 border">Pullout</th>
                                    <th class="p-2 border">Expiry Date</th>
                                    <th class="p-2 border">Status</th>
                                    <th class="p-2 border">Added By</th>
                                    <th class="p-2 border">Date Added</th>
                                </tr>
                            </thead>
                            <tbody>`;

                    if (stockBatches.length > 0) {
                        stockBatches.forEach(batch => {
                            const expiryDate = batch.expire_date ? new Date(batch.expire_date) : null;
                            const isExpired = batch.is_expired;
                            const addedDate = batch.created_at ? new Date(batch.created_at) : null;
                            const daysUntilExpiry = expiryDate ? Math.ceil((expiryDate - new Date()) / (1000 * 60 * 60 * 24)) : null;
                            const serviceUsage = batch.service_usage || 0;
                            
                            let statusText = 'Good';
                            let statusClass = 'text-green-600';
                            if (isExpired) {
                                statusText = 'Expired';
                                statusClass = 'text-red-600 font-bold';
                            } else if (daysUntilExpiry !== null && daysUntilExpiry <= 30) {
                                statusText = `Expiring (${daysUntilExpiry}d)`;
                                statusClass = 'text-yellow-600';
                            }
                            
                            content += `
                            <tr class="${isExpired ? 'bg-red-50' : ''}">
                                <td class="p-2 border font-medium">${batch.batch}</td>
                                <td class="p-2 border text-center">${batch.quantity}</td>
                                <td class="p-2 border text-center">
                                    <span class="font-bold ${serviceUsage > 0 ? 'text-purple-600' : 'text-gray-400'}">${serviceUsage}</span>
                                </td>
                                <td class="p-2 border text-center">
                                    <span class="font-bold ${batch.available_quantity > 0 ? 'text-green-600' : 'text-gray-400'}">${batch.available_quantity}</span>
                                </td>
                                <td class="p-2 border text-center text-red-600">${batch.total_damage}</td>
                                <td class="p-2 border text-center text-orange-600">${batch.total_pullout}</td>
                                <td class="p-2 border ${isExpired ? 'text-red-600 font-bold' : ''}">
                                    ${expiryDate ? expiryDate.toLocaleDateString() : 'N/A'}
                                </td>
                                <td class="p-2 border ${statusClass}">${statusText}</td>
                                <td class="p-2 border">${batch.created_by_name || 'System'}</td>
                                <td class="p-2 border">${addedDate ? addedDate.toLocaleDateString() : 'N/A'}</td>
                            </tr>`;
                        });
                    } else {
                        content += '<tr><td colspan="10" class="p-4 text-center text-gray-500">No stock batches found</td></tr>';
                    }

                    content += `
                            </tbody>
                        </table>
                    </div>

                    <h5 class="font-bold mb-3">Complete Stock Movement History</h5>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm border">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="p-2 border">Date</th>
                                    <th class="p-2 border">Type</th>
                                    <th class="p-2 border">Quantity</th>
                                    <th class="p-2 border">Damaged</th>
                                    <th class="p-2 border">Pullout</th>
                                    <th class="p-2 border">Reference</th>
                                    <th class="p-2 border">User</th>
                                    <th class="p-2 border">Notes</th>
                                </tr>
                            </thead>
                            <tbody>`;

                    stockHistory.forEach(movement => {
                        const typeClass = movement.type === 'Restock' ? 'text-green-600' :
                            movement.type === 'Sale' ? 'text-blue-600' :
                                movement.type === 'Service_usage' ? 'text-purple-600' :
                                    movement.type === 'Damage' ? 'text-red-600' :
                                        movement.type === 'Pullout' ? 'text-orange-600' : 'text-gray-600';

                        const movementDate = movement.date ? new Date(movement.date) : new Date();
                        
                        // Parse quantity, damage, and pullout values - convert to whole numbers
                        const rawQuantity = typeof movement.quantity === 'number' ? movement.quantity : 
                                             (movement.quantity ? parseFloat(movement.quantity) : 0);
                        const displayQuantity = Math.round(Math.abs(rawQuantity)); // Whole number, no decimal, no sign
                        
                        // Separate display for damage and pullout
                        let damageQty = 0;
                        let pulloutQty = 0;
                        let regularQty = displayQuantity;
                        let isAddition = rawQuantity > 0; // Track if it's an addition or deduction
                        
                        if (movement.type === 'Damage') {
                            damageQty = displayQuantity;
                            regularQty = 0;
                        } else if (movement.type === 'Pullout') {
                            pulloutQty = displayQuantity;
                            regularQty = 0;
                        }

                        // Color based on addition (green) or deduction (red) - no +/- signs
                        const quantityClass = isAddition ? 'text-green-600' : 'text-red-600';

                        content += `
                    <tr>
                        <td class="p-2 border">${movementDate.toLocaleDateString()}</td>
                        <td class="p-2 border"><span class="${typeClass} capitalize font-semibold">${movement.type.replace(/_/g, ' ')}</span></td>
                        <td class="p-2 border ${quantityClass} font-bold text-center">${regularQty !== 0 ? regularQty : '-'}</td>
                        <td class="p-2 border text-red-600 font-semibold text-center">${damageQty > 0 ? damageQty : '-'}</td>
                        <td class="p-2 border text-orange-600 font-semibold text-center">${pulloutQty > 0 ? pulloutQty : '-'}</td>
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
        function openAddProductModal(context = 'product', serviceId = null) {
            document.getElementById('productModal').classList.remove('hidden');
            document.getElementById('productModalTitle').innerText = 'Add Product';

            // Determine default product type based on context
            const isServiceProduct = context === 'service_product_inline';
            const defaultType = isServiceProduct ? 'Consumable' : 'Sale';

            // Fetch manufacturers dynamically
            fetch('/manufacturers')
                .then(response => response.json())
                .then(data => {
                    let manufacturerOptions = '<option value="">Select Manufacturer</option>';
                    if (data.success && data.manufacturers) {
                        data.manufacturers.forEach(m => {
                            manufacturerOptions += `<option value="${m.manufacturer_id}">${escapeHtml(m.manufacturer_name)}</option>`;
                        });
                    }

                    // Determine initial disabled states based on default type
                    const productCategoryDisabled = defaultType === 'Consumable' ? 'disabled' : '';
                    const serviceCategoryDisabled = defaultType === 'Sale' ? 'disabled' : '';
                    const productCategoryClass = defaultType === 'Consumable' ? 'bg-gray-100 cursor-not-allowed' : '';
                    const serviceCategoryClass = defaultType === 'Sale' ? 'bg-gray-100 cursor-not-allowed' : '';

                    let fields = `
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Product Name *</label>
                        <input type="text" name="prod_name" placeholder="Enter product name" class="border p-2 w-full rounded" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Product Type *</label>
                        <select name="prod_type" id="addProductType" class="border p-2 w-full rounded ${isServiceProduct ? 'bg-gray-100' : ''}" required ${isServiceProduct ? 'readonly onclick="return false;"' : ''} onchange="toggleProductTypeFields('add')">
                            <option value="Sale" ${defaultType === 'Sale' ? 'selected' : ''}>Sale (Available for POS)</option>
                            <option value="Consumable" ${defaultType === 'Consumable' ? 'selected' : ''}>Consumable (Used in Services)</option>
                            <option value="Prescription" ${defaultType === 'Prescription' ? 'selected' : ''}>Prescription (For Prescriptions)</option>
                        </select>
                        <small class="text-gray-500">${isServiceProduct ? 'Service products are always Consumable type' : 'Sale = POS, Consumable = Services, Prescription = Prescriptions'}</small>
                    </div>
                    <div class="mb-4" id="addProductCategoryField">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Product Category</label>
                        <select name="prod_category" id="addProductCategory" class="border p-2 w-full rounded ${productCategoryClass}" ${productCategoryDisabled}>
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
                        <small class="text-gray-500" id="addProductCategoryNote">For Sale products only</small>
                    </div>
                    <div class="mb-4" id="addServiceCategoryField">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Service Category</label>
                        <select name="service_category" id="addServiceCategory" class="border p-2 w-full rounded ${serviceCategoryClass}" ${serviceCategoryDisabled}>
                            <option value="">Select Service Category</option>
                            <option value="Grooming">Grooming</option>
                            <option value="Boarding">Boarding</option>
                            <option value="Vaccination">Vaccination</option>
                            <option value="Surgical">Surgical</option>
                            <option value="Emergency">Emergency</option>
                            <option value="Check-up">Check-up</option>
                            <option value="Deworming">Deworming</option>
                            <option value="Diagnostics">Diagnostics</option>
                            <option value="Medical Supply">Medical Supply</option>
                        </select>
                        <small class="text-gray-500" id="addServiceCategoryNote">For Consumable products only</small>
                    </div>
                    <div class="mb-4" id="addManufacturerField">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Manufacturer</label>
                        <select name="manufacturer_id" id="addManufacturer" class="border p-2 w-full rounded ${defaultType === 'Sale' ? 'bg-gray-100 cursor-not-allowed' : ''}" ${defaultType === 'Sale' ? 'disabled' : ''}>
                            ${manufacturerOptions}
                        </select>
                        <small class="text-gray-500" id="addManufacturerNote">${defaultType === 'Sale' ? 'Manufacturer not applicable for Sale products' : 'Select manufacturer for this product'}</small>
                    </div>
                    <div class="mb-4" id="addProductPriceField">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Price <span id="addPriceRequired">*</span></label>
                        <input type="number" step="0.01" name="prod_price" id="addProductPrice" placeholder="Enter price" class="border p-2 w-full rounded">
                        <small class="text-gray-500 hidden" id="addPriceNote">Price not applicable for consumable products</small>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Reorder Level *</label>
                        <input type="number" name="prod_reorderlevel" placeholder="Enter reorder level" class="border p-2 w-full rounded" value="10" required min="0">
                        <small class="text-gray-500">Alert level for low stock (must be 0 or greater)</small>
                    </div>
                    <input type="hidden" name="branch_id" value="{{ $activeBranchId ?? '' }}">
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
                    
                    // Set initial field visibility based on product type
                    setTimeout(() => toggleProductTypeFields('add'), 0);
                })
                .catch(error => {
                    console.error('Error loading manufacturers:', error);
                });
        }

        function openEditProductModal(data) {
            document.getElementById('productModal').classList.remove('hidden');
            document.getElementById('productModalTitle').innerText = 'Edit Product';

            // Fetch manufacturers dynamically
            fetch('/manufacturers')
                .then(response => response.json())
                .then(mfgData => {
                    let manufacturerOptions = '<option value="">Select Manufacturer</option>';
                    if (mfgData.success && mfgData.manufacturers) {
                        mfgData.manufacturers.forEach(m => {
                            const selected = data.manufacturer_id == m.manufacturer_id ? 'selected' : '';
                            manufacturerOptions += `<option value="${m.manufacturer_id}" ${selected}>${escapeHtml(m.manufacturer_name)}</option>`;
                        });
                    }

                    // Determine disabled states based on current product type
                    const isSale = data.prod_type === 'Sale';
                    const productCategoryDisabled = !isSale ? 'disabled' : '';
                    const serviceCategoryDisabled = isSale ? 'disabled' : '';
                    const productCategoryClass = !isSale ? 'bg-gray-100 cursor-not-allowed' : '';
                    const serviceCategoryClass = isSale ? 'bg-gray-100 cursor-not-allowed' : '';

                    // Service categories list
                    const serviceCategories = ['Grooming', 'Boarding', 'Vaccination', 'Surgical', 'Emergency', 'Check-up', 'Deworming', 'Diagnostics', 'Medical Supply'];
                    
                    // Check if prod_category is a service category (for consumable products)
                    const isServiceCategory = serviceCategories.includes(data.prod_category);
                    const displayProductCategory = isSale ? data.prod_category : '';
                    const displayServiceCategory = !isSale ? data.prod_category : '';

                    let fields = `
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Product Name *</label>
                        <input type="text" name="prod_name" value="${escapeHtml(data.prod_name)}" class="border p-2 w-full rounded" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Product Type *</label>
                        <select name="prod_type" id="editProductType" class="border p-2 w-full rounded" required onchange="toggleProductTypeFields('edit')">
                            <option value="Sale" ${data.prod_type === 'Sale' ? 'selected' : ''}>Sale (Available for POS)</option>
                            <option value="Consumable" ${data.prod_type === 'Consumable' ? 'selected' : ''}>Consumable (Used in Services)</option>
                            <option value="Prescription" ${data.prod_type === 'Prescription' ? 'selected' : ''}>Prescription (For Prescriptions)</option>
                        </select>
                        <small class="text-gray-500">Sale = POS, Consumable = Services, Prescription = Prescriptions</small>
                    </div>
                    <div class="mb-4" id="editProductCategoryField">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Product Category</label>
                        <select name="prod_category" id="editProductCategory" class="border p-2 w-full rounded ${productCategoryClass}" ${productCategoryDisabled}>
                            <option value="">Select Product Category</option>
                            <option value="Prescription Medicines" ${displayProductCategory === 'Prescription Medicines' ? 'selected' : ''}>Prescription Medicines</option>
                            <option value="Vaccines" ${displayProductCategory === 'Vaccines' ? 'selected' : ''}>Vaccines</option>
                            <option value="Nutritional Products" ${displayProductCategory === 'Nutritional Products' ? 'selected' : ''}>Nutritional Products</option>
                            <option value="Parasite Control" ${displayProductCategory === 'Parasite Control' ? 'selected' : ''}>Parasite Control</option>
                            <option value="Grooming Products" ${displayProductCategory === 'Grooming Products' ? 'selected' : ''}>Grooming Products</option>
                            <option value="Pet Accessories" ${displayProductCategory === 'Pet Accessories' ? 'selected' : ''}>Pet Accessories</option>
                            <option value="Medical Supplies" ${displayProductCategory === 'Medical Supplies' ? 'selected' : ''}>Medical Supplies</option>
                            <option value="Pet Food & Treats" ${displayProductCategory === 'Pet Food & Treats' ? 'selected' : ''}>Pet Food & Treats</option>
                            <option value="Hygiene & Sanitation" ${displayProductCategory === 'Hygiene & Sanitation' ? 'selected' : ''}>Hygiene & Sanitation</option>
                            <option value="Pet Care Equipment" ${displayProductCategory === 'Pet Care Equipment' ? 'selected' : ''}>Pet Care Equipment</option>
                        </select>
                        <small class="text-gray-500" id="editProductCategoryNote">For Sale products only</small>
                    </div>
                    <div class="mb-4" id="editServiceCategoryField">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Service Category</label>
                        <select name="service_category" id="editServiceCategory" class="border p-2 w-full rounded ${serviceCategoryClass}" ${serviceCategoryDisabled}>
                            <option value="">Select Service Category</option>
                            <option value="Grooming" ${displayServiceCategory === 'Grooming' ? 'selected' : ''}>Grooming</option>
                            <option value="Boarding" ${displayServiceCategory === 'Boarding' ? 'selected' : ''}>Boarding</option>
                            <option value="Vaccination" ${displayServiceCategory === 'Vaccination' ? 'selected' : ''}>Vaccination</option>
                            <option value="Surgical" ${displayServiceCategory === 'Surgical' ? 'selected' : ''}>Surgical</option>
                            <option value="Emergency" ${displayServiceCategory === 'Emergency' ? 'selected' : ''}>Emergency</option>
                            <option value="Check-up" ${displayServiceCategory === 'Check-up' ? 'selected' : ''}>Check-up</option>
                            <option value="Deworming" ${displayServiceCategory === 'Deworming' ? 'selected' : ''}>Deworming</option>
                            <option value="Diagnostics" ${displayServiceCategory === 'Diagnostics' ? 'selected' : ''}>Diagnostics</option>
                            <option value="Medical Supply" ${displayServiceCategory === 'Medical Supply' ? 'selected' : ''}>Medical Supply</option>
                        </select>
                        <small class="text-gray-500" id="editServiceCategoryNote">For Consumable products only</small>
                    </div>
                    <div class="mb-4" id="editManufacturerField">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Manufacturer</label>
                        <select name="manufacturer_id" id="editManufacturer" class="border p-2 w-full rounded ${data.prod_type === 'Sale' ? 'bg-gray-100 cursor-not-allowed' : ''}" ${data.prod_type === 'Sale' ? 'disabled' : ''}>
                            ${manufacturerOptions}
                        </select>
                        <small class="text-gray-500" id="editManufacturerNote">${data.prod_type === 'Sale' ? 'Manufacturer not applicable for Sale products' : 'Select manufacturer for this product'}</small>
                    </div>
                    <div class="mb-4" id="editProductPriceField">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Price <span id="editPriceRequired">*</span></label>
                        <input type="number" step="0.01" name="prod_price" id="editProductPrice" value="${data.prod_price}" class="border p-2 w-full rounded">
                        <small class="text-gray-500 hidden" id="editPriceNote">Price not applicable for consumable products</small>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Current Available Stock</label>
                        <input type="number" value="${data.prod_stocks || 0}" class="border p-2 w-full rounded bg-gray-100" readonly>
                        <small class="text-gray-500">Stock is managed through "Add Stock" batches. Current value shows non-expired available stock.</small>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Reorder Level *</label>
                        <input type="number" name="prod_reorderlevel" value="${data.prod_reorderlevel || ''}" class="border p-2 w-full rounded" required min="0">
                        <small class="text-gray-500">Alert level for low stock (must be 0 or greater)</small>
                    </div>
                    <input type="hidden" name="branch_id" value="${data.branch_id}">
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
                    
                    // Set initial field visibility based on product type
                    setTimeout(() => toggleProductTypeFields('edit'), 0);
                })
                .catch(error => {
                    console.error('Error loading manufacturers:', error);
                });
        }

        function closeProductModal() {
            document.getElementById('productModal').classList.add('hidden');
            document.getElementById('productModalForm').reset();

            // Remove method field
            let methodField = document.querySelector('#productModalForm input[name="_method"]');
            if (methodField) methodField.remove();
        }

        // LINKED CONSUMABLES MODAL FUNCTIONS
        function openLinkedConsumablesModal(productId, productName) {
            document.getElementById('linkedConsumablesModal').classList.remove('hidden');
            document.getElementById('linkedConsumableProductName').textContent = productName;
            document.getElementById('linkedConsumableProductId').value = productId;
            
            // Load available consumable products for the dropdown
            loadAvailableConsumables(productId);
            
            // Load currently linked consumables
            loadLinkedConsumables(productId);
        }

        function closeLinkedConsumablesModal() {
            document.getElementById('linkedConsumablesModal').classList.add('hidden');
            document.getElementById('linkedConsumableProductId').value = '';
            document.getElementById('linkedConsumableSelect').innerHTML = '<option value="">Select a consumable product...</option>';
            document.getElementById('linkedConsumablesList').innerHTML = '';
            document.getElementById('linkedConsumableQty').value = 1;
        }

        function loadAvailableConsumables(excludeProductId) {
            fetch(`/products/consumables/available?exclude=${excludeProductId}`)
                .then(response => response.json())
                .then(data => {
                    let select = document.getElementById('linkedConsumableSelect');
                    select.innerHTML = '<option value="">Select a consumable product...</option>';
                    
                    if (data.success && data.products) {
                        data.products.forEach(product => {
                            const stockStatus = product.available_stock <= 0 ? ' (Out of Stock)' : ` (Stock: ${product.available_stock})`;
                            select.innerHTML += `<option value="${product.prod_id}" ${product.available_stock <= 0 ? 'disabled' : ''}>${escapeHtml(product.prod_name)} - ${product.prod_category || 'No Category'}${stockStatus}</option>`;
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading consumables:', error);
                });
        }

        function loadLinkedConsumables(productId) {
            document.getElementById('linkedConsumablesList').innerHTML = `
                <div class="text-center py-4 text-gray-500">
                    <i class="fas fa-spinner fa-spin mr-1"></i> Loading linked consumables...
                </div>`;

            fetch(`/products/${productId}/linked-consumables`)
                .then(response => response.json())
                .then(data => {
                    let listDiv = document.getElementById('linkedConsumablesList');
                    
                    if (data.success && data.consumables && data.consumables.length > 0) {
                        let html = '<div class="overflow-x-auto"><table class="w-full text-sm border"><thead class="bg-gray-100"><tr>';
                        html += '<th class="border px-3 py-2 text-left">Consumable Product</th>';
                        html += '<th class="border px-3 py-2 text-center">Category</th>';
                        html += '<th class="border px-3 py-2 text-center">Qty per Use</th>';
                        html += '<th class="border px-3 py-2 text-center">Current Stock</th>';
                        html += '<th class="border px-3 py-2 text-center">Actions</th>';
                        html += '</tr></thead><tbody>';

                        data.consumables.forEach(item => {
                            const stockClass = item.consumable_product.available_stock <= 0 ? 'text-red-600 font-bold' : '';
                            html += `<tr class="hover:bg-gray-50">`;
                            html += `<td class="border px-3 py-2">${escapeHtml(item.consumable_product.prod_name)}</td>`;
                            html += `<td class="border px-3 py-2 text-center">${item.consumable_product.prod_category || 'N/A'}</td>`;
                            html += `<td class="border px-3 py-2 text-center">${item.quantity}</td>`;
                            html += `<td class="border px-3 py-2 text-center ${stockClass}">${item.consumable_product.available_stock ?? 0}</td>`;
                            html += `<td class="border px-3 py-2 text-center">
                                <button onclick="removeLinkedConsumable(${item.id})" class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 text-xs" title="Remove">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>`;
                            html += `</tr>`;
                        });

                        html += '</tbody></table></div>';
                        listDiv.innerHTML = html;
                    } else {
                        listDiv.innerHTML = `
                            <div class="text-center py-6 text-gray-500 bg-gray-50 rounded-lg">
                                <i class="fas fa-unlink text-3xl mb-2"></i>
                                <p>No linked consumables yet.</p>
                                <p class="text-xs mt-1">Add consumables above to auto-deduct them when this product is used.</p>
                            </div>`;
                    }
                })
                .catch(error => {
                    console.error('Error loading linked consumables:', error);
                    document.getElementById('linkedConsumablesList').innerHTML = `
                        <div class="text-center py-4 text-red-500">
                            <i class="fas fa-exclamation-circle mr-1"></i> Error loading linked consumables
                        </div>`;
                });
        }

        function addLinkedConsumable() {
            const productId = document.getElementById('linkedConsumableProductId').value;
            const consumableId = document.getElementById('linkedConsumableSelect').value;
            const quantity = document.getElementById('linkedConsumableQty').value;

            if (!consumableId) {
                alert('Please select a consumable product');
                return;
            }

            if (!quantity || quantity < 1) {
                alert('Please enter a valid quantity (minimum 1)');
                return;
            }

            fetch(`/products/${productId}/linked-consumables`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    consumable_product_id: consumableId,
                    quantity: quantity
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadLinkedConsumables(productId);
                    document.getElementById('linkedConsumableSelect').value = '';
                    document.getElementById('linkedConsumableQty').value = 1;
                } else {
                    alert(data.message || 'Failed to add linked consumable');
                }
            })
            .catch(error => {
                console.error('Error adding linked consumable:', error);
                alert('Error adding linked consumable');
            });
        }

        function removeLinkedConsumable(linkId) {
            if (!confirm('Remove this linked consumable?')) return;

            const productId = document.getElementById('linkedConsumableProductId').value;

            fetch(`/products/linked-consumables/${linkId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadLinkedConsumables(productId);
                } else {
                    alert(data.message || 'Failed to remove linked consumable');
                }
            })
            .catch(error => {
                console.error('Error removing linked consumable:', error);
                alert('Error removing linked consumable');
            });
        }

        // UPDATE STOCK MODAL
        function openUpdateStockModal(data) {
            document.getElementById('updateStockModal').classList.remove('hidden');
            document.getElementById('currentStock').innerText = data.prod_stocks || 0;
            document.getElementById('productName').innerText = data.prod_name;

            let form = document.getElementById('updateStockForm');
            form.action = `/inventory/update-stock/${data.prod_id}`;
            form.querySelector('input[name=product_id]').value = data.prod_id;
            if (form.querySelector('input[name=reorder_level]')) {
                form.querySelector('input[name=reorder_level]').value = data.prod_reorderlevel || '';
            }

            // Auto-generate batch code: [First 3 letters of product]-[MMDD]-[HMM]
            generateBatchCode(data.prod_name);
        }

        function generateBatchCode(productName) {
            const now = new Date();
            
            // Get first 3 letters of product name (uppercase, remove special chars)
            const cleanName = productName.replace(/[^a-zA-Z]/g, '').toUpperCase();
            const prefix = cleanName.substring(0, 3) || 'PRD';
            
            // Get month and day (MMDD format)
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const dateCode = month + day;
            
            // Get hour and minutes (HMM format - single digit hour, 2 digit minutes)
            const hour = now.getHours();
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const timeCode = hour + minutes;
            
            // Combine: PREFIX-MMDD-HMM
            const batchCode = `${prefix}-${dateCode}-${timeCode}`;
            
            document.getElementById('autoBatchCode').value = batchCode;
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
            form.querySelector('input[name=damaged_qty]').value = 0;
            form.querySelector('input[name=pullout_qty]').value = 0;
            form.querySelector('textarea[name=reason]').value = '';

            // Load stock batches for this product
            loadStockBatches(data.prod_id);
        }

        function closeDamagePulloutModal() {
            document.getElementById('damagePulloutModal').classList.add('hidden');
            document.getElementById('damagePulloutForm').reset();
            document.getElementById('stockBatchSelect').innerHTML = '<option value="">-- Select Batch --</option>';
        }

        // Load available stock batches for damage/pullout
        function loadStockBatches(productId) {
            const select = document.getElementById('stockBatchSelect');
            select.innerHTML = '<option value="">Loading...</option>';

            fetch(`/products/${productId}/stock-batches`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.batches && data.batches.length > 0) {
                        let options = '<option value="">-- Select Batch --</option>';
                        data.batches.forEach(batch => {
                            const expiry = new Date(batch.expire_date).toLocaleDateString();
                            const status = batch.is_expired ? ' (EXPIRED)' : '';
                            const disabled = batch.available_quantity <= 0 || batch.is_expired ? ' disabled' : '';
                            options += `<option value="${batch.id}"${disabled}>
                                Batch: ${batch.batch} | Available: ${batch.available_quantity}/${batch.quantity} | Exp: ${expiry}${status}
                            </option>`;
                        });
                        select.innerHTML = options;
                    } else {
                        select.innerHTML = '<option value="">No stock batches available</option>';
                    }
                })
                .catch(error => {
                    console.error('Error loading stock batches:', error);
                    select.innerHTML = '<option value="">Error loading batches</option>';
                });
        }
        
        // ===========================================
        // ✅ EQUIPMENT STATUS UPDATE MODAL
        // ===========================================
        function openUpdateStatusModal(data) {
            const totalQty = parseInt(data.equipment_quantity) || 0;
            const available = parseInt(data.equipment_available) || 0;
            const maintenance = parseInt(data.equipment_maintenance) || 0;
            const outOfService = parseInt(data.equipment_out_of_service) || 0;
            
            // Show modal
            document.getElementById('updateEquipmentStatusModal').classList.remove('hidden');
            
            // Set equipment info
            document.getElementById('statusEquipmentName').innerText = data.equipment_name;
            document.getElementById('statusTotalQuantity').innerText = totalQty;
            
            // Set form action
            let form = document.getElementById('updateEquipmentStatusForm');
            form.action = `/equipment/${data.equipment_id}/update-status`;
            form.querySelector('#statusEquipmentId').value = data.equipment_id;
            
            // Populate dropdowns
            populateStatusDropdown('statusAvailableDropdown', totalQty, available);
            populateStatusDropdown('statusMaintenanceDropdown', totalQty, maintenance);
            populateStatusDropdown('statusOutOfServiceDropdown', totalQty, outOfService);
            
            // Set hidden fields
            document.getElementById('statusEquipmentAvailable').value = available;
            document.getElementById('statusEquipmentMaintenance').value = maintenance;
            document.getElementById('statusEquipmentOutOfService').value = outOfService;
            
            // Calculate and display In Use
            validateStatusDistribution();
        }

        function populateStatusDropdown(dropdownId, maxQty, selectedValue) {
            const dropdown = document.getElementById(dropdownId);
            dropdown.innerHTML = '<option value="">-- Select Quantity --</option>';
            
            for (let i = 0; i <= maxQty; i++) {
                const option = document.createElement('option');
                option.value = i;
                option.textContent = `${i} unit${i !== 1 ? 's' : ''}`;
                if (i === selectedValue) {
                    option.selected = true;
                }
                dropdown.appendChild(option);
            }
        }

        function updateStatusDistribution(field) {
            const dropdownMap = {
                'available': 'statusAvailableDropdown',
                'maintenance': 'statusMaintenanceDropdown',
                'out_of_service': 'statusOutOfServiceDropdown'
            };
            
            const hiddenFieldMap = {
                'available': 'statusEquipmentAvailable',
                'maintenance': 'statusEquipmentMaintenance',
                'out_of_service': 'statusEquipmentOutOfService'
            };
            
            const dropdownId = dropdownMap[field];
            const hiddenFieldId = hiddenFieldMap[field];
            
            const selectedValue = document.getElementById(dropdownId)?.value || '0';
            const hiddenField = document.getElementById(hiddenFieldId);
            
            if (hiddenField) {
                hiddenField.value = selectedValue;
            }
            
            validateStatusDistribution();
        }

        function validateStatusDistribution() {
            const totalQty = parseInt(document.getElementById('statusTotalQuantity')?.textContent) || 0;
            const available = parseInt(document.getElementById('statusEquipmentAvailable')?.value) || 0;
            const maintenance = parseInt(document.getElementById('statusEquipmentMaintenance')?.value) || 0;
            const outOfService = parseInt(document.getElementById('statusEquipmentOutOfService')?.value) || 0;
            
            const sum = available + maintenance + outOfService;
            const inUse = Math.max(0, totalQty - sum);
            
            // Update In Use display
            const inUseDisplay = document.getElementById('statusInUseDisplay');
            if (inUseDisplay) {
                inUseDisplay.textContent = inUse;
            }
            
            const messageDiv = document.getElementById('statusValidationMessage');
            const submitBtn = document.getElementById('statusSubmitBtn');
            
            if (!messageDiv) return true;
            
            if (sum > totalQty) {
                messageDiv.innerHTML = `
                    <div class="p-3 bg-red-100 border border-red-400 rounded-lg text-red-700">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Error:</strong> Total allocated (${sum}) exceeds total quantity (${totalQty}). 
                        Please reduce by <strong>${sum - totalQty} unit(s)</strong>.
                    </div>
                `;
                if (submitBtn) submitBtn.disabled = true;
                return false;
            } else {
                messageDiv.innerHTML = `
                    <div class="p-3 bg-green-100 border border-green-400 rounded-lg text-green-700">
                        <i class="fas fa-check-circle mr-2"></i>
                        <strong>Valid:</strong> ${sum} allocated across statuses | ${inUse} in use | ${totalQty} total
                    </div>
                `;
                if (submitBtn) submitBtn.disabled = false;
                return true;
            }
        }

        function validateStatusFormSubmit() {
            const isValid = validateStatusDistribution();
            if (!isValid) {
                alert('❌ Cannot save: The sum of status quantities exceeds total equipment quantity. Please adjust the values.');
                return false;
            }
            return true;
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
                    const consumableProducts = data.consumable_products || [];
                    const stockHistory = data.stock_history || [];

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
                        <h4 class="font-bold text-lg mb-3">Consumable Products Used in Service</h4>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm border">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="p-2 border">Product Name</th>
                                        <th class="p-2 border">Type</th>
                                        <th class="p-2 border">Current Stock</th>
                                        <th class="p-2 border">Quantity Used/Service</th>
                                        <th class="p-2 border">Billable</th>
                                        <th class="p-2 border">Added By</th>
                                        <th class="p-2 border">Date Added</th>
                                    </tr>
                                </thead>
                                <tbody>`;

                    if (consumableProducts.length > 0) {
                        consumableProducts.forEach(product => {
                            const addedDate = product.created_at ? new Date(product.created_at) : null;
                            const stockClass = product.prod_stocks <= 10 ? 'text-red-600 font-bold' : 'text-green-600';
                            
                            content += `
                            <tr>
                                <td class="p-2 border font-medium">${product.prod_name}</td>
                                <td class="p-2 border">
                                    <span class="px-2 py-1 text-xs rounded ${product.prod_type === 'Consumable' ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-700'}">${product.prod_type || 'N/A'}</span>
                                </td>
                                <td class="p-2 border text-center ${stockClass}">${product.prod_stocks || 0}</td>
                                <td class="p-2 border text-center font-semibold">${product.quantity_used || 0}</td>
                                <td class="p-2 border text-center">${product.is_billable ? '<span class="text-green-600">✓ Yes</span>' : '<span class="text-gray-400">✗ No</span>'}</td>
                                <td class="p-2 border">${product.added_by || 'System'}</td>
                                <td class="p-2 border">${addedDate ? addedDate.toLocaleDateString() : 'N/A'}</td>
                            </tr>`;
                        });
                    } else {
                        content += '<tr><td colspan="7" class="p-4 text-center text-gray-500">No consumable products attached to this service</td></tr>';
                    }

                    content += `
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-6">
                        <h4 class="font-bold text-lg mb-3">Stock Usage History (Service Transactions)</h4>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm border">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="p-2 border">Date</th>
                                        <th class="p-2 border">Product</th>
                                        <th class="p-2 border">Quantity Used</th>
                                        <th class="p-2 border">Appointment</th>
                                        <th class="p-2 border">Performed By</th>
                                        <th class="p-2 border">Notes</th>
                                    </tr>
                                </thead>
                                <tbody>`;

                    if (stockHistory.length > 0) {
                        stockHistory.forEach(transaction => {
                            const transDate = transaction.created_at ? new Date(transaction.created_at) : null;
                            
                            content += `
                            <tr>
                                <td class="p-2 border">${transDate ? transDate.toLocaleDateString() + ' ' + transDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : 'N/A'}</td>
                                <td class="p-2 border font-medium">${transaction.prod_name}</td>
                                <td class="p-2 border text-center text-red-600 font-bold">${Math.abs(transaction.quantity_change || 0)}</td>
                                <td class="p-2 border">${transaction.appoint_id ? '#' + transaction.appoint_id : 'N/A'}</td>
                                <td class="p-2 border">${transaction.user_name || 'System'}</td>
                                <td class="p-2 border">${transaction.notes || transaction.reference || 'N/A'}</td>
                            </tr>`;
                        });
                    } else {
                        content += '<tr><td colspan="6" class="p-4 text-center text-gray-500">No stock transaction history found</td></tr>';
                    }

                    content += `
                                </tbody>
                            </table>
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
                        'partial': 'text-yellow-600',
                        'unavailable': 'text-orange-600',
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
                                <div><span class="font-medium">Overall Status:</span> <span class="capitalize font-bold ${statusColors[availabilityStatus]}">${availabilityStatus}</span></div>
                            </div>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-bold text-lg mb-3">Quantity Breakdown</h4>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center p-2 bg-white rounded border">
                                    <span class="font-medium">Total Quantity:</span> 
                                    <span class="text-lg font-bold text-blue-600">${usageData.total_quantity}</span>
                                </div>
                                <div class="flex justify-between items-center p-2 bg-white rounded border">
                                    <span class="font-medium">Available:</span> 
                                    <span class="text-lg font-bold text-green-600">${usageData.available_quantity}</span>
                                </div>
                                <div class="flex justify-between items-center p-2 bg-white rounded border">
                                    <span class="font-medium">Under Maintenance:</span> 
                                    <span class="text-lg font-bold text-yellow-600">${usageData.maintenance_quantity}</span>
                                </div>
                                <div class="flex justify-between items-center p-2 bg-white rounded border">
                                    <span class="font-medium">Out of Service:</span> 
                                    <span class="text-lg font-bold text-red-600">${usageData.out_of_service_quantity}</span>
                                </div>
                                <div class="flex justify-between items-center p-2 bg-white rounded border">
                                    <span class="font-medium">In Use:</span> 
                                    <span class="text-lg font-bold text-purple-600">${usageData.in_use_quantity}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <h4 class="font-bold text-lg mb-3">Status Distribution</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                                <div class="text-sm text-green-600 font-medium">Available</div>
                                <div class="text-2xl font-bold text-green-700">${usageData.available_quantity}</div>
                                <div class="text-xs text-green-600 mt-1">${usageData.total_quantity > 0 ? Math.round((usageData.available_quantity / usageData.total_quantity) * 100) : 0}%</div>
                            </div>
                            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                                <div class="text-sm text-yellow-600 font-medium">Maintenance</div>
                                <div class="text-2xl font-bold text-yellow-700">${usageData.maintenance_quantity}</div>
                                <div class="text-xs text-yellow-600 mt-1">${usageData.total_quantity > 0 ? Math.round((usageData.maintenance_quantity / usageData.total_quantity) * 100) : 0}%</div>
                            </div>
                            <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                                <div class="text-sm text-red-600 font-medium">Out of Service</div>
                                <div class="text-2xl font-bold text-red-700">${usageData.out_of_service_quantity}</div>
                                <div class="text-xs text-red-600 mt-1">${usageData.total_quantity > 0 ? Math.round((usageData.out_of_service_quantity / usageData.total_quantity) * 100) : 0}%</div>
                            </div>
                            <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                                <div class="text-sm text-purple-600 font-medium">In Use</div>
                                <div class="text-2xl font-bold text-purple-700">${usageData.in_use_quantity}</div>
                                <div class="text-xs text-purple-600 mt-1">${usageData.total_quantity > 0 ? Math.round((usageData.in_use_quantity / usageData.total_quantity) * 100) : 0}%</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 bg-blue-50 p-4 rounded-lg border border-blue-200">
                        <p class="text-sm text-blue-800">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>Note:</strong> Update equipment status quantities through the edit function to reflect current availability, maintenance, and out-of-service items.
                            If no values are set, total quantity is treated as available.
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
                <input type="hidden" name="branch_id" value="{{ $activeBranchId ?? '' }}">
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
                <input type="hidden" name="branch_id" value="{{ $activeBranchId ?? '' }}">
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
                     <option value="Vaccination" ${data.serv_type === 'Vaccination' ? 'selected' : ''}>Vaccination</option>
                    <option value="Deworming" ${data.serv_type === 'Deworming' ? 'selected' : ''}>Deworming</option>
                    <option value="Grooming" ${data.serv_type === 'Grooming' ? 'selected' : ''}>Grooming</option>
                    <option value="Emergency" ${data.serv_type === 'Emergency' ? 'selected' : ''}>Emergency</option>
                    <option value="Check-up" ${data.serv_type === 'Check-up' ? 'selected' : ''}>Check-up</option>
                    <option value="Diagnostics" ${data.serv_type === 'Diagnostics' ? 'selected' : ''}>Diagnostics</option>
                    <option value="Surgical" ${data.serv_type === 'Surgical' ? 'selected' : ''}>Surgical</option>
                    <option value="Boarding" ${data.serv_type === 'Boarding' ? 'selected' : ''}>Boarding</option>
                </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Price</label>
                    <input type="number" step="0.01" name="serv_price" value="${data.serv_price}" class="border p-2 w-full rounded" required>
                </div>
                <input type="hidden" name="branch_id" value="${data.branch_id}">
                <div class="mb-4 md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="serv_description" class="border p-2 w-full rounded" rows="3">${escapeHtml(data.serv_description || '')}</textarea>
                </div>`;

                document.getElementById('generalModalForm').action = `/services/${data.serv_id}`;

            } else if (type === 'equipment') {
                const totalQty = parseInt(data.equipment_quantity) || 0;
                const availableQty = parseInt(data.equipment_available) || 0;
                const maintenanceQty = parseInt(data.equipment_maintenance) || 0;
                const outOfServiceQty = parseInt(data.equipment_out_of_service) || 0;
                
                fields = `
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Equipment Name *</label>
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
                    <label class="block text-sm font-medium text-gray-700 mb-2">Total Quantity *</label>
                    <input type="number" id="equipment_quantity" name="equipment_quantity" value="${totalQty}" min="0" class="border p-2 w-full rounded" required onchange="validateEquipmentStatus()">
                    <small class="text-gray-500">Total equipment quantity</small>
                </div>

                <div class="mb-4 md:col-span-2 p-4 bg-blue-50 rounded-lg border border-blue-200">
                    <h4 class="font-bold text-sm text-blue-800 mb-3">
                        <i class="fas fa-clipboard-list mr-2"></i>Equipment Status Distribution
                    </h4>
                    <p class="text-xs text-blue-600 mb-4">Assign individual equipment units to different status categories. Total cannot exceed available quantity.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="border rounded-lg p-3 bg-white">
                            <label class="flex items-center text-sm font-medium text-green-700 mb-2">
                                <i class="fas fa-check-circle mr-2"></i>Available for Use
                            </label>
                            <select id="equipment_available_dropdown" class="border p-2 w-full rounded border-green-300 mb-2" onchange="updateEquipmentStatusFromDropdown('available')">
                                <option value="">-- Select Quantity --</option>`;
                                for(let i = 0; i <= totalQty; i++) {
                                    fields += `<option value="${i}" ${i === availableQty ? 'selected' : ''}>${i} unit${i !== 1 ? 's' : ''}</option>`;
                                }
                fields += `
                            </select>
                            <input type="hidden" id="equipment_available" name="equipment_available" value="${availableQty}">
                            <small class="text-xs text-gray-600">Equipment ready to be used</small>
                        </div>

                        <div class="border rounded-lg p-3 bg-white">
                            <label class="flex items-center text-sm font-medium text-yellow-700 mb-2">
                                <i class="fas fa-tools mr-2"></i>Under Maintenance
                            </label>
                            <select id="equipment_maintenance_dropdown" class="border p-2 w-full rounded border-yellow-300 mb-2" onchange="updateEquipmentStatusFromDropdown('maintenance')">
                                <option value="">-- Select Quantity --</option>`;
                                for(let i = 0; i <= totalQty; i++) {
                                    fields += `<option value="${i}" ${i === maintenanceQty ? 'selected' : ''}>${i} unit${i !== 1 ? 's' : ''}</option>`;
                                }
                fields += `
                            </select>
                            <input type="hidden" id="equipment_maintenance" name="equipment_maintenance" value="${maintenanceQty}">
                            <small class="text-xs text-gray-600">Equipment being serviced/repaired</small>
                        </div>

                        <div class="border rounded-lg p-3 bg-white">
                            <label class="flex items-center text-sm font-medium text-red-700 mb-2">
                                <i class="fas fa-times-circle mr-2"></i>Out of Service
                            </label>
                            <select id="equipment_out_of_service_dropdown" class="border p-2 w-full rounded border-red-300 mb-2" onchange="updateEquipmentStatusFromDropdown('out_of_service')">
                                <option value="">-- Select Quantity --</option>`;
                                for(let i = 0; i <= totalQty; i++) {
                                    fields += `<option value="${i}" ${i === outOfServiceQty ? 'selected' : ''}>${i} unit${i !== 1 ? 's' : ''}</option>`;
                                }
                fields += `
                            </select>
                            <input type="hidden" id="equipment_out_of_service" name="equipment_out_of_service" value="${outOfServiceQty}">
                            <small class="text-xs text-gray-600">Equipment permanently damaged/retired</small>
                        </div>

                        <div class="border rounded-lg p-3 bg-purple-50">
                            <label class="flex items-center text-sm font-medium text-purple-700 mb-2">
                                <i class="fas fa-hand-holding mr-2"></i>Currently In Use
                            </label>
                            <div class="text-3xl font-bold text-purple-700 mb-2" id="equipment_in_use_display">
                                ${Math.max(0, totalQty - availableQty - maintenanceQty - outOfServiceQty)}
                            </div>
                            <small class="text-xs text-gray-600">Auto-calculated (Total - Other Statuses)</small>
                        </div>
                    </div>
                    
                    <div id="status_validation_message" class="mt-4 text-sm"></div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Equipment Image</label>
                    <input type="file" name="equipment_image" accept="image/*" class="border p-2 w-full rounded">`;

                if (data.equipment_image) {
                    fields += `<div class="mt-2 text-sm text-gray-600">Current image: <img src="{{ asset('storage/') }}/${data.equipment_image}" class="h-16 w-16 object-cover inline-block ml-2 rounded"></div>`;
                }

                fields += `
                </div>
                <input type="hidden" name="branch_id" value="${data.branch_id}">
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

        // TOGGLE FIELDS BASED ON PRODUCT TYPE (Sale/Consumable)
        function toggleProductTypeFields(mode) {
            const typeSelect = document.getElementById(mode + 'ProductType');
            const priceField = document.getElementById(mode + 'ProductPriceField');
            const priceInput = document.getElementById(mode + 'ProductPrice');
            const priceRequired = document.getElementById(mode + 'PriceRequired');
            const priceNote = document.getElementById(mode + 'PriceNote');
            
            const productCategoryField = document.getElementById(mode + 'ProductCategoryField');
            const serviceCategoryField = document.getElementById(mode + 'ServiceCategoryField');
            const productCategorySelect = document.getElementById(mode + 'ProductCategory');
            const serviceCategorySelect = document.getElementById(mode + 'ServiceCategory');
            
            const manufacturerField = document.getElementById(mode + 'ManufacturerField');
            const manufacturerSelect = document.getElementById(mode + 'Manufacturer');
            const manufacturerNote = document.getElementById(mode + 'ManufacturerNote');
            
            if (!typeSelect) return;
            
            const selectedType = typeSelect.value;
            const isConsumable = selectedType === 'Consumable';
            const isPrescription = selectedType === 'Prescription';
            const isSale = selectedType === 'Sale';
            
            // Toggle Price Field
            if (priceField) {
                if (isConsumable) {
                    priceField.style.display = 'none';
                    if (priceInput) {
                        priceInput.removeAttribute('required');
                        priceInput.value = '0';
                    }
                    if (priceRequired) priceRequired.style.display = 'none';
                    if (priceNote) priceNote.classList.remove('hidden');
                } else {
                    priceField.style.display = 'block';
                    if (priceInput) {
                        priceInput.setAttribute('required', 'required');
                        if (priceInput.value === '0') priceInput.value = '';
                    }
                    if (priceRequired) priceRequired.style.display = 'inline';
                    if (priceNote) priceNote.classList.add('hidden');
                }
            }
            
            // Toggle Manufacturer Field based on Product Type
            if (manufacturerSelect) {
                if (isConsumable || isPrescription) {
                    // Consumable or Prescription: Enable Manufacturer
                    manufacturerSelect.disabled = false;
                    manufacturerSelect.classList.remove('bg-gray-100', 'cursor-not-allowed');
                    if (manufacturerNote) manufacturerNote.textContent = 'Select manufacturer for this product';
                } else {
                    // Sale: Disable Manufacturer
                    manufacturerSelect.disabled = true;
                    manufacturerSelect.value = ''; // Clear value
                    manufacturerSelect.classList.add('bg-gray-100', 'cursor-not-allowed');
                    if (manufacturerNote) manufacturerNote.textContent = 'Manufacturer not applicable for Sale products';
                }
            }
            
            // Toggle Category Fields based on Product Type - HIDE/SHOW instead of disable
            if (isConsumable) {
                // Consumable: Hide Product Category, Show Service Category
                if (productCategoryField) {
                    productCategoryField.style.display = 'none';
                }
                if (productCategorySelect) {
                    productCategorySelect.disabled = true;
                    productCategorySelect.value = ''; // Clear value
                }
                if (serviceCategoryField) {
                    serviceCategoryField.style.display = 'block';
                }
                if (serviceCategorySelect) {
                    serviceCategorySelect.disabled = false;
                    serviceCategorySelect.classList.remove('bg-gray-100', 'cursor-not-allowed');
                }
            } else if (isPrescription) {
                // Prescription: Show Product Category, Hide Service Category
                if (productCategoryField) {
                    productCategoryField.style.display = 'block';
                }
                if (productCategorySelect) {
                    productCategorySelect.disabled = false;
                    productCategorySelect.classList.remove('bg-gray-100', 'cursor-not-allowed');
                }
                if (serviceCategoryField) {
                    serviceCategoryField.style.display = 'none';
                }
                if (serviceCategorySelect) {
                    serviceCategorySelect.disabled = true;
                    serviceCategorySelect.value = ''; // Clear value
                }
            } else {
                // Sale: Show Product Category, Hide Service Category
                if (productCategoryField) {
                    productCategoryField.style.display = 'block';
                }
                if (productCategorySelect) {
                    productCategorySelect.disabled = false;
                    productCategorySelect.classList.remove('bg-gray-100', 'cursor-not-allowed');
                }
                if (serviceCategoryField) {
                    serviceCategoryField.style.display = 'none';
                }
                if (serviceCategorySelect) {
                    serviceCategorySelect.disabled = true;
                    serviceCategorySelect.value = ''; // Clear value
                }
            }
        }

        // Legacy function for backward compatibility
        function toggleProductPriceField(mode) {
            toggleProductTypeFields(mode);
        }

        // EQUIPMENT STATUS: UPDATE FROM DROPDOWN
        function updateEquipmentStatusFromDropdown(field) {
            const dropdownId = 'equipment_' + field + '_dropdown';
            const hiddenFieldId = 'equipment_' + field;
            
            const selectedValue = document.getElementById(dropdownId)?.value || '0';
            const hiddenField = document.getElementById(hiddenFieldId);
            
            if (hiddenField) {
                hiddenField.value = selectedValue;
            }
            
            validateEquipmentStatus();
        }

        // EQUIPMENT STATUS VALIDATION
        function validateEquipmentStatus() {
            const totalQty = parseInt(document.getElementById('equipment_quantity')?.value) || 0;
            const available = parseInt(document.getElementById('equipment_available')?.value) || 0;
            const maintenance = parseInt(document.getElementById('equipment_maintenance')?.value) || 0;
            const outOfService = parseInt(document.getElementById('equipment_out_of_service')?.value) || 0;
            
            const sum = available + maintenance + outOfService;
            const inUse = Math.max(0, totalQty - sum);
            
            const messageDiv = document.getElementById('status_validation_message');
            const inUseDisplay = document.getElementById('equipment_in_use_display');
            const submitBtn = document.getElementById('generalModalSubmitBtn');
            
            // Update In Use display
            if (inUseDisplay) {
                inUseDisplay.textContent = inUse;
            }
            
            if (!messageDiv) return true; // If not in equipment edit mode, skip validation
            
            if (sum > totalQty) {
                messageDiv.innerHTML = `
                    <div class="p-3 bg-red-100 border border-red-400 rounded text-red-700">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Error:</strong> Total allocated (${sum}) exceeds available quantity (${totalQty}). 
                        Please reduce by ${sum - totalQty} unit(s).
                    </div>
                `;
                if (submitBtn) submitBtn.disabled = true;
                return false;
            } else {
                messageDiv.innerHTML = `
                    <div class="p-3 bg-green-100 border border-green-400 rounded text-green-700">
                        <i class="fas fa-check-circle mr-2"></i>
                        <strong>Valid Distribution:</strong> ${sum} allocated | ${inUse} in use | ${totalQty} total
                    </div>
                `;
                if (submitBtn) submitBtn.disabled = false;
                return true;
            }
        }

        function validateEquipmentFormSubmit() {
            const activeTab = document.getElementById('active_tab')?.value;
            
            // Only validate if we're editing equipment
            if (activeTab === 'equipment' && document.getElementById('equipment_quantity')) {
                if (!validateEquipmentStatus()) {
                    alert('Please fix the equipment status validation errors before submitting.');
                    return false;
                }
            }
            
            return true;
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
                    // Skip service rows - they use the custom category filter
                    if (tr.classList.contains('service-row')) return;
                    
                    const text = tr.textContent.toLowerCase();
                    tr.style.display = !needle || text.includes(needle) ? '' : 'none';
                });
            }
            
            // Custom filter for services that combines search and category - make it global
            window.filterServicesTable = function() {
                const searchTerm = (document.getElementById('servicesSearch')?.value || '').toLowerCase().trim();
                const category = (document.getElementById('serviceCategoryFilter')?.value || '').toLowerCase();
                const rows = document.querySelectorAll('#servicesTab table tbody tr.service-row');
                
                console.log('Filtering services - Category:', category, 'Search:', searchTerm, 'Rows found:', rows.length);
                
                rows.forEach(row => {
                    const rowCategory = (row.dataset.category || '').toLowerCase();
                    const rowText = row.textContent.toLowerCase();
                    
                    const matchesCategory = !category || rowCategory === category;
                    const matchesSearch = !searchTerm || rowText.includes(searchTerm);
                    
                    row.style.display = (matchesCategory && matchesSearch) ? '' : 'none';
                });
            };

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
                    // Use custom filter for services tab
                    if (tab === 'services') {
                        filterServicesTable();
                    } else {
                        filterBody(tbody, last);
                    }
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
                    // Use custom filter for services tab
                    if (tab === 'services') {
                        filterServicesTable();
                    } else {
                        filterBody(tbody, q);
                    }
                });
            }

            // Refresh filters when switching tabs (and initialize if needed)
            const originalSwitchMainTab = window.switchMainTab;
            window.switchMainTab = function(tabId, tabName) {
                if (originalSwitchMainTab) originalSwitchMainTab(tabId, tabName);
                
                // Initialize or refresh the filter for the active tab
                setTimeout(() => {
                    if (typeof ListFilter === 'undefined') return;
                    
                    if (tabName === 'products') {
                        if (!window.listFilters['products']) {
                            window.listFilters['products'] = new ListFilter({
                                tableSelector: '#productsTable tbody',
                                searchInputId: 'productsSearch',
                                perPageSelectId: 'productsPerPage',
                                paginationContainerId: 'productsPagination',
                                searchColumns: [1, 2, 3, 4, 5, 7],
                                filterSelects: [
                                    { selectId: 'productsType', columnIndex: 3 }
                                ],
                                storageKey: 'productsFilter',
                                noResultsMessage: 'No products found.'
                            });
                        } else {
                            window.listFilters['products'].refresh();
                        }
                    } else if (tabName === 'services') {
                        if (!window.listFilters['services']) {
                            window.listFilters['services'] = new ListFilter({
                                tableSelector: '#servicesTable tbody',
                                searchInputId: 'servicesSearch',
                                perPageSelectId: 'servicesPerPage',
                                paginationContainerId: 'servicesPagination',
                                searchColumns: [0, 1, 2, 3, 4],
                                filterSelects: [
                                    { selectId: 'servicesCategoryFilter', columnIndex: 1 } // Type column
                                ],
                                storageKey: 'servicesFilter',
                                noResultsMessage: 'No services found.'
                            });
                        } else {
                            window.listFilters['services'].refresh();
                        }
                    } else if (tabName === 'equipment') {
                        if (!window.listFilters['equipment']) {
                            window.listFilters['equipment'] = new ListFilter({
                                tableSelector: '#equipmentTable tbody',
                                searchInputId: 'equipmentSearch',
                                perPageSelectId: 'equipmentPerPage',
                                paginationContainerId: 'equipmentPagination',
                                searchColumns: [1, 2, 3, 4, 5],
                                storageKey: 'equipmentFilter',
                                noResultsMessage: 'No equipment found.'
                            });
                        } else {
                            window.listFilters['equipment'].refresh();
                        }
                    }
                }, 100);
            };
        })();

        // Service Category Filter - calls the combined filter function
        function filterServicesByCategory() {
            const searchTerm = (document.getElementById('servicesSearch')?.value || '').toLowerCase().trim();
            const category = (document.getElementById('serviceCategoryFilter')?.value || '').toLowerCase();
            const rows = document.querySelectorAll('#servicesTab table tbody tr.service-row');
            
            rows.forEach(row => {
                const rowCategory = (row.dataset.category || '').toLowerCase().trim();
                const rowText = row.textContent.toLowerCase();
                
                // Handle category matching with variations (e.g., "diagnostic" matches "diagnostics")
                let matchesCategory = !category;
                if (category) {
                    // Check exact match or if one contains the other (for diagnostic/diagnostics)
                    matchesCategory = rowCategory === category || 
                                     rowCategory.startsWith(category) || 
                                     category.startsWith(rowCategory);
                }
                
                const matchesSearch = !searchTerm || rowText.includes(searchTerm);
                
                row.style.display = (matchesCategory && matchesSearch) ? '' : 'none';
            });
        }

        // Equipment Status Filter
        function filterEquipmentByStatus() {
            const searchTerm = (document.getElementById('equipmentSearch')?.value || '').toLowerCase().trim();
            const status = (document.getElementById('equipmentStatusFilter')?.value || '').toLowerCase();
            const rows = document.querySelectorAll('#equipmentTab table tbody tr.equipment-row');
            
            rows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                const available = parseInt(row.dataset.available || 0);
                const inUse = parseInt(row.dataset.inuse || 0);
                const maintenance = parseInt(row.dataset.maintenance || 0);
                const outOfService = parseInt(row.dataset.outofservice || 0);
                
                let matchesStatus = !status;
                if (status === 'available') matchesStatus = available > 0;
                else if (status === 'in_use') matchesStatus = inUse > 0;
                else if (status === 'maintenance') matchesStatus = maintenance > 0;
                else if (status === 'out_of_service') matchesStatus = outOfService > 0;
                
                const matchesSearch = !searchTerm || rowText.includes(searchTerm);
                
                row.style.display = (matchesStatus && matchesSearch) ? '' : 'none';
            });
        }
        
        // Update equipment search to also respect status filter
        document.addEventListener('DOMContentLoaded', function() {
            const equipmentSearchInput = document.getElementById('equipmentSearch');
            if (equipmentSearchInput) {
                equipmentSearchInput.addEventListener('input', filterEquipmentByStatus);
            }
        });

        // ==========================================
        // MANUFACTURER MODAL FUNCTIONS
        // ==========================================
        function openManufacturerModal() {
            document.getElementById('manufacturerModal').classList.remove('hidden');
            loadManufacturers();
        }

        function closeManufacturerModal() {
            document.getElementById('manufacturerModal').classList.add('hidden');
            document.getElementById('newManufacturerName').value = '';
        }

        function loadManufacturers() {
            const listContainer = document.getElementById('manufacturersList');
            listContainer.innerHTML = '<div class="text-center py-4 text-gray-500"><i class="fas fa-spinner fa-spin mr-1"></i> Loading manufacturers...</div>';

            fetch('/manufacturers')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.manufacturers.length > 0) {
                        let html = '';
                        data.manufacturers.forEach(manufacturer => {
                            html += `
                            <div class="flex items-center justify-between p-3 bg-white border rounded-lg hover:bg-gray-50">
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-800">${escapeHtml(manufacturer.manufacturer_name)}</div>
                                </div>
                                <div class="flex gap-2 ml-3">
                                    <button onclick="openEditManufacturer(${manufacturer.manufacturer_id}, '${escapeHtml(manufacturer.manufacturer_name)}')" 
                                        class="px-2 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 text-xs" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteManufacturer(${manufacturer.manufacturer_id}, '${escapeHtml(manufacturer.manufacturer_name)}')" 
                                        class="px-2 py-1 bg-red-500 text-white rounded hover:bg-red-600 text-xs" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>`;
                        });
                        listContainer.innerHTML = html;
                    } else {
                        listContainer.innerHTML = '<div class="text-center py-4 text-gray-500"><i class="fas fa-industry mr-1"></i> No manufacturers found. Add one above!</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    listContainer.innerHTML = '<div class="text-center py-4 text-red-500"><i class="fas fa-exclamation-circle mr-1"></i> Error loading manufacturers</div>';
                });
        }

        function addManufacturer() {
            const name = document.getElementById('newManufacturerName').value.trim();

            if (!name) {
                alert('Please enter a manufacturer name');
                return;
            }

            fetch('/manufacturers', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    manufacturer_name: name
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + data.message);
                    document.getElementById('newManufacturerName').value = '';
                    loadManufacturers();
                } else {
                    alert('❌ Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ Error adding manufacturer');
            });
        }

        function openEditManufacturer(id, name) {
            document.getElementById('editManufacturerId').value = id;
            document.getElementById('editManufacturerName').value = name;
            document.getElementById('editManufacturerModal').classList.remove('hidden');
        }

        function closeEditManufacturerModal() {
            document.getElementById('editManufacturerModal').classList.add('hidden');
        }

        function updateManufacturer() {
            const id = document.getElementById('editManufacturerId').value;
            const name = document.getElementById('editManufacturerName').value.trim();

            if (!name) {
                alert('Please enter a manufacturer name');
                return;
            }

            fetch(`/manufacturers/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    manufacturer_name: name
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + data.message);
                    closeEditManufacturerModal();
                    loadManufacturers();
                } else {
                    alert('❌ Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ Error updating manufacturer');
            });
        }

        function deleteManufacturer(id, name) {
            if (!confirm(`Are you sure you want to delete manufacturer "${name}"?`)) {
                return;
            }

            fetch(`/manufacturers/${id}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + data.message);
                    loadManufacturers();
                } else {
                    alert('❌ Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ Error deleting manufacturer');
            });
        }
    </script>
@endsection