<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            // Оборудование
            if (!Schema::hasColumn('cart_items', 'equipment_id')) {
                $table->unsignedBigInteger('equipment_id')->nullable()->after('cart_id');
                $table->foreign('equipment_id')->references('id')->on('equipment')->onDelete('cascade');
            }

            // Цена
            if (!Schema::hasColumn('cart_items', 'total_price')) {
                $table->decimal('total_price', 12, 2)->default(0)->after('fixed_customer_price');
            }

            // Параметры аренды
            if (!Schema::hasColumn('cart_items', 'shifts_per_day')) {
                $table->unsignedInteger('shifts_per_day')->default(1)->nullable()->after('actual_working_hours');
            }

            if (!Schema::hasColumn('cart_items', 'hours_per_shift')) {
                $table->unsignedInteger('hours_per_shift')->default(8)->nullable()->after('shifts_per_day');
            }

            if (!Schema::hasColumn('cart_items', 'quantity')) {
                $table->unsignedInteger('quantity')->default(1)->nullable()->after('hours_per_shift');
            }

            if (!Schema::hasColumn('cart_items', 'address')) {
                $table->string('address', 500)->nullable()->after('quantity');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropForeign(['equipment_id']);
            $table->dropColumn([
                'equipment_id',
                'total_price',
                'shifts_per_day',
                'hours_per_shift',
                'quantity',
                'address',
            ]);
        });
    }
};
