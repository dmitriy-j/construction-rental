<?php

namespace App\Services;

use App\Models\PlatformMarkup;
use App\Models\PlatformMarkupAudit;
use App\Models\User;
use App\Notifications\MarkupChangeNotification;
use Illuminate\Support\Facades\Notification;

class MarkupNotificationService
{
    /**
     * Отправка уведомления о создании наценки
     */
    public function notifyMarkupCreated(PlatformMarkup $markup, PlatformMarkupAudit $audit): void
    {
        $users = $this->getRecipientsForEvent('created');

        Notification::send($users, new MarkupChangeNotification(
            'created',
            $markup,
            $audit
        ));

        $this->logNotification('created', $markup, count($users));
    }

    /**
     * Отправка уведомления об обновлении наценки
     */
    public function notifyMarkupUpdated(PlatformMarkup $markup, PlatformMarkupAudit $audit): void
    {
        $users = $this->getRecipientsForEvent('updated');

        Notification::send($users, new MarkupChangeNotification(
            'updated',
            $markup,
            $audit
        ));

        $this->logNotification('updated', $markup, count($users));
    }

    /**
     * Отправка уведомления об удалении наценки
     */
    public function notifyMarkupDeleted(PlatformMarkup $markup, PlatformMarkupAudit $audit): void
    {
        $users = $this->getRecipientsForEvent('deleted');

        Notification::send($users, new MarkupChangeNotification(
            'deleted',
            $markup,
            $audit
        ));

        $this->logNotification('deleted', $markup, count($users));
    }

    /**
     * Отправка уведомления об активации наценки
     */
    public function notifyMarkupActivated(PlatformMarkup $markup): void
    {
        $users = $this->getRecipientsForEvent('activated');

        Notification::send($users, new MarkupChangeNotification(
            'activated',
            $markup
        ));

        $this->logNotification('activated', $markup, count($users));
    }

    /**
     * Отправка уведомления о деактивации наценки
     */
    public function notifyMarkupDeactivated(PlatformMarkup $markup): void
    {
        $users = $this->getRecipientsForEvent('deactivated');

        Notification::send($users, new MarkupChangeNotification(
            'deactivated',
            $markup
        ));

        $this->logNotification('deactivated', $markup, count($users));
    }

    /**
     * Отправка уведомления об истечении срока наценки
     */
    public function notifyMarkupExpired(PlatformMarkup $markup): void
    {
        $users = $this->getRecipientsForEvent('expired');

        Notification::send($users, new MarkupChangeNotification(
            'expired',
            $markup
        ));

        $this->logNotification('expired', $markup, count($users));
    }

    /**
     * Отправка уведомления о массовых операциях
     */
    public function notifyBulkOperation(array $results, string $reason = ''): void
    {
        $users = $this->getRecipientsForEvent('bulk_operation');

        // Создаем временную наценку для уведомления
        $dummyMarkup = new PlatformMarkup([
            'id' => 0,
            'type' => 'fixed',
            'entity_type' => 'order',
            'value' => 0,
        ]);

        // Создаем временный аудит для хранения причины
        $dummyAudit = new PlatformMarkupAudit([
            'reason' => $reason,
        ]);

        Notification::send($users, new MarkupChangeNotification(
            'bulk_operation',
            $dummyMarkup,
            $dummyAudit,
            $results
        ));

        \Log::info('Уведомление о массовых операциях отправлено', [
            'recipients' => count($users),
            'processed' => $results['processed'] ?? 0,
            'reason' => $reason
        ]);
    }

    /**
     * Отправка уведомления об ошибке расчета
     */
    public function notifyCalculationError(array $calculationData, string $error): void
    {
        $users = $this->getRecipientsForEvent('calculation_error');

        $dummyMarkup = new PlatformMarkup([
            'id' => 0,
            'type' => 'fixed',
            'entity_type' => 'order',
            'value' => 0,
        ]);

        Notification::send($users, new MarkupChangeNotification(
            'calculation_error',
            $dummyMarkup,
            null,
            [
                'calculation_data' => $calculationData,
                'error' => $error
            ]
        ));

        \Log::error('Уведомление об ошибке расчета отправлено', [
            'recipients' => count($users),
            'error' => $error
        ]);
    }

    /**
     * Получение получателей для события
     */
    protected function getRecipientsForEvent(string $event): \Illuminate\Database\Eloquent\Collection
    {
        $query = User::where('is_active', true)
            ->whereHas('roles', function ($query) use ($event) {
                $this->applyRoleFilters($query, $event);
            });

        // Применяем настройки уведомлений пользователей
        $query->where(function ($q) use ($event) {
            $q->whereDoesntHave('notificationSettings')
              ->orWhereHas('notificationSettings', function ($q) use ($event) {
                  $q->where('markup_' . $event, true);
              });
        });

        return $query->get();
    }

    /**
     * Применение фильтров ролей для событий
     */
    protected function applyRoleFilters($query, string $event): void
    {
        $roles = match($event) {
            'created', 'updated', 'deleted' => ['admin', 'manager'],
            'activated', 'deactivated' => ['admin', 'manager'],
            'expired' => ['admin'],
            'bulk_operation' => ['admin', 'manager'],
            'calculation_error' => ['admin', 'technical'],
            default => ['admin']
        };

        $query->whereIn('name', $roles);
    }

    /**
     * Логирование отправки уведомлений
     */
    protected function logNotification(string $event, PlatformMarkup $markup, int $recipientCount): void
    {
        \Log::info('Уведомление о изменении наценки отправлено', [
            'event' => $event,
            'markup_id' => $markup->id,
            'recipients' => $recipientCount,
            'markup_type' => $markup->type,
            'entity_type' => $markup->entity_type,
        ]);
    }

    /**
     * Отправка ежедневного отчета
     */
    public function sendDailyReport(): void
    {
        $users = $this->getRecipientsForEvent('daily_report');

        $stats = $this->getDailyStats();

        if ($stats['total_changes'] > 0) {
            foreach ($users as $user) {
                $user->notify(new \App\Notifications\MarkupDailyReport($stats));
            }
        }

        \Log::info('Ежедневный отчет по наценкам отправлен', [
            'recipients' => count($users),
            'changes' => $stats['total_changes']
        ]);
    }

    /**
     * Получение ежедневной статистики
     */
    protected function getDailyStats(): array
    {
        $today = now()->startOfDay();

        $created = PlatformMarkup::whereDate('created_at', $today)->count();
        $updated = PlatformMarkupAudit::whereDate('created_at', $today)
            ->where('action', 'updated')
            ->count();
        $calculations = 0; // Здесь должна быть интеграция с системой расчетов

        return [
            'date' => $today->format('Y-m-d'),
            'created' => $created,
            'updated' => $updated,
            'calculations' => $calculations,
            'total_changes' => $created + $updated,
        ];
    }

    /**
     * Очистка старых уведомлений
     */
    public function cleanupOldNotifications(int $days = 30): void
    {
        $cutoffDate = now()->subDays($days);

        \DB::table('notifications')
            ->where('type', MarkupChangeNotification::class)
            ->where('created_at', '<', $cutoffDate)
            ->delete();

        \Log::info('Очистка старых уведомлений выполнена', [
            'cutoff_date' => $cutoffDate,
            'days' => $days
        ]);
    }
}
