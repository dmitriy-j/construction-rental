<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\CompanyAuthController;
use App\Models\Company;
use App\Models\User;
use App\Mail\CompanyRegisteredMail;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Admin\NewsController as AdminNewsController;
use App\Http\Controllers\Admin\AdminController;

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
/*Route::get('/tenant/dashboard', function () {
    return view('tenant.dashboard');
})->name('tenant.dashboard')->middleware('auth:company');*/


// Добавить перед маршрутами регистрации
Route::get('/company/login', [CompanyAuthController::class, 'showLoginForm'])
    ->name('company.login.form');

Route::post('/company/login', [CompanyAuthController::class, 'login'])
    ->name('company.login');
//Маршрут выхода
Route::post('/company/logout', [CompanyAuthController::class, 'logout'])
    ->name('company.logout');
// Dashboard для арендатора
Route::get('/tenant/dashboard', function () {
    return view('tenant.dashboard');
})->name('tenant.dashboard')->middleware(['auth', 'type:tenant']);

// Аутентификация
Route::get('/adm/login', [AuthenticatedSessionController::class, 'create'])
    ->name('admin.login');

Route::post('/adm/login', [AuthenticatedSessionController::class, 'store']);

//временный маршрут подключение к БД
Route::get('/test-db', function() {
    try {
        DB::connection()->getPdo();
        return response()->json([
            'status' => 'success',
            'database' => DB::connection()->getDatabaseName()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});


// Админ-панель компании

Route::prefix('adm')->group(function () {
    // Вход
    Route::get('/login', [\App\Http\Controllers\Admin\AdminController::class, 'loginForm'])
        ->name('admin.login.form');

    Route::post('/login', [\App\Http\Controllers\Admin\AdminController::class, 'login'])
        ->name('admin.login');

    // Выход
    Route::post('/logout', [\App\Http\Controllers\Admin\AdminController::class, 'logout'])
        ->name('admin.logout');

    // Защищенные маршруты
    Route::middleware('auth:admin')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Admin\AdminController::class, 'dashboard'])
            ->name('admin.dashboard');
        });
});



Route::prefix('adm')
    ->middleware(['auth:admin']) // Использовать guard 'admin'
    ->name('admin.')
    ->group(function () {
        // Управление сотрудниками
        Route::resource('employees', \App\Http\Controllers\Admin\EmployeeController::class)
            ->except(['show']);

        // Управление новостями
        Route::resource('news', AdminNewsController::class)->except(['show']);

        // Дашборд администратора
        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('dashboard');

        // Дашборд сотрудника
        Route::get('/employee-dashboard', function () {
            return view('admin.employee-dashboard');
        })->name('employee.dashboard');



        // Другие разделы админ-панели...

});
Route::middleware(['auth', 'role:company_admin'])->prefix('company')->group(function () {
    Route::resource('employees', \App\Http\Controllers\Admin\EmployeeController::class)
        ->except(['show']);
});



// Регистрация компании
Route::get('/register/company', [CompanyAuthController::class, 'showRegistrationForm'])
     ->name('register.company');

Route::post('/register/company', [CompanyAuthController::class, 'register'])
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

require __DIR__.'/auth.php';
