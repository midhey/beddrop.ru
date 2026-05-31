<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Services\Media\UploadedImageOptimizer;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MediaController extends Controller
{
    use AuthorizesRequests;

    public function store(Request $request, UploadedImageOptimizer $optimizer)
    {
        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'image',
                'max:10240',
                'mimes:jpg,jpeg,png,webp',
            ],
        ]);

        $file = $validated['file'];

        try {
            $optimized = $optimizer->storeAsWebp($file);
        } catch (\RuntimeException) {
            throw ValidationException::withMessages([
                'file' => 'Не удалось обработать изображение. Загрузите корректный JPG, PNG или WebP файл.',
            ]);
        }

        $media = Media::create($optimized + [
            'uploaded_by_user_id' => $request->user()->id,
        ]);

        return response()->json([
            'media' => $media,
        ], 201);
    }

    public function destroy(Media $media)
    {
        $this->authorize('delete', $media);

        Storage::disk($media->disk ?? 'public')->delete($media->path);
        $media->delete();

        return response()->noContent();
    }
}
