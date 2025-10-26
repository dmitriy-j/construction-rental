<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePendingPayoutsTable extends Migration
{
    public function up()
    {
        Schema::create('pending_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_statement_transaction_id')->constrained()->onDelete('cascade');
            $table->string('payee_inn');
            $table->string('payee_name');
            $table->decimal('amount', 15, 2);
            $table->text('purpose');
            $table->string('status'); // pending_registration, processed, cancelled
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pending_payouts');
    }
}
