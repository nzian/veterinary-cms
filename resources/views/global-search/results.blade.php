@extends('AdminBoard')

@section('content')
<div class="container mx-auto px-6 py-8">
    <!-- Search Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">
                    Search Results
                </h1>
                <p class="text-gray-600">
                    @if(!empty($query))
                        Found <span class="font-semibold text-[#ff8c42]">{{ $resultCounts['total'] ?? 0 }}</span> results for "<span class="font-semibold">{{ $query }}</span>"
                    @else
                        Enter a search query to begin
                    @endif
                </p>
            </div>
            <a href="{{ route('dashboard-index') }}" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>
    </div>

    @if(!empty($query))
        <!-- Filter Tabs -->
        <div class="mb-6">
            <div class="flex flex-wrap gap-3">
                <button onclick="filterResults('all')" class="filter-btn active px-4 py-2 rounded-lg bg-[#ff8c42] text-white font-medium transition-all hover:bg-[#875e0cff]">
                    All Results ({{ $resultCounts['total'] }})
                </button>
                @if($resultCounts['pets'] > 0)
                <button onclick="filterResults('Pet')" class="filter-btn px-4 py-2 rounded-lg bg-gray-200 text-gray-700 font-medium transition-all hover:bg-gray-300">
                    <i class="fas fa-paw mr-2"></i>Pets ({{ $resultCounts['pets'] }})
                </button>
                @endif
                @if($resultCounts['owners'] > 0)
                <button onclick="filterResults('Owner')" class="filter-btn px-4 py-2 rounded-lg bg-gray-200 text-gray-700 font-medium transition-all hover:bg-gray-300">
                    <i class="fas fa-user mr-2"></i>Owners ({{ $resultCounts['owners'] }})
                </button>
                @endif
                @if($resultCounts['appointments'] > 0)
                <button onclick="filterResults('Appointment')" class="filter-btn px-4 py-2 rounded-lg bg-gray-200 text-gray-700 font-medium transition-all hover:bg-gray-300">
                    <i class="fas fa-calendar-alt mr-2"></i>Appointments ({{ $resultCounts['appointments'] }})
                </button>
                @endif
                @if($resultCounts['products'] > 0)
                <button onclick="filterResults('Product')" class="filter-btn px-4 py-2 rounded-lg bg-gray-200 text-gray-700 font-medium transition-all hover:bg-gray-300">
                    <i class="fas fa-box mr-2"></i>Products ({{ $resultCounts['products'] }})
                </button>
                @endif
                @if($resultCounts['services'] > 0)
                <button onclick="filterResults('Service')" class="filter-btn px-4 py-2 rounded-lg bg-gray-200 text-gray-700 font-medium transition-all hover:bg-gray-300">
                    <i class="fas fa-concierge-bell mr-2"></i>Services ({{ $resultCounts['services'] }})
                </button>
                @endif
                @if($resultCounts['branches'] > 0)
                <button onclick="filterResults('Branch')" class="filter-btn px-4 py-2 rounded-lg bg-gray-200 text-gray-700 font-medium transition-all hover:bg-gray-300">
                    <i class="fas fa-building mr-2"></i>Branches ({{ $resultCounts['branches'] }})
                </button>
                @endif
                @if($resultCounts['equipment'] > 0)
                <button onclick="filterResults('Equipment')" class="filter-btn px-4 py-2 rounded-lg bg-gray-200 text-gray-700 font-medium transition-all hover:bg-gray-300">
                    <i class="fas fa-tools mr-2"></i>Equipment ({{ $resultCounts['equipment'] }})
                </button>
                @endif
                @if($resultCounts['prescriptions'] > 0)
                <button onclick="filterResults('Prescription')" class="filter-btn px-4 py-2 rounded-lg bg-gray-200 text-gray-700 font-medium transition-all hover:bg-gray-300">
                    <i class="fas fa-prescription mr-2"></i>Prescriptions ({{ $resultCounts['prescriptions'] }})
                </button>
                @endif
                @if($resultCounts['referrals'] > 0)
                <button onclick="filterResults('Referral')" class="filter-btn px-4 py-2 rounded-lg bg-gray-200 text-gray-700 font-medium transition-all hover:bg-gray-300">
                    <i class="fas fa-file-medical mr-2"></i>Referrals ({{ $resultCounts['referrals'] }})
                </button>
                @endif
                @if($resultCounts['users'] > 0)
                <button onclick="filterResults('User')" class="filter-btn px-4 py-2 rounded-lg bg-gray-200 text-gray-700 font-medium transition-all hover:bg-gray-300">
                    <i class="fas fa-user-md mr-2"></i>Users ({{ $resultCounts['users'] }})
                </button>
                @endif
            </div>
        </div>

        <!-- Results Grid -->
        @if($results->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($results as $result)
            <a href="{{ $result['route'] }}" 
               class="result-card bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 p-6 hover:-translate-y-1"
               data-type="{{ $result['type'] }}">
                <div class="flex items-start gap-4">
                    <!-- Icon -->
                    <div class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0 
                                {{ $result['color'] === 'blue' ? 'bg-blue-100' : '' }}
                                {{ $result['color'] === 'green' ? 'bg-green-100' : '' }}
                                {{ $result['color'] === 'purple' ? 'bg-purple-100' : '' }}
                                {{ $result['color'] === 'orange' ? 'bg-orange-100' : '' }}
                                {{ $result['color'] === 'teal' ? 'bg-teal-100' : '' }}">
                        <i class="fas {{ $result['icon'] }} text-xl
                                  {{ $result['color'] === 'blue' ? 'text-blue-500' : '' }}
                                  {{ $result['color'] === 'green' ? 'text-green-500' : '' }}
                                  {{ $result['color'] === 'purple' ? 'text-purple-500' : '' }}
                                  {{ $result['color'] === 'orange' ? 'text-orange-500' : '' }}
                                  {{ $result['color'] === 'teal' ? 'text-teal-500' : '' }}"></i>
                    </div>
                    
                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <!-- Type Badge -->
                        <span class="inline-block px-2 py-1 rounded-md text-xs font-semibold mb-2
                                     {{ $result['color'] === 'blue' ? 'bg-blue-100 text-blue-700' : '' }}
                                     {{ $result['color'] === 'green' ? 'bg-green-100 text-green-700' : '' }}
                                     {{ $result['color'] === 'purple' ? 'bg-purple-100 text-purple-700' : '' }}
                                     {{ $result['color'] === 'orange' ? 'bg-orange-100 text-orange-700' : '' }}
                                     {{ $result['color'] === 'teal' ? 'bg-teal-100 text-teal-700' : '' }}">
                            {{ $result['type'] }}
                        </span>
                        
                        <!-- Title -->
                        <h3 class="font-bold text-gray-900 text-lg mb-1 truncate">
                            {{ $result['title'] }}
                        </h3>
                        
                        <!-- Subtitle -->
                        <p class="text-sm text-gray-600 mb-2">
                            {{ $result['subtitle'] }}
                        </p>
                        
                        <!-- Description -->
                        <p class="text-xs text-gray-500 line-clamp-2">
                            {{ $result['description'] }}
                        </p>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
        @else
        <div class="bg-white rounded-xl shadow-md p-12 text-center">
            <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">No results found</h3>
            <p class="text-gray-500">Try adjusting your search terms or browse our categories</p>
        </div>
        @endif
    @else
        <!-- Empty State -->
        <div class="bg-white rounded-xl shadow-md p-12 text-center">
            <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">Start searching</h3>
            <p class="text-gray-500">Use the search bar above to find pets, owners, appointments, products, and services</p>
        </div>
    @endif
</div>

<script>
function filterResults(type) {
    const cards = document.querySelectorAll('.result-card');
    const buttons = document.querySelectorAll('.filter-btn');
    
    // Update active button
    buttons.forEach(btn => {
        btn.classList.remove('active', 'bg-[#ff8c42]', 'text-white');
        btn.classList.add('bg-gray-200', 'text-gray-700');
    });
    
    event.target.classList.add('active', 'bg-[#ff8c42]', 'text-white');
    event.target.classList.remove('bg-gray-200', 'text-gray-700');
    
    // Filter cards
    cards.forEach(card => {
        if (type === 'all' || card.dataset.type === type) {
            card.style.display = 'block';
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 10);
        } else {
            card.style.opacity = '0';
            card.style.transform = 'translateY(10px)';
            setTimeout(() => {
                card.style.display = 'none';
            }, 300);
        }
    });
}

// Initialize card animations
document.addEventListener('DOMContentLoaded', () => {
    const cards = document.querySelectorAll('.result-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.3s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 50);
    });
});
</script>
@endsection