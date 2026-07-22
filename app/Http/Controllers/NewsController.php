<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Support\Facades\Cache;

class NewsController extends Controller
{
    public function index()
    {
        $news = Cache::remember('news_published', 300, function () {
            return News::published()
                ->with('author')
                ->latest('published_at')
                ->latest('created_at')
                ->paginate(12);
        });

        return view('news.index', compact('news'));
    }

    public function show(string $slug)
    {
        $newsItem = News::where('slug', $slug)
            ->published()
            ->firstOrFail();

        $newsItem->increment('views_count');

        return view('news.show', compact('newsItem'));
    }

    /**
     * Блок последних новостей для главной страницы.
     */
    public static function getLatest(int $limit = 4)
    {
        return Cache::remember('news_latest', 300, function () use ($limit) {
            return News::published()
                ->latest('published_at')
                ->latest('created_at')
                ->take($limit)
                ->get();
        });
    }

    /**
     * Новости для личного кабинета (фильтр по роли пользователя).
     */
    public function cabinet()
    {
        $user = auth()->user();
        $news = News::published()
            ->visibleFor($user)
            ->latest('published_at')
            ->latest('created_at')
            ->paginate(15);

        return view('news.cabinet', compact('news'));
    }
}
