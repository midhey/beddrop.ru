<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderRouteSegmentResource;
use App\Models\Address;
use App\Models\Order;
use App\Services\Geo\DadataAddressService;
use App\Services\Logistics\LogisticsSettingsService;
use App\Services\Logistics\ValhallaRouteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class LogisticsDebugController extends Controller
{
    public function testAddress(Request $request, DadataAddressService $dadata): JsonResponse
    {
        $this->ensureAdmin();

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
                'message' => 'Не удалось проверить адрес через DaData.',
            ], 502);
        }
    }

    public function testRoute(
        Request $request,
        ValhallaRouteService $routes,
        LogisticsSettingsService $settings,
    ): JsonResponse {
        $this->ensureAdmin();

        $data = $request->validate([
            'from_address_id' => ['nullable', 'integer', 'exists:addresses,id'],
            'to_address_id' => ['nullable', 'integer', 'exists:addresses,id'],
            'from.lat' => ['required_without:from_address_id', 'numeric'],
            'from.lng' => ['required_without:from_address_id', 'numeric'],
            'to.lat' => ['required_without:to_address_id', 'numeric'],
            'to.lng' => ['required_without:to_address_id', 'numeric'],
            'mode' => ['nullable', 'string', 'in:auto,bicycle,pedestrian'],
        ]);

        try {
            $from = $this->resolvePoint(
                $data['from_address_id'] ?? null,
                $data['from'] ?? null,
                'Не указана стартовая точка маршрута.',
            );
            $to = $this->resolvePoint(
                $data['to_address_id'] ?? null,
                $data['to'] ?? null,
                'Не указана конечная точка маршрута.',
            );

            $route = $routes->route($from, $to, $data['mode'] ?? 'auto');
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Не удалось проверить маршрут через Valhalla.',
            ], 502);
        }

        return response()->json([
            'route' => [
                'mode' => $data['mode'] ?? 'auto',
                'distance_meters' => $route['distance_meters'],
                'duration_seconds' => $route['duration_seconds'],
                'encoded_shape' => $route['encoded_shape'],
                'request' => $route['request'],
                'raw_response' => $route['raw'],
                'settings_snapshot' => $settings->snapshot(),
            ],
        ]);
    }

    public function orderRoutes(Order $order): JsonResponse
    {
        $this->ensureAdmin();

        $order->load('routeSegments');

        return response()->json([
            'order_id' => $order->id,
            'route_segments' => OrderRouteSegmentResource::collection($order->routeSegments)->resolve(),
        ]);
    }

    /**
     * @param array{lat: mixed, lng: mixed}|null $point
     * @return array{lat: float, lng: float}
     */
    private function resolvePoint(?int $addressId, ?array $point, string $fallbackMessage): array
    {
        if ($addressId) {
            $address = Address::findOrFail($addressId);

            if ($address->lat === null || $address->lng === null) {
                throw new RuntimeException('У выбранного адреса нет координат.');
            }

            return [
                'lat' => $address->lat,
                'lng' => $address->lng,
            ];
        }

        if (!$point || !isset($point['lat'], $point['lng'])) {
            throw new RuntimeException($fallbackMessage);
        }

        return [
            'lat' => (float) $point['lat'],
            'lng' => (float) $point['lng'],
        ];
    }

    private function ensureAdmin(): void
    {
        if (!request()->user()?->is_admin) {
            abort(403, 'Доступно только администратору.');
        }
    }
}
