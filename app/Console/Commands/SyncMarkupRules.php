<?php

namespace App\Console\Commands;

use App\Models\PlatformMarkup;
use App\Services\MarkupImportExportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SyncMarkupRules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'markups:sync
                            {--export : Экспорт наценок в файл}
                            {--import : Импорт наценок из файла}
                            {--file= : Имя файла для импорта/экспорта}
                            {--environment= : Целевое окружение (staging, production)}
                            {--dry-run : Предпросмотр без применения изменений}
                            {--force : Принудительное выполнение без подтверждения}
                            {--include-audit : Включить историю аудита}
                            {--only-active : Только активные наценки}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Синхронизация правил наценок между окружениями';

    /**
     * Сервис импорта/экспорта
     */
    protected MarkupImportExportService $importExportService;

    /**
     * Execute the console command.
     */
    public function handle(MarkupImportExportService $importExportService)
    {
        $this->importExportService = $importExportService;

        try {
            if ($this->option('export')) {
                return $this->handleExport();
            }

            if ($this->option('import')) {
                return $this->handleImport();
            }

            $this->error('Не указана операция. Используйте --export или --import');
            return 1;

        } catch (\Exception $e) {
            $this->error('Ошибка синхронизации: ' . $e->getMessage());
            $this->error('Trace: ' . $e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Обработка экспорта наценок
     */
    protected function handleExport(): int
    {
        $filename = $this->option('file') ?: $this->generateExportFilename();
        $filters = $this->getExportFilters();

        $this->info('🔄 Подготовка экспорта наценок...');

        $stats = $this->getExportStats($filters);
        $this->displayExportStats($stats);

        if (!$this->option('force') && !$this->confirmExport($stats['total'])) {
            $this->info('Экспорт отменен.');
            return 0;
        }

        $this->info('📤 Выполнение экспорта...');

        try {
            $content = $this->importExportService->exportToCsv($filters);

            Storage::disk('local')->put("markups/exports/{$filename}", $content);

            $fullPath = storage_path("app/markups/exports/{$filename}");

            $this->info('✅ Экспорт успешно завершен!');
            $this->line("Файл: {$fullPath}");
            $this->line("Размер: " . number_format(strlen($content)) . " байт");
            $this->line("Наценок экспортировано: {$stats['total']}");

            // Генерация отчета
            $this->generateExportReport($filename, $stats, $filters);

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Ошибка экспорта: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Обработка импорта наценок
     */
    protected function handleImport(): int
    {
        $filename = $this->option('file');

        if (!$filename) {
            $this->error('Не указан файл для импорта. Используйте --file=filename.csv');
            return 1;
        }

        $filePath = storage_path("app/markups/exports/{$filename}");

        if (!file_exists($filePath)) {
            $this->error("Файл не найден: {$filePath}");
            $this->line('Доступные файлы:');

            $files = Storage::disk('local')->files('markups/exports');
            foreach ($files as $file) {
                $this->line(" - " . basename($file));
            }
            return 1;
        }

        $this->info('🔄 Подготовка импорта наценок...');
        $this->line("Файл: {$filePath}");

        $content = Storage::disk('local')->get("markups/exports/{$filename}");
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🚧 Режим предпросмотра - изменения не будут применены');
        }

        if (!$this->option('force') && !$this->confirmImport($dryRun)) {
            $this->info('Импорт отменен.');
            return 0;
        }

        $this->info('📥 Выполнение импорта...');

        try {
            $results = $this->importExportService->importFromCsv($content, $dryRun);

            $this->displayImportResults($results);

            if (!$dryRun && empty($results['errors'])) {
                $this->generateImportReport($filename, $results);
            }

            return empty($results['errors']) ? 0 : 1;

        } catch (\Exception $e) {
            $this->error('❌ Ошибка импорта: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Генерация имени файла для экспорта
     */
    protected function generateExportFilename(): string
    {
        $timestamp = now()->format('Y-m-d_His');
        $env = app()->environment();

        return "markups_export_{$env}_{$timestamp}.csv";
    }

    /**
     * Получение фильтров для экспорта
     */
    protected function getExportFilters(): array
    {
        $filters = [];

        if ($this->option('only-active')) {
            $filters['is_active'] = true;
        }

        return $filters;
    }

    /**
     * Получение статистики для экспорта
     */
    protected function getExportStats(array $filters): array
    {
        $query = PlatformMarkup::query();

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        $total = $query->count();

        $byType = $query->clone()
            ->select('type', DB::raw('COUNT(*) as count'))
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        $byEntity = $query->clone()
            ->select('entity_type', DB::raw('COUNT(*) as count'))
            ->groupBy('entity_type')
            ->pluck('count', 'entity_type')
            ->toArray();

        return [
            'total' => $total,
            'by_type' => $byType,
            'by_entity' => $byEntity,
        ];
    }

    /**
     * Отображение статистики экспорта
     */
    protected function displayExportStats(array $stats): void
    {
        $this->line("Всего наценок: <comment>{$stats['total']}</comment>");

        if (!empty($stats['by_type'])) {
            $this->line("По типам:");
            foreach ($stats['by_type'] as $type => $count) {
                $this->line("  - {$type}: <comment>{$count}</comment>");
            }
        }

        if (!empty($stats['by_entity'])) {
            $this->line("По контекстам:");
            foreach ($stats['by_entity'] as $entity => $count) {
                $this->line("  - {$entity}: <comment>{$count}</comment>");
            }
        }
    }

    /**
     * Подтверждение экспорта
     */
    protected function confirmExport(int $count): bool
    {
        if ($this->option('force')) {
            return true;
        }

        $this->warn("Будет экспортировано наценок: {$count}");

        return $this->confirm('Продолжить экспорт?');
    }

    /**
     * Подтверждение импорта
     */
    protected function confirmImport(bool $dryRun): bool
    {
        if ($this->option('force')) {
            return true;
        }

        $message = $dryRun
            ? 'Выполнить предпросмотр импорта?'
            : 'Выполнить импорт наценок?';

        return $this->confirm($message);
    }

    /**
     * Отображение результатов импорта
     */
    protected function displayImportResults(array $results): void
    {
        $this->info('📊 Результаты импорта:');
        $this->line("Обработано записей: <comment>{$results['processed']}</comment>");
        $this->line("Создано: <info>{$results['created']}</info>");
        $this->line("Обновлено: <info>{$results['updated']}</info>");

        if (!empty($results['errors'])) {
            $this->error("Ошибки: " . count($results['errors']));

            foreach (array_slice($results['errors'], 0, 10) as $error) {
                $this->error("  - {$error}");
            }

            if (count($results['errors']) > 10) {
                $this->error("  ... и еще " . (count($results['errors']) - 10) . " ошибок");
            }
        } else {
            $this->info('✅ Ошибок не обнаружено');
        }

        if ($results['dry_run']) {
            $this->warn('💡 Это был предпросмотр. Для применения изменений запустите без --dry-run');
        }
    }

    /**
     * Генерация отчета об экспорте
     */
    protected function generateExportReport(string $filename, array $stats, array $filters): void
    {
        $report = [
            'export' => [
                'filename' => $filename,
                'timestamp' => now()->toISOString(),
                'environment' => app()->environment(),
                'filters' => $filters,
            ],
            'statistics' => $stats,
            'metadata' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'database' => config('database.default'),
            ]
        ];

        $reportFilename = str_replace('.csv', '_report.json', $filename);
        Storage::disk('local')->put(
            "markups/exports/{$reportFilename}",
            json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        $this->line("Отчет: storage/app/markups/exports/{$reportFilename}");
    }

    /**
     * Генерация отчета об импорте
     */
    protected function generateImportReport(string $filename, array $results): void
    {
        $report = [
            'import' => [
                'source_file' => $filename,
                'timestamp' => now()->toISOString(),
                'environment' => app()->environment(),
                'dry_run' => false,
            ],
            'results' => $results,
            'metadata' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'executed_by' => get_current_user(),
            ]
        ];

        $reportFilename = 'import_report_' . now()->format('Y-m-d_His') . '.json';
        Storage::disk('local')->put(
            "markups/imports/{$reportFilename}",
            json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        $this->line("Отчет импорта: storage/app/markups/imports/{$reportFilename}");
    }

    /**
     * Дополнительная информация о команде
     */
    public function getHelp(): string
    {
        return <<<HELP
Синхронизация правил наценок между окружениями.

Примеры использования:

  <comment>Экспорт всех наценок:</comment>
  php artisan markups:sync --export

  <comment>Экспорт только активных наценок:</comment>
  php artisan markups:sync --export --only-active

  <comment>Экспорт с указанием имени файла:</comment>
  php artisan markups:sync --export --file=my_markups.csv

  <comment>Предпросмотр импорта:</comment>
  php artisan markups:sync --import --file=markups.csv --dry-run

  <comment>Импорт наценок:</comment>
  php artisan markups:sync --import --file=markups.csv

  <comment>Принудительный импорт без подтверждения:</comment>
  php artisan markups:sync --import --file=markups.csv --force

Опции:

  <comment>--export</comment>        Экспорт наценок в файл
  <comment>--import</comment>        Импорт наценок из файла
  <comment>--file=</comment>         Имя файла для импорта/экспорта
  <comment>--dry-run</comment>       Предпросмотр без применения изменений
  <comment>--force</comment>         Принудительное выполнение без подтверждения
  <comment>--only-active</comment>   Только активные наценки (для экспорта)
  <comment>--include-audit</comment> Включить историю аудита (в разработке)

Файлы сохраняются в: storage/app/markups/exports/
HELP;
    }
}
