<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('rental_request_responses', function (Blueprint $table) {
            // ✅ Разрешаем NULL ТОЛЬКО для bulk-контейнеров
            $table->unsignedBigInteger('equipment_id')->nullable()->change();

            // ✅ Добавляем поясняющие комментарии к полям
            $table->string('proposal_type')->virtualAs(
                "CASE
                    WHEN is_bulk_main = 1 THEN 'bulk_container'
                    WHEN is_bulk_item = 1 THEN 'bulk_item'
                    ELSE 'single'
                END"
            );
        });
    }

    public function down()
    {
        Schema::table('rental_request_responses', function (Blueprint $table) {
            // Восстанавливаем NOT NULL, но только если нет NULL записей
            $table->unsignedBigInteger('equipment_id')->nullable(false)->change();
            $table->dropColumn('proposal_type');
        });
    }
};
