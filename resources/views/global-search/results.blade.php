@extends('AdminBoard')

@section('content')
<div class="max-w-7xl mx-auto p-6">
    <h2 class="text-xl font-bold mb-4">Search Results for: "{{ $query }}"</h2>

    <!-- Pets -->
    @if($pets->count())
        <h3 class="font-semibold mt-4">Pets</h3>
        <ul class="list-disc pl-6">
            @foreach($pets as $pet)
                <li>{{ $pet->pet_name }} ({{ $pet->pet_species }})</li>
            @endforeach
        </ul>
    @endif

    <!-- Owners -->
    @if($owners->count())
        <h3 class="font-semibold mt-4">Owners</h3>
        <ul class="list-disc pl-6">
            @foreach($owners as $owner)
                <li>{{ $owner->own_name }} - {{ $owner->own_contactnum }}</li>
            @endforeach
        </ul>
    @endif

    <!-- Appointments -->
    @if($appointments->count())
        <h3 class="font-semibold mt-4">Appointments</h3>
        <ul class="list-disc pl-6">
            @foreach($appointments as $appointment)
                <li>{{ $appointment->appoint_description }} - {{ $appointment->appoint_status }}</li>
            @endforeach
        </ul>
    @endif

    <!-- Products -->
    @if($products->count())
        <h3 class="font-semibold mt-4">Products</h3>
        <ul class="list-disc pl-6">
            @foreach($products as $product)
                <li>{{ $product->prod_name }} - ₱{{ $product->prod_price }}</li>
            @endforeach
        </ul>
    @endif

    <!-- Services -->
    @if($services->count())
        <h3 class="font-semibold mt-4">Services</h3>
        <ul class="list-disc pl-6">
            @foreach($services as $service)
                <li>{{ $service->serv_name }} - ₱{{ $service->serv_price }}</li>
            @endforeach
        </ul>
    @endif

    @if(!$pets->count() && !$owners->count() && !$appointments->count() && !$products->count() && !$services->count())
        <p class="text-gray-500">No results found.</p>
    @endif
</div>
@endsection
