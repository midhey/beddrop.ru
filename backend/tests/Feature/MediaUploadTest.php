<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesApiData;
use Tests\TestCase;

class MediaUploadTest extends TestCase
{
    use CreatesApiData;
    use RefreshDatabase;

    public function test_uploaded_image_is_optimized_and_stored_as_webp(): void
    {
        Storage::fake('public');

        $user = $this->createUser();
        $file = UploadedFile::fake()->image('logo.jpg', 800, 600)->size(1024);

        $response = $this
            ->actingAs($user, 'api')
            ->postJson('/api/v1/media', [
                'file' => $file,
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('media.disk', 'public')
            ->assertJsonPath('media.mime', 'image/webp')
            ->assertJsonPath('media.uploaded_by_user_id', $user->id);

        $path = $response->json('media.path');

        $this->assertIsString($path);
        $this->assertStringStartsWith('media/', $path);
        $this->assertStringEndsWith('.webp', $path);
        Storage::disk('public')->assertExists($path);

        $contents = Storage::disk('public')->get($path);

        $this->assertStringStartsWith('RIFF', $contents);
        $this->assertSame('WEBP', substr($contents, 8, 4));
        $this->assertDatabaseHas('media', [
            'path' => $path,
            'mime' => 'image/webp',
            'size_bytes' => strlen($contents),
            'uploaded_by_user_id' => $user->id,
        ]);
    }
}
