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
        Schema::table('waybills', function (Blueprint $table) {
            $table->string('shift')->default('day')->after('operator_id');
            $table->integer('odometer_start')->nullable()->after('notes');
            $table->integer('odometer_end')->nullable()->after('odometer_start');
            $table->decimal('fuel_start', 8, 2)->nullable()->after('odometer_end');
            $table->decimal('fuel_end', 8, 2)->nullable()->after('fuel_start');
            $table->decimal('fuel_consumption_standard', 8, 2)->nullable()->after('fuel_end');
            $table->decimal('fuel_consumption_actual', 8, 2)->nullable()->after('fuel_consumption_standard');
            $table->string('mechanic_signature_path')->nullable()->after('customer_signature_path');
            $table->text('work_description')->nullable()->after('mechanic_signature_path');
            $table->string('status')->default('created')->after('work_description');
            $table->foreignId('rental_condition_id')->nullable()->constrained()->after('status');
            $table->timestamp('completed_at')->nullable()->after('rental_condition_id');
        });
    }

    public function down()
    {
        Schema::table('waybills', function (Blueprint $table) {
            $table->dropColumn([
                'shift',
                'odometer_start',
                'odometer_end',
                'fuel_start',
                'fuel_end',
                'fuel_consumption_standard',
                'fuel_consumption_actual',
                'mechanic_signature_path',
                'work_description',
                'status',
                'rental_condition_id',
                'completed_at',
            ]);
        });
    }
};
