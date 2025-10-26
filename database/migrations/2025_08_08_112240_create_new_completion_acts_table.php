<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewCompletionActsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Создаем новую таблицу с правильной структурой
        Schema::create('new_completion_acts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('waybill_id')->constrained()->onDelete('cascade');
            $table->date('act_date')->comment('Дата составления акта');
            $table->unsignedInteger('total_hours')->comment('Общее количество отработанных часов');
            $table->unsignedInteger('total_downtime')->default(0)->comment('Общее количество часов простоя');
            $table->decimal('hourly_rate', 10, 2)->comment('Почасовая ставка');
            $table->decimal('total_amount', 10, 2)->comment('Общая сумма к оплате');
            $table->text('notes')->nullable()->comment('Комментарии');
            $table->string('document_path')->nullable()->comment('Путь к файлу акта');
            $table->enum('status', ['draft', 'signed', 'paid'])->default('draft')->comment('Статус акта');
            $table->timestamps();

            // Индексы
            $table->index('act_date');
            $table->index('status');
        });

        // Удаляем старую таблицу (если существует)
        Schema::dropIfExists('completion_acts');

        // Переименовываем новую таблицу
        Schema::rename('new_completion_acts', 'completion_acts');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Создаем резервную копию новой таблицы
        Schema::create('backup_completion_acts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('waybill_id')->constrained()->onDelete('cascade');
            $table->date('act_date');
            $table->unsignedInteger('total_hours');
            $table->unsignedInteger('total_downtime');
            $table->decimal('hourly_rate', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->text('notes')->nullable();
            $table->string('document_path')->nullable();
            $table->enum('status', ['draft', 'signed', 'paid']);
            $table->timestamps();
        });

        // Удаляем текущую таблицу
        Schema::dropIfExists('completion_acts');

        // Восстанавливаем оригинальную структуру (пример)
        Schema::create('completion_acts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained();
            $table->date('act_date');
            $table->unsignedInteger('total_hours');
            $table->decimal('total_amount', 10, 2);
            $table->text('notes')->nullable();
            $table->string('document_path')->nullable();
            $table->timestamps();
        });
    }
}
