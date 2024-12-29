<?php

use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\StoreOrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});



Route::middleware(['auth:sanctum'])
    ->prefix('store')
    ->group(function () {
        Route::middleware(['adminOrStoreOwner'])->group(function () {
            Route::put('/', [StoreController::class, "updateStore"]);
            Route::post('/', [StoreController::class, "createStore"]);
            Route::delete('/{store_id}', [StoreController::class, "deletStore"]);
        });
        Route::get('/search', [StoreController::class, "filterStoreByName"]);
        Route::get('/', [StoreController::class, "getAllStores"]);
        Route::get('/get-my-store', [StoreController::class, "getMyStore"])->middleware('isStoreOwner');
        Route::get('/{storeId}/products', [StoreController::class, 'getProductsByStore']);
    });
Route::middleware(['auth:sanctum'])->prefix('product')->group(function () {
    Route::middleware(['isStoreOwner'])->group((function () {
        Route::post('/', [ProductController::class, 'createProduct']);
        Route::put('/{product_id}', [ProductController::class, 'updateProduct']);
        Route::get('/get-my-products-store', [ProductController::class, "getMyProductsStore"]);
    }));
    Route::get('/', [ProductController::class, 'getAllProducts']);
    Route::get('/detail/{product_id}', [ProductController::class, "getProductDetails"]);
    Route::get('/search', [ProductController::class, "searchProducts"]);
});

// Customer Routes
Route::middleware('auth:sanctum')->group(function () {
    // Place an order
    Route::post('/orders', [OrderController::class, 'store']);
    // Get all orders for authenticated user
    Route::get('/orders', [OrderController::class, 'index']);
    // Get a specific order by ID
    Route::get('/orders/detail/{order_id}', [OrderController::class, 'show']);
    // Cancel an order
    Route::put('/orders/{orderItem_id}/cancel', [OrderController::class, 'cancel']);

    Route::put('/orders/{order_id}/update-order-items', [OrderController::class, 'updateOrderItems']);
});

// Admin Routes
Route::middleware('auth:sanctum', 'isAdmin')->group(function () {
    // Get all orders (admin can see all orders)
    Route::get('/admin/orders', [AdminOrderController::class, 'index']);
    // Get a specific order by ID (admin can see order details)
    Route::get('/admin/orders/{order_id}', [AdminOrderController::class, 'show']);
    // Update order status (admin can change the status of the order)
});

// Store Owner Routes
Route::middleware('auth:sanctum', 'isStoreOwner')->group(function () {
    // Get all orders related to the store (store owner can see only their store's orders)
    Route::get('/store/orders', [StoreOrderController::class, 'index']);
    // Get a specific order by ID (store owner can see their store's order details)
    Route::get('/store/orders/{order_id}', [StoreOrderController::class, 'show']);
    // update the order_status
    Route::put('/store/orders/update_status/{order_item_id}', [StoreOrderController::class, 'updateStatus']);
});



Route::middleware('auth:sanctum')->group(function () {
    // Add a product to favorites
    Route::post('/favorites/{product_id}', [FavoriteController::class, 'store']);

    // Remove a product from favorites
    Route::delete('/favorites/{favorite_id}', [FavoriteController::class, 'destroy']);

    // Get all user's favorite products
    Route::get('/favorites', [FavoriteController::class, 'index']);
});
