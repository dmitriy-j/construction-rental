<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\DeliveryController; // Импорт контроллера

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Публичные маршруты
Route::get('/news', [NewsController::class, 'index']);
Route::get('/news/{news}', [NewsController::class, 'show']);
Route::get('/news/search', [NewsController::class, 'search']);

// Доставка
Route::prefix('delivery')->group(function() {
    Route::post('/calculate', [DeliveryController::class, 'calculate']);
    Route::get('/locations', [DeliveryController::class, 'getLocations']);
});

// УДАЛИТЕ ЭТУ ЧАСТЬ (СТРОКИ 24-39)
// КЛАСС DeliveryController УЖЕ ОПРЕДЕЛЕН В ОТДЕЛЬНОМ ФАЙЛЕ

// Защищенные маршруты (только для админов/редакторов)
Route::middleware(['auth:sanctum', 'role:admin|editor'])->group(function () {
    Route::post('/news', [NewsController::class, 'store']);
    Route::put('/news/{news}', [NewsController::class, 'update']);
    Route::delete('/news/{news}', [NewsController::class, 'destroy']);
});

// Документы
Route::prefix('documents')->group(function () {
    Route::post('orders/{order}/delivery-notes', [DocumentController::class, 'createDeliveryNote']);
    Route::post('orders/{order}/waybills', [DocumentController::class, 'createWaybill']);
    Route::post('orders/{order}/completion-act', [DocumentController::class, 'generateCompletionAct']);
});
