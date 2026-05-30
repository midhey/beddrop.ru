<?php

namespace App\Services\Media;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class UploadedImageOptimizer
{
    private const WEBP_QUALITY = 82;

    /**
     * @return array{disk: string, path: string, mime: string, size_bytes: int}
     */
    public function storeAsWebp(UploadedFile $file, string $disk = 'public', string $directory = 'media'): array
    {
        $image = $this->createImage($file);

        try {
            $this->normalizeCanvas($image);
            $this->applyJpegOrientation($image, $file);

            $contents = $this->encodeWebp($image);
        } finally {
            imagedestroy($image);
        }

        $path = trim($directory, '/').'/'.Str::uuid().'.webp';

        Storage::disk($disk)->put($path, $contents);

        return [
            'disk' => $disk,
            'path' => $path,
            'mime' => 'image/webp',
            'size_bytes' => strlen($contents),
        ];
    }

    private function createImage(UploadedFile $file): GdImage
    {
        $contents = file_get_contents($file->getRealPath());

        if ($contents === false) {
            throw new RuntimeException('Unable to read uploaded image.');
        }

        $image = @imagecreatefromstring($contents);

        if (! $image instanceof GdImage) {
            throw new RuntimeException('Unable to decode uploaded image.');
        }

        return $image;
    }

    private function normalizeCanvas(GdImage $image): void
    {
        if (! imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);
    }

    private function applyJpegOrientation(GdImage &$image, UploadedFile $file): void
    {
        if (! in_array($file->getMimeType(), ['image/jpeg', 'image/jpg'], true) || ! function_exists('exif_read_data')) {
            return;
        }

        $exif = @exif_read_data($file->getRealPath());
        $orientation = (int) ($exif['Orientation'] ?? 1);

        $degrees = match ($orientation) {
            3 => 180,
            6 => 270,
            8 => 90,
            default => null,
        };

        if ($degrees === null) {
            return;
        }

        $rotated = imagerotate($image, $degrees, 0);

        if (! $rotated instanceof GdImage) {
            return;
        }

        imagedestroy($image);
        $image = $rotated;
        $this->normalizeCanvas($image);
    }

    private function encodeWebp(GdImage $image): string
    {
        if (! function_exists('imagewebp')) {
            throw new RuntimeException('WebP encoding is not available.');
        }

        ob_start();
        $encoded = imagewebp($image, null, self::WEBP_QUALITY);
        $contents = ob_get_clean();

        if (! $encoded || $contents === false || $contents === '') {
            throw new RuntimeException('Unable to encode image as WebP.');
        }

        return $contents;
    }
}
