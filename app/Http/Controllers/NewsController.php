<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function index()
    {
        $news = News::orderBy('publish_date', 'desc')->paginate(10);
        return view('news.index', compact('news'));
    }

    public function show(News $news)
    {
        return view('news.show', compact('news'));
    }

    // Для админки (позже добавим middleware)
    public function create() { /* ... */ }
    public function store(Request $request) { /* ... */ }
    public function edit(News $news) { /* ... */ }
    public function update(Request $request, News $news) { /* ... */ }
    public function destroy(News $news) { /* ... */ }
}
