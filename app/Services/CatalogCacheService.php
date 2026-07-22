<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CatalogCacheService
{
    const INDEX_TTL = 10;   // минут
    const SHOW_TTL = 30;    // минут
    const CACHE_PREFIX = 'catalog_';
    const SHOW_PREFIX = 'equipment_show_';
    const KEYS_KEY = 'catalog_cache_keys';

    /**
     * Сгенерировать уникальный ключ для списка техники на основе параметров запроса.
     */
    public function getIndexKey(Request $request): string
    {
        $params = [
            'category' => $request->category,
            'location' => $request->location,
            'min_price' => $request->min_price,
            'max_price' => $request->max_price,
            'year_from' => $request->year_from,
            'year_to' => $request->year_to,
            'search' => $request->search,
            'autocomplete' => $request->autocomplete,
            'sort' => $request->sort ?? 'newest',
            'per_page' => $request->per_page ?? 12,
            'page' => $request->page ?? 1,
        ];

        return self::CACHE_PREFIX . 'index_' . md5(serialize($params));
    }

    /**
     * Сгенерировать ключ для детальной страницы техники.
     */
    public function getShowKey(int $equipmentId): string
    {
        return self::SHOW_PREFIX . $equipmentId;
    }

    /**
     * Запомнить ключ кэша для последующей инвалидации.
     */
    private function trackKey(string $key): void
    {
        try {
            $keys = Cache::get(self::KEYS_KEY, []);
            if (!in_array($key, $keys)) {
                $keys[] = $key;
                Cache::put(self::KEYS_KEY, $keys, now()->addHours(2));
            }
        } catch (\Throwable $e) {
            Log::warning('CatalogCache: failed to track key', ['key' => $key, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Получить данные из кэша или сохранить результат callback.
     */
    public function rememberIndex(Request $request, callable $callback)
    {
        $key = $this->getIndexKey($request);

        return Cache::remember($key, now()->addMinutes(self::INDEX_TTL), function () use ($key, $callback) {
            $this->trackKey($key);
            return $callback();
        });
    }

    /**
     * Получить данные детальной страницы из кэша или сохранить.
     */
    public function rememberShow(int $equipmentId, callable $callback)
    {
        $key = $this->getShowKey($equipmentId);

        return Cache::remember($key, now()->addMinutes(self::SHOW_TTL), function () use ($key, $callback) {
            $this->trackKey($key);
            return $callback();
        });
    }

    /**
     * Очистить весь кэш каталога (список + детальные страницы).
     */
    public function clearCache(): void
    {
        try {
            $keys = Cache::get(self::KEYS_KEY, []);
            foreach ($keys as $key) {
                Cache::forget($key);
            }
            Cache::forget(self::KEYS_KEY);
            Log::info('Catalog cache cleared', ['keys_removed' => count($keys)]);
        } catch (\Throwable $e) {
            Log::error('Catalog cache clear error: ' . $e->getMessage());
        }
    }

    /**
     * Очистить кэш только детальной страницы конкретной техники.
     */
    public function clearShowCache(int $equipmentId): void
    {
        $key = $this->getShowKey($equipmentId);
        Cache::forget($key);

        // Также удаляем из трекера
        try {
            $keys = Cache::get(self::KEYS_KEY, []);
            $keys = array_filter($keys, fn($k) => $k !== $key);
            Cache::put(self::KEYS_KEY, array_values($keys), now()->addHours(2));
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /**
     * Очистить кэш каталога (удобный статический метод для вызова из контроллеров).
     */
    public static function clearCatalogCache(): void
    {
        try {
            $service = app(self::class);
            $service->clearCache();
        } catch (\Throwable $e) {
            Log::error('CatalogCache: clearCatalogCache error: ' . $e->getMessage());
        }
    }

    /**
     * Очистить кэш детальной страницы (статический метод).
     */
    public static function clearEquipmentShowCache(int $equipmentId): void
    {
        try {
            $service = app(self::class);
            $service->clearShowCache($equipmentId);
        } catch (\Throwable $e) {
            Log::error('CatalogCache: clearEquipmentShowCache error: ' . $e->getMessage());
        }
    }
}
