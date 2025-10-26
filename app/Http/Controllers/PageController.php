<?php

namespace App\Http\Controllers;

use App\Models\Platform;
use Illuminate\View\View;

class PageController extends Controller
{
    public function about(): View
    {
        return view('pages.about');
    }

    public function contacts(): View
    {
        $platform = Platform::getMain();
        return view('pages.contacts', compact('platform'));
    }

    public function cooperation(): View
    {
        return view('pages.cooperation');
    }

    public function vacancies(): View
    {
        return view('pages.vacancies');
    }
}
