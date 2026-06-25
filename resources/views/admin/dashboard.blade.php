@extends('admin.layout')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Edfundo Pay Operations Overview')

@section('topbar-actions')
    <a href="{{ route('admin.merchants.create') }}" class="btn-primary">
        <i class="fas fa-plus"></i> New Merchant
    </a>
@endsection

@section('content')

    {{-- Quick Actions --}}
    <div class="card mb-6 p-6">
        <h3 style="font-size: 14px; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 16px;">
            Quick Actions
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <a href="{{ route('admin.merchants.create') }}" class="btn-primary justify-center py-3">
                <i class="fas fa-store"></i> Add Merchant
            </a>
            <a href="{{ route('admin.merchants.index') }}" class="btn-secondary justify-center py-3">
                <i class="fas fa-list"></i> View Merchants
            </a>
            <a href="{{ route('admin.payments.index') }}" class="btn-secondary justify-center py-3">
                <i class="fas fa-credit-card"></i> View Payments
            </a>
        </div>
    </div>

    {{-- Stats Row 1: Overview --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">

        <a href="{{ route('admin.merchants.index') }}" class="stat-card hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #6b7280;">Total Merchants</p>
                    <p style="font-size: 28px; font-weight: 800; color: #000026; margin-top: 4px;">{{ \App\Models\Merchant::count() }}</p>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-store"></i>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.app_users.index') }}" class="stat-card hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #6b7280;">App Users</p>
                    <p style="font-size: 28px; font-weight: 800; color: #000026; margin-top: 4px;">{{ $installationStats['total'] }}</p>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-mobile-alt"></i>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.payments.index') }}" class="stat-card hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #6b7280;">Total Payments</p>
                    <p style="font-size: 28px; font-weight: 800; color: #000026; margin-top: 4px;">{{ $paymentStats['total'] }}</p>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-credit-card"></i>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.payments.index') }}" class="stat-card hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #6b7280;">Total Invoices</p>
                    <p style="font-size: 28px; font-weight: 800; color: #000026; margin-top: 4px;">{{ \App\Models\Invoice::count() }}</p>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-file-invoice"></i>
                </div>
            </div>
        </a>

    </div>

    {{-- Stats Row 2: Financial --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">

        <a href="{{ route('admin.payments.index') }}" class="stat-card hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #6b7280;">Total Volume (All Time)</p>
                    <p style="font-size: 24px; font-weight: 800; color: #000026; margin-top: 4px;">AED {{ number_format($totalAmountAllTime, 2) }}</p>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.payments.index') }}" class="stat-card hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #6b7280;">Volume This Month</p>
                    <p style="font-size: 24px; font-weight: 800; color: #000026; margin-top: 4px;">AED {{ number_format($totalAmountCurrentMonth, 2) }}</p>
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
                App Users — Last 30 Days
            </h3>
            <canvas id="installationsChart" height="110"></canvas>
        </div>

        <div class="card p-6">
            <h3 style="font-size: 14px; font-weight: 700; color: #111827; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-chart-area gradient-text"></i>
                Payments — Last 30 Days
            </h3>
            <canvas id="paymentsChart" height="110"></canvas>
        </div>

    </div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    const brandPurple = '#3d01bd';
    const brandCyan   = '#00bdff';
    const purpleAlpha = 'rgba(61, 1, 189, 0.08)';
    const cyanAlpha   = 'rgba(0, 189, 255, 0.08)';

    // App Users Chart
    new Chart(document.getElementById('installationsChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: @json($installationStats['labels']),
            datasets: [{
                label: 'App Users',
                data: @json($installationStats['data']),
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

    // Payments Chart
    new Chart(document.getElementById('paymentsChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: @json($paymentStats['labels']),
            datasets: [{
                label: 'Payments',
                data: @json($paymentStats['data']),
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
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f0f1f5' } },
                x: { ticks: { maxTicksLimit: 10 }, grid: { display: false } }
            }
        }
    });
</script>
@endpush
