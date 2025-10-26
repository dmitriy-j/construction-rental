<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->input('q');

        $results = News::search($query)->get();

        return view('search.results', [
            'results' => $results,
            'query' => $query,
        ]);
    }
}
