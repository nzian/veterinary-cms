@extends('AdminBoard')

@section('content')
<div class="min-h-screen bg-gray-50 px-4 sm:px-6 lg:px-8 py-6">

    {{-- WELCOME HEADER --}}
    <div class="bg-white shadow rounded-xl p-5 mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-[#f88e28]">
                Welcome back, {{ Auth::user()->user_name }}!
            </h1>
            <p class="text-gray-600 mt-1 text-sm">{{ now()->format('l, F j, Y') }}</p>
        </div>
        <div class="mt-3 sm:mt-0">
            <span class="bg-gradient-to-r from-[#f88e28] to-[#ff6b35] text-white px-4 py-2 rounded-lg shadow">
                Super Admin Panel
            </span>
        </div>
    </div>

    {{-- TOP METRICS --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        @php
            $superMetrics = [
                ['label' => 'Total Branches', 'value' => $totalBranches, 'icon' => '🏢', 'color' => 'from-indigo-500 to-indigo-600', 'route' => route('branches.index')],
                ['label' => 'Total Users', 'value' => $totalUsers, 'icon' => '👤', 'color' => 'from-emerald-500 to-emerald-600', 'route' => route('user.index')],
                ['label' => 'Total Sales', 'value' => '₱' . number_format($totalSales, 2), 'icon' => '💰', 'color' => 'from-amber-500 to-amber-600', 'route' => route('sales.index')],
                ['label' => 'Total Appointments', 'value' => $totalAppointments, 'icon' => '📅', 'color' => 'from-blue-500 to-blue-600', 'route' => route('appointments.index')],
            ];
        @endphp

        @foreach ($superMetrics as $metric)
            <a href="{{ $metric['route'] }}" class="block transform transition duration-300 hover:scale-105">
                <div class="relative bg-white rounded-xl shadow border border-gray-200 p-5 group">
                    <div class="absolute inset-0 bg-gradient-to-br {{ $metric['color'] }} opacity-0 group-hover:opacity-10 transition"></div>
                    <div class="flex justify-between items-center mb-3">
                        <span class="text-2xl">{{ $metric['icon'] }}</span>
                    </div>
                    <p class="text-gray-600 text-sm">{{ $metric['label'] }}</p>
                    <h2 class="text-2xl font-bold text-gray-900 group-hover:text-blue-600">{{ $metric['value'] }}</h2>
                </div>
            </a>
        @endforeach
    </div>

    {{-- BRANCH STATISTICS --}}
    <div class="bg-white rounded-xl shadow border border-gray-200 mb-8">
        <div class="px-5 py-4 border-b">
            <h2 class="text-lg font-semibold text-gray-900">📊 Branch Statistics Overview</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[700px]">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Branch Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Users</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Appointments</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Sales</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($branchStats as $branch)
                        <tr>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $branch->branch_name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $branch->user_count }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $branch->appointments_count }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">₱{{ number_format($branch->total_sales, 2) }}</td>
                            <td class="px-4 py-3 text-sm">
                                <a href="{{ route('branch.show', $branch->branch_id) }}" class="text-blue-600 hover:underline">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- CHARTS --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-8">
        <div class="bg-white rounded-xl shadow border border-gray-200 p-5">
            <div class="flex justify-between mb-3">
                <h3 class="text-base font-semibold text-gray-900">📅 Appointments Per Branch</h3>
            </div>
            <canvas id="appointmentsChart"></canvas>
        </div>

        <div class="bg-white rounded-xl shadow border border-gray-200 p-5">
            <div class="flex justify-between mb-3">
                <h3 class="text-base font-semibold text-gray-900">💰 Sales Per Branch</h3>
            </div>
            <canvas id="salesChart"></canvas>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const branchNames = {!! json_encode($branchStats->pluck('branch_name')) !!};
    const branchAppointments = {!! json_encode($branchStats->pluck('appointments_count')) !!};
    const branchSales = {!! json_encode($branchStats->pluck('total_sales')) !!};

    new Chart(document.getElementById('appointmentsChart'), {
        type: 'bar',
        data: {
            labels: branchNames,
            datasets: [{
                label: 'Appointments',
                data: branchAppointments,
            }]
        },
    });

    new Chart(document.getElementById('salesChart'), {
        type: 'bar',
        data: {
            labels: branchNames,
            datasets: [{
                label: 'Sales (₱)',
                data: branchSales,
            }]
        },
    });
</script>
@endsection
