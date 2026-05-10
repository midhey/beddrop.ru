<?php

namespace App\Http\Controllers\Profile;

use App\Enums\CartStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BootstrapController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => $user,
            'has_restaurants_access' => $this->hasRestaurantsAccess($user->id),
            'has_courier_access' => $this->hasCourierAccess($user->id),
            'active_order' => $this->activeOrderSummary($user->id),
            'cart_summary' => $this->cartSummary($user->id),
        ]);
    }

    private function hasRestaurantsAccess(int $userId): bool
    {
        return DB::table('restaurant_user')
            ->where('user_id', $userId)
            ->exists();
    }

    private function hasCourierAccess(int $userId): bool
    {
        return DB::table('courier_profiles')
            ->where('user_id', $userId)
            ->where('status', '!=', 'SUSPENDED')
            ->exists();
    }

    private function activeOrderSummary(int $userId): ?array
    {
        $order = Order::query()
            ->where('user_id', $userId)
            ->whereNotIn('status', $this->finalOrderStatuses())
            ->with(['restaurant:id,name,slug'])
            ->withSum('items as items_count', 'quantity')
            ->orderByDesc('created_at')
            ->first();

        return $order ? $this->serializeActiveOrder($order) : null;
    }

    private function cartSummary(int $userId): ?array
    {
        $cart = Cart::query()
            ->where('user_id', $userId)
            ->where('status', CartStatus::ACTIVE->value)
            ->where('is_active', true)
            ->with(['restaurant:id,name,slug'])
            ->withSum('items as items_count', 'quantity')
            ->first();

        if (! $cart) {
            return null;
        }

        $totalPrice = CartItem::query()
            ->where('cart_id', $cart->id)
            ->selectRaw('COALESCE(SUM(quantity * unit_price_snapshot), 0) as total')
            ->value('total');

        return [
            'id' => $cart->id,
            'user_id' => $cart->user_id,
            'restaurant_id' => $cart->restaurant_id,
            'status' => $cart->status,
            'is_active' => (bool) $cart->is_active,
            'is_summary' => true,
            'restaurant' => $cart->restaurant ? [
                'id' => $cart->restaurant->id,
                'name' => $cart->restaurant->name,
                'slug' => $cart->restaurant->slug,
            ] : null,
            'items' => [],
            'items_count' => (int) ($cart->items_count ?? 0),
            'total_price' => (float) $totalPrice,
            'created_at' => $cart->created_at,
            'updated_at' => $cart->updated_at,
        ];
    }

    private function serializeActiveOrder(Order $order): array
    {
        return [
            'id' => $order->id,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'payment_method' => $order->payment_method,
            'total_price' => $order->total_price,
            'courier_fee' => $order->courier_fee,
            'restaurant' => $order->restaurant ? [
                'id' => $order->restaurant->id,
                'name' => $order->restaurant->name,
                'slug' => $order->restaurant->slug,
            ] : null,
            'items_count' => (int) ($order->items_count ?? 0),
            'created_at' => $order->created_at,
            'updated_at' => $order->updated_at,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function finalOrderStatuses(): array
    {
        return [
            OrderStatus::DELIVERED->value,
            OrderStatus::CANCELED_BY_USER->value,
            OrderStatus::CANCELED_BY_RESTAURANT->value,
        ];
    }
}
