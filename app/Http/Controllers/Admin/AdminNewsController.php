<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendNewsNotificationJob;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AdminNewsController extends Controller
{
    public function index(Request $request)
    {
        $query = News::with('author');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $news = $query->latest()->paginate(20);
        return view('admin.news.index', compact('news'));
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
            'excerpt' => 'nullable|max:500',
            'category' => 'required|in:all,lessee,lessor',
            'is_active' => 'boolean',
            'published_at' => 'nullable|date',
        ]);

        $news = News::create($validated);

        // Рассылка уведомлений
        if ($news->is_active) {
            $this->sendNotifications($news);
        }

        Cache::forget('news_latest');

        return redirect()->route('admin.news.index')
            ->with('success', 'Новость создана!');
    }

    public function show(News $news)
    {
        return view('admin.news.show', compact('news'));
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
            'excerpt' => 'nullable|max:500',
            'category' => 'required|in:all,lessee,lessor',
            'is_active' => 'boolean',
            'published_at' => 'nullable|date',
        ]);

        $wasPublished = !$news->is_active && ($validated['is_active'] ?? false);
        $news->update($validated);

        // Если новость только что опубликована — отправляем уведомления
        if ($wasPublished) {
            $this->sendNotifications($news);
        }

        Cache::forget('news_latest');

        return redirect()->route('admin.news.index')
            ->with('success', 'Новость обновлена!');
    }

    public function destroy(News $news)
    {
        $news->delete();
        Cache::forget('news_latest');
        return redirect()->route('admin.news.index')
            ->with('success', 'Новость удалена!');
    }

    private function sendNotifications(News $news): void
    {
        try {
            $users = $news->getTargetUsers();
            foreach ($users as $user) {
                SendNewsNotificationJob::dispatch($news, $user->id);
            }
            Log::info("News #{$news->id} notifications dispatched to {$users->count()} users");
        } catch (\Throwable $e) {
            Log::error("Failed to dispatch news notifications: " . $e->getMessage());
        }
    }
}
