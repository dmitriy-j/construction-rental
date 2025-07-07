<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
        {
            Schema::create('companies', function (Blueprint $table) {
                $table->id();
                $table->boolean('is_lessor')->default(false); // Заменяем type на флаги
                $table->boolean('is_lessee')->default(false);
                $table->string('legal_name');
                $table->enum('tax_system', ['vat', 'no_vat']);
                $table->string('inn', 10);
                $table->string('kpp', 9);
                $table->string('ogrn', 13);
                $table->string('okpo', 10)->nullable();
                $table->text('legal_address');
                $table->text('actual_address')->nullable();
                $table->string('bank_name');
                $table->string('bank_account', 20);
                $table->string('bik', 9);
                $table->string('correspondent_account', 20)->nullable();
                $table->string('director_name');
                $table->string('phone', 20);
                $table->text('contacts')->nullable();
                // Убрали contact_email - будет использоваться email из users

                $table->enum('status', [
                    'pending',
                    'verified',
                    'rejected'
                ])->default('pending');

                $table->text('rejection_reason')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();
            });
        }
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
