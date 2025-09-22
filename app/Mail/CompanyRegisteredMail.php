<?php

namespace App\Mail;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CompanyRegisteredMail extends Mailable
{
    use Queueable, SerializesModels;

    public $company;

    public $user;

    public function __construct(Company $company)
    {
        $this->company = $company;
    }

    public function build()
    {
        return $this->subject('Регистрация компании завершена')
            ->view('emails.company_registered')
            ->with([
                'company' => $this->company,
                'user' => $this->user, // Теперь $user будет доступна в шаблоне
            ]);
    }
}
