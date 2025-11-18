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

// Подключение маршрутов арендодателя
Route::prefix('lessor')
    ->middleware(['auth', 'company.verified', 'company.lessor'])
    ->name('lessor.')
    ->group(base_path('routes/lessor.php'));

// Подключение маршрутов арендатора
Route::prefix('lessee')
    ->middleware(['auth', 'company.verified', 'company.lessee'])
    ->group(base_path('routes/lessee.php'));

// Загрузка УПД (общий доступ)
Route::get('/orders/{order}/upd/{type}', [\App\Http\Controllers\Lessee\OrderController::class, 'downloadUPDF']);

// Профиль пользователя
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // ✅ ДОБАВЬТЕ ЭТОТ МАРШРУТ
    Route::put('/profile/company-legal-details', [ProfileController::class, 'updateCompanyLegalDetails'])
         ->name('profile.company-details.update');

    Route::patch('/profile/bank-details', [ProfileController::class, 'updateBankDetails'])
         ->name('profile.bank-details.update');
    Route::get('/profile/export-pdf', [ProfileController::class, 'exportToPdf'])
         ->name('profile.export.pdf');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Уведомления
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('markAllAsRead');
        Route::post('/{notification}/mark-as-read', [NotificationController::class, 'markAsRead'])->name('markAsRead');
    });

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications');
});

Route::get('/debug/companies', function() {
    $companies = \App\Models\Company::where(function($q) {
        $q->where('is_lessee', true)->orWhere('is_lessor', true);
    })->get();

    return [
        'total' => $companies->count(),
        'companies' => $companies->map(function($company) {
            return [
                'id' => $company->id,
                'legal_name' => $company->legal_name,
                'name' => $company->name ?? 'NULL',
                'is_lessee' => $company->is_lessee,
                'is_lessor' => $company->is_lessor,
                'status' => $company->status
            ];
        })
    ];
});

require __DIR__.'/auth.php';
