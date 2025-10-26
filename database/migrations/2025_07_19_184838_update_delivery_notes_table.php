<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateDeliveryNotesTable extends Migration
{
    public function up()
    {
        Schema::table('delivery_notes', function (Blueprint $table) {
            // Добавляем поле для сценария доставки
            $table->enum('delivery_scenario', ['lessor_platform', 'platform_direct'])
                ->default('lessor_platform')
                ->after('type');

            // Для сценария 2: платформа организует доставку
            $table->unsignedBigInteger('carrier_company_id')->nullable()->after('receiver_company_id');
            $table->string('carrier_contact_name')->nullable()->after('carrier_company_id');
            $table->string('carrier_contact_phone')->nullable()->after('carrier_contact_name');

            // Переименовываем поля для более общего назначения
            $table->renameColumn('driver_name', 'transport_driver_name');
            $table->renameColumn('vehicle_model', 'transport_vehicle_model');
            $table->renameColumn('vehicle_number', 'transport_vehicle_number');
        });

        // Внешний ключ для carrier_company_id
        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->foreign('carrier_company_id')->references('id')->on('companies');
        });
    }

    public function down()
    {
        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->dropForeign(['carrier_company_id']);
            $table->dropColumn([
                'delivery_scenario',
                'carrier_company_id',
                'carrier_contact_name',
                'carrier_contact_phone',
            ]);

            $table->renameColumn('transport_driver_name', 'driver_name');
            $table->renameColumn('transport_vehicle_model', 'vehicle_model');
            $table->renameColumn('transport_vehicle_number', 'vehicle_number');
        });
    }
}
