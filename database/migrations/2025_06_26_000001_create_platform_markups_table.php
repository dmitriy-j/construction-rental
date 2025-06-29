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
        Schema::create('platform_markups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_id')->constrained()->cascadeOnDelete();
            $table->morphs('markupable');
            $table->string('type')->default('fixed')->comment('fixed, percent');
            $table->decimal('value', 10, 2);
            $table->timestamps();

            // Исправленное имя индекса
            $table->unique(
                ['platform_id', 'markupable_id', 'markupable_type'],
                'idx_platform_markup_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_markups');
    }
};
