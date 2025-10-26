<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddPausedStatusToRentalRequests extends Migration
{
    public function up()
    {
        // Обновляем столбец status, добавляя новый статус 'paused'
        DB::statement("ALTER TABLE rental_requests MODIFY COLUMN status ENUM('draft', 'active', 'paused', 'processing', 'completed', 'cancelled') NOT NULL DEFAULT 'draft'");
    }

    public function down()
    {
        // Возвращаем столбец status к предыдущему состоянию без 'paused'
        DB::statement("ALTER TABLE rental_requests MODIFY COLUMN status ENUM('draft', 'active', 'processing', 'completed', 'cancelled') NOT NULL DEFAULT 'draft'");
    }
};
