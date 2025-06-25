<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompanyRegistrationController;
use App\Models\Company;
use App\Models\User;
use App\Mail\CompanyRegisteredMail;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Admin\NewsController as AdminNewsController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('home');
});

Route::get('/catalog', function () {
    return view('catalog');
})->name('catalog');

Route::get('/free', function () {
    return view('free');
})->name('free');

Route::get('/cooperation', function () {
    return view('cooperation');
})->name('cooperation');

Route::get('/jobs', function () {
    return view('jobs');
})->name('jobs');

// Маршрут для tenant dashboard
Route::get('/tenant/dashboard', function () {
    return 'Dashboard';
})->name('tenant.dashboard');


// Авторизация для всех пользователей
Route::post('/login', [AuthenticatedSessionController::class, 'store']);

// Dashboard для арендатора
Route::get('/tenant/dashboard', function () {
    return view('tenant.dashboard');
})->name('tenant.dashboard')->middleware(['auth', 'type:tenant']);

// Админ-панель для сотрудников
Route::prefix('admin')->middleware(['auth', 'type:staff', 'role:admin'])->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
});



Route::get('/register/company', [CompanyRegistrationController::class, 'create'])
    ->name('register.company');

Route::post('/register/company', [CompanyRegistrationController::class, 'store'])
    ->name('register.company.store');

Route::get('/test-email', function() {
    $company = Company::first();
    $user = User::first();
    Mail::to($user->email)->send(new CompanyRegisteredMail($company, $user));
    return "Email sent!";
});

Route::get('/about', fn() => view('pages.about'));
Route::get('/requests', fn() => view('requests'));


//Route::post('/register/company', [CompanyRegistrationController::class, 'store'])
   // ->name('register.company.store');

// Маршруты аутентификации
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Новости - только просмотр
Route::get('/news', [NewsController::class, 'index'])->name('news.index');
Route::get('/news/{news}', [NewsController::class, 'show'])->name('news.show');

// Статические страницы
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contacts', [PageController::class, 'contacts'])->name('contacts');

// Административные маршруты для управления новостями
Route::prefix('adm')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('news', AdminNewsController::class)->except(['show']);
});

require __DIR__.'/auth.php';
