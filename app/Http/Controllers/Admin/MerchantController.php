<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\MerchantApiKey;
use App\Services\MerchantService;
use App\Services\PaymentService;
use App\Services\WebhookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class MerchantController extends Controller
{
    public function __construct(
        protected MerchantService $merchantService,
        protected PaymentService $paymentService
    ) {
    }

    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->get('search'),
            'is_active' => $request->get('is_active'),
            'onboarding' => $request->get('onboarding'),
        ];
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $perPage = $request->get('per_page', 15);

        $merchants = $this->merchantService->getAll($filters, $sortBy, $sortDir, $perPage);
        $awaitingReview = \App\Models\Merchant::where('onboarding_status', \App\Models\Merchant::ONBOARDING_SUBMITTED)->count();
        return view('admin.merchants.index', compact('merchants', 'awaitingReview'));
    }

    public function create(): View
    {
        return view('admin.merchants.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name'                    => 'required|string|max:255',
            'email'                   => 'required|email|unique:merchants,email|unique:merchant_users,email',
            'password'                => 'nullable|string|min:8|required_without:send_welcome',
            'fee_percentage'          => 'nullable|numeric|min:0|max:100',
            'is_active'               => 'sometimes|boolean',
            'iban'                    => 'nullable|string|max:34',
            'bank_name'               => 'nullable|string|max:255',
            'account_holder_name'     => 'nullable|string|max:255',
            'lean_destination_id'     => 'nullable|string|max:255',
            'merchant_trading_name'   => 'nullable|string|max:255',
            'support_email'           => 'nullable|email|max:255',
            'support_phone'           => 'nullable|string|max:50',
            'receipt_cc_email'        => 'nullable|email|max:255',
            'notification_email'      => 'nullable|email|max:255',
            'billing_email'           => 'nullable|email|max:255',
            'website'                 => 'nullable|url|max:2048',
            'webhook_url'             => 'nullable|url|max:2048',
            'fallback_type'           => 'nullable|in:,payment_gateway,bank_transfer',
            'fallback_payment_url'    => 'nullable|url|max:2048',
            'fallback_bank_name'      => 'nullable|string|max:255',
            'fallback_account_name'   => 'nullable|string|max:255',
            'fallback_reference_note' => 'nullable|string|max:500',
            'fallback_none_note'      => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($request->except('password'));
        }

        $data = $validator->validated();

        // "Same as notifications email" (or a blank field) stores nothing, so
        // billing keeps following the notification email.
        if ($request->boolean('billing_email_same') || empty($data['billing_email'] ?? null)) {
            $data['billing_email'] = null;
        }

        // Normalise empty string to null for fallback_type
        if (isset($data['fallback_type']) && $data['fallback_type'] === '') {
            $data['fallback_type'] = null;
        }

        // Clear fields that don't apply to the chosen fallback type
        if (($data['fallback_type'] ?? null) !== 'payment_gateway') {
            $data['fallback_payment_url'] = null;
        }
        if (($data['fallback_type'] ?? null) !== 'bank_transfer') {
            $data['fallback_bank_name']      = null;
            $data['fallback_account_name']   = null;
            $data['fallback_reference_note'] = null;
        }
        if (($data['fallback_type'] ?? null) !== null) {
            $data['fallback_none_note'] = null;
        }

        // Auto-generate a webhook secret if a URL is provided at creation time
        if (!empty($data['webhook_url'])) {
            $data['webhook_secret'] = WebhookService::generateSecret();
        }

        // Handle logo upload
        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            $data['logo_path'] = $request->file('logo')->store('logos', 'public');
        }

        $sendWelcome = $request->boolean('send_welcome');

        // Blank means "use the standard rate" (the column default).
        if (!isset($data['fee_percentage']) || $data['fee_percentage'] === '') {
            unset($data['fee_percentage']);
        }

        // With the welcome email the merchant picks their own password, so
        // the one stored until then is random and never shared.
        if (empty($data['password'])) {
            $data['password'] = \Illuminate\Support\Str::random(40);
        }
        unset($data['send_welcome']);

        $data['onboarding_status'] = $request->boolean('skip_onboarding')
            ? Merchant::ONBOARDING_APPROVED
            : Merchant::ONBOARDING_NOT_STARTED;
        $data['must_change_password'] = !$sendWelcome;

        $merchant = $this->merchantService->create($data);

        if ($sendWelcome) {
            try {
                $token = \Illuminate\Support\Facades\Password::broker('merchants_welcome')->createToken($merchant);
                \Illuminate\Support\Facades\Mail::to($merchant->email)->send(new \App\Mail\MerchantWelcomeMail($merchant, $token));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Merchant welcome email failed', ['merchant_id' => $merchant->id, 'error' => $e->getMessage()]);

                return redirect()->route('admin.merchants.show', $merchant->id)
                    ->with('error', 'Merchant created, but the welcome email could not be sent. Use "Send welcome email" on this page to try again.');
            }

            return redirect()->route('admin.merchants.index')
                ->with('success', "Merchant created. A welcome email with a link to set their password was sent to {$merchant->email}.");
        }

        return redirect()->route('admin.merchants.index')
            ->with('success', 'Merchant created successfully');
    }

    public function show(int $id): View
    {
        $merchant = $this->merchantService->getById($id);

        if (!$merchant) {
            abort(404, 'Merchant not found');
        }

        $apiKeys = MerchantApiKey::where('merchant_id', $id)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->get();

        $recentPayments = $this->paymentService->getAll($id, [], 'created_at', 'desc', 5);

        $paymentStats = $this->paymentService->getAllStatusPaymentStatsForMerchant($id, 30);
        $statusBreakdown = $this->paymentService->getStatusBreakdownForMerchant($id);
        $entityBreakdown = $this->paymentService->getEntityBreakdownForMerchant($id);
        $totalAmountAllTime = $this->paymentService->getTotalIncomeForMerchant($id);
        $totalAmountCurrentMonth = $this->paymentService->getTotalIncomeCurrentMonthForMerchant($id);
        // Edfundo's cut — this merchant's fee rate applied to their total
        // completed volume (the same rate their monthly statements use).
        $totalEdfundoRevenue = $totalAmountAllTime * ((float) $merchant->fee_percentage / 100);

        $billingCard = $merchant->activeBillingCard;
        $billingUpcoming = app(\App\Services\BillingService::class)->upcomingFor($merchant);
        $billingStatements = $merchant->billingStatements()->orderByDesc('period_start')->limit(6)->get();

        return view('admin.merchants.show', compact(
            'merchant', 'apiKeys', 'recentPayments',
            'paymentStats', 'statusBreakdown', 'entityBreakdown', 'totalAmountAllTime', 'totalAmountCurrentMonth', 'totalEdfundoRevenue',
            'billingCard', 'billingUpcoming', 'billingStatements'
        ));
    }

    public function edit(int $id): View
    {
        $merchant = $this->merchantService->getById($id);

        if (!$merchant) {
            abort(404, 'Merchant not found');
        }

        return view('admin.merchants.edit', compact('merchant'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $merchant = $this->merchantService->getById($id);

        if (!$merchant) {
            abort(404, 'Merchant not found');
        }

        $validator = Validator::make($request->all(), [
            'name'                    => 'sometimes|string|max:255',
            'email'                   => 'sometimes|email|unique:merchants,email,' . $id . '|unique:merchant_users,email',
            'password'                => 'nullable|string|min:8',
            'is_active'               => 'sometimes|boolean',
            'iban'                    => 'nullable|string|max:34',
            'bank_name'               => 'nullable|string|max:255',
            'account_holder_name'     => 'nullable|string|max:255',
            'lean_destination_id'     => 'nullable|string|max:255',
            'merchant_trading_name'   => 'nullable|string|max:255',
            'support_email'           => 'nullable|email|max:255',
            'support_phone'           => 'nullable|string|max:50',
            'receipt_cc_email'        => 'nullable|email|max:255',
            'notification_email'      => 'nullable|email|max:255',
            'billing_email'           => 'nullable|email|max:255',
            'fee_percentage'          => 'sometimes|numeric|min:0|max:100',
            'website'                 => 'nullable|url|max:2048',
            'webhook_url'             => 'nullable|url|max:2048',
            'fallback_type'           => 'nullable|in:,payment_gateway,bank_transfer',
            'fallback_payment_url'    => 'nullable|url|max:2048',
            'fallback_bank_name'      => 'nullable|string|max:255',
            'fallback_account_name'   => 'nullable|string|max:255',
            'fallback_reference_note' => 'nullable|string|max:500',
            'fallback_none_note'      => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($request->except('password'));
        }

        $data = $validator->validated();

        // "Same as notifications email" (or a blank field) stores nothing, so
        // billing keeps following the notification email.
        if ($request->boolean('billing_email_same') || empty($data['billing_email'] ?? null)) {
            $data['billing_email'] = null;
        }

        // An unchecked checkbox isn't submitted at all, so read it explicitly.
        $data['billing_enabled'] = $request->boolean('billing_enabled');
        $data['allow_payment_reconciliation'] = $request->boolean('allow_payment_reconciliation');
        $data['show_reward_offer'] = $request->boolean('show_reward_offer');

        // Normalise empty string to null for fallback_type
        if (isset($data['fallback_type']) && $data['fallback_type'] === '') {
            $data['fallback_type'] = null;
        }

        // Clear gateway URL when not using payment_gateway
        if (($data['fallback_type'] ?? null) !== 'payment_gateway') {
            $data['fallback_payment_url'] = null;
        }

        // Clear transfer fields when not using bank_transfer
        if (($data['fallback_type'] ?? null) !== 'bank_transfer') {
            $data['fallback_bank_name']      = null;
            $data['fallback_account_name']   = null;
            $data['fallback_reference_note'] = null;
        }
        if (($data['fallback_type'] ?? null) !== null) {
            $data['fallback_none_note'] = null;
        }

        // Auto-generate webhook secret when a URL is being set and none exists yet
        if (!empty($data['webhook_url']) && empty($merchant->webhook_secret)) {
            $data['webhook_secret'] = WebhookService::generateSecret();
        }

        // Clear the secret when the webhook URL is removed
        if (isset($data['webhook_url']) && empty($data['webhook_url'])) {
            $data['webhook_secret'] = null;
        }

        // Handle logo upload
        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            if ($merchant->logo_path) {
                Storage::disk('public')->delete($merchant->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('logos', 'public');
        }

        $this->merchantService->update($merchant, $data);

        return redirect()->route('admin.merchants.show', $id)
            ->with('success', 'Merchant updated successfully');
    }

    public function delete(int $id): RedirectResponse
    {
        $merchant = $this->merchantService->getById($id);

        if (!$merchant) {
            abort(404, 'Merchant not found');
        }

        $this->merchantService->delete($merchant);

        return redirect()->route('admin.merchants.index')
            ->with('success', 'Merchant deleted successfully');
    }

    public function toggleActive(int $id): RedirectResponse
    {
        $merchant = $this->merchantService->getById($id);

        if (!$merchant) {
            abort(404, 'Merchant not found');
        }

        $merchant->is_active = !$merchant->is_active;
        $merchant->save();

        $status = $merchant->is_active ? 'activated' : 'deactivated';

        return redirect()->back()
            ->with('success', "Merchant {$status} successfully");
    }
}
