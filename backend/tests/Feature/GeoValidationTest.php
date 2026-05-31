<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesApiData;
use Tests\TestCase;

class GeoValidationTest extends TestCase
{
    use CreatesApiData;
    use RefreshDatabase;

    public function test_reverse_geocode_validates_coordinate_bounds()
    {
        $user = $this->createUser();

        // Invalid Latitude
        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/geo/reverse-geocode', [
                'lat' => 91,
                'lng' => 0,
            ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['lat']);

        // Invalid Longitude
        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/geo/reverse-geocode', [
                'lat' => 0,
                'lng' => 181,
            ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['lng']);

        // Valid Coordinates (should pass validation)
        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/geo/reverse-geocode', [
                'lat' => 45,
                'lng' => 45,
            ]);
        // Status might be 502 if Dadata is not configured, but not 422
        $this->assertNotEquals(422, $response->status());
    }
}
