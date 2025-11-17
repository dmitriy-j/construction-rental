<?php

use App\Http\Controllers\Lessor\DashboardController;
use App\Http\Controllers\Lessor\DeliveryNoteController;
use App\Http\Controllers\Lessor\EquipmentController;
use App\Http\Controllers\Lessor\LessorOrderController;
use App\Http\Controllers\Lessor\OperatorController;
use App\Http\Controllers\Lessor\ShiftController;
use App\Http\Controllers\Lessor\UpdController;
use App\Http\Controllers\Lessor\WaybillController;
use App\Http\Controllers\Lessor\EquipmentMassImportController;
use App\Http\Controllers\Lessor\RentalRequestController;
use App\Http\Controllers\Lessor\ContractController;
use Illuminate\Support\Facades\Route;

// Дашборд
Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::post('dashboard/mark-as-viewed', [DashboardController::class, 'markAsViewed'])
    ->name('dashboard.markAsViewed');

// Баланс
Route::get('/balance', [\App\Http\Controllers\Lessor\BalanceController::class, 'index'])->name('balance.index');

// Оборудование
Route::resource('equipment', EquipmentController::class)
    ->names([
        'index' => 'equipment.index',
        'create' => 'equipment.create',
        'store' => 'equipment.store',
        'show' => 'equipment.show',
        'edit' => 'equipment.edit',
        'update' => 'equipment.update',
        'destroy' => 'equipment.destroy',
    ]);
Route::prefix('equipment-mass-import')->name('equipment.mass-import.')->group(function () {
    Route::get('create', [EquipmentMassImportController::class, 'create'])->name('create');
    Route::post('store', [EquipmentMassImportController::class, 'store'])->name('store');
    Route::get('download-template', [EquipmentMassImportController::class, 'downloadTemplate'])->name('download-template');
    Route::get('{import}', [EquipmentMassImportController::class, 'show'])->name('show');
});

// Шаблоны предложений
Route::prefix('proposal-templates')->name('proposal-templates.')->group(function () {
    Route::get('/', [\App\Http\Controllers\API\LessorProposalTemplateController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\API\LessorProposalTemplateController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\API\LessorProposalTemplateController::class, 'store'])->name('store');
    Route::get('/{proposalTemplate}/edit', [\App\Http\Controllers\API\LessorProposalTemplateController::class, 'edit'])->name('edit');
    Route::put('/{proposalTemplate}', [\App\Http\Controllers\API\LessorProposalTemplateController::class, 'update'])->name('update');
    Route::delete('/{proposalTemplate}', [\App\Http\Controllers\API\LessorProposalTemplateController::class, 'destroy'])->name('destroy');
});

// Операторы
Route::prefix('operators')->name('operators.')->group(function () {
    Route::get('/', [OperatorController::class, 'index'])->name('index');
    Route::get('create', [OperatorController::class, 'create'])->name('create');
    Route::post('/', [OperatorController::class, 'store'])->name('store');
    Route::get('{operator}/edit', [OperatorController::class, 'edit'])->name('edit');
    Route::put('{operator}', [OperatorController::class, 'update'])->name('update');
    Route::delete('{operator}', [OperatorController::class, 'destroy'])->name('destroy');
});

// Заказы
Route::prefix('orders')->name('orders.')->group(function () {
    Route::get('/', [LessorOrderController::class, 'index'])->name('index');
    Route::get('{order}', [LessorOrderController::class, 'show'])->name('show');
    Route::post('{order}/update-status', [LessorOrderController::class, 'updateStatus'])->name('updateStatus');
    Route::post('{order}/mark-active', [LessorOrderController::class, 'markAsActive'])->name('markActive');
    Route::post('{order}/mark-completed', [LessorOrderController::class, 'markAsCompleted'])->name('markCompleted');
    Route::post('{order}/handle-extension', [LessorOrderController::class, 'handleExtension'])->name('handleExtension');
    Route::post('{order}/prepare-shipment', [LessorOrderController::class, 'prepareForShipment'])->name('prepare-shipment');
    Route::post('{order}/approve', [LessorOrderController::class, 'approve'])->name('approve');
    Route::post('{order}/reject', [LessorOrderController::class, 'reject'])->name('reject');
});

// Документы
Route::prefix('documents')->name('documents.')->group(function () {
    Route::get('/', [\App\Http\Controllers\DocumentController::class, 'index'])->name('index');
    Route::get('download/{id}/{type}', [\App\Http\Controllers\DocumentController::class, 'download'])->name('download');
    Route::get('status-update', [\App\Http\Controllers\DocumentController::class, 'statusUpdate'])->name('status-update');
    Route::get('completion_acts/{act}', [\App\Http\Controllers\DocumentController::class, 'showCompletionAct'])->name('completion_acts.show');
});

// Накладные
Route::prefix('delivery-notes')->name('delivery-notes.')->group(function () {
    Route::get('{note}/edit', [DeliveryNoteController::class, 'edit'])->name('edit');
    Route::put('{note}', [DeliveryNoteController::class, 'update'])->name('update');
    Route::post('{note}/close', [DeliveryNoteController::class, 'close'])->name('close');
});

// Путевые листы
Route::prefix('waybills')->name('waybills.')->group(function () {
    Route::get('order/{order}', [WaybillController::class, 'index'])->name('index');
    Route::get('{waybill}', [WaybillController::class, 'show'])->name('show');
    Route::put('{waybill}', [WaybillController::class, 'update'])->name('update');
    Route::post('{waybill}/sign', [WaybillController::class, 'sign'])->name('sign');
    Route::get('{waybill}/download', [WaybillController::class, 'download'])->name('download');
    Route::post('{waybill}/add-shift', [WaybillController::class, 'addShift'])->name('add-shift');
    Route::post('{waybill}/close', [WaybillController::class, 'close'])->name('close');
    Route::get('{waybill}/shifts', [WaybillController::class, 'getShifts'])->name('shifts');
});

// УПД
Route::prefix('upds')->name('upds.')->group(function () {
    Route::get('/', [UpdController::class, 'index'])->name('index');
    Route::get('/create', [UpdController::class, 'create'])->name('create');
    Route::post('/', [UpdController::class, 'store'])->name('store');
    Route::get('/{upd}', [UpdController::class, 'show'])->name('show');
    Route::delete('/{upd}', [UpdController::class, 'destroy'])->name('destroy');
    Route::get('/download', [UpdController::class, 'download'])->name('download');
});

// Смены
Route::prefix('shifts')->name('shifts.')->group(function () {
    Route::put('{shift}', [ShiftController::class, 'update'])->name('update');
    Route::delete('{shift}', [ShiftController::class, 'destroy'])->name('destroy');
});

// Заявки
Route::get('/rental-requests', [RentalRequestController::class, 'index'])
    ->name('rental-requests.index');
Route::get('/rental-requests/{id}', [RentalRequestController::class, 'show'])
    ->name('rental-requests.show');

// Договоры
Route::prefix('contracts')->name('contracts.')->group(function () {
    Route::get('/', [ContractController::class, 'index'])->name('index');
    Route::get('/{contract}', [ContractController::class, 'show'])->name('show');
    Route::get('/{contract}/download', [ContractController::class, 'download'])->name('download');
});
