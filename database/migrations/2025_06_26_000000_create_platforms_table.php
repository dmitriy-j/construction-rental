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
        Schema::create('platforms', function (Blueprint $table) {
            $table->id();

            // Основные реквизиты
            $table->string('name')->unique()->comment('Полное наименование организации');
            $table->string('short_name')->nullable()->comment('Сокращенное наименование');
            $table->string('inn', 12)->unique()->comment('ИНН платформы');
            $table->string('kpp', 9)->nullable()->comment('КПП платформы');
            $table->string('ogrn', 15)->nullable()->comment('ОГРН платформы');
            $table->string('okpo', 10)->nullable()->comment('ОКПО');
            $table->string('okved', 10)->nullable()->comment('Основной вид деятельности');
            $table->string('okato', 20)->nullable()->comment('Код территории');
            $table->string('certificate_number')->nullable()->comment('Номер свидетельства о регистрации');



            // Адреса
            $table->string('legal_address')->comment('Юридический адрес');
            $table->string('physical_address')->comment('Фактический адрес');
            $table->string('post_address')->nullable()->comment('Почтовый адрес');

            // Банковские реквизиты
            $table->string('bank_name')->comment('Наименование банка');
            $table->string('bank_city')->comment('Город банка');
            $table->string('bic', 9)->comment('БИК банка');
            $table->string('correspondent_account', 255)->comment('Корреспондентский счет');
            $table->string('settlement_account', 255)->comment('Расчетный счет');

            // Контактная информация
            $table->string('website')->nullable()->comment('Сайт компании');
            $table->string('email')->comment('Основной email');
            $table->string('phone')->comment('Основной телефон');
            $table->string('additional_phones')->nullable()->comment('Дополнительные телефоны');

            // Руководство
            $table->string('ceo_name')->comment('ФИО Генерального директора');
            $table->string('ceo_position')->default('Генеральный директор')->comment('Должность руководителя');
            $table->string('accountant_name')->nullable()->comment('ФИО Главного бухгалтера');
            $table->string('accountant_position')->default('Главный бухгалтер')->comment('Должность бухгалтера');
            $table->string('ceo_basis')->nullable()->comment('Основание полномочий руководителя');

            // Дополнительные поля
            $table->text('notes')->nullable()->comment('Дополнительные заметки');
            $table->string('signature_image_path')->nullable()->comment('Путь к изображению подписи');
            $table->string('stamp_image_path')->nullable()->comment('Путь к изображению печати');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platforms');
    }
};
