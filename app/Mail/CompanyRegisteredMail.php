<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;


class CompanyRegisteredMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $company;
    public $user;

    // Настройки повторных попыток
    public $tries = 5; // Количество попыток отправки
    public $backoff = [30, 60, 180]; // Паузы между попытками (в секундах)

    /**
     * Create a new message instance.
     */
    public function __construct(Company $company, User $user)
    {
        $this->company = $company;
        $this->user = $user;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->markdown('emails.company.registered')
            ->subject('Регистрация компании ' . $this->company->name . ' завершена')
            ->with([
                'companyName' => $this->company->name,
                'loginLink' => route('login'),
                'supportEmail' => config('mail.support.address', 'support@example.com'),
            ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Ошибка отправки письма регистрации компании', [
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Дополнительные действия при ошибке:
        // - Уведомление в Slack/Telegram
        // - Пометить пользователя как "не получившего письмо"
    }
}
