<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;

class AdminNewsController extends Controller
{
    public function index()
    {
        $news = News::latest()->paginate(10);
        return view('admin.news.index', compact('news'));
    }

    public function show($id)
{
    $news = News::findOrFail($id);
    return view('admin.news.show', compact('news'));
}

    public function create()
    {
        return view('admin.news.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'content' => 'required',
            'publish_date' => 'required|date',
        ]);

        News::create($validated + [
            'author_id' => auth()->id(),
            'is_published' => $request->has('is_published')
        ]);

        return redirect()->route('news.index')->with('success', 'Новость создана!');
    }

    public function edit(News $news)
    {
        return view('admin.news.edit', compact('news'));
    }

    public function update(Request $request, News $news)
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'content' => 'required',
            'publish_date' => 'required|date',
        ]);

        $news->update($validated + [
            'is_published' => $request->has('is_published')
        ]);

        return redirect()->route('news.index')->with('success', 'Новость обновлена!');
    }

    public function destroy(News $news)
    {
        $news->delete();
        return redirect()->route('news.index')->with('success', 'Новость удалена!');
    }
}