@extends('AdminBoard')

@section('content')
<div class="px-4 md:px-10 py-6">
    <h2 class="text-[#0f7ea0] font-bold text-lg">Inventory Status</h2>

    <br>

  @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-800 px-4 py-2 rounded mb-4 text-sm">
      {{ session('success') }}
    </div>
  @endif

  <div class="overflow-auto">
  <table class="min-w-full bg-white border border-gray-300 text-sm">
    <thead class="bg-gray-100">
      <tr>
        <th class="border px-3 py-2">Products</th>
        <th class="border px-3 py-2">Description</th> <!-- NEW -->
        <th class="border px-3 py-2">Available</th>
        <th class="border px-3 py-2">Price</th>
        <th class="border px-3 py-2">Reorder Level</th>
        <th class="border px-3 py-2">Action</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($inventory as $item)
        <tr>
          <form method="POST" action="{{ route('inventory.update', $item->id) }}">
            @csrf
            @method('PUT')
            <td class="border px-3 py-2">{{ $item->name }}</td>
            <td class="border px-3 py-2">{{ $item->description }}</td> <!-- NEW -->
            <td class="border px-3 py-2">
              <input type="number" name="stock" value="{{ $item->stock }}" class="w-20 border rounded px-2 py-1 text-sm">
            </td>
            <td class="border px-3 py-2">₱{{ number_format($item->price, 2) }}</td>
            <td class="border px-3 py-2">
              <input type="number" name="reorder_level" value="{{ $item->reorder_level }}" class="w-20 border rounded px-2 py-1 text-sm">
            </td>
            <td class="border px-3 py-2">
              <button type="submit" class="bg-[#0f7ea0] text-white px-3 py-1 text-xs rounded hover:bg-[#0d6d8f]">
  Update
</button>
            </td>
          </form>
        </tr>
        @empty
        <tr>
          <td colspan="5" class="text-center py-3">No inventory items.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
