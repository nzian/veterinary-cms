@extends('AdminBoard')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto p-6">
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <form method="GET" id="reportForm" class="space-y-4">
            <!-- First Row: Report Type and Dates -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Report Type</label>
                    <select name="report" id="reportSelect" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" onchange="toggleFilters()">
                        @php
                            $reportOptions = [
                                'visits' => 'Visit Management',
                                'pets' => 'Pet Registration',
                                'billing' => 'Financial Billing',
                                'sales' => 'Product Sales',
                                'services' => 'Service Availability',
                                'inventory' => 'Inventory Status',
                                'revenue' => 'Revenue Analysis',
                                'referrals' => 'Referral Report', 
                                'equipment' => 'Equipment Inventory'
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
            </div>
            
        </form>

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

        @if(isset($reports[$reportType]))
            @php $currentReport = $reports[$reportType]; @endphp
            
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">{{ $currentReport['title'] }}</h2>
                        <p class="text-gray-600 mt-2">{{ $currentReport['description'] }}</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <!-- Filter Dropdown Before Record Count -->
                        <form method="GET" class="inline">
                            <input type="hidden" name="report" value="{{ $reportType }}">
                            <input type="hidden" name="start_date" value="{{ $startDate }}">
                            <input type="hidden" name="end_date" value="{{ $endDate }}">
                            
                            @if($reportType === 'visits')
                                <div class="flex items-center space-x-2">
                                    <label class="text-sm font-medium text-gray-700">Visit Status:</label>
                                    <select name="visit_status" class="border border-gray-300 rounded-md px-3 py-1 text-sm" onchange="this.form.submit()">
                                        <option value="">All Statuses</option>
                                        <option value="pending" {{ request('visit_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="in_progress" {{ request('visit_status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                        <option value="completed" {{ request('visit_status') === 'completed' ? 'selected' : '' }}>Completed</option>
                                        <option value="cancelled" {{ request('visit_status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                    </select>
                                </div>
                            @elseif($reportType === 'pets')
                                <div class="flex items-center space-x-2">
                                    <label class="text-sm font-medium text-gray-700">Species:</label>
                                    <select name="pet_species" class="border border-gray-300 rounded-md px-3 py-1 text-sm" onchange="this.form.submit()">
                                        <option value="">All Species</option>
                                        <option value="Dog" {{ request('pet_species') === 'Dog' ? 'selected' : '' }}>Dog</option>
                                        <option value="Cat" {{ request('pet_species') === 'Cat' ? 'selected' : '' }}>Cat</option>
                                        <option value="Bird" {{ request('pet_species') === 'Bird' ? 'selected' : '' }}>Bird</option>
                                        <option value="Rabbit" {{ request('pet_species') === 'Rabbit' ? 'selected' : '' }}>Rabbit</option>
                                        <option value="Fish" {{ request('pet_species') === 'Fish' ? 'selected' : '' }}>Fish</option>
                                        <option value="Other" {{ request('pet_species') === 'Other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                </div>
                            @elseif($reportType === 'billing')
                                <div class="flex items-center space-x-2">
                                    <label class="text-sm font-medium text-gray-700">Billing Status:</label>
                                    <select name="billing_status" class="border border-gray-300 rounded-md px-3 py-1 text-sm" onchange="this.form.submit()">
                                        <option value="">All Statuses</option>
                                        <option value="pending" {{ request('billing_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="paid" {{ request('billing_status') === 'paid' ? 'selected' : '' }}>Paid</option>
                                        <option value="partially_paid" {{ request('billing_status') === 'partially_paid' ? 'selected' : '' }}>Partially Paid</option>
                                        <option value="overdue" {{ request('billing_status') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                                        <option value="cancelled" {{ request('billing_status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                    </select>
                                </div>
                            @elseif($reportType === 'services')
                                <div class="flex items-center space-x-2">
                                    <label class="text-sm font-medium text-gray-700">Service Category:</label>
                                    <select name="service_category" class="border border-gray-300 rounded-md px-3 py-1 text-sm" onchange="this.form.submit()">
                                        <option value="">All Categories</option>
                                        <option value="Vaccination" {{ request('service_category') === 'Vaccination' ? 'selected' : '' }}>Vaccination</option>
                                        <option value="Deworming" {{ request('service_category') === 'Deworming' ? 'selected' : '' }}>Deworming</option>
                                        <option value="Grooming" {{ request('service_category') === 'Grooming' ? 'selected' : '' }}>Grooming</option>
                                        <option value="Emergency" {{ request('service_category') === 'Emergency' ? 'selected' : '' }}>Emergency</option>
                                        <option value="Check-up" {{ request('service_category') === 'Check-up' ? 'selected' : '' }}>Check-up</option>
                                        <option value="Diagnostics" {{ request('service_category') === 'Diagnostics' ? 'selected' : '' }}>Diagnostics</option>
                                        <option value="Surgical" {{ request('service_category') === 'Surgical' ? 'selected' : '' }}>Surgical</option>
                                        <option value="Boarding" {{ request('service_category') === 'Boarding' ? 'selected' : '' }}>Boarding</option>
                                    </select>
                                </div>
                            @elseif(in_array($reportType, ['inventory', 'sales']))
                                <div class="flex items-center space-x-2">
                                    <label class="text-sm font-medium text-gray-700">Product Type:</label>
                                    <select name="product_type" class="border border-gray-300 rounded-md px-3 py-1 text-sm" onchange="this.form.submit()">
                                        <option value="">All Types</option>
                                        <option value="Consumable" {{ request('product_type') === 'Consumable' ? 'selected' : '' }}>Consumable</option>
                                        <option value="Sale" {{ request('product_type') === 'Sale' ? 'selected' : '' }}>Sale</option>
                                    </select>
                                </div>
                            @elseif($reportType === 'referrals')
                                <div class="flex items-center space-x-2">
                                    <label class="text-sm font-medium text-gray-700">Referral Status:</label>
                                    <select name="referral_status" class="border border-gray-300 rounded-md px-3 py-1 text-sm" onchange="this.form.submit()">
                                        <option value="">All Statuses</option>
                                        <option value="pending" {{ request('referral_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="attended" {{ request('referral_status') === 'attended' ? 'selected' : '' }}>Attended</option>
                                        <option value="completed" {{ request('referral_status') === 'completed' ? 'selected' : '' }}>Completed</option>
                                        <option value="cancelled" {{ request('referral_status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                    </select>
                                </div>
                            @elseif($reportType === 'equipment')
                                <div class="flex items-center space-x-2">
                                    <label class="text-sm font-medium text-gray-700">Equipment Category:</label>
                                    <select name="equipment_category" class="border border-gray-300 rounded-md px-3 py-1 text-sm" onchange="this.form.submit()">
                                        <option value="">All Categories</option>
                                        <option value="Diagnostic Equipment" {{ request('equipment_category') === 'Diagnostic Equipment' ? 'selected' : '' }}>Diagnostic Equipment</option>
                                        <option value="Surgical Equipment" {{ request('equipment_category') === 'Surgical Equipment' ? 'selected' : '' }}>Surgical Equipment</option>
                                        <option value="Monitoring Equipment" {{ request('equipment_category') === 'Monitoring Equipment' ? 'selected' : '' }}>Monitoring Equipment</option>
                                        <option value="Treatment & Therapy Equipment" {{ request('equipment_category') === 'Treatment & Therapy Equipment' ? 'selected' : '' }}>Treatment & Therapy Equipment</option>
                                        <option value="Laboratory Equipment" {{ request('equipment_category') === 'Laboratory Equipment' ? 'selected' : '' }}>Laboratory Equipment</option>
                                        <option value="Grooming & Handling Equipment" {{ request('equipment_category') === 'Grooming & Handling Equipment' ? 'selected' : '' }}>Grooming & Handling Equipment</option>
                                    </select>
                                </div>
                            @endif
                        </form>

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

            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                @if($currentReport['data']->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200" id="reportTable">
                            <thead class="bg-gray-50">
                                <tr>
                                    @if($reportType === 'visits')
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Visit Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Services</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    @elseif($reportType === 'pets')
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registration Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Species</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Breed</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Age</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gender</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    @elseif($reportType === 'billing')
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet Owner</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    @elseif($reportType === 'sales')
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sale Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cashier</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    @elseif($reportType === 'referrals')
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Referred By</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Referred To</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    @elseif($reportType === 'equipment')
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Equipment Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total In Use</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Maintenance</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Available</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Out of Service</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    @elseif($reportType === 'services')
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    @elseif($reportType === 'inventory')
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Pull Out</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Damage</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Stocks</th>
                                        @if(request('product_type') === 'sales')
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                                        @endif
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    @elseif($reportType === 'revenue')
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Branch</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period Start</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period End</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Sales</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Billings</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Revenue</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Transactions</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($currentReport['data'] as $row)
                                    <tr class="hover:bg-gray-50">
                                        @if($reportType === 'visits')
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $loop->iteration }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ \Carbon\Carbon::parse($row->visit_date)->format('M d, Y') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $row->owner_name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row->owner_contact }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $row->pet_name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                    {{ is_string($row->patient_type) ? ucfirst($row->patient_type) : ($row->patient_type->value ?? 'N/A') }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500" style="max-width: 200px; white-space: normal;">
                                                {{ $row->services ?: 'No services' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    {{ $row->status === 'completed' ? 'bg-green-100 text-green-800' : 
                                                        ($row->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                                    {{ ucfirst($row->status) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex gap-1">
                                                    <button onclick="openDetailedPDF('visits', '{{ $row->visit_id }}')" 
                                                            class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs transition-colors duration-200" title="View PDF Report">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        @elseif($reportType === 'pets')
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $loop->iteration }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ \Carbon\Carbon::parse($row->registration_date)->format('M d, Y') }}</td>
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
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex gap-1">
                                                    <button onclick="openDetailedPDF('pets', '{{ $row->pet_id }}')" 
                                                             class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs transition-colors duration-200" title="View PDF Report">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        @elseif($reportType === 'billing')
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $loop->iteration }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ \Carbon\Carbon::parse($row->service_date)->format('M d, Y') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $row->customer_name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row->pet_name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">₱{{ number_format($row->pay_total, 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $row->payment_status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                    {{ ucfirst($row->payment_status) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex gap-1">
                                                    <button onclick="openDetailedPDF('billing', '{{ $row->bill_id }}')" 
                                                             class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs transition-colors duration-200" title="View PDF Report">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        @elseif($reportType === 'sales')
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $loop->iteration }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ \Carbon\Carbon::parse($row->sale_date)->format('M d, Y') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $row->customer_name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row->product_name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row->quantity_sold }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">₱{{ number_format($row->unit_price, 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">₱{{ number_format($row->total_amount, 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row->cashier }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex gap-1">
                                                    <button onclick="openDetailedPDF('sales', '{{ $row->ord_id }}')" 
                                                             class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs transition-colors duration-200" title="View PDF Report">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        @elseif($reportType === 'referrals')
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $loop->iteration }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ \Carbon\Carbon::parse($row->ref_date)->format('M d, Y') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $row->owner_name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row->pet_name }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-500">{{ Str::limit($row->referral_reason, 50) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row->referred_by }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row->referred_to }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex gap-1">
                                                    <button onclick="openDetailedPDF('referrals', '{{ $row->ref_id }}')" 
                                                             class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs transition-colors duration-200" title="View PDF Report">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        @elseif($reportType === 'equipment')
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $loop->iteration }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $row->equipment_name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row->equipment_category ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-500">{{ Str::limit($row->equipment_description ?? 'N/A', 50) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row->total_in_use }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row->total_maintenance }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row->total_available }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row->total_out_of_service }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex gap-1">
                                                    <button onclick="openDetailedPDF('equipment', '{{ $row->equipment_id }}')" 
                                                             class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs transition-colors duration-200" title="View PDF Report">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        @elseif($reportType === 'services')
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $loop->iteration }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $row->service_name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $row->service_type }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-500">{{ Str::limit($row->service_description, 50) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">₱{{ number_format($row->service_price, 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    {{ $row->status }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex gap-1">
                                                    <button onclick="openDetailedPDF('services', '{{ $row->service_id }}')"
                                                            class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs transition-colors duration-200" title="View PDF Report">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        @elseif($reportType === 'inventory')
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $loop->iteration }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $row->product_name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $row->product_type }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-500">{{ Str::limit($row->product_description, 50) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ number_format($row->total_pull_out) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ number_format($row->total_damage) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-blue-600">{{ number_format($row->total_stocks) }}</td>
                                            @if(request('product_type') === 'sales' && $row->unit_price !== null)
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">₱{{ number_format($row->unit_price, 2) }}</td>
                                            @elseif(request('product_type') === 'sales')
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">-</td>
                                            @endif
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    {{ $row->stock_status === 'Good Stock' ? 'bg-green-100 text-green-800' : 
                                                        ($row->stock_status === 'Low Stock' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                                    {{ $row->stock_status }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex gap-1">
                                                    <button onclick="openDetailedPDF('inventory', '{{ $row->product_id }}')"
                                                             class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs transition-colors duration-200" title="View PDF Report">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        @elseif($reportType === 'revenue')
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $row->branch_name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row->period_start }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row->period_end }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-blue-600">₱{{ number_format($row->total_sales, 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-yellow-600">₱{{ number_format($row->total_billings, 2) }}</td>
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

<style>
@media print {
    /* Retaining original print styles for table view printing */
    body * { visibility: hidden; }
    #reportTable, #reportTable * { visibility: visible; }
    .no-print, button { display: none !important; }
    @page { size: A4 landscape; margin: 15mm; }
}
</style>

<script>
function exportReport() {
    const form = document.getElementById('reportForm');
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
    // This function remains to handle the direct browser table print (with table-only layout)
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
                <div class="header-container">
                    <img src="{{ asset('images/header.jpg') }}" alt="Pets2GO Veterinary Clinic Header">
                </div>
                
                <div class="report-title">${reportTitle}</div>
                
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

// REMOVED: viewRecordDetails function

document.getElementById('reportSelect').addEventListener('change', function() {
    this.form.submit();
});
</script>
@endsection