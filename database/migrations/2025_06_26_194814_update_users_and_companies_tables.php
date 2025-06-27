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
        // Добавить контактный email в companies
        if (!Schema::hasColumn('companies', 'contact_email')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->string('contact_email')->after('phone');
            });
        }

        // Добавить недостающие поля в users
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'company_id')) {
                $table->foreignId('company_id')->nullable()->constrained()->after('id');
            }

            if (!Schema::hasColumn('users', 'position')) {
                $table->enum('position', ['admin', 'manager', 'dispatcher', 'accountant'])
                    ->default('admin')
                    ->after('type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Откат изменений не требуется
    }
};
