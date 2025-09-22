<?php

use App\Models\DeliveryNote;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDefaultToTypeInDeliveryNotes extends Migration
{
    public function up()
    {
        // 1. Добавляем значение по умолчанию
        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->string('type')
                ->default(DeliveryNote::TYPE_DIRECT) // Значение по умолчанию
                ->change(); // Изменяем существующий столбец
        });

        // 2. Заполняем существующие записи (если есть)
        DeliveryNote::whereNull('type')->update(['type' => DeliveryNote::TYPE_DIRECT]);

        // 3. Делаем поле обязательным
        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->string('type')
                ->default(DeliveryNote::TYPE_DIRECT)
                ->nullable(false) // Теперь поле НЕ может быть NULL
                ->change();
        });
    }

    public function down()
    {
        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->string('type')
                ->nullable() // Разрешаем NULL
                ->default(null) // Убираем значение по умолчанию
                ->change();
        });
    }
}
