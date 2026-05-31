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
        $media = $this->createMedia(['uploaded_by_user_id' => $owner->id]);
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
        $media = $this->createMedia(['uploaded_by_user_id' => $owner->id]);
        $this->createRestaurant($owner, ['logo_media_id' => $media->id]);

        Storage::disk('public')->put($media->path, 'logo');

        $response = $this
            ->actingAs($outsider, 'api')
            ->deleteJson("/api/v1/media/{$media->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('media', ['id' => $media->id]);
    }

    public function test_restaurant_owner_can_attach_own_logo_media(): void
    {
        $owner = $this->createUser();
        $media = $this->createMedia(['uploaded_by_user_id' => $owner->id]);
        $restaurant = $this->createRestaurant($owner);

        $this
            ->actingAs($owner, 'api')
            ->putJson("/api/v1/restaurants/{$restaurant->id}", [
                'logo_media_id' => $media->id,
            ])
            ->assertOk()
            ->assertJsonPath('restaurant.logo_media_id', $media->id);

        $this->assertDatabaseHas('restaurants', [
            'id' => $restaurant->id,
            'logo_media_id' => $media->id,
        ]);
    }

    public function test_tenant_cannot_attach_another_tenants_logo_media(): void
    {
        $tenantAOwner = $this->createUser();
        $tenantBOwner = $this->createUser();
        $tenantAMedia = $this->createMedia(['uploaded_by_user_id' => $tenantAOwner->id]);
        $tenantBRestaurant = $this->createRestaurant($tenantBOwner);

        $this
            ->actingAs($tenantBOwner, 'api')
            ->putJson("/api/v1/restaurants/{$tenantBRestaurant->id}", [
                'logo_media_id' => $tenantAMedia->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('logo_media_id');

        $this->assertDatabaseHas('restaurants', [
            'id' => $tenantBRestaurant->id,
            'logo_media_id' => null,
        ]);
    }

    public function test_tenant_cannot_attach_another_tenants_product_media(): void
    {
        $tenantAOwner = $this->createUser();
        $tenantBOwner = $this->createUser();
        $tenantAMedia = $this->createMedia(['uploaded_by_user_id' => $tenantAOwner->id]);
        $tenantBRestaurant = $this->createRestaurant($tenantBOwner);
        $tenantBProduct = $this->createProduct($tenantBRestaurant);

        $this
            ->actingAs($tenantBOwner, 'api')
            ->postJson("/api/v1/restaurants/{$tenantBRestaurant->slug}/products/{$tenantBProduct->id}/images", [
                'media_id' => $tenantAMedia->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('media_id');

        $this->assertDatabaseMissing('product_images', [
            'product_id' => $tenantBProduct->id,
            'media_id' => $tenantAMedia->id,
        ]);
    }
}
