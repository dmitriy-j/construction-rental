<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MarkupDailyReport extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Статистика за день
     */
    public array $stats;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $stats)
    {
        $this->stats = $stats;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $date = $this->stats['date'];

        return (new MailMessage)
            ->subject("Ежедневный отчет по наценкам - {$date}")
            ->greeting('Ежедневный отчет по системе наценок')
            ->line("Дата: {$date}")
            ->line("Создано наценок: {$this->stats['created']}")
            ->line("Обновлено наценок: {$this->stats['updated']}")
            ->line("Выполнено расчетов: {$this->stats['calculations']}")
            ->line("Всего изменений: {$this->stats['total_changes']}")
            ->action('Просмотреть статистику', route('admin.markups.statistics'))
            ->line('Это автоматическое уведомление. Вы можете изменить настройки уведомлений в вашем профиле.')
            ->salutation('С уважением, ' . config('app.name'));
    }

    /**
     * Get the array representation for database.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'daily_report',
            'date' => $this->stats['date'],
            'title' => 'Ежедневный отчет по наценкам',
            'message' => $this->getNotificationMessage(),
            'stats' => $this->stats,
            'icon' => 'bi-graph-up',
            'color' => 'info',
            'action_url' => route('admin.markups.statistics'),
            'action_text' => 'Просмотреть статистику',
        ];
    }

    /**
     * Get the notification message
     */
    protected function getNotificationMessage(): string
    {
        return "За {$this->stats['date']}: создано {$this->stats['created']}, обновлено {$this->stats['updated']} наценок";
    }
}
