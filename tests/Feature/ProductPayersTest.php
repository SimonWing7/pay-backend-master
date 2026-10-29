<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Mail\InvoicePaymentLinkMail;
use App\Mail\InvoiceReminderMail;
use App\Models\AppUserPayment;
use App\Models\Consumer;
use App\Models\Invoice;
use App\Models\Merchant;
use App\Models\Product;
use App\Models\ProductReminder;
use App\Services\InvoiceService;
use App\Services\PayerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ProductPayersTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchant;
    private Product $term1;
    private Product $term2;

    private const HEADER = "Parent Name,Student/Player Name,Parent Email,Parent Mobile\n";

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->merchant = $this->makeMerchant('Pinnakle Rugby Academy', 'pinnakle@example.com');
        $this->term1 = $this->makeProduct($this->merchant, 'Term 1 Fees', 1000);
        $this->term2 = $this->makeProduct($this->merchant, 'Term 2 Fees', 1200);
    }

    // -- helpers -----------------------------------------------------------

    private function makeMerchant(string $name, string $email): Merchant
    {
        return Merchant::create([
            'name' => $name, 'merchant_trading_name' => $name, 'email' => $email,
            'password' => 'password123', 'is_active' => true, 'must_change_password' => false,
        ]);
    }

    private function makeProduct(Merchant $merchant, string $name, float $fee): Product
    {
        return Product::create(['merchant_id' => $merchant->id, 'name' => $name, 'description' => $name, 'fee' => $fee, 'state' => 'active']);
    }

    private function upload(Product $product, string $csv, bool $combine = true, ?Merchant $as = null)
    {
        return $this->actingAs($as ?? $this->merchant, 'merchants')->post(
            route('merchant.products.payers.import', $product->id),
            array_filter(['file' => UploadedFile::fake()->createWithContent('payers.csv', $csv), 'combine' => $combine ? '1' : null])
        );
    }

    private function payer(Product $product, string $parent, string $student, ?string $email, ?string $mobile = null): Invoice
    {
        $csv = self::HEADER . "{$parent},{$student}," . ($email ?? '') . ',' . ($mobile ?? '') . "\n";
        app(PayerService::class)->importCsv($product, $csv, false);

        return Invoice::where('merchant_id', $product->merchant_id)
            ->whereHas('invoiceDetails.consumer', fn ($q) => $q->where('student_name', $student))
            ->whereHas('invoiceDetails', fn ($q) => $q->where('product_id', $product->id))
            ->latest('id')->first();
    }

    private function setLink(Invoice $invoice, ?string $status): Invoice
    {
        DB::table('invoices')->where('id', $invoice->id)->update(['link_email_status' => $status]);

        return $invoice->fresh();
    }

    // -- payers belong to one product -------------------------------------

    public function test_uploading_to_a_product_creates_invoices_for_that_product_only_and_emails_nobody(): void
    {
        $this->upload($this->term1, self::HEADER
            . "Chris Daniels,Henry Daniels,chris@example.com,0501234567\n"
            . "Pat Jones,Sam Jones,pat@example.com,0507654321\n")
            ->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertSame(2, Invoice::count());
        $this->assertSame(2, app(PayerService::class)->payersQuery($this->term1)->count());
        $this->assertSame(0, app(PayerService::class)->payersQuery($this->term2)->count());
        $this->assertSame(['not_sent', 'not_sent'], Invoice::pluck('link_email_status')->all());
        $this->assertSame(['batch', 'batch'], Invoice::pluck('link_type')->all());
        Mail::assertNothingSent();
    }

    public function test_the_same_parents_on_a_second_product_are_separate_not_mixed_in(): void
    {
        $csv = self::HEADER . "Chris Daniels,Henry Daniels,chris@example.com,0501234567\n";

        $this->upload($this->term1, $csv);
        $this->upload($this->term2, $csv);

        $this->assertSame(1, app(PayerService::class)->payersQuery($this->term1)->count());
        $this->assertSame(1, app(PayerService::class)->payersQuery($this->term2)->count());
        $this->assertSame(1, Consumer::count());               // one contact...
        $this->assertSame(2, Invoice::count());                // ...invoiced separately per product
        $this->assertSame(1000.0, (float) Invoice::whereHas('invoiceDetails', fn ($q) => $q->where('product_id', $this->term1->id))->first()->total_fee);
        $this->assertSame(1200.0, (float) Invoice::whereHas('invoiceDetails', fn ($q) => $q->where('product_id', $this->term2->id))->first()->total_fee);
    }

    public function test_re_uploading_the_same_file_never_double_bills(): void
    {
        $csv = self::HEADER . "Chris Daniels,Henry Daniels,chris@example.com,\nPat Jones,Sam Jones,pat@example.com,\n";

        $this->upload($this->term1, $csv);
        $this->upload($this->term1, $csv)
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'already on this product'));

        $this->assertSame(2, Invoice::count());
    }

    public function test_someone_already_paid_is_skipped_but_a_removed_payer_can_be_added_again(): void
    {
        $paid = $this->payer($this->term1, 'Chris Daniels', 'Henry Daniels', 'chris@example.com');
        $paid->update(['status' => InvoiceStatus::Paid]);
        $removed = $this->payer($this->term1, 'Pat Jones', 'Sam Jones', 'pat@example.com');
        $removed->update(['status' => InvoiceStatus::Archived]);

        $csv = self::HEADER . "Chris Daniels,Henry Daniels,chris@example.com,\nPat Jones,Sam Jones,pat@example.com,\n";
        $result = app(PayerService::class)->importCsv($this->term1, $csv, true);

        $this->assertSame(1, $result['already']);   // the one who paid
        $this->assertSame(1, $result['children']);  // the removed one comes back
        $this->assertSame(3, Invoice::count());
    }

    public function test_siblings_are_combined_into_one_invoice_per_family_when_ticked_and_separate_when_not(): void
    {
        $csv = self::HEADER
            . "Chris Daniels,Henry Daniels,chris@example.com,0501234567\n"
            . "Chris Daniels,Poppy Daniels,chris@example.com,0501234567\n"
            . "Pat Jones,Sam Jones,pat@example.com,0507654321\n";

        $this->upload($this->term1, $csv, true);
        $this->assertSame(2, Invoice::count());
        $invoice = Invoice::with('invoiceDetails')->get()->first(fn ($i) => $i->invoiceDetails->count() === 2);
        $this->assertSame(2000.0, (float) $invoice->total_fee);
        $this->assertEqualsCanonicalizing(
            ['Term 1 Fees — Henry Daniels', 'Term 1 Fees — Poppy Daniels'],
            $invoice->invoiceDetails->pluck('title')->all()
        );

        // Untick: a fresh product gets one invoice per child
        $this->upload($this->term2, $csv, false);
        $this->assertSame(3, app(PayerService::class)->payersQuery($this->term2)->count());
    }

    public function test_families_are_found_by_mobile_chained_through_a_sibling_and_no_contact_people_stay_alone(): void
    {
        $a = Consumer::create(['merchant_id' => $this->merchant->id, 'name' => 'P', 'student_name' => 'A', 'email' => 'f@example.com', 'mobile_number' => '0501111111']);
        $b = Consumer::create(['merchant_id' => $this->merchant->id, 'name' => 'P', 'student_name' => 'B', 'email' => 'f@example.com', 'mobile_number' => '+971 50 222 2222']);
        $c = Consumer::create(['merchant_id' => $this->merchant->id, 'name' => 'P', 'student_name' => 'C', 'email' => null, 'mobile_number' => '0502222222']);
        $d = Consumer::create(['merchant_id' => $this->merchant->id, 'name' => 'Q', 'student_name' => 'D', 'email' => null, 'mobile_number' => null]);
        $e = Consumer::create(['merchant_id' => $this->merchant->id, 'name' => 'R', 'student_name' => 'E', 'email' => null, 'mobile_number' => null]);

        $result = app(InvoiceService::class)->addPayersToProduct($this->term1, [$a->id, $b->id, $c->id, $d->id, $e->id], true);

        $this->assertEqualsCanonicalizing([3, 1, 1], $result['invoices']->map(fn ($i) => $i->invoiceDetails->count())->all());
    }

    public function test_the_invoice_goes_to_a_family_member_who_can_be_emailed(): void
    {
        $noEmail = Consumer::create(['merchant_id' => $this->merchant->id, 'name' => 'P', 'student_name' => 'A', 'mobile_number' => '0501111111']);
        $withEmail = Consumer::create(['merchant_id' => $this->merchant->id, 'name' => 'P', 'student_name' => 'B', 'email' => 'f@example.com', 'mobile_number' => '0501111111']);

        $invoice = app(InvoiceService::class)->addPayersToProduct($this->term1, [$noEmail->id, $withEmail->id], true)['invoices']->first();

        $this->assertSame($withEmail->id, $invoice->consumer_id);
    }

    public function test_unknown_or_other_merchants_contacts_are_never_invoiced(): void
    {
        $stranger = $this->makeMerchant('Other Co', 'other@example.com');
        $theirs = Consumer::create(['merchant_id' => $stranger->id, 'name' => 'X', 'student_name' => 'Y', 'email' => 'x@example.com']);

        $result = app(InvoiceService::class)->addPayersToProduct($this->term1, [$theirs->id, 99999], true);

        $this->assertSame(0, $result['children']);
        $this->assertSame(0, Invoice::count());
    }

    // -- the upload itself -------------------------------------------------

    public function test_skipped_rows_are_reported_with_the_row_number_and_reason(): void
    {
        $this->upload($this->term1, self::HEADER
            . "Chris Daniels,Henry,chris@example.com,\n"
            . ",Nameless,x@example.com,\n"
            . "No Contact,Kid,,\n")
            ->assertSessionHas('payer_skipped', fn ($rows) => $rows === [
                ['row' => 3, 'reason' => 'no parent name'],
                ['row' => 4, 'reason' => 'no email or mobile number'],
            ]);

        $this->assertSame(1, Invoice::count());
    }

    public function test_a_file_over_the_row_limit_is_refused_and_nothing_is_created(): void
    {
        config(['invoices.max_import_rows' => 2]);

        $this->upload($this->term1, self::HEADER . "A,A1,a@x.com,\nB,B1,b@x.com,\nC,C1,c@x.com,\n")
            ->assertSessionHasErrors('file');

        $this->assertSame(0, Invoice::count());
        $this->assertSame(0, Consumer::count());
    }

    public function test_a_failure_part_way_through_leaves_nothing_half_created(): void
    {
        $service = new class(app(\App\Services\ConsumerImportService::class), app(InvoiceService::class)) extends PayerService {
            public function importCsv(Product $product, string $csv, bool $combine = true): array
            {
                return DB::transaction(function () use ($product, $csv, $combine) {
                    parent::importCsv($product, $csv, $combine);
                    throw new \RuntimeException('boom after creating everything');
                });
            }
        };

        try {
            $service->importCsv($this->term1, self::HEADER . "Chris Daniels,Henry,chris@example.com,\n");
            $this->fail('expected the exception');
        } catch (\RuntimeException) {
        }

        $this->assertSame(0, Invoice::count());
        $this->assertSame(0, Consumer::count());
    }

    public function test_a_single_person_can_be_added_but_needs_an_email_or_mobile(): void
    {
        $this->actingAs($this->merchant, 'merchants')
            ->post(route('merchant.products.payers.store', $this->term1->id), ['name' => 'No Contact'])
            ->assertSessionHasErrors('email', null, 'addPayer');
        $this->assertSame(0, Invoice::count());

        $this->actingAs($this->merchant, 'merchants')
            ->post(route('merchant.products.payers.store', $this->term1->id), [
                'name' => 'Chris Daniels', 'student_name' => 'Henry Daniels', 'email' => 'chris@example.com',
            ])->assertSessionHasNoErrors();
        $this->assertSame(1, Invoice::count());
    }

    public function test_another_merchants_product_cannot_be_touched(): void
    {
        $stranger = $this->makeMerchant('Other Co', 'other@example.com');

        $this->upload($this->term1, self::HEADER . "A,A1,a@x.com,\n", true, $stranger)->assertNotFound();

        $this->actingAs($stranger, 'merchants')->post(route('merchant.products.payers.send', $this->term1->id))->assertNotFound();
        $this->actingAs($stranger, 'merchants')->get(route('merchant.products.payers.export', $this->term1->id))->assertNotFound();
        $this->assertSame(0, Invoice::count());
    }

    // -- sending in the background ----------------------------------------

    public function test_send_queues_only_unpaid_people_with_an_email_who_have_not_been_sent_a_link(): void
    {
        $notSent = $this->payer($this->term1, 'A Parent', 'A', 'a@example.com');
        $failed = $this->setLink($this->payer($this->term1, 'B Parent', 'B', 'b@example.com'), 'failed');
        $sent = $this->setLink($this->payer($this->term1, 'C Parent', 'C', 'c@example.com'), 'sent');
        $noEmail = $this->payer($this->term1, 'D Parent', 'D', null, '0501234567');
        $paid = $this->payer($this->term1, 'E Parent', 'E', 'e@example.com');
        $paid->update(['status' => InvoiceStatus::Paid]);

        $this->actingAs($this->merchant, 'merchants')
            ->post(route('merchant.products.payers.send', $this->term1->id))
            ->assertSessionHas('success', fn ($m) => str_contains($m, '2 payment links queued'));

        $this->assertSame('queued', $notSent->fresh()->link_email_status);
        $this->assertSame('queued', $failed->fresh()->link_email_status);
        $this->assertSame('sent', $sent->fresh()->link_email_status);
        $this->assertSame('not_sent', $noEmail->fresh()->link_email_status);
        $this->assertSame('not_sent', $paid->fresh()->link_email_status);
        Mail::assertNothingSent(); // the request itself sends nothing — the background command does
    }

    public function test_sending_with_nobody_eligible_says_so(): void
    {
        $this->actingAs($this->merchant, 'merchants')
            ->post(route('merchant.products.payers.send', $this->term1->id))
            ->assertSessionHas('error');
    }

    public function test_the_background_sender_sends_a_batch_stamps_them_and_leaves_the_rest_queued(): void
    {
        $invoices = [];
        foreach (range(1, 5) as $n) {
            $invoices[] = $this->setLink($this->payer($this->term1, "Parent {$n}", "Kid {$n}", "p{$n}@example.com"), 'queued');
        }

        $this->artisan('payers:send-links --limit=2')->assertExitCode(0);

        Mail::assertSent(InvoicePaymentLinkMail::class, 2);
        $this->assertSame(2, Invoice::where('link_email_status', 'sent')->count());
        $this->assertSame(3, Invoice::where('link_email_status', 'queued')->count());
        $sent = Invoice::where('link_email_status', 'sent')->first();
        $this->assertNotNull($sent->link_emailed_at);

        // Later runs finish the job and never email anyone twice
        $this->artisan('payers:send-links')->assertExitCode(0);
        $this->artisan('payers:send-links')->assertExitCode(0);
        Mail::assertSent(InvoicePaymentLinkMail::class, 5);
        $this->assertSame(5, Invoice::where('link_email_status', 'sent')->count());
    }

    public function test_one_failed_email_is_recorded_and_does_not_stop_the_rest(): void
    {
        $first = $this->setLink($this->payer($this->term1, 'Parent 1', 'Kid 1', 'p1@example.com'), 'queued');
        $second = $this->setLink($this->payer($this->term1, 'Parent 2', 'Kid 2', 'p2@example.com'), 'queued');

        $calls = 0;
        $sentTo = [];
        Mail::shouldReceive('to')->andReturnUsing(function ($email) use (&$calls, &$sentTo) {
            $calls++;
            if ($calls === 1) {
                throw new \RuntimeException('SES is down');
            }
            $sentTo[] = $email;

            return new class { public function send($mailable): void {} };
        });

        $this->artisan('payers:send-links')->assertExitCode(0);

        $this->assertSame('failed', $first->fresh()->link_email_status);
        $this->assertSame('SES is down', $first->fresh()->link_email_error);
        $this->assertSame('sent', $second->fresh()->link_email_status);
        $this->assertSame(['p2@example.com'], $sentTo);
    }

    public function test_a_queued_invoice_that_was_paid_meanwhile_is_not_emailed(): void
    {
        $invoice = $this->setLink($this->payer($this->term1, 'Parent 1', 'Kid 1', 'p1@example.com'), 'queued');
        $invoice->update(['status' => InvoiceStatus::Paid]);

        $this->artisan('payers:send-links')->assertExitCode(0);

        Mail::assertNothingSent();
        $this->assertSame('not_sent', $invoice->fresh()->link_email_status);
    }

    public function test_an_invoice_stuck_mid_send_is_put_back_in_the_queue_and_sent(): void
    {
        $invoice = $this->setLink($this->payer($this->term1, 'Parent 1', 'Kid 1', 'p1@example.com'), 'sending');
        DB::table('invoices')->where('id', $invoice->id)->update(['updated_at' => now()->subHour()]);

        $this->artisan('payers:send-links')->assertExitCode(0);

        Mail::assertSent(InvoicePaymentLinkMail::class, 1);
        $this->assertSame('sent', $invoice->fresh()->link_email_status);
    }

    public function test_failed_links_can_be_retried_without_touching_the_ones_already_sent(): void
    {
        $failed = $this->setLink($this->payer($this->term1, 'Parent 1', 'Kid 1', 'p1@example.com'), 'failed');
        $sent = $this->setLink($this->payer($this->term1, 'Parent 2', 'Kid 2', 'p2@example.com'), 'sent');
        $notSent = $this->payer($this->term1, 'Parent 3', 'Kid 3', 'p3@example.com');

        $this->actingAs($this->merchant, 'merchants')->post(route('merchant.products.payers.retry', $this->term1->id))->assertSessionHas('success');

        $this->assertSame('queued', $failed->fresh()->link_email_status);
        $this->assertSame('sent', $sent->fresh()->link_email_status);
        $this->assertSame('not_sent', $notSent->fresh()->link_email_status);
    }

    public function test_the_single_resend_button_stamps_the_status(): void
    {
        $invoice = $this->payer($this->term1, 'Parent 1', 'Kid 1', 'p1@example.com');

        $this->actingAs($this->merchant, 'merchants')->post(route('merchant.invoices.resend-link', $invoice->id))->assertSessionHas('success');

        Mail::assertSent(InvoicePaymentLinkMail::class, 1);
        $this->assertSame('sent', $invoice->fresh()->link_email_status);
        $this->assertNotNull($invoice->fresh()->link_emailed_at);
    }

    // -- emails ------------------------------------------------------------

    public function test_a_family_gets_one_email_listing_every_child_and_the_total(): void
    {
        $this->upload($this->term1, self::HEADER
            . "Chris Daniels,Henry Daniels,chris@example.com,\nChris Daniels,Poppy Daniels,chris@example.com,\nPat Jones,Sam Jones,pat@example.com,\n");
        $this->actingAs($this->merchant, 'merchants')->post(route('merchant.products.payers.send', $this->term1->id));
        $this->artisan('payers:send-links')->assertExitCode(0);

        Mail::assertSent(InvoicePaymentLinkMail::class, 2); // one per family, not per child

        Mail::assertSent(InvoicePaymentLinkMail::class, function ($mail) {
            if (!$mail->hasTo('chris@example.com')) {
                return false;
            }
            $html = $mail->render();

            return str_contains($html, 'Term 1 Fees — Henry Daniels')
                && str_contains($html, 'Term 1 Fees — Poppy Daniels')
                && str_contains($html, '2,000.00')
                && substr_count($html, route('public.invoice.show', $mail->invoice->uuid)) === 1;
        });

        Mail::assertSent(InvoicePaymentLinkMail::class, function ($mail) {
            return $mail->hasTo('pat@example.com')
                && str_contains($mail->render(), 'a payment request for Sam Jones')
                && str_contains($mail->render(), '1,000.00');
        });
    }

    public function test_reminders_list_the_children_too(): void
    {
        $invoice = app(InvoiceService::class)->addPayersToProduct($this->term1, [
            Consumer::create(['merchant_id' => $this->merchant->id, 'name' => 'Chris', 'student_name' => 'Henry', 'email' => 'c@example.com'])->id,
            Consumer::create(['merchant_id' => $this->merchant->id, 'name' => 'Chris', 'student_name' => 'Poppy', 'email' => 'c@example.com'])->id,
        ], true)['invoices']->first();

        $html = (new InvoiceReminderMail($invoice, 'Fees are due on Friday.'))->render();

        $this->assertStringContainsString('Term 1 Fees — Henry', $html);
        $this->assertStringContainsString('Term 1 Fees — Poppy', $html);
        $this->assertStringContainsString('2,000.00', $html);
    }

    public function test_reminders_only_chase_people_who_were_actually_sent_their_link(): void
    {
        $sent = $this->setLink($this->payer($this->term1, 'Sent Parent', 'S', 'sent@example.com'), 'sent');
        $legacy = $this->setLink($this->payer($this->term1, 'Legacy Parent', 'L', 'legacy@example.com'), null);
        $notSent = $this->payer($this->term1, 'NotSent Parent', 'N', 'notsent@example.com');
        $queued = $this->setLink($this->payer($this->term1, 'Queued Parent', 'Q', 'queued@example.com'), 'queued');
        $failed = $this->setLink($this->payer($this->term1, 'Failed Parent', 'F', 'failed@example.com'), 'failed');

        ProductReminder::create(['product_id' => $this->term1->id, 'send_date' => now()->toDateString(), 'message' => 'Please pay.']);

        $this->artisan('reminders:send')->assertExitCode(0);

        Mail::assertSent(InvoiceReminderMail::class, 2);
        Mail::assertSent(InvoiceReminderMail::class, fn ($m) => $m->hasTo('sent@example.com'));
        Mail::assertSent(InvoiceReminderMail::class, fn ($m) => $m->hasTo('legacy@example.com'));
        Mail::assertNotSent(InvoiceReminderMail::class, fn ($m) => $m->hasTo('notsent@example.com') || $m->hasTo('queued@example.com') || $m->hasTo('failed@example.com'));
    }

    // -- the product page --------------------------------------------------

    public function test_the_page_summarises_payers_and_money_and_filters_and_searches(): void
    {
        $paid = $this->payer($this->term1, 'Paid Parent', 'Paula', 'paid@example.com');
        $paid->update(['status' => InvoiceStatus::Paid]);
        $this->setLink($paid, 'sent');
        $this->payer($this->term1, 'Open Parent', 'Oscar', 'open@example.com');
        $this->payer($this->term1, 'Nomail Parent', 'Nora', null, '0501112222');
        $this->payer($this->term2, 'Other Term Parent', 'Elsewhere', 'elsewhere@example.com'); // must not show

        $page = $this->actingAs($this->merchant, 'merchants')->get(route('merchant.products.show', $this->term1->id));
        $page->assertOk()->assertSee('Paula')->assertSee('Oscar')->assertSee('Nora')->assertDontSee('Elsewhere');
        $page->assertSee('1,000.00')->assertSee('of 3')->assertSee('No email');
        $page->assertSee('Send payment links (1)'); // only Oscar is sendable

        $this->actingAs($this->merchant, 'merchants')->get(route('merchant.products.show', [$this->term1->id, 'status' => 'paid']))
            ->assertSee('Paula')->assertDontSee('Oscar');
        $this->actingAs($this->merchant, 'merchants')->get(route('merchant.products.show', [$this->term1->id, 'status' => 'unpaid']))
            ->assertSee('Oscar')->assertDontSee('Paula');
        $this->actingAs($this->merchant, 'merchants')->get(route('merchant.products.show', [$this->term1->id, 'link' => 'no_email']))
            ->assertSee('Nora')->assertDontSee('Oscar');
        $this->actingAs($this->merchant, 'merchants')->get(route('merchant.products.show', [$this->term1->id, 'q' => 'osc']))
            ->assertSee('Oscar')->assertDontSee('Paula');
    }

    public function test_the_list_is_paginated_for_large_schools(): void
    {
        $rows = self::HEADER;
        foreach (range(1, 120) as $n) {
            $rows .= "Parent {$n},Child {$n},parent{$n}@example.com,\n";
        }
        $this->upload($this->term1, $rows)->assertSessionHasNoErrors();

        $summary = app(PayerService::class)->summary($this->term1);
        $this->assertSame(120, $summary['invoices']);
        $this->assertSame(120, $summary['sendable']);

        $page1 = app(PayerService::class)->paginate($this->term1, []);
        $this->assertSame(50, $page1->count());
        $this->assertSame(120, $page1->total());
        $this->assertSame(3, $page1->lastPage());

        $this->actingAs($this->merchant, 'merchants')
            ->get(route('merchant.products.show', [$this->term1->id, 'page' => 3]))
            ->assertOk()->assertSee('Showing 101–120 of 120');
    }

    public function test_an_empty_product_invites_you_to_add_people(): void
    {
        $this->actingAs($this->merchant, 'merchants')->get(route('merchant.products.show', $this->term1->id))
            ->assertOk()->assertSee('Add the people who need to pay')->assertSee('Download a template');
    }

    public function test_removing_an_unpaid_payer_hides_them_but_a_paid_one_cannot_be_removed(): void
    {
        $unpaid = $this->payer($this->term1, 'Unpaid Parent', 'Una', 'u@example.com');
        $paid = $this->payer($this->term1, 'Paid Parent', 'Paula', 'p@example.com');
        $paid->update(['status' => InvoiceStatus::Paid]);

        $this->actingAs($this->merchant, 'merchants')
            ->post(route('merchant.products.payers.remove', [$this->term1->id, $unpaid->id]))->assertSessionHas('success');
        $this->actingAs($this->merchant, 'merchants')
            ->post(route('merchant.products.payers.remove', [$this->term1->id, $paid->id]))->assertSessionHas('error');

        $this->assertSame(InvoiceStatus::Archived, $unpaid->fresh()->status);
        $this->assertSame(InvoiceStatus::Paid, $paid->fresh()->status);
        $this->actingAs($this->merchant, 'merchants')->get(route('merchant.products.show', $this->term1->id))
            ->assertDontSee('Una')->assertSee('Paula');
    }

    public function test_export_and_template_downloads(): void
    {
        $this->payer($this->term1, 'Chris Daniels', 'Henry Daniels', 'chris@example.com', '0501234567');

        $export = $this->actingAs($this->merchant, 'merchants')->get(route('merchant.products.payers.export', $this->term1->id));
        $export->assertOk();
        $csv = $export->streamedContent();
        $this->assertStringContainsString('Payment link', $csv);
        $this->assertStringContainsString('Henry Daniels', $csv);
        $this->assertStringContainsString('chris@example.com', $csv);
        $this->assertStringContainsString('/invoice/', $csv);

        $template = $this->actingAs($this->merchant, 'merchants')->get(route('merchant.products.payers.template', $this->term1->id))->streamedContent();
        $header = str_getcsv(ltrim(strtok($template, "\n"), "\xEF\xBB\xBF"));
        $this->assertSame(['Parent Name', 'Student/Player Name', 'Parent Email', 'Parent Mobile', 'Amount'], $header);
    }

    // -- each payer's own amount ------------------------------------------

    private const AMOUNT_HEADER = "Parent Name,Student/Player Name,Parent Email,Parent Mobile,Amount\n";

    public function test_an_amount_column_gives_each_payer_their_own_amount_and_blank_uses_the_product_fee(): void
    {
        $this->upload($this->term1, self::AMOUNT_HEADER
            . "Chris Daniels,Henry Daniels,chris@example.com,0501234567,14500\n"
            . "Pat Jones,Sam Jones,pat@example.com,0507654321,\n"
            . "Lee Wong,Amy Wong,lee@example.com,0509999999,\"9,750.50\"\n")
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', fn ($m) => str_contains($m, '2 with their own amount'));

        $byParent = Invoice::with('consumer', 'invoiceDetails')->get()->keyBy(fn ($i) => $i->consumer->name);
        $this->assertSame(14500.0, (float) $byParent['Chris Daniels']->total_fee);
        $this->assertSame(1000.0, (float) $byParent['Pat Jones']->total_fee);
        $this->assertSame(9750.5, (float) $byParent['Lee Wong']->total_fee);
        $this->assertSame(14500.0, (float) $byParent['Chris Daniels']->invoiceDetails->first()->fee);
    }

    public function test_a_file_without_an_amount_column_still_charges_the_product_fee(): void
    {
        $this->upload($this->term1, self::HEADER . "Chris Daniels,Henry Daniels,chris@example.com,0501234567\n");

        $this->assertSame(1000.0, (float) Invoice::first()->total_fee);
    }

    public function test_aed_prefix_and_thousand_separators_are_understood(): void
    {
        $this->upload($this->term1, self::AMOUNT_HEADER
            . "A One,Kid A,a@example.com,,AED 12 500\n"
            . "B Two,Kid B,b@example.com,,\"AED 8,250.00\"\n");

        $this->assertEqualsCanonicalizing([12500.0, 8250.0], Invoice::pluck('total_fee')->map(fn ($f) => (float) $f)->all());
    }

    public function test_siblings_each_pay_their_own_amount_and_the_family_invoice_adds_them_up(): void
    {
        $this->upload($this->term1, self::AMOUNT_HEADER
            . "Chris Daniels,Henry Daniels,chris@example.com,0501234567,14500\n"
            . "Chris Daniels,Poppy Daniels,chris@example.com,0501234567,13050\n"
            . "Chris Daniels,Tom Daniels,chris@example.com,0501234567,\n", true);

        $this->assertSame(1, Invoice::count());
        $invoice = Invoice::with('invoiceDetails')->first();
        $this->assertSame(14500.0 + 13050.0 + 1000.0, (float) $invoice->total_fee);
        $this->assertEqualsCanonicalizing([14500.0, 13050.0, 1000.0], $invoice->invoiceDetails->pluck('fee')->map(fn ($f) => (float) $f)->all());
    }

    public function test_a_bad_amount_skips_that_row_with_a_reason_and_nothing_else_is_affected(): void
    {
        $this->upload($this->term1, self::AMOUNT_HEADER
            . "Chris Daniels,Henry,chris@example.com,,lots\n"
            . "Pat Jones,Sam,pat@example.com,,0\n"
            . "Lee Wong,Amy,lee@example.com,,-50\n"
            . "Max Wide,Kid,max@example.com,,99999999\n"
            . "Good Parent,Kid,good@example.com,,2500\n")
            ->assertSessionHas('payer_skipped', function ($rows) {
                return count($rows) === 4
                    && $rows[0]['row'] === 2
                    && str_contains($rows[0]['reason'], 'not a valid amount');
            });

        $this->assertSame(1, Invoice::count());
        $this->assertSame(2500.0, (float) Invoice::first()->total_fee);
    }

    public function test_the_same_parents_can_pay_different_amounts_on_a_different_product(): void
    {
        $this->upload($this->term1, self::AMOUNT_HEADER . "Chris Daniels,Henry Daniels,chris@example.com,0501234567,14500\n");
        $this->upload($this->term2, self::AMOUNT_HEADER . "Chris Daniels,Henry Daniels,chris@example.com,0501234567,15200\n");

        $totals = Invoice::orderBy('id')->pluck('total_fee')->map(fn ($f) => (float) $f)->all();
        $this->assertSame([14500.0, 15200.0], $totals);
    }

    public function test_a_single_person_can_be_added_with_their_own_amount(): void
    {
        $this->actingAs($this->merchant, 'merchants')
            ->post(route('merchant.products.payers.store', $this->term1->id), [
                'name' => 'Chris Daniels', 'student_name' => 'Henry Daniels', 'email' => 'chris@example.com', 'amount' => '11250.50',
            ])->assertSessionHasNoErrors();
        $this->assertSame(11250.5, (float) Invoice::first()->total_fee);

        $this->actingAs($this->merchant, 'merchants')
            ->post(route('merchant.products.payers.store', $this->term1->id), [
                'name' => 'Pat Jones', 'student_name' => 'Sam', 'email' => 'pat@example.com', 'amount' => '-5',
            ])->assertSessionHasErrors('amount', null, 'addPayer');
        $this->assertSame(1, Invoice::count());
    }

    public function test_the_payment_link_email_shows_each_childs_own_amount(): void
    {
        $this->upload($this->term1, self::AMOUNT_HEADER
            . "Chris Daniels,Henry Daniels,chris@example.com,0501234567,14500\n"
            . "Chris Daniels,Poppy Daniels,chris@example.com,0501234567,13050\n", true);

        $invoice = Invoice::with(['consumer', 'merchant', 'invoiceDetails.product', 'invoiceDetails.consumer'])->first();
        $html = (new InvoicePaymentLinkMail($invoice))->render();

        $this->assertStringContainsString('14,500.00', $html);
        $this->assertStringContainsString('13,050.00', $html);
        $this->assertStringContainsString('27,550.00', $html);
    }

    // -- around the flow ---------------------------------------------------

    public function test_creating_a_product_takes_you_to_it_to_add_people(): void
    {
        $this->actingAs($this->merchant, 'merchants')
            ->post(route('merchant.products.store'), ['name' => 'Term 3 Fees', 'description' => 'Term 3', 'fee' => 900])
            ->assertRedirect(route('merchant.products.show', Product::where('name', 'Term 3 Fees')->first()->id));
    }

    public function test_the_old_shared_bulk_invoice_page_is_gone(): void
    {
        $this->actingAs($this->merchant, 'merchants')->get('/merchant/invoices/create-bulk')->assertNotFound();
        $this->actingAs($this->merchant, 'merchants')->post('/merchant/invoices/store-bulk')->assertNotFound();
        $this->actingAs($this->merchant, 'merchants')->post('/merchant/consumers/import')->assertStatus(405);
    }

    public function test_the_dashboard_points_at_products_instead_of_the_old_bulk_page(): void
    {
        $this->actingAs($this->merchant, 'merchants')->get(route('merchant.dashboard'))
            ->assertOk()->assertSee('Collect from a List')->assertDontSee('Bulk Links');
    }

    public function test_a_failed_payment_attempt_leaves_a_batch_invoice_payable_but_closes_a_personal_one(): void
    {
        $batch = $this->payer($this->term1, 'Parent 1', 'Kid 1', 'p1@example.com');
        $personal = Invoice::create(['merchant_id' => $this->merchant->id, 'total_fee' => 100, 'status' => InvoiceStatus::Draft, 'link_type' => 'personal']);

        foreach ([$batch, $personal] as $invoice) {
            $payment = AppUserPayment::create([
                'invoice_id' => $invoice->id, 'payment_channel' => 'web', 'status' => PaymentStatus::Initiated,
                'token' => (string) \Illuminate\Support\Str::uuid(), 'lean_metadata' => ['latest_iso_status' => 'PENDING_WITH_BANK'],
            ]);
            $payment->forceFill(['created_at' => now()->subDay()])->saveQuietly();

            app(\App\Services\PaymentService::class)->reconcileReject($payment->fresh('invoice'), ['type' => 'admin', 'id' => 1, 'name' => 'Test Admin']);
        }

        $this->assertSame(InvoiceStatus::Draft, $batch->fresh()->status);     // parent can try again from the same link
        $this->assertSame(InvoiceStatus::Failed, $personal->fresh()->status);
    }
}
