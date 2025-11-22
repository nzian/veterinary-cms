@extends('AdminBoard')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    body {
        font-family: 'Inter', sans-serif;
    }

    @keyframes fadeSlideUp {
        0% { opacity: 0; transform: translateY(20px); }
        100% { opacity: 1; transform: translateY(0); }
    }
    
    .animate-fadeSlideUp {
        animation: fadeSlideUp 0.8s ease-out forwards;
    }
    
    @keyframes fadeInScale {
        0% { opacity: 0; transform: scale(0.95); }
        100% { opacity: 1; transform: scale(1); }
    }

    .animate-fadeInScale {
        animation: fadeInScale 0.8s ease-out forwards;
    }
</style>

<div class="min-h-screen bg-gray-50 animate-fadeInScale px-2 sm:px-4 lg:px-6">
    
    <!-- Welcome Card -->
    <div id="welcomeCard" class="w-full bg-white shadow-xl rounded-xl p-4 sm:p-6 mb-4 sm:mb-6 animate-fadeSlideUp">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-0">
            
            <!-- Left Side: User Welcome -->
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-[#f88e28]">
                    Welcome back, {{ Auth::user()->user_name ?? 'Admin' }}!
                </h1>
                <p class="text-gray-600 mt-1 text-sm sm:text-base">
                    {{ now()->format('l, F j, Y') }}
                </p>
            </div>

            <!-- Right Side: Quick Info -->
            <div class="flex items-center gap-2 sm:gap-3">
                <div class="bg-gradient-to-r from-[#f88e28] to-[#ff6b35] text-white px-4 py-2 rounded-lg shadow-md">
                    <p class="text-sm font-bold">System Overview</p>
                </div>
            </div>

        </div>
    </div>

   <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-4 gap-3 sm:gap-4 lg:gap-6 mb-6 sm:mb-8">
    @php
        $keyMetrics = [
            [
                'label' => 'Total Branches',
                'value' => $totalBranches,
                'icon' => '🏢',
                'color' => 'from-blue-500 to-blue-600',
                'subtext' => $activeBranches . ' Active',
                'change' => '+' . $activeBranches,
                'route' => route('branch-management.index')
            ],
            [
                'label' => 'Total Revenue',
                'value' => '₱' . number_format($totalRevenue / 1000, 1) . 'k',
                'icon' => '💰',
                'color' => 'from-emerald-500 to-emerald-600',
                'subtext' => 'Today: ₱' . number_format($todayRevenue, 0),
                'change' => '+15%',
                'route' => route('sales.index')
            ],
            [
                'label' => 'Total Staff',
                'value' => $totalStaff,
                'icon' => '👥',
                'color' => 'from-purple-500 to-purple-600',
                'subtext' => 'All branches',
                'change' => '+' . $totalStaff,
                'route' => route('branch-user-management.index')
            ],
            [
                'label' => 'Total Visits',
                'value' => $branchStats['total_visits'],
                'icon' => '📅',
                'color' => 'from-amber-500 to-amber-600',
                'subtext' => 'Today: ' . $branchStats['today_visits'],
                'change' => '+12%',
                'route' => route('medical.index') . '?tab=visits'
            ],
        ];
    @endphp

    @foreach ($keyMetrics as $metric)
    <a href="{{ $metric['route'] ?? '#' }}" 
       class="block transform transition-all duration-300 hover:scale-105 cursor-pointer">
        <div class="relative overflow-hidden bg-white rounded-xl sm:rounded-2xl shadow-sm border border-gray-200 hover:shadow-xl transition-all duration-300 group">
            <div class="absolute inset-0 bg-gradient-to-br {{ $metric['color'] }} opacity-0 group-hover:opacity-10 transition-opacity"></div>
            <div class="p-4 sm:p-6">
                <div class="flex items-center justify-between mb-3 sm:mb-4">
                    <div class="text-xl sm:text-2xl group-hover:scale-110 transition-transform">{{ $metric['icon'] }}</div>
                    <span class="text-xs font-medium text-green-600 bg-green-50 px-2 py-1 rounded-full">
                        {{ $metric['change'] }}
                    </span>
                </div>
                <div class="space-y-1">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 group-hover:text-gray-800 transition-colors">{{ $metric['label'] }}</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-900 group-hover:text-[#f88e28] transition-colors">{{ $metric['value'] }}</p>
                </div>
                <div class="mt-2 sm:mt-3 text-xs text-gray-500">
                    {{ $metric['subtext'] }}
                </div>
            </div>
        </div>
    </a>
    @endforeach
</div>
   <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-6 mb-6 sm:mb-8">
    
    <div class="flex items-center justify-between mb-4">
        
        <div class="flex items-center gap-3">
            <h3 class="text-base sm:text-lg font-semibold text-gray-900">Calendar & Notes</h3>
            
            {{-- Week Range Display: Aligned next to the title (updated by JS) --}}
            <span id="currentWeekRange" class="text-sm font-semibold text-[#f88e28]"></span>
        </div>

        <div class="flex items-center gap-2">
            <button id="prevWeek" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </button>
            <button id="nextWeek" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6"> 
        
        {{-- REMOVED max-w-xs from this column to let it use the full 50% fluid width --}}
        <div class="lg:col-span-1 lg:pr-6 lg:border-r border-gray-200">
            
            {{-- REMOVED: The max-w-xs mx-auto inner div for full fluidity --}}

            <div class="grid grid-cols-7 gap-2 mb-3"> 
                {{-- REVERTED TO ABBREVIATIONS for proper spacing in a 50% column --}}
                <div class="text-center text-sm font-semibold text-gray-600 py-2">Sun</div>
                <div class="text-center text-sm font-semibold text-gray-600 py-2">Mon</div>
                <div class="text-center text-sm font-semibold text-gray-600 py-2">Tue</div>
                <div class="text-center text-sm font-semibold text-gray-600 py-2">Wed</div>
                <div class="text-center text-sm font-semibold text-gray-600 py-2">Thu</div>
                <div class="text-center text-sm font-semibold text-gray-600 py-2">Fri</div>
                <div class="text-center text-sm font-semibold text-gray-600 py-2">Sat</div>
            </div>
            
            <div id="calendarDays" class="grid grid-cols-7 gap-2">
                </div>

        </div>
        
        <div class="lg:col-span-1 lg:pl-6">
            <h4 class="text-base font-semibold text-gray-900 mb-3">
                Notes for <span id="selectedDate" class="text-[#f88e28]"></span>
            </h4>
            
            <div id="notesList" class="space-y-2 mb-4 max-h-64 overflow-y-auto">
                <p class="text-sm text-gray-500">Select a date to view notes</p>
            </div>
            
            <div id="addNoteSection" class="hidden">
                <textarea 
                    id="noteInput" 
                    rows="4" 
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#f88e28] focus:border-transparent" 
                    placeholder="Add a note..."></textarea>
                <div class="flex gap-2 mt-3">
                    <button id="saveNote" class="flex-1 px-4 py-2 bg-[#f88e28] text-white text-sm font-medium rounded-lg hover:bg-[#e67e22] transition-colors">
                        Save Note
                    </button>
                    <button id="cancelNote" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
            
            <button id="addNoteBtn" class="w-full h-12 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add Note
            </button>
        </div>
    </div>
</div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 mb-6 sm:mb-8">
        <!-- Monthly Revenue Chart -->
        <div class="bg-white rounded-xl sm:rounded-2xl shadow-sm border border-gray-200 p-4 sm:p-6">
            <div class="flex items-center justify-between mb-4 sm:mb-6">
                <h3 class="text-base sm:text-lg font-semibold text-gray-900">Revenue Trends</h3>
                <span class="text-xs sm:text-sm text-gray-500">Monthly</span>
            </div>
            <div class="h-48 sm:h-64">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <!-- Top Performers -->
        <div class="bg-white rounded-xl sm:rounded-2xl shadow-sm border border-gray-200 p-4 sm:p-6">
            <div class="flex items-center justify-between mb-4 sm:mb-6">
                <h3 class="text-base sm:text-lg font-semibold text-gray-900">Top Performers</h3>
                <span class="text-xs sm:text-sm text-gray-500">By Revenue</span>
            </div>
            
            @foreach($topBranches as $index => $branch)
            <div class="flex items-center p-3 bg-gray-50 rounded-xl mb-3 hover:bg-gray-100 transition-colors">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-sm text-white mr-3
                    {{ $index == 0 ? 'bg-gradient-to-br from-yellow-400 to-yellow-600' : '' }}
                    {{ $index == 1 ? 'bg-gradient-to-br from-gray-400 to-gray-600' : '' }}
                    {{ $index == 2 ? 'bg-gradient-to-br from-orange-400 to-orange-600' : '' }}
                    {{ $index > 2 ? 'bg-gray-300 text-gray-700' : '' }}">
                    {{ $index + 1 }}
                </div>
                <div class="flex-grow">
                    <p class="font-semibold text-sm text-gray-900">{{ $branch['branch_name'] }}</p>
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                        <div class="bg-gradient-to-r from-[#f88e28] to-[#ff6b35] h-2 rounded-full transition-all duration-500" 
                             style="width: {{ $topBranches->isNotEmpty() && $topBranches->first()['total_revenue'] > 0 ? ($branch['total_revenue'] / $topBranches->first()['total_revenue']) * 100 : 0 }}%">
                        </div>
                    </div>
                </div>
                <div class="ml-3 text-right">
                    <p class="font-bold text-sm text-[#f88e28]">₱{{ number_format($branch['total_revenue'] / 1000, 1) }}k</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Weekly Revenue Chart -->
    <div class="bg-white rounded-xl sm:rounded-2xl shadow-sm border border-gray-200 p-4 sm:p-6 mb-6 sm:mb-8">
        <div class="flex items-center justify-between mb-4 sm:mb-6">
            <h3 class="text-base sm:text-lg font-semibold text-gray-900">Weekly Revenue</h3>
            <span class="text-xs sm:text-sm text-gray-500">Last 7 days</span>
        </div>
        <div class="h-48 sm:h-64">
            <canvas id="weeklyChart"></canvas>
        </div>
    </div>

    <!-- Branch Performance Table -->
    <div class="bg-white rounded-xl sm:rounded-2xl shadow-sm border border-gray-200 mb-6 sm:mb-8">
        <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
            <h3 class="text-base sm:text-lg font-semibold text-gray-900">Branch Overview</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[800px]">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Branch</th>
                        <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                        <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Staff</th>
                        <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                        <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($branchPerformance as $branch)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 sm:px-6 py-3 sm:py-4">
                            <p class="text-sm font-semibold text-gray-900">{{ $branch['branch_name'] }}</p>
                        </td>
                        <td class="px-4 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm text-gray-600">
                            {{ $branch['branch_location'] }}
                        </td>
                        <td class="px-4 sm:px-6 py-3 sm:py-4">
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full 
                                {{ $branch['branch_status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($branch['branch_status']) }}
                            </span>
                        </td>
                        <td class="px-4 sm:px-6 py-3 sm:py-4">
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                {{ $branch['staff_count'] }}
                            </span>
                        </td>
                        <td class="px-4 sm:px-6 py-3 sm:py-4 text-sm font-bold text-[#f88e28]">
                            ₱{{ number_format($branch['total_revenue'], 0) }}
                        </td>
                        <td class="px-4 sm:px-6 py-3 sm:py-4">
                            <a href="{{ route('superadmin.branch.show', $branch['branch_id']) }}" 
                               class="inline-flex items-center px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium rounded-lg transition-colors">
                                <span>View</span>
                                <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Visit Overview Section -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 mb-6 sm:mb-8">
        <!-- Visit by Status -->
        <div class="bg-white rounded-xl sm:rounded-2xl shadow-sm border border-gray-200 p-4 sm:p-6">
            <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-4">Visit Overview by Status</h3>
            <div class="space-y-3">
                @foreach($visitsByStatus ?? [] as $status => $count)
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-3 h-3 rounded-full 
                            {{ $status === 'completed' ? 'bg-green-500' : '' }}
                            {{ $status === 'pending' ? 'bg-yellow-500' : '' }}
                            {{ $status === 'cancelled' ? 'bg-red-500' : '' }}
                            {{ $status === 'arrived' ? 'bg-blue-500' : '' }}"></div>
                        <span class="text-sm font-medium text-gray-700 capitalize">{{ $status }}</span>
                    </div>
                    <span class="text-sm font-bold text-gray-900">{{ number_format($count) }}</span>
                </div>
                @endforeach
                @if(empty($visitsByStatus))
                <p class="text-sm text-gray-500 text-center py-4">No visit data available</p>
                @endif
            </div>
        </div>

        <!-- Visit by Patient Type -->
        <div class="bg-white rounded-xl sm:rounded-2xl shadow-sm border border-gray-200 p-4 sm:p-6">
            <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-4">Visit Overview by Patient Type</h3>
            <div class="space-y-3">
                @foreach($visitsByPatientType ?? [] as $patientType => $count)
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-3 h-3 rounded-full 
                            {{ strtolower($patientType) === 'inpatient' ? 'bg-purple-500' : 'bg-teal-500' }}"></div>
                        <span class="text-sm font-medium text-gray-700 capitalize">
                            {{ is_object($patientType) ? $patientType->value : $patientType }}
                        </span>
                    </div>
                    <span class="text-sm font-bold text-gray-900">{{ number_format($count) }}</span>
                </div>
                @endforeach
                @if(empty($visitsByPatientType))
                <p class="text-sm text-gray-500 text-center py-4">No visit data available</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Equipment Overview Table -->
    <div class="bg-white rounded-xl sm:rounded-2xl shadow-sm border border-gray-200 mb-6 sm:mb-8">
        <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
            <h3 class="text-base sm:text-lg font-semibold text-gray-900">Equipment Overview</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[800px]">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Branch</th>
                        <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Equipment Name</th>
                        <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                        <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Condition</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @php
                        $equipment = \App\Models\Equipment::with('branch')->get();
                    @endphp
                    @forelse($equipment as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 sm:px-6 py-3 sm:py-4">
                            <p class="text-sm font-semibold text-gray-900">{{ $item->branch->branch_name ?? 'N/A' }}</p>
                        </td>
                        <td class="px-4 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm text-gray-900 font-medium">
                            {{ $item->equipment_name }}
                        </td>
                        <td class="px-4 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm text-gray-600">
                            {{ $item->equipment_category ?? 'General' }}
                        </td>
                        <td class="px-4 sm:px-6 py-3 sm:py-4">
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full 
                                {{ $item->equipment_quantity > 5 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ $item->equipment_quantity }}
                            </span>
                        </td>
                        <td class="px-4 sm:px-6 py-3 sm:py-4">
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full 
                                {{ $item->equipment_quantity > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $item->equipment_quantity > 0 ? 'Available' : 'Out of Stock' }}
                            </span>
                        </td>
                        <td class="px-4 sm:px-6 py-3 sm:py-4">
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                Excellent
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 sm:px-6 py-8 text-center text-gray-500">
                            No equipment found
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white rounded-xl sm:rounded-2xl shadow-sm border border-gray-200 mb-6 sm:mb-8">
        <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
            <h3 class="text-base sm:text-lg font-semibold text-gray-900">Recent Orders</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[800px]">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                        <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Branch</th>
                        <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                        <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($recentOrders as $order)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 sm:px-6 py-3 sm:py-4">
                            <p class="text-sm font-semibold text-gray-900">#{{ $order->ord_id }}</p>
                        </td>
                        <td class="px-4 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm text-gray-600">
                            {{ $order->user->branch->branch_name ?? 'N/A' }}
                        </td>
                        <td class="px-4 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm text-gray-900">
                            {{ $order->product->prod_name ?? 'Multiple Items' }}
                        </td>
                        <td class="px-4 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm text-gray-600">
                            {{ \Carbon\Carbon::parse($order->ord_date)->format('M d, Y') }}
                        </td>
                        <td class="px-4 sm:px-6 py-3 sm:py-4 text-sm font-bold text-[#f88e28]">
                            ₱{{ number_format($order->ord_total, 2) }}
                        </td>
                        <td class="px-4 sm:px-6 py-3 sm:py-4">
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                Completed
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Appointments -->
    <div class="bg-white rounded-xl sm:rounded-2xl shadow-sm border border-gray-200 mb-6 sm:mb-8">
        <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
            <h3 class="text-base sm:text-lg font-semibold text-gray-900">Recent Appointments</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[800px]">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet</th>
                        <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner</th>
                        <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Branch</th>
                        <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($recentAppointments as $appointment)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 sm:px-6 py-3 sm:py-4">
                            <p class="text-sm font-semibold text-gray-900">{{ $appointment->pet->pet_name ?? 'N/A' }}</p>
                            <p class="text-xs text-gray-500">{{ $appointment->pet->pet_species ?? '' }}</p>
                        </td>
                        <td class="px-4 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm text-gray-600">
                            {{ $appointment->pet->owner->own_name ?? 'N/A' }}
                        </td>
                        <td class="px-4 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm text-gray-600">
                            {{ $appointment->user->branch->branch_name ?? 'N/A' }}
                        </td>
                        <td class="px-4 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm text-gray-600">
                            {{ \Carbon\Carbon::parse($appointment->appoint_date)->format('M d, Y') }}
                        </td>
                        <td class="px-4 sm:px-6 py-3 sm:py-4">
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                {{ $appointment->appoint_type ?? 'Check-up' }}
                            </span>
                        </td>
                        <td class="px-4 sm:px-6 py-3 sm:py-4">
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full 
                                {{ $appointment->appoint_status == 'completed' ? 'bg-green-100 text-green-800' : 
                                   ($appointment->appoint_status == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                   'bg-gray-100 text-gray-800') }}">
                                {{ ucfirst($appointment->appoint_status) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Referrals -->
    <div class="bg-white rounded-xl sm:rounded-2xl shadow-sm border border-gray-200 mb-6 sm:mb-8">
        <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
            <h3 class="text-base sm:text-lg font-semibold text-gray-900">Recent Referrals</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[800px]">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet</th>
                        <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">From Branch</th>
                        <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">To Branch</th>
                        <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                        <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @php
                        $recentReferrals = \App\Models\Referral::with(['appointment.pet.owner', 'refToBranch', 'refByBranch'])
                            ->orderBy('ref_date', 'desc')
                            ->limit(10)
                            ->get();
                    @endphp
                    @forelse($recentReferrals as $referral)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 sm:px-6 py-3 sm:py-4">
                            <p class="text-sm font-semibold text-gray-900">{{ $referral->appointment->pet->pet_name ?? 'N/A' }}</p>
                            <p class="text-xs text-gray-500">{{ $referral->appointment->pet->owner->own_name ?? 'N/A' }}</p>
                        </td>
                        <td class="px-4 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm text-gray-600">
                            {{ $referral->refByBranch?->branch_name ?? 'N/A' }}
                        </td>
                        <td class="px-4 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm text-gray-600">
                            {{ $referral->refToBranch?->branch_name ?? 'N/A' }}
                        </td>
                        <td class="px-4 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm text-gray-600">
                            {{ \Carbon\Carbon::parse($referral->ref_date)->format('M d, Y') }}
                        </td>
                        <td class="px-4 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm text-gray-700">
                            {{ \Illuminate\Support\Str::limit($referral->ref_description, 50) }}
                        </td>
                        <td class="px-4 sm:px-6 py-3 sm:py-4">
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">
                                {{ ucfirst($referral->ref_status ?? 'Pending') }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 sm:px-6 py-8 text-center text-gray-500">
                            No recent referrals
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- User Activity Logs -->
    <div class="bg-white rounded-xl sm:rounded-2xl shadow-sm border border-gray-200 mb-6 sm:mb-8">
        <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
            <h3 class="text-base sm:text-lg font-semibold text-gray-900">User Activity Logs</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[800px]">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Branch</th>
                        <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Activity</th>
                        <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timestamp</th>
                        <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @php
                        // Get recent user activities (orders and appointments)
                        $userActivities = collect();
                        
                        // Add recent orders as activities
                        foreach($recentOrders as $order) {
                            $userActivities->push([
                                'user_name' => $order->user->user_name ?? 'Unknown',
                                'branch_name' => $order->user->branch->branch_name ?? 'N/A',
                                'user_role' => $order->user->user_role ?? 'N/A',
                                'activity' => 'Created order #' . $order->ord_id,
                                'timestamp' => $order->ord_date,
                                'type' => 'order'
                            ]);
                        }
                        
                        // Add recent appointments as activities
                        foreach($recentAppointments as $appointment) {
                            $userActivities->push([
                                'user_name' => $appointment->user->user_name ?? 'Unknown',
                                'branch_name' => $appointment->user->branch->branch_name ?? 'N/A',
                                'user_role' => $appointment->user->user_role ?? 'N/A',
                                'activity' => 'Scheduled appointment for ' . ($appointment->pet->pet_name ?? 'Unknown'),
                                'timestamp' => $appointment->appoint_date,
                                'type' => 'appointment'
                            ]);
                        }
                        
                        // Sort by timestamp descending
                        $userActivities = $userActivities->sortByDesc('timestamp')->take(10);
                    @endphp
                    
                    @forelse($userActivities as $activity)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 sm:px-6 py-3 sm:py-4">
                            <p class="text-sm font-semibold text-gray-900">{{ $activity['user_name'] }}</p>
                        </td>
                        <td class="px-4 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm text-gray-600">
                            {{ $activity['branch_name'] }}
                        </td>
                        <td class="px-4 sm:px-6 py-3 sm:py-4">
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full 
                                {{ strtolower($activity['user_role']) == 'veterinarian' ? 'bg-blue-100 text-blue-800' : 
                                   (strtolower($activity['user_role']) == 'receptionist' ? 'bg-green-100 text-green-800' : 
                                   'bg-purple-100 text-purple-800') }}">
                                {{ ucfirst($activity['user_role']) }}
                            </span>
                        </td>
                        <td class="px-4 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm text-gray-700">
                            {{ $activity['activity'] }}
                        </td>
                        <td class="px-4 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm text-gray-600">
                            {{ \Carbon\Carbon::parse($activity['timestamp'])->format('M d, Y h:i A') }}
                        </td>
                        <td class="px-4 sm:px-6 py-3 sm:py-4">
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full 
                                {{ $activity['type'] == 'order' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ ucfirst($activity['type']) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 sm:px-6 py-8 text-center text-gray-500">
                            No recent activity
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ====================================================================
// CALENDAR APPLICATION LOGIC (Self-Contained)
// ====================================================================
// ====================================================================
var CalendarApp = {
    currentDate: new Date(),
    currentWeekStart: null, // Tracks the start date (Sunday) of the current week
    selectedDate: null,
    notes: {},
    
    init: function() {
        // 1. Load notes from localStorage (kept)
        var stored = localStorage.getItem('calendarNotes');
        if (stored) {
            try {
                this.notes = JSON.parse(stored);
            } catch(e) {
                this.notes = {};
            }
        }
        
        if (!document.getElementById('calendarDays')) return;

        // 2. Initialize to the start of the current week (Sunday)
        this.calculateCurrentWeekStart(this.currentDate);
        
        // 3. Set default selected date to today
        this.selectedDate = this.formatDate(this.currentDate);
        
        // 4. Bind events (using the new Week IDs)
        this.bindEvents();
        
        // 5. Render initial state
        this.renderCalendar();
        this.updateSelectedDateDisplay(this.selectedDate);
        this.renderNotes(this.selectedDate); 
    },

    // Calculates the Sunday of the week containing the given date
    calculateCurrentWeekStart: function(date) {
        var day = date.getDay(); // 0 for Sunday, 6 for Saturday
        var diff = date.getDate() - day;
        this.currentWeekStart = new Date(date.setDate(diff));
        this.currentWeekStart.setHours(0, 0, 0, 0); // Reset time for clean calculations
    },

    // --- NAVIGATION FUNCTIONS (UPDATED FOR WEEK) ---
    previousWeek: function() {
        this.currentWeekStart.setDate(this.currentWeekStart.getDate() - 7);
        this.renderCalendar();
    },
    
    nextWeek: function() {
        this.currentWeekStart.setDate(this.currentWeekStart.getDate() + 7);
        this.renderCalendar();
    },

    // --- RENDERING FUNCTIONS (UPDATED TO SHOW ONLY ONE ROW) ---
    renderCalendar: function() {
        var monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        
        var current = new Date(this.currentWeekStart);
        var html = '';
        var todayFormatted = this.formatDate(new Date());

        // Calculate and update week range display
        var start = new Date(this.currentWeekStart);
        var end = new Date(this.currentWeekStart);
        end.setDate(end.getDate() + 6);
        
        var currentWeekEl = document.getElementById('currentWeekRange');
        if (currentWeekEl) {
            // Display the week range (e.g., "Oct 12 - Oct 18, 2025")
            currentWeekEl.textContent = 
                monthNames[start.getMonth()] + ' ' + start.getDate() + ' - ' + 
                monthNames[end.getMonth()] + ' ' + end.getDate() + ', ' + end.getFullYear();
        }

        // Loop through exactly 7 days
        for (var i = 0; i < 7; i++) {
            var dateStr = this.formatDate(current);
            
            // Coloring logic (re-using your original logic)
            var isToday = (dateStr === todayFormatted);
            var isSelected = (this.selectedDate === dateStr);
            var hasNotes = this.notes[dateStr] && this.notes[dateStr].length > 0;
            
            var baseClass = 'text-gray-700 hover:bg-gray-100';
            var ringClass = '';
            
            if (isSelected) {
                baseClass = 'bg-blue-500 text-white shadow-lg shadow-blue-500/50 hover:bg-blue-600 transition-all';
                ringClass = 'ring-2 ring-offset-2 ring-blue-500'; 
            } else if (isToday) {
                baseClass = 'bg-[#f88e28] text-white hover:bg-[#e67e22]';
            }
            
            if (hasNotes && !isSelected) { 
                 ringClass = 'ring-2 ring-[#f88e28] ring-opacity-50';
            }

            // Note indicator dot
            var noteDot = '';
            if (hasNotes) {
                var dotColor = isSelected || isToday ? 'bg-white' : 'bg-[#f88e28]';
                noteDot = '<div class="w-1.5 h-1.5 ' + dotColor + ' rounded-full mx-auto mt-1"></div>';
            }
            
            html += '<div class="aspect-square p-1">' +
                    '<button type="button" onclick="CalendarApp.selectDate(\'' + dateStr + '\')" ' +
                    'class="w-full h-full rounded-lg text-sm font-medium transition-all ' + 
                    baseClass + ' ' + ringClass + '">' +
                    '<div class="flex flex-col items-center justify-center h-full">' +
                    '<span>' + current.getDate() + '</span>' + noteDot +
                    '</div></button></div>';

            // Move to the next day
            current.setDate(current.getDate() + 1);
        }
        
        var calendarDaysEl = document.getElementById('calendarDays');
        if (calendarDaysEl) {
            calendarDaysEl.innerHTML = html;
        }
    },
    
    // --- BINDING FUNCTIONS (UPDATED TO USE WEEK IDs) ---
    bindEvents: function() {
        var self = this;
        // NOTE: Changed IDs from prev/nextMonth to prev/nextWeek
        var prevBtn = document.getElementById('prevWeek');
        var nextBtn = document.getElementById('nextWeek');
        var addBtn = document.getElementById('addNoteBtn');
        var saveBtn = document.getElementById('saveNote');
        var cancelBtn = document.getElementById('cancelNote');
        
        if (prevBtn) { prevBtn.onclick = function(e) { e.preventDefault(); self.previousWeek(); }; }
        if (nextBtn) { nextBtn.onclick = function(e) { e.preventDefault(); self.nextWeek(); }; }
        
        if (addBtn) {
            addBtn.onclick = function(e) {
                e.preventDefault();
                if (!self.selectedDate) {
                    alert('Please select a date first');
                    return;
                }
                self.showNoteForm();
            };
        }
        
        if (saveBtn) { saveBtn.onclick = function(e) { e.preventDefault(); self.saveNote(); }; }
        if (cancelBtn) { cancelBtn.onclick = function(e) { e.preventDefault(); self.hideNoteForm(); }; }
    },
    
    // --- UTILITY/NOTE FUNCTIONS (Kept or simplified) ---
    formatDate: function(date) {
        var y = date.getFullYear();
        var m = this.pad(date.getMonth() + 1);
        var d = this.pad(date.getDate());
        return y + '-' + m + '-' + d;
    },
    
    updateSelectedDateDisplay: function(dateStr) {
        var parts = dateStr.split('-');
        // Note: Month is 0-indexed in JS, so subtract 1
        var date = new Date(parts[0], parts[1] - 1, parts[2]); 
        var monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        var formatted = monthNames[date.getMonth()] + ' ' + date.getDate() + ', ' + date.getFullYear();
        
        var selectedDateEl = document.getElementById('selectedDate');
        if (selectedDateEl) {
            selectedDateEl.textContent = formatted;
        }
    },
    
    // ... (rest of the note functions: showNoteForm, hideNoteForm, selectDate, 
    // renderNotes, saveNote, deleteNote, escapeHtml, pad are the same as before) ...

    showNoteForm: function() {
        document.getElementById('addNoteSection').classList.remove('hidden');
        document.getElementById('addNoteBtn').classList.add('hidden');
        document.getElementById('noteInput').focus();
    },
    
    hideNoteForm: function() {
        document.getElementById('noteInput').value = '';
        document.getElementById('addNoteSection').classList.add('hidden');
        document.getElementById('addNoteBtn').classList.remove('hidden');
    },

    selectDate: function(dateStr) {
        this.selectedDate = dateStr;
        this.updateSelectedDateDisplay(dateStr);
        this.hideNoteForm();
        this.renderNotes(dateStr);
        this.renderCalendar();
    },

    renderNotes: function(dateStr) {
        var notesListEl = document.getElementById('notesList');
        if (!notesListEl) return;
        
        var dateNotes = this.notes[dateStr] || [];
        
        if (dateNotes.length === 0) {
            notesListEl.innerHTML = '<p class="text-sm text-gray-500">No notes for this date</p>';
        } else {
            var html = '';
            for (var i = 0; i < dateNotes.length; i++) {
                html += '<div class="p-3 bg-gray-50 rounded-lg group relative hover:bg-gray-100 transition-colors mb-2">' +
                         '<p class="text-sm text-gray-700 pr-8 break-words">' + this.escapeHtml(dateNotes[i]) + '</p>' +
                         '<button type="button" onclick="CalendarApp.deleteNote(\'' + dateStr + '\', ' + i + ')" ' +
                         'class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 text-red-500 hover:text-red-700 transition-opacity">' +
                         '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                         '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>' +
                         '</svg></button></div>';
            }
            notesListEl.innerHTML = html;
        }
    },

    saveNote: function() {
        var noteInput = document.getElementById('noteInput');
        if (!noteInput || !this.selectedDate) {
             alert('Please select a date and enter a note.');
             return;
        }
        
        var noteText = noteInput.value.trim();
        if (!noteText) {
            alert('Please enter a note');
            return;
        }
        
        if (!this.notes[this.selectedDate]) {
            this.notes[this.selectedDate] = [];
        }
        
        this.notes[this.selectedDate].push(noteText);
        
        localStorage.setItem('calendarNotes', JSON.stringify(this.notes));
        
        this.renderNotes(this.selectedDate);
        this.renderCalendar();
        this.hideNoteForm();
    },

    deleteNote: function(dateStr, index) {
        if (!confirm('Delete this note?')) { return; }
        
        if (this.notes[dateStr]) {
            this.notes[dateStr].splice(index, 1);
            
            if (this.notes[dateStr].length === 0) {
                delete this.notes[dateStr];
            }
            
            localStorage.setItem('calendarNotes', JSON.stringify(this.notes));
            this.renderNotes(dateStr);
            this.renderCalendar();
        }
    },

    escapeHtml: function(text) {
        var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    },
    
    pad: function(num) {
        return (num < 10) ? '0' + num : num.toString();
    }
};
// ====================================================================
// INITIALIZATION AND CHART.JS CODE
// Wrap everything in DOMContentLoaded to ensure elements exist.
// ====================================================================
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Initialize Calendar
    if (typeof CalendarApp !== 'undefined') {
        CalendarApp.init();
    }

    // 2. Chart Options
    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleColor: '#fff',
                bodyColor: '#fff',
                titleFont: { size: window.innerWidth < 640 ? 11 : 12 },
                bodyFont: { size: window.innerWidth < 640 ? 10 : 11 },
                padding: window.innerWidth < 640 ? 6 : 10,
                cornerRadius: 8
            }
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: {
                    color: '#6B7280',
                    font: { size: window.innerWidth < 640 ? 9 : 11 }
                }
            },
            y: {
                beginAtZero: true,
                grid: { color: '#F3F4F6' },
                ticks: {
                    color: '#6B7280',
                    font: { size: window.innerWidth < 640 ? 9 : 11 },
                    callback: value => '₱' + value.toLocaleString()
                }
            }
        }
    };

    // 3. Revenue Chart
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: {!! json_encode($months ?? ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']) !!},
            datasets: (() => {
                const data = {!! json_encode($monthlyBranchRevenue ?? []) !!};
                const colors = ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899'];
                return data.map((branch, i) => ({
                    label: branch.branch_name,
                    data: branch.data,
                    borderColor: colors[i % colors.length],
                    backgroundColor: colors[i % colors.length] + '20',
                    tension: 0.4,
                    fill: true,
                    borderWidth: 3,
                    pointBackgroundColor: colors[i % colors.length],
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }));
            })()
        },
        options: {
            ...chartOptions,
            plugins: {
                ...chartOptions.plugins,
                legend: { display: true, position: 'bottom' }
            }
        }
    });

    // 4. Weekly Chart
    new Chart(document.getElementById('weeklyChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($dates ?? ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']) !!},
            datasets: (() => {
                const data = {!! json_encode($last7DaysRevenue ?? []) !!};
                const colors = ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899'];
                return Object.keys(data).map((branch, i) => ({
                    label: branch,
                    data: data[branch],
                    backgroundColor: colors[i % colors.length],
                    borderRadius: 8,
                    borderSkipped: false
                }));
            })()
        },
        options: {
            ...chartOptions,
            plugins: {
                ...chartOptions.plugins,
                legend: { display: true, position: 'bottom' }
            }
        }
    });
});
</script>
@endsection