@extends('AdminBoard')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<div class="px-4 md:px-10 py-6">
  <h2 class="text-[#0f7ea0] font-bold text-lg">Sales Report</h2>
  <br>

  <div class="overflow-auto">
    <table class="min-w-full bg-white border border-gray-300 text-sm">
      <thead class="bg-gray-100">
        <tr>
          <th class="border px-3 py-2">Invoice No</th>
          <th class="border px-3 py-2">Sale Date</th>
          <th class="border px-3 py-2">Branch</th>
          <th class="border px-3 py-2">Cash</th>
          <th class="border px-3 py-2">Change</th>
          <th class="border px-3 py-2">Handled By</th>
          <th class="border px-3 py-2">Items</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($sales as $sale)
        <tr>
          <td class="border px-3 py-2">{{ $sale->invoice_number }}</td>
          <td class="border px-3 py-2">{{ $sale->sale_date }}</td>
          <td class="border px-3 py-2">{{ $sale->branch_id }}</td>
          <td class="border px-3 py-2">₱{{ number_format($sale->cash, 2) }}</td>
          <td class="border px-3 py-2">₱{{ number_format($sale->change, 2) }}</td>
          <td class="border px-3 py-2">{{ $sale->handled_by }}</td>
          <td class="border px-3 py-2">
            <ul class="list-disc pl-4">
              @foreach ($sale->saleItems as $item)
                <li>{{ $item->product_name }}</li>
              @endforeach
            </ul>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection