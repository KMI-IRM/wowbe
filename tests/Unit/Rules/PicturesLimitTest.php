<?php

namespace Tests\Unit\Rules;

use App\Models\Site;
use App\Models\User;
use App\Rules\PicturesLimit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Tests\TestCase;

class PicturesLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_allows_upload_when_under_limit(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);

        $rule = new PicturesLimit($site, 'pictures');
        $fails = false;

        $rule->validate('pictures', 'some_file', function () use (&$fails) {
            $fails = true;
        });

        $this->assertFalse($fails, 'Should allow upload when under limit');
    }

    public function test_prevents_upload_when_at_limit(): void
    {
        $user = User::factory()->create();

        Site::factory()->create(['user_id' => $user->id]);

        // Use a mock to simulate a site with 4 existing pictures
        $siteMock = $this->createPartialMock(Site::class, ['getMedia']);

        // Create a mock media collection with 4 items using the proper MediaCollection class
        $mediaCollection = $this->createMock(MediaCollection::class);
        $mediaCollection->method('count')->willReturn(4);

        $siteMock->method('getMedia')->willReturn($mediaCollection);

        $rule = new PicturesLimit($siteMock, 'pictures');
        $fails = false;
        $failMessage = '';

        $rule->validate('pictures', 'some_file', function ($message) use (&$fails, &$failMessage) {
            $fails = true;
            $failMessage = $message;
        });

        $this->assertTrue($fails, 'Should prevent upload when at limit');
        $this->assertEquals('You can only upload a maximum of 4 pictures.', $failMessage);
    }
}
