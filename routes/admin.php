<?php

use App\Http\Controllers\Admin\AdminEquipmentController;
use App\Http\Controllers\Admin\AdminLesseeController;
use App\Http\Controllers\Admin\AdminLessorController;
use App\Http\Controllers\Admin\BankStatementController;
use App\Http\Controllers\Admin\CompletionActController;
use App\Http\Controllers\Admin\ContractController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\DocumentTemplateController;
use App\Http\Controllers\Admin\ExcelMappingController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\AdminNewsController;
use App\Http\Controllers\Admin\UpdController;
use App\Http\Controllers\Admin\MarkupController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\InvoiceController; // ДОБАВЛЕН импорт InvoiceController
use Illuminate\Support\Facades\Route;

// Админ кабинет
Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

// РАЗДЕЛ УПРАВЛЕНИЯ ЗАКАЗАМИ
Route::prefix('orders')->name('admin.orders.')->group(function () {
    Route::get('/', [AdminOrderController::class, 'index'])->name('index');
    Route::get('/{order}', [AdminOrderController::class, 'show'])->name('show');
    Route::get('/{order}/edit-dates', [AdminOrderController::class, 'editDates'])->name('edit-dates');
    Route::post('/{order}/check-dates-availability', [AdminOrderController::class, 'checkDatesAvailability'])->name('check-dates-availability');
    Route::post('/{order}/update-dates', [AdminOrderController::class, 'updateDates'])->name('update-dates');
    Route::post('/{order}/force-update-dates', [AdminOrderController::class, 'forceUpdateDates'])->name('force-update-dates');
});

// Оборудование
Route::get('/equipment', [AdminEquipmentController::class, 'index'])->name('admin.equipment.index');
Route::get('/equipment/approve/{equipment}', [AdminEquipmentController::class, 'approve'])->name('admin.equipment.approve');
Route::get('/equipment/reject/{equipment}', [AdminEquipmentController::class, 'reject'])->name('admin.equipment.reject');
Route::get('/equipment/{id}', [AdminEquipmentController::class, 'show'])->name('admin.equipment.show');
Route::put('/equipment/{equipment}', [AdminEquipmentController::class, 'update'])->name('admin.equipment.update');

// Арендаторы
Route::get('/lessees', [AdminLesseeController::class, 'index'])->name('admin.lessees.index');
Route::get('/lessees/{lessee}', [AdminLesseeController::class, 'show'])->name('admin.lessees.show');
Route::get('/lessees/{lessee}/orders/{order}', [AdminLesseeController::class, 'showOrder'])->name('admin.lessees.orders.show');

// Арендодатели
Route::get('/lessors', [AdminLessorController::class, 'index'])->name('admin.lessors.index');
Route::get('/lessors/{lessor}', [AdminLessorController::class, 'show'])->name('admin.lessors.show');
Route::get('/lessors/{lessor}/orders/{order}', [AdminLessorController::class, 'showOrder'])->name('admin.lessors.orders.show');

// Админские новости
Route::resource('news', AdminNewsController::class)
    ->except(['show'])
    ->names([
        'index' => 'admin.news.index',
        'create' => 'admin.news.create',
        'store' => 'admin.news.store',
        'edit' => 'admin.news.edit',
        'update' => 'admin.news.update',
        'destroy' => 'admin.news.destroy',
    ]);

// Управление шаблонами Excel
Route::resource('excel-mappings', ExcelMappingController::class)->names([
    'index' => 'admin.excel-mappings.index',
    'create' => 'admin.excel-mappings.create',
    'store' => 'admin.excel-mappings.store',
    'show' => 'admin.excel-mappings.show',
    'edit' => 'admin.excel-mappings.edit',
    'update' => 'admin.excel-mappings.update',
    'destroy' => 'admin.excel-mappings.destroy',
]);

Route::get('excel-mappings/{excelMapping}/download-example', [ExcelMappingController::class, 'downloadExample'])
    ->name('admin.excel-mappings.download-example');

// Управление УПД
Route::prefix('upds')->name('admin.upds.')->group(function () {
    Route::get('/', [UpdController::class, 'index'])->name('index');
    Route::get('/{upd}', [UpdController::class, 'show'])->name('show');
    Route::post('/{upd}/verify-paper', [UpdController::class, 'verifyPaper'])->name('verify-paper');
    Route::post('/{upd}/accept', [UpdController::class, 'accept'])->name('accept');
    Route::post('/{upd}/reject', [UpdController::class, 'reject'])->name('reject');
    Route::delete('/{upd}', [UpdController::class, 'destroy'])->name('destroy');

    // Скачивание
    Route::get('/{upd}/download', [UpdController::class, 'download'])->name('download'); // Загруженные файлы
    Route::get('/{upd}/download-generated', [UpdController::class, 'downloadGenerated'])->name('download-generated'); // Генерация на лету
});

// Управление счетами
Route::prefix('invoices')->name('admin.invoices.')->group(function () {
    Route::get('/', [InvoiceController::class, 'index'])->name('index');
    Route::get('/{invoice}', [InvoiceController::class, 'show'])->name('show');
    Route::get('/{invoice}/download', [InvoiceController::class, 'download'])->name('download');
    Route::post('/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('cancel');
    Route::delete('/{invoice}', [InvoiceController::class, 'destroy'])->name('destroy');

    // Создание счетов из заказов и УПД
    Route::post('/orders/{order}/create', [InvoiceController::class, 'createForOrder'])->name('create-for-order');
    Route::post('/upds/{upd}/create', [InvoiceController::class, 'createForUpd'])->name('create-for-upd');
});

// Документы (общий раздел)
Route::prefix('documents')->name('admin.documents.')->group(function () {
    Route::get('/', [DocumentController::class, 'index'])->name('index');
    Route::get('/{type}/{id}', [DocumentController::class, 'show'])->name('show');
});

// Управление наценками
Route::prefix('markups')->name('markups.')->middleware(['auth', 'can:manage-markups'])->group(function () {
    Route::get('/', [MarkupController::class, 'index'])->name('index');
    Route::get('/create', [MarkupController::class, 'create'])->name('create');
    Route::post('/', [MarkupController::class, 'store'])->name('store');
    Route::get('/{markup}/edit', [MarkupController::class, 'edit'])->name('edit');
    Route::put('/{markup}', [MarkupController::class, 'update'])->name('update');
    Route::delete('/{markup}', [MarkupController::class, 'destroy'])->name('destroy');

    // Rate limiting для тестового расчета
    Route::post('/test-calculation', [MarkupController::class, 'testCalculation'])
        ->name('test-calculation')
        ->middleware('throttle:10,1');
});

// Настройки
Route::prefix('settings')->name('admin.settings.')->group(function () {
    // Шаблоны документов
    Route::resource('document-templates', DocumentTemplateController::class)
        ->names([
            'index' => 'document-templates.index',
            'create' => 'document-templates.create',
            'store' => 'document-templates.store',
            'show' => 'document-templates.show',
            'edit' => 'document-templates.edit',
            'update' => 'document-templates.update',
            'destroy' => 'document-templates.destroy',
        ]);

    // Дополнительные маршруты для шаблонов документов
    Route::get('document-templates/{documentTemplate}/download', [DocumentTemplateController::class, 'download'])
        ->name('document-templates.download');

    Route::get('document-templates/{documentTemplate}/preview', [DocumentTemplateController::class, 'preview'])
        ->name('document-templates.preview');

    Route::get('/{documentTemplate}/generate', [DocumentTemplateController::class, 'generateForm'])
        ->name('generate-form');

    Route::post('/{documentTemplate}/generate', [DocumentTemplateController::class, 'generate'])
        ->name('generate');

    // Наценки
    Route::get('/markups', [MarkupController::class, 'index'])->name('markups.index');
});

// Акты выполненных работ
Route::prefix('completion-acts')->name('admin.completion-acts.')->group(function () {
    Route::post('/{completionAct}/generate-upd', [CompletionActController::class, 'generateUpd'])
        ->name('generate-upd');
    Route::post('/generate-upd-all', [CompletionActController::class, 'generateUpdForAll'])
        ->name('generate-upd-all');
});

// Финансовый раздел
Route::prefix('finance')->name('admin.finance.')->group(function () {
    Route::get('/', [FinanceController::class, 'dashboard'])->name('dashboard');
    Route::get('/transactions', [FinanceController::class, 'transactions'])->name('transactions');
    Route::get('/transactions/{transaction}', [FinanceController::class, 'showTransaction'])->name('transactions.show');
    Route::post('/transactions/{transaction}/cancel', [FinanceController::class, 'cancelTransaction'])->name('transactions.cancel');
    Route::get('/invoices', [FinanceController::class, 'invoices'])->name('invoices');
    Route::get('/invoices/{invoice}', [FinanceController::class, 'showInvoice'])->name('invoices.show');
});

// Банковские выписки
Route::prefix('bank-statements')->name('admin.bank-statements.')->group(function () {
    Route::get('/', [BankStatementController::class, 'index'])->name('index');
    Route::get('/create', [BankStatementController::class, 'create'])->name('create');
    Route::post('/', [BankStatementController::class, 'store'])->name('store');
    Route::get('/pending', [BankStatementController::class, 'pendingTransactions'])->name('pending');
    Route::post('/process-refund', [BankStatementController::class, 'processRefund'])->name('process-refund');
    Route::get('/{bankStatement}', [BankStatementController::class, 'show'])->name('show');
    Route::delete('/{bankStatement}', [BankStatementController::class, 'destroy'])->name('destroy');
    Route::post('/pending/{pendingTransaction}/process', [BankStatementController::class, 'processPendingTransaction'])->name('process-pending');
    Route::post('/pending-payout/{pendingPayout}/cancel', [BankStatementController::class, 'cancelPendingPayout'])->name('cancel-payout');
});

// Отчеты
Route::prefix('reports')->name('admin.reports.')->group(function () {
    Route::get('/', [ReportsController::class, 'index'])->name('index');
    Route::post('/generate', [ReportsController::class, 'generate'])->name('generate');
    Route::get('/export', [ReportsController::class, 'export'])->name('export');
});

// Управление договорами
Route::resource('contracts', ContractController::class)
    ->names([
        'index' => 'admin.contracts.index',
        'create' => 'admin.contracts.create',
        'store' => 'admin.contracts.store',
        'show' => 'admin.contracts.show',
        'edit' => 'admin.contracts.edit',
        'update' => 'admin.contracts.update',
        'destroy' => 'admin.contracts.destroy',
    ]);

Route::get('contracts/{contract}/download', [ContractController::class, 'download'])
    ->name('admin.contracts.download');


Route::get('/upds/{upd}/debug', [UpdController::class, 'debugTemplate'])->name('admin.upds.debug');
Route::get('/upds/{upd}/debug-placeholders', [UpdController::class, 'debugPlaceholders'])->name('admin.upds.debug-placeholders');
