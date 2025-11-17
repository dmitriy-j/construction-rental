<?php

use App\Http\Controllers\Lessee\DashboardController;
use App\Http\Controllers\Lessee\OrderController;
use App\Http\Controllers\Lessee\DocumentController;
use App\Http\Controllers\Lessee\RentalRequestController;
use App\Http\Controllers\Lessee\RequestResponseController;
use App\Http\Controllers\Lessee\ContractController;
use App\Http\Controllers\RentalConditionController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// Дашборд
Route::get('/dashboard', [DashboardController::class, 'index'])->name('lessee.dashboard');

// Баланс
Route::get('/balance', [\App\Http\Controllers\Lessee\BalanceController::class, 'index'])->name('lessee.balance.index');

// Заказы
Route::prefix('orders')->group(function () {
    Route::get('/', [OrderController::class, 'index'])->name('lessee.orders.index');
    Route::get('/{order}', [OrderController::class, 'show'])->name('lessee.orders.show');
    Route::post('/{order}/cancel', [OrderController::class, 'cancel'])->name('lessee.orders.cancel');
    Route::post('/{order}/request-extension', [OrderController::class, 'requestExtension'])->name('lessee.orders.requestExtension');
});

// Документы
Route::get('documents', [DocumentController::class, 'index'])
    ->name('documents.index');

Route::prefix('documents')->name('documents.')->group(function () {
    Route::get('orders/{order}/waybills', [DocumentController::class, 'waybills'])
        ->name('waybills.index');
    Route::get('orders/{order}/completion-acts', [DocumentController::class, 'completionActs'])
        ->name('completion-acts.index');
    Route::get('waybills/{waybill}/download', [DocumentController::class, 'downloadWaybill'])
        ->name('waybill.download');
    Route::get('completion-acts/{completionAct}/download', [DocumentController::class, 'downloadCompletionAct'])
        ->name('completion-act.download');
    Route::get('waybills/{waybill}', [DocumentController::class, 'showWaybill'])->name('waybills.show');
    Route::get('completion-acts/{completionAct}', [DocumentController::class, 'showCompletionAct'])->name('completion-acts.show');
    Route::get('delivery-notes/{deliveryNote}', [DocumentController::class, 'showDeliveryNote'])->name('delivery-notes.show');
});

// Управление условиями аренды
Route::get('/rental-conditions', [RentalConditionController::class, 'index'])
    ->name('lessee.rental-conditions.index');
Route::get('/rental-conditions/create', [RentalConditionController::class, 'create'])
    ->name('lessee.rental-conditions.create');
Route::post('/rental-conditions', [RentalConditionController::class, 'store'])
    ->name('lessee.rental-conditions.store');
Route::put('/rental-conditions/{condition}/set-default',
    [RentalConditionController::class, 'setDefault'])
    ->name('lessee.rental-conditions.set-default');

// Корзина
Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/add/{rentalTerm}', [CartController::class, 'add'])->name('add');
    Route::delete('/remove/{itemId}', [CartController::class, 'remove'])->name('remove');
    Route::post('/update-dates', [CartController::class, 'updateDates'])->name('update-dates');
    Route::post('/clear', [CartController::class, 'clear'])->name('clear');
    Route::delete('/remove-selected', [CartController::class, 'removeSelected'])->name('remove-selected');
});

Route::post('/checkout/proposal', [CheckoutController::class, 'processProposalCheckout'])
    ->name('checkout.proposal');

// Оформление заказа
Route::post('/checkout', [CheckoutController::class, 'checkout'])->name('checkout');

// Заявки арендатора
Route::resource('rental-requests', RentalRequestController::class)
    ->names('lessee.rental-requests');

// Управление предложениями
Route::prefix('rental-requests/{request}/proposals')->group(function () {
    Route::post('{proposal}/accept', [RentalRequestController::class, 'acceptProposal'])
        ->name('lessee.rental-requests.proposals.accept');
    Route::post('{proposal}/reject', [RequestResponseController::class, 'reject'])
        ->name('lessee.rental-requests.proposals.reject');
    Route::post('{proposal}/counter-offer', [RequestResponseController::class, 'counterOffer'])
        ->name('lessee.rental-requests.proposals.counter-offer');
});

// Создание локаций
Route::post('/locations', function (Request $request) {
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'address' => 'required|string|max:255',
        'latitude' => 'nullable|numeric',
        'longitude' => 'nullable|numeric'
    ]);

    try {
        $location = \App\Models\Location::create([
            'name' => $validated['name'],
            'address' => $validated['address'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'company_id' => auth()->user()->company_id
        ]);

        return response()->json([
            'success' => true,
            'location' => $location,
            'message' => 'Локация успешно создана'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Ошибка при создании локации: ' . $e->getMessage()
        ], 500);
    }
})->name('locations.store');

// Отдельный ресурс для предложений
Route::resource('rental-responses', RequestResponseController::class)
    ->only(['index', 'show'])
    ->names('lessee.rental-responses');

// Договоры
Route::prefix('contracts')->name('contracts.')->group(function () {
    Route::get('/', [ContractController::class, 'index'])->name('index');
    Route::get('/{contract}', [ContractController::class, 'show'])->name('show');
    Route::get('/{contract}/download', [ContractController::class, 'download'])->name('download');
});
