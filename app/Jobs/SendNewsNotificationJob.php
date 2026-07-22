<?php

namespace App\Jobs;

use App\Mail\NewsNotificationMail;
use App\Models\News;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNewsNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public News $news;
    public int $userId;

    public function __construct(News $news, int $userId)
    {
        $this->news = $news;
        $this->userId = $userId;
    }

    public function handle(): void
    {
        try {
            $user = User::find($this->userId);
            if (!$user || !$user->email) {
                return;
            }

            Mail::to($user->email)->send(new NewsNotificationMail(
                $this->news,
                $user->hasVerifiedEmail() ? route('home') : ''
            ));

            Log::info("News notification sent to user #{$this->userId}", [
                'news_id' => $this->news->id,
                'email' => $user->email,
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to send news notification to user #{$this->userId}", [
                'error' => $e->getMessage(),
                'news_id' => $this->news->id,
            ]);
        }
    }
}
