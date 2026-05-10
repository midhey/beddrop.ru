<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Restaurant;
use App\Services\Logistics\DeliveryQuoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class DeliveryQuoteController extends Controller
{
    public function __invoke(Request $request, DeliveryQuoteService $quotes): JsonResponse
    {
        $data = $request->validate([
            'restaurant_id' => ['required', 'integer', 'exists:restaurants,id'],
            'delivery_address_id' => ['required', 'integer', 'exists:addresses,id'],
            'mode' => ['nullable', 'string', 'in:auto,bicycle,pedestrian'],
        ]);

        $address = Address::query()
            ->where('id', $data['delivery_address_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $restaurant = Restaurant::query()
            ->with('address')
            ->findOrFail($data['restaurant_id']);

        try {
            $quote = $quotes->quote($restaurant, $address, $data['mode'] ?? 'auto');
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Не удалось рассчитать доставку.',
            ], 502);
        }

        return response()->json([
            'quote' => [
                'restaurant_id' => $quote['restaurant_id'],
                'delivery_address_id' => $quote['delivery_address_id'],
                'mode' => $quote['mode'],
                'distance_meters' => $quote['distance_meters'],
                'duration_seconds' => $quote['duration_seconds'],
                'prep_time_minutes' => $quote['prep_time_minutes'],
                'eta_minutes' => $quote['eta_minutes'],
                'delivery_price' => $quote['delivery_price'],
                'price' => $quote['price'],
                'time' => $quote['time'],
                'route' => [
                    'distance_meters' => $quote['route']['distance_meters'],
                    'duration_seconds' => $quote['route']['duration_seconds'],
                    'encoded_shape' => $quote['route']['encoded_shape'],
                ],
            ],
        ]);
    }
}
