<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class DebugAuthController extends Controller
{
    public function loginTest(Request $request)
    {
        // Тестовые учетные данные
        $credentials = [
            'email' => 'shaun.green@example.com',
            'password' => '123456789' // Замените на реальный
        ];

        Log::info('Starting authentication test', $credentials);
        
        // Попытка аутентификации
        if (Auth::attempt($credentials)) {
            Log::info('Auth::attempt SUCCESS', [
                'user_id' => Auth::id(),
                'session_id' => session()->getId()
            ]);
            
            return response()->json([
                'status' => 'success',
                'user' => Auth::user(),
                'session' => session()->all()
            ]);
        }
        
        Log::error('Auth::attempt FAILED', $credentials);
        return response()->json(['status' => 'failed'], 401);
    }

    public function checkAuth()
    {
        return response()->json([
            'authenticated' => Auth::check(),
            'user' => Auth::user(),
            'session_id' => session()->getId()
        ]);
    }
}