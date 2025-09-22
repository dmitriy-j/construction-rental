<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    public function loginForm()
    {
        return view('admin.login'); // Шаблон входа
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $admin = Admin::where('email', $request->email)->first();

        // Проверка статуса
        if ($admin && $admin->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => 'Учетная запись деактивирована',
            ]);
        }

        if (Auth::guard('admin')->attempt($request->only('email', 'password'))) {
            return redirect()->route('admin.dashboard');
        }

        throw ValidationException::withMessages([
            'email' => 'Неверные учетные данные',
        ]);
    }

    public function dashboard()
    {
        return view('admin.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();

        return redirect('/adm/login');
    }
}
