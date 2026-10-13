@extends('merchant.layout')

@section('title', 'Statement ' . $statement->number)
@section('page-title', 'Statement ' . $statement->number)
@section('page-subtitle', 'Edfundo Pay fees for ' . $statement->periodLabel())

@section('topbar-actions')
    <a href="{{ route('merchant.billing.index') }}" class="btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    <button type="button" onclick="window.print()" class="btn-primary"><i class="fas fa-print"></i> Print / save as PDF</button>
@endsection

@section('content')
@php $tz = config('billing.timezone', 'Asia/Dubai'); @endphp

<style>
    @media print {
        .sidebar, .topbar { display: none !important; }
        .main-content { margin-left: 0 !important; background: #fff !important; }
        .statement-sheet { box-shadow: none !important; border: none !important; }
    }
</style>

<div class="card statement-sheet p-10 max-w-3xl">
    <div class="flex justify-between items-start mb-8">
        <div>
            <p class="text-2xl font-extrabold" style="color:#000026;">Edfundo <span style="color:#3d01bd;">Pay</span></p>
            <p class="text-xs text-gray-400 mt-1">Monthly fee statement</p>
        </div>
        <div class="text-right">
            <p class="text-sm font-bold text-gray-800">{{ $statement->number }}</p>
            <p class="text-xs text-gray-400">Issued {{ $statement->issued_at?->copy()->setTimezone($tz)->format('j F Y') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-6 mb-8">
        <div>
            <p class="text-xs text-gray-400 font-medium uppercase tracking-wide mb-1">Billed to</p>
            <p class="text-sm font-semibold text-gray-800">{{ $merchant->merchant_trading_name ?: $merchant->name }}</p>
            @if($merchant->merchant_trading_name && $merchant->merchant_trading_name !== $merchant->name)
                <p class="text-xs text-gray-500">{{ $merchant->name }}</p>
            @endif
            <p class="text-xs text-gray-500">{{ $merchant->email }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-400 font-medium uppercase tracking-wide mb-1">Period</p>
            <p class="text-sm font-semibold text-gray-800">{{ $statement->period_start->format('j M Y') }} &ndash; {{ $statement->period_end->format('j M Y') }}</p>
            <p class="text-xs text-gray-500">Payments completed through Edfundo Pay</p>
        </div>
    </div>

    <table class="w-full mb-6" style="border-collapse:collapse;">
        <thead>
            <tr style="border-bottom:2px solid #e5e7eb;">
                <th class="text-left text-xs text-gray-400 uppercase tracking-wide py-2">Description</th>
                <th class="text-right text-xs text-gray-400 uppercase tracking-wide py-2">Amount (AED)</th>
            </tr>
        </thead>
        <tbody>
            <tr style="border-bottom:1px solid #f3f4f6;">
                <td class="py-3 text-sm text-gray-600">Completed payments processed</td>
                <td class="py-3 text-sm text-gray-800 text-right">{{ number_format($statement->volume, 2) }}</td>
            </tr>
            <tr style="border-bottom:1px solid #f3f4f6;">
                <td class="py-3 text-sm text-gray-600">Edfundo fee ({{ rtrim(rtrim(number_format($statement->fee_percentage, 2), '0'), '.') }}%)</td>
                <td class="py-3 text-sm text-gray-800 text-right">{{ number_format($statement->fee_amount, 2) }}</td>
            </tr>
            <tr>
                <td class="pt-4 text-base font-bold text-gray-900">Total due</td>
                <td class="pt-4 text-xl font-extrabold text-right" style="color:#3d01bd;">AED {{ number_format($statement->fee_amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="p-4 rounded-lg bg-gray-50 text-sm text-gray-600">
        @if($statement->status === 'paid')
            <strong class="text-green-700">Paid</strong> on {{ $statement->paid_at?->copy()->setTimezone($tz)->format('j F Y') }}
            @if($statement->paid_method === 'card')
                by card.
            @elseif($statement->paid_method === 'manual')
                (settled outside the card payment).
            @endif
        @elseif($statement->status === 'void')
            This statement has been cancelled.
        @elseif(in_array($statement->status, ['failed', 'awaiting_card', 'needs_review'], true))
            <strong>Payment outstanding.</strong>
            @if($statement->status === 'awaiting_card')
                Please <a href="{{ route('merchant.billing.index') }}" style="color:#3d01bd;">add a card</a> so we can collect it.
            @elseif($statement->status === 'failed')
                We couldn't collect this by card. Please <a href="{{ route('merchant.billing.index') }}" style="color:#3d01bd;">update your card</a> or contact us.
            @else
                We're checking the status of this payment with our provider.
            @endif
        @else
            Due to be charged to your registered card on <strong>{{ $statement->charge_due_at->copy()->setTimezone($tz)->format('j F Y') }}</strong>.
        @endif
    </div>
</div>
@endsection
