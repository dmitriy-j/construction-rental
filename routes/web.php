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
use App\Http\Controllers\NewsController;
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
    ->group(function () {
        // Дашборд
        Route::get('/dashboard', [LessorDashboardController::class, 'index'])->name('lessor.dashboard');
        Route::post('/dashboard/mark-as-viewed', [DashboardController::class, 'markAsViewed'])
    ->name('lessor.dashboard.markAsViewed');

        // Оборудование
        Route::resource('equipment', \App\Http\Controllers\Lessor\EquipmentController::class)
            ->names([
                'index' => 'lessor.equipment.index',
                'create' => 'lessor.equipment.create',
                'store' => 'lessor.equipment.store',
                'show' => 'lessor.equipment.show',
                'edit' => 'lessor.equipment.edit',
                'update' => 'lessor.equipment.update',
                'destroy' => 'lessor.equipment.destroy'
            ]);

        // Заказы
        Route::get('/orders', [LessorOrders::class, 'index'])->name('lessor.orders'); // Обновлено
        Route::get('/orders/{order}', [LessorOrders::class, 'show'])->name('lessor.orders.show'); // Обновлено
        Route::post('/orders/{order}/update-status', [LessorOrders::class, 'updateStatus'])->name('lessor.orders.updateStatus');
        Route::post('/orders/{order}/mark-active', [LessorOrders::class, 'markAsActive'])->name('lessor.orders.markActive');
        Route::post('/orders/{order}/mark-completed', [LessorOrders::class, 'markAsCompleted'])->name('lessor.orders.markCompleted');
        Route::post('/orders/{order}/handle-extension', [LessorOrders::class, 'handleExtension'])->name('lessor.orders.handleExtension');

        // Новые роуты для подтверждения/отклонения заказов
        Route::post('/orders/{order}/approve', [LessorOrders::class, 'approve'])->name('lessor.orders.approve');
        Route::post('/orders/{order}/reject', [LessorOrders::class, 'reject'])->name('lessor.orders.reject');

        // Документы
        Route::get('/documents', [DocumentController::class, 'index'])->name('lessor.documents');
        Route::get('/documents/download/{id}/{type}', [DocumentController::class, 'download'])->name('lessor.documents.download');

        // Документы для заказов
        Route::post('/orders/{order}/delivery-note', [DocumentController::class, 'createDeliveryNote'])->name('lessor.orders.createDeliveryNote');
        Route::post('/orders/{order}/waybill', [DocumentController::class, 'createWaybill'])->name('lessor.orders.createWaybill');
        Route::post('/orders/{order}/completion-act', [DocumentController::class, 'generateCompletionAct'])->name('lessor.orders.generateCompletionAct');
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
            Route::post('/{order}/cancel', [\App\Http\Controllers\Lessee\OrderController::class, 'cancel'])->name('lessee.orders.cancel');
            Route::post('/{order}/request-extension', [\App\Http\Controllers\Lessee\OrderController::class, 'requestExtension'])->name('lessee.orders.requestExtension');
        });
        // Документы
        Route::get('/documents', [DocumentController::class, 'index'])->name('lessee.documents');
        Route::get('/documents/download/{id}/{type}', [DocumentController::class, 'download'])->name('lessee.documents.download');

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
    Route::resource('news', AdminNewsController::class)->names([
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
