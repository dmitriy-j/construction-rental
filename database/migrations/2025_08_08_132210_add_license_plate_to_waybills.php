<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLicensePlateToWaybills extends Migration
{
    public function up()
    {
        Schema::table('waybills', function (Blueprint $table) {
            // Добавляем все новые поля без указания позиции
            $table->string('number')->unique();
            $table->string('license_plate', 20)->nullable();
            $table->text('notes')->nullable();
            $table->text('operator_notes')->nullable();
            $table->string('foreman_signature_path')->nullable();
            $table->string('supervisor_signature_path')->nullable();
        });
    }

    public function down()
    {
        Schema::table('waybills', function (Blueprint $table) {
            $table->dropColumn([
                'number',
                'license_plate',
                'notes',
                'operator_notes',
                'foreman_signature_path',
                'supervisor_signature_path',
            ]);
        });
    }
}
