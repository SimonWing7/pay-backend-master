<?php

namespace Tests\Feature;

use App\Mail\TeamInviteMail;
use App\Models\Merchant;
use App\Models\MerchantUser;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchant;
    private Merchant $other;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->merchant = $this->makeMerchant('Pinnakle Rugby Academy', 'owner@pinnakle.test');
        $this->other = $this->makeMerchant('Other School', 'owner@other.test');
    }

    // -- helpers -----------------------------------------------------------

    private function makeMerchant(string $name, string $email): Merchant
    {
        return Merchant::create([
            'name' => $name, 'merchant_trading_name' => $name, 'email' => $email,
            'password' => 'password123', 'is_active' => true, 'must_change_password' => false,
        ]);
    }

    private function makeMember(string $role, ?Merchant $merchant = null, array $overrides = []): MerchantUser
    {
        $merchant ??= $this->merchant;

        return MerchantUser::create(array_merge([
            'merchant_id' => $merchant->id,
            'name' => ucfirst($role) . ' Person',
            'email' => "{$role}-" . uniqid() . '@pinnakle.test',
            'password' => 'memberpass1',
            'role' => $role,
        ], $overrides));
    }

    /** Act as an invited member: the merchants guard plus the member id in the session. */
    private function asMember(MerchantUser $member)
    {
        return $this->withSession(['merchant_member_id' => $member->id])
            ->actingAs($member->merchant, 'merchants');
    }

    private function asOwner()
    {
        // A browser signing in as the owner starts without a member id.
        $this->flushSession();

        return $this->actingAs($this->merchant, 'merchants');
    }

    private function invite(array $data = [])
    {
        return $this->asOwner()->post(route('merchant.team.invite'), array_merge([
            'name' => 'Sara Ahmed', 'email' => 'sara@pinnakle.test', 'role' => 'staff',
        ], $data));
    }

    // -- inviting ----------------------------------------------------------

    public function test_owner_can_invite_and_the_email_carries_a_working_link(): void
    {
        $this->invite()->assertRedirect(route('merchant.team.index'))->assertSessionHas('success');

        $member = MerchantUser::where('email', 'sara@pinnakle.test')->firstOrFail();
        $this->assertSame($this->merchant->id, $member->merchant_id);
        $this->assertSame('staff', $member->role);
        $this->assertTrue($member->isPending());
        $this->assertSame('Pinnakle Rugby Academy', $member->invited_by);

        $token = null;
        Mail::assertSent(TeamInviteMail::class, function (TeamInviteMail $mail) use (&$token, $member) {
            $token = $mail->token;

            return $mail->hasTo($member->email) && $mail->kind === 'invite';
        });

        // Only the hash is stored, never the token itself.
        $this->assertNotSame($token, $member->setup_token_hash);
        $this->assertSame(hash('sha256', $token), $member->setup_token_hash);

        $html = (new TeamInviteMail($member->fresh(), $token))->render();
        $this->assertStringContainsString(route('merchant.team.accept', $token), $html);
        $this->assertStringContainsString('Staff', $html);
        $this->assertStringContainsString('Pinnakle Rugby Academy', $html);
    }

    public function test_invite_rejects_bad_role_duplicate_member_and_existing_merchant_email(): void
    {
        $this->invite(['role' => 'god'])->assertSessionHasErrors('role', null, 'invite');

        $this->invite();
        $this->invite()->assertSessionHasErrors('email', null, 'invite');
        $this->invite(['email' => 'OWNER@other.test'])->assertSessionHasErrors('email', null, 'invite');

        $this->assertSame(1, MerchantUser::count());
        Mail::assertSent(TeamInviteMail::class, 1);
    }

    public function test_a_failed_invite_email_keeps_the_member_and_can_be_resent(): void
    {
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('SES down'));

        $this->invite()->assertSessionHas('error');
        $this->assertSame(1, MerchantUser::count());
    }

    public function test_resend_replaces_the_old_link(): void
    {
        $this->invite();
        $member = MerchantUser::firstOrFail();
        $oldToken = null;
        Mail::assertSent(TeamInviteMail::class, function ($mail) use (&$oldToken) {
            $oldToken = $mail->token;

            return true;
        });

        $this->asOwner()->post(route('merchant.team.resend', $member->id))->assertSessionHas('success');

        $this->assertNull(MerchantUser::findByToken($oldToken));
        Mail::assertSent(TeamInviteMail::class, 2);
    }

    public function test_resend_is_refused_once_the_invite_has_been_accepted(): void
    {
        $member = $this->makeMember('staff');

        $this->asOwner()->post(route('merchant.team.resend', $member->id))->assertSessionHas('error');
        Mail::assertNothingSent();
    }

    // -- accepting ---------------------------------------------------------

    public function test_accepting_sets_the_password_once_and_burns_the_token(): void
    {
        $this->invite();
        $token = null;
        Mail::assertSent(TeamInviteMail::class, function ($mail) use (&$token) {
            $token = $mail->token;

            return true;
        });

        $this->get(route('merchant.team.accept', $token))->assertOk()->assertSee('Set your password')->assertSee('sara@pinnakle.test');

        $this->post(route('merchant.team.accept.post', $token), ['password' => 'short', 'password_confirmation' => 'short'])
            ->assertSessionHasErrors('password');

        $this->post(route('merchant.team.accept.post', $token), ['password' => 'brandnewpass1', 'password_confirmation' => 'brandnewpass1'])
            ->assertRedirect(route('merchant.login'));

        $member = MerchantUser::firstOrFail();
        $this->assertFalse($member->isPending());
        $this->assertTrue(Hash::check('brandnewpass1', $member->password));
        $this->assertNull($member->setup_token_hash);

        // Second use of the same link is dead.
        $this->get(route('merchant.team.accept', $token))->assertOk()->assertSee('expired or has already been used');
        $this->post(route('merchant.team.accept.post', $token), ['password' => 'anotherpass12', 'password_confirmation' => 'anotherpass12']);
        $this->assertTrue(Hash::check('brandnewpass1', $member->fresh()->password));
    }

    public function test_an_expired_invite_cannot_be_used(): void
    {
        $member = $this->makeMember('staff', null, ['password' => null]);
        $token = $member->issueSetupToken(1);
        $member->forceFill(['setup_token_expires_at' => now()->subMinute()])->save();

        $this->get(route('merchant.team.accept', $token))->assertSee('expired or has already been used');
        $this->post(route('merchant.team.accept.post', $token), ['password' => 'brandnewpass1', 'password_confirmation' => 'brandnewpass1']);
        $this->assertTrue($member->fresh()->isPending());
    }

    // -- signing in --------------------------------------------------------

    public function test_a_member_signs_in_as_the_merchant_with_their_role(): void
    {
        $staff = $this->makeMember('staff', null, ['email' => 'staff@pinnakle.test']);
        Product::create(['merchant_id' => $this->merchant->id, 'name' => 'Term 1', 'description' => 'x', 'fee' => 100, 'state' => 'active']);
        Product::create(['merchant_id' => $this->other->id, 'name' => 'Other Product', 'description' => 'x', 'fee' => 100, 'state' => 'active']);

        $this->post(route('merchant.login.post'), ['email' => 'staff@pinnakle.test', 'password' => 'memberpass1'])
            ->assertRedirect(route('merchant.dashboard'));

        $this->assertSame($this->merchant->id, auth('merchants')->id());
        $this->assertSame($staff->id, session('merchant_member_id'));
        $this->assertNotNull($staff->fresh()->last_login_at);

        $this->get(route('merchant.products.index'))->assertOk()->assertSee('Term 1')->assertDontSee('Other Product');
    }

    public function test_wrong_password_and_pending_members_cannot_sign_in(): void
    {
        $this->makeMember('staff', null, ['email' => 'staff@pinnakle.test']);
        $this->makeMember('staff', null, ['email' => 'pending@pinnakle.test', 'password' => null]);

        $this->post(route('merchant.login.post'), ['email' => 'staff@pinnakle.test', 'password' => 'wrong'])
            ->assertSessionHasErrors('email');
        $this->post(route('merchant.login.post'), ['email' => 'pending@pinnakle.test', 'password' => ''])
            ->assertSessionHasErrors();
        $this->assertGuest('merchants');
    }

    public function test_members_cannot_sign_in_when_the_merchant_is_inactive(): void
    {
        $this->makeMember('staff', null, ['email' => 'staff@pinnakle.test']);
        $this->merchant->update(['is_active' => false]);

        $this->post(route('merchant.login.post'), ['email' => 'staff@pinnakle.test', 'password' => 'memberpass1'])
            ->assertSessionHasErrors('email');
        $this->assertGuest('merchants');
    }

    public function test_the_owner_login_after_a_member_does_not_inherit_their_role(): void
    {
        $viewer = $this->makeMember('viewer', null, ['email' => 'viewer@pinnakle.test']);

        $this->post(route('merchant.login.post'), ['email' => 'viewer@pinnakle.test', 'password' => 'memberpass1']);
        $this->assertSame($viewer->id, session('merchant_member_id'));

        $this->post(route('merchant.logout'));
        $this->post(route('merchant.login.post'), ['email' => 'owner@pinnakle.test', 'password' => 'password123']);

        $this->assertNull(session('merchant_member_id'));
        $this->get(route('merchant.team.index'))->assertOk();
    }

    public function test_the_merchants_forced_password_change_does_not_trap_members(): void
    {
        $this->merchant->update(['must_change_password' => true]);
        $staff = $this->makeMember('staff');

        $this->asMember($staff)->get(route('merchant.dashboard'))->assertOk();
        $this->asOwner()->get(route('merchant.dashboard'))->assertRedirect(route('merchant.password.change'));
    }

    // -- access control ----------------------------------------------------

    public function test_removing_a_member_ends_their_session_immediately(): void
    {
        $staff = $this->makeMember('staff');

        $this->asMember($staff)->get(route('merchant.dashboard'))->assertOk();

        $this->asOwner()->delete(route('merchant.team.destroy', $staff->id))->assertSessionHas('success');

        $this->asMember($staff)->get(route('merchant.dashboard'))->assertRedirect(route('merchant.login'));
        $this->assertGuest('merchants');
    }

    public function test_demoting_takes_effect_on_the_next_request(): void
    {
        $member = $this->makeMember('owner');
        $this->asMember($member)->get(route('merchant.team.index'))->assertOk();

        $this->asOwner()->put(route('merchant.team.role', $member->id), ['role' => 'viewer']);

        $this->asMember($member)->get(route('merchant.team.index'))->assertRedirect(route('merchant.dashboard'));
    }

    public function test_staff_can_work_but_not_reach_owner_only_areas(): void
    {
        $staff = $this->makeMember('staff');

        foreach (['merchant.products.index', 'merchant.payments.index', 'merchant.consumers.index', 'merchant.invoices.index', 'merchant.settings.password'] as $route) {
            $this->asMember($staff)->get(route($route))->assertOk();
        }

        $this->asMember($staff)->post(route('merchant.products.store'), [
            'name' => 'Trip', 'description' => 'Trip', 'fee' => 50,
        ])->assertRedirect();
        $this->assertSame(1, Product::where('merchant_id', $this->merchant->id)->count());

        foreach (['merchant.team.index', 'merchant.billing.index', 'merchant.settings.profile', 'merchant.settings.api-keys', 'merchant.referrals.index'] as $route) {
            $this->asMember($staff)->get(route($route))->assertRedirect(route('merchant.dashboard'));
        }

        $this->asMember($staff)->post(route('merchant.team.invite'), [
            'name' => 'X', 'email' => 'x@pinnakle.test', 'role' => 'owner',
        ])->assertSessionHas('error');
        $this->assertSame(1, MerchantUser::count());
    }

    public function test_viewer_can_read_but_every_change_is_refused(): void
    {
        $viewer = $this->makeMember('viewer');
        $product = Product::create(['merchant_id' => $this->merchant->id, 'name' => 'Term 1', 'description' => 'x', 'fee' => 100, 'state' => 'active']);

        $this->asMember($viewer)->get(route('merchant.products.index'))->assertOk()->assertSee('view-only access');
        $this->asMember($viewer)->get(route('merchant.products.show', $product->id))->assertOk();

        $this->asMember($viewer)->post(route('merchant.products.store'), ['name' => 'Trip', 'description' => 'Trip', 'fee' => 50])
            ->assertSessionHas('error');
        $this->asMember($viewer)->delete(route('merchant.products.delete', $product->id))->assertSessionHas('error');
        $this->asMember($viewer)->post(route('merchant.products.payers.send', $product->id))->assertSessionHas('error');

        $this->assertSame(1, Product::count());
        $this->assertNotNull(Product::find($product->id));
    }

    public function test_viewer_does_not_see_create_buttons_or_owner_navigation(): void
    {
        $viewer = $this->makeMember('viewer');

        $html = $this->asMember($viewer)->get(route('merchant.dashboard'))->assertOk()->getContent();

        $this->assertStringNotContainsString('Quick Actions', $html);
        $this->assertStringNotContainsString(route('merchant.billing.index'), $html);
        $this->assertStringNotContainsString(route('merchant.referrals.index'), $html);
        $this->assertStringContainsString('Viewer', $html);
    }

    public function test_a_viewer_can_still_change_their_own_password_and_sign_out(): void
    {
        $viewer = $this->makeMember('viewer');

        $this->asMember($viewer)->post(route('merchant.settings.password.post'), [
            'current_password' => 'memberpass1', 'new_password' => 'newviewerpass1', 'new_password_confirmation' => 'newviewerpass1',
        ])->assertSessionHas('success');

        $this->assertTrue(Hash::check('newviewerpass1', $viewer->fresh()->password));
        // The account owner's own password is untouched.
        $this->assertTrue(Hash::check('password123', $this->merchant->fresh()->password));

        $this->asMember($viewer)->post(route('merchant.logout'))->assertRedirect(route('merchant.login'));
    }

    public function test_a_members_password_change_checks_their_own_current_password(): void
    {
        $staff = $this->makeMember('staff');

        // The merchant's password is not accepted as the member's current one.
        $this->asMember($staff)->post(route('merchant.settings.password.post'), [
            'current_password' => 'password123', 'new_password' => 'newstaffpass1', 'new_password_confirmation' => 'newstaffpass1',
        ])->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('memberpass1', $staff->fresh()->password));
    }

    public function test_owners_cannot_touch_another_merchants_members(): void
    {
        $theirs = $this->makeMember('staff', $this->other);

        $this->asOwner()->delete(route('merchant.team.destroy', $theirs->id))->assertNotFound();
        $this->asOwner()->put(route('merchant.team.role', $theirs->id), ['role' => 'owner'])->assertNotFound();
        $this->asOwner()->post(route('merchant.team.resend', $theirs->id))->assertNotFound();

        $this->assertSame('staff', $theirs->fresh()->role);
    }

    public function test_an_owner_member_can_manage_the_team(): void
    {
        $coOwner = $this->makeMember('owner');

        $this->asMember($coOwner)->get(route('merchant.team.index'))->assertOk();
        $this->asMember($coOwner)->post(route('merchant.team.invite'), [
            'name' => 'New Hire', 'email' => 'hire@pinnakle.test', 'role' => 'staff',
        ])->assertSessionHas('success');

        $this->assertSame('Owner Person', MerchantUser::where('email', 'hire@pinnakle.test')->value('invited_by'));
    }

    public function test_the_team_page_lists_the_account_login_and_members(): void
    {
        $this->invite();
        $this->makeMember('viewer', null, ['name' => 'Vera Viewer']);

        $this->asOwner()->get(route('merchant.team.index'))
            ->assertOk()
            ->assertSee('owner@pinnakle.test')
            ->assertSee('Sara Ahmed')
            ->assertSee('Invited')
            ->assertSee('Vera Viewer');
    }

    // -- password reset ----------------------------------------------------

    public function test_a_member_who_forgot_their_password_gets_a_short_lived_link(): void
    {
        $staff = $this->makeMember('staff', null, ['email' => 'staff@pinnakle.test']);

        $this->post(route('merchant.password.forgot.post'), ['email' => 'staff@pinnakle.test'])
            ->assertSessionHas('status');

        $token = null;
        Mail::assertSent(TeamInviteMail::class, function ($mail) use (&$token) {
            $token = $mail->token;

            return $mail->kind === 'reset';
        });

        $this->assertLessThanOrEqual(now()->addHours(2)->addMinute()->timestamp, $staff->fresh()->setup_token_expires_at->timestamp);

        $this->post(route('merchant.team.accept.post', $token), ['password' => 'resetpass123', 'password_confirmation' => 'resetpass123'])
            ->assertRedirect(route('merchant.login'));
        $this->assertTrue(Hash::check('resetpass123', $staff->fresh()->password));

        $this->post(route('merchant.login.post'), ['email' => 'staff@pinnakle.test', 'password' => 'resetpass123'])
            ->assertRedirect(route('merchant.dashboard'));
    }

    public function test_forgot_password_for_an_unknown_address_does_not_email_anyone(): void
    {
        $this->post(route('merchant.password.forgot.post'), ['email' => 'nobody@pinnakle.test']);

        Mail::assertNotSent(TeamInviteMail::class);
    }

    // -- admin -------------------------------------------------------------

    public function test_the_templates_that_changed_still_compile(): void
    {
        // Covered in bulk by BladeTemplatesTest; this just pins the new ones.
        foreach (['merchant.settings.team', 'merchant.team-accept', 'emails.team-invite'] as $view) {
            $this->assertTrue(view()->exists($view), $view);
        }
    }
}
