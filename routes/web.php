<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CompanyEmployeeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Catalog\CatalogController;
use App\Http\Controllers\Catalog\Equipment\EquipmentController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\Lessor\DashboardController as LessorDashboardController;
use App\Http\Controllers\Lessor\OrderController as LessorOrderController;
use App\Http\Controllers\Lessee\DashboardController as LesseeDashboardController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

// Главная страница
Route::get('/', function () {
    return view('home');
})->name('home');

//Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
//Route::post('/login', [AuthenticatedSessionController::class, 'store']);

// Каталог
Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog');
Route::get('/catalog/{equipment}', [CatalogController::class, 'show'])->name('catalog.show');

// Статические страницы
Route::get('/free', fn() => view('free'))->name('free');
Route::get('/cooperation', fn() => view('cooperation'))->name('cooperation');
Route::get('/jobs', fn() => view('jobs'))->name('jobs');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contacts', [PageController::class, 'contacts'])->name('contacts');

// Регистрация и аутентификация
Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');

// Личный кабинет компании
Route::prefix('/company')
    ->middleware(['auth', 'role:company_admin'])
    ->group(function () {
        Route::get('/dashboard', fn() => view('company.dashboard'))->name('company.dashboard');
        Route::resource('employees', CompanyEmployeeController::class);
    });

// Для арендодателя
Route::prefix('lessor')
    ->middleware(['auth', 'company.verified', 'company.lessor'])
    ->group(function () {
        // Дашборд
        Route::get('/dashboard', [LessorDashboardController::class, 'index'])->name('lessor.dashboard');
        
        // Оборудование
        Route::get('/equipment', [LessorDashboardController::class, 'equipment'])->name('lessor.equipment');
        
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
        
        // Корзина
        Route::get('/cart', [CartController::class, 'index'])->name('lessee.cart.index');
        Route::post('/cart/add/{rentalTerm}', [CartController::class, 'add'])->name('lessee.cart.add');
        Route::delete('/cart/remove/{item}', [CartController::class, 'remove'])->name('lessee.cart.remove');
        Route::post('/cart/update-dates', [CartController::class, 'updateDates'])->name('lessee.cart.updateDates');
    });

// Оформление заказа (только для арендатора)
Route::post('/checkout', [CheckoutController::class, 'checkout'])
    ->middleware(['auth', 'company.verified', 'company.lessee'])
    ->name('checkout');

// Загрузка УПД (общий доступ)
Route::get('/orders/{order}/upd/{type}', [OrderController::class, 'downloadUPDF']);

// Админ-панель платформы
Route::prefix('adm')
    ->group(function () {
        // Вход
        Route::get('/login', [\App\Http\Controllers\Admin\AdminController::class, 'loginForm'])
            ->name('admin.login.form');
        Route::post('/login', [\App\Http\Controllers\Admin\AdminController::class, 'login'])
            ->name('admin.login');

        // Защищенные маршруты
        Route::middleware('auth:admin')->group(function () {
            Route::post('/logout', [\App\Http\Controllers\Admin\AdminController::class, 'logout'])
                ->name('admin.logout');

            Route::get('/dashboard', [\App\Http\Controllers\Admin\AdminController::class, 'dashboard'])
                ->name('admin.dashboard');

            // Управление сотрудниками платформы
            Route::resource('employees', \App\Http\Controllers\Admin\EmployeeController::class)
                ->except(['show']);

            // Управление новостями
            Route::resource('news', \App\Http\Controllers\Admin\NewsController::class)
                ->except(['show']);
        });
    });

    // Личный кабинет (защищён auth и ролями)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/tenant-dashboard', fn() => view('tenant'))->name('tenant.dashboard')->middleware('role:tenant');
    Route::get('/landlord-dashboard', fn() => view('landlord'))->name('landlord.dashboard')->middleware('role:landlord');
});

// Профиль пользователя
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/notifications', [NotificationController::class, 'index'])
    ->middleware('auth')
    ->name('notifications');

require __DIR__.'/auth.php';