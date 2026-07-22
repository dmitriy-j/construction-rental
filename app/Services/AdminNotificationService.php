<?php

namespace App\Services;

use App\Mail\AdminNotificationMail;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AdminNotificationService
{
    private string $adminEmail;

    public function __construct()
    {
        $this->adminEmail = config('mail.from.address', 'office@fap24.ru');
    }

    /**
     * Найти администратора для системного уведомления (database).
     */
    private function getAdminUsers(): ?User
    {
        return User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['platform_super', 'platform_admin']);
        })->first();
    }

    /**
     * Отправить email + database уведомление.
     */
    private function notify(string $type, string $title, array $data, string $actionUrl = ''): void
    {
        // Email админу
        try {
            $subject = match ($type) {
                'bank_statement_uploaded' => 'Загружена банковская выписка',
                'payment_overdue' => 'Нарушен срок оплаты',
                'new_user_registered' => 'Зарегистрирован новый пользователь',
                'new_rental_request' => 'Создана новая заявка на аренду',
                'new_order' => 'Создан новый заказ',
                'order_completed' => 'Заказ завершён',
                default => 'Уведомление с платформы',
            };

            Mail::to($this->adminEmail)->send(new AdminNotificationMail(
                $subject,
                $title,
                $data,
                'emails.admin-notification',
                $actionUrl,
                'Перейти в админ-панель'
            ));
        } catch (\Throwable $e) {
            Log::error("Ошибка отправки email-уведомления админу (type: {$type})", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // Database уведомление для админа
        try {
            $admin = $this->getAdminUsers();
            if ($admin) {
                $admin->notify(new \App\Notifications\AdminSystemNotification($type, $title, $data, $actionUrl));
            }
        } catch (\Throwable $e) {
            Log::error("Ошибка создания database-уведомления (type: {$type})", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ---- События ----

    public function bankStatementUploaded(int $statementId, int $transactionCount, float $totalAmount): void
    {
        $this->notify(
            'bank_statement_uploaded',
            '📄 Загружена банковская выписка',
            [
                'ID выписки' => "#{$statementId}",
                'Количество транзакций' => $transactionCount,
                'Общая сумма' => number_format($totalAmount, 2, ',', ' ') . ' ₽',
                'Дата' => now()->format('d.m.Y H:i'),
            ],
            route('admin.bank-statements.index')
        );
    }

    public function paymentOverdue(string $orderNumber, float $amount, string $lesseeName): void
    {
        $this->notify(
            'payment_overdue',
            '⚠️ Нарушен срок оплаты по заказу',
            [
                'Номер заказа' => $orderNumber,
                'Сумма' => number_format($amount, 2, ',', ' ') . ' ₽',
                'Арендатор' => $lesseeName,
                'Просрочка' => 'более 15 дней',
            ],
            route('admin.orders.show', ['order' => $orderNumber])
        );
    }

    public function newUserRegistered(string $companyName, string $companyType, string $contactPerson, string $phone, string $email): void
    {
        $this->notify(
            'new_user_registered',
            '👤 Зарегистрирован новый пользователь',
            [
                'Компания' => $companyName,
                'Тип' => $companyType === 'lessee' ? 'Арендатор' : 'Арендодатель',
                'Контактное лицо' => $contactPerson,
                'Телефон' => $phone,
                'Email' => $email,
                'Дата регистрации' => now()->format('d.m.Y H:i'),
            ],
            route('admin.lessees.index')
        );
    }

    public function newRentalRequest(string $requestNumber, string $lesseeName, \DateTimeInterface $date, string $equipmentName = ''): void
    {
        $this->notify(
            'new_rental_request',
            '📋 Создана новая заявка на аренду',
            [
                'Номер заявки' => $requestNumber,
                'Арендатор' => $lesseeName,
                'Техника' => $equipmentName ?: 'Не указана',
                'Дата создания' => $date->format('d.m.Y H:i'),
            ],
            route('admin.rental-requests.index')
        );
    }

    public function newOrder(string $orderNumber, string $lesseeName, ?string $lessorName, float $amount): void
    {
        $this->notify(
            'new_order',
            '🛒 Создан новый заказ',
            [
                'Номер заказа' => $orderNumber,
                'Арендатор' => $lesseeName,
                'Арендодатель' => $lessorName ?? 'Не назначен',
                'Сумма' => number_format($amount, 2, ',', ' ') . ' ₽',
                'Дата' => now()->format('d.m.Y H:i'),
            ],
            route('admin.orders.show', ['order' => $orderNumber])
        );
    }

    public function orderCompleted(string $orderNumber, string $lesseeName, float $amount): void
    {
        $this->notify(
            'order_completed',
            '✅ Заказ завершён',
            [
                'Номер заказа' => $orderNumber,
                'Арендатор' => $lesseeName,
                'Итоговая сумма' => number_format($amount, 2, ',', ' ') . ' ₽',
                'Дата завершения' => now()->format('d.m.Y H:i'),
            ],
            route('admin.orders.show', ['order' => $orderNumber])
        );
    }
}
