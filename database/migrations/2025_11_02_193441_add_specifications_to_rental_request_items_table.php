<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('rental_request_items', function (Blueprint $table) {
            // Добавляем новые поля для разделения спецификаций
            $table->json('standard_specifications')->nullable()->after('specifications');
            $table->json('custom_specifications')->nullable()->after('standard_specifications');

            // Индекс для оптимизации запросов
            $table->index(['category_id']);
        });

        // Конвертируем существующие данные
        $this->migrateExistingData();
    }

    public function down()
    {
        Schema::table('rental_request_items', function (Blueprint $table) {
            $table->dropColumn(['standard_specifications', 'custom_specifications']);
            $table->dropIndex(['category_id']);
        });
    }

    private function migrateExistingData()
    {
        \Illuminate\Support\Facades\DB::table('rental_request_items')->chunkById(100, function ($items) {
            foreach ($items as $item) {
                $this->convertItemSpecifications($item);
            }
        });
    }

    private function convertItemSpecifications($item)
    {
        $specifications = json_decode($item->specifications, true) ?? [];
        $customMetadata = json_decode($item->custom_specs_metadata, true) ?? [];

        $standardSpecs = [];
        $customSpecs = [];

        foreach ($specifications as $key => $value) {
            if (str_starts_with($key, 'custom_')) {
                // Это кастомная спецификация
                $metadata = $customMetadata[$key] ?? [];
                $customSpecs[$key] = [
                    'label' => $metadata['name'] ?? $key,
                    'value' => $value,
                    'unit' => $metadata['unit'] ?? '',
                    'dataType' => $metadata['dataType'] ?? 'string'
                ];
            } else {
                // Это стандартная спецификация
                $standardSpecs[$key] = $value;
            }
        }

        \Illuminate\Support\Facades\DB::table('rental_request_items')
            ->where('id', $item->id)
            ->update([
                'standard_specifications' => json_encode($standardSpecs),
                'custom_specifications' => json_encode($customSpecs),
            ]);
    }
};
