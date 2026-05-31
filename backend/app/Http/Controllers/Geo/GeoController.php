<?php

namespace App\Http\Controllers\Geo;

use App\Http\Controllers\Controller;
use App\Services\Geo\DadataAddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class GeoController extends Controller
{
    public function suggestions(Request $request, DadataAddressService $dadata): JsonResponse
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:255'],
            'count' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        try {
            return response()->json([
                'suggestions' => $dadata->suggestions($data['q'], $data['count'] ?? 10),
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Не удалось получить подсказки адресов.',
            ], 502);
        }
    }

    public function clean(Request $request, DadataAddressService $dadata): JsonResponse
    {
        $data = $request->validate([
            'address' => ['required', 'string', 'min:2', 'max:500'],
        ]);

        try {
            return response()->json([
                'address' => $dadata->clean($data['address']),
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Не удалось нормализовать адрес.',
            ], 502);
        }
    }

    public function reverseGeocode(Request $request, DadataAddressService $dadata): JsonResponse
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'min:-90', 'max:90'],
            'lng' => ['required', 'numeric', 'min:-180', 'max:180'],
        ]);

        try {
            return response()->json([
                'address' => $dadata->reverseGeocode((float) $data['lat'], (float) $data['lng']),
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Не удалось определить адрес по координатам.',
            ], 502);
        }
    }
}
