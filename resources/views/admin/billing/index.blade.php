@extends('admin.layout')

@section('title', 'Billing')
@section('page-title', 'Billing')
@section('page-subtitle', 'Monthly Edfundo fee statements and card charges')

@section('content')

@if($needsAttention > 0)
<div class="mb-4 p-4 rounded-xl text-sm" style="background:#fffbeb;border:1px solid #fcd34d;color:#92400e;">
    <i class="fas fa-exclamation-triangle mr-1"></i>
    <strong>{{ $needsAttention }}</strong> {{ \Illuminate\Support\Str::plural('statement', $needsAttention) }} need attention (failed, no card on file, or under review).
    <a href="{{ route('admin.billing.index') }}?status=needs_review" class="underline ml-2">Under review</a>
    <a href="{{ route('admin.billing.index') }}?status=failed" class="underline ml-2">Failed</a>
    <a href="{{ route('admin.billing.index') }}?status=awaiting_card" class="underline ml-2">No card</a>
</div>
@endif

<div class="card p-4 mb-4">
    <form method="GET" action="{{ route('admin.billing.index') }}" class="flex flex-wrap items-center gap-3">
        <select name="status" class="form-input text-sm py-2 w-48">
            <option value="">All statuses</option>
            @foreach(['issued' => 'Scheduled', 'charging' => 'Processing', 'paid' => 'Paid', 'failed' => 'Payment failed', 'awaiting_card' => 'Card needed', 'needs_review' => 'Under review', 'void' => 'Void'] as $value => $label)
                <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <select name="merchant_id" class="form-input text-sm py-2 w-56">
            <option value="">All merchants</option>
            @foreach($merchants as $merchant)
                <option value="{{ $merchant->id }}" {{ request('merchant_id') == $merchant->id ? 'selected' : '' }}>{{ $merchant->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn-primary py-2 text-sm"><i class="fas fa-filter"></i> Filter</button>
        <a href="{{ route('admin.billing.index') }}" class="btn-secondary py-2 text-sm">Clear</a>
    </form>
</div>

<div class="card overflow-hidden">
    <table class="data-table w-full">
        <thead>
            <tr>
                <th class="text-left">Statement</th>
                <th class="text-left">Merchant</th>
                <th class="text-right">Fee (AED)</th>
                <th class="text-left">Status</th>
                <th class="text-left">Charging</th>
                <th class="text-left">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($statements as $statement)
            @php
                $badge = match($statement->status) {
                    'paid' => 'badge-success',
                    'failed', 'awaiting_card' => 'badge-danger',
                    'needs_review' => 'badge-warning',
                    'void' => 'badge-muted',
                    default => 'badge-info',
                };
                $canRetry = in_array($statement->status, ['failed', 'awaiting_card', 'needs_review', 'issued'], true);
                $canMarkPaid = !in_array($statement->status, ['paid', 'void', 'charging'], true);
                $canVoid = !in_array($statement->status, ['paid', 'void', 'charging'], true);
            @endphp
            <tr>
                <td>
                    <div class="text-sm font-mono text-gray-800">{{ $statement->number }}</div>
                    <div class="text-xs text-gray-400">{{ $statement->periodLabel() }} &middot; {{ rtrim(rtrim(number_format($statement->fee_percentage, 2), '0'), '.') }}% of {{ number_format($statement->volume, 2) }}</div>
                </td>
                <td class="text-sm text-gray-700">
                    <a href="{{ route('admin.merchants.show', $statement->merchant_id) }}" style="color:#3d01bd;">{{ $statement->merchant->name ?? '—' }}</a>
                </td>
                <td class="text-sm font-semibold text-gray-800 text-right">{{ number_format($statement->fee_amount, 2) }}</td>
                <td>
                    <span class="{{ $badge }}">{{ $statement->statusLabel() }}</span>
                    @if($statement->failure_message && $statement->status !== 'paid')
                        <div class="text-xs text-gray-400 mt-1" style="max-width:220px;">{{ $statement->failure_message }}</div>
                    @endif
                </td>
                <td class="text-xs text-gray-500">
                    {{ $statement->attempts }} {{ \Illuminate\Support\Str::plural('attempt', $statement->attempts) }}
                    @if($statement->next_attempt_at && in_array($statement->status, ['issued', 'awaiting_card'], true))
                        <div>next {{ $statement->next_attempt_at->copy()->setTimezone(config('billing.timezone'))->format('j M, H:i') }}</div>
                    @endif
                    @if($statement->attemptsLog->count() > 0)
                        <details class="mt-1">
                            <summary class="cursor-pointer" style="color:#3d01bd;">history</summary>
                            @foreach($statement->attemptsLog as $attempt)
                                <div class="mt-1">#{{ $attempt->attempt_number }} {{ $attempt->outcome }}
                                    @if($attempt->message) &mdash; {{ $attempt->message }} @endif
                                    @if($attempt->telr_tran_ref) <span class="font-mono">({{ $attempt->telr_tran_ref }})</span> @endif
                                </div>
                            @endforeach
                        </details>
                    @endif
                </td>
                <td>
                    <div class="flex flex-col gap-2" style="min-width:210px;">
                        @if($canRetry)
                        <form method="POST" action="{{ route('admin.billing.retry', $statement->id) }}"
                            onsubmit="return confirm('Charge {{ $statement->merchant->name ?? 'this merchant' }} AED {{ number_format($statement->fee_amount, 2) }} now?');">
                            @csrf
                            @if($statement->status === 'needs_review')
                            <label class="flex items-start gap-1 text-xs text-gray-500 mb-1">
                                <input type="checkbox" name="confirmed" value="1" class="mt-0.5"> I checked Telr: nothing was charged
                            </label>
                            @endif
                            <button type="submit" class="btn-secondary py-1 text-xs"><i class="fas fa-redo"></i> {{ $statement->status === 'issued' ? 'Charge now' : 'Retry now' }}</button>
                        </form>
                        @endif
                        @if($canMarkPaid)
                        <details>
                            <summary class="cursor-pointer text-xs" style="color:#3d01bd;">Mark paid (outside card)</summary>
                            <form method="POST" action="{{ route('admin.billing.paid', $statement->id) }}" class="mt-1 flex gap-1">
                                @csrf
                                <input type="text" name="note" required placeholder="e.g. bank transfer ref" class="form-input text-xs py-1">
                                <button type="submit" class="btn-secondary py-1 text-xs">Save</button>
                            </form>
                        </details>
                        @endif
                        @if($canVoid)
                        <form method="POST" action="{{ route('admin.billing.void', $statement->id) }}" onsubmit="return confirm('Void this statement? It will never be charged.');">
                            @csrf
                            <button type="submit" class="text-xs" style="color:#dc2626;">Void</button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center py-12 text-gray-400">No statements yet</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($statements->hasPages())
<div class="mt-4">{{ $statements->links() }}</div>
@endif
@endsection
