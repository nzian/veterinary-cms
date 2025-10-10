@extends('AdminBoard')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto p-6">
        <!-- Filter Section -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Report Type</label>
                    <select name="report" id="reportSelect" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @php
                            $reportOptions = [
                                'appointments' => 'Appointment Management',
                                'pets' => 'Pet Registration',
                                'billing' => 'Financial Billing',
                                'sales' => 'Product Sales',
                                'services' => 'Service Availability',
                                'inventory' => 'Inventory Status',
                                'revenue' => 'Revenue Analysis',
                                'referrals' => 'Referral Report', 
                                'equipment' => 'Equipment Inventory', 
                            ];
                        @endphp
                        @foreach($reportOptions as $key => $label)
                            <option value="{{ $key }}" {{ $reportType === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                    <input type="date" name="start_date" value="{{ $startDate }}" 
                           class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                    <input type="date" name="end_date" value="{{ $endDate }}" 
                           class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div class="flex items-end">
                    <div class="w-full space-y-2">
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors duration-200 flex items-center justify-center">
                            <i class="fas fa-search mr-2"></i>
                            Generate Report
                        </button>
                        <button type="button" onclick="exportReport()" class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md transition-colors duration-200 flex items-center justify-center">
                            <i class="fas fa-download mr-2"></i>
                            Export CSV
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Note: Branch Filter Removed -->
        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-blue-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-700">
                        <strong>Note:</strong> All reports are automatically filtered for <strong>{{ $branch->branch_name }}</strong> branch only.
                    </p>
                </div>
            </div>
        </div>

        <!-- Report Content -->
        @if(isset($reports[$reportType]))
            @php $currentReport = $reports[$reportType]; @endphp
            
            <!-- Report Header -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">{{ $currentReport['title'] }}</h2>
                        <p class="text-gray-600 mt-2">{{ $currentReport['description'] }}</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <div class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                            {{ $currentReport['data']->count() }} Records
                        </div>
                        <button onclick="printReportClean()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md text-sm flex items-center">
                            <i class="fas fa-print mr-2"></i>
                            Print
                        </button>
                    </div>
                </div>
            </div>

            <!-- Report Table -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                @if($currentReport['data']->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200" id="reportTable">
                            <thead class="bg-gray-50">
                                <tr>
                                    @if($reportType === 'appointments')
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Appointment ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Veterinarian</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    @elseif($reportType === 'pets')
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Species</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Breed</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Age</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gender</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registration Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    @elseif($reportType === 'billing')
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bill ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    @elseif($reportType === 'sales')
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sale Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cashier</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    @elseif($reportType === 'referrals')
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Referral ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Referred By</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Referred To</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    @elseif($reportType === 'equipment')
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Equipment ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Equipment Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    @elseif($reportType === 'services')
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    @elseif($reportType === 'inventory')
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    @elseif($reportType === 'revenue')
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Branch</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period Start</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period End</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Revenue</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Transactions</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($currentReport['data'] as $row)
                                    <tr class="hover:bg-gray-50">
                                        @if($reportType === 'appointments')
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $row->appoint_id }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $row->owner_name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row->owner_contact }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $row->pet_name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ \Carbon\Carbon::parse($row->appointment_date)->format('M d, Y') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ \Carbon\Carbon::parse($row->appointment_time)->format('h:i A') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $row->veterinarian }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    {{ $row->status === 'completed' ? 'bg-green-100 text-green-800' : 
                                                       ($row->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                                    {{ ucfirst($row->status) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex gap-1">
                                                    <button onclick="viewRecordDetails('appointments', '{{ $row->appoint_id }}')" 
                                                            class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs transition-colors duration-200" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button onclick="openDetailedPDF('appointments', '{{ $row->appoint_id }}')" 
                                                            class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs transition-colors duration-200" title="View PDF Report">
                                                        <i class="fas fa-file-pdf"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        @elseif($reportType === 'pets')
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $row->pet_id }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $row->owner_name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row->owner_contact }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $row->pet_name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row->pet_species }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row->pet_breed }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row->pet_age }} years</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $row->pet_gender == 'male' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800' }}">
                                                    {{ ucfirst($row->pet_gender) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ \Carbon\Carbon::parse($row->registration_date)->format('M d, Y') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex gap-1">
                                                    <button onclick="viewRecordDetails('pets', '{{ $row->pet_id }}')" 
                                                            class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs transition-colors duration-200" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button onclick="openDetailedPDF('pets', '{{ $row->pet_id }}')" 
                                                            class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs transition-colors duration-200" title="View PDF Report">
                                                        <i class="fas fa-file-pdf"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        @elseif($reportType === 'billing')
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $row->bill_id }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $row->customer_name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row->pet_name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ \Carbon\Carbon::parse($row->service_date)->format('M d, Y') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">₱{{ number_format($row->pay_total, 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $row->payment_status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                    {{ ucfirst($row->payment_status) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex gap-1">
                                                    <button onclick="viewRecordDetails('billing', '{{ $row->bill_id }}')" 
                                                            class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs transition-colors duration-200" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button onclick="openDetailedPDF('billing', '{{ $row->bill_id }}')" 
                                                            class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs transition-colors duration-200" title="View PDF Report">
                                                        <i class="fas fa-file-pdf"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        @elseif($reportType === 'sales')
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $row->ord_id }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ \Carbon\Carbon::parse($row->sale_date)->format('M d, Y') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $row->customer_name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row->product_name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row->quantity_sold }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">₱{{ number_format($row->unit_price, 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">₱{{ number_format($row->total_amount, 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row->cashier }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex gap-1">
                                                    <button onclick="viewRecordDetails('sales', '{{ $row->ord_id }}')" 
                                                            class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs transition-colors duration-200" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button onclick="openDetailedPDF('sales', '{{ $row->ord_id }}')" 
                                                            class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs transition-colors duration-200" title="View PDF Report">
                                                        <i class="fas fa-file-pdf"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        @elseif($reportType === 'referrals')
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $row->ref_id }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ \Carbon\Carbon::parse($row->ref_date)->format('M d, Y') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $row->owner_name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row->pet_name }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-500">{{ Str::limit($row->referral_reason, 50) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row->referred_by }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row->referred_to }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex gap-1">
                                                    <button onclick="viewRecordDetails('referrals', '{{ $row->ref_id }}')" 
                                                            class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs transition-colors duration-200" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button onclick="openDetailedPDF('referrals', '{{ $row->ref_id }}')" 
                                                            class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs transition-colors duration-200" title="View PDF Report">
                                                        <i class="fas fa-file-pdf"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        @elseif($reportType === 'equipment')
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $row->equipment_id }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $row->equipment_name }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-500">{{ Str::limit($row->equipment_description ?? 'N/A', 50) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row->equipment_quantity }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    {{ $row->stock_status === 'Good Stock' ? 'bg-green-100 text-green-800' : 
                                                       ($row->stock_status === 'Low Stock' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                                    {{ $row->stock_status }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex gap-1">
                                                    <button onclick="viewRecordDetails('equipment', '{{ $row->equipment_id }}')" 
                                                            class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs transition-colors duration-200" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button onclick="openDetailedPDF('equipment', '{{ $row->equipment_id }}')" 
                                                            class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs transition-colors duration-200" title="View PDF Report">
                                                        <i class="fas fa-file-pdf"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        @elseif($reportType === 'services')
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $row->service_id }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $row->service_name }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-500">{{ Str::limit($row->service_description, 50) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">₱{{ number_format($row->service_price, 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    {{ $row->status }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex gap-1">
                                                    <button onclick="viewRecordDetails('services', '{{ $row->service_id }}')" 
                                                            class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs transition-colors duration-200" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button onclick="openDetailedPDF('services', '{{ $row->service_id }}')" 
                                                            class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs transition-colors duration-200" title="View PDF Report">
                                                        <i class="fas fa-file-pdf"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        @elseif($reportType === 'inventory')
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $row->product_id }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $row->product_name }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-500">{{ Str::limit($row->product_description, 50) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row->quantity }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">₱{{ number_format($row->unit_price, 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    {{ $row->stock_status === 'Good Stock' ? 'bg-green-100 text-green-800' : 
                                                       ($row->stock_status === 'Low Stock' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                                    {{ $row->stock_status }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex gap-1">
                                                    <button onclick="viewRecordDetails('inventory', '{{ $row->product_id }}')" 
                                                            class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs transition-colors duration-200" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button onclick="openDetailedPDF('inventory', '{{ $row->product_id }}')" 
                                                            class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs transition-colors duration-200" title="View PDF Report">
                                                        <i class="fas fa-file-pdf"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        @elseif($reportType === 'revenue')
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $row->branch_name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row->period_start }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row->period_end }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-green-600">₱{{ number_format($row->total_revenue, 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row->total_transactions }}</td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-12">
                        <div class="text-gray-400 mb-4">
                            <i class="fas fa-chart-bar text-6xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No Data Available</h3>
                        <p class="text-gray-500">No records found for the selected criteria. Try adjusting your filters.</p>
                    </div>
                @endif
            </div>
        @else
            <!-- Default state -->
            <div class="bg-white rounded-lg shadow-sm p-12 text-center">
                <div class="text-gray-400 mb-4">
                    <i class="fas fa-file-alt text-6xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Select Report Type</h3>
                <p class="text-gray-500">Choose a report type from the dropdown above to view data.</p>
            </div>
        @endif
    </div>
</div>

<!-- Universal Record Details Modal -->
<div id="recordModal" class="fixed inset-0 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <!-- Header with Logo -->
            <div class="header w-full">
                <div class="p-4 rounded-t-lg w-full" style="background-color: #f88e28;">
                    <img src="{{ asset('images/header.jpg') }}" alt="Pets2GO Veterinary Clinic Header" 
                         class="w-full h-auto object-contain" style="max-height: 120px; min-height: 80px;">
                </div>
            </div>
            
            <div class="flex justify-between items-center p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900" id="modalTitle">Record Details</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fa-solid fa-circle-xmark text-2xl"></i>
                </button>
            </div>
            <div class="p-6" id="modalContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<style>
@keyframes fadeSlideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fadeSlideUp {
  animation: fadeSlideUp 0.6s ease-out;
}

@media print {
    body * { visibility: hidden; }
    #reportTable, #reportTable * { visibility: visible; }
    .no-print, button { display: none !important; }
    @page { size: A4 landscape; margin: 15mm; }
}
</style>

<script>
function exportReport() {
    const form = document.querySelector('form');
    const exportForm = document.createElement('form');
    exportForm.method = 'GET';
    exportForm.action = '{{ route("branch-reports.export") }}';
    
    const formData = new FormData(form);
    for (let [key, value] of formData.entries()) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        exportForm.appendChild(input);
    }
    
    document.body.appendChild(exportForm);
    exportForm.submit();
    document.body.removeChild(exportForm);
}

function printReportClean() {
    const reportTitle = document.querySelector('.text-2xl.font-bold')?.textContent || 'Report';
    const reportTable = document.getElementById('reportTable');
    
    if (!reportTable) {
        alert('No report data to print');
        return;
    }
    
    // Clone the table
    const tableClone = reportTable.cloneNode(true);
    
    // Remove action column from header
    const headerCells = tableClone.querySelectorAll('thead th');
    if (headerCells.length > 0 && headerCells[headerCells.length - 1].textContent.includes('Actions')) {
        headerCells[headerCells.length - 1].remove();
    }
    
    // Remove action column from all rows
    const rows = tableClone.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length > 0) {
            cells[cells.length - 1].remove();
        }
    });
    
    // Get current date and time
    const now = new Date();
    const formattedDate = now.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    });
    const formattedTime = now.toLocaleTimeString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit',
        hour12: true 
    });
    const generatedDateTime = `${formattedDate} - ${formattedTime}`;
    
    // Create a new window/tab
    const printWindow = window.open('', '_blank');
    
    // Write the complete HTML document
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>${reportTitle} - ${formattedDate}</title>
            <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
            <style>
                @page { 
                    size: A4 portrait; 
                    margin: 15mm 20mm 25mm 20mm;
                }
                
                * {
                    box-sizing: border-box;
                }
                
                body { 
                    font-family: Arial, sans-serif; 
                    margin: 0; 
                    padding: 0;
                    -webkit-print-color-adjust: exact; 
                    print-color-adjust: exact; 
                    counter-reset: page;
                }
                
                .content-wrapper {
                    padding: 20px;
                    padding-bottom: 30mm;
                }
                
                .header-container { 
                    background-color: #f88e28; 
                    padding: 15px; 
                    margin-bottom: 20px; 
                    border-radius: 8px; 
                }
                
                .header-container img { 
                    max-height: 80px; 
                    width: 100%; 
                    object-fit: contain; 
                }
                
                .report-title { 
                    font-size: 18px; 
                    font-weight: bold; 
                    margin-bottom: 15px; 
                    color: #1f2937; 
                    text-align: center;
                }
                
                table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    font-size: 8pt; 
                }
                
                thead { 
                    background-color: #f3f4f6; 
                }
                
                th { 
                    background-color: #e5e7eb; 
                    padding: 6px 4px; 
                    text-align: left; 
                    font-weight: bold; 
                    border: 1px solid #d1d5db; 
                    font-size: 7pt; 
                    text-transform: uppercase; 
                }
                
                td { 
                    padding: 5px 4px; 
                    border: 1px solid #d1d5db; 
                }
                
                tr:nth-child(even) { 
                    background-color: #f9fafb; 
                }
                
                .bg-green-100 { background-color: #d1fae5 !important; color: #065f46 !important; }
                .bg-yellow-100 { background-color: #fef3c7 !important; color: #92400e !important; }
                .bg-red-100 { background-color: #fee2e2 !important; color: #991b1b !important; }
                .bg-blue-100 { background-color: #dbeafe !important; color: #1e40af !important; }
                .bg-pink-100 { background-color: #fce7f3 !important; color: #9f1239 !important; }
                .text-green-600 { color: #059669 !important; }
                
                tr { 
                    page-break-inside: avoid; 
                }
                
                thead { 
                    display: table-header-group; 
                }

                /* Footer styles with page counter */
                @media print {
                    body {
                        counter-increment: page;
                    }
                    
                    .page-footer {
                        position: fixed;
                        bottom: 0;
                        left: 0;
                        right: 0;
                        height: 20mm;
                        padding: 8px 20mm;
                        border-top: 2px solid #f88e28;
                        font-size: 8pt;
                        color: #6b7280;
                        background-color: white;
                    }

                    .footer-content {
                        display: table;
                        width: 100%;
                        height: 100%;
                    }

                    .footer-row {
                        display: table-row;
                    }

                    .footer-left, .footer-center, .footer-right {
                        display: table-cell;
                        vertical-align: middle;
                    }

                    .footer-left {
                        text-align: left;
                        width: 33%;
                    }

                    .footer-center {
                        text-align: center;
                        width: 34%;
                    }

                    .footer-right {
                        text-align: right;
                        width: 33%;
                    }

                    .page-number::after {
                        content: counter(page);
                    }
                }
                
                @media screen {
                    .page-footer {
                        display: none;
                    }
                }
            </style>
        </head>
        <body>
            <!-- Fixed Footer (only visible in print) -->
            <div class="page-footer">
                <div class="footer-content">
                    <div class="footer-row">
                        <div class="footer-left">
                            <strong>{{ $branch->branch_name }}</strong>
                        </div>
                        <div class="footer-center">
                            Generated: ${generatedDateTime}
                        </div>
                        <div class="footer-right">
                            Page <span class="page-number"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-wrapper">
                <!-- Header -->
                <div class="header-container">
                    <img src="{{ asset('images/header.jpg') }}" alt="Pets2GO Veterinary Clinic Header">
                </div>
                
                <!-- Report Title -->
                <div class="report-title">${reportTitle}</div>
                
                <!-- Table -->
                ${tableClone.outerHTML}
            </div>
            
            <script>
                // Wait for everything to load, then trigger print
                window.onload = function() {
                    setTimeout(function() {
                        window.print();
                    }, 500);
                };
            <\/script>
        </body>
        </html>
    `);
    
    // Close the document to finish loading
    printWindow.document.close();
}

function openDetailedPDF(reportType, recordId) {
    const url = `/branch-reports/${reportType}/${recordId}/pdf`;
    window.open(url, '_blank');
}

function viewRecordDetails(reportType, recordId) {
    console.log('Viewing details for:', reportType, recordId);
    
    fetch(`/branch-reports/${reportType}/${recordId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data) {
                showRecordModal(data, reportType);
            } else {
                alert('Record details not found');
            }
        })
        .catch(error => {
            console.error('Error fetching record details:', error);
            alert('Error loading record details: ' + error.message);
        });
}

function showRecordModal(data, reportType) {
    const modal = document.getElementById('recordModal');
    const title = document.getElementById('modalTitle');
    const content = document.getElementById('modalContent');
    
    const titles = {
        'appointments': 'Appointment Details',
        'pets': 'Pet Medical Record',
        'billing': 'Billing Statement',
        'sales': 'Sales Receipt',
        'equipment': 'Equipment Information',
        'services': 'Service Information',
        'inventory': 'Inventory Record',
        'referrals': 'Referral Details',
        'revenue': 'Revenue Transaction'
    };
    
    title.textContent = titles[reportType] || 'Record Details';
    
    let html = '<div class="space-y-6">';
    
    // Copy the EXACT same switch cases from your report.blade.php file here
    // I'll include the complete code for all report types...
    
    switch(reportType) {
        case 'appointments':
            html += `
                <div class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-500">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Patient & Owner Information</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Owner Name</p>
                            <p class="text-sm font-semibold text-gray-900">${data.own_name || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Contact Number</p>
                            <p class="text-sm font-semibold text-gray-900">${data.own_contactnum || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Pet Name</p>
                            <p class="text-sm font-semibold text-gray-900">${data.pet_name || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Breed/Species</p>
                            <p class="text-sm font-semibold text-gray-900">${data.pet_breed || 'N/A'} - ${data.pet_species || 'N/A'}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-gray-200 p-4 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Appointment Information</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Appointment ID</p>
                            <p class="text-sm font-semibold text-gray-900">${data.appoint_id || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Type</p>
                            <p class="text-sm font-semibold text-gray-900">${data.appoint_type || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Date</p>
                            <p class="text-sm font-semibold text-gray-900">${formatDate(data.appoint_date)}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Time</p>
                            <p class="text-sm font-semibold text-gray-900">${formatTime(data.appoint_time)}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Veterinarian</p>
                            <p class="text-sm font-semibold text-gray-900">${data.user_name || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Status</p>
                            <p class="text-sm"><span class="px-3 py-1 rounded-full ${getStatusClass(data.appoint_status)}">${data.appoint_status || 'N/A'}</span></p>
                        </div>
                    </div>
                </div>

                ${data.appoint_description ? `
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Description/Notes</h4>
                    <p class="text-sm text-gray-700 whitespace-pre-wrap">${data.appoint_description}</p>
                </div>
                ` : ''}
            `;
            break;

        case 'pets':
            html += `
                <div class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-500">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Owner Information</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Owner Name</p>
                            <p class="text-sm font-semibold text-gray-900">${data.own_name || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Contact Number</p>
                            <p class="text-sm font-semibold text-gray-900">${data.own_contactnum || 'N/A'}</p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-xs text-gray-500 uppercase">Address</p>
                            <p class="text-sm font-semibold text-gray-900">${data.own_location || 'N/A'}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-gray-200 p-4 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Pet Profile</h4>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Pet ID</p>
                            <p class="text-sm font-semibold text-gray-900">${data.pet_id || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Pet Name</p>
                            <p class="text-sm font-semibold text-gray-900">${data.pet_name || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Registration Date</p>
                            <p class="text-sm font-semibold text-gray-900">${formatDate(data.pet_registration)}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-purple-50 p-4 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Physical Information</h4>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Species</p>
                            <p class="text-sm font-semibold text-gray-900">${data.pet_species || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Breed</p>
                            <p class="text-sm font-semibold text-gray-900">${data.pet_breed || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Gender</p>
                            <p class="text-sm"><span class="px-3 py-1 rounded-full ${data.pet_gender === 'male' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800'}">${(data.pet_gender || 'N/A').toUpperCase()}</span></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Age</p>
                            <p class="text-sm font-semibold text-gray-900">${data.pet_age || 'N/A'} years</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Date of Birth</p>
                            <p class="text-sm font-semibold text-gray-900">${formatDate(data.pet_birthdate)}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Weight</p>
                            <p class="text-sm font-semibold text-gray-900">${data.pet_weight || 'N/A'} kg</p>
                        </div>
                        ${data.pet_temperature ? `
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Temperature</p>
                            <p class="text-sm font-semibold text-gray-900">${data.pet_temperature} °C</p>
                        </div>
                        ` : ''}
                    </div>
                </div>
            `;
            break;

        case 'billing':
            html += `
                <div class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-500">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Customer Information</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Customer Name</p>
                            <p class="text-sm font-semibold text-gray-900">${data.own_name || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Pet Name</p>
                            <p class="text-sm font-semibold text-gray-900">${data.pet_name || 'N/A'}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-gray-200 p-4 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Bill Information</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Bill ID</p>
                            <p class="text-sm font-semibold text-gray-900">${data.bill_id || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Service Date</p>
                            <p class="text-sm font-semibold text-gray-900">${formatDate(data.appoint_date)}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Billing Date</p>
                            <p class="text-sm font-semibold text-gray-900">${formatDate(data.bill_date)}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-green-50 p-4 rounded-lg border-l-4 border-green-500">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Payment Summary</h4>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Total Amount:</span>
                            <span class="text-xl font-bold text-green-600">₱${formatMoney(data.pay_total)}</span>
                        </div>
                        <div class="flex justify-between items-center pt-2 border-t">
                            <span class="text-sm text-gray-600">Payment Status:</span>
                            <span class="px-3 py-1 rounded-full text-xs ${getStatusClass(data.bill_status)}">${data.bill_status || 'N/A'}</span>
                        </div>
                    </div>
                </div>
            `;
            break;

        case 'sales':
            html += `
                <div class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-500">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Transaction Information</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Order ID</p>
                            <p class="text-sm font-semibold text-gray-900">${data.ord_id || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Sale Date</p>
                            <p class="text-sm font-semibold text-gray-900">${formatDate(data.ord_date)}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Customer Name</p>
                            <p class="text-sm font-semibold text-gray-900">${data.own_name || 'Walk-in Customer'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Cashier</p>
                            <p class="text-sm font-semibold text-gray-900">${data.user_name || 'N/A'}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-gray-200 p-4 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Product Details</h4>
                    <div classspace-y-3">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Product Name</p>
                            <p class="text-lg font-semibold text-gray-900">${data.prod_name || 'N/A'}</p>
                        </div>
                        <div class="grid grid-cols-3 gap-4 pt-2">
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Unit Price</p>
                                <p class="text-sm font-semibold">₱${formatMoney(data.prod_price)}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Quantity</p>
                                <p class="text-sm font-semibold">${data.ord_quantity || 'N/A'}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Total</p>
                                <p class="text-lg font-bold text-green-600">₱${formatMoney(data.ord_total)}</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            break;

        case 'equipment':
            html += `
                <div class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-500">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Equipment Information</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Equipment ID</p>
                            <p class="text-sm font-semibold text-gray-900">${data.equipment_id || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Equipment Name</p>
                            <p class="text-lg font-semibold text-gray-900">${data.equipment_name || 'N/A'}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-gray-200 p-4 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Description</h4>
                    <p class="text-sm text-gray-700 whitespace-pre-wrap">${data.equipment_description || 'No description available'}</p>
                </div>

                <div class="bg-purple-50 p-4 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Stock Information</h4>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Quantity</p>
                            <p class="text-2xl font-bold text-gray-900">${data.equipment_quantity || '0'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Branch</p>
                            <p class="text-sm font-semibold text-gray-900">{{ $branch->branch_name }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Status</p>
                            <p class="text-sm"><span class="px-3 py-1 rounded-full ${getStatusClass(data.stock_status)}">${data.stock_status || 'N/A'}</span></p>
                        </div>
                    </div>
                </div>
            `;
            break;

        case 'services':
            html += `
                <div class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-500">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Service Information</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Service ID</p>
                            <p class="text-sm font-semibold text-gray-900">${data.serv_id || data.service_id || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Service Name</p>
                            <p class="text-lg font-semibold text-gray-900">${data.serv_name || 'N/A'}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-gray-200 p-4 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Details</h4>
                    <div class="space-y-3">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Description</p>
                            <p class="text-sm text-gray-700 whitespace-pre-wrap">${data.serv_description || 'No description available'}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-green-50 p-4 rounded-lg border-l-4 border-green-500">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Pricing & Availability</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Price</p>
                            <p class="text-xl font-bold text-green-600">₱${formatMoney(data.serv_price)}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Status</p>
                            <p class="text-sm"><span class="px-3 py-1 rounded-full bg-green-100 text-green-800">Active</span></p>
                        </div>
                    </div>
                </div>
            `;
            break;

        case 'inventory':
            html += `
                <div class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-500">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Product Information</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Product ID</p>
                            <p class="text-sm font-semibold text-gray-900">${data.prod_id || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Product Name</p>
                            <p class="text-lg font-semibold text-gray-900">${data.prod_name || 'N/A'}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-gray-200 p-4 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Product Details</h4>
                    <div class="space-y-3">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Description</p>
                            <p class="text-sm text-gray-700 whitespace-pre-wrap">${data.prod_description || 'No description available'}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-orange-50 p-4 rounded-lg border-l-4 border-orange-500">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Stock Information</h4>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Current Stock</p>
                            <p class="text-2xl font-bold text-gray-900">${data.prod_quantity || '0'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Branch</p>
                            <p class="text-sm font-semibold text-gray-900">{{ $branch->branch_name }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Status</p>
                            <p class="text-sm"><span class="px-3 py-1 rounded-full ${getStatusClass(data.stock_status)}">${data.stock_status || 'N/A'}</span></p>
                        </div>
                    </div>
                </div>

                <div class="bg-green-50 p-4 rounded-lg border-l-4 border-green-500">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Pricing Information</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Unit Price</p>
                            <p class="text-xl font-bold text-green-600">₱${formatMoney(data.prod_price)}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Total Value</p>
                            <p class="text-sm font-semibold text-gray-900">₱${formatMoney((data.prod_price || 0) * (data.prod_quantity || 0))}</p>
                        </div>
                    </div>
                </div>
            `;
            break;

        case 'referrals':
            html += `
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-800 mb-3 border-b border-gray-300 pb-2">Basic Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Referral ID</dt>
                            <dd class="mt-1 text-sm text-gray-900">${data.ref_id || 'N/A'}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Date</dt>
                            <dd class="mt-1 text-sm text-gray-900">${formatDate(data.ref_date)}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Owner Name</dt>
                            <dd class="mt-1 text-sm text-gray-900">${data.own_name || 'N/A'}</dd>
                        </div>
                    </div>
                </div>

                <div class="bg-blue-50 p-4 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-800 mb-3 border-b border-gray-300 pb-2">Pet Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Pet Name</dt>
                            <dd class="mt-1 text-sm text-gray-900">${data.pet_name || 'N/A'}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Date of Birth</dt>
                            <dd class="mt-1 text-sm text-gray-900">${formatDate(data.pet_birthdate)}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Gender</dt>
                            <dd class="mt-1 text-sm text-gray-900">${data.pet_gender ? data.pet_gender.charAt(0).toUpperCase() + data.pet_gender.slice(1) : 'N/A'}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Species</dt>
                            <dd class="mt-1 text-sm text-gray-900">${data.pet_species || 'N/A'}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Breed</dt>
                            <dd class="mt-1 text-sm text-gray-900">${data.pet_breed || 'N/A'}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Contact Number</dt>
                            <dd class="mt-1 text-sm text-gray-900">${data.own_contactnum || 'N/A'}</dd>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-gray-200 p-4 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-800 mb-3 border-b border-gray-300 pb-2">Medical History</h4>
                    <div class="text-sm text-gray-700 whitespace-pre-wrap">${data.medical_history || 'No medical history provided'}</div>
                </div>

                <div class="bg-white border border-gray-200 p-4 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-800 mb-3 border-b border-gray-300 pb-2">Tests Conducted</h4>
                    <div class="text-sm text-gray-700 whitespace-pre-wrap">${data.tests_conducted || 'No tests documented'}</div>
                </div>

                <div class="bg-white border border-gray-200 p-4 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-800 mb-3 border-b border-gray-300 pb-2">Medications Given</h4>
                    <div class="text-sm text-gray-700 whitespace-pre-wrap">${data.medications_given || 'No medications documented'}</div>
                </div>

                <div class="bg-yellow-50 p-4 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-800 mb-3 border-b border-gray-300 pb-2">Reason for Referral</h4>
                    <div class="text-sm text-gray-700 whitespace-pre-wrap">${data.ref_description || 'No reason provided'}</div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-orange-50 p-4 rounded-lg border-l-4 border-orange-400">
                        <h4 class="text-md font-semibold text-gray-800 mb-3">Referring Veterinarian</h4>
                        <div class="space-y-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">From Branch</dt>
                                <dd class="mt-1 text-sm text-gray-900">${data.ref_by || '{{ $branch->branch_name }}'}</dd>
                            </div>
                        </div>
                    </div>

                    <div class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-500">
                        <h4 class="text-md font-semibold text-gray-800 mb-3">Referred To</h4>
                        <div class="space-y-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Branch/Facility</dt>
                                <dd class="mt-1 text-sm text-gray-900 font-semibold">${data.ref_to || 'N/A'}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Purpose</dt>
                                <dd class="mt-1 text-sm text-gray-900">Specialist Veterinary Care</dd>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            break;

        default:
            html += '<p class="text-gray-500">No details available for this record type.</p>';
    }
    
    html += '</div>';
    content.innerHTML = html;
    modal.classList.remove('hidden');
}

function closeModal() {
    document.getElementById('recordModal').classList.add('hidden');
}

// Helper functions
function formatDate(date) {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function formatTime(time) {
    if (!time) return 'N/A';
    return new Date('1970-01-01T' + time + 'Z').toLocaleTimeString('en-US', {
        timeZone: 'UTC',
        hour12: true,
        hour: 'numeric',
        minute: '2-digit'
    });
}

function formatMoney(amount) {
    if (!amount || isNaN(parseFloat(amount))) return '0.00';
    return parseFloat(amount).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function getStatusClass(status) {
    if (!status) return 'bg-gray-100 text-gray-800';
    const statusLower = status.toString().toLowerCase();
    if (['completed', 'paid', 'active', 'good', 'good stock'].includes(statusLower)) {
        return 'bg-green-100 text-green-800 font-medium';
    } else if (['pending', 'processing'].includes(statusLower)) {
        return 'bg-yellow-100 text-yellow-800 font-medium';
    } else if (['cancelled', 'expired', 'inactive', 'out of stock'].includes(statusLower)) {
        return 'bg-red-100 text-red-800 font-medium';
    } else if (['low stock', 'expiring soon'].includes(statusLower)) {
        return 'bg-orange-100 text-orange-800 font-medium';
    }
    return 'bg-gray-100 text-gray-800 font-medium';
}

// Close modal when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    const recordModal = document.getElementById('recordModal');
    
    if (recordModal) {
        recordModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    }
});

document.getElementById('reportSelect').addEventListener('change', function() {
    this.form.submit();
});
</script>
@endsection