<?php

// use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\DeliveryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; // Импорт контроллера
use App\Http\Controllers\API\LocationController;
use App\Http\Controllers\API\PublicProposalController;
use App\Http\Controllers\API\PublicRentalRequestController;
use App\Http\Controllers\API\ProposalCartController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user()->load('company');
});

// Публичные маршруты
Route::prefix('public')->group(function () {
    Route::get('/rental-requests', [PublicRentalRequestController::class, 'index'])
        ->name('api.public.rental-requests.index');

    Route::get('/rental-requests/{id}', [PublicRentalRequestController::class, 'show'])
        ->name('api.public.rental-requests.show');
});

// Доставка
Route::prefix('delivery')->group(function () {
    Route::post('/calculate', [DeliveryController::class, 'calculate']);
    Route::get('/locations', [DeliveryController::class, 'getLocations']);
});

// Этот маршрут будет загружать доступную технику для заявки
Route::get('/rental-requests/{rentalRequest}/available-equipment', [PublicProposalController::class, 'getAvailableEquipment'])
    ->middleware(['auth:sanctum', 'company.verified'])
    ->name('api.public.rental-requests.available-equipment');

// Этот маршрут будет обрабатывать отправку предложения
Route::post('/rental-requests/{rentalRequest}/proposals', [PublicProposalController::class, 'store'])
    ->middleware(['auth:sanctum', 'company.verified'])
    ->name('api.public.rental-requests.proposals.store');

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
    Route::get('/rental-requests', [PublicRentalRequestController::class, 'index']);
    Route::get('/rental-requests/{id}', [PublicRentalRequestController::class, 'show']);
});

// Защищенные маршруты для арендодателей
Route::middleware(['auth:sanctum', 'company.verified'])->group(function () {
    // ========== МАРШРУТЫ ДЛЯ ПРЕДЛОЖЕНИЙ ==========

    // Получение доступной техники для заявки
    Route::get('/rental-requests/{rentalRequest}/available-equipment',
        [PublicProposalController::class, 'getAvailableEquipment'])
        ->name('api.public.rental-requests.available-equipment');

    // Создание предложения (одиночного или bulk)
    Route::post('/rental-requests/{rentalRequest}/proposals',
        [PublicProposalController::class, 'store'])
        ->name('api.public.rental-requests.proposals.store');

    // Получение деталей bulk-предложения
    Route::get('/proposals/{proposal}/bulk',
        [PublicProposalController::class, 'getBulkProposal'])
        ->name('api.public.proposals.bulk');

    // Принятие bulk-предложения
    Route::post('/proposals/{proposal}/accept-bulk',
        [PublicProposalController::class, 'acceptBulkProposal'])
        ->name('api.public.proposals.accept-bulk');

    // ========== СУЩЕСТВУЮЩИЕ МАРШРУТЫ ==========

    // Получение доступного оборудования для заявки
    Route::post('/lessor/equipment/available-for-request',
        [\App\Http\Controllers\API\LessorEquipmentController::class, 'getAvailableForRequest']);
});

// Новый маршрут для оформления заказа
Route::middleware(['auth:sanctum'])->group(function () {
    // API для управления корзиной предложений
    Route::prefix('cart')->group(function () {
        Route::get('/proposal', [ProposalCartController::class, 'getProposalCart']);
        Route::post('/proposal/add', [ProposalCartController::class, 'addToCart']);
        Route::post('/proposal/extend-reservation', [ProposalCartController::class, 'extendReservation']);
        Route::get('/request-progress/{requestId}', [ProposalCartController::class, 'getRequestProgress']);

        // 🔥 ДОБАВИТЬ: Маршрут для удаления элементов корзины
        Route::delete('/items/{itemId}', [ProposalCartController::class, 'removeItem']);
        Route::post('/remove-selected', [ProposalCartController::class, 'removeSelected']);

        // 🔥 НОВЫЕ МАРШРУТЫ ДЛЯ ОФОРМЛЕНИЯ ЗАКАЗА
        Route::post('/proposal/checkout-selected', [ProposalCartController::class, 'checkoutSelected']);
        Route::post('/proposal/direct-checkout-selected', [ProposalCartController::class, 'directCheckoutSelected']);
        Route::post('/proposal/simple-checkout-selected', [ProposalCartController::class, 'simpleCheckoutSelected']);

        // 🔥 МАРШРУТ ДЛЯ МАССОВОГО УДАЛЕНИЯ
        Route::delete('/remove-selected-items', [ProposalCartController::class, 'removeSelectedItems']);
        Route::post('proposal/update-rental-period', [ProposalCartController::class, 'updateRentalPeriod']);
        Route::post('proposal/test-api', [ProposalCartController::class, 'testApi']);
    });
});

// Дополнительные маршруты для предложений
Route::post('/rental-requests/{id}/calculate-delivery', [PublicProposalController::class, 'calculateDelivery'])
    ->middleware(['auth:sanctum', 'company.verified']);
Route::post('/proposal-cart/checkout', [ProposalCartController::class, 'checkoutSelected'])
    ->middleware(['auth:sanctum']);
