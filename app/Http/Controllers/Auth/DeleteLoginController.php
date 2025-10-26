<?php
/*
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/home';

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    protected function authenticated(Request $request, $user)
    {
        if ($user->isPlatformAdmin()) {
            return redirect()->route('admin.dashboard');
        }
        
        if ($user->company) {
            if ($user->company->is_lessor) {
                return redirect()->route('lessor.dashboard');
            }
            
            if ($user->company->is_lessee) {
                return redirect()->route('lessee.dashboard');
            }
        }
        
        return redirect($this->redirectTo);
    }
}