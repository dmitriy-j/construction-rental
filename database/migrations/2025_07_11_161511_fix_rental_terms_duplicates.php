<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::statement('
            CREATE TEMPORARY TABLE temp_terms AS
            SELECT MIN(id) AS id, equipment_id, period
            FROM equipment_rental_terms
            GROUP BY equipment_id, period;
        ');

        DB::statement('
            DELETE FROM equipment_rental_terms
            WHERE id NOT IN (SELECT id FROM temp_terms);
        ');

        DB::statement('DROP TEMPORARY TABLE IF EXISTS temp_terms;');
    }

    public function down()
    {
        // Необратимая операция
    }
};
