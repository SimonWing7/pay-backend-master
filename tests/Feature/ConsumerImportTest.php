<?php

namespace Tests\Feature;

use App\Models\Consumer;
use App\Models\Merchant;
use App\Services\ConsumerImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsumerImportTest extends TestCase
{
    use RefreshDatabase;

    private function merchant(): Merchant
    {
        static $n = 0;
        $n++;

        return Merchant::create([
            'name' => "Import Merchant {$n}", 'email' => "import{$n}@example.com", 'password' => 'password123',
            'is_active' => true, 'must_change_password' => false,
        ]);
    }

    private function import(Merchant $merchant, string $csv): array
    {
        return app(ConsumerImportService::class)->import($csv, $merchant->id);
    }

    private const HEADER = "Parent Name,Student/Player Name,Parent Email,Parent Mobile\n";

    public function test_each_child_of_the_same_parent_becomes_their_own_record(): void
    {
        $m = $this->merchant();

        $stats = $this->import($m, self::HEADER
            . "Chris Daniels,Henry Daniels,parent@example.com,0501234567\n"
            . "Chris Daniels,Poppy Daniels,parent@example.com,0501234567\n"
            . "Chris Daniels,Oscar Daniels,parent@example.com,0501234567\n");

        $this->assertSame(3, $stats['created']);
        $this->assertSame(0, $stats['updated']);
        $this->assertEqualsCanonicalizing(
            ['Henry Daniels', 'Poppy Daniels', 'Oscar Daniels'],
            Consumer::where('merchant_id', $m->id)->pluck('student_name')->all()
        );
    }

    public function test_five_rows_sharing_one_test_email_give_five_records(): void
    {
        $m = $this->merchant();

        $csv = self::HEADER;
        foreach (['Henry', 'Poppy', 'Oscar', 'Freya', 'Alfie'] as $child) {
            $csv .= "Chris Daniels,{$child} Daniels,simon@example.com,\n";
        }

        $stats = $this->import($m, $csv);

        $this->assertSame(5, $stats['created']);
        $this->assertSame(5, Consumer::where('merchant_id', $m->id)->count());
    }

    public function test_re_uploading_the_same_file_updates_instead_of_duplicating(): void
    {
        $m = $this->merchant();
        $csv = self::HEADER
            . "Chris Daniels,Henry Daniels,parent@example.com,0501234567\n"
            . "Chris Daniels,Poppy Daniels,parent@example.com,0501234567\n";

        $this->import($m, $csv);
        $second = $this->import($m, $csv);

        $this->assertSame(0, $second['created']);
        $this->assertSame(2, $second['updated']);
        $this->assertSame(2, Consumer::where('merchant_id', $m->id)->count());
    }

    public function test_student_names_match_regardless_of_case_and_spacing(): void
    {
        $m = $this->merchant();
        $this->import($m, self::HEADER . "Chris Daniels,Henry Daniels,parent@example.com,\n");

        $stats = $this->import($m, self::HEADER . "Chris Daniels,  henry daniels ,parent@example.com,\n");

        $this->assertSame(0, $stats['created']);
        $this->assertSame(1, Consumer::where('merchant_id', $m->id)->count());
    }

    public function test_an_existing_contact_with_no_student_gets_the_student_filled_in(): void
    {
        $m = $this->merchant();
        $existing = Consumer::create(['merchant_id' => $m->id, 'name' => 'Chris Daniels', 'email' => 'parent@example.com']);

        $stats = $this->import($m, self::HEADER . "Chris Daniels,Henry Daniels,parent@example.com,\n");

        $this->assertSame(0, $stats['created']);
        $this->assertSame(1, $stats['updated']);
        $this->assertSame('Henry Daniels', $existing->fresh()->student_name);
    }

    public function test_a_row_with_no_student_still_matches_by_contact(): void
    {
        $m = $this->merchant();
        $this->import($m, self::HEADER . "Chris Daniels,Henry Daniels,parent@example.com,\n");

        $stats = $this->import($m, "Parent Name,Parent Email\nChristopher Daniels,parent@example.com\n");

        $this->assertSame(0, $stats['created']);
        $this->assertSame(1, Consumer::where('merchant_id', $m->id)->count());
        $this->assertSame('Christopher Daniels', Consumer::first()->name);
        $this->assertSame('Henry Daniels', Consumer::first()->student_name); // blank cell never wipes existing data
    }

    public function test_siblings_are_matched_by_mobile_too_when_there_is_no_email(): void
    {
        $m = $this->merchant();

        $stats = $this->import($m, self::HEADER
            . "Sam Parent,Child One,,+971 50 123 4567\n"
            . "Sam Parent,Child Two,,0501234567\n");

        $this->assertSame(2, $stats['created']);
    }

    public function test_the_same_contact_for_a_different_merchant_is_a_separate_record(): void
    {
        $a = $this->merchant();
        $b = $this->merchant();

        $this->import($a, self::HEADER . "Chris Daniels,Henry Daniels,parent@example.com,\n");
        $stats = $this->import($b, self::HEADER . "Chris Daniels,Henry Daniels,parent@example.com,\n");

        $this->assertSame(1, $stats['created']);
        $this->assertSame(1, Consumer::where('merchant_id', $a->id)->count());
        $this->assertSame(1, Consumer::where('merchant_id', $b->id)->count());
    }

    public function test_rows_without_a_name_or_any_contact_are_skipped(): void
    {
        $m = $this->merchant();

        $stats = $this->import($m, self::HEADER
            . ",Henry Daniels,parent@example.com,\n"
            . "Chris Daniels,Henry Daniels,,\n");

        $this->assertSame(2, $stats['skipped']);
        $this->assertSame(0, Consumer::where('merchant_id', $m->id)->count());
    }

    public function test_the_database_still_refuses_the_same_child_twice_for_one_parent(): void
    {
        $m = $this->merchant();
        Consumer::create(['merchant_id' => $m->id, 'name' => 'Chris', 'student_name' => 'Henry', 'email' => 'p@example.com', 'mobile_number' => '0501234567']);

        // A sibling with the same contact details is fine...
        Consumer::create(['merchant_id' => $m->id, 'name' => 'Chris', 'student_name' => 'Poppy', 'email' => 'p@example.com', 'mobile_number' => '0501234567']);
        $this->assertSame(2, Consumer::where('merchant_id', $m->id)->count());

        // ...the very same child again is not.
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        Consumer::create(['merchant_id' => $m->id, 'name' => 'Chris', 'student_name' => 'Henry', 'email' => 'p@example.com', 'mobile_number' => '0509999999']);
    }
}
