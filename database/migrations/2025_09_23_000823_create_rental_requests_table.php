<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRentalRequestsTable extends Migration
{
    public function up()
    {
        Schema::create('rental_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Арендатор
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->foreignId('category_id')->constrained('equipment_categories');
            $table->json('desired_specifications')->nullable(); // Желаемые характеристики
            $table->date('rental_period_start');
            $table->date('rental_period_end');
            $table->decimal('budget_from', 10, 2);
            $table->decimal('budget_to', 10, 2);
            $table->foreignId('location_id')->constrained();
            $table->boolean('delivery_required')->default(false);
            $table->enum('status', ['draft', 'active', 'processing', 'completed', 'cancelled'])->default('draft');
            $table->integer('views_count')->default(0);
            $table->integer('responses_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'expires_at']);
            $table->index(['category_id', 'location_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('rental_requests');
    }
}
