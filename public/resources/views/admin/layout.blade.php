<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Dashboard') - Edfundo Pay</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }

        :root {
            --purple: #3d01bd;
            --cyan: #00bdff;
            --navy: #000026;
        }

        .sidebar {
            background-color: var(--navy);
            width: 260px;
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            display: flex;
            flex-direction: column;
            z-index: 50;
        }

        .sidebar-logo-bar {
            height: 2px;
            background: linear-gradient(90deg, var(--purple), var(--cyan));
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 20px;
            color: rgba(255,255,255,0.6);
            font-size: 14px;
            font-weight: 500;
            border-radius: 8px;
            margin: 2px 12px;
            transition: all 0.15s ease;
            text-decoration: none;
        }
        .nav-link:hover {
            background: rgba(255,255,255,0.08);
            color: rgba(255,255,255,0.95);
        }
        .nav-link.active {
            background: linear-gradient(135deg, var(--purple), var(--cyan));
            color: #ffffff;
            font-weight: 600;
        }
        .nav-link i {
            width: 18px;
            text-align: center;
            font-size: 15px;
        }

        .nav-section-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: rgba(255,255,255,0.25);
            padding: 16px 32px 6px;
        }

        .main-content {
            margin-left: 260px;
            min-height: 100vh;
            background-color: #f5f6fa;
        }

        .topbar {
            background: #ffffff;
            border-bottom: 1px solid #eef0f5;
            padding: 0 32px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 40;
        }

        /* Admin badge in topbar */
        .admin-badge {
            background: linear-gradient(135deg, var(--purple), var(--cyan));
            color: white;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 3px 10px;
            border-radius: 20px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--purple), var(--cyan));
            color: #ffffff;
            font-weight: 600;
            padding: 8px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            transition: opacity 0.15s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary:hover { opacity: 0.9; }

        .btn-secondary {
            background: #ffffff;
            color: var(--navy);
            font-weight: 600;
            padding: 8px 20px;
            border-radius: 8px;
            border: 1.5px solid #e2e5ef;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.15s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-secondary:hover { border-color: var(--purple); color: var(--purple); }

        .card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #eef0f5;
            box-shadow: 0 1px 3px rgba(0,0,38,0.04);
        }

        .stat-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #eef0f5;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,38,0.04);
        }
        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--purple), var(--cyan));
            color: white;
            font-size: 18px;
        }

        .gradient-text {
            background: linear-gradient(135deg, var(--purple), var(--cyan));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .data-table th {
            background: #f8f9fc;
            color: #6b7280;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 12px 16px;
        }
        .data-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f0f1f5;
            font-size: 14px;
            color: #374151;
        }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tr:hover td { background: #fafbff; }

        .badge-success { background: #ecfdf5; color: #059669; font-size: 12px; font-weight: 600; padding: 3px 10px; border-radius: 20px; }
        .badge-warning { background: #fffbeb; color: #d97706; font-size: 12px; font-weight: 600; padding: 3px 10px; border-radius: 20px; }
        .badge-danger  { background: #fef2f2; color: #dc2626; font-size: 12px; font-weight: 600; padding: 3px 10px; border-radius: 20px; }
        .badge-info    { background: #eff6ff; color: #2563eb; font-size: 12px; font-weight: 600; padding: 3px 10px; border-radius: 20px; }

        .form-input {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid #e2e5ef;
            border-radius: 8px;
            font-size: 14px;
            color: #111827;
            background: #ffffff;
            transition: border-color 0.15s;
            outline: none;
        }
        .form-input:focus { border-color: var(--purple); }
        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }

        .alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 14px 18px; border-radius: 10px; font-size: 14px; }
        .alert-error   { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 14px 18px; border-radius: 10px; font-size: 14px; }

        .sidebar-logout {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 20px;
            color: rgba(255,255,255,0.4);
            font-size: 14px;
            font-weight: 500;
            border-radius: 8px;
            margin: 2px 12px 16px;
            transition: all 0.15s ease;
            width: calc(100% - 24px);
            background: none;
            border: none;
            cursor: pointer;
            text-align: left;
        }
        .sidebar-logout:hover {
            background: rgba(255,255,255,0.06);
            color: rgba(255,255,255,0.8);
        }
    </style>
</head>
<body>

@auth('admin')
<div class="flex">

    {{-- ===================== SIDEBAR ===================== --}}
    <aside class="sidebar">

        {{-- Logo --}}
        <div class="px-6 py-5">
            <img src="/images/edfundo-logo-white.png" alt="Edfundo Pay" class="h-8 w-auto">
        </div>
        <div class="sidebar-logo-bar"></div>

        {{-- Navigation --}}
        <nav class="flex-1 pt-4 pb-2 overflow-y-auto">

            <div class="nav-section-label">Overview</div>

            <a href="{{ route('admin.dashboard') }}"
               class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="fas fa-chart-pie"></i>
                <span>Dashboard</span>
            </a>

            <div class="nav-section-label">Operations</div>

            <a href="{{ route('admin.merchants.index') }}"
               class="nav-link {{ request()->routeIs('admin.merchants*') ? 'active' : '' }}">
                <i class="fas fa-store"></i>
                <span>Merchants</span>
            </a>

            <a href="{{ route('admin.payments.index') }}"
               class="nav-link {{ request()->routeIs('admin.payments*') ? 'active' : '' }}">
                <i class="fas fa-credit-card"></i>
                <span>Payments</span>
            </a>

            <a href="{{ route('admin.app_users.index') }}"
               class="nav-link {{ request()->routeIs('admin.app_users*') ? 'active' : '' }}">
                <i class="fas fa-users"></i>
                <span>App Users</span>
            </a>

        </nav>

        {{-- User info + Logout --}}
        <div class="border-t border-white/10 pt-3">
            <div class="px-8 pb-2">
                <p class="text-white/40 text-xs font-medium">Signed in as</p>
                <p class="text-white/80 text-sm font-semibold truncate">{{ auth('admin')->user()->name }}</p>
            </div>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="sidebar-logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Sign out</span>
                </button>
            </form>
        </div>

    </aside>

    {{-- ===================== MAIN CONTENT ===================== --}}
    <div class="main-content flex-1">

        {{-- Top bar --}}
        <div class="topbar">
            <div>
                <h1 class="text-base font-bold text-gray-800">@yield('page-title', 'Dashboard')</h1>
                <p class="text-xs text-gray-400 mt-0.5">@yield('page-subtitle', 'Edfundo Pay Admin')</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="admin-badge">Admin</span>
                @yield('topbar-actions')
            </div>
        </div>

        {{-- Flash messages --}}
        <div class="px-8 pt-6">
            @if(session('success'))
                <div class="alert-success mb-5 flex items-center gap-3">
                    <i class="fas fa-check-circle text-green-500"></i>
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="alert-error mb-5 flex items-center gap-3">
                    <i class="fas fa-exclamation-circle text-red-500"></i>
                    {{ session('error') }}
                </div>
            @endif
        </div>

        {{-- Page content --}}
        <div class="px-8 pb-10">
            @yield('content')
        </div>

    </div>

</div>
@else
    @yield('content')
@endauth

@stack('scripts')
</body>
</html>
