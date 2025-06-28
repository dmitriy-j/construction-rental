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
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->string('event');
                $table->morphs('auditable');
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->foreignId('user_id')
                    ->constrained()
                    ->onDelete('cascade'); // Добавлено каскадное удаление
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();
            });
        }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
