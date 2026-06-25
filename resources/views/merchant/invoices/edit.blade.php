@extends('merchant.layout')

@section('title', 'Edit Payment Link')
@section('page-title', 'Edit Payment Link')
@section('page-subtitle', 'Update payment link details')

@section('topbar-actions')
    <a href="{{ route('merchant.invoices.show', $invoice->id) }}" class="btn-secondary">
        <i class="fas fa-arrow-left"></i> Back
    </a>
@endsection

@section('content')
<div class="max-w-lg">

    {{-- Status notice --}}
    @php
        $statusValue = $invoice->status->value ?? $invoice->status;
        if ($statusValue == 10) {
            $statusLabel = 'Paid';
            $statusClass = 'background: rgba(16,185,129,0.1); color: #065f46; border: 1px solid rgba(16,185,129,0.3);';
            $statusIcon  = 'fa-check-circle';
        } elseif ($statusValue == 20) {
            $statusLabel = 'Failed';
            $statusClass = 'background: rgba(239,68,68,0.1); color: #991b1b; border: 1px solid rgba(239,68,68,0.3);';
            $statusIcon  = 'fa-times-circle';
        } else {
            $statusLabel = 'Draft';
            $statusClass = 'background: rgba(107,114,128,0.1); color: #374151; border: 1px solid rgba(107,114,128,0.25);';
            $statusIcon  = 'fa-clock';
        }
        $isPaid = ($statusValue == 10);
    @endphp

    <div class="card p-4 mb-4 flex items-center gap-3" style="{{ $statusClass }} border-radius: 10px;">
        <i class="fas {{ $statusIcon }}" style="font-size: 16px;"></i>
        <div>
            <p class="text-sm font-semibold">Status: {{ $statusLabel }}</p>
            @if($isPaid)
                <p class="text-xs" style="opacity: 0.75; margin-top: 2px;">This payment link has been paid. Amount cannot be changed.</p>
            @else
                <p class="text-xs" style="opacity: 0.75; margin-top: 2px;">Status is set automatically when a payer completes their payment — it cannot be changed manually.</p>
            @endif
        </div>
    </div>

    <div class="card p-6">
        <form method="POST" action="{{ route('merchant.invoices.update', $invoice->id) }}">
            @csrf
            @method('PUT')

            <div class="mb-6">
                <label for="total_fee" class="form-label">Amount (AED)</label>
                @if($isPaid)
                    {{-- Paid invoices: show amount as read-only --}}
                    <div class="form-input" style="background: #f9fafb; color: #6b7280; cursor: not-allowed;">
                        {{ number_format($invoice->total_fee, 2) }}
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Amount cannot be changed after a payment has been made.</p>
                @else
                    <input type="number" step="0.01" name="total_fee" id="total_fee"
                        value="{{ old('total_fee', $invoice->total_fee) }}" min="0.01"
                        class="form-input @error('total_fee') border-red-400 @enderror">
                    @error('total_fee')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                @endif
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('merchant.invoices.show', $invoice->id) }}" class="btn-secondary flex-1 justify-center">Cancel</a>
                @if(!$isPaid)
                    <button type="submit" class="btn-primary flex-1 justify-center">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                @endif
            </div>
        </form>
    </div>

    {{-- Info panel --}}
    <div class="card p-5 mt-4" style="background: linear-gradient(135deg, rgba(61,1,189,0.04), rgba(0,189,255,0.04));">
        <p class="text-xs font-semibold text-gray-600 mb-1"><i class="fas fa-info-circle text-blue-400 mr-1"></i> How payment status works</p>
        <p class="text-xs text-gray-500">Payment links start as <strong>Draft</strong>. When a payer completes their payment via the Edfundo app, the link is automatically marked as <strong>Paid</strong> and recorded in your payments history. This ensures accurate reconciliation.</p>
    </div>

</div>
@endsection
