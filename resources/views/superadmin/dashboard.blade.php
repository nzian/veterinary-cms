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

    <!-- Key Metrics - Mobile Responsive Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 lg:gap-6 mb-6 sm:mb-8">
        @php
            $keyMetrics = [
                [
                    'label' => 'Total Branches',
                    'value' => $totalBranches,
                    'icon' => '🏢',
                    'color' => 'from-blue-500 to-blue-600',
                    'subtext' => $activeBranches . ' Active',
                    'change' => '+' . $activeBranches
                ],
                [
                    'label' => 'Total Revenue',
                    'value' => '₱' . number_format($totalRevenue / 1000, 1) . 'k',
                    'icon' => '💰',
                    'color' => 'from-emerald-500 to-emerald-600',
                    'subtext' => 'Today: ₱' . number_format($todayRevenue, 0),
                    'change' => '+15%'
                ],
                [
                    'label' => 'Total Staff',
                    'value' => $totalStaff,
                    'icon' => '👥',
                    'color' => 'from-purple-500 to-purple-600',
                    'subtext' => 'All branches',
                    'change' => '+' . $totalStaff
                ],
                [
                    'label' => 'Appointments',
                    'value' => $branchStats['total_appointments'],
                    'icon' => '📅',
                    'color' => 'from-amber-500 to-amber-600',
                    'subtext' => 'Today: ' . $branchStats['today_appointments'],
                    'change' => '+12%'
                ],
            ];
        @endphp

        @foreach ($keyMetrics as $metric)
        <div class="block transform transition-all duration-300 hover:scale-105">
            <div class="relative overflow-hidden bg-white rounded-xl sm:rounded-2xl shadow-sm border border-gray-200 hover:shadow-xl transition-all duration-300 group cursor-pointer">
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
        </div>
        @endforeach
    </div>
    
    <!-- Performance Indicators -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6 mb-6 sm:mb-8">
        <!-- Revenue Distribution -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-6">
            <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-4">Revenue Distribution</h3>
            <div class="space-y-3">
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">Products Sales</span>
                        <span class="font-semibold text-gray-900">45%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-2 rounded-full" style="width: 45%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">Services</span>
                        <span class="font-semibold text-gray-900">35%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-gradient-to-r from-green-500 to-green-600 h-2 rounded-full" style="width: 35%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">Appointments</span>
                        <span class="font-semibold text-gray-900">20%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-gradient-to-r from-purple-500 to-purple-600 h-2 rounded-full" style="width: 20%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Branch Status -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-6">
            <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-4">Branch Status</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-green-500 rounded-full mr-3"></div>
                        <span class="text-sm text-gray-600">Active</span>
                    </div>
                    <span class="text-lg font-bold text-gray-900">{{ $activeBranches }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-gray-400 rounded-full mr-3"></div>
                        <span class="text-sm text-gray-600">Inactive</span>
                    </div>
                    <span class="text-lg font-bold text-gray-900">{{ $totalBranches - $activeBranches }}</span>
                </div>
                <div class="flex items-center justify-between pt-3 border-t">
                    <span class="text-sm font-medium text-gray-700">Total</span>
                    <span class="text-xl font-bold text-[#f88e28]">{{ $totalBranches }}</span>
                </div>
            </div>
        </div>

        <!-- Quick Stats Summary -->
        <div class="bg-gradient-to-br from-[#f88e28] to-[#ff6b35] rounded-xl shadow-lg p-4 sm:p-6 text-white">
            <h3 class="text-base sm:text-lg font-semibold mb-4">System Health</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between pb-3 border-b border-white/20">
                    <span class="text-sm opacity-90">Uptime</span>
                    <span class="text-lg font-bold">99.9%</span>
                </div>
                <div class="flex items-center justify-between pb-3 border-b border-white/20">
                    <span class="text-sm opacity-90">Performance</span>
                    <span class="text-lg font-bold">Excellent</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm opacity-90">Status</span>
                    <span class="inline-flex items-center px-2 py-1 bg-white/20 rounded-full text-xs font-semibold">
                        <span class="w-2 h-2 bg-white rounded-full mr-2"></span>
                        Operational
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Mini Calendar with Notes -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-6 mb-6 sm:mb-8">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base sm:text-lg font-semibold text-gray-900">Calendar & Notes</h3>
            <div class="flex items-center gap-2">
                <button id="prevMonth" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>
                <span id="currentMonth" class="text-sm font-semibold text-gray-700 min-w-[120px] text-center"></span>
                <button id="nextMonth" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- Calendar -->
            <div class="lg:col-span-2">
                <div class="grid grid-cols-7 gap-1 mb-2">
                    <div class="text-center text-xs font-semibold text-gray-600 py-2">Sun</div>
                    <div class="text-center text-xs font-semibold text-gray-600 py-2">Mon</div>
                    <div class="text-center text-xs font-semibold text-gray-600 py-2">Tue</div>
                    <div class="text-center text-xs font-semibold text-gray-600 py-2">Wed</div>
                    <div class="text-center text-xs font-semibold text-gray-600 py-2">Thu</div>
                    <div class="text-center text-xs font-semibold text-gray-600 py-2">Fri</div>
                    <div class="text-center text-xs font-semibold text-gray-600 py-2">Sat</div>
                </div>
                <div id="calendarDays" class="grid grid-cols-7 gap-1">
                    <!-- Calendar days will be generated here -->
                </div>
            </div>
            
            <!-- Notes Section -->
            <div class="border-l border-gray-200 pl-4">
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Notes for <span id="selectedDate"></span></h4>
                <div id="notesList" class="space-y-2 mb-4 max-h-48 overflow-y-auto">
                    <p class="text-xs text-gray-500">Select a date to view notes</p>
                </div>
                <div id="addNoteSection" class="hidden">
                    <textarea 
                        id="noteInput" 
                        rows="3" 
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#f88e28] focus:border-transparent" 
                        placeholder="Add a note..."></textarea>
                    <div class="flex gap-2 mt-2">
                        <button id="saveNote" class="flex-1 px-3 py-2 bg-[#f88e28] text-white text-sm font-medium rounded-lg hover:bg-[#e67e22] transition-colors">
                            Save
                        </button>
                        <button id="cancelNote" class="px-3 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                            Cancel
                        </button>
                    </div>
                </div>
                <button id="addNoteBtn" class="w-full px-3 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                    + Add Note
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

    <!-- Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
        <!-- Recent Orders -->
        <div class="bg-white rounded-xl sm:rounded-2xl shadow-sm border border-gray-200">
            <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                <h3 class="text-base sm:text-lg font-semibold text-gray-900">Recent Orders</h3>
            </div>
            <div class="p-4 space-y-3">
                @foreach($recentOrders as $order)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center text-white mr-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-sm text-gray-900">#{{ $order->ord_id }}</p>
                            <p class="text-xs text-gray-500">{{ $order->user->branch->branch_name ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-sm text-[#f88e28]">₱{{ number_format($order->ord_total, 2) }}</p>
                        <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($order->ord_date)->format('M d') }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Recent Appointments -->
        <div class="bg-white rounded-xl sm:rounded-2xl shadow-sm border border-gray-200">
            <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                <h3 class="text-base sm:text-lg font-semibold text-gray-900">Recent Appointments</h3>
            </div>
            <div class="p-4 space-y-3">
                @foreach($recentAppointments as $appointment)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center text-white mr-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-sm text-gray-900">{{ $appointment->pet->pet_name ?? 'N/A' }}</p>
                            <p class="text-xs text-gray-500">{{ $appointment->pet->owner->own_name ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full 
                            {{ $appointment->appoint_status == 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ ucfirst($appointment->appoint_status) }}
                        </span>
                        <p class="text-xs text-gray-500 mt-1">{{ \Carbon\Carbon::parse($appointment->appoint_date)->format('M d') }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    // Mini Calendar with Notes
    let currentDate = new Date();
    let selectedDateStr = null;
    let notes = JSON.parse(localStorage.getItem('calendarNotes') || '{}');

    function renderCalendar() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();
        
        document.getElementById('currentMonth').textContent = 
            currentDate.toLocaleString('default', { month: 'long', year: 'numeric' });
        
        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const today = new Date();
        
        let html = '';
        let day = 1;
        
        // Generate calendar grid
        for (let i = 0; i < 6; i++) {
            for (let j = 0; j < 7; j++) {
                if (i === 0 && j < firstDay) {
                    html += '<div class="aspect-square"></div>';
                } else if (day > daysInMonth) {
                    html += '<div class="aspect-square"></div>';
                } else {
                    const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                    const isToday = day === today.getDate() && month === today.getMonth() && year === today.getFullYear();
                    const hasNotes = notes[dateStr] && notes[dateStr].length > 0;
                    
                    html += `
                        <div class="aspect-square p-1">
                            <button 
                                onclick="selectDate('${dateStr}')" 
                                class="w-full h-full rounded-lg text-sm font-medium transition-all hover:bg-gray-100
                                ${isToday ? 'bg-[#f88e28] text-white hover:bg-[#e67e22]' : 'text-gray-700'}
                                ${hasNotes ? 'ring-2 ring-[#f88e28] ring-opacity-50' : ''}
                                ${selectedDateStr === dateStr ? 'ring-2 ring-[#f88e28]' : ''}">
                                ${day}
                                ${hasNotes ? '<div class="w-1 h-1 bg-[#f88e28] rounded-full mx-auto mt-0.5"></div>' : ''}
                            </button>
                        </div>
                    `;
                    day++;
                }
            }
            if (day > daysInMonth) break;
        }
        
        document.getElementById('calendarDays').innerHTML = html;
    }

    function selectDate(dateStr) {
        selectedDateStr = dateStr;
        const date = new Date(dateStr + 'T00:00:00');
        document.getElementById('selectedDate').textContent = 
            date.toLocaleDateString('default', { month: 'short', day: 'numeric', year: 'numeric' });
        
        renderNotes(dateStr);
        renderCalendar();
    }

    function renderNotes(dateStr) {
        const notesList = document.getElementById('notesList');
        const dateNotes = notes[dateStr] || [];
        
        if (dateNotes.length === 0) {
            notesList.innerHTML = '<p class="text-xs text-gray-500">No notes for this date</p>';
        } else {
            notesList.innerHTML = dateNotes.map((note, index) => `
                <div class="p-2 bg-gray-50 rounded-lg group relative">
                    <p class="text-xs text-gray-700 pr-6">${note}</p>
                    <button 
                        onclick="deleteNote('${dateStr}', ${index})" 
                        class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 text-red-500 hover:text-red-700 transition-opacity">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            `).join('');
        }
    }

    function deleteNote(dateStr, index) {
        if (confirm('Delete this note?')) {
            notes[dateStr].splice(index, 1);
            if (notes[dateStr].length === 0) {
                delete notes[dateStr];
            }
            localStorage.setItem('calendarNotes', JSON.stringify(notes));
            renderNotes(dateStr);
            renderCalendar();
        }
    }

    // Calendar controls
    document.getElementById('prevMonth').addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar();
    });

    document.getElementById('nextMonth').addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar();
    });

    document.getElementById('addNoteBtn').addEventListener('click', () => {
        if (!selectedDateStr) {
            alert('Please select a date first');
            return;
        }
        document.getElementById('addNoteSection').classList.remove('hidden');
        document.getElementById('addNoteBtn').classList.add('hidden');
        document.getElementById('noteInput').focus();
    });

    document.getElementById('saveNote').addEventListener('click', () => {
        const noteText = document.getElementById('noteInput').value.trim();
        if (noteText) {
            if (!notes[selectedDateStr]) {
                notes[selectedDateStr] = [];
            }
            notes[selectedDateStr].push(noteText);
            localStorage.setItem('calendarNotes', JSON.stringify(notes));
            document.getElementById('noteInput').value = '';
            renderNotes(selectedDateStr);
            renderCalendar();
            document.getElementById('addNoteSection').classList.add('hidden');
            document.getElementById('addNoteBtn').classList.remove('hidden');
        }
    });

    document.getElementById('cancelNote').addEventListener('click', () => {
        document.getElementById('noteInput').value = '';
        document.getElementById('addNoteSection').classList.add('hidden');
        document.getElementById('addNoteBtn').classList.remove('hidden');
    });

    // Make selectDate and deleteNote available globally
    window.selectDate = selectDate;
    window.deleteNote = deleteNote;

    // Initialize calendar
    renderCalendar();

    // Chart Options
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

    // Revenue Chart
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

    // Weekly Chart
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

    // Appointments Trend Chart
    new Chart(document.getElementById('appointmentsChart'), {
        type: 'line',
        data: {
            labels: {!! json_encode($months ?? ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']) !!},
            datasets: [{
                label: 'Appointments',
                data: [45, 52, 48, 65, 70, 68, 75, 80, 72, 85, 78, 82],
                borderColor: '#F59E0B',
                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                tension: 0.4,
                fill: true,
                borderWidth: 3,
                pointBackgroundColor: '#F59E0B',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4
            }]
        },
        options: chartOptions
    });

    // Branch Comparison Chart
    new Chart(document.getElementById('branchComparisonChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($topBranches->pluck('branch_name')->take(5)) !!},
            datasets: [{
                label: 'Services',
                data: {!! json_encode($branchPerformance->take(5)->pluck('services_count')) !!},
                backgroundColor: '#3B82F6',
                borderRadius: 6
            }, {
                label: 'Products',
                data: {!! json_encode($branchPerformance->take(5)->pluck('products_count')) !!},
                backgroundColor: '#10B981',
                borderRadius: 6
            }]
        },
        options: {
            ...chartOptions,
            plugins: {
                ...chartOptions.plugins,
                legend: { display: true, position: 'bottom' }
            },
            scales: {
                ...chartOptions.scales,
                y: {
                    ...chartOptions.scales.y,
                    ticks: {
                        ...chartOptions.scales.y.ticks,
                        callback: value => value
                    }
                }
            }
        }
    });

    // Staff Distribution Pie Chart
    new Chart(document.getElementById('staffPieChart'), {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($topBranches->pluck('branch_name')->take(5)) !!},
            datasets: [{
                data: {!! json_encode($branchPerformance->take(5)->pluck('staff_count')) !!},
                backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        padding: 10,
                        font: { size: 10 }
                    }
                }
            }
        }
    });

    // Revenue Source Chart
    new Chart(document.getElementById('revenueSourceChart'), {
        type: 'doughnut',
        data: {
            labels: ['Services', 'Products', 'Appointments'],
            datasets: [{
                data: [45, 35, 20],
                backgroundColor: ['#10B981', '#3B82F6', '#F59E0B'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        padding: 10,
                        font: { size: 10 }
                    }
                }
            }
        }
    });

    // Pet Types Chart
    new Chart(document.getElementById('petTypesChart'), {
        type: 'doughnut',
        data: {
            labels: ['Dogs', 'Cats', 'Birds', 'Others'],
            datasets: [{
                data: [45, 30, 15, 10],
                backgroundColor: ['#F59E0B', '#8B5CF6', '#06B6D4', '#EC4899'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        padding: 10,
                        font: { size: 10 }
                    }
                }
            }
        }
    });

    // Growth Rate Chart
    new Chart(document.getElementById('growthChart'), {
        type: 'line',
        data: {
            labels: {!! json_encode($months ?? ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']) !!},
            datasets: [{
                label: 'Growth %',
                data: [5, 8, 12, 10, 15, 18, 14, 20, 17, 22, 19, 25],
                borderColor: '#10B981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.4,
                fill: true,
                borderWidth: 3,
                pointBackgroundColor: '#10B981',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4
            }]
        },
        options: {
            ...chartOptions,
            scales: {
                ...chartOptions.scales,
                y: {
                    ...chartOptions.scales.y,
                    ticks: {
                        ...chartOptions.scales.y.ticks,
                        callback: value => value + '%'
                    }
                }
            }
        }
    });

    // Customer Activity Chart
    new Chart(document.getElementById('customerChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($months ?? ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']) !!},
            datasets: [{
                label: 'Active Owners',
                data: [120, 145, 160, 175, 190, 200, 185, 210, 195, 220, 205, 230],
                backgroundColor: '#8B5CF6',
                borderRadius: 8,
                borderSkipped: false
            }]
        },
        options: {
            ...chartOptions,
            scales: {
                ...chartOptions.scales,
                y: {
                    ...chartOptions.scales.y,
                    ticks: {
                        ...chartOptions.scales.y.ticks,
                        callback: value => value
                    }
                }
            }
        }
    });
</script>
@endpushgetElementById('weeklyChart'), {
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
</script>
@endpush
@endsection