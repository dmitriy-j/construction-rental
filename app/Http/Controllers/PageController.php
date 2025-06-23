<?php

namespace App\Http\Controllers;

class PageController extends Controller
{
    public function about()
    {
        return view('pages.about');
    }

    public function contacts()
    {
        return view('pages.contacts');
    }

    public function cooperation()
    {
        return view('pages.cooperation');
    }

    public function vacancies()
    {
        return view('pages.vacancies');
    }
}
