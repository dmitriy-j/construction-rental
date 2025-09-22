<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRentalRequestResponsesTable extends Migration
{
    public function up()
    {
        Schema::create('rental_request_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('lessor_id')->constrained('users')->onDelete('cascade'); // Арендодатель
            $table->foreignId('equipment_id')->constrained()->onDelete('cascade');
            $table->decimal('proposed_price', 10, 2);
            $table->text('message');
            $table->json('availability_dates')->nullable();
            $table->text('additional_terms')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected', 'counter_offer'])->default('pending');
            $table->decimal('counter_price', 10, 2)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['rental_request_id', 'status']);
            $table->unique(['rental_request_id', 'lessor_id', 'equipment_id'], 'unique_rental_response');
        });
    }

    public function down()
    {
        Schema::dropIfExists('rental_request_responses');
    }
}
