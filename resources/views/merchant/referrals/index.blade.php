@extends('merchant.layout')

@section('page-title', 'Referrals')
@section('page-subtitle', 'Share your referral link and track your earnings')

@section('topbar-actions')
    @if($totalReferrals > 0)
        <a href="{{ route('merchant.referrals.export') }}" class="btn-secondary">
            <i class="fas fa-download mr-2"></i>Export CSV
        </a>
    @endif
@endsection

@section('content')

{{-- ── Referral link card ──────────────────────────────────────────────────── --}}
<div class="card" style="margin-bottom:2rem;padding:1.5rem;background:linear-gradient(135deg,#f5f3ff,#eff6ff);border-left:4px solid var(--purple);">
    <div style="font-size:0.7rem;font-weight:700;color:var(--purple);margin-bottom:0.625rem;text-transform:uppercase;letter-spacing:0.08em;">
        <i class="fas fa-gift mr-1"></i> Your Referral Link
    </div>
    <div style="display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;">
        <input type="text" id="referral-link-input" value="{{ $referralLink }}"
               readonly class="form-input"
               style="flex:1;min-width:0;font-size:0.8125rem;font-family:monospace;background:#fff;color:#374151;">
        <button onclick="copyReferralLink()" id="copy-btn" class="btn-primary" style="white-space:nowrap;">
            <i class="fas fa-copy mr-2"></i>Copy Link
        </button>
    </div>
    <p style="font-size:0.8125rem;color:#6b7280;margin-top:0.875rem;line-height:1.5;">
        Share this link with your parents. When they sign up and start a paid Edfundo subscription,
        you earn <strong>AED 50</strong> — and they receive a <strong>AED 50 reward</strong>.
    </p>
</div>

{{-- ── Stat cards ──────────────────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1.5rem;margin-bottom:2rem;">

    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,var(--purple),var(--cyan));">
            <i class="fas fa-user-plus"></i>
        </div>
        <div class="stat-value">{{ number_format($totalReferrals) }}</div>
        <div class="stat-label">Total Referrals</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#10b981,#059669);">
            <i class="fas fa-star"></i>
        </div>
        <div class="stat-value">{{ number_format($earnedCount) }}</div>
        <div class="stat-label">Qualified</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);">
            <i class="fas fa-coins"></i>
        </div>
        <div class="stat-value">AED {{ number_format($totalEarned, 0) }}</div>
        <div class="stat-label">Total Earned</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);">
            <i class="fas fa-wallet"></i>
        </div>
        <div class="stat-value">AED {{ number_format($pendingPayout, 0) }}</div>
        <div class="stat-label">Pending Payout</div>
    </div>

</div>

{{-- ── Table ────────────────────────────────────────────────────────────────── --}}
<div class="card">
    <table class="data-table">
        <thead>
            <tr>
                <th>User</th>
                <th>Registered</th>
                <th>Plan</th>
                <th>Subscribed</th>
                <th>Their Reward</th>
                <th>Your Commission</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($referrals as $referral)
            <tr>
                <td style="font-size:0.875rem;">
                    {{ $referral->edfundo_user_email ?? 'Anonymous' }}
                </td>
                <td style="font-size:0.875rem;white-space:nowrap;">
                    {{ $referral->registered_at?->format('d M Y') ?? '-' }}
                </td>
                <td>
                    @if($referral->subscription_plan)
                        <span class="badge-info">{{ $referral->subscription_plan }}</span>
                    @else
                        <span style="color:#d1d5db;font-size:0.875rem;">Not yet</span>
                    @endif
                </td>
                <td style="font-size:0.875rem;white-space:nowrap;">
                    {{ $referral->subscribed_at?->format('d M Y') ?? '-' }}
                </td>
                <td>
                    @if($referral->credited_at)
                        <span class="badge-success">
                            {{ $referral->credit_currency }} {{ number_format($referral->credit_amount, 0) }} issued
                        </span>
                    @else
                        <span style="color:#9ca3af;font-size:0.875rem;">Pending</span>
                    @endif
                </td>
                <td style="font-weight:700;font-size:0.9rem;">
                    AED {{ number_format($referral->commission_amount, 2) }}
                </td>
                <td>
                    @if($referral->commission_status === 'settled')
                        <span class="badge-success">Paid out</span>
                    @elseif($referral->commission_status === 'earned')
                        <span class="badge-warning">Earned</span>
                    @else
                        <span class="badge-danger">Pending</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align:center;padding:4rem 2rem;color:#9ca3af;">
                    <div style="font-size:2rem;margin-bottom:0.75rem;">&#127873;</div>
                    <div style="font-weight:600;font-size:1rem;color:#374151;margin-bottom:0.5rem;">No referrals yet</div>
                    <div style="font-size:0.875rem;max-width:360px;margin:0 auto;line-height:1.6;">
                        Copy your referral link above and share it with your parents to start earning AED 50 per qualified referral.
                    </div>
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

@push('scripts')
<script>
function copyReferralLink() {
    const input = document.getElementById('referral-link-input');
    const btn   = document.getElementById('copy-btn');
    input.select();
    input.setSelectionRange(0, 99999);
    try {
        navigator.clipboard.writeText(input.value).catch(() => document.execCommand('copy'));
    } catch(e) {
        document.execCommand('copy');
    }
    btn.innerHTML = '<i class="fas fa-check mr-2"></i>Copied!';
    btn.style.background = 'linear-gradient(135deg,#10b981,#059669)';
    setTimeout(() => {
        btn.innerHTML = '<i class="fas fa-copy mr-2"></i>Copy Link';
        btn.style.background = '';
    }, 2500);
}
</script>
@endpush
