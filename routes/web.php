<?php

use App\Http\Controllers\Admin\AdminEquipmentController;
use App\Http\Controllers\Admin\AdminLesseeController;
use App\Http\Controllers\Admin\AdminLessorController;
use App\Http\Controllers\Admin\BankStatementController;
use App\Http\Controllers\Admin\CompletionActController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExcelMappingController;
use App\Http\Controllers\Admin\DocumentTemplateController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\Catalog\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CompanyEmployeeController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\Lessee\DashboardController as LesseeDashboardController;
use App\Http\Controllers\Lessee\OrderController;
use App\Http\Controllers\Lessor\DashboardController as LessorDashboardController;
use App\Http\Controllers\Lessor\DeliveryNoteController;
use App\Http\Controllers\Lessor\EquipmentController as LessorEquipmentController;
use App\Http\Controllers\Lessor\LessorOrderController as LessorOrders;
use App\Http\Controllers\Lessor\OperatorController;
use App\Http\Controllers\Lessor\ShiftController;
use App\Http\Controllers\Lessor\UpdController;
use App\Http\Controllers\Lessor\WaybillController;
use App\Http\Controllers\Lessor\EquipmentMassImportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RentalConditionController;
use App\Http\Controllers\RentalRequestController;
use App\Http\Controllers\Lessor\RentalRequestController as LessorRentalRequestController;
use App\Http\Controllers\Lessor\ProposalTemplateController as LessorProposalTemplateController;
use App\Http\Controllers\NewsController; // Добавлено для публичных новостей
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// Главная страница
Route::get('/', function () {
    return view('home');
})->name('home');

// Заявки
Route::get('/requests', [RentalRequestController::class, 'index'])->name('rental-requests.index');
Route::get('/portal/rental-requests/{id}', function ($id) {
    return view('public.rental-request-show', [
        'rentalRequestId' => $id
    ]);
})->name('portal.rental-requests.show');

// Маршрут для страницы контактов
Route::get('/contacts', [PageController::class, 'contacts'])->name('pages.contacts');

// Каталог
Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/catalog/{equipment}', [CatalogController::class, 'show'])->name('catalog.show');

// Статические страницы
Route::get('/free', fn () => view('free'))->name('free');
Route::get('/cooperation', fn () => view('cooperation'))->name('cooperation');
Route::get('/jobs', fn () => view('jobs'))->name('jobs');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contacts', [PageController::class, 'contacts'])->name('contacts');

// Публичные роуты новостей
Route::get('/news', [NewsController::class, 'index'])->name('news.index');
Route::get('/news/{news:slug}', [NewsController::class, 'show'])->name('news.show');

// Маршрут для страницы "Ремонт"
Route::get('/repair', function () {
    return view('repair');
})->name('repair');

// Регистрация и аутентификация
Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');

// Личный кабинет компании
Route::prefix('/company')
    ->middleware(['auth', 'role:company_admin'])
    ->group(function () {
        Route::get('/dashboard', fn () => view('company.dashboard'))->name('company.dashboard');
        Route::resource('employees', CompanyEmployeeController::class)
            ->names([
                'index' => 'company.employees.index',
                'create' => 'company.employees.create',
                'store' => 'company.employees.store',
                'show' => 'company.employees.show',
                'edit' => 'company.employees.edit',
                'update' => 'company.employees.update',
                'destroy' => 'company.employees.destroy',
            ]);
    });

// Для арендодателя
Route::prefix('lessor')
    ->middleware(['auth', 'company.verified', 'company.lessor'])
    ->name('lessor.')
    ->group(function () {
        // Дашборд
        Route::get('dashboard', [LessorDashboardController::class, 'index'])->name('dashboard');
        Route::post('dashboard/mark-as-viewed', [LessorDashboardController::class, 'markAsViewed'])
            ->name('dashboard.markAsViewed');

        // Баланс
        Route::get('/balance', [\App\Http\Controllers\Lessor\BalanceController::class, 'index'])->name('balance.index');

        // Оборудование (ИСПРАВЛЕНО: используем контроллер из Lessor)
        Route::resource('equipment', LessorEquipmentController::class)
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

        // 🔥 ШАБЛОНЫ ПРЕДЛОЖЕНИЙ - ДОБАВЛЕНЫ МАРШРУТЫ
        Route::prefix('proposal-templates')->name('proposal-templates.')->group(function () {
            Route::get('/', [LessorProposalTemplateController::class, 'index'])->name('index');
            Route::get('/create', [LessorProposalTemplateController::class, 'create'])->name('create');
            Route::post('/', [LessorProposalTemplateController::class, 'store'])->name('store');
            Route::get('/{proposalTemplate}/edit', [LessorProposalTemplateController::class, 'edit'])->name('edit');
            Route::put('/{proposalTemplate}', [LessorProposalTemplateController::class, 'update'])->name('update');
            Route::delete('/{proposalTemplate}', [LessorProposalTemplateController::class, 'destroy'])->name('destroy');
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
            Route::get('/', [LessorOrders::class, 'index'])->name('index');
            Route::get('{order}', [LessorOrders::class, 'show'])->name('show');
            Route::post('{order}/update-status', [LessorOrders::class, 'updateStatus'])->name('updateStatus');
            Route::post('{order}/mark-active', [LessorOrders::class, 'markAsActive'])->name('markActive');
            Route::post('{order}/mark-completed', [LessorOrders::class, 'markAsCompleted'])->name('markCompleted');
            Route::post('{order}/handle-extension', [LessorOrders::class, 'handleExtension'])->name('handleExtension');
            Route::post('{order}/prepare-shipment', [LessorOrders::class, 'prepareForShipment'])->name('prepare-shipment');
            Route::post('{order}/approve', [LessorOrders::class, 'approve'])->name('approve');
            Route::post('{order}/reject', [LessorOrders::class, 'reject'])->name('reject');
            Route::post('{order}/delivery-note', [DocumentController::class, 'createDeliveryNote'])->name('createDeliveryNote');
            Route::post('{order}/waybill', [DocumentController::class, 'createWaybill'])->name('createWaybill');
            Route::post('{order}/completion-act', [DocumentController::class, 'generateCompletionAct'])->name('generateCompletionAct');
        });

        // Документы
        Route::prefix('documents')->name('documents.')->group(function () {
            Route::get('/', [DocumentController::class, 'index'])->name('index');
            Route::get('download/{id}/{type}', [DocumentController::class, 'download'])->name('download');
            Route::get('status-update', [DocumentController::class, 'statusUpdate'])->name('status-update');
            Route::get('completion_acts/{act}', [DocumentController::class, 'showCompletionAct'])->name('completion_acts.show');
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

        // 🔥ЗАЯВКИ ИСПРАВЛЕНИЕ: Используем правильный контроллер для ЛК арендодателя
        Route::get('/rental-requests', [\App\Http\Controllers\Lessor\RentalRequestController::class, 'index'])
            ->name('rental-requests.index');
        Route::get('/rental-requests/{id}', [\App\Http\Controllers\Lessor\RentalRequestController::class, 'show'])
            ->name('rental-requests.show');
    });

// Для арендатора
Route::prefix('lessee')
    ->middleware(['auth', 'company.verified', 'company.lessee'])
    ->group(function () {
        // Дашборд
        Route::get('/dashboard', [LesseeDashboardController::class, 'index'])->name('lessee.dashboard');

        // Баланс
        Route::get('/balance', [\App\Http\Controllers\Lessee\BalanceController::class, 'index'])->name('lessee.balance.index');

        // Заказы
        Route::prefix('orders')->group(function () {
            Route::get('/', [\App\Http\Controllers\Lessee\OrderController::class, 'index'])->name('lessee.orders.index');
            Route::get('/{order}', [\App\Http\Controllers\Lessee\OrderController::class, 'show'])->name('lessee.orders.show');
            Route::post('/{order}/cancel', [OrderController::class, 'cancel'])->name('lessee.orders.cancel');
            Route::post('/{order}/request-extension', [\App\Http\Controllers\Lessee\OrderController::class, 'requestExtension'])->name('lessee.orders.requestExtension');
        });

        // Документы - общий маршрут
        Route::get('documents', [\App\Http\Controllers\Lessee\DocumentController::class, 'index'])
            ->name('documents.index');

        // Специфические маршруты документов
        Route::prefix('documents')->name('documents.')->group(function () {
            Route::get('orders/{order}/waybills', [\App\Http\Controllers\Lessee\DocumentController::class, 'waybills'])
                ->name('waybills.index');
            Route::get('orders/{order}/completion-acts', [\App\Http\Controllers\Lessee\DocumentController::class, 'completionActs'])
                ->name('completion-acts.index');
            Route::get('waybills/{waybill}/download', [\App\Http\Controllers\Lessee\DocumentController::class, 'downloadWaybill'])
                ->name('waybill.download');
            Route::get('completion-acts/{completionAct}/download', [\App\Http\Controllers\Lessee\DocumentController::class, 'downloadCompletionAct'])
                ->name('completion-act.download');
            Route::get('waybills/{waybill}', [\App\Http\Controllers\Lessee\DocumentController::class, 'showWaybill'])->name('waybills.show');
            Route::get('completion-acts/{completionAct}', [\App\Http\Controllers\Lessee\DocumentController::class, 'showCompletionAct'])->name('completion-acts.show');
            Route::get('delivery-notes/{deliveryNote}', [\App\Http\Controllers\Lessee\DocumentController::class, 'showDeliveryNote'])->name('delivery-notes.show');
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
        Route::resource('rental-requests', \App\Http\Controllers\Lessee\RentalRequestController::class)
            ->names('lessee.rental-requests');

        // Удален дублирующий маршрут
        // Route::get('/rental-requests/{id}/edit', [\App\Http\Controllers\Lessee\RentalRequestController::class, 'edit'])
        //     ->name('lessee.rental-requests.edit');

        // Управление предложениями
        Route::prefix('rental-requests/{request}/proposals')->group(function () {
            Route::post('{proposal}/accept', [\App\Http\Controllers\Lessee\RentalRequestController::class, 'acceptProposal'])
                ->name('lessee.rental-requests.proposals.accept');
            Route::post('{proposal}/reject', [\App\Http\Controllers\Lessee\RequestResponseController::class, 'reject'])
                ->name('lessee.rental-requests.proposals.reject');
            Route::post('{proposal}/counter-offer', [\App\Http\Controllers\Lessee\RequestResponseController::class, 'counterOffer'])
                ->name('lessee.rental-requests.proposals.counter-offer');
        });

        // Новый маршрут для создания локаций
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
        Route::resource('rental-responses', \App\Http\Controllers\Lessee\RequestResponseController::class)
            ->only(['index', 'show'])
            ->names('lessee.rental-responses');
    });

// Загрузка УПД (общий доступ)
Route::get('/orders/{order}/upd/{type}', [OrderController::class, 'downloadUPDF']);

// Для перевозчика
/*Route::middleware(['auth', 'verified', 'carrier'])->prefix('carrier')->group(function () {
    Route::get('/dashboard', [CarrierDashboardController::class, 'index'])->name('carrier.dashboard');
    Route::get('/orders', [CarrierOrderController::class, 'index'])->name('carrier.orders.index');
    Route::get('/orders/{order}', [CarrierOrderController::class, 'show'])->name('carrier.orders.show');
    Route::post('/orders/{order}/accept', [CarrierOrderController::class, 'accept'])->name('carrier.orders.accept');
    Route::post('/orders/{order}/complete', [CarrierOrderController::class, 'complete'])->name('carrier.orders.complete');
});*/

// Админ кабинет
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/equipment', [AdminEquipmentController::class, 'index'])->name('admin.equipment.index');
    Route::get('/equipment/approve/{equipment}', [AdminEquipmentController::class, 'approve'])->name('admin.equipment.approve');
    Route::get('/equipment/reject/{equipment}', [AdminEquipmentController::class, 'reject'])->name('admin.equipment.reject');
    Route::get('/admin/equipment/{id}', [AdminEquipmentController::class, 'show'])->name('admin.equipment.show');
    Route::get('/lessees', [AdminLesseeController::class, 'index'])->name('admin.lessees.index');
    Route::get('/lessees/{lessee}', [AdminLesseeController::class, 'show'])->name('admin.lessees.show');
    Route::get('/lessees/{lessee}/orders/{order}', [AdminLesseeController::class, 'showOrder'])->name('admin.lessees.orders.show');
    Route::get('/lessors', [AdminLessorController::class, 'index'])->name('admin.lessors.index');
    Route::get('/lessors/{lessor}', [AdminLessorController::class, 'show'])->name('admin.lessors.show');
    Route::get('/lessors/{lessor}/orders/{order}', [AdminLessorController::class, 'showOrder'])->name('admin.lessors.orders.show');
    Route::put('/equipment/{equipment}', [AdminEquipmentController::class, 'update'])->name('admin.equipment.update');

    // Админские новости (исправлено)
    Route::resource('news', \App\Http\Controllers\Admin\AdminNewsController::class)
        ->except(['show']) // Убираем show
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
        Route::get('/', [\App\Http\Controllers\Admin\UpdController::class, 'index'])->name('index');
        Route::get('/{upd}', [\App\Http\Controllers\Admin\UpdController::class, 'show'])->name('show');
        Route::post('/{upd}/verify-paper', [\App\Http\Controllers\Admin\UpdController::class, 'verifyPaper'])->name('verify-paper');
        Route::post('/{upd}/accept', [\App\Http\Controllers\Admin\UpdController::class, 'accept'])->name('accept');
        Route::post('/{upd}/reject', [\App\Http\Controllers\Admin\UpdController::class, 'reject'])->name('reject');
        Route::delete('/{upd}', [\App\Http\Controllers\Admin\UpdController::class, 'destroy'])->name('destroy');
        // Новый маршрут для генерации УПД из шаблона
        Route::get('/{upd}/generate-from-template', [\App\Http\Controllers\Admin\UpdController::class, 'generateFromTemplate'])->name('generate-from-template');
    });

    // Документы
    Route::prefix('admin/documents')->name('admin.documents.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\DocumentController::class, 'index'])->name('index');
        Route::get('/{type}/{id}', [\App\Http\Controllers\Admin\DocumentController::class, 'show'])->name('show');

        // Дополнительные маршруты для конкретных действий с документами
        Route::prefix('upds')->name('upds.')->group(function () {
            Route::post('/{upd}/verify-paper', [\App\Http\Controllers\Admin\UpdController::class, 'verifyPaper'])->name('verify-paper');
            Route::post('/{upd}/accept', [\App\Http\Controllers\Admin\UpdController::class, 'accept'])->name('accept');
            Route::post('/{upd}/reject', [\App\Http\Controllers\Admin\UpdController::class, 'reject'])->name('reject');
        });
    });

    // Новый раздел: Настройки
    Route::prefix('settings')->name('admin.settings.')->group(function () {
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

        // Дополнительные маршруты
        Route::get('document-templates/{documentTemplate}/download', [DocumentTemplateController::class, 'download'])
            ->name('document-templates.download');
        Route::get('document-templates/{documentTemplate}/preview', [DocumentTemplateController::class, 'preview'])
            ->name('document-templates.preview');

        Route::get('/{documentTemplate}/generate', [DocumentTemplateController::class, 'generateForm'])->name('generate-form');
        Route::post('/{documentTemplate}/generate', [DocumentTemplateController::class, 'generate'])->name('generate');
    });

    Route::prefix('completion-acts')->name('admin.completion-acts.')->group(function () {
        Route::post('/{completionAct}/generate-upd', [CompletionActController::class, 'generateUpd'])
            ->name('generate-upd');
        Route::post('/generate-upd-all', [CompletionActController::class, 'generateUpdForAll'])
            ->name('generate-upd-all');
    });

    // Финансовый раздел
    Route::prefix('finance')->name('admin.finance.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\FinanceController::class, 'dashboard'])->name('dashboard');
        Route::get('/transactions', [\App\Http\Controllers\Admin\FinanceController::class, 'transactions'])->name('transactions');
        Route::get('/transactions/{transaction}', [\App\Http\Controllers\Admin\FinanceController::class, 'showTransaction'])->name('transactions.show');
        Route::post('/transactions/{transaction}/cancel', [\App\Http\Controllers\Admin\FinanceController::class, 'cancelTransaction'])->name('transactions.cancel');
        Route::get('/invoices', [\App\Http\Controllers\Admin\FinanceController::class, 'invoices'])->name('invoices');
        Route::get('/invoices/{invoice}', [\App\Http\Controllers\Admin\FinanceController::class, 'showInvoice'])->name('invoices.show');
    });

    // Банковские выписки
    Route::prefix('bank-statements')->name('admin.bank-statements.')->group(function () {
        Route::get('/', [BankStatementController::class, 'index'])->name('index');
        Route::get('/create', [BankStatementController::class, 'create'])->name('create');
        Route::post('/', [BankStatementController::class, 'store'])->name('store');

        // Маршруты без параметров должны быть ВЫШЕ маршрутов с параметрами
        Route::get('/pending', [BankStatementController::class, 'pendingTransactions'])->name('pending');
        Route::post('/process-refund', [BankStatementController::class, 'processRefund'])->name('process-refund');

        // Маршруты с параметрами должны быть НИЖЕ
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
});

// Профиль пользователя

Route::middleware('auth')->group(function () {
    // Основной маршрут профиля
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');

    // Обновление персональных данных (стандартный Breeze)
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // 🔥 ДОБАВЛЯЕМ МАРШРУТ ДЛЯ БАНКОВСКИХ РЕКВИЗИТОВ
    Route::patch('/profile/bank-details', [ProfileController::class, 'updateBankDetails'])
         ->name('profile.bank-details.update');

    // Экспорт в PDF
    Route::get('/profile/export-pdf', [ProfileController::class, 'exportToPdf'])
         ->name('profile.export.pdf');

    // Существующие маршруты
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Маршруты для уведомлений
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('markAllAsRead');
        Route::post('/{notification}/mark-as-read', [NotificationController::class, 'markAsRead'])->name('markAsRead');
    });

    // Добавьте этот отдельный маршрут для совместимости со старым кодом
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications');
});

require __DIR__.'/auth.php';
