<?php

namespace Tests\Unit;

use App\Http\Middleware\Localization;
use App\Mail\AccountCreated;
use App\Mail\AccountDeleted;
use App\Mail\SiteCreated;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_created_email(): void
    {
        $user = User::factory()->createOne();

        foreach (Localization::LOCALES as $locale) {
            new AccountCreated($user)
                ->locale($locale)
                ->assertHasSubject(config('app.name').' - '.trans('mail.account_created.subject', [], $locale));
        }
    }

    public function test_account_deleted_email(): void
    {
        $user = User::factory()->createOne();

        foreach (Localization::LOCALES as $locale) {
            new AccountDeleted($user)
                ->locale($locale)
                ->assertHasSubject(config('app.name').' - '.trans('mail.account_deleted.subject', [], $locale));
        }
    }

    public function test_site_created_email(): void
    {
        $user = User::factory()->createOne();
        $site = Site::factory()->createOne(['user_id' => $user->id]);

        foreach (Localization::LOCALES as $locale) {
            new SiteCreated($site)
                ->locale($locale)
                ->assertHasSubject(config('app.name').' - '.trans('mail.site_created.subject', [], $locale))
                ->assertSeeInOrderInHtml([$site->name, $site->id, $site->auth_key])
                ->assertSeeInOrderInText([$site->name, $site->id, $site->auth_key]);
        }
    }
}
