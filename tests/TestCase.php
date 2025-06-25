<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

         // Отключаем интерактивные запросы в миграциях
        \Illuminate\Console\Application::starting(function ($console) {
        $console->setInteractive(false);

        });

        // Отключаем CSRF-защиту для всех тестов
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }
}
