@extends('AdminBoard')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<div class="min-h-screen bg-gray-50 animate-fadeInScale px-2 sm:px-4 lg:px-6">
    
    <div id="welcomeCard" 
        class="w-full bg-white shadow-xl rounded-xl p-4 sm:p-6 mb-4 sm:mb-6 animate-fadeSlideUp">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-0">
            
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-[#f88e28]">
                    Welcome back, {{ Auth::user()->user_name ?? 'User' }}!
                </h1>
                <p class="text-gray-600 mt-1 text-sm sm:text-base">
                    {{ now()->format('l, F j, Y') }}
                </p>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                <div class="bg-gradient-to-r from-[#f88e28] to-[#ff6b35] text-white px-4 py-2 rounded-lg shadow-md">
                    <p class="text-sm font-bold">{{ $branchName ?? 'Main Branch' }}</p>
                </div>
            </div>

        </div>
    </div>

    <style>
        .calendar-notification {
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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

        /* Mobile scrollbar styling */
        .overflow-x-auto::-webkit-scrollbar {
            height: 6px;
        }
        .overflow-x-auto::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        .overflow-x-auto::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        .overflow-x-auto::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>

    {{-- Success Message --}}
    @if (session('success'))
        <div class="mb-3 text-xs sm:text-sm text-green-600 bg-green-100 border border-green-300 rounded px-3 py-2">
            {{ session('success') }}
        </div>
    @endif

    {{-- Error Message --}}
    @if (session('error'))
        <div class="mb-3 text-xs sm:text-sm text-red-600 bg-red-100 border border-red-300 rounded px-3 py-2">
            {{ session('error') }}
        </div>
    @endif

    {{-- Key Metrics - Mobile Responsive Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 lg:gap-6 mb-6 sm:mb-8">
        @php
            $keyMetrics = [
                [
                    'label' => 'Total Appointments', 
                    'value' => $totalAppointments,
                    'icon' => '📅',
                    'color' => 'from-blue-500 to-blue-600',
                    'change' => '+12%',
                    'route' => route('medical.index') . '?tab=appointments'
                ],
                [
                    'label' => "Today's Appointments", 
                    'value' => $todaysAppointments,
                    'icon' => '🕒',
                    'color' => 'from-emerald-500 to-emerald-600',
                    'change' => '+8%',
                    'route' => route('medical.index') . '?tab=appointments'
                ],
                [
                    'label' => 'Total Pet Owners', 
                    'value' => $totalOwners,
                    'icon' => '👥',
                    'color' => 'from-purple-500 to-purple-600',
                    'change' => '+5%',
                    'route' => route('pet-management.index') . '?tab=owners'
                ],
                [
                    'label' => 'Daily Revenue', 
                    'value' => '₱' . number_format($dailySales, 2),
                    'icon' => '💰',
                    'color' => 'from-amber-500 to-amber-600',
                    'change' => '+15%',
                    'route' => route('sales.index'). '?tab=orders'
                ],
            ];
        @endphp

        @foreach ($keyMetrics as $metric)
            <a href="{{ $metric['route'] }}" class="block transform transition-all duration-300 hover:scale-105">
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
                            <p class="text-xl sm:text-2xl font-bold text-gray-900 group-hover:text-blue-600 transition-colors">{{ $metric['value'] }}</p>
                        </div>
                        <div class="mt-2 sm:mt-3 text-xs text-gray-500 group-hover:text-blue-600 transition-colors flex items-center">
                            <span>View details</span>
                            <svg class="w-3 h-3 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </a>
        @endforeach
    </div>

    {{-- Vaccination Overview Section (Unified) --}}
    <div class="bg-white rounded-xl sm:rounded-2xl shadow-sm border border-gray-200 mb-6 sm:mb-8">
        <div class="p-4 sm:p-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-4 sm:mb-6 gap-3 sm:gap-0">
                <div>
                    <h2 class="text-lg sm:text-xl font-semibold text-gray-900">Pet Vaccination Overview</h2>
                    <p class="text-xs sm:text-sm text-gray-500 mt-1">Track vaccination status across all registered pets</p>
                </div>
                <a href="{{ route('medical.index') }}?tab=vaccinations" class="px-3 sm:px-4 py-2 bg-blue-500 text-white text-xs sm:text-sm rounded-lg hover:bg-blue-600 transition-colors">
                    Manage Vaccinations
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                {{-- Vaccination Status Chart --}}
                <div>
                    <h3 class="text-sm sm:text-base font-medium text-gray-700 mb-3 sm:mb-4">Overall Vaccination Status</h3>
                    <div class="h-48 sm:h-64 flex items-center justify-center">
                        <canvas id="vaccinationStatusChart"></canvas>
                    </div>
                </div>

                {{-- Vaccination Types Distribution --}}
                <div>
                    <h3 class="text-sm sm:text-base font-medium text-gray-700 mb-3 sm:mb-4">Top 5 Common Vaccinations</h3>
                    <div class="h-48 sm:h-64 flex items-center justify-center">
                        <canvas id="vaccinationTypesChart"></canvas>
                    </div>
                </div>
            </div>

            {{-- Vaccination Statistics Grid (All Pets) --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mt-4 sm:mt-6">
                <div class="bg-green-50 rounded-lg p-3 sm:p-4 border border-green-200">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-lg sm:text-xl">✅</span>
                        <span class="text-xs sm:text-sm font-medium text-gray-600">Up to Date</span>
                    </div>
                    <p class="text-xl sm:text-2xl font-bold text-green-600">{{ $vaccinationStats['upToDate'] ?? 0 }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ round(($vaccinationStats['upToDate'] ?? 0) / max(($vaccinationStats['total'] ?? 1), 1) * 100) }}% of pets</p>
                </div>

                <div class="bg-yellow-50 rounded-lg p-3 sm:p-4 border border-yellow-200">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-lg sm:text-xl">⏰</span>
                        <span class="text-xs sm:text-sm font-medium text-gray-600">Due Soon</span>
                    </div>
                    <p class="text-xl sm:text-2xl font-bold text-yellow-600">{{ $vaccinationStats['dueSoon'] ?? 0 }}</p>
                    <p class="text-xs text-gray-500 mt-1">Within 30 days</p>
                </div>

                <div class="bg-red-50 rounded-lg p-3 sm:p-4 border border-red-200">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-lg sm:text-xl">⚠️</span>
                        <span class="text-xs sm:text-sm font-medium text-gray-600">Overdue</span>
                    </div>
                    <p class="text-xl sm:text-2xl font-bold text-red-600">{{ $vaccinationStats['overdue'] ?? 0 }}</p>
                    <p class="text-xs text-gray-500 mt-1">Needs attention</p>
                </div>

                <div class="bg-gray-50 rounded-lg p-3 sm:p-4 border border-gray-200">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-lg sm:text-xl">📊</span>
                        <span class="text-xs sm:text-sm font-medium text-gray-600">Total Records</span>
                    </div>
                    <p class="text-xl sm:text-2xl font-bold text-gray-900">{{ $vaccinationStats['total'] ?? 0 }}</p>
                    <p class="text-xs text-gray-500 mt-1">All vaccinations</p>
                </div>
            </div>

            {{-- Upcoming Vaccinations Table (Unified with Species) --}}
            <div class="mt-4 sm:mt-6">
                <h3 class="text-sm sm:text-base font-medium text-gray-700 mb-3">Upcoming Vaccinations</h3>
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="w-full min-w-[700px]">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 sm:px-4 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase">Pet Name</th>
                                <th class="px-3 sm:px-4 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase">Species</th>
                                <th class="px-3 sm:px-4 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase">Vaccine</th>
                                <th class="px-3 sm:px-4 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
                                <th class="px-3 sm:px-4 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($upcomingVaccinations ?? [] as $vaccination)
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 sm:px-4 py-3 text-xs sm:text-sm font-medium text-gray-900">{{ $vaccination->pet_name ?? 'N/A' }}</td>
                                <td class="px-3 sm:px-4 py-3 text-xs sm:text-sm text-gray-600">
                                    @if (strtolower($vaccination->pet_species ?? 'n/a') === 'dog')
                                    Dog
                                    @elseif (strtolower($vaccination->pet_species ?? 'n/a') === 'cat')
                                    Cat
                                    @else
                                        {{ $vaccination->pet_species ?? 'N/A' }}
                                    @endif
                                </td>
                                <td class="px-3 sm:px-4 py-3 text-xs sm:text-sm text-gray-600">{{ $vaccination->vaccine_name ?? 'N/A' }}</td>
                                <td class="px-3 sm:px-4 py-3 text-xs sm:text-sm text-gray-600">{{ $vaccination->due_date ?? 'N/A' }}</td>
                               <td class="px-3 sm:px-4 py-3">
    @php
        $dueDate = \Carbon\Carbon::parse($vaccination->due_date);
        $today = \Carbon\Carbon::today();

        // Days until due (negative if past, 0 if today, positive if future)
        $daysUntilDue = $dueDate->diffInDays($today, false);
        
        // --- Define Time Windows ---
        // 1. Critical Overdue starts 7 days AFTER the due date has passed (i.e., today is > due date + 7 days)
        $criticalOverdueStart = $dueDate->copy()->addDays(7);
        
        // 2. Due Soon starts 14 days BEFORE the due date.
        $dueSoonStart = $dueDate->copy()->subDays(14); 

        // 1. CRITICALLY OVERDUE (Past 7-day Overdue window)
        if ($criticalOverdueStart->lt($today)) {
            $statusClass = 'bg-red-100 text-red-800';
            $daysLate = $today->diffInDays($criticalOverdueStart);
            $statusText = 'OVERDUE (Missed by ' . $daysLate . ' days)';
        }
        // 2. OVERDUE GRACE PERIOD (Past due date, but within 7 days)
        elseif ($dueDate->lt($today)) {
            $statusClass = 'bg-orange-100 text-orange-800';
            $daysPast = $today->diffInDays($dueDate);
            $daysRemaining = 7 - $daysPast;
            $statusText = 'Overdue Grace (' . $daysRemaining . ' days left)';
        }
        // 3. DUE SOON (Within the 14-day window: $dueSoonStart to $dueDate)
        elseif ($dueSoonStart->lte($today)) {
            $statusClass = 'bg-yellow-100 text-yellow-800';
            $daysUntil = $today->diffInDays($dueDate);
            $statusText = 'Due Soon (' . ($daysUntil === 0 ? 'Today' : 'in ' . $daysUntil . ' days') . ')';
        }
        // 4. UPCOMING (More than 14 days out)
        else {
            $statusClass = 'bg-blue-100 text-blue-800';
            $statusText = 'Upcoming';
        }
    @endphp
    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full {{ $statusClass }}">
        {{ $statusText }}
    </span>
</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">
                                    No upcoming vaccinations scheduled
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Calendar Section - Mobile Responsive --}}
    <div class="bg-white rounded-xl sm:rounded-2xl shadow-sm border border-gray-200 mb-6 sm:mb-8">
        <div class="p-4 sm:p-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-4 sm:mb-6 gap-3 sm:gap-0">
                <h2 class="text-lg sm:text-xl font-semibold text-gray-900">Appointment Calendar</h2>
                
                {{-- Calendar Controls - Mobile Responsive --}}
                <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto"> 
                    <button id="monthlyBtn" class="px-2.5 sm:px-3 py-1.5 text-xs sm:text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-[#0f7ea0] transition-colors">Monthly</button>
                    <button id="weeklyBtn" class="px-2.5 sm:px-3 py-1.5 text-xs sm:text-sm font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">Weekly</button>
                    <button id="todayBtn" class="px-2.5 sm:px-3 py-1.5 text-xs sm:text-sm font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">Today</button>
                    <div class="flex space-x-1 ml-auto sm:ml-2">
                        <button id="prevBtn" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">←</button>
                        <button id="nextBtn" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">→</button>
                    </div>
                </div>
            </div>
            
            <div id="calendar">
                <div id="calendarHeader" class="text-base sm:text-lg font-semibold text-gray-900 text-center mb-3 sm:mb-4"></div>
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="w-full min-w-[640px]">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
                                    <th class="px-2 sm:px-3 py-2 sm:py-3 text-xs sm:text-sm font-medium text-gray-700 text-center">{{ $day }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody id="calendarBody" class="divide-y divide-gray-200"></tbody>
                    </table>
                </div>
            </div>
            
            {{-- Legend - Mobile Responsive --}}
            <div class="mt-3 sm:mt-4 flex flex-wrap items-center gap-2 sm:gap-4 text-xs sm:text-sm">
                <div class="flex items-center gap-1.5 sm:gap-2">
                    <span class="inline-block w-3 h-3 sm:w-4 sm:h-4 bg-green-100 border border-green-300 rounded"></span> 
                    <span class="text-xs sm:text-sm">Arrived</span>
                </div>
                <div class="flex items-center gap-1.5 sm:gap-2">
                    <span class="inline-block w-3 h-3 sm:w-4 sm:h-4 bg-yellow-100 border border-yellow-300 rounded"></span> 
                    <span class="text-xs sm:text-sm">Pending</span>
                </div>
                <div class="flex items-center gap-1.5 sm:gap-2">
                    <span class="inline-block w-3 h-3 sm:w-4 sm:h-4 bg-orange-100 border border-orange-300 rounded"></span> 
                    <span class="text-xs sm:text-sm">Reschedule</span>
                </div>
                <div class="flex items-center gap-1.5 sm:gap-2">
                    <span class="inline-block w-3 h-3 sm:w-4 sm:h-4 bg-red-100 border border-red-300 rounded"></span> 
                    <span class="text-xs sm:text-sm">Missed</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Section - Mobile Responsive --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 mb-6 sm:mb-8">
        {{-- Daily Revenue Chart --}}
        <div class="bg-white rounded-xl sm:rounded-2xl shadow-sm border border-gray-200 p-4 sm:p-6">
            <div class="flex items-center justify-between mb-4 sm:mb-6">
                <h3 class="text-base sm:text-lg font-semibold text-gray-900">Daily Revenue</h3>
                <span class="text-xs sm:text-sm text-gray-500">Last 7 days</span>
            </div>
            <div class="h-48 sm:h-64">
                <canvas id="dailyOrdersChart"></canvas>
            </div>
        </div>
        
        {{-- Monthly Overview --}}
        <div class="bg-white rounded-xl sm:rounded-2xl shadow-sm border border-gray-200 p-4 sm:p-6">
            <div class="flex items-center justify-between mb-4 sm:mb-6">
                <h3 class="text-base sm:text-lg font-semibold text-gray-900">Monthly Overview</h3>
                <span class="text-xs sm:text-sm text-gray-500">This year</span>
            </div>
            <div class="h-48 sm:h-64">
                <canvas id="monthlyOrdersChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Recent Activity Tables - Mobile Responsive --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
        {{-- Recent Appointments --}}
        <div class="bg-white rounded-xl sm:rounded-2xl shadow-sm border border-gray-200">
            <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                <h3 class="text-base sm:text-lg font-semibold text-gray-900">Recent Appointments</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[500px]">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet</th>
                            <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($recentAppointments->take(5) as $appointment)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm text-gray-900">{{ $appointment->appoint_date }}</td>
                                <td class="px-4 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm text-gray-600">{{ $appointment->pet?->pet_name ?? 'N/A' }}</td>
                                <td class="px-4 sm:px-6 py-3 sm:py-4">
                                    @php
                                        $statusColors = [
                                            'arrived' => 'bg-green-100 text-green-800',
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'rescheduled' => 'bg-blue-100 text-blue-800'
                                        ];
                                        $status = strtolower($appointment->appoint_status);
                                        $colorClass = $statusColors[$status] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full {{ $colorClass }}">
                                        {{ ucfirst($appointment->appoint_status) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Recent Referrals --}}
        <div class="bg-white rounded-xl sm:rounded-2xl shadow-sm border border-gray-200">
            <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200">
                <h3 class="text-base sm:text-lg font-semibold text-gray-900">Recent Referrals</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[500px]">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-4 sm:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($recentReferrals->take(5) as $ref)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm text-gray-900">{{ $ref->ref_date }}</td>
                                <td class="px-4 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm text-gray-600">{{ Str::limit($ref->ref_description, 30) }}</td>
                                <td class="px-4 sm:px-6 py-3 sm:py-4">
                                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                        {{ ucfirst($ref->ref_status ?? 'Pending') }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

{{-- Modals (Appointment and Visit) - keeping original code --}}
<div id="appointmentModal" class="fixed inset-0 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl sm:rounded-2xl shadow-xl max-w-md w-full">
        <div class="p-4 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base sm:text-lg font-semibold text-gray-900">Appointment Details</h3>
                <button id="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div id="appointmentDetails" class="space-y-3 mb-4 sm:mb-6 text-sm sm:text-base">
            </div>
            
            <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
                <button id="viewProfileBtn" class="flex-1 px-4 py-2 bg-blue-500 text-white text-sm sm:text-base rounded-lg hover:bg-blue-600 transition-colors">
                    View Profile
                </button>
                <button id="closeModalBtn" class="px-4 py-2 border border-gray-300 text-gray-700 text-sm sm:text-base rounded-lg hover:bg-gray-50 transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<div id="visitModal" class="fixed inset-0 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl sm:rounded-2xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
        <div class="p-4 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base sm:text-lg font-semibold text-gray-900">Update Pet Visit</h3>
                <button id="closeVisitModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div id="petProfile" class="space-y-3 sm:space-y-4 mb-4 sm:mb-6 text-sm">
            </div>
            
            <div class="space-y-3 sm:space-y-4">
                <div>
                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">Visit Status</label>
                    <select id="visitStatus" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="pending">Pending</option>
                        <option value="arrived">Arrived</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">Visit Notes</label>
                    <textarea id="visitNotes" rows="3" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Add visit notes..."></textarea>
                </div>
                
                <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
                    <button id="updateVisitBtn" class="flex-1 px-4 py-2 bg-green-500 text-white text-sm sm:text-base rounded-lg hover:bg-green-600 transition-colors">
                        Update Visit
                    </button>
                    <button id="closeVisitModalBtn" class="px-4 py-2 border border-gray-300 text-gray-700 text-sm sm:text-base rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Scripts --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const appointments = {!! $appointments->toJson() !!};
    let viewDate = new Date();
    let currentView = 'monthly';
    let currentAppointment = null;

    const calendarHeader = document.getElementById('calendarHeader');
    const calendarBody = document.getElementById('calendarBody');
    const appointmentModal = document.getElementById('appointmentModal');
    const visitModal = document.getElementById('visitModal');

    function formatDate(date) {
        return date.toISOString().split('T')[0];
    }

    function getStatusColor(status) {
        const colors = {
            'arrive': 'bg-green-100 text-green-700 text-xs px-2 py-1 rounded-full cursor-pointer hover:bg-green-200',
            'arrived': 'bg-green-100 text-green-700 text-xs px-2 py-1 rounded-full cursor-pointer hover:bg-green-200',
            'completed': 'bg-green-100 text-green-700 text-xs px-2 py-1 rounded-full cursor-pointer hover:bg-green-200',
            'pending': 'bg-yellow-100 text-yellow-700 text-xs px-2 py-1 rounded-full cursor-pointer hover:bg-yellow-200',
            'approved': 'bg-yellow-100 text-yellow-700 text-xs px-2 py-1 rounded-full cursor-pointer hover:bg-yellow-200',
            'rescheduled': 'bg-orange-100 text-orange-700 text-xs px-2 py-1 rounded-full cursor-pointer hover:bg-orange-200'
        };
        return colors[(status || 'pending').toLowerCase()] || 'bg-yellow-100 text-yellow-700 text-xs px-2 py-1 rounded-full cursor-pointer hover:bg-yellow-200';
    }

    function updateViewButtons(activeView) {
        const buttons = ['monthlyBtn', 'weeklyBtn', 'todayBtn'];
        buttons.forEach(id => {
            const btn = document.getElementById(id);
            if (id.replace('Btn', '') === activeView) {
                btn.className = 'px-2.5 sm:px-3 py-1.5 text-xs sm:text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600 transition-colors';
            } else {
                btn.className = 'px-2.5 sm:px-3 py-1.5 text-xs sm:text-sm font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors';
            }
        });
    }

    function renderCalendar(view) {
        calendarBody.innerHTML = '';
        currentView = view;
        updateViewButtons(view);

        if (view === 'monthly') {
            calendarHeader.textContent = viewDate.toLocaleString('default', { month: 'long', year: 'numeric' });
            const firstDay = new Date(viewDate.getFullYear(), viewDate.getMonth(), 1);
            const lastDay = new Date(viewDate.getFullYear(), viewDate.getMonth() + 1, 0);
            const startDay = firstDay.getDay();
            const totalDays = lastDay.getDate();

            let day = 1;
            for (let row = 0; row < 6; row++) {
                let html = '<tr>';
                for (let i = 0; i < 7; i++) {
                    if ((row === 0 && i < startDay) || day > totalDays) {
                        html += `<td class="p-2 sm:p-3 h-20 sm:h-24 lg:h-32 align-top border-t border-gray-200"></td>`;
                    } else {
                        const dateObj = new Date(viewDate.getFullYear(), viewDate.getMonth(), day);
                        const dateStr = formatDate(dateObj);
                        const events = appointments[dateStr] || [];

                        let eventsHTML = '';
                        events.slice(0, 3).forEach(event => {
                            const today = new Date();
                            const eventDate = new Date(event.date);

                            let colorClass = getStatusColor(event.status);
                            if (eventDate < today && !['arrived','completed'].includes((event.status || '').toLowerCase())) {
                                colorClass = 'bg-red-100 text-red-700 text-xs px-2 py-1 rounded-full cursor-pointer hover:bg-red-200';
                                event.status = 'missed';
                            }

                            const petName = event.pet_name || 'Unknown Pet';
                            eventsHTML += `<div class="mb-1">
                                <span class="${colorClass}" onclick="openAppointmentModal(${JSON.stringify(event).replace(/"/g, '&quot;')})">
                                    ${petName}
                                </span>
                            </div>`;
                        });

                        if (events.length > 3) {
                            eventsHTML += `<div class="text-xs text-gray-500">+${events.length - 3} more</div>`;
                        }

                        const isToday = formatDate(new Date()) === dateStr;
                        const bgClass = isToday ? 'bg-blue-50' : '';

                        html += `<td class="p-2 sm:p-3 h-20 sm:h-24 lg:h-32 align-top border-t border-gray-200 ${bgClass} hover:bg-gray-50 transition-colors">
                                    <div class="font-medium text-xs sm:text-sm mb-1 ${isToday ? 'text-blue-600' : 'text-gray-900'}">${day}</div>
                                    <div class="space-y-1">${eventsHTML}</div>
                                </td>`;
                        day++;
                    }
                }
                html += '</tr>';
                calendarBody.innerHTML += html;
                if (day > totalDays) break;
            }
        }

        else if (view === 'weekly') {
            calendarHeader.textContent = 'Week of ' + viewDate.toLocaleDateString();
            const weekStart = new Date(viewDate);
            weekStart.setDate(viewDate.getDate() - viewDate.getDay());

            let row = '<tr>';
            for (let i = 0; i < 7; i++) {
                const date = new Date(weekStart);
                date.setDate(weekStart.getDate() + i);
                const dateStr = formatDate(date);
                const events = appointments[dateStr] || [];

                let eventsHTML = '';
                events.forEach(event => {
                    const colorClass = getStatusColor(event.status);
                    const petName = event.pet_name || 'Unknown Pet';
                    eventsHTML += `<div class="mb-1">
                        <span class="${colorClass}" onclick="openAppointmentModal(${JSON.stringify(event).replace(/"/g, '&quot;')})">
                            ${petName}
                        </span>
                    </div>`;
                });

                const isToday = formatDate(new Date()) === dateStr;
                const bgClass = isToday ? 'bg-blue-50' : '';

                row += `<td class="p-2 sm:p-4 h-32 sm:h-40 align-top border-t border-gray-200 ${bgClass} hover:bg-gray-50 transition-colors">
                            <div class="font-medium mb-2 text-xs sm:text-sm ${isToday ? 'text-blue-600' : 'text-gray-900'}">${date.getDate()}</div>
                            <div class="space-y-1">${eventsHTML}</div>
                        </td>`;
            }
            row += '</tr>';
            calendarBody.innerHTML = row;
        }

        else if (view === 'today') {
            calendarHeader.textContent = viewDate.toDateString();
            const todayStr = formatDate(viewDate);
            const events = appointments[todayStr] || [];

            let html = `<tr><td class="p-4 sm:p-6 border-t border-gray-200" colspan="7">`;
            if (events.length) {
                html += '<div class="space-y-2 sm:space-y-3">';
                events.forEach(event => {
                    const colorClass = getStatusColor(event.status);
                    const petName = event.pet_name || 'Unknown Pet';
                    html += `<div class="flex items-center space-x-2 sm:space-x-3">
                        <span class="${colorClass}" onclick="openAppointmentModal(${JSON.stringify(event).replace(/"/g, '&quot;')})">
                            ${petName} - ${event.time || 'No time set'}
                        </span>
                    </div>`;
                });
                html += '</div>';
            } else {
                html += '<div class="text-center text-gray-500 py-6 sm:py-8 text-sm sm:text-base">No appointments today</div>';
            }
            html += `</td></tr>`;
            calendarBody.innerHTML = html;
        }
    }

    document.getElementById('monthlyBtn').onclick = () => renderCalendar('monthly');
    document.getElementById('weeklyBtn').onclick = () => renderCalendar('weekly');
    document.getElementById('todayBtn').onclick = () => renderCalendar('today');
    
    document.getElementById('prevBtn').onclick = () => {
        if (currentView === 'monthly') viewDate.setMonth(viewDate.getMonth() - 1);
        if (currentView === 'weekly') viewDate.setDate(viewDate.getDate() - 7);
        if (currentView === 'today') viewDate.setDate(viewDate.getDate() - 1);
        renderCalendar(currentView);
    };
    
    document.getElementById('nextBtn').onclick = () => {
        if (currentView === 'monthly') viewDate.setMonth(viewDate.getMonth() + 1);
        if (currentView === 'weekly') viewDate.setDate(viewDate.getDate() + 7);
        if (currentView === 'today') viewDate.setDate(viewDate.getDate() + 1);
        renderCalendar(currentView);
    };

    renderCalendar('monthly');

    function openAppointmentModal(appointment) {
        currentAppointment = appointment;
        const modal = document.getElementById('appointmentModal');
        const details = document.getElementById('appointmentDetails');
        
        details.innerHTML = `
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                <div>
                    <span class="text-xs sm:text-sm text-gray-600">Pet Name:</span>
                    <p class="font-medium text-sm sm:text-base">${appointment.pet_name || 'Unknown Pet'}</p>
                </div>
                <div>
                    <span class="text-xs sm:text-sm text-gray-600">Owner:</span>
                    <p class="font-medium text-sm sm:text-base">${appointment.owner_name || 'Unknown Owner'}</p>
                </div>
                <div>
                    <span class="text-xs sm:text-sm text-gray-600">Date:</span>
                    <p class="font-medium text-sm sm:text-base">${appointment.date}</p>
                </div>
                <div>
                    <span class="text-xs sm:text-sm text-gray-600">Time:</span>
                    <p class="font-medium text-sm sm:text-base">${appointment.time || 'No time set'}</p>
                </div>
                <div>
                    <span class="text-xs sm:text-sm text-gray-600">Appointment Type:</span>
                    <p class="font-medium text-sm sm:text-base">${appointment.type || 'Checkup'}</p>
                </div>
                <div>
                    <span class="text-xs sm:text-sm text-gray-600">Status:</span>
                    <span class="${getStatusColor(appointment.status)}">${appointment.status || 'Pending'}</span>
                </div>
                ${appointment.notes ? `
                <div class="col-span-1 sm:col-span-2">
                    <span class="text-xs sm:text-sm text-gray-600">Notes:</span>
                    <p class="font-medium text-sm sm:text-base">${appointment.notes}</p>
                </div>
                ` : ''}
            </div>
        `;
        
        modal.classList.remove('hidden');
    }

    function openVisitModal() {
        if (!currentAppointment) return;
        
        appointmentModal.classList.add('hidden');
        const modal = document.getElementById('visitModal');
        const profile = document.getElementById('petProfile');
        const statusSelect = document.getElementById('visitStatus');
        const notesTextarea = document.getElementById('visitNotes');
        
        profile.innerHTML = `
            <div class="bg-gray-50 rounded-lg p-3 sm:p-4">
                <h4 class="font-semibold text-gray-900 mb-2 sm:mb-3 text-sm sm:text-base">${currentAppointment.pet_name || 'Unknown Pet'}</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3 text-xs sm:text-sm">
                    <div>
                        <span class="text-gray-600">Owner:</span>
                        <span class="font-medium">${currentAppointment.owner_name || 'Unknown Owner'}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">Pet Breed:</span>
                        <span class="font-medium">${currentAppointment.pet_breed || 'N/A'}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">Pet Age:</span>
                        <span class="font-medium">${currentAppointment.pet_age || 'N/A'}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">Gender:</span>
                        <span class="font-medium">${currentAppointment.pet_gender || 'N/A'}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">Date:</span>
                        <span class="font-medium">${currentAppointment.date}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">Time:</span>
                        <span class="font-medium">${currentAppointment.time || 'No time set'}</span>
                    </div>
                    <div class="col-span-1 sm:col-span-2">
                        <span class="text-gray-600">Current Status:</span>
                        <span class="${getStatusColor(currentAppointment.status)}">${currentAppointment.status || 'Pending'}</span>
                    </div>
                </div>
            </div>
        `;
        
        statusSelect.value = currentAppointment.status || 'pending';
        notesTextarea.value = currentAppointment.notes || '';
        
        modal.classList.remove('hidden');
    }

    function updateVisit() {
        if (!currentAppointment) return;
        
        const statusSelect = document.getElementById('visitStatus');
        const notesTextarea = document.getElementById('visitNotes');
        const newStatus = statusSelect.value;
        const newNotes = notesTextarea.value;
        const updateBtn = document.getElementById('updateVisitBtn');
        
        updateBtn.disabled = true;
        updateBtn.textContent = 'Updating...';
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        fetch(`/care-continuity/appointments/${currentAppointment.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                appoint_status: newStatus,
                appoint_description: newNotes,
                appoint_date: currentAppointment.date,
                appoint_time: currentAppointment.time,
                pet_id: currentAppointment.pet_id,
                appoint_type: currentAppointment.type || 'Walk-in'
            })
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error(`HTTP ${response.status}: ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            currentAppointment.status = newStatus;
            currentAppointment.notes = newNotes;
            
            const dateStr = currentAppointment.date;
            if (appointments[dateStr]) {
                const appointmentIndex = appointments[dateStr].findIndex(apt => 
                    apt.id === currentAppointment.id
                );
                
                if (appointmentIndex !== -1) {
                    appointments[dateStr][appointmentIndex].status = newStatus;
                    appointments[dateStr][appointmentIndex].notes = newNotes;
                }
            }
            
            showNotification('Appointment updated successfully!', 'success');
            
            document.getElementById('visitModal').classList.add('hidden');
            renderCalendar(currentView);
            
            updateBtn.disabled = false;
            updateBtn.textContent = 'Update Visit';
        })
        .catch(error => {
            showNotification(`Error: ${error.message}`, 'error');
            
            updateBtn.disabled = false;
            updateBtn.textContent = 'Update Visit';
        });
    }

    function showNotification(message, type = 'success') {
        const existingNotification = document.querySelector('.calendar-notification');
        if (existingNotification) {
            existingNotification.remove();
        }
        
        const notification = document.createElement('div');
        notification.className = `calendar-notification mb-3 sm:mb-4 px-3 sm:px-4 py-2 rounded text-xs sm:text-sm ${
            type === 'success' 
                ? 'text-green-700 bg-green-100 border border-green-400' 
                : 'text-red-700 bg-red-100 border border-red-400'
        }`;
        notification.textContent = message;
        
        const calendarContainer = document.getElementById('calendar');
        calendarContainer.insertBefore(notification, calendarContainer.firstChild);
        
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }

    document.getElementById('closeModal').onclick = () => appointmentModal.classList.add('hidden');
    document.getElementById('closeModalBtn').onclick = () => appointmentModal.classList.add('hidden');
    document.getElementById('viewProfileBtn').onclick = openVisitModal;
    
    document.getElementById('closeVisitModal').onclick = () => visitModal.classList.add('hidden');
    document.getElementById('closeVisitModalBtn').onclick = () => visitModal.classList.add('hidden');
    document.getElementById('updateVisitBtn').onclick = updateVisit;

    appointmentModal.onclick = (e) => {
        if (e.target === appointmentModal) {
            appointmentModal.classList.add('hidden');
        }
    };
    
    visitModal.onclick = (e) => {
        if (e.target === visitModal) {
            visitModal.classList.add('hidden');
        }
    };

    window.openAppointmentModal = openAppointmentModal;

    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { 
                display: false 
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleColor: '#fff',
                bodyColor: '#fff',
                titleFont: {
                    size: window.innerWidth < 640 ? 11 : 12
                },
                bodyFont: {
                    size: window.innerWidth < 640 ? 10 : 11
                },
                padding: window.innerWidth < 640 ? 6 : 10
            }
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { 
                    color: '#6B7280',
                    font: {
                        size: window.innerWidth < 640 ? 9 : 11
                    }
                }
            },
            y: {
                beginAtZero: true,
                grid: { color: '#F3F4F6' },
                ticks: { 
                    color: '#6B7280',
                    font: {
                        size: window.innerWidth < 640 ? 9 : 11
                    },
                    callback: value => '₱' + value.toLocaleString()
                }
            }
        }
    };

    new Chart(document.getElementById('dailyOrdersChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($orderDates) !!},
            datasets: [{
                label: 'Revenue (₱)',
                data: {!! json_encode($orderTotals) !!},
                backgroundColor: '#3B82F6',
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: chartOptions
    });

    new Chart(document.getElementById('monthlyOrdersChart'), {
        type: 'line',
        data: {
            labels: {!! json_encode($months) !!},
            datasets: [{
                label: 'Monthly Revenue (₱)',
                data: {!! json_encode($monthlySalesTotals) !!},
                borderColor: '#10B981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: window.innerWidth < 640 ? 2 : 3,
                pointBackgroundColor: '#10B981',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: window.innerWidth < 640 ? 4 : 6
            }]
        },
        options: {
            ...chartOptions,
            plugins: {
                ...chartOptions.plugins,
                tooltip: {
                    ...chartOptions.plugins.tooltip,
                    borderColor: '#10B981',
                    borderWidth: 1
                }
            }
        }
    });

    // Vaccination Charts
    new Chart(document.getElementById('vaccinationStatusChart'), {
        type: 'doughnut',
        data: {
            labels: ['Up to Date', 'Due Soon', 'Overdue'],
            datasets: [{
                data: [
                    {{ $vaccinationStats['upToDate'] ?? 0 }},
                    {{ $vaccinationStats['dueSoon'] ?? 0 }},
                    {{ $vaccinationStats['overdue'] ?? 0 }}
                ],
                backgroundColor: ['#10B981', '#F59E0B', '#EF4444'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: {
                            size: window.innerWidth < 640 ? 10 : 12
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: window.innerWidth < 640 ? 8 : 12,
                    titleFont: {
                        size: window.innerWidth < 640 ? 11 : 13
                    },
                    bodyFont: {
                        size: window.innerWidth < 640 ? 10 : 12
                    },
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });

    new Chart(document.getElementById('vaccinationTypesChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($vaccinationTypes['labels'] ?? ['Rabies', 'Distemper', 'Parvovirus', 'Hepatitis', 'Leptospirosis']) !!},
            datasets: [{
                label: 'Vaccinations',
                data: {!! json_encode($vaccinationTypes['data'] ?? [45, 38, 32, 28, 25]) !!},
                backgroundColor: '#8B5CF6',
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { 
                    display: false 
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    titleFont: {
                        size: window.innerWidth < 640 ? 11 : 12
                    },
                    bodyFont: {
                        size: window.innerWidth < 640 ? 10 : 11
                    },
                    padding: window.innerWidth < 640 ? 6 : 10
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { 
                        color: '#6B7280',
                        font: {
                            size: window.innerWidth < 640 ? 9 : 11
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: '#F3F4F6' },
                    ticks: { 
                        color: '#6B7280',
                        font: {
                            size: window.innerWidth < 640 ? 9 : 11
                        },
                        stepSize: 10
                    }
                }
            }
        }
    });

    window.addEventListener('resize', () => {
        const charts = Chart.instances;
        Object.values(charts).forEach(chart => {
            if (chart) {
                chart.options.plugins.tooltip.titleFont.size = window.innerWidth < 640 ? 11 : 12;
                chart.options.plugins.tooltip.bodyFont.size = window.innerWidth < 640 ? 10 : 11;
                chart.options.plugins.tooltip.padding = window.innerWidth < 640 ? 6 : 10;
                
                if (chart.options.scales) {
                    chart.options.scales.x.ticks.font.size = window.innerWidth < 640 ? 9 : 11;
                    chart.options.scales.y.ticks.font.size = window.innerWidth < 640 ? 9 : 11;
                }
                
                if (chart.config.type === 'line') {
                    chart.data.datasets[0].borderWidth = window.innerWidth < 640 ? 2 : 3;
                    chart.data.datasets[0].pointRadius = window.innerWidth < 640 ? 4 : 6;
                }
                
                chart.update();
            }
        });
    });
</script>
@endsection