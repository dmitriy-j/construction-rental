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
use App\Http\Controllers\Admin\AdminFinanceController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\AdminRentalRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

Route::prefix('orders')->name('admin.orders.')->group(function () {
    Route::get('/', [AdminOrderController::class, 'index'])->name('index');
    Route::get('/{order}', [AdminOrderController::class, 'show'])->name('show');
    Route::get('/{order}/edit-dates', [AdminOrderController::class, 'editDates'])->name('edit-dates');
    Route::post('/{order}/check-dates-availability', [AdminOrderController::class, 'checkDatesAvailability'])->name('check-dates-availability');
    Route::post('/{order}/update-dates', [AdminOrderController::class, 'updateDates'])->name('update-dates');
    Route::post('/{order}/force-update-dates', [AdminOrderController::class, 'forceUpdateDates'])->name('force-update-dates');
});

Route::post('/locations', [\App\Http\Controllers\Admin\AdminLocationController::class, 'store'])->name('admin.locations.store');

Route::get('/equipment', [AdminEquipmentController::class, 'index'])->name('admin.equipment.index');
Route::get('/equipment/create', [AdminEquipmentController::class, 'create'])->name('admin.equipment.create');
Route::post('/equipment', [AdminEquipmentController::class, 'store'])->name('admin.equipment.store');
Route::get('/equipment/approve/{equipment}', [AdminEquipmentController::class, 'approve'])->name('admin.equipment.approve');
Route::get('/equipment/reject/{equipment}', [AdminEquipmentController::class, 'reject'])->name('admin.equipment.reject');
Route::get('/equipment/{equipment}/edit', [AdminEquipmentController::class, 'edit'])->name('admin.equipment.edit');
Route::get('/equipment/{equipment}', [AdminEquipmentController::class, 'show'])->name('admin.equipment.show');
Route::put('/equipment/{equipment}', [AdminEquipmentController::class, 'update'])->name('admin.equipment.update');
Route::delete('/equipment/{equipment}', [AdminEquipmentController::class, 'destroy'])->name('admin.equipment.destroy');

Route::get('/lessees', [AdminLesseeController::class, 'index'])->name('admin.lessees.index');
Route::get('/lessees/{lessee}/edit', [AdminLesseeController::class, 'edit'])->name('admin.lessees.edit');
Route::put('/lessees/{lessee}', [AdminLesseeController::class, 'update'])->name('admin.lessees.update');
Route::post('/lessees/{lessee}/verify', [AdminLesseeController::class, 'verify'])->name('admin.lessees.verify');
Route::get('/lessees/{lessee}', [AdminLesseeController::class, 'show'])->name('admin.lessees.show');
Route::get('/lessees/{lessee}/orders/{order}', [AdminLesseeController::class, 'showOrder'])->name('admin.lessees.orders.show');

Route::get('/lessors', [AdminLessorController::class, 'index'])->name('admin.lessors.index');
Route::get('/lessors/{lessor}/edit', [AdminLessorController::class, 'edit'])->name('admin.lessors.edit');
Route::put('/lessors/{lessor}', [AdminLessorController::class, 'update'])->name('admin.lessors.update');
Route::post('/lessors/{lessor}/verify', [AdminLessorController::class, 'verify'])->name('admin.lessors.verify');
Route::get('/lessors/{lessor}', [AdminLessorController::class, 'show'])->name('admin.lessors.show');
Route::get('/lessors/{lessor}/orders/{order}', [AdminLessorController::class, 'showOrder'])->name('admin.lessors.orders.show');

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

Route::prefix('upds')->name('admin.upds.')->group(function () {
    Route::get('/', [UpdController::class, 'index'])->name('index');
    Route::get('/{upd}', [UpdController::class, 'show'])->name('show');
    Route::post('/{upd}/verify-paper', [UpdController::class, 'verifyPaper'])->name('verify-paper');
    Route::post('/{upd}/accept', [UpdController::class, 'accept'])->name('accept');
    Route::post('/{upd}/reject', [UpdController::class, 'reject'])->name('reject');
    Route::delete('/{upd}', [UpdController::class, 'destroy'])->name('destroy');
    Route::get('/{upd}/download', [UpdController::class, 'download'])->name('download');
    Route::get('/{upd}/download-generated', [UpdController::class, 'downloadGenerated'])->name('download-generated');
});

Route::prefix('invoices')->name('admin.invoices.')->group(function () {
    Route::get('/', [InvoiceController::class, 'index'])->name('index');
    Route::get('/{invoice}', [InvoiceController::class, 'show'])->name('show');
    Route::get('/{invoice}/download', [InvoiceController::class, 'download'])->name('download');
    Route::post('/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('cancel');
    Route::delete('/{invoice}', [InvoiceController::class, 'destroy'])->name('destroy');
    Route::post('/orders/{order}/create', [InvoiceController::class, 'createForOrder'])->name('create-for-order');
    Route::post('/upds/{upd}/create', [InvoiceController::class, 'createForUpd'])->name('create-for-upd');
});

Route::prefix('documents')->name('admin.documents.')->group(function () {
    Route::get('/', [DocumentController::class, 'index'])->name('index');
    Route::get('/{type}/{id}', [DocumentController::class, 'show'])->name('show');
});

Route::prefix('markups')->name('markups.')->middleware(['auth', 'can:manage-markups'])->group(function () {
    Route::get('/', [MarkupController::class, 'index'])->name('index');
    Route::get('/create', [MarkupController::class, 'create'])->name('create');
    Route::post('/', [MarkupController::class, 'store'])->name('store');
    Route::get('/{markup}/edit', [MarkupController::class, 'edit'])->name('edit');
    Route::put('/{markup}', [MarkupController::class, 'update'])->name('update');
    Route::delete('/{markup}', [MarkupController::class, 'destroy'])->name('destroy');
    Route::post('/test-calculation', [MarkupController::class, 'testCalculation'])->name('test-calculation')->middleware('throttle:10,1');
});

Route::prefix('settings')->name('admin.settings.')->group(function () {
    Route::resource('document-templates', DocumentTemplateController::class)->names([
        'index' => 'document-templates.index',
        'create' => 'document-templates.create',
        'store' => 'document-templates.store',
        'show' => 'document-templates.show',
        'edit' => 'document-templates.edit',
        'update' => 'document-templates.update',
        'destroy' => 'document-templates.destroy',
    ]);
    Route::get('document-templates/{documentTemplate}/download', [DocumentTemplateController::class, 'download'])->name('document-templates.download');
    Route::get('document-templates/{documentTemplate}/preview', [DocumentTemplateController::class, 'preview'])->name('document-templates.preview');
    Route::get('/{documentTemplate}/generate', [DocumentTemplateController::class, 'generateForm'])->name('generate-form');
    Route::post('/{documentTemplate}/generate', [DocumentTemplateController::class, 'generate'])->name('generate');
    Route::get('/markups', [MarkupController::class, 'index'])->name('markups.index');
});

Route::prefix('completion-acts')->name('admin.completion-acts.')->group(function () {
    Route::post('/{completionAct}/generate-upd', [CompletionActController::class, 'generateUpd'])->name('generate-upd');
    Route::post('/generate-upd-all', [CompletionActController::class, 'generateUpdForAll'])->name('generate-upd-all');
});

Route::prefix('finance')->name('admin.finance.')->group(function () {
    Route::get('/', [FinanceController::class, 'dashboard'])->name('dashboard');
    Route::get('/transactions', [FinanceController::class, 'transactions'])->name('transactions');
    Route::get('/transactions/{transaction}', [FinanceController::class, 'showTransaction'])->name('transactions.show');
    Route::post('/transactions/{transaction}/cancel', [FinanceController::class, 'cancelTransaction'])->name('transactions.cancel');
    Route::get('/invoices', [FinanceController::class, 'invoices'])->name('invoices');
    Route::get('/invoices/{invoice}', [FinanceController::class, 'showInvoice'])->name('invoices.show');
    Route::get('/lessee-debts', [AdminFinanceController::class, 'lesseeDebts'])->name('lessee-debts');
    Route::get('/lessor-debts', [AdminFinanceController::class, 'lessorDebts'])->name('lessor-debts');
    Route::get('/adjustment-create', [AdminFinanceController::class, 'adjustmentCreate'])->name('adjustment-create');
    Route::post('/adjustment-store', [AdminFinanceController::class, 'adjustmentStore'])->name('adjustment-store');
    Route::get('/balance-adjustments', [AdminFinanceController::class, 'balanceAdjustments'])->name('balance-adjustments');
    Route::get('/company/{company}', [AdminFinanceController::class, 'companyDetail'])->name('company-detail');
    Route::get('/reconciliation/{company}', [AdminFinanceController::class, 'reconciliationAct'])->name('reconciliation-act');
});

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
    Route::put('/{transaction}/match', [BankStatementController::class, 'matchTransaction'])->name('match-transaction');
});

Route::prefix('reports')->name('admin.reports.')->group(function () {
    Route::get('/', [ReportsController::class, 'index'])->name('index');
    Route::post('/generate', [ReportsController::class, 'generate'])->name('generate');
    Route::get('/export', [ReportsController::class, 'export'])->name('export');
});

Route::resource('contracts', ContractController::class)->names([
    'index' => 'admin.contracts.index',
    'create' => 'admin.contracts.create',
    'store' => 'admin.contracts.store',
    'show' => 'admin.contracts.show',
    'edit' => 'admin.contracts.edit',
    'update' => 'admin.contracts.update',
    'destroy' => 'admin.contracts.destroy',
]);

Route::get('contracts/{contract}/download', [ContractController::class, 'download'])->name('admin.contracts.download');

Route::resource('rental-requests', AdminRentalRequestController::class)->names([
    'index' => 'admin.rental-requests.index',
    'create' => 'admin.rental-requests.create',
    'store' => 'admin.rental-requests.store',
    'show' => 'admin.rental-requests.show',
    'edit' => 'admin.rental-requests.edit',
    'update' => 'admin.rental-requests.update',
    'destroy' => 'admin.rental-requests.destroy',
]);

Route::get('/upds/{upd}/debug', [UpdController::class, 'debugTemplate'])->name('admin.upds.debug');
Route::get('/upds/{upd}/debug-placeholders', [UpdController::class, 'debugPlaceholders'])->name('admin.upds.debug-placeholders');

// Обращения с сайта
use App\Http\Controllers\Admin\AdminContactController;
Route::get('/contacts', [AdminContactController::class, 'index'])->name('admin.contacts.index');
Route::patch('/contacts/{contact}', [AdminContactController::class, 'markAsRead'])->name('admin.contacts.mark-read');
Route::delete('/contacts/{contact}', [AdminContactController::class, 'destroy'])->name('admin.contacts.destroy');
