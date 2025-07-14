<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CompanyEmployeeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Catalog\CatalogController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\Lessor\DashboardController as LessorDashboardController;
use App\Http\Controllers\Lessor\OrderController as LessorOrderController;
use App\Http\Controllers\Lessee\DashboardController as LesseeDashboardController;
<<<<<<< HEAD
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\Admin\AdminNewsController;



//use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\DashboardController;
//use App\Http\Controllers\Admin\EquipmentController;
use App\Http\Controllers\Admin\OrdersController;

=======
>>>>>>> 79881ea0251f8c67829e39a1935771c9c3f29440

// Главная страница
Route::get('/', function () {
    return view('home');
})->name('home');

// Каталог
Route::get('/catalog', [App\Http\Controllers\Catalog\CatalogController::class, 'index'])
    ->name('catalog.index');

Route::get('/catalog/{equipment}', [App\Http\Controllers\Catalog\CatalogController::class, 'show'])
    ->name('catalog.show');

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

        // Оборудование - только ресурсный маршрут
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
        Route::get('/orders', [LessorDashboardController::class, 'orders'])->name('lessor.orders');
        Route::get('/orders/{order}', [LessorOrderController::class, 'show'])->name('lessor.orders.show');
        Route::post('/orders/{order}/update-status', [LessorOrderController::class, 'updateStatus'])->name('lessor.orders.updateStatus');
        Route::post('/orders/{order}/mark-active', [LessorOrderController::class, 'markAsActive'])->name('lessor.orders.markActive');
        Route::post('/orders/{order}/mark-completed', [LessorOrderController::class, 'markAsCompleted'])->name('lessor.orders.markCompleted');
        Route::post('/orders/{order}/handle-extension', [LessorOrderController::class, 'handleExtension'])->name('lessor.orders.handleExtension');

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
        Route::get('/orders', [LesseeDashboardController::class, 'orders'])->name('lessee.orders');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('lessee.orders.show');
        Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('lessee.orders.cancel');
        Route::post('/orders/{order}/request-extension', [OrderController::class, 'requestExtension'])->name('lessee.orders.requestExtension');

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
        });

        // Оформление заказа
        Route::post('/checkout', [CheckoutController::class, 'checkout'])->name('checkout');
    });

// Загрузка УПД
Route::get('/orders/{order}/upd/{type}', [OrderController::class, 'downloadUPDF']);

<<<<<<< HEAD
// админ кабинет 
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::resource('equipment', EquipmentController::class);
    Route::resource('orders', OrdersController::class);
    //Route::resource('news', NewsController::class)->only(['index', 'show']);
    Route::resource('news', AdminNewsController::class)->names([
        'index' => 'admin.news.index',
        'create' => 'admin.news.create',
        'store' => 'admin.news.store',
        'edit' => 'admin.news.edit',
        'update' => 'admin.news.update',
        'destroy' => 'admin.news.destroy'
    ]);
    Route::resource('news', AdminNewsController::class)->except(['show']);
});

    // Личный кабинет (защищён auth и ролями)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/tenant-dashboard', fn() => view('tenant'))->name('tenant.dashboard')->middleware('role:tenant');
    Route::get('/landlord-dashboard', fn() => view('landlord'))->name('landlord.dashboard')->middleware('role:landlord');
});
=======
// Админ-панель платформы
Route::prefix('adm')
    ->group(function () {
        Route::get('/login', [\App\Http\Controllers\Admin\AdminController::class, 'loginForm'])
            ->name('admin.login.form');
        Route::post('/login', [\App\Http\Controllers\Admin\AdminController::class, 'login'])
            ->name('admin.login');

        Route::middleware('auth:admin')->group(function () {
            Route::post('/logout', [\App\Http\Controllers\Admin\AdminController::class, 'logout'])
                ->name('admin.logout');

            Route::get('/dashboard', [\App\Http\Controllers\Admin\AdminController::class, 'dashboard'])
                ->name('admin.dashboard');

            Route::resource('employees', \App\Http\Controllers\Admin\EmployeeController::class)
                ->except(['show'])
                ->names([
                    'index' => 'admin.employees.index',
                    'create' => 'admin.employees.create',
                    'store' => 'admin.employees.store',
                    'edit' => 'admin.employees.edit',
                    'update' => 'admin.employees.update',
                    'destroy' => 'admin.employees.destroy'
                ]);

            Route::resource('news', \App\Http\Controllers\Admin\NewsController::class)
                ->except(['show']);
        });
    });
>>>>>>> 79881ea0251f8c67829e39a1935771c9c3f29440

// Профиль пользователя
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])
    ->middleware('auth')
    ->name('notifications');

require __DIR__.'/auth.php';
