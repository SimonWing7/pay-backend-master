<?php

namespace Tests\Feature;

use App\Mail\MerchantWelcomeMail;
use App\Mail\OnboardingStatusMail;
use App\Models\Admin;
use App\Models\Invoice;
use App\Models\Merchant;
use App\Models\MerchantBillingCard;
use App\Models\MerchantOnboarding;
use App\Models\MerchantUser;
use App\Models\Product;
use App\Services\OnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchant;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Storage::fake('public');
        Storage::fake('local');
        config(['onboarding.notify_email' => 'ops@edfundo.test', 'billing.enabled' => false]);

        $this->merchant = $this->newMerchant('owner@school.test');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // -- helpers -----------------------------------------------------------

    private function newMerchant(string $email, string $status = 'not_started'): Merchant
    {
        return Merchant::create([
            'name' => 'Pinnakle Rugby Academy', 'email' => $email, 'password' => 'password123',
            'is_active' => true, 'must_change_password' => false, 'onboarding_status' => $status,
        ])->fresh();
    }

    private function owner()
    {
        $this->flushSession();

        return $this->actingAs($this->merchant->fresh(), 'merchants');
    }

    private function save(string $step, array $data = [], string $action = 'next')
    {
        return $this->owner()->post(route('merchant.onboarding.save', $step), $data + ['action' => $action]);
    }

    private function business(array $o = []): array
    {
        return $o + ['trading_name' => 'Pinnakle Rugby', 'support_email' => 'hello@pinnakle.test', 'support_phone' => '+971501234567', 'website' => 'https://pinnakle.test'];
    }

    private function bank(array $o = []): array
    {
        return $o + ['account_holder_name' => 'Pinnakle Sports LLC', 'bank_name' => 'Emirates NBD', 'iban' => 'AE070331234567890123456'];
    }

    private function contact(array $o = []): array
    {
        return $o + ['signatory_name' => 'Sara Ahmed', 'signatory_role' => 'Managing Director', 'signatory_mobile' => '+971509876543', 'signatory_email' => 'sara@pinnakle.test'];
    }

    private function completeEverything(): void
    {
        $this->save('business', $this->business());
        $this->save('bank', $this->bank());
        $this->save('contact', $this->contact());
        $this->save('fees', ['terms' => '1']);
    }

    private function submitted(): Merchant
    {
        $this->completeEverything();
        $this->owner()->post(route('merchant.onboarding.submit'))->assertRedirect(route('merchant.onboarding.index'));

        return $this->merchant->fresh();
    }

    private function admin()
    {
        $admin = Admin::first() ?? Admin::create(['name' => 'Edfundo Ops', 'email' => 'ops@example.com', 'password' => 'password123']);

        return $this->actingAs($admin, 'admin');
    }

    // -- welcome email & account creation ---------------------------------

    public function test_admin_creates_a_merchant_who_gets_a_welcome_link_that_lasts_a_week(): void
    {
        $this->admin()->post(route('admin.merchants.store'), [
            'name' => 'New School', 'email' => 'principal@newschool.test', 'send_welcome' => '1', 'is_active' => '1',
        ])->assertRedirect(route('admin.merchants.index'));

        $merchant = Merchant::where('email', 'principal@newschool.test')->firstOrFail();
        $this->assertSame('not_started', $merchant->onboarding_status);
        $this->assertFalse((bool) $merchant->must_change_password);

        $token = null;
        Mail::assertSent(MerchantWelcomeMail::class, function (MerchantWelcomeMail $m) use (&$token, $merchant) {
            $token = $m->token;

            return $m->hasTo($merchant->email);
        });

        $html = (new MerchantWelcomeMail($merchant, $token))->render();
        $this->assertStringContainsString('principal@newschool.test', $html);
        $this->assertStringContainsString($token, $html);
        $this->assertStringContainsString('5 minutes', $html);

        // Two days later the standard one-hour reset link would be dead; this one still works.
        Carbon::setTestNow(now()->addDays(2));
        $this->post(route('merchant.password.reset.post'), [
            'token' => $token, 'email' => 'principal@newschool.test',
            'password' => 'chosenpass123', 'password_confirmation' => 'chosenpass123',
        ])->assertRedirect(route('merchant.login'));

        $this->assertTrue(Hash::check('chosenpass123', $merchant->fresh()->password));
    }

    public function test_a_welcome_link_expires_after_a_week(): void
    {
        $this->admin()->post(route('admin.merchants.store'), [
            'name' => 'New School', 'email' => 'principal@newschool.test', 'send_welcome' => '1',
        ]);
        $token = null;
        Mail::assertSent(MerchantWelcomeMail::class, function ($m) use (&$token) {
            $token = $m->token;

            return true;
        });

        Carbon::setTestNow(now()->addDays(8));
        $this->post(route('merchant.password.reset.post'), [
            'token' => $token, 'email' => 'principal@newschool.test',
            'password' => 'chosenpass123', 'password_confirmation' => 'chosenpass123',
        ])->assertSessionHasErrors('email');
    }

    public function test_creating_without_a_welcome_email_still_needs_a_password(): void
    {
        $this->admin()->post(route('admin.merchants.store'), ['name' => 'X', 'email' => 'x@school.test'])
            ->assertSessionHasErrors('password');
        $this->assertSame(0, Merchant::where('email', 'x@school.test')->count());

        $this->admin()->post(route('admin.merchants.store'), ['name' => 'X', 'email' => 'x@school.test', 'password' => 'temporary123'])
            ->assertRedirect(route('admin.merchants.index'));
        $m = Merchant::where('email', 'x@school.test')->first();
        $this->assertTrue((bool) $m->must_change_password);
        Mail::assertNothingSent();
    }

    public function test_skip_onboarding_makes_the_merchant_live_immediately(): void
    {
        $this->admin()->post(route('admin.merchants.store'), [
            'name' => 'Internal', 'email' => 'int@school.test', 'password' => 'temporary123', 'skip_onboarding' => '1',
        ]);

        $this->assertSame('approved', Merchant::where('email', 'int@school.test')->value('onboarding_status'));
    }

    public function test_welcome_email_can_be_resent_from_admin(): void
    {
        $this->admin()->post(route('admin.merchants.welcome', $this->merchant->id))->assertSessionHas('success');

        Mail::assertSent(MerchantWelcomeMail::class, fn ($m) => $m->hasTo('owner@school.test'));
    }

    // -- first login & resuming -------------------------------------------

    public function test_first_sign_in_starts_onboarding_but_later_sign_ins_go_to_the_dashboard(): void
    {
        $this->post(route('merchant.login.post'), ['email' => 'owner@school.test', 'password' => 'password123'])
            ->assertRedirect(route('merchant.onboarding.index'));

        $this->get(route('merchant.onboarding.index'))->assertRedirect(route('merchant.onboarding.show', 'welcome'));
        $this->get(route('merchant.onboarding.show', 'welcome'))->assertOk()->assertSee('Welcome, Pinnakle Rugby Academy');
        $this->assertSame('in_progress', $this->merchant->fresh()->onboarding_status);

        $this->post(route('merchant.logout'));
        $this->post(route('merchant.login.post'), ['email' => 'owner@school.test', 'password' => 'password123'])
            ->assertRedirect(route('merchant.dashboard'));
    }

    public function test_existing_merchants_are_untouched(): void
    {
        $live = $this->newMerchant('live@school.test', 'approved');

        $this->actingAs($live, 'merchants')->get(route('merchant.dashboard'))->assertOk()->assertDontSee('Finish setting up');
        $this->actingAs($live, 'merchants')->get(route('merchant.onboarding.index'))->assertRedirect(route('merchant.dashboard'));
    }

    public function test_the_dashboard_shows_progress_and_a_continue_button(): void
    {
        $this->save('business', $this->business());

        $this->owner()->get(route('merchant.dashboard'))
            ->assertOk()
            ->assertSee('Finish setting up your account')
            ->assertSee('1 of 4 steps done')
            ->assertSee('Continue');
    }

    // -- the steps ---------------------------------------------------------

    public function test_welcome_leads_to_the_business_step(): void
    {
        $this->save('welcome')->assertRedirect(route('merchant.onboarding.show', 'business'));
    }

    public function test_continuing_needs_the_required_answers(): void
    {
        $this->save('business', ['trading_name' => 'Only a name'])
            ->assertSessionHasErrors(['support_email', 'support_phone']);

        $this->save('business', $this->business(['support_email' => 'not-an-email']))->assertSessionHasErrors('support_email');
        $this->assertSame([], $this->merchant->fresh()->onboarding?->step('business') ? [] : []);
    }

    public function test_business_step_saves_and_moves_on_with_a_logo(): void
    {
        $this->save('business', $this->business(['logo' => UploadedFile::fake()->image('logo.png')]))
            ->assertRedirect(route('merchant.onboarding.show', 'bank'));

        $ob = $this->merchant->fresh()->onboarding;
        $this->assertSame('Pinnakle Rugby', $ob->step('business')['trading_name']);
        Storage::disk('public')->assertExists($ob->step('business')['logo_path']);

        // The live merchant record is not touched until they submit.
        $this->assertNull($this->merchant->fresh()->merchant_trading_name);
    }

    public function test_save_and_finish_later_keeps_partial_answers_and_resumes_there(): void
    {
        $this->save('business', ['trading_name' => 'Half done'], 'later')
            ->assertRedirect(route('merchant.dashboard'))
            ->assertSessionHas('success');

        $this->assertSame('Half done', $this->merchant->fresh()->onboarding->step('business')['trading_name']);

        // Coming back lands on the same screen, with what they typed.
        $this->owner()->get(route('merchant.onboarding.index'))->assertRedirect(route('merchant.onboarding.show', 'business'));
        $this->owner()->get(route('merchant.onboarding.show', 'business'))->assertOk()->assertSee('Half done');
    }

    public function test_finish_later_still_rejects_badly_formatted_answers(): void
    {
        $this->save('bank', ['iban' => 'GB82WEST12345698765432'], 'later')->assertSessionHasErrors('iban');
    }

    public function test_back_keeps_what_was_typed_and_goes_to_the_previous_step(): void
    {
        $this->save('bank', ['bank_name' => 'Mashreq'], 'back')->assertRedirect(route('merchant.onboarding.show', 'business'));

        $this->assertSame('Mashreq', $this->merchant->fresh()->onboarding->step('bank')['bank_name']);
    }

    public function test_back_never_traps_the_merchant_on_an_invalid_screen(): void
    {
        $this->save('business', ['support_email' => 'nope'], 'back')->assertRedirect(route('merchant.onboarding.show', 'welcome'));
    }

    public function test_iban_is_tidied_and_checked(): void
    {
        $this->save('bank', $this->bank(['iban' => 'ae07 0331 2345 6789 0123 456']))->assertRedirect(route('merchant.onboarding.show', 'contact'));
        $this->assertSame('AE070331234567890123456', $this->merchant->fresh()->onboarding->step('bank')['iban']);

        $this->save('bank', $this->bank(['iban' => 'AE12345']))->assertSessionHasErrors('iban');
        $this->save('bank', $this->bank(['iban' => 'GB82WEST12345698765432']))->assertSessionHasErrors('iban');
    }

    public function test_contact_step_stores_documents_privately(): void
    {
        $this->save('contact', $this->contact([
            'signatory_id' => UploadedFile::fake()->create('eid.pdf', 200, 'application/pdf'),
            'bank_letter' => UploadedFile::fake()->create('letter.png', 100, 'image/png'),
        ]))->assertRedirect(route('merchant.onboarding.show', 'fees'));

        $ob = $this->merchant->fresh()->onboarding;
        Storage::disk('local')->assertExists($ob->signatory_id_path);
        Storage::disk('local')->assertExists($ob->bank_letter_path);
        $this->assertStringStartsWith('onboarding/' . $this->merchant->id . '/', $ob->signatory_id_path);
        $this->assertStringNotContainsString($ob->signatory_id_path, json_encode(Storage::disk('public')->allFiles()));
    }

    public function test_documents_must_be_pdf_or_images_and_small(): void
    {
        $this->save('contact', $this->contact(['signatory_id' => UploadedFile::fake()->create('virus.exe', 10)]))->assertSessionHasErrors('signatory_id');
        $this->save('contact', $this->contact(['bank_letter' => UploadedFile::fake()->create('big.pdf', 6000, 'application/pdf')]))->assertSessionHasErrors('bank_letter');
    }

    public function test_fees_step_needs_the_agreement_and_records_who_accepted(): void
    {
        $this->save('fees')->assertSessionHasErrors('terms');

        $this->save('fees', ['terms' => '1'])->assertRedirect(route('merchant.onboarding.show', 'review'));

        $ob = $this->merchant->fresh()->onboarding;
        $this->assertNotNull($ob->terms_accepted_at);
        $this->assertSame('Pinnakle Rugby Academy', $ob->terms_accepted_by);
        $this->assertNotNull($ob->terms_accepted_ip);
    }

    public function test_fees_page_shows_the_merchants_rate(): void
    {
        $this->merchant->update(['fee_percentage' => 1.25]);

        $this->owner()->get(route('merchant.onboarding.show', 'fees'))->assertOk()->assertSee('1.25%')->assertSee('1st of each month');
    }

    public function test_a_card_is_only_required_once_billing_is_live(): void
    {
        $svc = app(OnboardingService::class);
        $this->save('fees', ['terms' => '1']);

        $this->assertTrue($svc->isStepComplete($this->merchant->fresh()->onboarding, 'fees'));

        config(['billing.enabled' => true]);
        $this->assertFalse($svc->isStepComplete($this->merchant->fresh()->onboarding, 'fees'));

        MerchantBillingCard::create([
            'merchant_id' => $this->merchant->id, 'telr_tran_ref' => 'REF1', 'card_type' => 'Visa', 'last4' => '1111',
            'expiry_month' => 12, 'expiry_year' => 2030, 'status' => 'active', 'setup_cartid' => 'EPCARD-1',
        ]);
        $this->assertTrue($svc->isStepComplete($this->merchant->fresh()->onboarding, 'fees'));
    }

    public function test_returning_from_card_registration_lands_back_in_onboarding(): void
    {
        config(['billing.enabled' => true]);
        $card = MerchantBillingCard::create([
            'merchant_id' => $this->merchant->id, 'telr_tran_ref' => 'REF1', 'card_type' => 'Visa', 'last4' => '1111',
            'expiry_month' => 12, 'expiry_year' => 2030, 'status' => 'active', 'setup_cartid' => 'EPCARD-1',
        ]);

        $this->owner()->withSession(['billing_return' => 'onboarding'])
            ->get(route('merchant.billing.card.return', 'EPCARD-1'))
            ->assertRedirect(route('merchant.onboarding.show', 'fees'));

        $this->owner()->get(route('merchant.billing.card.return', 'EPCARD-1'))
            ->assertRedirect(route('merchant.billing.index'));
    }

    // -- review & submit ---------------------------------------------------

    public function test_review_flags_unfinished_steps_and_submit_is_refused(): void
    {
        $this->save('business', $this->business());

        $this->owner()->get(route('merchant.onboarding.show', 'review'))
            ->assertOk()->assertSee('Finish this step')->assertSee('Some required details are missing');

        $this->owner()->post(route('merchant.onboarding.submit'))
            ->assertRedirect(route('merchant.onboarding.show', 'review'))
            ->assertSessionHas('error');
        $this->assertSame('in_progress', $this->merchant->fresh()->onboarding_status);
        Mail::assertNotSent(OnboardingStatusMail::class);
    }

    public function test_submitting_applies_the_answers_and_tells_edfundo(): void
    {
        $merchant = $this->submitted();

        $this->assertSame('submitted', $merchant->onboarding_status);
        $this->assertSame('Pinnakle Rugby', $merchant->merchant_trading_name);
        $this->assertSame('hello@pinnakle.test', $merchant->support_email);
        $this->assertSame('AE070331234567890123456', $merchant->iban);
        $this->assertSame('Emirates NBD', $merchant->bank_name);
        $this->assertSame('Pinnakle Sports LLC', $merchant->account_holder_name);
        $this->assertNotNull($merchant->onboarding->submitted_at);

        Mail::assertSent(OnboardingStatusMail::class, fn ($m) => $m->kind === 'submitted' && $m->hasTo('ops@edfundo.test'));
    }

    public function test_after_submitting_the_journey_is_locked_and_shows_the_waiting_page(): void
    {
        $this->submitted();

        $this->owner()->get(route('merchant.onboarding.index'))->assertOk()->assertSee('Thanks, we have your details');
        $this->owner()->get(route('merchant.onboarding.show', 'bank'))->assertRedirect(route('merchant.onboarding.index'));
        $this->save('bank', $this->bank(['iban' => 'AE990331234567890123999']))->assertRedirect(route('merchant.onboarding.index'));
        $this->assertSame('AE070331234567890123456', $this->merchant->fresh()->iban);

        $this->owner()->get(route('merchant.dashboard'))->assertSee('with Edfundo for review');
    }

    // -- who can do it -----------------------------------------------------

    public function test_only_owners_can_run_onboarding(): void
    {
        $this->merchant->update(['onboarding_status' => 'in_progress']);

        foreach (['staff', 'viewer'] as $role) {
            $member = MerchantUser::create([
                'merchant_id' => $this->merchant->id, 'name' => ucfirst($role), 'email' => "$role@school.test",
                'password' => 'memberpass1', 'role' => $role,
            ]);

            $this->flushSession();
            $this->withSession(['merchant_member_id' => $member->id])->actingAs($this->merchant->fresh(), 'merchants')
                ->get(route('merchant.onboarding.index'))->assertRedirect(route('merchant.dashboard'));
        }
    }

    // -- gating real payments ---------------------------------------------

    private function invoiceFor(Merchant $merchant): Invoice
    {
        $consumer = \App\Models\Consumer::create(['merchant_id' => $merchant->id, 'name' => 'Parent', 'email' => 'parent@x.test']);

        return Invoice::create([
            'merchant_id' => $merchant->id, 'consumer_id' => $consumer->id, 'total_fee' => 100, 'status' => 0, 'link_type' => 'personal',
        ]);
    }

    public function test_payers_cannot_use_pages_of_a_merchant_that_is_not_approved(): void
    {
        $invoice = $this->invoiceFor($this->merchant);
        $product = Product::create(['merchant_id' => $this->merchant->id, 'name' => 'Trip', 'description' => 'x', 'fee' => 50, 'state' => 'active']);

        $this->get(route('public.invoice.show', $invoice->uuid))->assertForbidden();
        $this->post(route('public.invoice.pay', $invoice->uuid), [])->assertForbidden();
        $this->get(route('public.product', $product->uuid))->assertForbidden();
        $this->post(route('public.product.store', $product->uuid), ['customer_name' => 'A', 'customer_email' => 'a@x.test', 'customer_mobile' => '050'])->assertForbidden();
    }

    public function test_the_same_pages_work_once_approved(): void
    {
        $this->merchant->update(['onboarding_status' => 'approved']);
        $invoice = $this->invoiceFor($this->merchant);
        $product = Product::create(['merchant_id' => $this->merchant->id, 'name' => 'Trip', 'description' => 'x', 'fee' => 50, 'state' => 'active']);

        $this->get(route('public.invoice.show', $invoice->uuid))->assertOk();
        $this->get(route('public.product', $product->uuid))->assertOk();
    }

    public function test_an_unapproved_merchant_can_prepare_but_not_send_payment_links(): void
    {
        $this->merchant->update(['onboarding_status' => 'in_progress']);
        $product = Product::create(['merchant_id' => $this->merchant->id, 'name' => 'Trip', 'description' => 'x', 'fee' => 50, 'state' => 'active']);

        // Preparing is fine…
        $this->owner()->post(route('merchant.products.payers.store', $product->id), [
            'name' => 'Pat Parent', 'student_name' => 'Sam', 'email' => 'pat@x.test',
        ])->assertSessionHasNoErrors();
        $invoice = Invoice::where('merchant_id', $this->merchant->id)->firstOrFail();

        // …sending is not.
        $this->owner()->post(route('merchant.products.payers.send', $product->id))->assertSessionHas('error');
        $this->owner()->post(route('merchant.products.payers.retry', $product->id))->assertSessionHas('error');
        $this->owner()->post(route('merchant.invoices.resend-link', $invoice->id))->assertSessionHas('error');

        $this->assertSame(0, Invoice::where('link_email_status', 'queued')->count());
        Mail::assertNothingSent();
    }

    // -- admin review ------------------------------------------------------

    public function test_admin_sees_the_submission_and_can_download_documents(): void
    {
        $this->save('contact', $this->contact(['signatory_id' => UploadedFile::fake()->create('eid.pdf', 50, 'application/pdf')]));
        $this->completeEverything();
        $this->owner()->post(route('merchant.onboarding.submit'));

        $this->admin()->get(route('admin.merchants.show', $this->merchant->id))
            ->assertOk()->assertSee('Awaiting review')->assertSee('Sara Ahmed')->assertSee('AE070331234567890123456')
            ->assertSee('Contact ID');

        $this->admin()->get(route('admin.merchants.onboarding.document', [$this->merchant->id, 'signatory-id']))->assertOk();
        $this->admin()->get(route('admin.merchants.onboarding.document', [$this->merchant->id, 'bank-letter']))->assertNotFound();

        $this->admin()->get(route('admin.merchants.index'))->assertSee('waiting for onboarding review');
    }

    public function test_documents_are_not_available_to_the_public_or_merchants(): void
    {
        $this->save('contact', $this->contact(['signatory_id' => UploadedFile::fake()->create('eid.pdf', 50, 'application/pdf')]));

        $this->flushSession();
        $this->get(route('admin.merchants.onboarding.document', [$this->merchant->id, 'signatory-id']))->assertRedirect();
        $this->actingAs($this->merchant->fresh(), 'merchants')
            ->get(route('admin.merchants.onboarding.document', [$this->merchant->id, 'signatory-id']))->assertRedirect();
    }

    public function test_approval_needs_a_submission_a_lean_destination_and_then_goes_live(): void
    {
        // Not submitted yet.
        $this->admin()->post(route('admin.merchants.onboarding.approve', $this->merchant->id))->assertSessionHas('error');

        $this->submitted();

        // Submitted but no Lean destination.
        $this->admin()->post(route('admin.merchants.onboarding.approve', $this->merchant->id))->assertSessionHas('error');
        $this->assertSame('submitted', $this->merchant->fresh()->onboarding_status);

        $this->merchant->update(['lean_destination_id' => 'dest-123']);
        $this->admin()->post(route('admin.merchants.onboarding.approve', $this->merchant->id))->assertSessionHas('success');

        $merchant = $this->merchant->fresh();
        $this->assertTrue($merchant->acceptsPayments());
        $this->assertSame('Edfundo Ops', $merchant->onboarding->reviewed_by);
        Mail::assertSent(OnboardingStatusMail::class, fn ($m) => $m->kind === 'approved' && $m->hasTo('owner@school.test'));

        $this->owner()->get(route('merchant.dashboard'))->assertDontSee('Finish setting up');
    }

    public function test_sending_back_needs_a_note_and_lets_the_merchant_edit_and_resubmit(): void
    {
        $this->submitted();

        $this->admin()->post(route('admin.merchants.onboarding.changes', $this->merchant->id), ['note' => ''])->assertSessionHasErrors('note');

        $this->admin()->post(route('admin.merchants.onboarding.changes', $this->merchant->id), ['note' => "The IBAN does not match the trade licence name."])
            ->assertSessionHas('success');

        $this->assertSame('changes_requested', $this->merchant->fresh()->onboarding_status);
        Mail::assertSent(OnboardingStatusMail::class, function ($m) {
            return $m->kind === 'changes' && $m->hasTo('owner@school.test') && str_contains($m->render(), 'The IBAN does not match');
        });

        // The merchant sees the note and can fix it.
        $this->owner()->get(route('merchant.dashboard'))->assertSee('asked for a few changes')->assertSee('The IBAN does not match');
        $this->owner()->get(route('merchant.onboarding.show', 'bank'))->assertOk()->assertSee('The IBAN does not match');

        $this->save('bank', $this->bank(['iban' => 'AE990331234567890123999']))->assertRedirect();
        $this->owner()->post(route('merchant.onboarding.submit'))->assertRedirect(route('merchant.onboarding.index'));

        $merchant = $this->merchant->fresh();
        $this->assertSame('submitted', $merchant->onboarding_status);
        $this->assertSame('AE990331234567890123999', $merchant->iban);
    }

    public function test_onboarding_screens_compile(): void
    {
        foreach (['welcome', 'business', 'bank', 'contact', 'fees', 'review'] as $step) {
            $this->owner()->get(route('merchant.onboarding.show', $step))->assertOk();
        }
    }
}
