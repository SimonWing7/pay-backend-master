{{-- Shared by the payment link page and the product page. Expects: $merchant, $entity (nullable), $amount --}}
                <div id="loadingState" class="hidden mt-4 text-center">
                    <div class="inline-flex items-center gap-3 text-sm font-medium" style="color:#3d01bd;">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Connecting to your bank…
                    </div>
                </div>

                {{-- "My bank is not listed" escape hatch --}}
                @php
                    // $merchant, $entity, $amount are passed in by the including page
                    // Entity-specific bank details take priority over the
                    // merchant's own default — otherwise a customer on an
                    // Abu Dhabi-tagged invoice would see Dubai's bank
                    // account (or vice versa) for the fallback transfer.
                    $fallbackBankName    = $entity?->fallback_bank_name ?: $merchant->fallback_bank_name;
                    $fallbackAccountName = $entity?->fallback_account_name ?: $merchant->fallback_account_name;
                    $fallbackIban        = $entity?->iban ?: $merchant->iban;
                    $fallbackReferenceNote = $entity?->fallback_reference_note ?: $merchant->fallback_reference_note;
                    $fallbackType    = $merchant->fallback_type ?? null;
                    $hasCardFallback = $fallbackType === 'payment_gateway' && !empty($merchant->fallback_payment_url);
                    $toggleLabel     = $hasCardFallback
                        ? "Can't find your bank? Pay by card instead"
                        : "My bank isn't listed yet";
                    $toggleIcon      = $hasCardFallback ? 'fa-arrow-right' : 'fa-chevron-down';
                @endphp
                <div class="text-center mt-4">
                    <button type="button" onclick="toggleFallback()" id="fallbackToggle"
                        style="color:#9ca3af;background:none;border:none;cursor:pointer;padding:0;font-size:12px;font-weight:500;font-family:inherit;">
                        {{ $toggleLabel }}
                        <i class="fas {{ $toggleIcon }} ml-1 text-xs" id="fallbackChevron"></i>
                    </button>
                </div>

                {{-- Fallback panel (hidden by default) --}}
                <div id="fallbackPanel" class="hidden mt-3 rounded-xl border border-gray-100 overflow-hidden" style="background:#fafbff;">
                    <div class="px-4 py-3 border-b border-gray-100" style="background:#f3f4f6;">
                        <p class="text-xs font-semibold uppercase tracking-wide" style="color:#6b7280;">
                            <i class="fas fa-info-circle mr-1"></i>Alternative Payment
                        </p>
                    </div>
                    <div class="px-4 py-4">
                        @if($fallbackType === 'payment_gateway' && !empty($merchant->fallback_payment_url))
                            <p class="text-sm mb-4" style="color:#6b7280;">
                                You can pay by card via a secure payment page.
                            </p>
                            <a href="{{ $merchant->fallback_payment_url }}" target="_blank" rel="noopener" onclick="return openCardFallback(this)"
                                style="display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:12px 16px;border-radius:10px;font-weight:700;font-size:14px;background:linear-gradient(135deg,#3d01bd,#00bdff);color:white;text-decoration:none;">
                                <i class="fas fa-credit-card"></i>
                                Pay by Card
                                <i class="fas fa-external-link-alt text-xs" style="opacity:0.7;margin-left:2px;"></i>
                            </a>

                        @elseif($fallbackType === 'bank_transfer')
                            <p class="text-sm mb-4" style="color:#6b7280;">
                                Transfer the exact amount to the account below. Please include the reference shown.
                            </p>
                            <div class="space-y-2.5">
                                <div class="flex justify-between items-center gap-3">
                                    <span class="text-xs font-medium" style="color:#9ca3af;">Amount</span>
                                    <span class="text-sm font-bold" style="color:#3d01bd;">AED {{ number_format($amount, 2) }}</span>
                                </div>
                                @if($fallbackBankName)
                                <div class="flex justify-between items-start gap-3">
                                    <span class="text-xs font-medium flex-shrink-0" style="color:#9ca3af;">Bank</span>
                                    <span class="text-sm font-semibold text-right" style="color:#1f2937;">{{ $fallbackBankName }}</span>
                                </div>
                                @endif
                                @if($fallbackAccountName)
                                <div class="flex justify-between items-start gap-3">
                                    <span class="text-xs font-medium flex-shrink-0" style="color:#9ca3af;">Account Name</span>
                                    <span class="text-sm font-semibold text-right" style="color:#1f2937;">{{ $fallbackAccountName }}</span>
                                </div>
                                @endif
                                @if($fallbackIban)
                                <div class="flex justify-between items-start gap-3">
                                    <span class="text-xs font-medium flex-shrink-0" style="color:#9ca3af;">IBAN</span>
                                    <span class="text-xs font-mono font-semibold text-right" style="color:#1f2937;">{{ $fallbackIban }}</span>
                                </div>
                                @endif
                                @if($fallbackReferenceNote)
                                <div class="pt-3 mt-1 border-t border-gray-100">
                                    <p class="text-xs font-semibold uppercase tracking-wide mb-1" style="color:#9ca3af;">Payment Reference</p>
                                    <p class="text-sm" style="color:#6b7280;">{{ $fallbackReferenceNote }}</p>
                                </div>
                                @endif
                            </div>

                        @else
                            <p class="text-sm mb-3" style="color:#6b7280;">
                                @if($merchant->fallback_none_note)
                                    {{ $merchant->fallback_none_note }}
                                @else
                                    Please contact {{ $merchant->merchant_trading_name ?? $merchant->name }} to arrange payment.
                                @endif
                            </p>
                            <div class="space-y-2">
                                @if($merchant->support_email)
                                <a href="mailto:{{ $merchant->support_email }}"
                                    class="flex items-center gap-3 text-sm font-medium" style="color:#3d01bd;text-decoration:none;">
                                    <i class="fas fa-envelope w-4" style="color:#d1d5db;"></i>
                                    {{ $merchant->support_email }}
                                </a>
                                @endif
                                @if($merchant->support_phone)
                                <a href="tel:{{ $merchant->support_phone }}"
                                    class="flex items-center gap-3 text-sm font-medium" style="color:#3d01bd;text-decoration:none;">
                                    <i class="fas fa-phone w-4" style="color:#d1d5db;"></i>
                                    {{ $merchant->support_phone }}
                                </a>
                                @endif
                                @if(!$merchant->support_email && !$merchant->support_phone)
                                <p class="text-sm" style="color:#9ca3af;">Contact the merchant directly for alternative payment options.</p>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-center gap-5 text-xs text-gray-400">
                    <span><i class="fas fa-lock mr-1"></i>256-bit encrypted</span>
                    <span><i class="fas fa-shield-alt mr-1"></i>UAE Central Bank regulated</span>
                </div>

