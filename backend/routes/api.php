<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\LogoutAllController;
use App\Http\Controllers\Auth\RefreshController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\Admin\AdminCourierController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminRestaurantController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\LogisticsDebugController;
use App\Http\Controllers\Admin\LogisticsSettingsController;
use App\Http\Controllers\Delivery\DeliveryQuoteController;
use App\Http\Controllers\Geo\GeoController;
use App\Http\Controllers\Courier\CourierLocationController;
use App\Http\Controllers\Courier\CourierProfileController;
use App\Http\Controllers\Courier\CourierShiftController;
use App\Http\Controllers\Courier\CourierOrderController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\OrderActiveController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Payment\OrderPaymentController;
use App\Http\Controllers\Payment\YooKassaWebhookController;
use App\Http\Controllers\Product\ProductCategoryController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\Product\ProductImageController;
use App\Http\Controllers\Profile\AddressController;
use App\Http\Controllers\Profile\BootstrapController;
use App\Http\Controllers\Profile\PasswordController;
use App\Http\Controllers\Profile\ProfileController;
use App\Http\Controllers\Restaurant\RestaurantController;
use App\Http\Controllers\Restaurant\RestaurantOrderController;
use App\Http\Controllers\Restaurant\RestaurantStaffController;
use App\Http\Controllers\Restaurant\RestaurantStaffInviteController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', fn() => ['message' => 'pong']);

Route::prefix('/v1')->group(function () {
    Route::prefix('/auth')->group(function () {
        Route::post('register', RegisterController::class);
        Route::post('login', LoginController::class);
        Route::post('refresh', RefreshController::class);
        Route::post('logout', LogoutController::class);

        Route::middleware('auth:api')->group(function () {
            Route::post('logout-all', LogoutAllController::class);
        });
    });

    Route::middleware('auth:api')->get('/me/bootstrap', BootstrapController::class);

    Route::middleware('auth:api')->prefix('/profile')->group(function () {
        Route::get('me', [ProfileController::class, 'show']);
        Route::get('bootstrap', BootstrapController::class);
        Route::put('me', [ProfileController::class, 'update']);
        Route::put('password', PasswordController::class);
    });

    Route::prefix('/restaurants')->group(function () {
        Route::get('/', [RestaurantController::class, 'index']);

        Route::middleware('auth:api')->group(function () {
            Route::get('/my', [RestaurantController::class, 'my']);
            Route::post('/', [RestaurantController::class, 'store']);
            Route::put('/{restaurant}', [RestaurantController::class, 'update']);
            Route::delete('/{restaurant}', [RestaurantController::class, 'destroy']);
        });

        Route::prefix('/{restaurant:slug}')->group(function () {
            Route::get('/', [RestaurantController::class, 'show']);

            Route::middleware('auth:api')->prefix('/users')->group(function () {
                Route::get('/', [RestaurantStaffController::class, 'index']);
                Route::post('/', [RestaurantStaffController::class, 'store']);
                Route::put('/{user}', [RestaurantStaffController::class, 'update']);
                Route::delete('/{user}', [RestaurantStaffController::class, 'destroy']);
            });

            Route::middleware('auth:api')->prefix('/staff-invites')->group(function () {
                Route::post('/', [RestaurantStaffInviteController::class, 'store']);
            });

            Route::prefix('/products')->group(function () {
                Route::get('/', [ProductController::class, 'index']);
                Route::get('/{product}', [ProductController::class, 'show']);

                Route::middleware('auth:api')->group(function () {
                    Route::post('/', [ProductController::class, 'store']);
                    Route::put('/{product}', [ProductController::class, 'update']);
                    Route::delete('/{product}', [ProductController::class, 'destroy']);

                    Route::post('/{product}/images', [ProductImageController::class, 'store']);
                    Route::put('/{product}/images/{image}', [ProductImageController::class, 'update']);
                    Route::delete('/{product}/images/{image}', [ProductImageController::class, 'destroy']);
                });
            });

            Route::middleware('auth:api')->prefix('/orders')->group(function () {
                Route::get('/', [RestaurantOrderController::class, 'index']);
                Route::get('/{order}', [RestaurantOrderController::class, 'show']);
                Route::post('/{order}/accept', [RestaurantOrderController::class, 'accept']);
                Route::post('/{order}/ready', [RestaurantOrderController::class, 'ready']);
                Route::post('/{order}/cancel', [RestaurantOrderController::class, 'cancel']);
            });
        });
    });

    Route::prefix('/staff-invites')->group(function () {
        Route::get('/{token}', [RestaurantStaffInviteController::class, 'show']);

        Route::middleware('auth:api')->post('/{token}/accept', [RestaurantStaffInviteController::class, 'accept']);
    });

    Route::prefix('/product-categories')->group(function () {
        Route::get('/', [ProductCategoryController::class, 'index']);

        Route::middleware('auth:api')->group(function () {
            Route::post('/', [ProductCategoryController::class, 'store']);
            Route::put('/{category}', [ProductCategoryController::class, 'update']);
            Route::delete('/{category}', [ProductCategoryController::class, 'destroy']);
        });
    });

    Route::middleware('auth:api')->prefix('/cart')->group(function () {
        Route::get('/', [CartController::class, 'show']);
        Route::post('/items', [CartController::class, 'addItem']);
        Route::put('/items/{item}', [CartController::class, 'updateItem']);
        Route::delete('/items/{item}', [CartController::class, 'removeItem']);
        Route::delete('/', [CartController::class, 'clear']);
    });

    Route::post('/payments/yookassa/webhook', YooKassaWebhookController::class);

    Route::middleware('auth:api')->prefix('/orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::get('/active', OrderActiveController::class);
        Route::get('/{order}', [OrderController::class, 'show']);
        Route::post('/', [OrderController::class, 'store']);
        Route::post('/{order}/payment', [OrderPaymentController::class, 'store']);
        Route::post('/{order}/payment/status', [OrderPaymentController::class, 'status']);
        Route::post('/{order}/payment/sync', [OrderPaymentController::class, 'sync']);
        Route::post("/{order}/cancel", [OrderController::class, "cancel"]);
    });

    Route::middleware('auth:api')->prefix('/geo')->group(function () {
        Route::get('/address-suggestions', [GeoController::class, 'suggestions']);
        Route::post('/clean-address', [GeoController::class, 'clean']);
        Route::post('/reverse-geocode', [GeoController::class, 'reverseGeocode']);
    });

    Route::middleware('auth:api')->prefix('/delivery')->group(function () {
        Route::post('/quote', DeliveryQuoteController::class);
    });

    Route::middleware('auth:api')->prefix('/courier')->group(function () {
        Route::get('/profile', [CourierProfileController::class, 'show']);
        Route::post('/profile', [CourierProfileController::class, 'upsert']);

        Route::post('/shifts/start', [CourierShiftController::class, 'start']);
        Route::post('/shifts/end', [CourierShiftController::class, 'end']);
        Route::get('/shifts/current', [CourierShiftController::class, 'current']);

        Route::get('/orders/available', [CourierOrderController::class, 'available']);
        Route::get('/orders/active', [CourierOrderController::class, 'active']);
        Route::get('/orders/history', [CourierOrderController::class, 'history']);

        Route::post('/location', [CourierLocationController::class, 'store']);

        Route::post('/orders/{order}/assign', [CourierOrderController::class, 'assign']);
        Route::post('/orders/{order}/picked-up', [CourierOrderController::class, 'pickedUp']);
        Route::post('/orders/{order}/delivered', [CourierOrderController::class, 'delivered']);
    });

    Route::middleware('auth:api')->prefix('/addresses')->group(function () {
        Route::get('/', [AddressController::class, 'index']);
        Route::post('/', [AddressController::class, 'store']);
        Route::put('/{address}', [AddressController::class, 'update']);
        Route::delete('/{address}', [AddressController::class, 'destroy']);
    });

    Route::middleware('auth:api')->prefix('/media')->group(function () {
        Route::post('/', [MediaController::class, 'store']);
        Route::delete('/{media}', [MediaController::class, 'destroy']);
    });

    Route::middleware(['auth:api', 'admin'])->prefix('/admin')->group(function () {
        Route::get('/dashboard', AdminDashboardController::class);

        Route::get('/users', [AdminUserController::class, 'index']);
        Route::get('/users/{user}', [AdminUserController::class, 'show']);
        Route::patch('/users/{user}', [AdminUserController::class, 'update']);

        Route::get('/restaurants', [AdminRestaurantController::class, 'index']);
        Route::get('/restaurants/{restaurant}', [AdminRestaurantController::class, 'show']);
        Route::put('/restaurants/{restaurant}', [AdminRestaurantController::class, 'update']);
        Route::put('/restaurants/{restaurant}/staff/{user}', [AdminRestaurantController::class, 'updateStaff']);
        Route::delete('/restaurants/{restaurant}/staff/{user}', [AdminRestaurantController::class, 'removeStaff']);

        Route::get('/couriers', [AdminCourierController::class, 'index']);
        Route::post('/couriers', [AdminCourierController::class, 'store']);
        Route::get('/couriers/{courier}', [AdminCourierController::class, 'show']);
        Route::patch('/couriers/{courier}', [AdminCourierController::class, 'update']);

        Route::get('/orders', [AdminOrderController::class, 'index']);
        Route::get('/orders/{order}', [AdminOrderController::class, 'show']);
        Route::post('/orders/{order}/accept', [AdminOrderController::class, 'accept']);
        Route::post('/orders/{order}/ready', [AdminOrderController::class, 'ready']);
        Route::post('/orders/{order}/cancel', [AdminOrderController::class, 'cancel']);
        Route::post('/orders/{order}/assign-courier', [AdminOrderController::class, 'assign']);
        Route::post('/orders/{order}/unassign-courier', [AdminOrderController::class, 'unassign']);
        Route::post('/orders/{order}/picked-up', [AdminOrderController::class, 'pickedUp']);
        Route::post('/orders/{order}/delivered', [AdminOrderController::class, 'delivered']);
        Route::patch('/orders/{order}/payment', [AdminOrderController::class, 'updatePayment']);

        Route::get('/logistics/settings', [LogisticsSettingsController::class, 'index']);
        Route::put('/logistics/settings', [LogisticsSettingsController::class, 'update']);
        Route::post('/logistics/test-address', [LogisticsDebugController::class, 'testAddress']);
        Route::post('/logistics/test-route', [LogisticsDebugController::class, 'testRoute']);
        Route::get('/orders/{order}/routes', [LogisticsDebugController::class, 'orderRoutes']);
    });
});
