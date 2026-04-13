<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Уведомления о наценках
            $table->boolean('markup_created')->default(true);
            $table->boolean('markup_updated')->default(true);
            $table->boolean('markup_deleted')->default(true);
            $table->boolean('markup_activated')->default(true);
            $table->boolean('markup_deactivated')->default(false);
            $table->boolean('markup_expired')->default(true);
            $table->boolean('markup_bulk_operation')->default(true);
            $table->boolean('markup_calculation_error')->default(true);
            $table->boolean('markup_daily_report')->default(false);

            // Каналы доставки
            $table->boolean('email_notifications')->default(true);
            $table->boolean('browser_notifications')->default(true);
            $table->boolean('push_notifications')->default(false);

            $table->timestamps();

            // Один пользователь - одна настройка
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_notification_settings');
    }
};
