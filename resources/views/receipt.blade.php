@extends('AdminBoard')

@section('content')
<div class="min-h-screen px-4 py-4">
  <div class="max-w-3xl mx-auto bg-white border rounded-lg shadow">
    <div class="p-4 border-b flex items-center justify-between">
      <h2 class="text-lg font-bold text-[#0f7ea0]">Official Receipt</h2>
      <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs">
        <i class="fas fa-print mr-1"></i> Print
      </button>
    </div>
    <div class="p-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
        <div>
          <div class="text-sm text-gray-600">Receipt No.</div>
          <div class="font-mono font-semibold">#{{ $billing->bill_id }}</div>
        </div>
        <div>
          <div class="text-sm text-gray-600">Date</div>
          <div class="font-semibold">{{ \Carbon\Carbon::parse($billing->bill_date)->format('M d, Y h:i A') }}</div>
        </div>
        <div>
          <div class="text-sm text-gray-600">Owner</div>
          <div class="font-semibold">{{ $billing->visit?->pet?->owner?->own_name ?? 'N/A' }}</div>
        </div>
        <div>
          <div class="text-sm text-gray-600">Pet</div>
          <div class="font-semibold">{{ $billing->visit?->pet?->pet_name ?? 'N/A' }}</div>
        </div>
      </div>

      <div class="border rounded overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-100">
            <tr>
              <th class="text-left px-3 py-2 border-b">Item</th>
              <th class="text-center px-3 py-2 border-b">Qty</th>
              <th class="text-right px-3 py-2 border-b">Price</th>
              <th class="text-right px-3 py-2 border-b">Total</th>
            </tr>
          </thead>
          <tbody>
            @forelse($orders as $order)
              <tr class="border-b">
                <td class="px-3 py-2">
                  {{ $order->product->prod_name ?? 'Item' }}
                  @php
                    $days = null;
                    if (Str::contains(Str::lower($order->product->prod_name ?? ''), 'boarding') && isset($billing->visit)) {
                      $boardingService = $billing->visit->services->first(function($service) use ($order) {
                        return Str::contains(Str::lower($service->serv_name), 'boarding');
                      });
                      $days = $boardingService && isset($boardingService->pivot->quantity) ? $boardingService->pivot->quantity : null;
                    }
                  @endphp
                  @if(!is_null($days))
                    <br><span class="text-xs text-gray-500">Days: {{ $days }}</span>
                  @endif
                </td>
                <td class="px-3 py-2 text-center">{{ $order->ord_quantity ?? 1 }}</td>
                <td class="px-3 py-2 text-right">₱{{ number_format(($order->ord_price ?? optional($order->product)->prod_price ?? 0), 2) }}</td>
                <td class="px-3 py-2 text-right">₱{{ number_format(($order->ord_quantity ?? 1) * ($order->ord_price ?? optional($order->product)->prod_price ?? 0), 2) }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="px-3 py-4 text-center text-gray-500">No items</td>
              </tr>
            @endforelse
          </tbody>
          <tfoot>
            <tr>
              <td colspan="3" class="px-3 py-2 text-right font-semibold">Grand Total</td>
              <td class="px-3 py-2 text-right font-bold text-green-700">₱{{ number_format($total, 2) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>

      <div class="mt-6 text-center text-xs text-gray-500">
        Thank you for your payment!
      </div>
    </div>
  </div>
</div>
@endsection
