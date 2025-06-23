<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function index()
    {
        $news = News::latest()->paginate(10);
        return view('admin.news.index', compact('news'));
    }

    public function create()
    {
        return view('admin.news.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'publish_date' => 'required|date',
            'is_published' => 'boolean'
        ]);

        // Автоматическая генерация slug
        $validated['slug'] = Str::slug($validated['title']);

        News::create($validated);

        return redirect()->route('admin.news.index')
                         ->with('success', 'News created successfully');
    }

    public function edit(News $news)
    {
        return view('admin.news.edit', compact('news'));
    }

    public function update(Request $request, News $news)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'publish_date' => 'required|date',
            'is_published' => 'boolean'
        ]);


         // Обновляем slug только если изменился заголовок
        if ($request->title !== $news->title) {
            $validated['slug'] = Str::slug($validated['title']);
        }


        $news->update($validated);

        return redirect()->route('admin.news.index')
                         ->with('success', 'News updated successfully');
    }

    public function destroy(News $news)
    {
        $news->delete();
        return redirect()->route('admin.news.index')
                         ->with('success', 'News deleted successfully');
    }
}
