<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesApiData;
use Tests\TestCase;

class MediaPermissionsTest extends TestCase
{
    use CreatesApiData;
    use RefreshDatabase;

    public function test_restaurant_owner_can_delete_restaurant_logo_media(): void
    {
        Storage::fake('public');

        $owner = $this->createUser();
        $media = $this->createMedia();
        $restaurant = $this->createRestaurant($owner, ['logo_media_id' => $media->id]);

        Storage::disk('public')->put($media->path, 'logo');

        $response = $this
            ->actingAs($owner, 'api')
            ->deleteJson("/api/v1/media/{$media->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        $this->assertNull($restaurant->fresh()->logo_media_id);
    }

    public function test_unrelated_user_cannot_delete_restaurant_logo_media(): void
    {
        Storage::fake('public');

        $owner = $this->createUser();
        $outsider = $this->createUser();
        $media = $this->createMedia();
        $this->createRestaurant($owner, ['logo_media_id' => $media->id]);

        Storage::disk('public')->put($media->path, 'logo');

        $response = $this
            ->actingAs($outsider, 'api')
            ->deleteJson("/api/v1/media/{$media->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('media', ['id' => $media->id]);
    }
}
