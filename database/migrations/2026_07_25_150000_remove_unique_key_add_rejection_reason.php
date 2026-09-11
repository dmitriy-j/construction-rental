<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('rental_request_responses', function (Blueprint $table) {
            // Удаляем уникальный ключ, чтобы можно было хранить историю
            $table->dropUnique('unique_rental_response');
            // Добавляем поле для причины отклонения
            $table->text('rejection_reason')->nullable()->after('message');
        });
    }

    public function down()
    {
        Schema::table('rental_request_responses', function (Blueprint $table) {
            $table->unique(['rental_request_id', 'lessor_id', 'equipment_id'], 'unique_rental_response');
            $table->dropColumn('rejection_reason');
        });
    }
};
