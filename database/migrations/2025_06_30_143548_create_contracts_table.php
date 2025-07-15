<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractsTable extends Migration
{
    public function up()
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('number')->comment('Номер договора');
            $table->text('description')->nullable()->comment('Предмет договора');
            $table->enum('payment_type', ['prepay', 'postpay', 'mixed'])->default('postpay');
            $table->integer('documentation_deadline')->default(7)->comment('Срок предоставления документов (дни)');
            $table->integer('payment_deadline')->default(7)->comment('Срок оплаты (дни)');
            $table->decimal('penalty_rate', 5, 2)->default(0.3)->comment('Процент штрафа за простой');
            $table->string('file_path')->nullable()->comment('Скан договора');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('contracts');
    }
}
