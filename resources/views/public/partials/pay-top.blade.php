{{-- Shared by the payment link page and the product page. Expects: $merchantId, $liveBanks --}}
                {{-- Edfundo reward banner --}}
                <div style="margin-bottom:14px;padding:12px 12px 10px;border-radius:14px;border:1px solid #cfe0fb;background:linear-gradient(135deg,#eaf3ff 0%,#f6faff 100%);">
                    <span style="display:inline-block;padding:2px 9px;font-size:9px;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;color:#ffffff;background:#2f6df6;border-radius:999px;">Exclusive Edfundo reward</span>
                    <div style="display:flex;align-items:center;gap:10px;margin:8px 0 10px;">
                        <svg width="46" height="46" viewBox="0 0 64 64" aria-hidden="true" style="flex-shrink:0;">
                            <path d="M8 14l3 5M5 24l5-1M18 6l1 5" stroke="#f5b82e" stroke-width="2.5" stroke-linecap="round" fill="none"/>
                            <rect x="12" y="30" width="40" height="26" rx="3" fill="#3b6ff0"/>
                            <rect x="9" y="23" width="46" height="10" rx="3" fill="#2f5fd9"/>
                            <rect x="29" y="23" width="6" height="33" fill="#f5b82e"/>
                            <path d="M32 23c-4-9-14-8-12-3s9 3 12 3zM32 23c4-9 14-8 12-3s-9 3-12 3z" fill="#f5b82e"/>
                        </svg>
                        <div>
                            <p style="margin:0;font-size:17px;font-weight:800;line-height:1.2;color:#0f1a3d;">Get <span style="color:#2f6df6;">AED 50</span> in your Edfundo wallet!</p>
                            <p style="margin:3px 0 0;font-size:12px;line-height:1.4;color:#4b5563;">Pay with Edfundo Pay, then subscribe to Edfundo Family to claim your reward.</p>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:7px 10px;border-radius:9px;background:#ffffff;">
                        <span style="font-size:10px;line-height:1.35;color:#6b7280;">AED 25/month, billed annually at AED 300.<br>New customers. T&amp;Cs apply.</span>
                        <a href="https://edfundo.com/edfundo-pay-rewards/?ref={{ $merchantId }}&amp;utm_source=edfundo_pay&amp;utm_medium=payment_success" target="_blank" rel="noopener"
                           style="flex-shrink:0;font-size:12px;font-weight:700;color:#2f6df6;text-decoration:none;white-space:nowrap;">How it works &rarr;</a>
                    </div>
                </div>
                <p class="text-center mb-5" style="font-size:12px;color:#6b7280;">Secure &bull; No card details &bull; No payment fee</p>

                {{-- Live bank availability pills — sourced from the lean_banks cache,
                     kept fresh by the lean:sync-banks scheduled command and the
                     bank.availability.updated webhook. Hidden entirely if the
                     cache is empty (e.g. before the first sync has run) rather
                     than showing a stale or made-up list. --}}
                @if($liveBanks->count() > 0)
                <div class="mb-5">
                    <p class="text-xs font-semibold text-center uppercase tracking-wide mb-3" style="color:#9ca3af;">Live banks</p>
                    <div class="flex flex-wrap justify-center gap-2">
                        @foreach($liveBanks as $bank)
                        <span style="display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:20px;font-size:11px;font-weight:700;background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;">
                            <span style="width:7px;height:7px;border-radius:50%;background:#009A44;flex-shrink:0;"></span>{{ $bank->name }}
                        </span>
                        @endforeach
                    </div>
                </div>
                @endif

