<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('document_templates', function (Blueprint $table) {
            $table->string('scenario')->nullable()->after('type'); // Добавляем поле scenario
        });
    }

    public function down()
    {
        Schema::table('document_templates', function (Blueprint $table) {
            $table->dropColumn('scenario'); // Удаляем поле scenario при откате миграции
        });
    }

};
