<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment {{ $status === 'success' ? 'Complete' : 'Update' }} - EdFundo Pay</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-lg w-full space-y-6">

            @if($status === 'success' || ($payment && $payment->status === \App\Enums\PaymentStatus::Complete))

            {{-- Payment completed successfully --}}
            <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                <div class="mx-auto h-20 w-20 rounded-full bg-green-100 flex items-center justify-center mb-6">
                    <i class="fas fa-check text-4xl text-green-600"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Payment Submitted</h1>
                <p class="text-gray-500 mb-6">
                    Your bank transfer has been initiated. The payment will be confirmed once your bank processes it.
                </p>

                @if($invoice)
                <div class="bg-gray-50 rounded-lg p-4 text-left mb-6">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm text-gray-500">Merchant</span>
                        <span class="text-sm font-semibold text-gray-900">{{ $invoice->merchant->name }}</span>
                    </div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm text-gray-500">Amount</span>
                        <span class="text-sm font-semibold text-blue-600">AED {{ number_format($invoice->total_fee, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">Invoice</span>
                        <span class="text-xs font-mono text-gray-600">{{ $invoice->uuid }}</span>
                    </div>
                </div>
                @endif

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800">
                    <i class="fas fa-info-circle mr-2"></i>
                    You'll receive a confirmation once the payment clears. This usually takes a few seconds for UAE bank transfers.
                </div>
            </div>

            @elseif($status === 'failed' || ($payment && $payment->status === \App\Enums\PaymentStatus::Failed))

            {{-- Payment failed --}}
            <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                <div class="mx-auto h-20 w-20 rounded-full bg-red-100 flex items-center justify-center mb-6">
                    <i class="fas fa-times text-4xl text-red-600"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Payment Failed</h1>
                <p class="text-gray-500 mb-6">
                    The payment could not be completed. Please try again or contact the merchant.
                </p>

                @if($invoice)
                <a href="{{ route('public.invoice.show', $invoice->uuid) }}"
                   class="inline-block w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition duration-200 mb-3">
                    Try Again
                </a>
                @endif
            </div>

            @else

            {{-- Processing / unknown status — webhook will update shortly --}}
            <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                <div class="mx-auto h-20 w-20 rounded-full bg-yellow-100 flex items-center justify-center mb-6">
                    <svg class="animate-spin h-10 w-10 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Processing Payment</h1>
                <p class="text-gray-500 mb-6">
                    We're confirming your payment with your bank. This should only take a moment.
                </p>

                @if($invoice)
                <div class="bg-gray-50 rounded-lg p-4 text-left mb-6">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm text-gray-500">Merchant</span>
                        <span class="text-sm font-semibold text-gray-900">{{ $invoice->merchant->name }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">Amount</span>
                        <span class="text-sm font-semibold text-blue-600">AED {{ number_format($invoice->total_fee, 2) }}</span>
                    </div>
                </div>

                <p class="text-xs text-gray-400">
                    You can also check your invoice status at any time:
                    <a href="{{ route('public.invoice.show', $invoice->uuid) }}" class="text-blue-500 underline">View Invoice</a>
                </p>
                @endif
            </div>

            @endif

            <!-- Footer -->
            <div class="text-center text-sm text-gray-500">
                <p>Powered by <span class="font-semibold text-blue-600">EdFundo Pay</span></p>
            </div>
        </div>
    </div>
</body>
</html>
