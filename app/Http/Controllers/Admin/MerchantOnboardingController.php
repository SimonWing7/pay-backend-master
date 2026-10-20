<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\MerchantWelcomeMail;
use App\Models\Merchant;
use App\Services\OnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Edfundo's side of merchant onboarding: review, approve, send back, welcome email. */
class MerchantOnboardingController extends Controller
{
    public function __construct(protected OnboardingService $onboarding)
    {
    }

    public function approve(Request $request, int $id): RedirectResponse
    {
        $merchant = Merchant::findOrFail($id);

        $error = $this->onboarding->approve($merchant, $request->user('admin')->name);

        return redirect()->route('admin.merchants.show', $merchant->id)
            ->with($error ? 'error' : 'success', $error ?? 'Approved. The merchant has been emailed and can now take payments.');
    }

    public function requestChanges(Request $request, int $id): RedirectResponse
    {
        $merchant = Merchant::findOrFail($id);

        $data = $request->validate(['note' => 'required|string|max:2000'], [
            'note.required' => 'Tell the merchant what needs to change.',
        ]);

        $error = $this->onboarding->requestChanges($merchant, $data['note'], $request->user('admin')->name);

        return redirect()->route('admin.merchants.show', $merchant->id)
            ->with($error ? 'error' : 'success', $error ?? 'Sent back to the merchant with your note.');
    }

    /** (Re)send the "set your password" email. */
    public function welcome(int $id): RedirectResponse
    {
        $merchant = Merchant::findOrFail($id);

        try {
            $token = Password::broker('merchants_welcome')->createToken($merchant);
            Mail::to($merchant->email)->send(new MerchantWelcomeMail($merchant, $token));
        } catch (\Throwable $e) {
            Log::warning('Merchant welcome email failed', ['merchant_id' => $merchant->id, 'error' => $e->getMessage()]);

            return redirect()->route('admin.merchants.show', $merchant->id)->with('error', 'The welcome email could not be sent.');
        }

        return redirect()->route('admin.merchants.show', $merchant->id)
            ->with('success', "Welcome email sent to {$merchant->email}. Earlier links stop working.");
    }

    public function document(int $id, string $type): StreamedResponse
    {
        $onboarding = Merchant::findOrFail($id)->onboarding;
        $column = ['signatory-id' => 'signatory_id_path', 'bank-letter' => 'bank_letter_path'][$type] ?? abort(404);
        $path = $onboarding?->{$column};

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, "{$type}-merchant-{$id}." . pathinfo($path, PATHINFO_EXTENSION));
    }
}
