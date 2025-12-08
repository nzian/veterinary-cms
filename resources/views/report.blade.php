            @if(isset($currentReport) && isset($currentReport['data']))
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                @if($currentReport['data']->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200" id="reportTable">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient / Owner</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Visit Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Species / Breed</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fee</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Performed By</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Branch</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($currentReport['data'] as $record)
                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{!! $record['Date & Time'] ?? '' !!}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{!! $record['Service'] ?? '' !!}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{!! $record['Patient / Owner'] ?? '' !!}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{!! $record['Visit Type'] ?? '' !!}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{!! $record['Species / Breed'] ?? '' !!}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{!! $record['Fee'] ?? '' !!}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{!! $record['Performed By'] ?? '' !!}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{!! $record['Status'] ?? '' !!}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{!! $record['Notes'] ?? '' !!}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{!! $record['Branch'] ?? '' !!}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-6 text-gray-500">No service usage history found.</div>
                @endif
            </div>
            @endif
@extends('AdminBoard')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto p-6">

        <div class="bg-white rounded-lg shadow-sm p-6 mb-6 no-print">
            <form method="GET" id="reportform" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Report Type</label>
                    <select name="report" id="reportSelect" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
    @php
        $reportOptions = [
            // Main Reports
            'visits' => 'Visit Management',
            'owner_pets' => 'Pet Owner and Their Pets',
            'visit_billing' => 'Visit with Billing & Payment',
            'product_purchases' => 'Product Purchase Report',
            'referrals' => 'Inter-Branch Referrals',
            // Service & Scheduling Reports
            'visit_services' => 'Services in Visits',
            'branch_visits' => 'Branch Visit Schedule',
            'multi_service_visits' => 'Multiple Services Visits',
            // Financial Reports
            'product_sales' => 'Product Sales by User',
            'payment_collection' => 'Payment Collection Report',
            'branch_payments' => 'Branch Payment Summary',
            // Medical Reports
            'prescriptions' => 'Prescriptions by Branch',
            // Staff & Branch Reports
            'branch_users' => 'Users Assigned per Branch',
            // Inventory & Equipment Reports
            'branch_equipment' => 'Branch Equipment Summary',
            'damaged_products' => 'Complete Stock Movement History',
            // Utilization Reports
            'service_utilization' => 'Service Utilization per Branch',
        ];
    @endphp
    @foreach($reportOptions as $key => $label)
        <option value="{{ $key }}" {{ $reportType === $key ? 'selected' : '' }}>{{ $label }}</option>
    @endforeach
</select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                    <input type="date" name="start_date" value="{{ $startDate }}" 
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                    <input type="date" name="end_date" value="{{ $endDate }}" 
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Branch</label>
                    <select name="branch" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Branches</option>
                        @foreach($branches as $branchOption)
                            <option value="{{ $branchOption->branch_id }}" {{ $branch == $branchOption->branch_id ? 'selected' : '' }}>
                                {{ $branchOption->branch_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end">
                    <div class="w-full space-y-2">
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors duration-200 flex items-center justify-center">
                            <i class="fas fa-search mr-2"></i>
                            Generate Report
                        </button>
                        <button type="button" onclick="exportReport()" class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md transition-colors duration-200 flex items-center justify-center">
                            <i class="fas fa-download mr-2"></i>
                            Export CSV
                        </button>
                    </div>
                </div>
            </form>
        </div>

        @if(isset($reports[$reportType]))
            @php $currentReport = $reports[$reportType]; @endphp
            
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">{{ $currentReport['title'] }}</h2>
                        <p class="text-gray-600 mt-2">{{ $currentReport['description'] }}</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <div class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                            {{ $currentReport['data']->count() }} Records
                        </div>
                        <button onclick="printReportClean()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md text-sm flex items-center no-print">
                            <i class="fas fa-print mr-2"></i>
                            Print
                        </button>
                    </div>
                </div>
            </div>

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
                </div>
            @endif

            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                @if($currentReport['data']->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200" id="reportTable">
                            <thead class="bg-gray-50">
                                <tr>
                                    @php
                                        // For damaged_products, enforce column order
                                        if ($reportType === 'damaged_products' && $currentReport['data']->count() > 0) {
                                            $recordKeys = ['row_number', 'prod_name', 'prod_category', 'prod_type', 'branch_name', 'user_name', 'serv_name', 'reference', 'type', 'quantity'];
                                        } else {
                                            $recordKeys = $currentReport['data']->first() ? array_keys((array) $currentReport['data']->first()) : [];
                                        }
                                        // Hide technical/ID columns from the on-screen table
                                        $hiddenColumns = [
                                            'id','visit_id','bill_id','ord_id','orderId','appoint_id','appointId','pet_id','own_id','user_id','branch_id',
                                            'prod_id','serv_id','service_id','ref_id','equipment_id','prescription_id','appoint_serv_id','pay_id','ref_to','ref_by',
                                            'is_priority','workflow_status','is_group_parent','owner_id','prescription_id','sample','n/a','No Data','array','object','json','debug','IS PRIORITY','WORKFLOW STATUS','IS GROUP PARENT','OWNER ID','ORDER ID','PRESCRIPTION ID','unit price','total amount','raw','pivot','group','priority','parent','contact','date','status','count','amount','price','maintenance','available','out of service','sample','No Data','N/A','array','object','json','[','{',']','}',
                                            'formatted_reference','type_label','transaction_type','quantity_change','created_at','notes','branch_address','appoint_id','visit_id','_id' // Hide helper fields for damaged_products (keep serv_name and row_number visible)
                                        ];
                                    @endphp
                                    @foreach($recordKeys as $key)
                                        @continue(in_array($key, $hiddenColumns))
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            @if(($reportType === 'visits' || $reportType === 'visit_services' || $reportType === 'branch_visits') && $key === 'veterinarian')
                                                Veterinarian
                                            @elseif($reportType === 'owner_pets' && $key === 'total_pets')
                                                Total Pets (Count)
                                            @elseif($reportType === 'branch_payments' && $key === 'total_payments_count')
                                                Total Payment Counts
                                            @elseif($reportType === 'branch_equipment' && $key === 'total_equipment_count')
                                                Total Equipment (Count)
                                            @elseif($reportType === 'branch_equipment' && $key === 'total_quantity_sum')
                                                Total Quantity
                                            @elseif($reportType === 'service_utilization' && $key === 'total_used_count')
                                                Total Used
                                            @elseif($reportType === 'prescriptions' && $key === 'raw_medication_data')
                                                Medications
                                            @elseif($reportType === 'damaged_products' && $key === 'row_number')
                                                #
                                            @elseif($reportType === 'damaged_products' && $key === 'prod_name')
                                                Prod Name
                                            @elseif($reportType === 'damaged_products' && $key === 'prod_category')
                                                Prod Category
                                            @elseif($reportType === 'damaged_products' && $key === 'prod_type')
                                                Prod Type
                                            @elseif($reportType === 'damaged_products' && $key === 'branch_name')
                                                Branch Name
                                            @elseif($reportType === 'damaged_products' && $key === 'user_name')
                                                User Name
                                            @elseif($reportType === 'damaged_products' && $key === 'serv_name')
                                                Serv Name
                                            @elseif($reportType === 'damaged_products' && $key === 'reference')
                                                Reference
                                            @elseif($reportType === 'damaged_products' && $key === 'type')
                                                Type
                                            @elseif($reportType === 'damaged_products' && $key === 'quantity')
                                                Quantity
                                            @else
                                                {{ ucwords(str_replace('_', ' ', $key)) }}
                                            @endif
                                        </th>
                                    @endforeach
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider no-print">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($currentReport['data'] as $record)
                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                        @foreach($record as $key => $value)
                                            @continue(in_array($key, $hiddenColumns))
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                @php
                                                    $displayValue = $value;
                                                    $isNumeric = is_numeric($value);
                                                    $isCurrency = str_contains($key, 'price') || str_contains($key, 'amount') || str_contains($key, 'total');
                                                    $isCount = str_contains($key, '_count') || str_contains($key, '_sum') || $key === 'total_pets' || $key === 'ord_quantity';

                                                    // Display services as readable list for visits
                                                    if (($reportType === 'visits' || $reportType === 'visit_services' || $reportType === 'branch_visits') && ($key === 'services' || $key === 'visit_services')) {
                                                        if (is_array($value) || (is_object($value) && (method_exists($value, 'toArray') || $value instanceof \Illuminate\Support\Collection))) {
                                                            $services = is_object($value) && method_exists($value, 'toArray') ? $value->toArray() : (array)$value;
                                                            $serviceList = [];
                                                            foreach ($services as $service) {
                                                                $name = $service['serv_name'] ?? ($service['service_name'] ?? 'Service');
                                                                $serviceList[] = trim($name);
                                                            }
                                                            $displayValue = count($serviceList) ? implode(', ', $serviceList) : 'No services';
                                                        } else {
                                                            $displayValue = $value ?: 'No services';
                                                        }
                                                    } elseif ($reportType === 'owner_pets' && $key === 'own_contactnum') {
                                                        $displayValue = $value;
                                                    } elseif ($reportType === 'branch_users' && $key === 'user_contactNum') {
                                                        $displayValue = $value;
                                                    } elseif ($reportType === 'branch_visits' && $key === 'visit_date') {
                                                        $displayValue = date('M d, Y', strtotime($value));
                                                    } elseif ($reportType === 'prescriptions' && $key === 'raw_medication_data') {
                                                        $medications = json_decode($value, true);
                                                        $productNames = [];
                                                        if (is_array($medications)) {
                                                            foreach ($medications as $med) {
                                                                $name = $med['product_name'] ?? null;
                                                                if ($name) $productNames[] = $name;
                                                            }
                                                            $displayValue = count($productNames) ? implode(', ', $productNames) : '';
                                                        } else {
                                                            $displayValue = '';
                                                        }
                                                    } elseif ($reportType === 'damaged_products' && $key === 'type') {
                                                        // Display transaction type with color coding
                                                        $typeClasses = [
                                                            'Stock Added' => 'bg-green-100 text-green-800',
                                                            'POS Sale' => 'bg-blue-100 text-blue-800',
                                                            'Service Usage' => 'bg-purple-100 text-purple-800',
                                                            'Damaged' => 'bg-red-100 text-red-800',
                                                            'Pull-out' => 'bg-orange-100 text-orange-800',
                                                            'Adjustment' => 'bg-gray-100 text-gray-800',
                                                            'Return' => 'bg-teal-100 text-teal-800',
                                                        ];
                                                        $typeClass = $typeClasses[$value] ?? 'bg-gray-100 text-gray-800';
                                                        $displayValue = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . $typeClass . '">' . ($value ?? 'N/A') . '</span>';
                                                    } elseif ($reportType === 'damaged_products' && $key === 'quantity') {
                                                        // Show quantity as-is (can be positive or negative)
                                                        $displayValue = $value ?? 0;
                                                    } elseif (str_contains($key, 'date')) {
                                                        $displayValue = $value ? \Carbon\Carbon::parse($value)->format('M d, Y') : '';
                                                    } elseif ($isNumeric && $isCurrency && !$isCount) {
                                                        $displayValue = '<span class="text-green-600 font-semibold">₱' . number_format($value, 2) . '</span>';
                                                    } elseif ($isNumeric && $isCount) {
                                                        $displayValue = number_format($value);
                                                    } elseif (str_contains($key, 'status')) {
                                                         $statusClass = match(strtolower($value)) {
                                                            'completed', 'paid', 'active', 'good', 'good stock' => 'bg-green-100 text-green-800',
                                                            'pending', 'processing', 'low stock' => 'bg-yellow-100 text-yellow-800',
                                                            'cancelled', 'expired', 'inactive', 'out of stock' => 'bg-red-100 text-red-800',
                                                            default => 'bg-gray-100 text-gray-800'
                                                        };
                                                        $displayValue = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . $statusClass . '">' . ucfirst($value) . '</span>';
                                                    }
                                                @endphp
                                                {!! $displayValue !!}
                                            </td>
                                        @endforeach
                                        
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium no-print">
                                            @php
                                                // Prefer a meaningful identifier for the action button
                                                $idFields = [
                                                    'id','visit_id','bill_id','ord_id','orderId','appoint_id','pet_id','own_id','user_id',
                                                    'prod_id','serv_id','service_id','ref_id','equipment_id','prescription_id','appoint_serv_id','pay_id',
                                                    '_id' // Hidden ID field
                                                ];
                                                $recordId = null;
                                                
                                                // Handle both objects and arrays
                                                if (is_object($record)) {
                                                    foreach ($idFields as $idField) {
                                                        if (isset($record->$idField)) { 
                                                            $recordId = $record->$idField; 
                                                            break; 
                                                        }
                                                    }
                                                    // If still no ID, try to get first property
                                                    if (!$recordId) {
                                                        $recordArray = (array)$record;
                                                        $firstKey = array_key_first($recordArray);
                                                        if ($firstKey && isset($recordArray[$firstKey])) {
                                                            $recordId = is_numeric($recordArray[$firstKey]) ? $recordArray[$firstKey] : null;
                                                        }
                                                    }
                                                } elseif (is_array($record)) {
                                                    foreach ($idFields as $idField) {
                                                        if (isset($record[$idField])) { 
                                                            $recordId = $record[$idField]; 
                                                            break; 
                                                        }
                                                    }
                                                }
                                                
                                                // Final fallback - use first numeric value found
                                                if (!$recordId && is_object($record)) {
                                                    $recordArray = (array)$record;
                                                    foreach ($recordArray as $key => $value) {
                                                        if (is_numeric($value) && $value > 0) {
                                                            $recordId = $value;
                                                            break;
                                                        }
                                                    }
                                                }
                                            @endphp
                                            @if($recordId)
                                            <button onclick="viewRecordDetails('{{ $reportType }}', '{{ $recordId }}')" 
                                                class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs transition-colors duration-200" title="View Details/PDF">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            @else
                                            <span class="text-gray-400 text-xs">N/A</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="bg-gray-50 px-6 py-3 border-t border-gray-200">
                        <div class="text-sm text-gray-700">
                            Showing {{ $currentReport['data']->count() }} records
                            @if($startDate && $endDate)
                                from {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
                            @endif
                        </div>
                    </div>
                @else
                    <div class="text-center py-12">
                        <div class="text-gray-400 mb-4">
                            <i class="fas fa-chart-bar text-6xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No Data Available</h3>
                        <p class="text-gray-500">No records found for the selected criteria. Try adjusting your filters.</p>
                    </div>
                @endif
            </div>
        @else
            <div class="bg-white rounded-lg shadow-sm p-12 text-center">
                <div class="text-gray-400 mb-4">
                    <i class="fas fa-file-alt text-6xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Select Report Type</h3>
                <p class="text-gray-500">Choose a report type from the dropdown above to view data.</p>
            </div>
        @endif
    </div>
</div>

<script>
    // Helper function to format money (moved here for completeness, likely exists elsewhere)
    function formatMoney(amount) {
        if (!amount || isNaN(parseFloat(amount))) return '0.00';
        return parseFloat(amount).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    /**
     * FIX: Print function updated for PORTRAIT orientation and CENTERED title.
     */
    function printReportClean() {
        const reportTitle = document.querySelector('.text-2xl.font-bold')?.textContent || 'Report';
        const reportTable = document.getElementById('reportTable');
        const generatedAt = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
        const reportType = document.getElementById('reportSelect').options[document.getElementById('reportSelect').selectedIndex].text;
        
        if (!reportTable) {
            alert('No report data to print');
            return;
        }
        
        // Clone the table
        const tableClone = reportTable.cloneNode(true);
        
        // Remove 'Actions' column from header and rows in the clone
        const headerCells = tableClone.querySelectorAll('thead th');
        if (headerCells.length > 0) {
            headerCells[headerCells.length - 1].remove(); // Remove last column (Actions)
        }
        const rows = tableClone.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length > 0) {
                cells[cells.length - 1].remove(); // Remove last cell (Action Button)
            }
        });

        const logoUrl = '{{ asset("images/header.jpg") }}'; 
        const user = '{{ auth()->check() ? auth()->user()->user_name : "System" }}';

        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>${reportTitle} - ${new Date().toLocaleDateString()}</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        margin: 0;
                        padding: 10mm;
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }
                    @page {
                        size: A4 landscape; /* Landscape for print to fit all data */
                        margin: 10mm;
                        @bottom-right {
                            content: "Page " counter(page) " of " counter(pages);
                            font-size: 8pt;
                            color: #4a5568;
                        }
                    }
                    .header-container {
                        background-color: #f88e28;
                        padding: 15px;
                        margin-bottom: 20px;
                    }
                    .header-container img {
                        max-height: 80px;
                        width: 100%;
                        object-fit: contain;
                    }
                    .metadata {
                        display: flex;
                        justify-content: space-between;
                        font-size: 9pt;
                        margin-bottom: 15px;
                        color: #4a5568;
                    }
                    .report-title {
                        font-size: 20px;
                        font-weight: bold;
                        margin-bottom: 15px;
                        color: #1f2937;
                        text-align: center; /* FIX: Centered the report title */
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        font-size: 9pt;
                        page-break-inside: auto;
                    }
                    tr {
                        page-break-inside: avoid;
                        page-break-after: auto;
                    }
                    thead {
                        display: table-header-group;
                        background-color: #e5e7eb;
                    }
                    th {
                        background-color: #e5e7eb;
                        padding: 8px 6px;
                        text-align: left;
                        font-weight: bold;
                        border: 1px solid #d1d5db;
                        font-size: 8pt;
                        text-transform: uppercase;
                        color: #4a5568;
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }
                    td {
                        padding: 6px 6px;
                        border: 1px solid #d1d5db;
                        color: #1f2937;
                    }
                    tr:nth-child(even) {
                        background-color: #f9fafb;
                    }
                    /* Print color adjustments for badges/money */
                    .text-green-600 { color: #059669 !important; }
                    .bg-green-100 { background-color: #d1fae5 !important; color: #065f46 !important; }
                    .bg-yellow-100 { background-color: #fef3c7 !important; color: #92400e !important; }
                    .bg-red-100 { background-color: #fee2e2 !important; color: #991b1b !important; }
                    .bg-blue-100 { background-color: #dbeafe !important; color: #1e40af !important; }
                    .bg-pink-100 { background-color: #fce7f3 !important; color: #9f1239 !important; }
                    .bg-orange-100 { background-color: #ffedd5 !important; color: #9a3412 !important; }
                    .bg-gray-100 { background-color: #f3f4f6 !important; color: #374151 !important; }

                </style>
            </head>
            <body>
                <div class="header-container">
                    <img src="${logoUrl}" alt="Clinic Header">
                </div>
                
                <div class="report-title">${reportTitle}</div>
                
                <div class="metadata">
                    <span>Report Type: ${reportType}</span>
                    <span>Generated By: ${user}</span>
                    <span>Generated On: ${generatedAt}</span>
                </div>

                ${tableClone.outerHTML}
                
                <script>
                    window.onload = function() {
                        // Small timeout to ensure image rendering before print dialog
                        setTimeout(() => {
                            window.print();
                            window.close();
                        }, 500);
                    };
                <\/script>
            </body>
            </html>
        `);
        printWindow.document.close();
    }

    // Retained original functions for export/view, updated to use fixed values
    function exportReport() {
        const form = document.querySelector('#reportform');
        const exportForm = document.createElement('form');
        exportForm.method = 'GET';
        exportForm.action = '{{ route("reports.export") }}';
        
        const formData = new FormData(form);
        for (let [key, value] of formData.entries()) {
            console.log(key, value); // Debug log
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value;
            exportForm.appendChild(input);
        }
        
        document.body.appendChild(exportForm);
        exportForm.submit();
        document.body.removeChild(exportForm);
    }

    /**
     * FIX: Changed viewRecordDetails to use PDF generation endpoint
     */
    function viewRecordDetails(reportType, recordId) {
        // Open PDF in new tab using the new endpoint
        window.open(`/reports/${reportType}/${recordId}/pdf`, '_blank');
    }

    // Auto-submit form when report type changes
    const reportSelectEl = document.getElementById('reportSelect');
    if (reportSelectEl) {
        reportSelectEl.addEventListener('change', function() {
            if (this.form) this.form.submit();
        });
    }
</script>

<style>
/* Add a media query to hide print-only elements on screen */
@media screen {
    .print-only {
        display: none !important;
    }
}
/* Add a media query to ensure non-printable elements are hidden during print */
@media print {
    .no-print {
        display: none !important;
    }
}
</style>
@endsection