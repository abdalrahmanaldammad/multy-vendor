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
    });
