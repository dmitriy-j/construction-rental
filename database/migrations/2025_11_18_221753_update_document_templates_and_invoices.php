<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Добавляем поле scenario в document_templates если его еще нет
        if (!Schema::hasColumn('document_templates', 'scenario')) {
            Schema::table('document_templates', function (Blueprint $table) {
                $table->string('scenario')->nullable()->after('type');
                $table->index(['type', 'scenario', 'is_active']);
            });
        }

        // 2. Добавляем недостающие поля в существующую таблицу invoices
        Schema::table('invoices', function (Blueprint $table) {
            // Добавляем связь с УПД если еще нет
            if (!Schema::hasColumn('invoices', 'upd_id')) {
                $table->foreignId('upd_id')->nullable()->constrained()->onDelete('set null');
            }

            // Добавляем поле для причины отмены если еще нет
            if (!Schema::hasColumn('invoices', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable();
            }

            // Добавляем индекс для связи с УПД
            $table->index(['upd_id', 'status']);
        });

        // 3. Создаем таблицу для хранения позиций счета (если нужна детализация)
        if (!Schema::hasTable('invoice_items')) {
            Schema::create('invoice_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
                $table->string('name'); // Наименование услуги/товара
                $table->text('description')->nullable();
                $table->decimal('quantity', 10, 2)->default(1);
                $table->string('unit')->default('шт.');
                $table->decimal('price', 12, 2);
                $table->decimal('amount', 12, 2);
                $table->decimal('vat_rate', 5, 2)->default(0); // Ставка НДС
                $table->decimal('vat_amount', 12, 2)->default(0);
                $table->timestamps();

                $table->index(['invoice_id']);
            });
        }
    }

    public function down()
    {
        // Откатываем только добавленные поля и таблицы
        Schema::table('document_templates', function (Blueprint $table) {
            $table->dropColumn('scenario');
            $table->dropIndex(['type', 'scenario', 'is_active']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'upd_id')) {
                $table->dropForeign(['upd_id']);
                $table->dropColumn('upd_id');
            }
            if (Schema::hasColumn('invoices', 'cancellation_reason')) {
                $table->dropColumn('cancellation_reason');
            }
        });

        Schema::dropIfExists('invoice_items');
    }
};
