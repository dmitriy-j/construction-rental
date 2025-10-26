<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('rental_condition_id')
                ->nullable()
                ->constrained('rental_conditions')
                ->onDelete('set null')
                ->after('rental_term_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['rental_condition_id']);
            $table->dropColumn('rental_condition_id');
        });
    }
};
