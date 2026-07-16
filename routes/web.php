<?php

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\Catalog\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CompanyEmployeeController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RentalConditionController;
use App\Http\Controllers\RentalRequestController;
use App\Http\Controllers\NewsController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\UpdController;
use App\Http\Controllers\Api\CatalogApiController;
use App\Http\Controllers\Api\CartApiController;
use App\Http\Controllers\Api\ProposalCartController;
use App\Http\Controllers\Api\OrderApiController;

// Главная страница
Route::get('/', function () { return view('home'); })->name('home');

// Заявки
Route::get('/requests', [RentalRequestController::class, 'index'])->name('rental-requests.index');
Route::get('/portal/rental-requests/{id}', function ($id) {
    return view('public.rental-request-show', ['rentalRequestId' => $id]);
})->name('portal.rental-requests.show');
Route::get('/contacts', [PageController::class, 'contacts'])->name('pages.contacts');

// Каталог
Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/catalog/{equipment}', [CatalogController::class, 'show'])->name('catalog.show');

// API каталога
Route::prefix('api')->name('api.')->group(function () {
    Route::get('/equipment', [CatalogApiController::class, 'index'])->name('equipment.index');
    Route::get('/equipment/autocomplete', [CatalogApiController::class, 'index'])->name('equipment.autocomplete');
    Route::get('/equipment/{equipment}', [CatalogApiController::class, 'show'])->name('equipment.show');
    Route::get('/equipment/{equipment}/price', [CatalogApiController::class, 'price'])->name('equipment.price');
    Route::get('/equipment/{equipment}/availability', [CatalogApiController::class, 'availability'])->name('equipment.availability');
    // API корзины
    Route::prefix('cart')->name('cart.')->group(function () {
        Route::get('/', [CartApiController::class, 'index'])->name('index');
        Route::post('/', [CartApiController::class, 'store'])->name('store');
        Route::put('/{id}', [CartApiController::class, 'update'])->name('update');
        Route::delete('/{id}', [CartApiController::class, 'destroy'])->name('destroy');
        Route::post('/destroy-batch', [CartApiController::class, 'destroyBatch'])->name('destroy-batch');
    });
    // API корзины заявок
    Route::prefix('proposal-cart')->name('proposal-cart.')->group(function () {
        Route::get('/', [ProposalCartController::class, 'index'])->name('index');
        Route::post('/', [ProposalCartController::class, 'store'])->name('store');
        Route::delete('/{id}', [ProposalCartController::class, 'destroy'])->name('destroy');
    });
    // API заказов
    Route::post('/orders', [OrderApiController::class, 'store'])->name('orders.store');
    Route::post('/orders/proposal', [OrderApiController::class, 'storeFromProposal'])->name('orders.proposal');
    // Очистка битых позиций в корзине
    Route::post('/cleanup-cart', [\App\Http\Controllers\Api\CartCleanupController::class, 'cleanup'])->name('cleanup-cart');
});

// Статические страницы
Route::get('/free', fn () => view('free'))->name('free');
Route::get('/cooperation', fn () => view('cooperation'))->name('cooperation');
Route::get('/jobs', fn () => view('jobs'))->name('jobs');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contacts', [PageController::class, 'contacts'])->name('contacts');
Route::get('/news', [NewsController::class, 'index'])->name('news.index');
Route::get('/news/{news:slug}', [NewsController::class, 'show'])->name('news.show');
Route::get('/repair', function () { return view('repair'); })->name('repair');

// Регистрация
Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');

// Компания
Route::prefix('/company')->middleware(['auth', 'role:company_admin'])->group(function () {
    Route::get('/dashboard', fn () => view('company.dashboard'))->name('company.dashboard');
    Route::resource('employees', CompanyEmployeeController::class)->names([
        'index' => 'company.employees.index', 'create' => 'company.employees.create',
        'store' => 'company.employees.store', 'show' => 'company.employees.show',
        'edit' => 'company.employees.edit', 'update' => 'company.employees.update',
        'destroy' => 'company.employees.destroy',
    ]);
});

// Lessor/Lessee routes
Route::prefix('lessor')->middleware(['auth', 'company.verified', 'company.lessor'])->name('lessor.')->group(base_path('routes/lessor.php'));
Route::prefix('lessee')->middleware(['auth', 'company.verified', 'company.lessee'])->group(base_path('routes/lessee.php'));

Route::get('/orders/{order}/upd/{type}', [\App\Http\Controllers\Lessee\OrderController::class, 'downloadUPDF']);

// Профиль
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/company-legal-details', [ProfileController::class, 'updateCompanyLegalDetails'])->name('profile.company-details.update');
    Route::patch('/profile/bank-details', [ProfileController::class, 'updateBankDetails'])->name('profile.bank-details.update');
    Route::get('/profile/export-pdf', [ProfileController::class, 'exportToPdf'])->name('profile.export.pdf');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('markAllAsRead');
        Route::post('/{notification}/mark-as-read', [NotificationController::class, 'markAsRead'])->name('markAsRead');
    });
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications');
});

require __DIR__.'/auth.php';
