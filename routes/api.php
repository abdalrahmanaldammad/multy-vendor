<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StoreController;
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
        Route::get('/getMyStore', [StoreController::class, "getMyStore"])->middleware('isStoreOwner');
        Route::get('/{storeId}/products', [StoreController::class, 'getProductsByStore']);
    });
Route::middleware(['auth:sanctum'])->prefix('product')->group(function () {
    Route::middleware(['isStoreOwner'])->group((function () {
        Route::post('/', [ProductController::class, 'createProduct']);
        Route::put('/{product_id}', [ProductController::class, 'updateProduct']);
        Route::get('/get-my-products-store', [ProductController::class, "getMyProductsStore"]);
    }));
    Route::get('/', [ProductController::class, 'getAllProducts']);
    Route::get('/get-product-by-id/{product_id}', [ProductController::class, "getProductDetails"]);
    Route::get('/search', [ProductController::class, "searchProducts"]);
});
