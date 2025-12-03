@extends('AdminBoard')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Dashboard Metrics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {{-- Total Visits --}}
        <div class="bg-white rounded-xl shadow-lg p-6 hover-lift">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Total Visits</p>
                    <h3 class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($totalVisits ?? 0) }}</h3>
                </div>
                <div class="bg-blue-100 rounded-full p-4">
                    <i class="fas fa-calendar-check text-blue-600 text-2xl"></i>
                </div>
            </div>
        </div>

        {{-- Today's Visits --}}
        <div class="bg-white rounded-xl shadow-lg p-6 hover-lift">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Today's Visits</p>
                    <h3 class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($todaysVisits ?? 0) }}</h3>
                </div>
                <div class="bg-green-100 rounded-full p-4">
                    <i class="fas fa-clock text-green-600 text-2xl"></i>
                </div>
            </div>
        </div>

        {{-- Total Pets --}}
        <div class="bg-white rounded-xl shadow-lg p-6 hover-lift">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Total Pets</p>
                    <h3 class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($totalPets ?? 0) }}</h3>
                </div>
                <div class="bg-purple-100 rounded-full p-4">
                    <i class="fas fa-paw text-purple-600 text-2xl"></i>
                </div>
            </div>
        </div>

        {{-- Daily Sales --}}
        <div class="bg-white rounded-xl shadow-lg p-6 hover-lift">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Daily Revenue</p>
                    <h3 class="text-3xl font-bold text-gray-900 mt-2">₱{{ number_format($dailySales ?? 0, 2) }}</h3>
                </div>
                <div class="bg-amber-100 rounded-full p-4">
                    <i class="fas fa-money-bill-wave text-amber-600 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Additional dashboard content can go here --}}
</div>
@endsection
