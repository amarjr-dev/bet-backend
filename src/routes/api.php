<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\GatewayController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Endpoints públicos
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('auth.login');
});

Route::post('purchases', [TransactionController::class, 'store'])
    ->name('purchases.store')
    ->middleware('throttle:60,1');

// Endpoints privados (requer Sanctum)
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

    // Gateways | Rule ADMIN
    Route::prefix('gateways')->middleware('role:admin')->group(function () {
        Route::get('/', [GatewayController::class, 'index'])->name('gateways.index');
        Route::patch('{gateway}/toggle', [GatewayController::class, 'toggle'])->name('gateways.toggle');
        Route::patch('{gateway}/priority', [GatewayController::class, 'updatePriority'])->name('gateways.priority');
    });

    // Users | Rules ADMIN e MANAGER
    Route::middleware('role:admin,manager')->group(function () {
        Route::apiResource('users', UserController::class);
    });

    // Products | gets Rules todos Users autenticados
    // Products | post, put, patch | Rules ADMIN, MANAGER e FINANCE
    // Products | delete | Rules ADMIN e MANAGER
    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show');

    Route::middleware('role:admin,manager,finance')->group(function () {
        Route::post('products', [ProductController::class, 'store'])->name('products.store');
        Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::patch('products/{product}', [ProductController::class, 'update']);
    });

    Route::middleware('role:admin,manager')->group(function () {
        Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    });

    // Clients | Rules ADMIN, MANAGER e FINANCE
    Route::middleware('role:admin,manager,finance')->group(function () {
        Route::get('clients', [ClientController::class, 'index'])->name('clients.index');
        Route::get('clients/{client}', [ClientController::class, 'show'])->name('clients.show');
    });

    // Transactions | Rules ADMIN e FINANCE
    Route::middleware('role:admin,finance')->group(function () {
        Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
        Route::get('transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
        Route::post('transactions/{transaction}/refund', [TransactionController::class, 'refund'])->name('transactions.refund');
    });
});
