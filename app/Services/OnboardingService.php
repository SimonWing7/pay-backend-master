<?php

namespace App\Services;

use App\Mail\OnboardingStatusMail;
use App\Models\Merchant;
use App\Models\MerchantOnboarding;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * The new-merchant onboarding journey: one screen per step, answers saved as a
 * draft so the merchant can leave and come back, then submitted for Edfundo to
 * review. Nothing is written to the live merchant record until they submit.
 */
class OnboardingService
{
    /** Every screen, in order. 'welcome' and 'review' hold no questions. */
    public const STEPS = ['welcome', 'business', 'registration', 'bank', 'contact', 'fees', 'review'];

    /** The screens that ask questions, shown as "Step n of 5". */
    public const FORM_STEPS = ['business', 'registration', 'bank', 'contact', 'fees'];

    public const TITLES = [
        'welcome'  => 'Welcome',
        'business' => 'Your business',
        'registration' => 'Business registration',
        'bank'     => 'Where we pay you',
        'contact'  => 'Authorised contact',
        'fees'     => 'Fees & agreement',
        'review'   => 'Review & submit',
    ];

    public function forMerchant(Merchant $merchant): MerchantOnboarding
    {
        // Always query rather than trust the cached relation: the authenticated
        // merchant instance lives for the whole request and may have cached
        // "no onboarding yet" before this row was created.
        $onboarding = MerchantOnboarding::firstOrCreate(
            ['merchant_id' => $merchant->id],
            [
                'current_step' => 'welcome',
                'data' => [
                    'business' => array_filter([
                        'trading_name' => $merchant->merchant_trading_name ?: $merchant->name,
                        'support_email' => $merchant->support_email ?: $merchant->email,
                        'support_phone' => $merchant->support_phone,
                        'website' => $merchant->website,
                        'logo_path' => $merchant->logo_path,
                    ]),
                    'bank' => array_filter([
                        'account_holder_name' => $merchant->account_holder_name,
                        'bank_name' => $merchant->bank_name,
                        'iban' => $merchant->iban,
                    ]),
                    'contact' => ['signatory_email' => $merchant->email],
                ],
            ]
        );

        $onboarding->setRelation('merchant', $merchant);
        $merchant->setRelation('onboarding', $onboarding);

        return $onboarding;
    }

    /** True while the merchant can still edit (before submit, or after changes are requested). */
    public function isEditable(Merchant $merchant): bool
    {
        return $merchant->onboardingIncomplete();
    }

    /**
     * Validation rules for a step. A draft ("save and finish later") accepts
     * partial answers, so everything required becomes optional but formats
     * are still checked.
     */
    public function rules(string $step, bool $draft): array
    {
        $rules = match ($step) {
            'business' => [
                'trading_name' => ['required', 'string', 'max:255'],
                'support_email' => ['required', 'email', 'max:255'],
                'support_phone' => ['required', 'string', 'max:50'],
                'website' => ['nullable', 'url', 'max:255'],
                'logo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,svg,webp', 'max:2048'],
            ],
            'registration' => [
                'trade_license_number' => ['required', 'string', 'max:100'],
                'address' => ['required', 'string', 'max:255'],
                'city' => ['required', 'string', 'max:255'],
            ],
            'bank' => [
                'account_holder_name' => ['required', 'string', 'max:255'],
                'bank_name' => ['required', 'string', 'max:255'],
                'iban' => ['required', 'regex:/^AE\d{21}$/'],
                'account_number' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9]+$/'],
                'swift_code' => ['required', 'regex:/^[A-Z]{6}[A-Z0-9]{2}([A-Z0-9]{3})?$/'],
            ],
            'contact' => [
                'signatory_name' => ['required', 'string', 'max:255'],
                'signatory_role' => ['required', 'string', 'max:100'],
                'signatory_mobile' => ['required', 'string', 'max:50'],
                'signatory_email' => ['required', 'email', 'max:255'],
                'signatory_id' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
                'bank_letter' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            ],
            // When a card is required the agreement is given in the same tick as the
            // card authorisation, so there is nothing to validate on this form.
            'fees' => $this->cardRequired() ? [] : [
                'terms' => ['accepted'],
            ],
            default => [],
        };

        if (!$draft) {
            return $rules;
        }

        return array_map(function (array $r) {
            $r = array_values(array_filter($r, fn ($x) => $x !== 'required' && $x !== 'accepted'));

            return in_array('nullable', $r, true) ? $r : array_merge(['nullable'], $r);
        }, $rules);
    }

    public function messages(): array
    {
        return [
            'iban.regex' => 'Enter a UAE IBAN: "AE" followed by 21 digits (23 characters in total).',
            'account_number.regex' => 'The account number should be letters and numbers only, with no spaces or dashes.',
            'swift_code.regex' => 'A SWIFT code is 8 or 11 letters and numbers, for example EBILAEAD.',
            'terms.accepted' => 'Please tick the box to confirm you agree to the fees and terms.',
            'logo.max' => 'The logo must be 2 MB or smaller.',
            'signatory_id.max' => 'Each document must be 5 MB or smaller.',
            'bank_letter.max' => 'Each document must be 5 MB or smaller.',
        ];
    }

    /** Tidy what the merchant typed before it is validated. */
    public function normalise(string $step, array $input): array
    {
        if ($step === 'bank') {
            foreach (['iban', 'swift_code'] as $field) {
                if (isset($input[$field])) {
                    $input[$field] = strtoupper(preg_replace('/[\s-]+/', '', (string) $input[$field]));
                }
            }
            if (isset($input['account_number'])) {
                $input['account_number'] = preg_replace('/[\s-]+/', '', (string) $input['account_number']);
            }
        }

        return $input;
    }

    /**
     * Store a step's validated answers (and any uploaded files) as the draft.
     *
     * @param array<string, mixed> $validated
     */
    public function saveStep(MerchantOnboarding $onboarding, string $step, array $validated, ?string $ip = null, ?string $by = null): MerchantOnboarding
    {
        $data = $onboarding->data ?? [];
        $answers = $data[$step] ?? [];

        foreach ($validated as $key => $value) {
            if ($value instanceof UploadedFile) {
                continue;
            }
            if (in_array($key, ['logo', 'signatory_id', 'bank_letter', 'terms'], true)) {
                continue;
            }
            $answers[$key] = is_string($value) ? trim($value) : $value;
        }

        $updates = [];

        if ($step === 'business' && isset($validated['logo']) && $validated['logo'] instanceof UploadedFile) {
            $answers['logo_path'] = $validated['logo']->store('logos', 'public');
        }

        if ($step === 'contact') {
            foreach (['signatory_id' => 'signatory_id_path', 'bank_letter' => 'bank_letter_path'] as $field => $column) {
                if (isset($validated[$field]) && $validated[$field] instanceof UploadedFile) {
                    if ($onboarding->{$column}) {
                        Storage::disk('local')->delete($onboarding->{$column});
                    }
                    $updates[$column] = $validated[$field]->store("onboarding/{$onboarding->merchant_id}", 'local');
                }
            }
        }

        if ($step === 'fees' && !empty($validated['terms']) && !$onboarding->terms_accepted_at) {
            $updates['terms_accepted_at'] = now();
            $updates['terms_accepted_ip'] = $ip;
            $updates['terms_accepted_by'] = $by;
        }

        $data[$step] = $answers;
        $onboarding->forceFill(array_merge($updates, ['data' => $data]))->save();

        return $onboarding;
    }

    /** Note the agreement (once), e.g. when it is given together with the card authorisation. */
    public function recordTerms(Merchant $merchant, ?string $ip, ?string $by): void
    {
        $onboarding = $this->forMerchant($merchant);

        if (!$onboarding->terms_accepted_at) {
            $onboarding->update([
                'terms_accepted_at' => now(),
                'terms_accepted_ip' => $ip,
                'terms_accepted_by' => $by,
            ]);
        }
    }

    public function moveTo(MerchantOnboarding $onboarding, string $step): void
    {
        $onboarding->update(['current_step' => $step]);

        $merchant = $onboarding->merchant;
        if ($merchant && $merchant->onboarding_status === Merchant::ONBOARDING_NOT_STARTED) {
            $merchant->update(['onboarding_status' => Merchant::ONBOARDING_IN_PROGRESS]);
        }
    }

    public function nextStep(string $step): string
    {
        $i = array_search($step, self::STEPS, true);

        return self::STEPS[min(($i === false ? 0 : $i) + 1, count(self::STEPS) - 1)];
    }

    public function previousStep(string $step): string
    {
        $i = array_search($step, self::STEPS, true);

        return self::STEPS[max(($i === false ? 0 : $i) - 1, 0)];
    }

    /** Is every required answer for this step saved? */
    public function isStepComplete(MerchantOnboarding $onboarding, string $step): bool
    {
        $answers = $onboarding->step($step);

        $required = match ($step) {
            'business' => ['trading_name', 'support_email', 'support_phone'],
            'registration' => ['trade_license_number', 'address', 'city'],
            'bank' => ['account_holder_name', 'bank_name', 'iban', 'account_number', 'swift_code'],
            'contact' => ['signatory_name', 'signatory_role', 'signatory_mobile', 'signatory_email'],
            default => [],
        };

        foreach ($required as $key) {
            if (trim((string) ($answers[$key] ?? '')) === '') {
                return false;
            }
        }

        if ($step === 'bank'
            && (!preg_match('/^AE\d{21}$/', (string) $answers['iban'])
                || !preg_match('/^[A-Z]{6}[A-Z0-9]{2}([A-Z0-9]{3})?$/', (string) $answers['swift_code']))) {
            return false;
        }

        if ($step === 'fees') {
            if (!$onboarding->terms_accepted_at) {
                return false;
            }
            if ($this->cardRequired() && !$onboarding->merchant->activeBillingCard) {
                return false;
            }
        }

        return true;
    }

    /** Form steps still to finish, in order. */
    public function incompleteSteps(MerchantOnboarding $onboarding): array
    {
        return array_values(array_filter(
            self::FORM_STEPS,
            fn ($step) => !$this->isStepComplete($onboarding, $step)
        ));
    }

    /** A card is only demanded once monthly fee billing is actually live. */
    public function cardRequired(): bool
    {
        return (bool) config('billing.enabled');
    }

    /** "Step 2 of 4" style progress for banners. */
    public function progress(MerchantOnboarding $onboarding): array
    {
        $done = count(self::FORM_STEPS) - count($this->incompleteSteps($onboarding));

        return ['done' => $done, 'total' => count(self::FORM_STEPS)];
    }

    /**
     * Send the answers to Edfundo for review. Returns null on success or a
     * message listing what is still missing.
     */
    public function submit(Merchant $merchant): ?string
    {
        $onboarding = $this->forMerchant($merchant);

        if (!$this->isEditable($merchant)) {
            return 'Your details have already been sent to Edfundo.';
        }

        $missing = $this->incompleteSteps($onboarding);
        if ($missing) {
            $names = implode(', ', array_map(fn ($s) => self::TITLES[$s], $missing));

            return "Please finish these steps first: {$names}.";
        }

        $b = $onboarding->step('business');
        $k = $onboarding->step('bank');

        $merchant->update(array_filter([
            'merchant_trading_name' => $b['trading_name'],
            'support_email' => $b['support_email'],
            'support_phone' => $b['support_phone'],
            'website' => $b['website'] ?: null,
            'logo_path' => $b['logo_path'] ?? null,
            'account_holder_name' => $k['account_holder_name'],
            'bank_name' => $k['bank_name'],
            'iban' => $k['iban'],
        ], fn ($v) => $v !== null && $v !== '') + ['onboarding_status' => Merchant::ONBOARDING_SUBMITTED]);

        $onboarding->update(['submitted_at' => now(), 'current_step' => 'review']);

        $this->notifyEdfundo($merchant);

        return null;
    }

    /** Approve a submitted merchant. Returns null on success or the reason it can't be. */
    public function approve(Merchant $merchant, string $by): ?string
    {
        if ($merchant->onboarding_status !== Merchant::ONBOARDING_SUBMITTED) {
            return 'This merchant has not submitted their onboarding for review.';
        }

        if (empty($merchant->lean_destination_id)) {
            return 'Set the Lean destination ID on the merchant first (Edit), otherwise payments cannot be settled.';
        }

        if (empty($merchant->iban)) {
            return 'The merchant has no IBAN on file.';
        }

        $merchant->update(['onboarding_status' => Merchant::ONBOARDING_APPROVED]);
        $this->forMerchant($merchant)->update(['reviewed_at' => now(), 'reviewed_by' => $by, 'review_note' => null]);

        $this->mailMerchant($merchant, 'approved');

        return null;
    }

    public function requestChanges(Merchant $merchant, string $note, string $by): ?string
    {
        if ($merchant->onboarding_status !== Merchant::ONBOARDING_SUBMITTED) {
            return 'This merchant has not submitted their onboarding for review.';
        }

        $merchant->update(['onboarding_status' => Merchant::ONBOARDING_CHANGES_REQUESTED]);
        $onboarding = $this->forMerchant($merchant);
        $onboarding->update(['reviewed_at' => now(), 'reviewed_by' => $by, 'review_note' => $note, 'current_step' => 'review']);

        $this->mailMerchant($merchant, 'changes', $note);

        return null;
    }

    private function mailMerchant(Merchant $merchant, string $kind, ?string $note = null): void
    {
        try {
            Mail::to($merchant->email)->send(new OnboardingStatusMail($merchant, $kind, $note));
        } catch (\Throwable $e) {
            Log::warning('Onboarding status email failed', ['merchant_id' => $merchant->id, 'kind' => $kind, 'error' => $e->getMessage()]);
        }
    }

    private function notifyEdfundo(Merchant $merchant): void
    {
        $to = config('onboarding.notify_email');
        if (!$to) {
            return;
        }

        try {
            Mail::to($to)->send(new OnboardingStatusMail($merchant, 'submitted'));
        } catch (\Throwable $e) {
            Log::warning('Onboarding submitted notification failed', ['merchant_id' => $merchant->id, 'error' => $e->getMessage()]);
        }
    }
}
