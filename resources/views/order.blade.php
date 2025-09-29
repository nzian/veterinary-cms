@extends('AdminBoard')

@section('content')
<div class="min-h-screen">
    <div class="max-w-7xl mx-auto bg-white p-6 rounded-lg shadow">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-[#0f7ea0] font-bold text-xl">POS Sales</h2>
            <div class="flex gap-2">
                <button onclick="exportSales()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
    Export CSV
</button>

                <button onclick="showFilters()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                    Filter
                </button>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-100 text-green-700 px-4 py-2 mb-4 rounded">
                {{ session('success') }}
            </div>
        @endif

        <!-- Filter Section (Initially Hidden) -->
        <div id="filterSection" class="mb-4 p-4 bg-gray-100 rounded-lg hidden">
            <form method="GET" class="flex gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Start Date</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}" 
                           class="mt-1 block w-full border-gray-300 rounded-md">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">End Date</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}" 
                           class="mt-1 block w-full border-gray-300 rounded-md">
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                    Apply Filter
                </button>
                <a href="{{ request()->url() }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded">
                    Reset
                </a>
            </form>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-blue-100 p-4 rounded-lg">
                <h3 class="text-blue-800 font-semibold">Total Sales</h3>
                <p class="text-2xl font-bold text-blue-900">
                    ₱{{ number_format($totalSales, 2) }}
                </p>
            </div>
            <div class="bg-green-100 p-4 rounded-lg">
                <h3 class="text-green-800 font-semibold">Total Transactions</h3>
                <p class="text-2xl font-bold text-green-900">
                    {{ $totalTransactions }}
                </p>
            </div>
            <div class="bg-purple-100 p-4 rounded-lg">
                <h3 class="text-purple-800 font-semibold">Items Sold</h3>
                <p class="text-2xl font-bold text-purple-900">
                    {{ $totalItemsSold }}
                </p>
            </div>
            <div class="bg-yellow-100 p-4 rounded-lg">
                <h3 class="text-yellow-800 font-semibold">Average Sale</h3>
                <p class="text-2xl font-bold text-yellow-900">
                    ₱{{ number_format($averageSale, 2) }}
                </p>
            </div>
        </div>

        <!-- Sales Table -->
        <div class="overflow-x-auto">
            <table class="table-auto w-full border-collapse border text-sm text-center">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="border px-4 py-2">#</th>
                        <th class="border px-4 py-2">Transaction ID</th>
                        <th class="border px-4 py-2">Sale Date</th>
                        <th class="border px-4 py-2">Products</th>
                        <th class="border px-4 py-2">Total Items</th>
                        <th class="border px-4 py-2">Transaction Total</th>
                        <th class="border px-4 py-2">Customer</th>
                        <th class="border px-4 py-2">Cashier</th>
                        <th class="border px-4 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($paginatedTransactions as $transactionId => $orders)
                        @php
                            $firstOrder = $orders->first();
                            $transactionTotal = $orders->sum(function($order) {
                                return $order->ord_quantity * ($order->product->prod_price ?? 0);
                            });
                            $totalItems = $orders->sum('ord_quantity');
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="border px-4 py-2">{{ $loop->iteration + (($paginator->currentPage() - 1) * $paginator->perPage()) }}</td>
                            <td class="border px-4 py-2 font-mono text-blue-600">
                                #{{ $transactionId }}
                            </td>
                            <td class="border px-4 py-2">
                                {{ \Carbon\Carbon::parse($firstOrder->ord_date)->format('M d, Y h:i A') }}
                            </td>
                            <td class="border px-4 py-2 text-left">
                                @foreach($orders as $order)
                                    <div class="flex justify-between items-center py-1 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                                        <span class="text-gray-700">{{ $order->product->prod_name ?? 'N/A' }}</span>
                                        <span class="text-sm text-gray-500 ml-2">×{{ $order->ord_quantity }}</span>
                                    </div>
                                @endforeach
                            </td>
                            <td class="border px-4 py-2 font-semibold">
                                {{ $totalItems }}
                            </td>
                            <td class="border px-4 py-2 font-bold text-green-600">
                                ₱{{ number_format($transactionTotal, 2) }}
                            </td>
                            <td class="border px-4 py-2">
                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full
                                    {{ $firstOrder->owner ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $firstOrder->owner->own_name ?? 'Walk-in Customer' }}
                                </span>
                            </td>
                            <td class="border px-4 py-2">
                                {{ $firstOrder->user->user_name ?? 'N/A' }}
                            </td>
                           <td class="border px-4 py-2">
    <button onclick="viewTransaction('{{ $transactionId }}')" 
            class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 text-xs"
            title="View Transaction Details">
        <i class="fas fa-eye"></i>
    </button>
<button onclick="printTransaction(this)" 
        data-id="{{ $transactionId }}" 
        class="bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600 text-xs"
        title="Print Receipt">
    <i class="fas fa-print"></i>
</button>

</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-gray-500">
                                <div class="text-center">
                                    <i class="fas fa-receipt text-4xl mb-4 opacity-50"></i>
                                    <p class="text-lg">No transactions found.</p>
                                    <p class="text-sm">Try adjusting your date filters or check back later.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $paginator->appends(request()->query())->links() }}
        </div>

        <!-- Additional Info -->
        @if($paginatedTransactions->count() > 0)
            <div class="mt-4 text-sm text-gray-600 text-center">
                Showing {{ $paginator->firstItem() }} to {{ $paginator->lastItem() }} of {{ $paginator->total() }} transactions
            </div>
        @endif
    </div>
</div>


<script>
   function printTransaction(button) {
    const transactionId = button.getAttribute('data-id'); // gets transaction ID
    const url = `/orders/print/${transactionId}`;         // route to your controller
    window.open(url, '_blank', 'width=800,height=600');  // opens print view
}

function showFilters() {
    const filterSection = document.getElementById('filterSection');
    filterSection.classList.toggle('hidden');
}

function exportSales() {
    const startDate = document.querySelector('input[name="start_date"]')?.value || '';
    const endDate = document.querySelector('input[name="end_date"]')?.value || '';

    let exportUrl = '{{ route("orders.export") }}';
    const params = new URLSearchParams();
    
    if (startDate) params.append('start_date', startDate);
    if (endDate) params.append('end_date', endDate);

    if (params.toString()) {
        exportUrl += '?' + params.toString();
    }

    window.location.href = exportUrl;
}



function viewTransaction(transactionId) {
    window.location.href = '/orders/transaction/' + transactionId;
}

function printTransaction(button) {
    const transactionId = button.getAttribute('data-id');
    const transaction = window.transactions.find(t => t.ord_id == transactionId);

    if (!transaction) return alert("Transaction not found");

    let printContent = `
        <h2>Transaction Receipt</h2>
        <p>ID: ${transaction.ord_id}</p>
        <p>Date: ${transaction.ord_date}</p>
        <p>Total: ₱${transaction.total}</p>
        <hr>
        <ul>
            ${transaction.items.map(i => `<li>${i.prod_name} x ${i.ord_quantity} - ₱${i.prod_price}</li>`).join('')}
        </ul>
    `;

    const printWindow = window.open('', '', 'width=600,height=800');
    printWindow.document.write(printContent);
    printWindow.document.close();
    printWindow.print();
}


</script>
@endsection