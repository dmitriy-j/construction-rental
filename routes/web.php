<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CompanyEmployeeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Catalog\CatalogController;
use App\Http\Controllers\Catalog\Equipment\EquipmentController;
use App\Http\Controllers\Catalog\EquipmentImageController;
use App\Http\Controllers\Catalog\EquipmentRentalController;
use App\Http\Controllers\Catalog\EquipmentReviewController;
use App\Http\Controllers\Catalog\EquipmentFavoriteController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Lessor\DashboardController as LessorDashboardController;
use App\Http\Controllers\Lessee\DashboardController as LesseeDashboardController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

// Главная страница
Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('/login', [AuthenticatedSessionController::class, 'store']);

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

Route::get('/debug/login-test', [\App\Http\Controllers\Auth\DebugAuthController::class, 'loginTest']);
Route::get('/debug/check-auth', [\App\Http\Controllers\Auth\DebugAuthController::class, 'checkAuth']);

// Для арендодателя
Route::prefix('lessor')
    ->middleware(['auth', 'company.verified', 'company.lessor'])->group(function () {
    Route::get('/dashboard', [LessorDashboardController::class, 'index'])->name('lessor.dashboard');
    Route::get('/equipment', [LessorDashboardController::class, 'equipment'])->name('lessor.equipment');
    Route::get('/orders', [LessorDashboardController::class, 'orders'])->name('lessor.orders');
    Route::get('/documents', [LessorDashboardController::class, 'documents'])->name('lessor.documents');
});

// Для арендатора
Route::prefix('lessee')
    ->middleware(['auth', 'company.verified', 'company.lessee'])->group(function () {
    Route::get('/dashboard', [LesseeDashboardController::class, 'index'])->name('lessee.dashboard');
    Route::get('/orders', [LesseeDashboardController::class, 'orders'])->name('lessee.orders');
    Route::get('/documents', [LesseeDashboardController::class, 'documents'])->name('lessee.documents');
});

// Корзина
Route::middleware('auth')->group(function () {
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
    Route::delete('/cart/remove/{item}', [CartController::class, 'remove'])->name('cart.remove');
    Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');

    // Оформление заказа
    Route::post('/checkout', [CheckoutController::class, 'checkout'])->name('checkout');
});

// Заказы
Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');

// Загрузка УПД
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

require __DIR__.'/auth.php';