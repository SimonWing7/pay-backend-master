<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Payment - {{ $invoice->merchant->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.leantech.me/link/loader/prod/ae/latest/lean-link-loader.min.js"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex flex-col items-center justify-start py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl w-full space-y-6">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">{{ $invoice->merchant->name }}</h2>
                        <p class="text-sm text-gray-500 mt-1">Complete your bank payment</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-500">Amount Due</p>
                        <p class="text-2xl font-bold text-blue-600">AED {{ number_format($invoice->total_fee, 2) }}</p>
                    </div>
                </div>
            </div>
            <div id="sdkLoading" class="bg-white rounded-lg shadow-lg p-8 text-center">
                <div class="flex flex-col items-center space-y-4">
                    <svg class="animate-spin h-10 w-10 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <p class="text-gray-600 font-medium">Launching secure bank connection…</p>
                    <p class="text-sm text-gray-400">You will be redirected to your bank momentarily.</p>
                </div>
            </div>
            <div id="sdkError" class="hidden bg-red-50 border border-red-200 rounded-lg p-6 text-center">
                <i class="fas fa-exclamation-circle text-4xl text-red-500 mb-3"></i>
                <p class="text-lg font-semibold text-red-800">Something went wrong</p>
                <p class="text-sm text-red-600 mt-2" id="sdkErrorMessage">The payment session could not be loaded. Please go back and try again.</p>
                <a href="{{ route('public.invoice.show', $invoice->uuid) }}"
                   class="inline-block mt-4 px-6 py-2 bg-gray-700 text-white rounded-lg text-sm hover:bg-gray-800 transition">
                    Go Back
                </a>
            </div>
            <div class="text-center text-sm text-gray-500">
                <div class="flex items-center justify-center space-x-4 mb-2">
                    <div class="flex items-center space-x-1">
                        <i class="fas fa-lock text-gray-400"></i>
                        <span>256-bit encrypted</span>
                    </div>
                    <div class="flex items-center space-x-1">
                        <i class="fas fa-shield-alt text-gray-400"></i>
                        <span>UAE Central Bank regulated</span>
                    </div>
                </div>
                <p>Powered by <span class="font-semibold text-blue-600">EdFundo Pay</span></p>
            </div>
        </div>
    </div>
    <script>
        const paymentIntentId    = @json($paymentIntentId);
        const leanAppToken       = @json($leanAppToken);
        const leanSandbox        = @json($leanSandbox);
        const leanCustomerToken  = @json($leanCustomerToken);
        const invoiceUuid        = @json($invoice->uuid);
        const baseReturnUrl      = @json(route('public.payment.return'));
        const successRedirectUrl = baseReturnUrl + '?intent_id=' + encodeURIComponent(paymentIntentId) + '&status=success';
        const failRedirectUrl    = baseReturnUrl + '?intent_id=' + encodeURIComponent(paymentIntentId) + '&status=failed';

        function showError(message) {
            document.getElementById('sdkLoading').style.display = 'none';
            document.getElementById('sdkError').classList.remove('hidden');
            if (message) {
                document.getElementById('sdkErrorMessage').textContent = message;
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Lean === 'undefined') {
                showError('Payment SDK could not be loaded. Please check your connection and try again.');
                return;
            }
            if (!paymentIntentId) {
                showError('No payment session found. Please go back and try again.');
                return;
            }
            try {
                Lean.checkout({
                    app_token:            leanAppToken,
                    payment_intent_id:    paymentIntentId,
                    access_token:         leanCustomerToken || undefined,
                    success_redirect_url: successRedirectUrl,
                    fail_redirect_url:    failRedirectUrl,
                    sandbox:              leanSandbox,
                    callback: function (response) {
                        const status = (response && response.status)
                            ? response.status.toUpperCase()
                            : 'UNKNOWN';
                        if (status === 'SUCCESS') {
                            window.location.href = successRedirectUrl;
                        } else if (status === 'CANCELLED') {
                            window.location.href = '/invoice/' + invoiceUuid + '?cancelled=1';
                        } else if (status === 'REDIRECT') {
                            // Lean is redirecting to bank — do nothing
                        } else {
                            window.location.href = failRedirectUrl;
                        }
                    },
                });
                document.getElementById('sdkLoading').style.display = 'none';
            } catch (e) {
                console.error('Lean.pay() exception:', e);
                showError('The payment session could not be started. Please go back and try again.');
            }
        });
    </script>
</body>
</html>
