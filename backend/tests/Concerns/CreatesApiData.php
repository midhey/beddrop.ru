<?php

namespace Tests\Concerns;

use App\Enums\CartStatus;
use App\Enums\CourierProfileStatus;
use App\Enums\CourierVehicle;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RestaurantStaffRole;
use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CourierProfile;
use App\Models\Media;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

trait CreatesApiData
{
    protected int $sequence = 1;

    protected function createUser(array $attributes = []): User
    {
        $sequence = $this->nextSequence();

        return User::create(array_merge([
            'email' => "user{$sequence}@example.com",
            'phone' => sprintf('7999000%04d', $sequence),
            'password' => Hash::make('password'),
            'name' => "User {$sequence}",
            'is_admin' => false,
            'is_banned' => false,
        ], $attributes));
    }

    protected function createAddress(?User $user = null, array $attributes = []): Address
    {
        $sequence = $this->nextSequence();

        return Address::create(array_merge([
            'user_id' => $user?->id,
            'label' => "Address {$sequence}",
            'line1' => "Street {$sequence}",
            'line2' => null,
            'city' => 'Moscow',
            'postal_code' => sprintf('10%04d', $sequence),
            'lat' => null,
            'lng' => null,
        ], $attributes));
    }

    protected function createMedia(array $attributes = []): Media
    {
        $sequence = $this->nextSequence();

        return Media::create(array_merge([
            'disk' => 'public',
            'path' => "media/test-{$sequence}.png",
            'mime' => 'image/png',
            'size_bytes' => 128,
        ], $attributes));
    }

    protected function createRestaurant(?User $owner = null, array $attributes = []): Restaurant
    {
        $sequence = $this->nextSequence();
        $address = $attributes['address'] ?? $this->createAddress($owner);

        unset($attributes['address']);

        $restaurant = Restaurant::create(array_merge([
            'name' => "Restaurant {$sequence}",
            'slug' => "restaurant-{$sequence}",
            'address_id' => $address->id,
            'phone' => "+7999111{$sequence}",
            'is_active' => true,
            'prep_time_min' => 15,
            'prep_time_max' => 30,
            'logo_media_id' => null,
        ], $attributes));

        if ($owner !== null) {
            $restaurant->users()->attach($owner->id, ['role' => RestaurantStaffRole::OWNER->value]);
        }

        return $restaurant->fresh();
    }

    protected function attachRestaurantUser(Restaurant $restaurant, User $user, RestaurantStaffRole|string $role): void
    {
        $restaurant->users()->attach($user->id, [
            'role' => $role instanceof RestaurantStaffRole ? $role->value : $role,
        ]);
    }

    protected function createProductCategory(array $attributes = []): ProductCategory
    {
        $sequence = $this->nextSequence();

        return ProductCategory::create(array_merge([
            'slug' => "category-{$sequence}",
            'name' => "Category {$sequence}",
            'sort_order' => $sequence,
        ], $attributes));
    }

    protected function createProduct(
        Restaurant $restaurant,
        ?ProductCategory $category = null,
        array $attributes = []
    ): Product {
        $sequence = $this->nextSequence();
        $category ??= $this->createProductCategory();

        return Product::create(array_merge([
            'restaurant_id' => $restaurant->id,
            'category_id' => $category->id,
            'name' => "Product {$sequence}",
            'description' => "Product {$sequence} description",
            'price' => 499.00,
            'is_active' => true,
        ], $attributes));
    }

    protected function createActiveCart(User $user, Restaurant $restaurant): Cart
    {
        return Cart::create([
            'user_id' => $user->id,
            'restaurant_id' => $restaurant->id,
            'status' => CartStatus::ACTIVE->value,
            'is_active' => true,
        ]);
    }

    protected function addCartItem(Cart $cart, Product $product, int $quantity = 1): CartItem
    {
        return CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price_snapshot' => $product->price,
        ]);
    }

    protected function createCourierProfile(User $user, array $attributes = []): CourierProfile
    {
        return CourierProfile::create(array_merge([
            'user_id' => $user->id,
            'status' => CourierProfileStatus::ACTIVE->value,
            'vehicle' => CourierVehicle::BIKE->value,
            'rating' => 5.0,
        ], $attributes));
    }

    protected function createAcceptedOrder(
        User $customer,
        Restaurant $restaurant,
        ?Product $product = null,
        array $attributes = []
    ): Order {
        $product ??= $this->createProduct($restaurant);
        $deliveryAddress = $attributes['delivery_address_id'] ?? $this->createAddress($customer)->id;

        $order = Order::create(array_merge([
            'user_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'courier_id' => null,
            'status' => OrderStatus::ACCEPTED_BY_RESTAURANT->value,
            'payment_status' => PaymentStatus::PENDING->value,
            'payment_method' => PaymentMethod::CASH->value,
            'total_price' => 499.00,
            'courier_fee' => 0,
            'comment' => null,
            'delivery_address_id' => $deliveryAddress,
            'delivery_lat' => null,
            'delivery_lng' => null,
        ], $attributes));

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'name_snapshot' => $product->name,
            'unit_price_snapshot' => $product->price,
            'quantity' => 1,
        ]);

        return $order->fresh();
    }

    protected function nextSequence(): int
    {
        return $this->sequence++;
    }
}
