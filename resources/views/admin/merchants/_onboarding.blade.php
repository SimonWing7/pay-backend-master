@php
    $status = $merchant->onboarding_status;
    $ob = $merchant->onboarding;
    $labels = [
        'not_started' => ['Not started', 'badge-muted'],
        'in_progress' => ['In progress', 'badge-warning'],
        'submitted' => ['Awaiting review', 'badge-warning'],
        'changes_requested' => ['Changes requested', 'badge-danger'],
        'approved' => ['Approved', 'badge-success'],
    ];
    [$label, $badge] = $labels[$status] ?? [$status, 'badge-muted'];
    $b = $ob?->step('business') ?? []; $k = $ob?->step('bank') ?? []; $c = $ob?->step('contact') ?? [];
    $progress = $ob ? app(\App\Services\OnboardingService::class)->progress($ob) : ['done' => 0, 'total' => 4];
@endphp
<div class="card p-6 mb-6" id="onboarding">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-semibold text-gray-700">Onboarding <span class="{{ $badge }} ml-1">{{ $label }}</span></h3>
        @if($status !== 'approved')
        <form method="POST" action="{{ route('admin.merchants.welcome', $merchant->id) }}" class="inline">
            @csrf
            <button type="submit" class="text-xs font-medium" style="color:#3d01bd;"><i class="fas fa-paper-plane"></i> {{ $status === 'not_started' ? 'Send' : 'Resend' }} welcome email</button>
        </form>
        @endif
    </div>

    @if($status === 'approved' && !$ob?->submitted_at)
        <p class="text-sm text-gray-500">This merchant did not go through the onboarding journey (an existing or internal account).</p>
    @elseif($status === 'not_started' || !$ob)
        <p class="text-sm text-gray-500">The merchant hasn't started yet. They'll be taken to the journey when they first sign in.</p>
    @else
        @if($status === 'in_progress' || $status === 'changes_requested')
            <p class="text-sm text-gray-500 mb-4">{{ $progress['done'] }} of {{ $progress['total'] }} steps completed by the merchant.</p>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm mb-4">
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Business</p>
                <p class="font-semibold text-gray-800">{{ $b['trading_name'] ?? '—' }}</p>
                <p class="text-gray-500">{{ $b['support_email'] ?? '—' }}</p>
                <p class="text-gray-500">{{ $b['support_phone'] ?? '—' }}</p>
                <p class="text-gray-500">{{ $b['website'] ?? '' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Bank</p>
                <p class="font-semibold text-gray-800">{{ $k['account_holder_name'] ?? '—' }}</p>
                <p class="text-gray-500">{{ $k['bank_name'] ?? '—' }}</p>
                <p class="text-gray-500 font-mono text-xs">{{ $k['iban'] ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Authorised contact</p>
                <p class="font-semibold text-gray-800">{{ $c['signatory_name'] ?? '—' }}</p>
                <p class="text-gray-500">{{ $c['signatory_role'] ?? '—' }}</p>
                <p class="text-gray-500">{{ $c['signatory_mobile'] ?? '' }} {{ $c['signatory_email'] ?? '' }}</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm mb-4">
            @if($ob->terms_accepted_at)
                <span class="text-green-700"><i class="fas fa-check-circle"></i> Fees &amp; terms accepted {{ $ob->terms_accepted_at->copy()->setTimezone('Asia/Dubai')->format('j M Y, H:i') }}{{ $ob->terms_accepted_by ? ' by ' . $ob->terms_accepted_by : '' }}</span>
            @else
                <span class="text-amber-600"><i class="fas fa-exclamation-circle"></i> Terms not accepted yet</span>
            @endif
            @if($ob->signatory_id_path)<a href="{{ route('admin.merchants.onboarding.document', [$merchant->id, 'signatory-id']) }}" style="color:#3d01bd;"><i class="fas fa-download"></i> Contact ID</a>@endif
            @if($ob->bank_letter_path)<a href="{{ route('admin.merchants.onboarding.document', [$merchant->id, 'bank-letter']) }}" style="color:#3d01bd;"><i class="fas fa-download"></i> Bank letter</a>@endif
        </div>

        @if($ob->review_note && $status === 'changes_requested')
            <p class="text-sm p-3 rounded-lg mb-4" style="background:#fffbeb;color:#92400e;white-space:pre-line;"><strong>Sent back{{ $ob->reviewed_by ? ' by ' . $ob->reviewed_by : '' }}:</strong> {{ $ob->review_note }}</p>
        @endif

        @if($status === 'submitted')
            <div class="border-t border-gray-100 pt-4">
                @if(empty($merchant->lean_destination_id))
                    <p class="text-sm mb-3" style="color:#b45309;"><i class="fas fa-exclamation-triangle"></i> Set the Lean destination ID (<a href="{{ route('admin.merchants.edit', $merchant->id) }}" style="text-decoration:underline;">Edit merchant</a>) before approving.</p>
                @endif
                <div class="flex flex-wrap items-start gap-4">
                    <form method="POST" action="{{ route('admin.merchants.onboarding.approve', $merchant->id) }}"
                        onsubmit="return confirm('Approve this merchant? They will be able to take real payments.');">
                        @csrf
                        <button type="submit" class="btn-primary"><i class="fas fa-check"></i> Approve</button>
                    </form>
                    <form method="POST" action="{{ route('admin.merchants.onboarding.changes', $merchant->id) }}" class="flex-1" style="min-width:260px;">
                        @csrf
                        <textarea name="note" rows="2" class="form-input mb-2" placeholder="What needs to change? The merchant sees this exactly as written.">{{ old('note') }}</textarea>
                        @error('note')<p class="text-red-500 text-xs mb-2">{{ $message }}</p>@enderror
                        <button type="submit" class="btn-secondary"><i class="fas fa-undo"></i> Send back for changes</button>
                    </form>
                </div>
            </div>
        @elseif($status === 'approved')
            <p class="text-xs text-gray-400">Approved{{ $ob->reviewed_at ? ' ' . $ob->reviewed_at->copy()->setTimezone('Asia/Dubai')->format('j M Y, H:i') : '' }}{{ $ob->reviewed_by ? ' by ' . $ob->reviewed_by : '' }}.</p>
        @endif
    @endif
</div>
