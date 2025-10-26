<?php
// app/Http/Controllers/ContactController.php

namespace App\Http\Controllers;

use App\Models\Platform;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(): View
    {
        $platform = Platform::getMain();
        return view('pages.contacts', compact('platform'));
    }
}
