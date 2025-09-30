<?php

//use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\DeliveryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; // Импорт контроллера
use App\Http\Controllers\API\LocationController;


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Публичные маршруты
/*Route::get('/news', [NewsController::class, 'index']);
Route::get('/news/{news}', [NewsController::class, 'show']);
Route::get('/news/search', [NewsController::class, 'search']);*/

// Доставка
Route::prefix('delivery')->group(function () {
    Route::post('/calculate', [DeliveryController::class, 'calculate']);
    Route::get('/locations', [DeliveryController::class, 'getLocations']);
});

// УДАЛИТЕ ЭТУ ЧАСТЬ (СТРОКИ 24-39)
// КЛАСС DeliveryController УЖЕ ОПРЕДЕЛЕН В ОТДЕЛЬНОМ ФАЙЛЕ

// Защищенные маршруты (только для админов/редакторов)
/*Route::middleware(['auth:sanctum', 'role:admin|editor'])->group(function () {
    Route::post('/news', [NewsController::class, 'store']);
    Route::put('/news/{news}', [NewsController::class, 'update']);
    Route::delete('/news/{news}', [NewsController::class, 'destroy']);
});*/

// Документы
/*Route::prefix('documents')->group(function () {
    Route::post('orders/{order}/delivery-notes', [DocumentController::class, 'createDeliveryNote']);
    Route::post('orders/{order}/waybills', [DocumentController::class, 'createWaybill']);
    Route::post('orders/{order}/completion-act', [DocumentController::class, 'generateCompletionAct']);
});*/

// Финансы
/*Route::prefix('finance')->middleware('auth:api')->group(function () {
    Route::get('/balance', [API\FinanceController::class, 'balance']);
    Route::get('/transactions', [API\FinanceController::class, 'transactions']);
    Route::get('/invoices', [API\FinanceController::class, 'invoices']);
    Route::get('/reconciliation-acts', [API\FinanceController::class, 'reconciliationActs']);
});*/

Route::get('/debug/edit-test/{id}', function ($id) {
    \Log::info('API Debug Test', ['id' => $id, 'user' => auth()->user()]);

    return response()->json([
        'success' => true,
        'message' => 'API тест успешен',
        'data' => [
            'request_id' => $id,
            'user_id' => auth()->id(),
            'user_email' => auth()->user()->email,
            'timestamp' => now()
        ]
    ]);
})->middleware('auth:sanctum');

Route::middleware(['auth:sanctum'])->prefix('lessee')->group(function () {
    // Список заявок с фильтрацией
    Route::get('/rental-requests', [\App\Http\Controllers\API\RentalRequestController::class, 'index']);

      // Добавляем тестовый маршрут ДО основных маршрутов
    Route::get('/debug/edit-test/{id}', function ($id) {
        \Log::info('API Debug Test', [
            'id' => $id,
            'user_id' => auth()->id(),
            'user_email' => auth()->user()->email
        ]);

        return response()->json([
            'success' => true,
            'message' => 'API тест успешен',
            'data' => [
                'request_id' => $id,
                'user_id' => auth()->id(),
                'user_email' => auth()->user()->email,
                'timestamp' => now()->toDateTimeString()
            ]
        ]);
    });
    // Получение одной заявки
    Route::get('/rental-requests/{id}', [\App\Http\Controllers\API\RentalRequestController::class, 'show']);

    // Создание заявки
    Route::post('/rental-requests', [\App\Http\Controllers\API\RentalRequestController::class, 'store']);

    // НОВЫЙ маршрут для Vue-компонента
    Route::get('/rental-requests/{id}/show', [\App\Http\Controllers\API\RentalRequestController::class, 'showForVue']);

      // Технические параметры по категории
    Route::get('/categories/{categoryId}/specifications',
        [\App\Http\Controllers\API\SpecificationController::class, 'getTemplate']);

    // Валидация технических параметров
    Route::post('/specifications/validate',
        [\App\Http\Controllers\API\SpecificationController::class, 'validateSpecifications']);

    Route::put('/rental-requests/{id}', [\App\Http\Controllers\API\RentalRequestController::class, 'update']);

     // Добавляем новые маршруты для операций с заявками
    Route::post('/rental-requests/{id}/pause', [\App\Http\Controllers\API\RentalRequestController::class, 'pause']);
    Route::post('/rental-requests/{id}/resume', [\App\Http\Controllers\API\RentalRequestController::class, 'resume']); // ДОБАВИТЬ
    Route::post('/rental-requests/{id}/cancel', [\App\Http\Controllers\API\RentalRequestController::class, 'cancel']);
    Route::post('/rental-requests/{request}/proposals/{proposal}/accept', [\App\Http\Controllers\API\RentalRequestController::class, 'acceptProposal']);
    Route::post('/rental-requests/{request}/proposals/{proposal}/reject', [\App\Http\Controllers\API\RentalRequestController::class, 'rejectProposal']);


    // Статистика
    Route::get('/rental-requests/stats', [\App\Http\Controllers\API\RentalRequestController::class, 'stats']);


});


// Публичные маршруты
Route::prefix('public')->group(function () {
    // ИСПРАВЛЕНО: используем полный путь к контроллеру
    Route::get('/rental-requests', [\App\Http\Controllers\API\PublicRentalRequestController::class, 'index']);
    Route::get('/rental-requests/{id}', [\App\Http\Controllers\API\PublicRentalRequestController::class, 'show']);

});

// Защищенные маршруты для арендодателей
Route::middleware(['auth:sanctum', 'company.verified'])->group(function () {
    Route::post('/rental-requests/{rentalRequest}/proposals',
        [\App\Http\Controllers\API\PublicRentalRequestController::class, 'createProposal']);

    // ВРЕМЕННО ЗАКОММЕНТИРОВАТЬ - контроллера нет
    // Route::get('/lessor/proposals', [LessorProposalController::class, 'index']);
});
