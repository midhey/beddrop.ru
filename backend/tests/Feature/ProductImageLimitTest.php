<?php

namespace Tests\Feature;

use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesApiData;
use Tests\TestCase;

class ProductImageLimitTest extends TestCase
{
    use CreatesApiData;
    use RefreshDatabase;

    public function test_restaurant_product_cannot_have_more_than_five_images(): void
    {
        $owner = $this->createUser();
        $restaurant = $this->createRestaurant($owner);
        $product = $this->createProduct($restaurant);

        for ($index = 0; $index < 5; $index++) {
            ProductImage::create([
                'product_id' => $product->id,
                'media_id' => $this->createMedia(['uploaded_by_user_id' => $owner->id])->id,
                'sort_order' => $index,
                'is_cover' => $index === 0,
            ]);
        }

        $response = $this
            ->actingAs($owner, 'api')
            ->postJson("/api/v1/restaurants/{$restaurant->slug}/products/{$product->id}/images", [
                'media_id' => $this->createMedia(['uploaded_by_user_id' => $owner->id])->id,
                'sort_order' => 5,
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'У блюда может быть не больше 5 фотографий.');
    }
}
