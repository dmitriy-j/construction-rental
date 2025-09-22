<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentTemplatesTable extends Migration
{
    public function up()
    {
        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // путевой_лист, акт, счет_на_оплату
            $table->text('description')->nullable();
            $table->string('file_path'); // Путь к оригинальному файлу
            $table->json('mapping'); // Конфигурация маппинга полей
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('document_templates');
    }
}
