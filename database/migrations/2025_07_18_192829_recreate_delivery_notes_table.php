<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RecreateDeliveryNotesTable extends Migration
{
    public function up()
    {
        Schema::dropIfExists('delivery_notes');

        Schema::create('delivery_notes', function (Blueprint $table) {
            $table->id();
            $table->string('document_number')->unique();
            $table->date('issue_date');
            $table->string('type', 50);

            $table->foreignId('order_id')->constrained();
            $table->foreignId('order_item_id')->constrained();

            $table->foreignId('sender_company_id')->constrained('companies');
            $table->foreignId('receiver_company_id')->constrained('companies');

            $table->foreignId('delivery_from_id')->constrained('locations');
            $table->foreignId('delivery_to_id')->constrained('locations');

            $table->string('cargo_description');
            $table->decimal('cargo_weight', 10, 2)->nullable();
            $table->decimal('cargo_value', 15, 2)->nullable();

            $table->string('transport_type', 50)->nullable();
            $table->string('equipment_condition', 255)->nullable();

            $table->string('driver_name');
            $table->string('vehicle_model');
            $table->string('vehicle_number');
            $table->string('driver_contact');

            $table->decimal('distance_km', 10, 2);
            $table->decimal('calculated_cost', 15, 2);

            $table->string('sender_signature_path')->nullable();
            $table->string('carrier_signature_path')->nullable();
            $table->string('receiver_signature_path')->nullable();

            $table->string('document_path')->nullable();
            $table->string('status', 20)->default('draft');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('delivery_notes');
    }
}
