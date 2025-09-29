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
                        <button onclick="printReport()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md text-sm flex items-center">
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
                            class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs transition-colors duration-200">
                        <i class="fas fa-eye mr-1"></i>View
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
                            class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs transition-colors duration-200">
                        <i class="fas fa-eye mr-1"></i>View
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
                            class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs transition-colors duration-200">
                        <i class="fas fa-eye mr-1"></i>View
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
                            class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs transition-colors duration-200">
                        <i class="fas fa-eye mr-1"></i>View
                    </button>
                </td>
                
            @elseif($reportType == 'referrals')
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $record->ref_id }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ \Carbon\Carbon::parse($record->ref_date)->format('M d, Y') }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->owner_name }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->pet_name }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ Str::limit($record->referral_reason, 50) }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->referred_by }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->referred_to }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button onclick="viewRecordDetails('referrals', '{{ $record->ref_id }}')" 
                            class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs transition-colors duration-200">
                        <i class="fas fa-eye mr-1"></i>View
                    </button>
                </td>
                
            @elseif($reportType == 'equipment')
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $record->equipment_id }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->equipment_name }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ Str::limit($record->equipment_description ?? 'N/A', 50) }}</td>
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
                            class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs transition-colors duration-200">
                        <i class="fas fa-eye mr-1"></i>View
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
                            class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs transition-colors duration-200">
                        <i class="fas fa-eye mr-1"></i>View
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

<!-- Record Details Modal -->
<div id="recordModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900" id="modalTitle">Record Details</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-6" id="modalContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #reportTable, #reportTable * {
        visibility: visible;
    }
    #reportTable {
        position: absolute;
        left: 0;
        top: 0;
    }
    .no-print {
        display: none !important;
    }
}

.report-card {
    transition: all 0.3s ease;
    border: 1px solid #e5e7eb;
}

.report-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}
</style>

<script>
function exportReport() {
    const form = document.querySelector('form');
    
    // Create a temporary form with export action
    const exportForm = document.createElement('form');
    exportForm.method = 'GET';
    exportForm.action = '{{ route("reports.export") }}';
    
    // Copy all form data
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
    fetch(`/reports/${reportType}/${recordId}`)
        .then(response => response.json())
        .then(data => {
            if (data) {
                showRecordModal(data, reportType);
            } else {
                alert('Record details not found');
            }
        })
        .catch(error => {
            console.error('Error fetching record details:', error);
            alert('Error loading record details');
        });
}

function showRecordModal(data, reportType) {
    const modal = document.getElementById('recordModal');
    const title = document.getElementById('modalTitle');
    const content = document.getElementById('modalContent');
    
    // Set title based on report type
    const titles = {
        'appointments': 'Appointment Details',
        'pets': 'Pet Information',
        'billing': 'Billing Details',
        'sales': 'Sales Transaction',
        'medical': 'Medical History',
        'services': 'Service Details',
        'staff': 'Staff Information',
        'inventory': 'Inventory Details',
        'revenue': 'Revenue Details',
        'branch_performance': 'Branch Performance',
        'prescriptions': 'Prescription Report'
    };
    
    title.textContent = titles[reportType] || 'Record Details';
    
    // Build content HTML
    let html = '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
    
    for (const [key, value] of Object.entries(data)) {
        if (value !== null && value !== '') {
            const label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            let displayValue = value;
            
            // Format specific types of data
            if (key.includes('date') && value) {
                displayValue = new Date(value).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            } else if (key.includes('time') && value) {
                displayValue = new Date('1970-01-01T' + value + 'Z').toLocaleTimeString('en-US', {
                    timeZone: 'UTC',
                    hour12: true,
                    hour: 'numeric',
                    minute: '2-digit'
                });
            } else if ((key.includes('price') || key.includes('amount') || key.includes('total') || 
                       key.includes('revenue') || key.includes('cash') || key.includes('change')) && 
                       !isNaN(parseFloat(value))) {
                displayValue = '₱' + parseFloat(value).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            } else if (key.includes('status')) {
                const statusClass = value === 'completed' || value === 'paid' || value === 'active' ? 'text-green-600' : 
                                   value === 'pending' ? 'text-yellow-600' : 
                                   value === 'cancelled' || value === 'expired' ? 'text-red-600' : 'text-gray-600';
                displayValue = `<span class="${statusClass} font-semibold">${value}</span>`;
            }
            
            // Don't format contact numbers or IDs
            if (key.includes('contact') || key.includes('id') || key.includes('license')) {
                displayValue = value; // Use original value without formatting
            }
            
            html += `
                <div class="border-b border-gray-100 pb-3">
                    <dt class="text-sm font-medium text-gray-500">${label}</dt>
                    <dd class="mt-1 text-sm text-gray-900">${displayValue || 'N/A'}</dd>
                </div>
            `;
        }
    }
    
    html += '</div>';
    content.innerHTML = html;
    
    // Show modal
    modal.classList.remove('hidden');
}

function closeModal() {
    document.getElementById('recordModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('recordModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

// Auto-submit form when report type changes
document.getElementById('reportSelect').addEventListener('change', function() {
    this.form.submit();
});

function viewRecordDetails(reportType, recordId) {
    console.log('Attempting to view details for:', reportType, recordId); // Debug line
    
    const url = `/reports/${reportType}/${recordId}`;
    console.log('Fetching URL:', url); // Debug line
    
    fetch(url)
        .then(response => {
            console.log('Response status:', response.status); // Debug line
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Received data:', data); // Debug line
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
</script>
@endsection