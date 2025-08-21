<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CompanyEmployeeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Catalog\CatalogController;
use App\Http\Controllers\Catalog\Equipment\EquipmentController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\Lessor\DashboardController as LessorDashboardController;
use App\Http\Controllers\Lessor\LessorOrderController as LessorOrders;
use App\Http\Controllers\Lessee\DashboardController as LesseeDashboardController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\AdminNewsController;
use App\Http\Controllers\Admin\AdminEquipmentController;
use App\Http\Controllers\Admin\AdminLesseeController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminLessorController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrdersController;
use App\Http\Controllers\RentalConditionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Lessee\OrderController;
use App\Http\Controllers\DeliveryNoteExportController;
use App\Http\Controllers\Lessor\WaybillController;
use App\Http\Controllers\Lessor\ShiftController;
use App\Http\Controllers\Lessor\OperatorController;
use App\Http\Controllers\Lessor\DeliveryNoteController;
use App\Http\Controllers\Lessor\EquipmentController as LessorEquipmentController;



// Главная страница
Route::get('/', function () {
    return view('home');
})->name('home');

// Каталог
Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/catalog/{equipment}', [CatalogController::class, 'show'])->name('catalog.show');

// Статические страницы
Route::get('/free', fn() => view('free'))->name('free');
Route::get('/cooperation', fn() => view('cooperation'))->name('cooperation');
Route::get('/jobs', fn() => view('jobs'))->name('jobs');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contacts', [PageController::class, 'contacts'])->name('contacts');

// Публичные роуты новостей
Route::get('/news', [NewsController::class, 'index'])->name('news.index');
Route::get('/news/{news:slug}', [NewsController::class, 'show'])->name('news.show');

// Регистрация и аутентификация
Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');

// Личный кабинет компании
Route::prefix('/company')
    ->middleware(['auth', 'role:company_admin'])
    ->group(function () {
        Route::get('/dashboard', fn() => view('company.dashboard'))->name('company.dashboard');
        Route::resource('employees', CompanyEmployeeController::class)
            ->names([
                'index' => 'company.employees.index',
                'create' => 'company.employees.create',
                'store' => 'company.employees.store',
                'show' => 'company.employees.show',
                'edit' => 'company.employees.edit',
                'update' => 'company.employees.update',
                'destroy' => 'company.employees.destroy'
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

           // Оборудование (ИСПРАВЛЕНО: используем контроллер из Lessor)
        Route::resource('equipment', LessorEquipmentController::class)
            ->names([
                'index' => 'equipment.index',
                'create' => 'equipment.create',
                'store' => 'equipment.store',
                'show' => 'equipment.show',
                'edit' => 'equipment.edit',
                'update' => 'equipment.update',
                'destroy' => 'equipment.destroy'
            ]);

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
            Route::post('{order}/prepare-shipment', [LessorOrderController::class, 'prepareForShipment'])->name('prepare-shipment');
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
        Route::prefix('delivery-notes')->name('delivery-notes.')->group(function() {
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
            Route::get('{waybill}/shifts', [WaybillController::class, 'getShifts']) ->name('shifts');

        });


        // Смены
        Route::prefix('shifts')->name('shifts.')->group(function () {
            Route::put('{shift}', [ShiftController::class, 'update'])->name('update');
            Route::delete('{shift}', [ShiftController::class, 'destroy'])->name('destroy');
        });
    });


// Для арендатора
Route::prefix('lessee')
    ->middleware(['auth', 'company.verified', 'company.lessee'])
    ->group(function () {
        // Дашборд
        Route::get('/dashboard', [LesseeDashboardController::class, 'index'])->name('lessee.dashboard');

        // Заказы
        Route::prefix('orders')->group(function () {
            Route::get('/', [\App\Http\Controllers\Lessee\OrderController::class, 'index'])->name('lessee.orders.index');
            Route::get('/{order}', [\App\Http\Controllers\Lessee\OrderController::class, 'show'])->name('lessee.orders.show');
            Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('lessee.orders.cancel');
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

        // Оформление заказа
        Route::post('/checkout', [CheckoutController::class, 'checkout'])->name('checkout');
    });

    // Загрузка УПД (общий доступ)
    Route::get('/orders/{order}/upd/{type}', [OrderController::class, 'downloadUPDF']);


    //Для перевозчика
    Route::middleware(['auth', 'verified', 'carrier'])->prefix('carrier')->group(function () {
        Route::get('/dashboard', [CarrierDashboardController::class, 'index'])->name('carrier.dashboard');
        Route::get('/orders', [CarrierOrderController::class, 'index'])->name('carrier.orders.index');
        Route::get('/orders/{order}', [CarrierOrderController::class, 'show'])->name('carrier.orders.show');
        Route::post('/orders/{order}/accept', [CarrierOrderController::class, 'accept'])->name('carrier.orders.accept');
        Route::post('/orders/{order}/complete', [CarrierOrderController::class, 'complete'])->name('carrier.orders.complete');
    });


// Админ кабинет
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/equipment', [AdminEquipmentController::class, 'index'])->name('admin.equipment.index');
    Route::get('/equipment/approve/{equipment}', [AdminEquipmentController::class, 'approve'])->name('admin.equipment.approve');
    Route::get('/equipment/reject/{equipment}', [AdminEquipmentController::class, 'reject'])->name('admin.equipment.reject');
    Route::get('/admin/equipment/{id}', [AdminEquipmentController::class, 'show'])->name('admin.equipment.show');
    Route::get('/lessees', [AdminLesseeController::class, 'index'])->name('admin.lessees.index');
    Route::get('/lessees/{lessee}', [AdminLesseeController::class, 'show'])->name('admin.lessees.show');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('admin.orders.show');
    Route::get('/lessees/{lessee}/orders/{order}', [AdminLesseeController::class, 'showOrder'])->name('admin.lessees.orders.show');
    Route::get('/lessors', [AdminLessorController::class, 'index'])->name('admin.lessors.index');
    Route::get('/lessors/{lessor}', [AdminLessorController::class, 'show'])->name('admin.lessors.show');
    Route::get('/lessors/{lessor}/orders/{order}', [AdminLessorController::class, 'showOrder'])->name('admin.lessors.orders.show');
    Route::put('/equipment/{equipment}', [AdminEquipmentController::class, 'update'])->name('admin.equipment.update');
    Route::resource('orders', OrdersController::class);
    Route::resource('news', \App\Http\Controllers\Admin\AdminNewsController::class)->names([
        'index' => 'admin.news.index',
        'create' => 'admin.news.create',
        'store' => 'admin.news.store',
        'edit' => 'admin.news.edit',
        'update' => 'admin.news.update',
        'destroy' => 'admin.news.destroy'
    ]);
});

// Профиль пользователя
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Уведомления
Route::get('/notifications', [NotificationController::class, 'index'])
    ->middleware('auth')
    ->name('notifications');

require __DIR__.'/auth.php';
