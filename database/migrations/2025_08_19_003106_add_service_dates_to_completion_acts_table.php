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
        Schema::table('completion_acts', function (Blueprint $table) {
            $table->date('service_start_date')->after('act_date');
            $table->date('service_end_date')->after('service_start_date');
            $table->decimal('penalty_amount', 10, 2)->default(0)->after('total_downtime');
            $table->decimal('prepayment_amount', 10, 2)->default(0)->after('total_amount');
            $table->decimal('final_amount', 10, 2)->default(0)->after('prepayment_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('completion_acts', function (Blueprint $table) {
            //
        });
    }
};
