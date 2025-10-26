<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Проверяем и создаем индексы для rental_requests
        Schema::table('rental_requests', function (Blueprint $table) {
            // Проверяем существование индексов перед созданием
            $indexes = $this->getTableIndexes('rental_requests');

            if (!in_array('idx_public_requests', $indexes)) {
                $table->index(['visibility', 'status', 'created_at'], 'idx_public_requests');
            }

            if (!in_array('idx_location_visibility', $indexes)) {
                $table->index(['location_id', 'visibility'], 'idx_location_visibility');
            }
        });

        // Проверяем и добавляем поля для rental_request_responses
        Schema::table('rental_request_responses', function (Blueprint $table) {
            // Добавляем поля только если они не существуют
            if (!Schema::hasColumn('rental_request_responses', 'platform_markup_details')) {
                $table->json('platform_markup_details')->nullable();
            }

            if (!Schema::hasColumn('rental_request_responses', 'reservation_status')) {
                $table->enum('reservation_status', ['pending', 'reserved', 'expired'])->default('pending');
            }

            if (!Schema::hasColumn('rental_request_responses', 'reserved_until')) {
                $table->timestamp('reserved_until')->nullable();
            }

            // Проверяем существование индексов
            $indexes = $this->getTableIndexes('rental_request_responses');

            if (!in_array('idx_request_status', $indexes)) {
                $table->index(['rental_request_id', 'status'], 'idx_request_status');
            }

            if (!in_array('idx_reservation', $indexes)) {
                $table->index(['reservation_status', 'reserved_until'], 'idx_reservation');
            }
        });

        // Проверяем и создаем индекс для rental_request_items
        Schema::table('rental_request_items', function (Blueprint $table) {
            $indexes = $this->getTableIndexes('rental_request_items');

            if (!in_array('idx_item_category_request', $indexes)) {
                $table->index(['category_id', 'rental_request_id'], 'idx_item_category_request');
            }
        });
    }

    public function down()
    {
        // В down методе просто удаляем все созданные индексы
        // (поля оставляем, так как они нужны для функционала)
        Schema::table('rental_requests', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_public_requests');
            $table->dropIndexIfExists('idx_location_visibility');
        });

        Schema::table('rental_request_responses', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_request_status');
            $table->dropIndexIfExists('idx_reservation');
        });

        Schema::table('rental_request_items', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_item_category_request');
        });
    }

    /**
     * Получаем список всех индексов таблицы
     */
    private function getTableIndexes(string $tableName): array
    {
        $indexes = [];
        $results = DB::select("SHOW INDEX FROM {$tableName}");

        foreach ($results as $result) {
            $indexes[] = $result->Key_name;
        }

        return array_unique($indexes);
    }
};
