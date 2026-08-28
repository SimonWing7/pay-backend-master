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

<div class="dash-compact">
<style>
    /* Scoped to this page only — doesn't touch .card/.stat-card/.stat-icon
       used elsewhere in the admin panel (Merchants, Payments, Invoices). */
    .dash-compact .card,
    .dash-compact .stat-card { padding: 12px; }
    .dash-compact .stat-icon { width: 30px; height: 30px; border-radius: 8px; font-size: 12px; }
    .dash-compact .stat-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; }
    .dash-compact .stat-value-lg { font-size: 18px; font-weight: 800; color: #000026; margin-top: 2px; }
    .dash-compact .stat-value-md { font-size: 16px; font-weight: 800; color: #000026; margin-top: 2px; }
    .dash-compact .chart-title { font-size: 12px; font-weight: 700; color: #111827; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
    .dash-compact .qa-title { font-size: 12px; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 10px; }
</style>

    {{-- Quick Actions --}}
    <div class="card mb-4">
        <h3 class="qa-title">Quick Actions</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
            <a href="{{ route('admin.merchants.create') }}" class="btn-primary justify-center py-2 text-sm">
                <i class="fas fa-store"></i> Add Merchant
            </a>
            <a href="{{ route('admin.merchants.index') }}" class="btn-secondary justify-center py-2 text-sm">
                <i class="fas fa-list"></i> View Merchants
            </a>
            <a href="{{ route('admin.payments.index') }}" class="btn-secondary justify-center py-2 text-sm">
                <i class="fas fa-credit-card"></i> View Payments
            </a>
        </div>
    </div>

    {{-- Stats Row 1: Overview --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">

        <a href="{{ route('admin.merchants.index') }}" class="stat-card hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-label">Total Merchants</p>
                    <p class="stat-value-lg">{{ \App\Models\Merchant::count() }}</p>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-store"></i>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.payments.index') }}" class="stat-card hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-label">Total Payments</p>
                    <p class="stat-value-lg">{{ $paymentStats['total'] }}</p>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-credit-card"></i>
                </div>
            </div>
        </a>

    </div>

    {{-- Stats Row 2: Financial --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">

        <a href="{{ route('admin.payments.index') }}" class="stat-card hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-label">Total Volume (All Time)</p>
                    <p class="stat-value-md">AED {{ number_format($totalAmountAllTime, 2) }}</p>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.payments.index') }}" class="stat-card hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="stat-label">Volume This Month</p>
                    <p class="stat-value-md">AED {{ number_format($totalAmountCurrentMonth, 2) }}</p>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
            </div>
        </a>

    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        <div class="card">
            <h3 class="chart-title"><i class="fas fa-chart-area gradient-text"></i> Payments — Last 30 Days</h3>
            <div style="height:140px;"><canvas id="paymentsChart"></canvas></div>
        </div>

        <div class="card">
            <h3 class="chart-title"><i class="fas fa-chart-pie gradient-text"></i> Payment Status</h3>
            <div style="height:140px;"><canvas id="statusChart"></canvas></div>
        </div>

        <div class="card lg:col-span-2">
            <h3 class="chart-title"><i class="fas fa-chart-pie gradient-text"></i> Payments by Merchant <span class="text-xs text-gray-400 font-normal ml-1">(top 10)</span></h3>
            <div style="height:160px;"><canvas id="merchantChart"></canvas></div>
        </div>

    </div>

</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
<script>
    Chart.register(ChartDataLabels);

    // Percentage label formatter shared by both doughnut charts — Chart.js
    // datalabels doesn't compute percentages itself, just raw values.
    function pctLabel(value, ctx) {
        const total = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
        if (!total || value === 0) return '';
        return (value / total * 100).toFixed(0) + '%';
    }

    const brandPurple = '#3d01bd';
    const brandCyan   = '#00bdff';
    const purpleAlpha = 'rgba(61, 1, 189, 0.08)';
    const cyanAlpha   = 'rgba(0, 189, 255, 0.08)';

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
            maintainAspectRatio: false,
            // datalabels is registered globally for the percentage-labeled
            // doughnut charts below — explicitly off here, a per-point count
            // label on every day of a 30-day line chart would be unreadable.
            plugins: { legend: { display: false }, datalabels: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f0f1f5' } },
                x: { ticks: { maxTicksLimit: 10 }, grid: { display: false } }
            }
        }
    });

    // Payment Status Chart — same colors as the status badges used elsewhere
    // (badge-success/warning/danger) so it reads consistently with the rest
    // of the dashboard.
    new Chart(document.getElementById('statusChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: @json($statusBreakdown['labels']),
            datasets: [{
                data: @json($statusBreakdown['data']),
                backgroundColor: ['#059669', '#d97706', '#dc2626'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true } },
                datalabels: { color: '#fff', font: { weight: 'bold', size: 11 }, formatter: pctLabel }
            }
        }
    });

    // Payments by Merchant Chart — top 10 + "Other" for the rest, greyed out
    // so it doesn't compete visually with the real merchant slices.
    (function () {
        const labels = @json($merchantBreakdown['labels']);
        const merchantPalette = ['#3d01bd', '#00bdff', '#7c3aed', '#0ea5e9', '#a855f7', '#06b6d4', '#8b5cf6', '#22d3ee', '#c084fc', '#67e8f9'];
        const colors = labels.map((label, i) => label === 'Other' ? '#9ca3af' : merchantPalette[i % merchantPalette.length]);

        new Chart(document.getElementById('merchantChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: @json($merchantBreakdown['data']),
                    backgroundColor: colors,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true } },
                    datalabels: { color: '#fff', font: { weight: 'bold', size: 10 }, formatter: pctLabel }
                }
            }
        });
    })();
</script>
@endpush
