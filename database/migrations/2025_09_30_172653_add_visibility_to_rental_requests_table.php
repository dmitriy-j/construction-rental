<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVisibilityToRentalRequestsTable extends Migration
{
    public function up()
    {
        Schema::table('rental_requests', function (Blueprint $table) {
            // Добавляем колонку visibility, если она не существует
            if (!Schema::hasColumn('rental_requests', 'visibility')) {
                $table->enum('visibility', ['public', 'private'])->default('public')->after('title'); // замените some_column на актуальную колонку
            }
        });
    }

    public function down()
    {
        Schema::table('rental_requests', function (Blueprint $table) {
            if (Schema::hasColumn('rental_requests', 'visibility')) {
                $table->dropColumn('visibility');
            }
        });
    }
}
