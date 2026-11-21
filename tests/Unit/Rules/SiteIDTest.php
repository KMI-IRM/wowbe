<?php

namespace Tests\Unit\Rules;

use App\Models\Site;
use App\Models\User;
use App\Rules\SiteID;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SiteIDTest extends TestCase
{
    use RefreshDatabase;

    public function test_validates_existing_site_uuid(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Site $site */
        $site = Site::factory()->create(['user_id' => $user->id]);

        $rule = new SiteID;
        $fails = false;

        $rule->validate('site_id', $site->id, function () use (&$fails) {
            $fails = true;
        });

        $this->assertFalse($fails, 'Valid site UUID should pass validation');
    }

    public function test_fails_for_non_existent_uuid(): void
    {
        $nonExistentUuid = (string) Str::uuid();

        $rule = new SiteID;
        $fails = false;
        $failMessage = '';

        $rule->validate('site_id', $nonExistentUuid, function ($message) use (&$fails, &$failMessage) {
            $fails = true;
            $failMessage = $message;
        });

        $this->assertTrue($fails, 'Non-existent UUID should fail validation');
        $this->assertStringContainsString('must be a valid site UUID', $failMessage);
    }

    public function test_validates_existing_site_short_id(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Site $site */
        $site = Site::factory()->create(['user_id' => $user->id]);

        $rule = new SiteID;
        $fails = false;

        $rule->validate('site_id', $site->short_id, function () use (&$fails) {
            $fails = true;
        });

        $this->assertFalse($fails, 'Valid site short ID should pass validation');
    }

    public function test_fails_for_non_existent_short_id(): void
    {
        $nonExistentShortId = 'NONEXISTENT';

        $rule = new SiteID;
        $fails = false;
        $failMessage = '';

        $rule->validate('site_id', $nonExistentShortId, function ($message) use (&$fails, &$failMessage) {
            $fails = true;
            $failMessage = $message;
        });

        $this->assertTrue($fails, 'Non-existent short ID should fail validation');
        $this->assertStringContainsString('must be a valid site short ID', $failMessage);
    }
}
