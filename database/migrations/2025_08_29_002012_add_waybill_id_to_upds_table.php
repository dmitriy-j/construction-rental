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
        Schema::table('upds', function (Blueprint $table) {
            $table->foreignId('waybill_id')
                ->after('lessee_company_id')
                ->nullable()
                ->constrained('waybills')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('upds', function (Blueprint $table) {
            $table->dropForeign(['waybill_id']);
            $table->dropColumn('waybill_id');
        });
    }
};
