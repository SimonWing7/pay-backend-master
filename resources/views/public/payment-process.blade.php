<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Payment - {{ $invoice->merchant->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    {{-- NymCard Web SDK --}}
    <script src="https://web-sdk.nymcard.com/openfinance/latest/nymcard-openfinance.js"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex flex-col items-center justify-start py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl w-full space-y-6">

            <!-- Header -->
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

            <!-- Loading state (shown while SDK initialises) -->
            <div id="sdkLoading" class="bg-white rounded-lg shadow-lg p-8 text-center">
                <div class="flex flex-col items-center space-y-4">
                    <svg class="animate-spin h-10 w-10 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <p class="text-gray-600 font-medium">Loading secure bank connection…</p>
                </div>
            </div>

            <!-- NymCard Web SDK renders here -->
            <div id="nymcard-openfinance" class="bg-white rounded-lg shadow-lg overflow-hidden" style="display:none; min-height: 500px;"></div>

            <!-- Error state -->
            <div id="sdkError" class="hidden bg-red-50 border border-red-200 rounded-lg p-6 text-center">
                <i class="fas fa-exclamation-circle text-4xl text-red-500 mb-3"></i>
                <p class="text-lg font-semibold text-red-800">Something went wrong</p>
                <p class="text-sm text-red-600 mt-2" id="sdkErrorMessage">The payment session could not be loaded. Please go back and try again.</p>
                <a href="{{ route('public.invoice.show', $invoice->uuid) }}"
                   class="inline-block mt-4 px-6 py-2 bg-gray-700 text-white rounded-lg text-sm hover:bg-gray-800 transition">
                    Go Back
                </a>
            </div>

            <!-- Footer -->
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
        const sdkToken = @json($sdkToken);
        const resourceId = @json($resourceId);
        const invoiceUuid = @json($invoice->uuid);
        const returnUrl = @json(route('public.payment.return'));

        function showError(message) {
            document.getElementById('sdkLoading').style.display = 'none';
            document.getElementById('nymcard-openfinance').style.display = 'none';
            document.getElementById('sdkError').classList.remove('hidden');
            if (message) {
                document.getElementById('sdkErrorMessage').textContent = message;
            }
        }

        function collectDeviceInformation() {
            return {
                browserAcceptHeader: navigator.languages ? navigator.languages.join(',') : navigator.language,
                browserColorDepth: String(screen.colorDepth),
                browserJavaEnabled: String(navigator.javaEnabled ? navigator.javaEnabled() : false),
                browserLanguage: navigator.language,
                browserScreenHeight: String(screen.height),
                browserScreenWidth: String(screen.width),
                browserTimeZone: String(new Date().getTimezoneOffset()),
                browserUserAgent: navigator.userAgent,
                browserJavascriptEnabled: 'true',
            };
        }

        document.addEventListener('DOMContentLoaded', function () {
            if (typeof NymCardOpenFinance === 'undefined') {
                showError('Payment SDK could not be loaded. Please check your connection and try again.');
                return;
            }

            try {
                const deviceInformation = collectDeviceInformation();

                NymCardOpenFinance.init({
                    token: sdkToken,
                    deviceInformation: deviceInformation,
                    containerId: 'nymcard-openfinance',
                    onReady: function () {
                        document.getElementById('sdkLoading').style.display = 'none';
                        document.getElementById('nymcard-openfinance').style.display = 'block';
                    },
                    onSuccess: function (data) {
                        // Payment flow completed successfully — redirect to return page
                        window.location.href = returnUrl + '?resourceId=' + encodeURIComponent(resourceId || '') + '&status=success';
                    },
                    onFailure: function (data) {
                        // Payment flow failed
                        window.location.href = returnUrl + '?resourceId=' + encodeURIComponent(resourceId || '') + '&status=failed';
                    },
                    onCancel: function () {
                        // User cancelled — return to invoice page
                        window.location.href = '/invoice/' + invoiceUuid;
                    },
                    onError: function (error) {
                        console.error('NymCard SDK error:', error);
                        showError('A payment error occurred. Please go back and try again.');
                    },
                });
            } catch (e) {
                console.error('NymCard init exception:', e);
                showError('The payment session could not be started. Please go back and try again.');
            }
        });
    </script>
</body>
</html>
