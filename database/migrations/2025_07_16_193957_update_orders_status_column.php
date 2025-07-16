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
        DB::statement("ALTER TABLE orders MODIFY status VARCHAR(30) NOT NULL");
    }

    public function down()
    {
        // Возвращаем обратно к ENUM при откате
        DB::statement("ALTER TABLE orders MODIFY status ENUM(
            'pending',
            'pending_approval',
            'confirmed',
            'active',
            'completed',
            'cancelled',
            'extension_requested',
            'rejected'
        ) NOT NULL");
    }
};
