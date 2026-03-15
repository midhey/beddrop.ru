<?php

namespace Tests\Feature;

use App\Enums\RestaurantStaffRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesApiData;
use Tests\TestCase;

class RestaurantSettingsUpdateTest extends TestCase
{
    use CreatesApiData;
    use RefreshDatabase;

    public function test_manager_can_update_restaurant_settings_with_nested_address(): void
    {
        $owner = $this->createUser();
        $manager = $this->createUser();
        $logo = $this->createMedia();
        $restaurant = $this->createRestaurant($owner, [
            'description' => 'Старое описание',
        ]);

        $this->attachRestaurantUser($restaurant, $manager, RestaurantStaffRole::MANAGER);

        $response = $this
            ->actingAs($manager, 'api')
            ->putJson("/api/v1/restaurants/{$restaurant->id}", [
                'name' => 'Новое название ресторана',
                'description' => 'Новое длинное описание ресторана',
                'phone' => '+79990001122',
                'is_active' => false,
                'prep_time_min' => 20,
                'prep_time_max' => 35,
                'logo_media_id' => $logo->id,
                'address' => [
                    'label' => 'Ресторан',
                    'line1' => 'Новый адрес, 10',
                    'line2' => 'Вход со двора',
                    'city' => 'Санкт-Петербург',
                    'postal_code' => '190000',
                    'lat' => 59.9343,
                    'lng' => 30.3351,
                ],
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('restaurant.name', 'Новое название ресторана')
            ->assertJsonPath('restaurant.description', 'Новое длинное описание ресторана')
            ->assertJsonPath('restaurant.phone', '+79990001122')
            ->assertJsonPath('restaurant.is_active', false)
            ->assertJsonPath('restaurant.prep_time_min', 20)
            ->assertJsonPath('restaurant.prep_time_max', 35)
            ->assertJsonPath('restaurant.logo_media_id', $logo->id)
            ->assertJsonPath('restaurant.address.label', 'Ресторан')
            ->assertJsonPath('restaurant.address.line1', 'Новый адрес, 10')
            ->assertJsonPath('restaurant.address.city', 'Санкт-Петербург');

        $restaurant->refresh();
        $this->assertSame('Новое название ресторана', $restaurant->name);
        $this->assertSame('Новое длинное описание ресторана', $restaurant->description);
        $this->assertSame($logo->id, $restaurant->logo_media_id);
        $this->assertSame('Новый адрес, 10', $restaurant->address?->line1);
        $this->assertSame('Ресторан', $restaurant->address?->label);
    }
}
