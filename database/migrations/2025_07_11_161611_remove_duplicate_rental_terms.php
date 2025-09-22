<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Удаление дубликатов
        DB::statement('
            DELETE t1 FROM equipment_rental_terms t1
            INNER JOIN equipment_rental_terms t2
            WHERE
                t1.id < t2.id AND
                t1.equipment_id = t2.equipment_id AND
                t1.period = t2.period
        ');
    }

    public function down()
    {
        // Откат невозможен, операция деструктивна
    }
};
