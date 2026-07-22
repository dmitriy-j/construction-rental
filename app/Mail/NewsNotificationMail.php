<?php

namespace App\Mail;

use App\Models\News;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewsNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public News $news;
    public string $unsubscribeUrl;

    public function __construct(News $news, string $unsubscribeUrl = '')
    {
        $this->news = $news;
        $this->unsubscribeUrl = $unsubscribeUrl;
    }

    public function build()
    {
        return $this->subject($this->news->title)
            ->view('emails.news-notification')
            ->with([
                'news' => $this->news,
                'unsubscribeUrl' => $this->unsubscribeUrl,
                'actionUrl' => route('news.show', $this->news->slug),
                'actionText' => 'Читать новость',
            ]);
    }
}
