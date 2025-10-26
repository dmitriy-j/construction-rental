<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::transaction(function () {
            // Обновляем price_per_unit из rental_terms
            DB::statement('
                UPDATE order_items
                JOIN equipment_rental_terms ON order_items.rental_term_id = equipment_rental_terms.id
                SET
                    order_items.fixed_lessor_price = equipment_rental_terms.price_per_hour,
                    order_items.fixed_customer_price = order_items.price_per_unit
                WHERE order_items.fixed_lessor_price IS NULL
            ');

            // Для записей без rental_term (70% от цены для арендатора)
            DB::statement('
                UPDATE order_items
                SET
                    fixed_lessor_price = price_per_unit * 0.7,
                    fixed_customer_price = price_per_unit
                WHERE rental_term_id IS NULL
                    AND fixed_lessor_price IS NULL
            ');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
