<?php

namespace App\Policies;

use App\Models\User;
use App\Models\PlatformMarkup;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class MarkupPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): Response
    {
        return $user->hasPermission('markups.view') ||
               $user->hasRole(['admin', 'manager', 'analyst'])
            ? Response::allow()
            : Response::deny('У вас нет прав для просмотра наценок.');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PlatformMarkup $markup): Response
    {
        // Аналитики могут просматривать только активные наценки
        if ($user->hasRole('analyst') && !$markup->is_active) {
            return Response::deny('Аналитики могут просматривать только активные наценки.');
        }

        return $user->hasPermission('markups.view') ||
               $user->hasRole(['admin', 'manager', 'analyst'])
            ? Response::allow()
            : Response::deny('У вас нет прав для просмотра этой наценки.');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): Response
    {
        // Проверка лимита активных наценок
        $activeMarkupsCount = PlatformMarkup::where('is_active', true)->count();
        $maxActiveMarkups = config('markups.limits.max_active_markups');

        if ($activeMarkupsCount >= $maxActiveMarkups) {
            return Response::deny("Достигнут лимит активных наценок ({$maxActiveMarkups}). Деактивируйте некоторые наценки перед созданием новых.");
        }

        return $user->hasPermission('markups.create') ||
               $user->hasRole(['admin', 'manager'])
            ? Response::allow()
            : Response::deny('У вас нет прав для создания наценок.');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PlatformMarkup $markup): Response
    {
        // Менеджеры не могут изменять наценки с высоким приоритетом (оборудование)
        if ($user->hasRole('manager') && $markup->priority >= 300) {
            return Response::deny('Менеджеры не могут изменять наценки на оборудование.');
        }

        // Проверка периода действия для обновления
        if ($markup->valid_to && $markup->valid_to->isPast()) {
            return Response::deny('Нельзя изменять наценки с истекшим сроком действия.');
        }

        return $user->hasPermission('markups.update') ||
               $user->hasRole(['admin', 'manager'])
            ? Response::allow()
            : Response::deny('У вас нет прав для обновления этой наценки.');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PlatformMarkup $markup): Response
    {
        // Нельзя удалять активные наценки с историей применений
        if ($markup->is_active) {
            return Response::deny('Нельзя удалять активные наценки. Сначала деактивируйте наценку.');
        }

        // Проверка на наличие зависимостей
        if ($this->hasDependencies($markup)) {
            return Response::deny('Нельзя удалить наценку, так как она связана с другими записями.');
        }

        return $user->hasPermission('markups.delete') ||
               $user->hasRole(['admin'])
            ? Response::allow()
            : Response::deny('У вас нет прав для удаления наценок.');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PlatformMarkup $markup): Response
    {
        return $user->hasPermission('markups.restore') ||
               $user->hasRole(['admin'])
            ? Response::allow()
            : Response::deny('У вас нет прав для восстановления наценок.');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PlatformMarkup $markup): Response
    {
        // Полное удаление разрешено только администраторам
        // и только для наценок без аудиторских записей
        if ($markup->audits()->exists()) {
            return Response::deny('Нельзя полностью удалить наценку с историей изменений.');
        }

        return $user->hasPermission('markups.force_delete') &&
               $user->hasRole(['admin'])
            ? Response::allow()
            : Response::deny('У вас нет прав для полного удаления наценок.');
    }

    /**
     * Determine whether the user can perform bulk operations.
     */
    public function bulkOperations(User $user): Response
    {
        $maxItems = config('markups.bulk_operations.max_items');

        return $user->hasPermission('markups.bulk') ||
               $user->hasRole(['admin', 'manager'])
            ? Response::allow()
            : Response::deny('У вас нет прав для выполнения массовых операций.');
    }

    /**
     * Determine whether the user can export data.
     */
    public function export(User $user): Response
    {
        return $user->hasPermission('markups.export') ||
               $user->hasRole(['admin', 'manager', 'analyst'])
            ? Response::allow()
            : Response::deny('У вас нет прав для экспорта данных.');
    }

    /**
     * Determine whether the user can import data.
     */
    public function import(User $user): Response
    {
        return $user->hasPermission('markups.import') ||
               $user->hasRole(['admin', 'manager'])
            ? Response::allow()
            : Response::deny('У вас нет прав для импорта данных.');
    }

    /**
     * Determine whether the user can view audit logs.
     */
    public function viewAudit(User $user): Response
    {
        return $user->hasPermission('markups.audit') ||
               $user->hasRole(['admin', 'manager'])
            ? Response::allow()
            : Response::deny('У вас нет прав для просмотра журнала аудита.');
    }

    /**
     * Determine whether the user can test calculations.
     */
    public function testCalculations(User $user): Response
    {
        $calculationsPerMinute = $this->getCalculationsCount($user);
        $maxCalculations = config('markups.performance.max_calculations_per_minute');

        if ($calculationsPerMinute >= $maxCalculations) {
            return Response::deny("Превышен лимит тестовых расчетов ({$maxCalculations} в минуту).");
        }

        return $user->hasPermission('markups.test') ||
               $user->hasAnyRole(['admin', 'manager', 'analyst'])
            ? Response::allow()
            : Response::deny('У вас нет прав для тестирования расчетов.');
    }

    /**
     * Determine whether the user can manage markup templates.
     */
    public function manageTemplates(User $user): Response
    {
        return $user->hasPermission('markups.templates') ||
               $user->hasRole(['admin'])
            ? Response::allow()
            : Response::deny('У вас нет прав для управления шаблонами наценок.');
    }

    /**
     * Determine whether the user can manage seasonal markups.
     */
    public function manageSeasonal(User $user): Response
    {
        return $user->hasPermission('markups.seasonal') ||
               $user->hasRole(['admin', 'manager'])
            ? Response::allow()
            : Response::deny('У вас нет прав для управления сезонными наценками.');
    }

    /**
     * Determine whether the user can approve markups.
     */
    public function approve(User $user, PlatformMarkup $markup): Response
    {
        // Нельзя утверждать свои же наценки
        if ($markup->created_by === $user->id) {
            return Response::deny('Вы не можете утверждать созданные вами наценки.');
        }

        // Проверка на необходимость утверждения
        if (!$this->requiresApproval($markup)) {
            return Response::deny('Эта наценка не требует утверждения.');
        }

        return $user->hasPermission('markups.approve') ||
               $user->hasRole(['admin'])
            ? Response::allow()
            : Response::deny('У вас нет прав для утверждения наценок.');
    }

    /**
     * Determine whether the user can view statistics.
     */
    public function viewStatistics(User $user): Response
    {
        return $user->hasPermission('markups.statistics') ||
               $user->hasAnyRole(['admin', 'manager', 'analyst'])
            ? Response::allow()
            : Response::deny('У вас нет прав для просмотра статистики.');
    }

    /**
     * Determine whether the user can manage system settings.
     */
    public function manageSettings(User $user): Response
    {
        return $user->hasPermission('markups.settings') &&
               $user->hasRole(['admin'])
            ? Response::allow()
            : Response::deny('У вас нет прав для управления настройками системы.');
    }

    /**
     * Check if markup has dependencies
     */
    private function hasDependencies(PlatformMarkup $markup): bool
    {
        // Проверка на наличие связанных записей в аудите
        if ($markup->audits()->exists()) {
            return true;
        }

        // Проверка на наличие применений в расчетах
        // Здесь должна быть интеграция с системой расчетов
        // Временно возвращаем false
        return false;
    }

    /**
     * Get user's calculations count in the last minute
     */
    private function getCalculationsCount(User $user): int
    {
        // Здесь должна быть реализация подсчета расчетов пользователя
        // Временно возвращаем 0
        return 0;
    }

    /**
     * Check if markup requires approval
     */
    private function requiresApproval(PlatformMarkup $markup): bool
    {
        // Наценки с высоким приоритетом или большим значением требуют утверждения
        return $markup->priority >= 300 ||
               ($markup->type === 'percent' && $markup->value > 20) ||
               ($markup->type === 'fixed' && $markup->value > 500);
    }

    /**
     * Determine whether the user can duplicate the markup.
     */
    public function duplicate(User $user, PlatformMarkup $markup): Response
    {
        // Проверка лимита активных наценок
        $activeMarkupsCount = PlatformMarkup::where('is_active', true)->count();
        $maxActiveMarkups = config('markups.limits.max_active_markups');

        if ($activeMarkupsCount >= $maxActiveMarkups) {
            return Response::deny("Достигнут лимит активных наценок ({$maxActiveMarkups}).");
        }

        return $user->hasPermission('markups.create') ||
               $user->hasRole(['admin', 'manager'])
            ? Response::allow()
            : Response::deny('У вас нет прав для дублирования наценок.');
    }

    /**
     * Determine whether the user can change markup status.
     */
    public function changeStatus(User $user, PlatformMarkup $markup): Response
    {
        // Проверка периода действия
        if ($markup->valid_to && $markup->valid_to->isPast() && !$markup->is_active) {
            return Response::deny('Нельзя активировать наценку с истекшим сроком действия.');
        }

        return $user->hasPermission('markups.update') ||
               $user->hasRole(['admin', 'manager'])
            ? Response::allow()
            : Response::deny('У вас нет прав для изменения статуса наценки.');
    }

    /**
     * Determine whether the user can view markup history.
     */
    public function viewHistory(User $user, PlatformMarkup $markup): Response
    {
        return $user->hasPermission('markups.audit') ||
               $user->hasRole(['admin', 'manager'])
            ? Response::allow()
            : Response::deny('У вас нет прав для просмотра истории наценки.');
    }
}
