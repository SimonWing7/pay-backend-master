@extends('merchant.layout')

@section('title', 'Invoice Details')

@section('content')
<div class="px-4 py-5 sm:p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Invoice Details</h1>
        <div class="space-x-2">
            <a href="{{ route('merchant.invoices.edit', $invoice->id) }}" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-edit mr-2"></i>Edit
            </a>
            <a href="{{ route('merchant.invoices.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Back to List
            </a>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
            <div>
                <dt class="text-sm font-medium text-gray-500">Invoice ID</dt>
                <dd class="mt-1 text-sm text-gray-900">#{{ $invoice->id }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">UUID</dt>
                <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $invoice->uuid }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-sm font-medium text-gray-500 mb-2">Payment Link</dt>
                <dd class="mt-1">
                    <div class="flex items-center space-x-2">
                        <input type="text" 
                            id="paymentLink" 
                            value="{{ route('public.invoice.show', $invoice->uuid) }}" 
                            readonly
                            class="flex-1 shadow appearance-none border rounded py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-gray-50 text-sm font-mono">
                        <button 
                            onclick="copyPaymentLink('{{ route('public.invoice.show', $invoice->uuid) }}')" 
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded flex items-center space-x-2"
                            id="copyButton">
                            <i class="fas fa-copy"></i>
                            <span>Copy</span>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Share this link with your customer to pay</p>
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Total Fee</dt>
                <dd class="mt-1 text-sm text-gray-900 font-semibold">AED {{ number_format($invoice->total_fee, 2) }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Status</dt>
                <dd class="mt-1">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                        @if($invoice->status->value === 10) bg-green-100 text-green-800
                        @elseif($invoice->status->value === 20) bg-red-100 text-red-800
                        @else bg-yellow-100 text-yellow-800
                        @endif">
                        {{ $invoice->status->label() }}
                    </span>
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Consumer</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $invoice->consumer->name ?? 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Created At</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $invoice->created_at->format('Y-m-d H:i:s') }}</dd>
            </div>
        </dl>
    </div>

    @if($invoice->invoiceDetails->count() > 0)
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Invoice Details</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fee</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($invoice->invoiceDetails as $detail)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $detail->product->name ?? 'N/A' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $detail->title }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">AED {{ number_format($detail->fee, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

<script>
function copyPaymentLink(link) {
    navigator.clipboard.writeText(link).then(function() {
        const button = document.getElementById('copyButton');
        const originalHTML = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check"></i> <span>Copied!</span>';
        button.classList.remove('bg-blue-500', 'hover:bg-blue-700');
        button.classList.add('bg-green-500');
        
        setTimeout(function() {
            button.innerHTML = originalHTML;
            button.classList.remove('bg-green-500');
            button.classList.add('bg-blue-500', 'hover:bg-blue-700');
        }, 2000);
    }).catch(function(err) {
        // Fallback for older browsers
        const input = document.getElementById('paymentLink');
        input.select();
        document.execCommand('copy');
        
        const button = document.getElementById('copyButton');
        const originalHTML = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check"></i> <span>Copied!</span>';
        button.classList.remove('bg-blue-500', 'hover:bg-blue-700');
        button.classList.add('bg-green-500');
        
        setTimeout(function() {
            button.innerHTML = originalHTML;
            button.classList.remove('bg-green-500');
            button.classList.add('bg-blue-500', 'hover:bg-blue-700');
        }, 2000);
    });
}
</script>
@endsection

