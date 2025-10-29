<?php
// database/migrations/2024_12_19_000000_create_equipment_imports_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('equipment_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('file_name');
            $table->string('original_name');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->integer('total_rows')->default(0);
            $table->integer('processed_rows')->default(0);
            $table->integer('successful_rows')->default(0);
            $table->integer('failed_rows')->default(0);
            $table->json('errors')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('equipment_imports');
    }
};
