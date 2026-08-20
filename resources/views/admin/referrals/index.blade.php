@extends('admin.layout')

@section('page-title', 'Referrals')
@section('page-subtitle', 'Merchant referral activity and commission ledger')

@section('topbar-actions')
    <a href="{{ route('admin.referrals.import') }}" class="btn-secondary">
        <i class="fas fa-upload mr-2"></i>Import Signups
    </a>
    <a href="{{ route('admin.referrals.export') }}" class="btn-secondary">
        <i class="fas fa-download mr-2"></i>Export CSV
    </a>
@endsection

@section('content')

{{-- ── Stat cards ──────────────────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1.5rem;margin-bottom:2rem;">

    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,var(--purple),var(--cyan));">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-value">{{ number_format($totalReferrals) }}</div>
        <div class="stat-label">Total Referrals</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#ef4444,#dc2626);">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-value">{{ number_format($pendingCount) }}</div>
        <div class="stat-label">Pending</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);">
            <i class="fas fa-star"></i>
        </div>
        <div class="stat-value">{{ number_format($earnedCount) }}</div>
        <div class="stat-label">Commissions Earned</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#10b981,#059669);">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-value">{{ number_format($settledCount) }}</div>
        <div class="stat-label">Settled</div>
    </div>

</div>

{{-- ── Commission summary banner ───────────────────────────────────────────── --}}
<div class="card" style="margin-bottom:2rem;padding:1.5rem;display:flex;gap:2.5rem;align-items:center;flex-wrap:wrap;background:linear-gradient(135deg,#f5f3ff,#eff6ff);border-left:4px solid var(--purple);">
    <div>
        <div style="font-size:0.7rem;text-transform:uppercase;letter-spacing:0.08em;color:#6b7280;margin-bottom:0.25rem;">Awaiting Payout</div>
        <div style="font-size:1.75rem;font-weight:800;color:var(--purple);">AED {{ number_format($totalCommissionEarned - $totalCommissionSettled, 2) }}</div>
    </div>
    <div style="width:1px;background:#e5e7eb;height:3rem;"></div>
    <div>
        <div style="font-size:0.7rem;text-transform:uppercase;letter-spacing:0.08em;color:#6b7280;margin-bottom:0.25rem;">Total Earned (all time)</div>
        <div style="font-size:1.75rem;font-weight:800;color:#d97706;">AED {{ number_format($totalCommissionEarned, 2) }}</div>
    </div>
    <div style="width:1px;background:#e5e7eb;height:3rem;"></div>
    <div>
        <div style="font-size:0.7rem;text-transform:uppercase;letter-spacing:0.08em;color:#6b7280;margin-bottom:0.25rem;">Total Settled</div>
        <div style="font-size:1.75rem;font-weight:800;color:#059669;">AED {{ number_format($totalCommissionSettled, 2) }}</div>
    </div>
</div>

{{-- ── Flash messages ──────────────────────────────────────────────────────── --}}
@if(session('success'))
    <div class="alert-success" style="margin-bottom:1.5rem;">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert-error" style="margin-bottom:1.5rem;">{{ session('error') }}</div>
@endif

{{-- ── Filters ──────────────────────────────────────────────────────────────── --}}
<div class="card" style="margin-bottom:1.5rem;padding:1.25rem;">
    <form method="GET" action="{{ route('admin.referrals.index') }}"
          style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap;">
        <div style="flex:1;min-width:200px;">
            <label style="display:block;font-size:0.7rem;font-weight:700;color:#374151;margin-bottom:0.375rem;text-transform:uppercase;letter-spacing:0.06em;">
                Search
            </label>
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Merchant UUID, user email or user ID…"
                   class="form-input">
        </div>
        <div style="min-width:160px;">
            <label style="display:block;font-size:0.7rem;font-weight:700;color:#374151;margin-bottom:0.375rem;text-transform:uppercase;letter-spacing:0.06em;">
                Commission Status
            </label>
            <select name="status" class="form-input">
                <option value="">All statuses</option>
                <option value="pending"  @selected(request('status') === 'pending')>Pending</option>
                <option value="earned"   @selected(request('status') === 'earned')>Earned</option>
                <option value="settled"  @selected(request('status') === 'settled')>Settled</option>
            </select>
        </div>
        <button type="submit" class="btn-primary">Filter</button>
        @if(request()->hasAny(['search','status']))
            <a href="{{ route('admin.referrals.index') }}" class="btn-secondary">Clear</a>
        @endif
    </form>
</div>

{{-- ── Table ────────────────────────────────────────────────────────────────── --}}
<div class="card">
    <table class="data-table">
        <thead>
            <tr>
                <th>Merchant</th>
                <th>User</th>
                <th>Registered</th>
                <th>Subscribed</th>
                <th>Plan</th>
                <th>Credit Issued</th>
                <th>Commission</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($referrals as $referral)
            <tr>
                <td>
                    <div style="font-weight:600;font-size:0.875rem;color:#111827;">
                        {{ optional($referral->merchant)->name ?? '—' }}
                    </div>
                    <div style="font-size:0.7rem;color:#9ca3af;font-family:monospace;margin-top:2px;">
                        {{ substr($referral->merchant_uuid, 0, 18) }}…
                    </div>
                </td>
                <td>
                    <div style="font-size:0.875rem;">{{ $referral->edfundo_user_email ?? '-' }}</div>
                    <div style="font-size:0.7rem;color:#9ca3af;font-family:monospace;margin-top:2px;">
                        {{ $referral->edfundo_user_id }}
                    </div>
                </td>
                <td style="font-size:0.875rem;white-space:nowrap;">
                    {{ $referral->registered_at?->format('d M Y') ?? '-' }}
                    @if($referral->registered_at)
                        <div style="font-size:0.7rem;color:#9ca3af;">{{ $referral->registered_at->format('H:i') }}</div>
                    @endif
                </td>
                <td style="font-size:0.875rem;white-space:nowrap;">
                    {{ $referral->subscribed_at?->format('d M Y') ?? '-' }}
                    @if($referral->subscribed_at)
                        <div style="font-size:0.7rem;color:#9ca3af;">{{ $referral->subscribed_at->format('H:i') }}</div>
                    @endif
                </td>
                <td>
                    @if($referral->subscription_plan)
                        <span class="badge-info">{{ $referral->subscription_plan }}</span>
                    @else
                        <span style="color:#d1d5db;font-size:0.875rem;">-</span>
                    @endif
                </td>
                <td style="font-size:0.875rem;">
                    @if($referral->credited_at)
                        <div style="white-space:nowrap;">{{ $referral->credited_at->format('d M Y') }}</div>
                        <div style="font-size:0.7rem;color:#6b7280;">
                            {{ $referral->credit_currency }} {{ number_format($referral->credit_amount, 2) }}
                        </div>
                    @else
                        <span style="color:#d1d5db;">-</span>
                    @endif
                </td>
                <td style="font-weight:700;font-size:0.9rem;">
                    AED {{ number_format($referral->commission_amount, 2) }}
                </td>
                <td>
                    @if($referral->commission_status === 'settled')
                        <span class="badge-success">Settled</span>
                        <div style="font-size:0.7rem;color:#6b7280;margin-top:3px;">
                            {{ $referral->commission_settled_at?->format('d M Y') }}
                        </div>
                    @elseif($referral->commission_status === 'earned')
                        <span class="badge-warning">Earned</span>
                    @else
                        <span class="badge-danger">Pending</span>
                    @endif
                </td>
                <td>
                    @if($referral->commission_status === 'earned')
                        <form method="POST"
                              action="{{ route('admin.referrals.settle', $referral->id) }}"
                              onsubmit="return confirm('Mark this commission as settled?')">
                            @csrf
                            <button type="submit" class="btn-primary"
                                    style="padding:0.3rem 0.7rem;font-size:0.75rem;">
                                Settle
                            </button>
                        </form>
                    @else
                        <span style="color:#d1d5db;font-size:0.875rem;">-</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" style="text-align:center;padding:3rem;color:#9ca3af;">
                    No referrals found.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($referrals->hasPages())
        <div style="padding:1rem 1.5rem;border-top:1px solid #f3f4f6;">
            {{ $referrals->links() }}
        </div>
    @endif
</div>

@endsection
