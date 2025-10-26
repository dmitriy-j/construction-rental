<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePendingTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('pending_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_statement_id')->constrained()->onDelete('cascade');
            $table->string('company_inn');
            $table->string('company_name');
            $table->decimal('amount', 15, 2);
            $table->string('type'); // incoming/outgoing
            $table->json('transaction_data');
            $table->string('status'); // pending_registration, processed, cancelled
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pending_transactions');
    }
}
