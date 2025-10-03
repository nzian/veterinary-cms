@extends('AdminBoard')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto p-6">

        <!-- Filter Section -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Report Type</label>
                    <select name="report" id="reportSelect" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @php
                            $reportOptions = [
                            'appointments' => 'Appointment Management',
                            'pets' => 'Pet Registration',
                            'billing' => 'Financial Billing',
                            'sales' => 'Product Sales',
                            'medical' => 'Medical History',
                            'services' => 'Service Availability',
                            'staff' => 'Staff Assignment',
                            'inventory' => 'Inventory Status',
                            'revenue' => 'Revenue Analysis',
                            'branch_performance' => 'Branch Performance',
                            'prescriptions' => 'Prescription Report', 
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

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Branch</label>
                    <select name="branch" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Branches</option>
                        @foreach($branches as $branchOption)
                            <option value="{{ $branchOption->branch_id }}" {{ $branch == $branchOption->branch_id ? 'selected' : '' }}>
                                {{ $branchOption->branch_name }}
                            </option>
                        @endforeach
                    </select>
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
                       <!-- Replace the existing print button with this -->
<button onclick="printReportClean()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md text-sm flex items-center">
    <i class="fas fa-print mr-2"></i>
    Print
</button>
                    </div>
                </div>
            </div>

            <!-- Success/Error Messages -->
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
                </div>
            @endif

            <!-- Report Table -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                @if($currentReport['data']->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200" id="reportTable">
                            <thead class="bg-gray-50">
                                <tr>
                                    @if($reportType == 'appointments')
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet Breed</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Branch</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Veterinarian</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    @elseif($reportType == 'pets')
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Species & Breed</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Age</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gender</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registration</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    @elseif($reportType == 'billing')
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bill ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Branch</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    @elseif($reportType == 'sales')
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sale Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cashier</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    @elseif($reportType == 'referrals')
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Referral ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Referral Reason</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Referred By</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Referred To</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    @elseif($reportType == 'equipment')
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Equipment ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Equipment Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Branch</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    @else
                                        @foreach($currentReport['data']->first() as $key => $value)
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                {{ ucwords(str_replace('_', ' ', $key)) }}
                                            </th>
                                        @endforeach
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($currentReport['data'] as $index => $record)
                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                        @if($reportType == 'appointments')
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $record->appoint_id }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->owner_name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->owner_contact }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->pet_name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->pet_breed }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->branch_name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <div>{{ \Carbon\Carbon::parse($record->appointment_date)->format('M d, Y') }}</div>
                                                <div class="text-gray-500 text-xs">{{ \Carbon\Carbon::parse($record->appointment_time)->format('h:i A') }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->veterinarian }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @php
                                                    $statusClass = match(strtolower($record->status)) {
                                                        'completed' => 'bg-green-100 text-green-800',
                                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                                        'cancelled' => 'bg-red-100 text-red-800',
                                                        default => 'bg-gray-100 text-gray-800'
                                                    };
                                                @endphp
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                                                    {{ ucfirst($record->status) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <button onclick="viewRecordDetails('appointments', '{{ $record->appoint_id }}')" 
                                                        class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs transition-colors duration-200" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        @elseif($reportType == 'pets')
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $record->pet_id }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->owner_name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->owner_contact }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->pet_name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->pet_species }} - {{ $record->pet_breed }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->pet_age }} years</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $record->pet_gender == 'male' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800' }}">
                                                    {{ ucfirst($record->pet_gender) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ \Carbon\Carbon::parse($record->registration_date)->format('M d, Y') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <button onclick="viewRecordDetails('pets', '{{ $record->pet_id }}')" 
                                                        class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs transition-colors duration-200" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        @elseif($reportType == 'billing')
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $record->bill_id }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->customer_name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->pet_name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ \Carbon\Carbon::parse($record->service_date)->format('M d, Y') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">₱{{ number_format($record->bill_amount ?? 0, 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->branch_name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @php
                                                    $statusClass = match(strtolower($record->payment_status)) {
                                                        'paid' => 'bg-green-100 text-green-800',
                                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                                        default => 'bg-gray-100 text-gray-800'
                                                    };
                                                @endphp
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                                                    {{ ucfirst($record->payment_status) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <button onclick="viewRecordDetails('billing', '{{ $record->bill_id }}')" 
                                                        class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs transition-colors duration-200" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        @elseif($reportType == 'sales')
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $record->ord_id }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ \Carbon\Carbon::parse($record->sale_date)->format('M d, Y') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->customer_name ?? 'Walk-in' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->product_name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($record->quantity_sold, 0) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">₱{{ number_format($record->unit_price, 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">₱{{ number_format($record->total_amount, 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->cashier }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <button onclick="viewRecordDetails('sales', '{{ $record->ord_id }}')" 
                                                        class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs transition-colors duration-200" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        @elseif($reportType == 'referrals')
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $record->ref_id }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ \Carbon\Carbon::parse($record->ref_date)->format('M d, Y') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->owner_name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->pet_name }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-900 max-w-xs truncate" title="{{ $record->referral_reason }}">{{ Str::limit($record->referral_reason, 50) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->referred_by }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->referred_to }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <button onclick="viewRecordDetails('referrals', '{{ $record->ref_id }}')" 
                                                        class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs transition-colors duration-200" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        @elseif($reportType == 'equipment')
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $record->equipment_id }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->equipment_name }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-900 max-w-xs truncate" title="{{ $record->equipment_description ?? 'N/A' }}">{{ Str::limit($record->equipment_description ?? 'N/A', 50) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->equipment_quantity }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->branch_name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @php
                                                    $statusClass = match(strtolower($record->stock_status)) {
                                                        'good stock' => 'bg-green-100 text-green-800',
                                                        'low stock' => 'bg-yellow-100 text-yellow-800',
                                                        'out of stock' => 'bg-red-100 text-red-800',
                                                        default => 'bg-gray-100 text-gray-800'
                                                    };
                                                @endphp
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                                                    {{ $record->stock_status }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <button onclick="viewRecordDetails('equipment', '{{ $record->equipment_id }}')" 
                                                        class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs transition-colors duration-200" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        @else
                                            @foreach($record as $key => $value)
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                    @if(is_numeric($value) && $key !== 'id' && !str_contains($key, '_id'))
                                                        @if(str_contains($key, 'price') || str_contains($key, 'amount') || str_contains($key, 'total') || str_contains($key, 'revenue'))
                                                            <span class="text-green-600 font-semibold">₱{{ number_format($value, 2) }}</span>
                                                        @else
                                                            <span class="text-gray-900 font-medium">{{ number_format($value) }}</span>
                                                        @endif
                                                    @elseif(str_contains($key, 'date'))
                                                        <span class="text-gray-700">{{ \Carbon\Carbon::parse($value)->format('M d, Y') }}</span>
                                                    @elseif(str_contains($key, 'status'))
                                                        @php
                                                            $statusClass = match(strtolower($value)) {
                                                                'completed', 'paid', 'active', 'good' => 'bg-green-100 text-green-800',
                                                                'pending', 'processing' => 'bg-yellow-100 text-yellow-800',
                                                                'cancelled', 'expired', 'inactive' => 'bg-red-100 text-red-800',
                                                                'low stock', 'expiring soon' => 'bg-orange-100 text-orange-800',
                                                                default => 'bg-gray-100 text-gray-800'
                                                            };
                                                        @endphp
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                                                            {{ ucfirst($value) }}
                                                        </span>
                                                    @else
                                                        <span class="text-gray-900">{{ $value }}</span>
                                                    @endif
                                                </td>
                                            @endforeach
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <button onclick="viewRecordDetails('{{ $reportType }}', '{{ $record->{array_keys((array)$record)[0]} }}')" 
                                                        class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs transition-colors duration-200" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Table Footer -->
                    <div class="bg-gray-50 px-6 py-3 border-t border-gray-200">
                        <div class="text-sm text-gray-700">
                            Showing {{ $currentReport['data']->count() }} records
                            @if($startDate && $endDate)
                                from {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
                            @endif
                        </div>
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
            <!-- Default state when no report type is selected -->
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
<!-- Universal Record Details Modal (including Referrals) -->
<div id="recordModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <!-- Header Section with Full Width Orange Background Container -->
            <div class="header w-full">
                <div class="p-4 rounded-t-lg w-full" style="background-color: #f88e28;">
                    <img src="{{ asset('images/header.jpg') }}" alt="Pets2GO Veterinary Clinic Header" class="w-full h-auto object-contain" style="max-height: 120px; min-height: 80px;">
                </div>
            </div>
            
            <div class="flex justify-between items-center p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900" id="modalTitle">Record Details</h3>
            </div>
            <div class="p-6" id="modalContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>
<style>
/* Enhanced Print Styles */
@media print {
    /* Hide everything by default */
    body * {
        visibility: hidden;
    }
    
    /* Show only the report table and its contents */
    #reportTable, 
    #reportTable *, 
    .print-header,
    .print-header * {
        visibility: visible;
    }
    
    /* Hide action buttons and controls */
    .no-print,
    button,
    .bg-blue-500,
    .bg-green-600,
    .bg-purple-600,
    td:last-child,
    th:last-child {
        display: none !important;
        visibility: hidden !important;
    }
    
    /* Landscape orientation */
    @page {
        size: A4 landscape;
        margin: 15mm;
    }
    
    /* Position table at top */
    #reportTable {
        position: absolute;
        left: 0;
        top: 100px;
        width: 100%;
    }
    
    /* Header styling */
    .print-header {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        background-color: #f88e28 !important;
        padding: 10px;
        margin: 0;
        width: 100%;
        z-index: 1000;
    }
    
    .print-header img {
        max-height: 80px;
        width: 100%;
        object-fit: contain;
    }
    
    /* Table styling for print */
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 10pt;
    }
    
    thead {
        background-color: #f3f4f6 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    th, td {
        border: 1px solid #d1d5db;
        padding: 8px 6px;
        text-align: left;
    }
    
    th {
        font-weight: bold;
        background-color: #e5e7eb !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    /* Status badges */
    .bg-green-100, .bg-yellow-100, .bg-red-100, .bg-blue-100, .bg-pink-100, .bg-orange-100, .bg-gray-100 {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    /* Remove shadows and transitions */
    * {
        box-shadow: none !important;
        transition: none !important;
    }
    
    /* Break page if needed */
    tr {
        page-break-inside: avoid;
    }
    
    thead {
        display: table-header-group;
    }
    
    tfoot {
        display: table-footer-group;
    }
}

/* Regular screen styles */
.print-header {
    display: none;
}

@media screen {
    .print-header {
        display: none !important;
    }
}
</style>


<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
 function printReport() {
    // Store original title
    const originalTitle = document.title;
    
    // Get report type name
    const reportTitle = document.querySelector('.text-2xl.font-bold')?.textContent || 'Report';
    
    // Set document title for print
    document.title = reportTitle + ' - ' + new Date().toLocaleDateString();
    
    // Trigger print
    window.print();
    
    // Restore original title after print dialog closes
    setTimeout(() => {
        document.title = originalTitle;
    }, 1000);
}

// Alternative: Create a clean print window
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
    if (headerCells.length > 0) {
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
    
    // Create print window
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>${reportTitle} - ${new Date().toLocaleDateString()}</title>
            <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
            <style>
                @page {
                    size: A4 landscape;
                    margin: 15mm;
                }
                
                body {
                    font-family: Arial, sans-serif;
                    margin: 0;
                    padding: 20px;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
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
                    font-size: 20px;
                    font-weight: bold;
                    margin-bottom: 15px;
                    color: #1f2937;
                }
                
                table {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 9pt;
                }
                
                thead {
                    background-color: #f3f4f6;
                }
                
                th {
                    background-color: #e5e7eb;
                    padding: 8px 6px;
                    text-align: left;
                    font-weight: bold;
                    border: 1px solid #d1d5db;
                    font-size: 8pt;
                    text-transform: uppercase;
                }
                
                td {
                    padding: 6px 6px;
                    border: 1px solid #d1d5db;
                }
                
                tr:nth-child(even) {
                    background-color: #f9fafb;
                }
                
                /* Status badges */
                .bg-green-100 { background-color: #d1fae5 !important; color: #065f46 !important; }
                .bg-yellow-100 { background-color: #fef3c7 !important; color: #92400e !important; }
                .bg-red-100 { background-color: #fee2e2 !important; color: #991b1b !important; }
                .bg-blue-100 { background-color: #dbeafe !important; color: #1e40af !important; }
                .bg-pink-100 { background-color: #fce7f3 !important; color: #9f1239 !important; }
                .bg-orange-100 { background-color: #ffedd5 !important; color: #9a3412 !important; }
                .bg-gray-100 { background-color: #f3f4f6 !important; color: #374151 !important; }
                
                .text-green-600 { color: #059669 !important; }
                
                tr {
                    page-break-inside: avoid;
                }
                
                thead {
                    display: table-header-group;
                }
                
                @media print {
                    body {
                        margin: 0;
                        padding: 10mm;
                    }
                }
            </style>
        </head>
        <body>
            <div class="header-container">
                <img src="{{ asset('images/header.jpg') }}" alt="Pets2GO Veterinary Clinic Header">
            </div>
            
            <div class="report-title">${reportTitle}</div>
            
            ${tableClone.outerHTML}
            
            <script>
                window.onload = function() {
                    setTimeout(() => {
                        window.print();
                    }, 500);
                };
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}
    
function exportReport() {
    const form = document.querySelector('form');
    const exportForm = document.createElement('form');
    exportForm.method = 'GET';
    exportForm.action = '{{ route("reports.export") }}';
    
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

function printReport() {
    window.print();
}

function viewRecordDetails(reportType, recordId) {
    console.log('Viewing details for:', reportType, recordId);
    
    fetch(`/reports/${reportType}/${recordId}`)
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
        'medical': 'Medical History Report',
        'services': 'Service Information',
        'staff': 'Staff Profile',
        'inventory': 'Inventory Record',
        'revenue': 'Revenue Transaction',
        'branch_performance': 'Branch Performance Metrics',
        'prescriptions': 'Prescription Details',
        'equipment': 'Equipment Information'
    };
    
    title.textContent = titles[reportType] || 'Record Details';
    
    let html = '<div class="space-y-6">';
    
    // FORMAT BASED ON REPORT TYPE
    switch(reportType) {
        case 'appointments':
            html += `
                <!-- Patient & Owner Information -->
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

                <!-- Appointment Details -->
                <div class="bg-white border border-gray-200 p-4 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Appointment Information</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Appointment ID</p>
                            <p class="text-sm font-semibold text-gray-900">${data.appoint_id || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Type</p>
                            <p class="text-sm font-semibold text-gray-900">${data.appoint_type || data.appointment_type || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Date</p>
                            <p class="text-sm font-semibold text-gray-900">${formatDate(data.appoint_date || data.appointment_date)}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Time</p>
                            <p class="text-sm font-semibold text-gray-900">${formatTime(data.appoint_time || data.appointment_time)}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Branch</p>
                            <p class="text-sm font-semibold text-gray-900">${data.branch_name || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Veterinarian</p>
                            <p class="text-sm font-semibold text-gray-900">${data.user_name || data.veterinarian || 'N/A'}</p>
                        </div>
                    </div>
                </div>

                <!-- Status & Notes -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Status & Notes</h4>
                    <div class="space-y-3">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Status</p>
                            <p class="text-sm"><span class="px-3 py-1 rounded-full ${getStatusClass(data.appoint_status || data.status)}">${data.appoint_status || data.status || 'N/A'}</span></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Description/Notes</p>
                            <p class="text-sm text-gray-700 whitespace-pre-wrap">${data.appoint_description || data.description || 'No notes provided'}</p>
                        </div>
                    </div>
                </div>

                ${data.bill_amount ? `
                <div class="bg-green-50 p-4 rounded-lg border-l-4 border-green-500">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Billing Information</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Total Amount</p>
                            <p class="text-lg font-bold text-green-600">₱${formatMoney(data.pay_total || data.bill_amount)}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Payment Status</p>
                            <p class="text-sm"><span class="px-3 py-1 rounded-full ${getStatusClass(data.bill_status)}">${data.bill_status || 'N/A'}</span></p>
                        </div>
                    </div>
                </div>
                ` : ''}
            `;
            break;

        case 'pets':
            html += `
                <!-- Owner Information -->
                <div class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-500">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Owner Information</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Owner Name</p>
                            <p class="text-sm font-semibold text-gray-900">${data.own_name || data.owner_name || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Contact Number</p>
                            <p class="text-sm font-semibold text-gray-900">${data.own_contactnum || data.owner_contact || 'N/A'}</p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-xs text-gray-500 uppercase">Address</p>
                            <p class="text-sm font-semibold text-gray-900">${data.own_location || data.owner_location || 'N/A'}</p>
                        </div>
                    </div>
                </div>

                <!-- Pet Profile -->
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
                            <p class="text-sm font-semibold text-gray-900">${formatDate(data.pet_registration || data.registration_date)}</p>
                        </div>
                    </div>
                </div>

                <!-- Physical Information -->
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
                            <p class="text-sm"><span class="px-3 py-1 rounded-full ${data.pet_gender === 'male' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800'}">${data.pet_gender ? data.pet_gender.toUpperCase() : 'N/A'}</span></p>
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
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Temperature</p>
                            <p class="text-sm font-semibold text-gray-900">${data.pet_temperature || 'N/A'} °C</p>
                        </div>
                    </div>
                </div>
            `;
            break;

        case 'billing':
            html += `
                <!-- Customer Information -->
                <div class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-500">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Customer Information</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Customer Name</p>
                            <p class="text-sm font-semibold text-gray-900">${data.customer_name || data.own_name || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Pet Name</p>
                            <p class="text-sm font-semibold text-gray-900">${data.pet_name || 'N/A'}</p>
                        </div>
                    </div>
                </div>

                <!-- Bill Details -->
                <div class="bg-white border border-gray-200 p-4 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Bill Information</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Bill ID</p>
                            <p class="text-sm font-semibold text-gray-900">${data.bill_id || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Service Type</p>
                            <p class="text-sm font-semibold text-gray-900">${data.service_type || data.appoint_type || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Service Date</p>
                            <p class="text-sm font-semibold text-gray-900">${formatDate(data.service_date)}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Billing Date</p>
                            <p class="text-sm font-semibold text-gray-900">${formatDate(data.bill_date || data.billing_date)}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Branch</p>
                            <p class="text-sm font-semibold text-gray-900">${data.branch_name || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Handled By</p>
                            <p class="text-sm font-semibold text-gray-900">${data.handled_by || data.user_name || 'N/A'}</p>
                        </div>
                    </div>
                </div>

                <!-- Payment Summary -->
                <div class="bg-green-50 p-4 rounded-lg border-l-4 border-green-500">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Payment Summary</h4>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Total Amount:</span>
                            <span class="text-xl font-bold text-green-600">₱${formatMoney(data.bill_amount || data.pay_total)}</span>
                        </div>
                        ${data.pay_cashAmount ? `
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-600">Cash Received:</span>
                            <span class="font-semibold">₱${formatMoney(data.pay_cashAmount || data.cash_received)}</span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-600">Change:</span>
                            <span class="font-semibold">₱${formatMoney(data.pay_change || data.change_given)}</span>
                        </div>
                        ` : ''}
                        <div class="flex justify-between items-center pt-2 border-t">
                            <span class="text-sm text-gray-600">Payment Status:</span>
                            <span class="px-3 py-1 rounded-full text-xs ${getStatusClass(data.payment_status || data.bill_status)}">${data.payment_status || data.bill_status || 'N/A'}</span>
                        </div>
                    </div>
                </div>
            `;
            break;

        case 'sales':
            html += `
                <!-- Transaction Information -->
                <div class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-500">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Transaction Information</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Order ID</p>
                            <p class="text-sm font-semibold text-gray-900">${data.ord_id || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Sale Date</p>
                            <p class="text-sm font-semibold text-gray-900">${formatDate(data.sale_date || data.ord_date)}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Customer Name</p>
                            <p class="text-sm font-semibold text-gray-900">${data.customer_name || data.own_name || 'Walk-in Customer'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Cashier</p>
                            <p class="text-sm font-semibold text-gray-900">${data.cashier || data.user_name || 'N/A'}</p>
                        </div>
                    </div>
                </div>

                <!-- Product Details -->
                <div class="bg-white border border-gray-200 p-4 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Product Details</h4>
                    <div class="space-y-3">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Product Name</p>
                            <p class="text-lg font-semibold text-gray-900">${data.product_name || data.prod_name || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Category</p>
                            <p class="text-sm text-gray-700">${data.category || data.prod_category || 'N/A'}</p>
                        </div>
                        <div class="grid grid-cols-3 gap-4 pt-2">
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Unit Price</p>
                                <p class="text-sm font-semibold">₱${formatMoney(data.unit_price || data.prod_price)}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Quantity</p>
                                <p class="text-sm font-semibold">${data.quantity_sold || data.ord_quantity || 'N/A'}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Total</p>
                                <p class="text-lg font-bold text-green-600">₱${formatMoney(data.total_amount || data.ord_total)}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Branch Information -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Branch</h4>
                    <p class="text-sm font-semibold text-gray-900">${data.branch_name || 'N/A'}</p>
                </div>
            `;
            break;

        case 'equipment':
            html += `
                <!-- Equipment Information -->
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

                <!-- Description & Details -->
                <div class="bg-white border border-gray-200 p-4 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Description</h4>
                    <p class="text-sm text-gray-700 whitespace-pre-wrap">${data.equipment_description || 'No description available'}</p>
                </div>

                <!-- Stock Information -->
                <div class="bg-purple-50 p-4 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Stock Information</h4>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Quantity</p>
                            <p class="text-2xl font-bold text-gray-900">${data.equipment_quantity || '0'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Branch</p>
                            <p class="text-sm font-semibold text-gray-900">${data.branch_name || 'All Branches'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Status</p>
                            <p class="text-sm"><span class="px-3 py-1 rounded-full ${getStatusClass(data.stock_status)}">${data.stock_status || 'N/A'}</span></p>
                        </div>
                    </div>
                </div>
            `;
            break;
             case 'medical':
            html += `
                <!-- Patient Information -->
                <div class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-500">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Patient Information</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Record ID</p>
                            <p class="text-sm font-semibold text-gray-900">${data.medical_id || data.id || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Pet Name</p>
                            <p class="text-sm font-semibold text-gray-900">${data.pet_name || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Owner Name</p>
                            <p class="text-sm font-semibold text-gray-900">${data.owner_name || data.own_name || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Contact Number</p>
                            <p class="text-sm font-semibold text-gray-900">${data.owner_contact || data.own_contactnum || 'N/A'}</p>
                        </div>
                    </div>
                </div>

                <!-- Visit Information -->
                <div class="bg-white border border-gray-200 p-4 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Visit Information</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Visit Date</p>
                            <p class="text-sm font-semibold text-gray-900">${formatDate(data.visit_date || data.medical_date)}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Veterinarian</p>
                            <p class="text-sm font-semibold text-gray-900">${data.veterinarian || data.user_name || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Branch</p>
                            <p class="text-sm font-semibold text-gray-900">${data.branch_name || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Visit Type</p>
                            <p class="text-sm font-semibold text-gray-900">${data.visit_type || data.medical_type || 'N/A'}</p>
                        </div>
                    </div>
                </div>

                <!-- Medical Details -->
                <div class="bg-purple-50 p-4 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Medical Details</h4>
                    <div class="space-y-3">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Diagnosis</p>
                            <p class="text-sm text-gray-700 whitespace-pre-wrap">${data.diagnosis || 'No diagnosis recorded'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Treatment</p>
                            <p class="text-sm text-gray-700 whitespace-pre-wrap">${data.treatment || 'No treatment recorded'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Notes</p>
                            <p class="text-sm text-gray-700 whitespace-pre-wrap">${data.notes || data.medical_notes || 'No additional notes'}</p>
                        </div>
                    </div>
                </div>

                <!-- Vital Signs -->
                ${data.weight || data.temperature ? `
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Vital Signs</h4>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        ${data.weight ? `
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Weight</p>
                            <p class="text-sm font-semibold text-gray-900">${data.weight} kg</p>
                        </div>
                        ` : ''}
                        ${data.temperature ? `
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Temperature</p>
                            <p class="text-sm font-semibold text-gray-900">${data.temperature} °C</p>
                        </div>
                        ` : ''}
                        ${data.heart_rate ? `
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Heart Rate</p>
                            <p class="text-sm font-semibold text-gray-900">${data.heart_rate} bpm</p>
                        </div>
                        ` : ''}
                        ${data.respiratory_rate ? `
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Respiratory Rate</p>
                            <p class="text-sm font-semibold text-gray-900">${data.respiratory_rate} /min</p>
                        </div>
                        ` : ''}
                    </div>
                </div>
                ` : ''}
            `;
            break;

        case 'services':
            html += `
                <!-- Service Information -->
                <div class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-500">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Service Information</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Service ID</p>
                            <p class="text-sm font-semibold text-gray-900">${data.service_id || data.id || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Service Name</p>
                            <p class="text-lg font-semibold text-gray-900">${data.service_name || 'N/A'}</p>
                        </div>
                    </div>
                </div>

                <!-- Service Details -->
                <div class="bg-white border border-gray-200 p-4 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Details</h4>
                    <div class="space-y-3">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Description</p>
                            <p class="text-sm text-gray-700 whitespace-pre-wrap">${data.service_description || data.description || 'No description available'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Category</p>
                            <p class="text-sm font-semibold text-gray-900">${data.service_category || data.category || 'N/A'}</p>
                        </div>
                    </div>
                </div>

                <!-- Pricing & Availability -->
                <div class="bg-green-50 p-4 rounded-lg border-l-4 border-green-500">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Pricing & Availability</h4>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Price</p>
                            <p class="text-xl font-bold text-green-600">₱${formatMoney(data.service_price || data.price)}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Duration</p>
                            <p class="text-sm font-semibold text-gray-900">${data.duration || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Status</p>
                            <p class="text-sm"><span class="px-3 py-1 rounded-full ${getStatusClass(data.status || data.availability)}">${data.status || data.availability || 'N/A'}</span></p>
                        </div>
                    </div>
                </div>

                <!-- Branch Information -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Branch Availability</h4>
                    <p class="text-sm font-semibold text-gray-900">${data.branch_name || 'All Branches'}</p>
                </div>
            `;
            break;
            case 'staff':
            html += `
                <!-- Staff Information -->
                <div class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-500">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Staff Information</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Staff ID</p>
                            <p class="text-sm font-semibold text-gray-900">${data.staff_id || data.user_id || data.id || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Full Name</p>
                            <p class="text-lg font-semibold text-gray-900">${data.staff_name || data.user_name || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Position</p>
                            <p class="text-sm font-semibold text-gray-900">${data.position || data.user_type || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Contact Number</p>
                            <p class="text-sm font-semibold text-gray-900">${data.contact_number || data.user_contact || 'N/A'}</p>
                        </div>
                    </div>
                </div>

                <!-- Employment Details -->
                <div class="bg-white border border-gray-200 p-4 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Employment Details</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Branch Assignment</p>
                            <p class="text-sm font-semibold text-gray-900">${data.branch_name || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Hire Date</p>
                            <p class="text-sm font-semibold text-gray-900">${formatDate(data.hire_date || data.date_hired)}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Department</p>
                            <p class="text-sm font-semibold text-gray-900">${data.department || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Employment Status</p>
                            <p class="text-sm"><span class="px-3 py-1 rounded-full ${getStatusClass(data.employment_status || data.status)}">${data.employment_status || data.status || 'N/A'}</span></p>
                        </div>
                    </div>
                </div>

                <!-- Credentials -->
                ${data.license_number || data.specialization ? `
                <div class="bg-purple-50 p-4 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Professional Credentials</h4>
                    <div class="grid grid-cols-2 gap-4">
                        ${data.license_number ? `
                        <div>
                            <p class="text-xs text-gray-500 uppercase">License Number</p>
                            <p class="text-sm font-semibold text-gray-900">${data.license_number}</p>
                        </div>
                        ` : ''}
                        ${data.specialization ? `
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Specialization</p>
                            <p class="text-sm font-semibold text-gray-900">${data.specialization}</p>
                        </div>
                        ` : ''}
                    </div>
                </div>
                ` : ''}

                <!-- Email & Username -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Account Information</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Email</p>
                            <p class="text-sm text-gray-900">${data.email || data.user_email || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Username</p>
                            <p class="text-sm text-gray-900">${data.username || data.user_username || 'N/A'}</p>
                        </div>
                    </div>
                </div>
            `;
            break;

        case 'inventory':
            html += `
                <!-- Product Information -->
                <div class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-500">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Product Information</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Product ID</p>
                            <p class="text-sm font-semibold text-gray-900">${data.product_id || data.prod_id || data.id || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Product Name</p>
                            <p class="text-lg font-semibold text-gray-900">${data.product_name || data.prod_name || 'N/A'}</p>
                        </div>
                    </div>
                </div>

                <!-- Product Details -->
                <div class="bg-white border border-gray-200 p-4 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Product Details</h4>
                    <div class="space-y-3">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Description</p>
                            <p class="text-sm text-gray-700 whitespace-pre-wrap">${data.product_description || data.prod_description || 'No description available'}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Category</p>
                                <p class="text-sm font-semibold text-gray-900">${data.category || data.prod_category || 'N/A'}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Brand</p>
                                <p class="text-sm font-semibold text-gray-900">${data.brand || data.prod_brand || 'N/A'}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stock Information -->
                <div class="bg-orange-50 p-4 rounded-lg border-l-4 border-orange-500">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Stock Information</h4>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Current Stock</p>
                            <p class="text-2xl font-bold text-gray-900">${data.quantity || data.stock_quantity || data.prod_quantity || '0'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Reorder Level</p>
                            <p class="text-sm font-semibold text-gray-900">${data.reorder_level || data.min_stock || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Branch</p>
                            <p class="text-sm font-semibold text-gray-900">${data.branch_name || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Status</p>
                            <p class="text-sm"><span class="px-3 py-1 rounded-full ${getStatusClass(data.stock_status || data.status)}">${data.stock_status || data.status || 'N/A'}</span></p>
                        </div>
                    </div>
                </div>

                <!-- Pricing Information -->
                <div class="bg-green-50 p-4 rounded-lg border-l-4 border-green-500">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Pricing Information</h4>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Unit Price</p>
                            <p class="text-xl font-bold text-green-600">₱${formatMoney(data.unit_price || data.prod_price)}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Cost Price</p>
                            <p class="text-sm font-semibold text-gray-900">₱${formatMoney(data.cost_price || data.prod_cost)}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Total Value</p>
                            <p class="text-sm font-semibold text-gray-900">₱${formatMoney((data.unit_price || data.prod_price || 0) * (data.quantity || data.prod_quantity || 0))}</p>
                        </div>
                    </div>
                </div>

                <!-- Additional Information -->
                ${data.expiry_date || data.supplier ? `
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Additional Information</h4>
                    <div class="grid grid-cols-2 gap-4">
                        ${data.expiry_date ? `
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Expiry Date</p>
                            <p class="text-sm font-semibold text-gray-900">${formatDate(data.expiry_date)}</p>
                        </div>
                        ` : ''}
                        ${data.supplier ? `
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Supplier</p>
                            <p class="text-sm font-semibold text-gray-900">${data.supplier}</p>
                        </div>
                        ` : ''}
                    </div>
                </div>
                ` : ''}
            `;
            break;

        case 'revenue':
            html += `
                <!-- Transaction Information -->
                <div class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-500">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Transaction Information</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Transaction ID</p>
                            <p class="text-sm font-semibold text-gray-900">${data.transaction_id || data.revenue_id || data.id || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Transaction Date</p>
                            <p class="text-sm font-semibold text-gray-900">${formatDate(data.transaction_date || data.revenue_date)}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Transaction Type</p>
                            <p class="text-sm font-semibold text-gray-900">${data.transaction_type || data.revenue_type || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Branch</p>
                            <p class="text-sm font-semibold text-gray-900">${data.branch_name || 'N/A'}</p>
                        </div>
                    </div>
                </div>

                <!-- Revenue Details -->
                <div class="bg-green-50 p-4 rounded-lg border-l-4 border-green-500">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Revenue Details</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Gross Revenue</p>
                            <p class="text-2xl font-bold text-green-600">₱${formatMoney(data.gross_revenue || data.revenue_amount || data.amount)}</p>
                        </div>
                        ${data.expenses || data.deductions ? `
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Expenses/Deductions</p>
                            <p class="text-lg font-semibold text-red-600">₱${formatMoney(data.expenses || data.deductions)}</p>
                        </div>
                        ` : ''}
                        ${data.net_revenue ? `<div class="col-span-2">
                            <p class="text-xs text-gray-500 uppercase">Net Revenue</p>
                            <p class="text-2xl font-bold text-blue-600">₱${formatMoney(data.net_revenue)}</p>
                        </div>
                        ` : ''}
                    </div>
                </div>

                <!-- Customer Information -->
                ${data.customer_name || data.customer_id ? `
                <div class="bg-white border border-gray-200 p-4 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Customer Information</h4>
                    <div class="grid grid-cols-2 gap-4">
                        ${data.customer_name ? `
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Customer Name</p>
                            <p class="text-sm font-semibold text-gray-900">${data.customer_name}</p>
                        </div>
                        ` : ''}
                        ${data.customer_id ? `
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Customer ID</p>
                            <p class="text-sm font-semibold text-gray-900">${data.customer_id}</p>
                        </div>
                        ` : ''}
                    </div>
                </div>
                ` : ''}

                <!-- Payment Method -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Payment Information</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Payment Method</p>
                            <p class="text-sm font-semibold text-gray-900">${data.payment_method || 'Cash'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Status</p>
                            <p class="text-sm"><span class="px-3 py-1 rounded-full ${getStatusClass(data.payment_status || data.status)}">${data.payment_status || data.status || 'N/A'}</span></p>
                        </div>
                    </div>
                </div>

                <!-- Additional Notes -->
                ${data.notes || data.description ? `
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Notes</h4>
                    <p class="text-sm text-gray-700 whitespace-pre-wrap">${data.notes || data.description}</p>
                </div>
                ` : ''}
            `;
            break;

          case 'branch_performance':
    html += `
        <!-- Branch Information -->
        <div class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-500">
            <h4 class="text-md font-semibold text-gray-800 mb-3">Branch Information</h4>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-gray-500 uppercase">Branch ID</p>
                    <p class="text-sm font-semibold text-gray-900">${data.branch_id || data.id || 'N/A'}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase">Branch Name</p>
                    <p class="text-lg font-semibold text-gray-900">${data.branch_name || 'N/A'}</p>
                </div>
                <div class="col-span-2">
                    <p class="text-xs text-gray-500 uppercase">Location</p>
                    <p class="text-sm text-gray-900">${data.branch_location || data.location || data.branch_address || 'N/A'}</p>
                </div>
            </div>
        </div>

        <!-- Performance Metrics -->
        <div class="bg-green-50 p-4 rounded-lg border-l-4 border-green-500">
            <h4 class="text-md font-semibold text-gray-800 mb-3">Performance Metrics</h4>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div>
                    <p class="text-xs text-gray-500 uppercase">Total Revenue</p>
                    <p class="text-xl font-bold text-green-600">₱${formatMoney(data.total_revenue || data.revenue || data.total_sales || 0)}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase">Total Appointments</p>
                    <p class="text-xl font-bold text-blue-600">${data.total_appointments || data.appointments || data.appointment_count || '0'}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase">Total Sales</p>
                    <p class="text-xl font-bold text-purple-600">${data.total_sales || data.sales || data.sales_count || '0'}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase">Customer Count</p>
                    <p class="text-xl font-bold text-orange-600">${data.customer_count || data.customers || data.total_customers || '0'}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase">Staff Count</p>
                    <p class="text-xl font-bold text-indigo-600">${data.staff_count || data.staff || data.total_staff || '0'}</p>
                </div>
                ${data.rating || data.average_rating ? `
                <div>
                    <p class="text-xs text-gray-500 uppercase">Rating</p>
                    <p class="text-xl font-bold text-yellow-600">${data.rating || data.average_rating} ⭐</p>
                </div>
                ` : ''}
            </div>
        </div>

        <!-- All Available Data (Debug View) -->
        <div class="bg-white border border-gray-200 p-4 rounded-lg">
            <h4 class="text-md font-semibold text-gray-800 mb-3">Complete Performance Data</h4>
            <div class="grid grid-cols-2 gap-3 text-sm">
                ${Object.entries(data).map(([key, value]) => {
                    if (key === 'branch_id' || key === 'branch_name') return ''; // Skip already shown fields
                    
                    const label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    let displayValue = value;
                    
                    if (key.includes('date') && value) {
                        displayValue = formatDate(value);
                    } else if (key.includes('time') && value) {
                        displayValue = formatTime(value);
                    } else if ((key.includes('price') || key.includes('amount') || key.includes('total') || 
                               key.includes('revenue') || key.includes('sales')) && 
                               !isNaN(parseFloat(value))) {
                        displayValue = '₱' + formatMoney(value);
                    } else if (key.includes('status')) {
                        displayValue = `<span class="px-2 py-1 rounded-full text-xs ${getStatusClass(value)}">${value}</span>`;
                    }
                    
                    return `
                        <div class="bg-gray-50 p-2 rounded">
                            <p class="text-xs text-gray-500 uppercase">${label}</p>
                            <p class="text-sm font-semibold text-gray-900">${displayValue || 'N/A'}</p>
                        </div>
                    `;
                }).join('')}
            </div>
        </div>

        <!-- Period Information -->
        ${data.period_start || data.period_end || data.start_date || data.end_date ? `
        <div class="bg-gray-50 p-4 rounded-lg">
            <h4 class="text-md font-semibold text-gray-800 mb-3">Reporting Period</h4>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-gray-500 uppercase">Period Start</p>
                    <p class="text-sm font-semibold text-gray-900">${formatDate(data.period_start || data.start_date) || 'N/A'}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase">Period End</p>
                    <p class="text-sm font-semibold text-gray-900">${formatDate(data.period_end || data.end_date) || 'N/A'}</p>
                </div>
            </div>
        </div>
        ` : ''}

        <!-- Growth Metrics -->
        ${data.growth_rate || data.month_over_month || data.performance_percentage ? `
        <div class="bg-purple-50 p-4 rounded-lg">
            <h4 class="text-md font-semibold text-gray-800 mb-3">Growth Analysis</h4>
            <div class="grid grid-cols-2 gap-4">
                ${data.growth_rate || data.performance_percentage ? `
                <div>
                    <p class="text-xs text-gray-500 uppercase">Growth Rate</p>
                    <p class="text-lg font-bold ${parseFloat(data.growth_rate || data.performance_percentage) >= 0 ? 'text-green-600' : 'text-red-600'}">${data.growth_rate || data.performance_percentage}%</p>
                </div>
                ` : ''}
                ${data.month_over_month ? `
                <div>
                    <p class="text-xs text-gray-500 uppercase">Month Over Month</p>
                    <p class="text-lg font-bold ${parseFloat(data.month_over_month) >= 0 ? 'text-green-600' : 'text-red-600'}">${data.month_over_month}%</p>
                </div>
                ` : ''}
            </div>
        </div>
        ` : ''}

        <!-- Status & Notes -->
        ${data.status || data.performance_status || data.notes || data.remarks ? `
        <div class="bg-gray-50 p-4 rounded-lg">
            <h4 class="text-md font-semibold text-gray-800 mb-3">Additional Information</h4>
            <div class="space-y-3">
                ${data.status || data.performance_status ? `
                <div>
                    <p class="text-xs text-gray-500 uppercase">Performance Status</p>
                    <p class="text-sm"><span class="px-3 py-1 rounded-full ${getStatusClass(data.performance_status || data.status)}">${data.performance_status || data.status}</span></p>
                </div>
                ` : ''}
                ${data.notes || data.remarks ? `
                <div>
                    <p class="text-xs text-gray-500 uppercase">Notes</p>
                    <p class="text-sm text-gray-700 whitespace-pre-wrap">${data.notes || data.remarks}</p>
                </div>
                ` : ''}
            </div>
        </div>
        ` : ''}
    `;
    break;

        case 'prescriptions':
            html += `
                <!-- Patient Information -->
                <div class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-500">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Patient Information</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Prescription ID</p>
                            <p class="text-sm font-semibold text-gray-900">${data.prescription_id || data.presc_id || data.id || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Pet Name</p>
                            <p class="text-sm font-semibold text-gray-900">${data.pet_name || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Owner Name</p>
                            <p class="text-sm font-semibold text-gray-900">${data.owner_name || data.own_name || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Contact Number</p>
                            <p class="text-sm font-semibold text-gray-900">${data.owner_contact || data.own_contactnum || 'N/A'}</p>
                        </div>
                    </div>
                </div>

                <!-- Prescription Details -->
                <div class="bg-white border border-gray-200 p-4 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Prescription Information</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Date Issued</p>
                            <p class="text-sm font-semibold text-gray-900">${formatDate(data.prescription_date || data.presc_date || data.date_issued)}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Veterinarian</p>
                            <p class="text-sm font-semibold text-gray-900">${data.veterinarian || data.user_name || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Branch</p>
                            <p class="text-sm font-semibold text-gray-900">${data.branch_name || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Valid Until</p>
                            <p class="text-sm font-semibold text-gray-900">${formatDate(data.valid_until || data.expiry_date) || 'N/A'}</p>
                        </div>
                    </div>
                </div>

                <!-- Medication Details -->
                <div class="bg-purple-50 p-4 rounded-lg border-l-4 border-purple-500">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Medication Details</h4>
                    <div class="space-y-3">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Medication Name</p>
                            <p class="text-lg font-semibold text-gray-900">${data.medication_name || data.medicine_name || 'N/A'}</p>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Dosage</p>
                                <p class="text-sm font-semibold text-gray-900">${data.dosage || data.dose || 'N/A'}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Frequency</p>
                                <p class="text-sm font-semibold text-gray-900">${data.frequency || 'N/A'}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase">Duration</p>
                                <p class="text-sm font-semibold text-gray-900">${data.duration || 'N/A'}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Instructions -->
                <div class="bg-yellow-50 p-4 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Instructions</h4>
                    <p class="text-sm text-gray-700 whitespace-pre-wrap">${data.instructions || data.prescription_notes || 'No special instructions'}</p>
                </div>

                <!-- Diagnosis -->
                ${data.diagnosis || data.condition ? `
                <div class="bg-white border border-gray-200 p-4 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Diagnosis</h4>
                    <p class="text-sm text-gray-700 whitespace-pre-wrap">${data.diagnosis || data.condition}</p>
                </div>
                ` : ''}

                <!-- Status & Refills -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Status & Refills</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Status</p>
                            <p class="text-sm"><span class="px-3 py-1 rounded-full ${getStatusClass(data.prescription_status || data.status)}">${data.prescription_status || data.status || 'N/A'}</span></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Refills Remaining</p>
                            <p class="text-sm font-semibold text-gray-900">${data.refills_remaining || data.refills || '0'}</p>
                        </div>
                    </div>
                </div>
            `;
            break;

           case 'referrals':
    html += `
        <!-- Basic Information -->
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

        <!-- Pet Information -->
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

        <!-- Medical History -->
        <div class="bg-white border border-gray-200 p-4 rounded-lg">
            <h4 class="text-md font-semibold text-gray-800 mb-3 border-b border-gray-300 pb-2">Medical History</h4>
            <div class="text-sm text-gray-700 whitespace-pre-wrap">${data.medical_history || 'No medical history provided'}</div>
        </div>

        <!-- Tests Conducted -->
        <div class="bg-white border border-gray-200 p-4 rounded-lg">
            <h4 class="text-md font-semibold text-gray-800 mb-3 border-b border-gray-300 pb-2">Tests Conducted</h4>
            <div class="text-sm text-gray-700 whitespace-pre-wrap">${data.tests_conducted || 'No tests documented'}</div>
        </div>

        <!-- Medications Given -->
        <div class="bg-white border border-gray-200 p-4 rounded-lg">
            <h4 class="text-md font-semibold text-gray-800 mb-3 border-b border-gray-300 pb-2">Medications Given</h4>
            <div class="text-sm text-gray-700 whitespace-pre-wrap">${data.medications_given || 'No medications documented'}</div>
        </div>

        <!-- Reason for Referral -->
        <div class="bg-yellow-50 p-4 rounded-lg">
            <h4 class="text-md font-semibold text-gray-800 mb-3 border-b border-gray-300 pb-2">Reason for Referral</h4>
            <div class="text-sm text-gray-700 whitespace-pre-wrap">${data.ref_description || 'No reason provided'}</div>
        </div>

        <!-- Referral Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-orange-50 p-4 rounded-lg border-l-4 border-orange-400">
                <h4 class="text-md font-semibold text-gray-800 mb-3">Referring Veterinarian</h4>
                <div class="space-y-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Veterinarian</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-semibold">${data.referring_vet_name || data.user_name || 'N/A'}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">License No.</dt>
                        <dd class="mt-1 text-sm text-gray-900">${data.referring_vet_license || data.user_license || 'N/A'}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">From Branch</dt>
                        <dd class="mt-1 text-sm text-gray-900">${data.branch_name || data.referring_branch || 'N/A'}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Contact</dt>
                        <dd class="mt-1 text-sm text-gray-900">${data.referring_vet_contact || data.user_contact || 'N/A'}</dd>
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
                    <div>
                        <dt class="text-sm font-medium text-gray-500">For</dt>
                        <dd class="mt-1 text-sm text-gray-900">Specialized treatment and consultation</dd>
                    </div>
                </div>
            </div>
        </div>
    `;
    break;
            

          default:
            // Generic fallback for other report types
            html += '<div class="space-y-3">';
            for (const [key, value] of Object.entries(data)) {
                if (value !== null && value !== '' && !key.includes('_id') && key !== 'id') {
                    const label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    let displayValue = value;
                    
                    if (key.includes('date') && value) {
                        displayValue = formatDate(value);
                    } else if (key.includes('time') && value) {
                        displayValue = formatTime(value);
                    } else if ((key.includes('price') || key.includes('amount') || key.includes('total') || 
                               key.includes('revenue') || key.includes('cash') || key.includes('change')) && 
                               !isNaN(parseFloat(value))) {
                        displayValue = '₱' + formatMoney(value);
                    } else if (key.includes('status')) {
                        displayValue = `<span class="px-3 py-1 rounded-full text-xs ${getStatusClass(value)}">${value}</span>`;
                    }
                    
                    html += `
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-xs text-gray-500 uppercase mb-1">${label}</p>
                            <p class="text-sm text-gray-900">${displayValue || 'N/A'}</p>
                        </div>
                    `;
                }
            }
            html += '</div>';
    }
    
    html += '</div>';
    content.innerHTML = html;
    modal.classList.remove('hidden');
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

function showReferralModal(data) {
    const modal = document.getElementById('referralModal');
    const content = document.getElementById('referralModalContent');
    
    const formatDate = (date) => {
        if (!date) return 'N/A';
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };
    
    let html = `
        <div class="space-y-6">
            <!-- Header Section with Full Width Orange Background Container -->
            <div class="header mb-4 w-full -mx-6 -mt-6">
                <div class="p-4 rounded-t-lg w-full" style="background-color: #f88e28;">
                    <img src="{{ asset('images/header.jpg') }}" alt="Pets2GO Veterinary Clinic Header" class="w-full h-auto object-contain" style="max-height: 120px; min-height: 80px;">
                </div>
            </div>
            <!-- Basic Information -->
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

            <!-- Pet Information -->
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

            <!-- Medical History -->
            <div class="bg-white border border-gray-200 p-4 rounded-lg">
                <h4 class="text-md font-semibold text-gray-800 mb-3 border-b border-gray-300 pb-2">Medical History</h4>
                <div class="text-sm text-gray-700 whitespace-pre-wrap">${data.medical_history || 'No medical history provided'}</div>
            </div>

            <!-- Tests Conducted -->
            <div class="bg-white border border-gray-200 p-4 rounded-lg">
                <h4 class="text-md font-semibold text-gray-800 mb-3 border-b border-gray-300 pb-2">Tests Conducted</h4>
                <div class="text-sm text-gray-700 whitespace-pre-wrap">${data.tests_conducted || 'No tests documented'}</div>
            </div>

            <!-- Medications Given -->
            <div class="bg-white border border-gray-200 p-4 rounded-lg">
                <h4 class="text-md font-semibold text-gray-800 mb-3 border-b border-gray-300 pb-2">Medications Given</h4>
                <div class="text-sm text-gray-700 whitespace-pre-wrap">${data.medications_given || 'No medications documented'}</div>
            </div>

            <!-- Reason for Referral -->
            <div class="bg-yellow-50 p-4 rounded-lg">
                <h4 class="text-md font-semibold text-gray-800 mb-3 border-b border-gray-300 pb-2">Reason for Referral</h4>
                <div class="text-sm text-gray-700 whitespace-pre-wrap">${data.ref_description || 'No reason provided'}</div>
            </div>

            <!-- Referral Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-orange-50 p-4 rounded-lg border-l-4 border-orange-400">
                    <h4 class="text-md font-semibold text-gray-800 mb-3">Referring Veterinarian</h4>
                    <div class="space-y-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Veterinarian</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-semibold">DR. JAN JERICK M. GO</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">License No.</dt>
                            <dd class="mt-1 text-sm text-gray-900">0012045</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">From Branch</dt>
                            <dd class="mt-1 text-sm text-gray-900">${data.ref_by || 'PETS 2GO VETERINARY CLINIC'}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Contact</dt>
                            <dd class="mt-1 text-sm text-gray-900">0906-765-9732</dd>
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
                        <div>
                            <dt class="text-sm font-medium text-gray-500">For</dt>
                            <dd class="mt-1 text-sm text-gray-900">Specialized treatment and consultation</dd>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    content.innerHTML = html;
    modal.classList.remove('hidden');
}

function closeModal() {
    document.getElementById('recordModal').classList.add('hidden');
}

function closeReferralModal() {
    document.getElementById('referralModal').classList.add('hidden');
}

// Close modals when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    const recordModal = document.getElementById('recordModal');
    const referralModal = document.getElementById('referralModal');
    
    if (recordModal) {
        recordModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    }
    
    if (referralModal) {
        referralModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeReferralModal();
            }
        });
    }
});

// Auto-submit form when report type changes
document.getElementById('reportSelect').addEventListener('change', function() {
    this.form.submit();
});
function addModalActions() {
    const modalTitle = document.getElementById('modalTitle');
    if (modalTitle && !document.getElementById('modalActionButtons')) {
        const actionsDiv = document.createElement('div');
        actionsDiv.id = 'modalActionButtons';
        actionsDiv.className = 'flex gap-2';
        actionsDiv.innerHTML = `
            <button onclick="printModalContent()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md text-sm flex items-center no-print">
                <i class="fas fa-print "></i>
            </button>
            <button onclick="downloadModalPDF()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm flex items-center no-print">
                <i class="fas fa-file-pdf "></i>
            </button>
            <button onclick="openModalInNewTab()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm flex items-center no-print">
                <i class="fas fa-external-link-alt "></i>
            </button>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                  <i class="fa-solid fa-circle-xmark"></i>
                </button>


        `;
        modalTitle.parentElement.appendChild(actionsDiv);
    }
}

// Print modal content
function printModalContent() {
    const modalContent = document.getElementById('modalContent').cloneNode(true);
    const modalTitle = document.getElementById('modalTitle').textContent;
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>${modalTitle}</title>
            <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
            <style>
                @media print {
                    body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
                }
                body { font-family: Arial, sans-serif; padding: 20px; }
                .no-print { display: none !important; }
            </style>
        </head>
        <body>
            <!-- Header -->
            <div class="mb-6" style="background-color: #f88e28; padding: 16px; border-radius: 8px;">
                <img src="{{ asset('images/header.jpg') }}" alt="Pets2GO Veterinary Clinic Header" style="width: 100%; max-height: 120px; object-fit: contain;">
            </div>
            <h1 class="text-2xl font-bold mb-6">${modalTitle}</h1>
            ${modalContent.innerHTML}
        </body>
        </html>
    `);
    printWindow.document.close();
    
    // Wait for images to load before printing
    printWindow.onload = function() {
        setTimeout(() => {
            printWindow.print();
        }, 250);
    };
}

// Download modal as PDF
function downloadModalPDF() {
    const modalContent = document.getElementById('modalContent').cloneNode(true);
    const modalTitle = document.getElementById('modalTitle').textContent;
    
    // Create a temporary container
    const container = document.createElement('div');
    container.style.padding = '20px';
    container.innerHTML = `
        <div style="background-color: #f88e28; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
            <img src="{{ asset('images/header.jpg') }}" alt="Pets2GO Veterinary Clinic Header" style="width: 100%; max-height: 120px; object-fit: contain;">
        </div>
        <h1 style="font-size: 24px; font-weight: bold; margin-bottom: 20px;">${modalTitle}</h1>
        ${modalContent.innerHTML}
    `;
    
    const opt = {
        margin: 10,
        filename: `${modalTitle.replace(/\s+/g, '_')}_${Date.now()}.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true, letterRendering: true },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    
    html2pdf().set(opt).from(container).save();
}

// Open modal content in new tab
function openModalInNewTab() {
    const modalContent = document.getElementById('modalContent').cloneNode(true);
    const modalTitle = document.getElementById('modalTitle').textContent;
    
    const newTab = window.open('', '_blank');
    newTab.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>${modalTitle}</title>
            <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; background-color: #f9fafb; }
                .container { max-width: 1200px; margin: 0 auto; }
                @media print {
                    body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
                    .no-print { display: none !important; }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <!-- Header -->
                <div class="mb-6" style="background-color: #f88e28; padding: 16px; border-radius: 8px;">
                    <img src="{{ asset('images/header.jpg') }}" alt="Pets2GO Veterinary Clinic Header" style="width: 100%; max-height: 120px; object-fit: contain;">
                </div>
                
                <!-- Action Buttons -->
                <div class="mb-6 flex gap-2 no-print">
                    <button onclick="window.print()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md text-sm flex items-center">
                        <i class="fas fa-print mr-2"></i>
                        Print
                    </button>
                    <button onclick="downloadPDF()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm flex items-center">
                        <i class="fas fa-file-pdf mr-2"></i>
                        Download PDF
                    </button>
                </div>
                
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h1 class="text-2xl font-bold mb-6">${modalTitle}</h1>
                    ${modalContent.innerHTML}
                </div>
            </div>
            
            <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"><\/script>
            <script>
                function downloadPDF() {
                    const element = document.querySelector('.container');
                    const opt = {
                        margin: 10,
                        filename: '${modalTitle.replace(/\s+/g, '_')}_${Date.now()}.pdf',
                        image: { type: 'jpeg', quality: 0.98 },
                        html2canvas: { scale: 2, useCORS: true, letterRendering: true },
                        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
                    };
                    html2pdf().set(opt).from(element).save();
                }
            <\/script>
        </body>
        </html>
    `);
    newTab.document.close();
}

// Update the existing showRecordModal function to add action buttons
const originalShowRecordModal = showRecordModal;
showRecordModal = function(data, reportType) {
    originalShowRecordModal(data, reportType);
    setTimeout(addModalActions, 100);
};

// Update the existing showReferralModal function to add action buttons
const originalShowReferralModal = showReferralModal;
showReferralModal = function(data) {
    originalShowReferralModal(data);
    setTimeout(addModalActions, 100);
};
</script>

<style>
@media print {
    .no-print {
        display: none !important;
    }
    body * {
        visibility: !important;
    }
}
</style>
@endsection