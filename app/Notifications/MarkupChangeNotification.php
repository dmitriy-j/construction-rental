<?php

namespace App\Notifications;

use App\Models\PlatformMarkup;
use App\Models\PlatformMarkupAudit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

class MarkupChangeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Тип уведомления
     */
    public string $type;

    /**
     * Данные наценки
     */
    public PlatformMarkup $markup;

    /**
     * Данные аудита
     */
    public ?PlatformMarkupAudit $audit;

    /**
     * Дополнительные данные
     */
    public array $data;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $type, PlatformMarkup $markup, ?PlatformMarkupAudit $audit = null, array $data = [])
    {
        $this->type = $type;
        $this->markup = $markup;
        $this->audit = $audit;
        $this->data = $data;

        // Задержка для группировки уведомлений
        $this->delay(now()->addSeconds(30));
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Email уведомления только для важных событий
        if (in_array($this->type, ['created', 'deleted', 'bulk_operation'])) {
            $channels[] = 'mail';
        }

        // Broadcast для реального времени
        if (config('broadcasting.default') !== 'null') {
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->getMailSubject();
        $greeting = $this->getMailGreeting();
        $action = $this->getMailAction();

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($this->getMailMessage())
            ->action($action['text'], $action['url']);

        // Добавляем детали изменений если есть
        if ($this->audit && $this->type === 'updated') {
            $mail->line('Изменения:');

            foreach ($this->getFormattedChanges() as $change) {
                $mail->line("• {$change}");
            }
        }

        // Добавляем причину если указана
        if ($this->audit?->reason) {
            $mail->line("Причина: {$this->audit->reason}");
        }

        // Добавляем информацию о массовых операциях
        if ($this->type === 'bulk_operation' && isset($this->data['processed'])) {
            $mail->line("Обработано наценок: {$this->data['processed']}");
            $mail->line("Успешно: {$this->data['success']}");

            if (isset($this->data['errors']) && $this->data['errors'] > 0) {
                $mail->line("Ошибки: {$this->data['errors']}")
                     ->error();
            }
        }

        return $mail->line('')
            ->salutation('С уважением, ' . config('app.name'));
    }

    /**
     * Get the array representation for database.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => $this->type,
            'markup_id' => $this->markup->id,
            'audit_id' => $this->audit?->id,
            'title' => $this->getNotificationTitle(),
            'message' => $this->getNotificationMessage(),
            'changes' => $this->getFormattedChanges(),
            'reason' => $this->audit?->reason,
            'data' => $this->data,
            'icon' => $this->getNotificationIcon(),
            'color' => $this->getNotificationColor(),
            'action_url' => $this->getActionUrl(),
            'action_text' => $this->getActionText(),
        ];
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'type' => 'markup_change',
            'title' => $this->getNotificationTitle(),
            'message' => $this->getNotificationMessage(),
            'icon' => $this->getNotificationIcon(),
            'color' => $this->getNotificationColor(),
            'timestamp' => now()->toISOString(),
            'markup_id' => $this->markup->id,
            'action_url' => $this->getActionUrl(),
        ]);
    }

    /**
     * Get the notification title
     */
    protected function getNotificationTitle(): string
    {
        return match($this->type) {
            'created' => 'Создана новая наценка',
            'updated' => 'Обновлена наценка',
            'deleted' => 'Удалена наценка',
            'activated' => 'Наценка активирована',
            'deactivated' => 'Наценка деактивирована',
            'expired' => 'Срок действия наценки истек',
            'bulk_operation' => 'Выполнены массовые операции',
            'calculation_error' => 'Ошибка расчета наценки',
            default => 'Изменение наценки'
        };
    }

    /**
     * Get the notification message
     */
    protected function getNotificationMessage(): string
    {
        $markupDescription = $this->getMarkupDescription();

        return match($this->type) {
            'created' => "Создана наценка: {$markupDescription}",
            'updated' => "Обновлена наценка: {$markupDescription}",
            'deleted' => "Удалена наценка: {$markupDescription}",
            'activated' => "Активирована наценка: {$markupDescription}",
            'deactivated' => "Деактивирована наценка: {$markupDescription}",
            'expired' => "Срок действия наценки истек: {$markupDescription}",
            'bulk_operation' => $this->data['message'] ?? 'Выполнены массовые операции с наценками',
            'calculation_error' => "Ошибка при расчете наценки: {$this->data['error'] ?? 'Неизвестная ошибка'}",
            default => "Изменена наценка: {$markupDescription}"
        };
    }

    /**
     * Get the notification icon
     */
    protected function getNotificationIcon(): string
    {
        return match($this->type) {
            'created' => 'bi-plus-circle',
            'updated' => 'bi-pencil',
            'deleted' => 'bi-trash',
            'activated' => 'bi-power',
            'deactivated' => 'bi-power-off',
            'expired' => 'bi-clock',
            'bulk_operation' => 'bi-collection',
            'calculation_error' => 'bi-exclamation-triangle',
            default => 'bi-info-circle'
        };
    }

    /**
     * Get the notification color
     */
    protected function getNotificationColor(): string
    {
        return match($this->type) {
            'created' => 'success',
            'updated' => 'warning',
            'deleted' => 'danger',
            'activated' => 'success',
            'deactivated' => 'secondary',
            'expired' => 'warning',
            'bulk_operation' => 'info',
            'calculation_error' => 'danger',
            default => 'primary'
        };
    }

    /**
     * Get the mail subject
     */
    protected function getMailSubject(): string
    {
        $appName = config('app.name');

        return match($this->type) {
            'created' => "Создана новая наценка - {$appName}",
            'updated' => "Обновлена наценка - {$appName}",
            'deleted' => "Удалена наценка - {$appName}",
            'bulk_operation' => "Массовые операции с наценками - {$appName}",
            default => "Изменение наценки - {$appName}"
        };
    }

    /**
     * Get the mail greeting
     */
    protected function getMailGreeting(): string
    {
        return match($this->type) {
            'created' => 'Уведомление о создании наценки!',
            'updated' => 'Уведомление об изменении наценки!',
            'deleted' => 'Уведомление об удалении наценки!',
            'bulk_operation' => 'Отчет о массовых операциях!',
            default => 'Уведомление о изменении наценки!'
        };
    }

    /**
     * Get the mail message
     */
    protected function getMailMessage(): string
    {
        $markupDescription = $this->getMarkupDescription();

        return match($this->type) {
            'created' => "Была создана новая наценка: {$markupDescription}",
            'updated' => "Была обновлена наценка: {$markupDescription}",
            'deleted' => "Была удалена наценка: {$markupDescription}",
            'bulk_operation' => $this->data['message'] ?? 'Были выполнены массовые операции с наценками.',
            default => "Произошло изменение наценки: {$markupDescription}"
        };
    }

    /**
     * Get the mail action
     */
    protected function getMailAction(): array
    {
        $url = $this->getActionUrl();

        return match($this->type) {
            'created', 'updated' => [
                'text' => 'Просмотреть наценку',
                'url' => $url
            ],
            'deleted' => [
                'text' => 'Просмотреть журнал аудита',
                'url' => route('admin.markups.audit-log')
            ],
            'bulk_operation' => [
                'text' => 'Просмотреть отчет',
                'url' => $url
            ],
            default => [
                'text' => 'Просмотреть детали',
                'url' => $url
            ]
        };
    }

    /**
     * Get the action URL
     */
    protected function getActionUrl(): string
    {
        return match($this->type) {
            'created', 'updated' => route('admin.markups.edit', $this->markup),
            'deleted' => route('admin.markups.audit-log'),
            'bulk_operation' => route('admin.markups.index'),
            default => route('admin.markups.index')
        };
    }

    /**
     * Get the action text
     */
    protected function getActionText(): string
    {
        return match($this->type) {
            'created', 'updated' => 'Просмотреть наценку',
            'deleted' => 'Просмотреть аудит',
            'bulk_operation' => 'Просмотреть отчет',
            default => 'Просмотреть детали'
        };
    }

    /**
     * Get formatted markup description
     */
    protected function getMarkupDescription(): string
    {
        $typeLabel = $this->getMarkupTypeLabel($this->markup->type);
        $entityLabel = $this->getEntityTypeLabel($this->markup->entity_type);

        $description = "{$typeLabel} • {$entityLabel}";

        if ($this->markup->markupable) {
            $entityName = $this->getMarkupableName($this->markup);
            $description .= " • {$entityName}";
        }

        return $description . " (#{$this->markup->id})";
    }

    /**
     * Get formatted changes for display
     */
    protected function getFormattedChanges(): array
    {
        if (!$this->audit || !$this->audit->formatted_changes) {
            return [];
        }

        $changes = [];

        foreach ($this->audit->formatted_changes as $change) {
            $changes[] = "{$change['field']}: {$change['from']} → {$change['to']}";
        }

        return $changes;
    }

    /**
     * Get markup type label
     */
    protected function getMarkupTypeLabel(string $type): string
    {
        return match($type) {
            'fixed' => 'Фиксированная',
            'percent' => 'Процентная',
            'tiered' => 'Ступенчатая',
            'combined' => 'Комбинированная',
            'seasonal' => 'Сезонная',
            default => $type
        };
    }

    /**
     * Get entity type label
     */
    protected function getEntityTypeLabel(string $entityType): string
    {
        return match($entityType) {
            'order' => 'Заказы',
            'rental_request' => 'Заявки',
            'proposal' => 'Предложения',
            default => $entityType
        };
    }

    /**
     * Get markupable name
     */
    protected function getMarkupableName(PlatformMarkup $markup): string
    {
        if (!$markup->markupable) {
            return 'Общая';
        }

        return match(get_class($markup->markupable)) {
            \App\Models\Equipment::class => $markup->markupable->title,
            \App\Models\EquipmentCategory::class => $markup->markupable->name,
            \App\Models\Company::class => $markup->markupable->legal_name,
            default => 'Неизвестно'
        };
    }
}
