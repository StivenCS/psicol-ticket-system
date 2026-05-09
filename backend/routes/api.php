<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TicketController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

Route::middleware('auth:api')->group(function () {
    Route::get('dashboard/stats', [DashboardController::class, 'stats']);

    Route::get('tickets',          [TicketController::class, 'index']);
    Route::get('tickets/export',   [TicketController::class, 'export']);
    Route::post('tickets',         [TicketController::class, 'store']);
    Route::get('tickets/{ticket}', [TicketController::class, 'show']);

    Route::get('users', fn() => response()->json(
        User::select('id', 'name', 'email')->orderBy('name')->get()
    ));

    Route::middleware('role:admin|agente')->group(function () {
        Route::put('tickets/{ticket}',   [TicketController::class, 'update']);
        Route::patch('tickets/{ticket}', [TicketController::class, 'update']);
    });

    Route::middleware('role:admin')->group(function () {
        Route::delete('tickets/{ticket}', [TicketController::class, 'destroy']);
    });
});
