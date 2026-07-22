<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $subjectText;
    public string $title;
    public array $data;
    public string $viewTemplate;
    public string $actionUrl;
    public string $actionText;

    public function __construct(
        string $subject,
        string $title,
        array $data,
        string $viewTemplate = 'emails.admin-notification',
        string $actionUrl = '',
        string $actionText = 'Перейти в админ-панель'
    ) {
        $this->subjectText = $subject;
        $this->title = $title;
        $this->data = $data;
        $this->viewTemplate = $viewTemplate;
        $this->actionUrl = $actionUrl;
        $this->actionText = $actionText;
    }

    public function build()
    {
        return $this->subject($this->subjectText)
            ->view($this->viewTemplate)
            ->with([
                'title' => $this->title,
                'data' => $this->data,
                'actionUrl' => $this->actionUrl,
                'actionText' => $this->actionText,
            ]);
    }
}
