@extends('merchant.layout')

@section('title', 'Billing')
@section('page-title', 'Billing')
@section('page-subtitle', 'Your Edfundo Pay fees and the card they are charged to')

@section('content')

<style>details > summary::-webkit-details-marker { display: none; }</style>

@if(session('info'))
<div class="mb-5 p-4 rounded-xl text-sm" style="background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;">
    <i class="fas fa-info-circle mr-1"></i> {{ session('info') }}
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

    {{-- Upcoming billing --}}
    <div class="card p-6">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Upcoming billing</h3>

        @if($merchant->billing_enabled)
            <p class="text-xs text-gray-400 font-medium mb-1">{{ $upcoming['period_label'] }} so far</p>
            <p class="text-3xl font-extrabold gradient-text mb-1">AED {{ number_format($upcoming['fee'], 2) }}</p>
            <p class="text-sm text-gray-500 mb-4">
                {{ rtrim(rtrim(number_format($upcoming['fee_percentage'], 2), '0'), '.') }}% of AED {{ number_format($upcoming['volume'], 2) }} in completed payments
            </p>
            <div class="text-sm text-gray-600 space-y-1 p-3 rounded-lg bg-gray-50">
                <p><i class="fas fa-file-invoice text-gray-300 w-5"></i> Statement emailed on <strong>{{ $upcoming['invoice_date']->format('j F Y') }}</strong></p>
                <p><i class="fas fa-credit-card text-gray-300 w-5"></i>
                    @if($card)
                        Card charged on <strong>{{ $upcoming['charge_date']->format('j F Y') }}</strong>
                    @else
                        <span class="text-red-600 font-medium">Add a card below so we can collect this on {{ $upcoming['charge_date']->format('j F Y') }}</span>
                    @endif
                </p>
            </div>
            <p class="text-xs text-gray-400 mt-3">This is an estimate that grows as payments complete. The final amount is on your statement.</p>
        @else
            <p class="text-sm text-gray-500">Monthly fee billing isn't active on your account yet. We'll let you know before it starts.</p>
        @endif
    </div>

    {{-- Card on file --}}
    <div class="card p-6">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Payment card</h3>

        @if($card)
            <div class="flex items-center gap-3 mb-1">
                <div class="stat-icon" style="width:40px;height:40px;font-size:16px;"><i class="fas fa-credit-card"></i></div>
                <div>
                    <p class="text-sm font-bold text-gray-800">{{ $card->card_type ?: 'Card' }} ending {{ $card->last4 }}</p>
                    @if($card->expiry_month && $card->expiry_year)
                        <p class="text-xs {{ $card->isExpired() ? 'text-red-600 font-semibold' : 'text-gray-400' }}">
                            {{ $card->isExpired() ? 'Expired' : 'Expires' }} {{ sprintf('%02d/%d', $card->expiry_month, $card->expiry_year) }}
                        </p>
                    @endif
                </div>
            </div>
            <p class="text-xs text-gray-400 mb-1">Your monthly fees are charged to this card.</p>
            <p class="text-xs text-green-700 mb-4">
                <i class="fas fa-check-circle"></i>
                Monthly charges authorised{{ $card->consent_at ? ' on ' . $card->consent_at->copy()->setTimezone(config('billing.timezone'))->format('j F Y') : '' }}.
            </p>
        @elseif($pendingCard)
            <p class="text-sm text-gray-600 mb-3">You started registering a card but it hasn't been completed yet.</p>
            <form method="POST" action="{{ route('merchant.billing.card.check', $pendingCard->id) }}" class="mb-4">
                @csrf
                <button type="submit" class="btn-secondary"><i class="fas fa-sync"></i> Check status</button>
            </form>
        @else
            <p class="text-sm text-gray-500 mb-4">No card registered yet.</p>
        @endif

        @if($card)
            <details class="border-t border-gray-100 pt-4" {{ $errors->has('consent') ? 'open' : '' }}>
                <summary class="btn-secondary" style="cursor:pointer;list-style:none;display:inline-flex;"><i class="fas fa-exchange-alt"></i> Replace card</summary>
                <div class="mt-4">
                    @include('merchant.billing._card-form', ['card' => $card, 'withTopBorder' => false])
                </div>
            </details>
        @else
            @include('merchant.billing._card-form', ['card' => null, 'withTopBorder' => true])
        @endif
    </div>
</div>

{{-- Statements --}}
<div class="card overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="text-sm font-semibold text-gray-700">Statements</h3>
    </div>
    @if($statements->count() > 0)
    <table class="data-table w-full">
        <thead>
            <tr>
                <th class="text-left">Statement</th>
                <th class="text-left">Period</th>
                <th class="text-right">Payments processed</th>
                <th class="text-right">Fee (AED)</th>
                <th class="text-left">Status</th>
                <th class="text-right"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($statements as $statement)
            <tr>
                <td class="text-sm font-mono text-gray-700">{{ $statement->number }}</td>
                <td class="text-sm text-gray-600">{{ $statement->periodLabel() }}</td>
                <td class="text-sm text-gray-600 text-right">{{ number_format($statement->volume, 2) }}</td>
                <td class="text-sm font-semibold text-gray-800 text-right">{{ number_format($statement->fee_amount, 2) }}</td>
                <td>
                    @php
                        $badge = match($statement->status) {
                            'paid' => 'badge-success',
                            'failed', 'awaiting_card' => 'badge-danger',
                            'needs_review' => 'badge-warning',
                            'void' => 'badge-muted',
                            default => 'badge-info',
                        };
                    @endphp
                    <span class="{{ $badge }}">{{ $statement->statusLabel() }}</span>
                </td>
                <td class="text-right">
                    <a href="{{ route('merchant.billing.statement', $statement->number) }}" class="text-xs font-medium" style="color:#3d01bd;">View</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="p-10 text-center">
        <p class="text-gray-500 font-medium">No statements yet</p>
        <p class="text-gray-400 text-sm mt-1">Your first statement will appear here after the end of the month.</p>
    </div>
    @endif
</div>
@endsection
