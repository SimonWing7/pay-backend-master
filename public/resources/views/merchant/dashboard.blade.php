@extends('merchant.layout')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Welcome back, {{ auth("merchants")->user()->name }}')

@section('topbar-actions')
    <a href="{{ route('merchant.invoices.create') }}" class="btn-primary">
        <i class="fas fa-plus"></i> New Payment Link
    </a>
@endsection

@section('content')

    {{-- Quick Actions --}}
    <div class="card mb-6 p-6">
        <h3 style="font-size: 14px; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 16px;">
            Quick Actions
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
            <a href="{{ route('merchant.invoices.create') }}" class="btn-primary justify-center py-3">
                <i class="fas fa-link"></i> New Payment Link
            </a>
            <a href="{{ route('merchant.invoices.create-bulk') }}" class="btn-primary justify-center py-3">
                <i class="fas fa-layer-group"></i> Bulk Links
            </a>
            <a href="{{ route('merchant.products.create') }}" class="btn-secondary justify-center py-3">
                <i class="fas fa-tag"></i> Add Product
            </a>
            <a href="{{ route('merchant.consumers.create') }}" class="btn-secondary justify-center py-3">
                <i class="fas fa-user-plus"></i> Add Customer
            </a>
        </div>
    </div>

    {{-- Stats Row 1: Resources --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">

        <a href="{{ route('merchant.invoices.index') }}" class="stat-card hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #6b7280;">Payment Links</p>
                    <p style="font-size: 28px; font-weight: 800; color: #000026; margin-top: 4px;">{{ $stats['invoices'] }}</p>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-link"></i>
                </div>
            </div>
        </a>

        <a href="{{ route('merchant.payments.index') }}" class="stat-card hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #6b7280;">Payments</p>
                    <p style="font-size: 28px; font-weight: 800; color: #000026; margin-top: 4px;">{{ $stats['payments'] }}</p>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-credit-card"></i>
                </div>
            </div>
        </a>

        <a href="{{ route('merchant.consumers.index') }}" class="stat-card hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #6b7280;">Customers</p>
                    <p style="font-size: 28px; font-weight: 800; color: #000026; margin-top: 4px;">{{ $stats['consumers'] }}</p>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </a>

        <a href="{{ route('merchant.products.index') }}" class="stat-card hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #6b7280;">Products</p>
                    <p style="font-size: 28px; font-weight: 800; color: #000026; margin-top: 4px;">{{ $stats['products'] }}</p>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-tag"></i>
                </div>
            </div>
        </a>

    </div>

    {{-- Stats Row 2: Income --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">

        <a href="{{ route('merchant.payments.index') }}" class="stat-card hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #6b7280;">Total Income</p>
                    <p style="font-size: 24px; font-weight: 800; color: #000026; margin-top: 4px;">AED {{ number_format($totalIncome, 2) }}</p>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
            </div>
        </a>

        <a href="{{ route('merchant.payments.index') }}" class="stat-card hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #6b7280;">Income This Month</p>
                    <p style="font-size: 24px; font-weight: 800; color: #000026; margin-top: 4px;">AED {{ number_format($totalIncomeCurrentMonth, 2) }}</p>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
            </div>
        </a>

    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <div class="card p-6">
            <h3 style="font-size: 14px; font-weight: 700; color: #111827; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-chart-line gradient-text"></i>
                Payments — Last 30 Days
            </h3>
            <canvas id="paymentsChart" height="110"></canvas>
        </div>

        <div class="card p-6">
            <h3 style="font-size: 14px; font-weight: 700; color: #111827; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-chart-area gradient-text"></i>
                Income — Last 30 Days
            </h3>
            <canvas id="incomeChart" height="110"></canvas>
        </div>

    </div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    const brandPurple  = '#3d01bd';
    const brandCyan    = '#00bdff';
    const purpleAlpha  = 'rgba(61, 1, 189, 0.08)';
    const cyanAlpha    = 'rgba(0, 189, 255, 0.08)';

    // Payments Chart
    new Chart(document.getElementById('paymentsChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: @json($paymentStats['labels']),
            datasets: [{
                label: 'Payments',
                data: @json($paymentStats['data']),
                borderColor: brandPurple,
                backgroundColor: purpleAlpha,
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: brandPurple,
                pointRadius: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f0f1f5' } },
                x: { ticks: { maxTicksLimit: 10 }, grid: { display: false } }
            }
        }
    });

    // Income Chart
    new Chart(document.getElementById('incomeChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: @json($incomeStats['labels']),
            datasets: [{
                label: 'Income (AED)',
                data: @json($incomeStats['data']),
                borderColor: brandCyan,
                backgroundColor: cyanAlpha,
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: brandCyan,
                pointRadius: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => 'AED ' + parseFloat(ctx.parsed.y).toFixed(2)
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { callback: v => 'AED ' + v.toFixed(0) },
                    grid: { color: '#f0f1f5' }
                },
                x: { ticks: { maxTicksLimit: 10 }, grid: { display: false } }
            }
        }
    });
</script>
@endpush
