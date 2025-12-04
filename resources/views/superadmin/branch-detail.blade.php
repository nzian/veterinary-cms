@extends('AdminBoard')

@section('content')
<style>
    :root {
        --primary: #6366f1;
        --secondary: #8b5cf6;
        --success: #10b981;
        --warning: #f59e0b;
        --danger: #ef4444;
        --info: #06b6d4;
        --dark: #1e293b;
    }

    body {
        background: #f8fafc;
    }

    .branch-hero {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 2.5rem;
        border-radius: 24px;
        margin-bottom: 2rem;
        color: white;
        position: relative;
        overflow: hidden;
    }

    .branch-hero::before {
        content: '';
        position: absolute;
        width: 400px;
        height: 400px;
        background: rgba(255,255,255,0.1);
        border-radius: 50%;
        top: -200px;
        right: -200px;
    }

    .back-btn {
        background: rgba(255,255,255,0.2);
        border: none;
        padding: 0.625rem 1.25rem;
        border-radius: 12px;
        color: white;
        font-weight: 600;
        transition: all 0.2s;
        backdrop-filter: blur(10px);
    }

    .back-btn:hover {
        background: rgba(255,255,255,0.3);
        transform: translateX(-4px);
    }

    .stat-card-branch {
        background: white;
        border-radius: 20px;
        padding: 1.75rem;
        border: 1px solid #e2e8f0;
        transition: all 0.3s;
        height: 100%;
    }

    .stat-card-branch:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.08);
        border-color: var(--stat-color);
    }

    .stat-icon-branch {
        width: 64px;
        height: 64px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        margin-bottom: 1rem;
        color: white;
    }

    .stat-value-branch {
        font-size: 2.25rem;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 0.5rem;
    }

    .stat-label-branch {
        font-size: 0.875rem;
        color: #64748b;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.75rem;
    }

    .chart-container-branch {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        border: 1px solid #e2e8f0;
    }

    .section-card {
        background: white;
        border-radius: 20px;
        padding: 1.75rem;
        border: 1px solid #e2e8f0;
        margin-bottom: 1.5rem;
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #f1f5f9;
    }

    .section-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--dark);
        margin: 0;
    }

    .list-item {
        display: flex;
        align-items: center;
        padding: 1rem;
        background: #f8fafc;
        border-radius: 12px;
        margin-bottom: 0.75rem;
        transition: all 0.2s;
        border: 1px solid transparent;
    }

    .list-item:hover {
        background: white;
        border-color: var(--primary);
        transform: translateX(4px);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.1);
    }

    .avatar {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.125rem;
        color: white;
        margin-right: 1rem;
        flex-shrink: 0;
    }

    .item-content {
        flex-grow: 1;
    }

    .item-title {
        font-weight: 600;
        color: var(--dark);
        margin-bottom: 0.25rem;
    }

    .item-subtitle {
        font-size: 0.875rem;
        color: #64748b;
    }

    .badge-custom {
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .table-modern-branch {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 0.5rem;
    }

    .table-modern-branch thead th {
        background: transparent;
        border: none;
        color: #64748b;
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        padding: 0.75rem 1rem;
    }

    .table-modern-branch tbody tr {
        background: #f8fafc;
        transition: all 0.2s;
    }

    .table-modern-branch tbody tr:hover {
        background: white;
        box-shadow: 0 4px 12px rgba(0,0,0,0.04);
    }

    .table-modern-branch tbody td {
        padding: 1rem;
        border: none;
    }

    .table-modern-branch tbody td:first-child {
        border-radius: 12px 0 0 12px;
    }

    .table-modern-branch tbody td:last-child {
        border-radius: 0 12px 12px 0;
    }

    .stock-indicator {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.875rem;
    }

    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: #94a3b8;
    }

    .empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.3;
    }

    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-slide {
        animation: slideInUp 0.6s ease forwards;
    }
</style>

<div class="container-fluid p-4">
    <!-- Hero Header -->
    <div class="branch-hero position-relative">
        <button class="back-btn mb-3" onclick="window.location.href='{{ route('superadmin.dashboard') }}'">
            <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
        </button>
        <div class="row align-items-end position-relative" style="z-index: 1;">
            <div class="col-lg-8">
                <h1 class="font-weight-bold mb-2" style="font-size: 2.5rem;">
                    {{ $branch->branch_name }}
                </h1>
                <div class="d-flex align-items-center flex-wrap" style="gap: 1rem;">
                    <div class="d-flex align-items-center" style="background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 12px; backdrop-filter: blur(10px);">
                        <i class="fas fa-map-marker-alt mr-2"></i>
                        <span>{{ $branch->branch_location ?? 'Location not specified' }}</span>
                    </div>
                    <div class="d-flex align-items-center" style="background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 12px; backdrop-filter: blur(10px);">
                        <i class="fas fa-calendar mr-2"></i>
                        <span>{{ date('Y') }} Performance</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 text-lg-right mt-3 mt-lg-0">
                <div class="d-inline-block text-left" style="background: rgba(255,255,255,0.15); padding: 1.5rem; border-radius: 16px; backdrop-filter: blur(10px);">
                    <div class="text-uppercase mb-1" style="font-size: 0.75rem; opacity: 0.9; letter-spacing: 1px;">Total Revenue</div>
                    <div class="h2 font-weight-bold mb-0">₱{{ number_format($branchRevenue, 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-4 animate-slide" style="animation-delay: 0.1s">
            <div class="stat-card-branch" style="--stat-color: var(--success)">
                <div class="stat-icon-branch" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-value-branch">₱{{ number_format($branchRevenue / 1000, 1) }}k</div>
                <div class="stat-label-branch">Total Revenue</div>
                <div class="badge-custom" style="background: #d1fae5; color: #065f46;">
                    <i class="fas fa-arrow-up"></i>
                    Today: ₱{{ number_format($todayRevenue, 0) }}
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4 animate-slide" style="animation-delay: 0.2s">
            <div class="stat-card-branch" style="--stat-color: var(--warning)">
                <div class="stat-icon-branch" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-value-branch">{{ $totalVisits ?? 0 }}</div>
                <div class="stat-label-branch">Visits</div>
                <div class="badge-custom" style="background: #fef3c7; color: #92400e;">
                    <i class="fas fa-clock"></i>
                    Today: {{ $todayVisits ?? 0 }}
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4 animate-slide" style="animation-delay: 0.3s">
            <div class="stat-card-branch" style="--stat-color: var(--info)">
                <div class="stat-icon-branch" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value-branch">{{ $branch->users->count() }}</div>
                <div class="stat-label-branch">Staff Members</div>
                <div class="badge-custom" style="background: #cffafe; color: #155e75;">
                    <i class="fas fa-user-check"></i>
                    Active Team
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4 animate-slide" style="animation-delay: 0.4s">
            <div class="stat-card-branch" style="--stat-color: var(--primary)">
                <div class="stat-icon-branch" style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
                    <i class="fas fa-concierge-bell"></i>
                </div>
                <div class="stat-value-branch">{{ $branch->services->count() }}</div>
                <div class="stat-label-branch">Services</div>
                <div class="badge-custom" style="background: #dbeafe; color: #1e40af;">
                    <i class="fas fa-box"></i>
                    {{ $branch->products->count() }} Products
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue Chart -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="chart-container-branch">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="section-title">Revenue Performance</h3>
                        <p class="text-muted mb-0" style="font-size: 0.875rem;">Monthly trends for {{ date('Y') }}</p>
                    </div>
                    <div class="badge-custom" style="background: #f1f5f9; color: #64748b;">
                        <i class="fas fa-chart-area"></i>
                        12 Months
                    </div>
                </div>
                <canvas id="monthlyRevenueChart" height="70"></canvas>
            </div>
        </div>
    </div>

    <!-- Staff and Services Row -->
    <div class="row mb-4">
        <!-- Staff Members -->
        <div class="col-lg-6 mb-4">
            <div class="section-card">
                <div class="section-header">
                    <div>
                        <h3 class="section-title">Staff Members</h3>
                        <p class="text-muted mb-0" style="font-size: 0.875rem;">{{ $branch->users->count() }} team members</p>
                    </div>
                </div>
                <div style="max-height: 450px; overflow-y: auto;">
                    @forelse($branch->users as $index => $user)
                    <div class="list-item">
                        <div class="avatar" style="background: linear-gradient(135deg, {{ ['#6366f1', '#10b981', '#f59e0b', '#06b6d4', '#ec4899'][$index % 5] }}, {{ ['#8b5cf6', '#059669', '#d97706', '#0891b2', '#db2777'][$index % 5] }});">
                            {{ strtoupper(substr($user->name ?? $user->user_name ?? 'U', 0, 1)) }}
                        </div>
                        <div class="item-content">
                            <div class="item-title">{{ $user->name ?? $user->user_name ?? 'N/A' }}</div>
                            <div class="item-subtitle">
                                <i class="fas fa-envelope mr-1"></i>{{ $user->email }}
                            </div>
                        </div>
                        <div class="badge-custom" style="background: #dbeafe; color: #1e40af;">
                            {{ ucfirst($user->user_role) }}
                        </div>
                    </div>
                    @empty
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <p class="mb-0">No staff members found</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Services Offered -->
        <div class="col-lg-6 mb-4">
            <div class="section-card">
                <div class="section-header">
                    <div>
                        <h3 class="section-title">Services Offered</h3>
                        <p class="text-muted mb-0" style="font-size: 0.875rem;">{{ $branch->services->count() }} services available</p>
                    </div>
                </div>
                <div style="max-height: 450px; overflow-y: auto;">
                    @forelse($branch->services->take(10) as $index => $service)
                    <div class="list-item">
                        <div class="avatar" style="background: linear-gradient(135deg, #10b981, #059669);">
                            <i class="fas fa-spa"></i>
                        </div>
                        <div class="item-content">
                            <div class="item-title">{{ $service->serv_name }}</div>
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-success">
                            ₱{{ number_format($service->serv_price, 2) }}
                        </div>
                    </div>
                    @empty
                    <div class="empty-state">
                        <i class="fas fa-concierge-bell"></i>
                        <p class="mb-0">No services found</p>
                    </div>
                    @endforelse
                    
                    @if($branch->services->count() > 10)
                    <div class="text-center mt-3">
                        <small class="text-muted">
                            <i class="fas fa-ellipsis-h mr-2"></i>
                            Showing 10 of {{ $branch->services->count() }} services
                        </small>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Products Table -->
    <div class="row">
        <div class="col-12">
            <div class="section-card">
                <div class="section-header">
                    <div>
                        <h3 class="section-title">Product Inventory</h3>
                        <p class="text-muted mb-0" style="font-size: 0.875rem;">{{ $branch->products->count() }} products in stock</p>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table-modern-branch">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Description</th>
                                <th>Price</th>
                                <th>Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($branch->products->take(15) as $product)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed); margin-right: 0.75rem;">
                                            <i class="fas fa-box"></i>
                                        </div>
                                        <span class="font-weight-600">{{ $product->prod_name }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-muted">{{ Str::limit($product->prod_desc ?? 'No description', 50) }}</span>
                                </td>
                                <td>
                                    <span class="font-weight-bold text-success">₱{{ number_format($product->prod_price, 2) }}</span>
                                </td>
                                <td>
                                    <div class="stock-indicator {{ $product->current_stock > 10 ? 'bg-success' : ($product->current_stock > 0 ? 'bg-warning' : 'bg-danger') }} text-white">
                                        {{ $product->current_stock }}
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4">
                                    <div class="empty-state">
                                        <i class="fas fa-box-open"></i>
                                        <p class="mb-0">No products found</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                    @if($branch->products->count() > 15)
                    <div class="text-center mt-3">
                        <small class="text-muted">
                            <i class="fas fa-ellipsis-h mr-2"></i>
                            Showing 15 of {{ $branch->products->count() }} products
                        </small>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('monthlyRevenueChart');
    if (ctx) {
        const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(99, 102, 241, 0.3)');
        gradient.addColorStop(1, 'rgba(99, 102, 241, 0.01)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json($months),
                datasets: [{
                    label: 'Revenue',
                    data: @json($monthlyRevenue),
                    backgroundColor: gradient,
                    borderColor: '#6366f1',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 8,
                    pointHoverBackgroundColor: '#6366f1',
                    pointHoverBorderColor: 'white',
                    pointHoverBorderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 16,
                        cornerRadius: 12,
                        titleFont: { size: 14, weight: '600' },
                        bodyFont: { size: 13 },
                        displayColors: false,
                        callbacks: {
                            title: (items) => items[0].label,
                            label: (context) => '₱' + context.parsed.y.toLocaleString()
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        border: { display: false },
                        grid: {
                            color: '#f1f5f9',
                            drawTicks: false
                        },
                        ticks: {
                            padding: 10,
                            callback: (value) => '₱' + (value / 1000) + 'k',
                            font: { size: 11 }
                        }
                    },
                    x: {
                        border: { display: false },
                        grid: { display: false },
                        ticks: {
                            padding: 10,
                            font: { size: 11 }
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush
@endsection