<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RentalRequestController extends Controller
{
    /**
     * Display a listing of the rental requests.
     */
    public function index()
    {
        // Этот метод будет обрабатывать страницу /requests
        return view('rental-requests.index');
    }
}
