<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Covers the AutoCount nightly-sync settings: the Setting key/value helper and
 * the admin Settings page (GET /settings/autocount, POST to save).
 *
 * Uses DatabaseTransactions so the configured dev DB is rolled back, never
 * rebuilt.
 */
class AutoCountSyncSettingsTest extends TestCase
{
    use DatabaseTransactions;

    private function actingAsInvoiceUser(): User
    {
        $permission = Permission::firstOrCreate([
            'name' => 'invoice',
            'guard_name' => 'web',
        ]);

        $user = User::create([
            'name' => 'Settings Tester',
            'email' => 'settings-tester-' . uniqid() . '@example.test',
            'password' => bcrypt('secret'),
        ]);

        $user->givePermissionTo($permission);
        $this->actingAs($user, 'web');

        return $user;
    }

    /** @test */
    public function setting_get_returns_default_when_missing()
    {
        $this->assertSame('fallback', Setting::get('some_missing_key_' . uniqid(), 'fallback'));
    }

    /** @test */
    public function setting_set_persists_and_is_read_back()
    {
        Setting::set(Setting::AUTOCOUNT_NIGHTLY_SYNC_TIME, '22:30');
        Setting::set(Setting::AUTOCOUNT_NIGHTLY_SYNC_ENABLED, '1');

        $this->assertSame('22:30', Setting::nightlySyncTime());
        $this->assertTrue(Setting::nightlySyncEnabled());
    }

    /** @test */
    public function nightly_sync_time_falls_back_when_value_is_malformed()
    {
        Setting::set(Setting::AUTOCOUNT_NIGHTLY_SYNC_TIME, 'not-a-time');

        $this->assertSame(Setting::DEFAULT_NIGHTLY_SYNC_TIME, Setting::nightlySyncTime());
    }

    /** @test */
    public function settings_page_renders()
    {
        $this->actingAsInvoiceUser();

        $this->get('/settings/autocount')->assertOk();
    }

    /** @test */
    public function settings_can_be_saved_through_the_page()
    {
        $this->actingAsInvoiceUser();

        $response = $this->post('/settings/autocount', [
            'nightly_sync_enabled' => 1,
            'nightly_sync_time' => '21:15',
        ]);

        $response->assertRedirect(route('settings.autocount'));

        $this->assertTrue(Setting::nightlySyncEnabled());
        $this->assertSame('21:15', Setting::nightlySyncTime());
    }

    /** @test */
    public function unchecked_enable_disables_nightly_sync()
    {
        $this->actingAsInvoiceUser();
        Setting::set(Setting::AUTOCOUNT_NIGHTLY_SYNC_ENABLED, '1');

        // Checkbox omitted entirely when unticked.
        $this->post('/settings/autocount', [
            'nightly_sync_time' => '20:00',
        ])->assertRedirect(route('settings.autocount'));

        $this->assertFalse(Setting::nightlySyncEnabled());
    }

    /** @test */
    public function invalid_time_is_rejected()
    {
        $this->actingAsInvoiceUser();

        $this->post('/settings/autocount', [
            'nightly_sync_time' => '99:99',
        ])->assertSessionHasErrors('nightly_sync_time');
    }
}
