<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            // Добавляем тип организации
            $table->enum('legal_type', ['ip', 'ooo'])->default('ooo')->after('is_platform');

            // Делаем KPP необязательным
            $table->string('kpp', 9)->nullable()->change();

            // Увеличиваем длину полей для ИП
            $table->string('inn', 12)->change();
            $table->string('ogrn', 15)->change();
        });
    }

    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('legal_type');
            $table->string('kpp', 9)->nullable(false)->change();
            $table->string('inn', 10)->change();
            $table->string('ogrn', 13)->change();
        });
    }
};
