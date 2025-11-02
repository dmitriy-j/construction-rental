<?php

use App\Http\Controllers\DeliveryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\LocationController;
use App\Http\Controllers\API\PublicProposalController;
use App\Http\Controllers\API\PublicRentalRequestController;
use App\Http\Controllers\API\LessorProposalTemplateController;
use App\Http\Controllers\API\ProposalCartController;
use App\Http\Controllers\API\LessorDashboardController; // 🔥 ДОБАВЛЕНО
use App\Http\Controllers\API\LessorRecommendationController;


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

// Защищенные маршруты для предложений
Route::get('/rental-requests/{rentalRequest}/available-equipment', [PublicProposalController::class, 'getAvailableEquipment'])
    ->middleware(['auth:sanctum', 'company.verified'])
    ->name('api.public.rental-requests.available-equipment');

Route::post('/rental-requests/{rentalRequest}/proposals', [PublicProposalController::class, 'store'])
    ->middleware(['auth:sanctum', 'company.verified'])
    ->name('api.public.rental-requests.proposals.store');

// Маршруты для арендаторов
Route::middleware(['auth:sanctum'])->prefix('lessee')->group(function () {
    Route::get('/rental-requests', [\App\Http\Controllers\API\RentalRequestController::class, 'index']);
    Route::get('/rental-requests/{id}', [\App\Http\Controllers\API\RentalRequestController::class, 'show']);
    Route::post('/rental-requests', [\App\Http\Controllers\API\RentalRequestController::class, 'store']);
    Route::get('/rental-requests/{id}/show', [\App\Http\Controllers\API\RentalRequestController::class, 'showForVue']);
    Route::put('/rental-requests/{id}', [\App\Http\Controllers\API\RentalRequestController::class, 'update']);

    Route::get('/categories/{categoryId}/specifications',
        [\App\Http\Controllers\API\SpecificationController::class, 'getTemplate']);
    Route::post('/specifications/validate',
        [\App\Http\Controllers\API\SpecificationController::class, 'validateSpecifications']);

    Route::post('/rental-requests/{id}/pause', [\App\Http\Controllers\API\RentalRequestController::class, 'pause']);
    Route::post('/rental-requests/{id}/resume', [\App\Http\Controllers\API\RentalRequestController::class, 'resume']);
    Route::post('/rental-requests/{id}/cancel', [\App\Http\Controllers\API\RentalRequestController::class, 'cancel']);
    Route::post('/rental-requests/{request}/proposals/{proposal}/accept', [\App\Http\Controllers\API\RentalRequestController::class, 'acceptProposal']);
    Route::post('/rental-requests/{request}/proposals/{proposal}/reject', [\App\Http\Controllers\API\RentalRequestController::class, 'rejectProposal']);
    Route::get('/rental-requests/stats', [\App\Http\Controllers\API\RentalRequestController::class, 'stats']);
     // ⚠️ МАРШРУТ ДЛЯ ЭКСПОРТА PDF
    Route::get('/rental-requests/{id}/export-pdf',
        [\App\Http\Controllers\Lessee\RentalRequestController::class, 'exportPDF'])
        ->name('api.lessee.rental-requests.export-pdf');
});

// Публичные маршруты
Route::prefix('public')->group(function () {
    Route::get('/rental-requests', [PublicRentalRequestController::class, 'index']);
    Route::get('/rental-requests/{id}', [PublicRentalRequestController::class, 'show']);
});

// Защищенные маршруты для арендодателей
Route::middleware(['auth:sanctum', 'company.verified'])->group(function () {
    Route::get('/rental-requests/{rentalRequest}/available-equipment',
        [PublicProposalController::class, 'getAvailableEquipment'])
        ->name('api.public.rental-requests.available-equipment');

    Route::post('/rental-requests/{rentalRequest}/proposals',
        [PublicProposalController::class, 'store'])
        ->name('api.public.rental-requests.proposals.store');

    Route::get('/proposals/{proposal}/bulk',
        [PublicProposalController::class, 'getBulkProposal'])
        ->name('api.public.proposals.bulk');

    Route::post('/proposals/{proposal}/accept-bulk',
        [PublicProposalController::class, 'acceptBulkProposal'])
        ->name('api.public.proposals.accept-bulk');

    Route::post('/lessor/equipment/available-for-request',
        [\App\Http\Controllers\API\LessorEquipmentController::class, 'getAvailableForRequest']);
});

// 🔥 ОСНОВНЫЕ МАРШРУТЫ ДЛЯ АРЕНДОДАТЕЛЕЙ
Route::middleware(['auth:sanctum', 'company.lessor'])->prefix('lessor')->group(function () {
    // Заявки на аренду
    Route::get('/rental-requests', [\App\Http\Controllers\API\LessorRentalRequestController::class, 'index']);
    Route::get('/rental-requests/{id}', [\App\Http\Controllers\API\LessorRentalRequestController::class, 'show']);
    Route::get('/rental-requests/{id}/analytics', [\App\Http\Controllers\API\LessorRentalRequestController::class, 'analytics']);

    // Шаблоны предложений
    Route::apiResource('proposal-templates', LessorProposalTemplateController::class);
    Route::get('proposal-templates/stats', [LessorProposalTemplateController::class, 'stats']);
    Route::post('proposal-templates/bulk-actions', [LessorProposalTemplateController::class, 'bulkActions']);

    // Применение шаблонов к заявкам
    Route::post('proposal-templates/{templateId}/preview-apply/{rentalRequestId}', [LessorProposalTemplateController::class, 'previewApplyTemplate']);
    Route::post('proposal-templates/{templateId}/apply/{rentalRequestId}', [LessorProposalTemplateController::class, 'applyTemplate']);
    Route::post('rental-requests/{rentalRequest}/apply-template/{template}', [LessorProposalTemplateController::class, 'applyToRequest']);

    // Предложения
    Route::get('/proposals', [\App\Http\Controllers\API\LessorProposalController::class, 'index']);

    // 🔥 МАРШРУТ ДЛЯ СЧЕТЧИКОВ ДАШБОРДА - ДОБАВЛЕНО
    Route::get('/dashboard-counters', [LessorDashboardController::class, 'getCounters'])
        ->name('lessor.dashboard-counters');

     Route::get('/proposal-templates/{id}/ab-test-stats', [LessorProposalTemplateController::class, 'getAbTestStats']);
    Route::post('/proposal-templates/{id}/start-ab-test', [LessorProposalTemplateController::class, 'startAbTest']);
    Route::post('/proposal-templates/{id}/stop-ab-test', [LessorProposalTemplateController::class, 'stopAbTest']);
    Route::post('/proposal-templates/{id}/declare-winner', [LessorProposalTemplateController::class, 'declareWinner']);

    // Рекомендации
    Route::get('/rental-requests/{rentalRequestId}/recommendations',
        [LessorRecommendationController::class, 'getRecommendations']);
    Route::post('/recommendations/quick',
        [LessorRecommendationController::class, 'getQuickRecommendations']);
    Route::post('/recommendation-feedback',
        [LessorRecommendationController::class, 'saveFeedback']);
    Route::get('/recommendations/stats',
        [LessorRecommendationController::class, 'getStats']);
});

// Аналитика для арендодателя
Route::prefix('lessor/analytics')->group(function () {
    Route::get('/realtime', [\App\Http\Controllers\API\LessorAnalyticsController::class, 'getRealTimeData']);
    Route::get('/strategic', [\App\Http\Controllers\API\LessorAnalyticsController::class, 'getStrategicData']);
    Route::get('/dashboard-counters', [\App\Http\Controllers\API\LessorAnalyticsController::class, 'getDashboardCounters']);
});

// Корзина предложений
Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('cart')->group(function () {
        Route::get('/proposal', [ProposalCartController::class, 'getProposalCart']);
        Route::post('/proposal/add', [ProposalCartController::class, 'addToCart']);
        Route::post('/proposal/extend-reservation', [ProposalCartController::class, 'extendReservation']);
        Route::get('/request-progress/{requestId}', [ProposalCartController::class, 'getRequestProgress']);
        Route::delete('/items/{itemId}', [ProposalCartController::class, 'removeItem']);
        Route::post('/remove-selected', [ProposalCartController::class, 'removeSelected']);
        Route::post('/proposal/checkout-selected', [ProposalCartController::class, 'checkoutSelected']);
        Route::post('/proposal/direct-checkout-selected', [ProposalCartController::class, 'directCheckoutSelected']);
        Route::post('/proposal/simple-checkout-selected', [ProposalCartController::class, 'simpleCheckoutSelected']);
        Route::delete('/remove-selected-items', [ProposalCartController::class, 'removeSelectedItems']);
        Route::post('proposal/update-rental-period', [ProposalCartController::class, 'updateRentalPeriod']);
        Route::post('proposal/test-api', [ProposalCartController::class, 'testApi']);
    });
});

// Дополнительные маршруты
Route::post('/rental-requests/{id}/calculate-delivery', [PublicProposalController::class, 'calculateDelivery'])
    ->middleware(['auth:sanctum', 'company.verified']);
Route::post('/proposal-cart/checkout', [ProposalCartController::class, 'checkoutSelected'])
    ->middleware(['auth:sanctum']);

// 🔥 ДЕБАГ МАРШРУТЫ
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

// В routes/api.php добавить:
Route::middleware(['auth:sanctum'])->prefix('debug')->group(function () {
    Route::get('/rental-requests/{id}/specs-test', function ($id) {
        try {
            $rentalRequest = \App\Models\RentalRequest::with('items.category')
                ->where('user_id', auth()->id())
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'request_id' => $rentalRequest->id,
                'items_count' => $rentalRequest->items->count(),
                'items' => $rentalRequest->items->map(function($item) {
                    return [
                        'id' => $item->id,
                        'category' => $item->category->name ?? 'N/A',
                        'quantity' => $item->quantity,
                        'specifications_raw' => $item->specifications,
                        'specifications_type' => gettype($item->specifications),
                        'formatted_specifications' => $item->formatted_specifications,
                        'has_specifications' => !empty($item->specifications)
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    });
});
