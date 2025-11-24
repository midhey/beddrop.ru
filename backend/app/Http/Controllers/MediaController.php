<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'max:5120',
                'mimes:jpg,jpeg,png,webp'
            ],
        ]);

        $file = $validated['file'];

        $path = $file->store('media', 'public');

        $media = Media::create([
            'disk'       => 'public',
            'path'       => $path,
            'mime'       => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
        ]);

        return response()->json([
            'media' => $media,
        ], 201);
    }

    public function destroy(Media $media)
    {
        Storage::disk($media->disk ?? 'public')->delete($media->path);
        $media->delete();

        return response()->noContent();
    }
}
