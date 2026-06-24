<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Invoice - {{ $invoice->merchant->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl w-full space-y-8">

            <!-- Merchant Info Card -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="text-center mb-6">
                    <div class="mx-auto h-16 w-16 rounded-full bg-blue-100 flex items-center justify-center mb-4">
                        <i class="fas fa-store text-3xl text-blue-600"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">{{ $invoice->merchant->name }}</h2>
                    <p class="text-gray-500 mt-2">Invoice Payment</p>
                </div>

                <!-- Invoice Details -->
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Invoice Details</h3>

                    <dl class="space-y-3 mb-6">
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Invoice Number</dt>
                            <dd class="text-sm text-gray-900 font-mono">{{ $invoice->uuid }}</dd>
                        </div>
                        @if($invoice->consumer)
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Customer</dt>
                            <dd class="text-sm text-gray-900">{{ $invoice->consumer->name }}</dd>
                        </div>
                        @endif
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                            <dd class="text-sm">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    @if($invoice->status->value === 10) bg-green-100 text-green-800
                                    @elseif($invoice->status->value === 20) bg-red-100 text-red-800
                                    @else bg-yellow-100 text-yellow-800
                                    @endif">
                                    {{ $invoice->status->label() }}
                                </span>
                            </dd>
                        </div>
                    </dl>

                    <!-- Invoice Items -->
                    @if($invoice->invoiceDetails->count() > 0)
                    <div class="border-t border-gray-200 pt-4 mb-6">
                        <h4 class="text-sm font-semibold text-gray-900 mb-3">Items</h4>
                        <div class="space-y-2">
                            @foreach($invoice->invoiceDetails as $detail)
                            <div class="flex justify-between items-start py-2 border-b border-gray-100 last:border-0">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900">{{ $detail->title }}</p>
                                    @if($detail->product)
                                    <p class="text-xs text-gray-500">{{ $detail->product->name }}</p>
                                    @endif
                                </div>
                                <p class="text-sm font-semibold text-gray-900">AED {{ number_format($detail->fee, 2) }}</p>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Total -->
                    <div class="border-t-2 border-gray-300 pt-4">
                        <div class="flex justify-between items-center">
                            <dt class="text-lg font-bold text-gray-900">Total Amount</dt>
                            <dd class="text-2xl font-bold text-blue-600">AED {{ number_format($invoice->total_fee, 2) }}</dd>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alerts -->
            @if(session('error'))
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                    <p class="text-sm text-red-800">{{ session('error') }}</p>
                </div>
            </div>
            @endif

            @if(session('success'))
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-500 mr-3"></i>
                    <p class="text-sm text-green-800">{{ session('success') }}</p>
                </div>
            </div>
            @endif

            <!-- Payment Action -->
            @if($invoice->status->value === 0)
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Pay by Bank</h3>
                <p class="text-sm text-gray-500 mb-6">
                    Pay securely directly from your UAE bank account using Open Finance. No card details required.
                </p>

                <form method="POST" action="{{ route('public.invoice.pay', $invoice->uuid) }}" id="paymentForm">
                    @csrf
                    <button
                        type="submit"
                        id="payButton"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-6 rounded-lg text-lg transition duration-200 flex items-center justify-center space-x-3">
                        <i class="fas fa-university text-xl"></i>
                        <span>Pay AED {{ number_format($invoice->total_fee, 2) }} by Bank</span>
                    </button>
                </form>

                <div id="loadingState" class="hidden mt-4 text-center">
                    <div class="inline-flex items-center space-x-3 text-blue-600">
                        <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span class="text-sm font-medium">Connecting to your bank securely…</span>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-center space-x-4 text-xs text-gray-400">
                    <div class="flex items-center space-x-1">
                        <i class="fas fa-lock"></i>
                        <span>256-bit encrypted</span>
                    </div>
                    <div class="flex items-center space-x-1">
                        <i class="fas fa-shield-alt"></i>
                        <span>UAE Central Bank regulated</span>
                    </div>
                </div>
            </div>

            @elseif($invoice->status->value === 10)
            <div class="bg-green-50 border border-green-200 rounded-lg p-6 text-center">
                <i class="fas fa-check-circle text-4xl text-green-600 mb-3"></i>
                <p class="text-lg font-semibold text-green-800">Invoice Paid</p>
                <p class="text-sm text-green-600 mt-2">This invoice has already been paid. Thank you!</p>
            </div>

            @elseif($invoice->status->value === 20)
            <div class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
                <i class="fas fa-times-circle text-4xl text-red-600 mb-3"></i>
                <p class="text-lg font-semibold text-red-800">Payment Failed</p>
                <p class="text-sm text-red-600 mt-2">This invoice payment failed. Please contact the merchant.</p>
            </div>
            @endif

            <!-- Footer -->
            <div class="text-center text-sm text-gray-500">
                <p>Powered by <span class="font-semibold text-blue-600">EdFundo Pay</span></p>
            </div>
        </div>
    </div>

    <script>
        const form = document.getElementById('paymentForm');
        const payButton = document.getElementById('payButton');
        const loadingState = document.getElementById('loadingState');

        if (form) {
            form.addEventListener('submit', function () {
                payButton.disabled = true;
                payButton.classList.add('opacity-50', 'cursor-not-allowed');
                loadingState.classList.remove('hidden');
            });
        }
    </script>
</body>
</html>
