<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reconciliation_acts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('starting_balance', 15, 2);
            $table->decimal('ending_balance', 15, 2);
            $table->json('transactions');
            $table->boolean('confirmed_by_company')->default(false);
            $table->boolean('confirmed_by_platform')->default(false);
            $table->timestamp('confirmed_at')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_acts');
    }
};
