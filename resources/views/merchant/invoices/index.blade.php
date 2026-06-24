@extends('merchant.layout')

@section('title', 'Invoices')

@section('content')
<div class="px-4 py-5 sm:p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Invoices</h1>
        <div class="space-x-2">
            <a href="{{ route('merchant.invoices.create-bulk') }}" class="bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-layer-group mr-2"></i>Bulk Create
            </a>
            <a href="{{ route('merchant.invoices.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-plus mr-2"></i>Create Invoice
            </a>
        </div>
    </div>

    <x-filter-sort 
        :route="route('merchant.invoices.index')"
        :filters="[
            'search' => true,
            'status' => true,
            'consumer_id' => true,
            'group_id' => true,
            'date_from' => true,
            'date_to' => true,
            'status_options' => [0 => 'Draft', 10 => 'Paid', 20 => 'Failed'],
            'consumers' => $consumers ?? [],
            'groups' => $groups ?? [],
            'sort_options' => ['created_at' => 'Created At', 'updated_at' => 'Updated At', 'total_fee' => 'Total Fee', 'status' => 'Status']
        ]"
        :sortBy="request('sort_by', 'created_at')"
        :sortDir="request('sort_dir', 'desc')"
    />

    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <ul class="divide-y divide-gray-200">
            @forelse($invoices as $invoice)
            <li>
                <div class="px-4 py-4 sm:px-6 hover:bg-gray-50 cursor-pointer" onclick="window.location.href='{{ route('merchant.invoices.show', $invoice->id) }}'">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="h-10 w-10 rounded-full bg-purple-300 flex items-center justify-center">
                                    <i class="fas fa-file-invoice text-purple-700"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">Invoice #{{ $invoice->id }}</div>
                                <div class="text-sm text-gray-500">UUID: {{ $invoice->uuid }}</div>
                                <div class="text-sm text-gray-500">Consumer: {{ $invoice->consumer->name ?? 'N/A' }}</div>
                                <div class="text-sm text-gray-900 font-semibold mt-1">Total: AED {{ number_format($invoice->total_fee, 2) }}</div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4" onclick="event.stopPropagation();">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                @if($invoice->status->value === 10) bg-green-100 text-green-800
                                @elseif($invoice->status->value === 20) bg-red-100 text-red-800
                                @else bg-yellow-100 text-yellow-800
                                @endif">
                                {{ $invoice->status->label() }}
                            </span>
                            <button 
                                onclick="event.preventDefault(); event.stopPropagation(); copyPaymentLink('{{ route('public.invoice.show', $invoice->uuid) }}', '{{ $invoice->id }}')" 
                                class="text-purple-600 hover:text-purple-900"
                                title="Copy Payment Link"
                                id="copyBtn{{ $invoice->id }}">
                                <i class="fas fa-link"></i>
                            </button>
                            <a href="{{ route('merchant.invoices.show', $invoice->id) }}" class="text-blue-600 hover:text-blue-900" onclick="event.stopPropagation();">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('merchant.invoices.edit', $invoice->id) }}" class="text-yellow-600 hover:text-yellow-900" onclick="event.stopPropagation();">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="{{ route('merchant.invoices.delete', $invoice->id) }}" class="inline" onsubmit="event.stopPropagation(); return confirm('Are you sure you want to delete this invoice?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </li>
            @empty
            <li class="px-4 py-8 text-center text-gray-500">
                No invoices found. <a href="{{ route('merchant.invoices.create') }}" class="text-blue-500 hover:underline">Create your first invoice</a>
            </li>
            @endforelse
        </ul>
    </div>

    @if($invoices->hasPages())
    <div class="mt-4">
        {{ $invoices->links() }}
    </div>
    @endif

    <div class="mt-4 text-sm text-gray-500">
        Showing {{ $invoices->firstItem() ?? 0 }} to {{ $invoices->lastItem() ?? 0 }} of {{ $invoices->total() }} results
    </div>
</div>

<script>
function copyPaymentLink(link, invoiceId) {
    navigator.clipboard.writeText(link).then(function() {
        const button = document.getElementById('copyBtn' + invoiceId);
        const originalHTML = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check text-green-600"></i>';
        button.classList.remove('text-purple-600', 'hover:text-purple-900');
        button.classList.add('text-green-600');
        
        setTimeout(function() {
            button.innerHTML = originalHTML;
            button.classList.remove('text-green-600');
            button.classList.add('text-purple-600', 'hover:text-purple-900');
        }, 2000);
    }).catch(function(err) {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = link;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        
        const button = document.getElementById('copyBtn' + invoiceId);
        const originalHTML = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check text-green-600"></i>';
        button.classList.remove('text-purple-600', 'hover:text-purple-900');
        button.classList.add('text-green-600');
        
        setTimeout(function() {
            button.innerHTML = originalHTML;
            button.classList.remove('text-green-600');
            button.classList.add('text-purple-600', 'hover:text-purple-900');
        }, 2000);
    });
}
</script>
@endsection

