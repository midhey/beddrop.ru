<?php

namespace App\Http\Controllers\Profile;

use App\Actions\Order\FindActiveOrder;
use App\Enums\CartStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ActiveOrderResource;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BootstrapController extends Controller
{
    public function __invoke(Request $request, FindActiveOrder $findActiveOrder): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => $user,
            'has_restaurants_access' => $this->hasRestaurantsAccess($user->id),
            'has_courier_access' => $this->hasCourierAccess($user->id),
            'active_order' => $this->activeOrderSummary($findActiveOrder, $user->id),
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

    private function activeOrderSummary(FindActiveOrder $findActiveOrder, int $userId): ?ActiveOrderResource
    {
        $order = $findActiveOrder($userId);

        return $order ? new ActiveOrderResource($order) : null;
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
}
