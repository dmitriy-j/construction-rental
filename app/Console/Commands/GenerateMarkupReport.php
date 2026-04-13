<?php

namespace App\Console\Commands;

use App\Models\PlatformMarkup;
use App\Services\MarkupImportExportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class GenerateMarkupReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'markups:report
                            {--type=summary : Тип отчета (summary, performance, revenue, audit)}
                            {--period=month : Период (day, week, month, quarter, year, custom)}
                            {--date-from= : Начальная дата (для custom)}
                            {--date-to= : Конечная дата (для custom)}
                            {--format=csv : Формат вывода (csv, json, html)}
                            {--output= : Файл для сохранения отчета}
                            {--email= : Email для отправки отчета}
                            {--include-charts : Включить графики (для HTML)}
                            {--verbose : Подробный вывод}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Генерация отчетов по наценкам';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        $period = $this->option('period');

        $this->info("📊 Генерация отчета: {$type} за период: {$period}");

        try {
            $reportData = $this->generateReportData($type, $period);

            if ($this->option('verbose')) {
                $this->displayReportPreview($reportData);
            }

            $output = $this->formatReport($reportData, $this->option('format'));

            if ($this->option('output')) {
                $this->saveReport($output, $this->option('output'));
            } else {
                $this->outputReport($output, $this->option('format'));
            }

            if ($this->option('email')) {
                $this->sendReportEmail($output, $this->option('email'), $type, $period);
            }

            $this->info('✅ Отчет успешно сгенерирован');

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Ошибка генерации отчета: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Генерация данных отчета
     */
    protected function generateReportData(string $type, string $period): array
    {
        $dateRange = $this->getDateRange($period);

        return match($type) {
            'summary' => $this->generateSummaryReport($dateRange),
            'performance' => $this->generatePerformanceReport($dateRange),
            'revenue' => $this->generateRevenueReport($dateRange),
            'audit' => $this->generateAuditReport($dateRange),
            default => throw new \Exception("Неизвестный тип отчета: {$type}")
        };
    }

    /**
     * Получение диапазона дат
     */
    protected function getDateRange(string $period): array
    {
        return match($period) {
            'day' => [
                'from' => now()->subDay(),
                'to' => now()
            ],
            'week' => [
                'from' => now()->subWeek(),
                'to' => now()
            ],
            'month' => [
                'from' => now()->subMonth(),
                'to' => now()
            ],
            'quarter' => [
                'from' => now()->subQuarter(),
                'to' => now()
            ],
            'year' => [
                'from' => now()->subYear(),
                'to' => now()
            ],
            'custom' => [
                'from' => $this->option('date-from')
                    ? Carbon::parse($this->option('date-from'))
                    : now()->subMonth(),
                'to' => $this->option('date-to')
                    ? Carbon::parse($this->option('date-to'))
                    : now()
            ],
            default => [
                'from' => now()->subMonth(),
                'to' => now()
            ]
        };
    }

    /**
     * Генерация сводного отчета
     */
    protected function generateSummaryReport(array $dateRange): array
    {
        $stats = $this->getBasicStats($dateRange);
        $topMarkups = $this->getTopMarkups($dateRange);
        $typeDistribution = $this->getTypeDistribution($dateRange);

        return [
            'report_type' => 'summary',
            'period' => $dateRange,
            'generated_at' => now()->toISOString(),
            'statistics' => $stats,
            'top_markups' => $topMarkups,
            'type_distribution' => $typeDistribution,
            'trends' => $this->getTrends($dateRange),
        ];
    }

    /**
     * Генерация отчета по производительности
     */
    protected function generatePerformanceReport(array $dateRange): array
    {
        $performanceData = DB::table('platform_markup_audits')
            ->whereBetween('created_at', [$dateRange['from'], $dateRange['to']])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as changes')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $responseTimes = $this->getResponseTimeStats($dateRange);
        $efficiency = $this->getEfficiencyStats($dateRange);

        return [
            'report_type' => 'performance',
            'period' => $dateRange,
            'generated_at' => now()->toISOString(),
            'performance_metrics' => [
                'total_changes' => $performanceData->sum('changes'),
                'average_changes_per_day' => round($performanceData->avg('changes'), 2),
                'peak_activity_day' => $performanceData->sortByDesc('changes')->first(),
            ],
            'response_times' => $responseTimes,
            'efficiency_metrics' => $efficiency,
            'daily_activity' => $performanceData,
        ];
    }

    /**
     * Генерация отчета по доходам
     */
    protected function generateRevenueReport(array $dateRange): array
    {
        // Здесь должна быть интеграция с финансовой системой
        // Временно используем демо-данные
        $revenueByType = DB::table('platform_markups')
            ->whereBetween('created_at', [$dateRange['from'], $dateRange['to']])
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type')
            ->toArray();

        $revenueTrend = $this->getRevenueTrend($dateRange);

        return [
            'report_type' => 'revenue',
            'period' => $dateRange,
            'generated_at' => now()->toISOString(),
            'revenue_summary' => [
                'estimated_revenue' => array_sum($revenueByType) * 1000, // Демо-данные
                'markup_applications' => array_sum($revenueByType),
            ],
            'revenue_by_type' => $revenueByType,
            'revenue_trend' => $revenueTrend,
            'top_revenue_markups' => $this->getTopRevenueMarkups($dateRange),
        ];
    }

    /**
     * Генерация отчета аудита
     */
    protected function generateAuditReport(array $dateRange): array
    {
        $auditData = DB::table('platform_markup_audits')
            ->whereBetween('created_at', [$dateRange['from'], $dateRange['to']])
            ->selectRaw('action, COUNT(*) as count')
            ->groupBy('action')
            ->get();

        $userActivity = DB::table('platform_markup_audits')
            ->join('users', 'platform_markup_audits.user_id', '=', 'users.id')
            ->whereBetween('platform_markup_audits.created_at', [$dateRange['from'], $dateRange['to']])
            ->selectRaw('users.name, COUNT(*) as changes')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('changes')
            ->limit(10)
            ->get();

        return [
            'report_type' => 'audit',
            'period' => $dateRange,
            'generated_at' => now()->toISOString(),
            'audit_summary' => [
                'total_changes' => $auditData->sum('count'),
                'changes_by_action' => $auditData->pluck('count', 'action')->toArray(),
            ],
            'user_activity' => $userActivity,
            'recent_changes' => $this->getRecentChanges($dateRange),
        ];
    }

    /**
     * Получение базовой статистики
     */
    protected function getBasicStats(array $dateRange): array
    {
        $totalMarkups = PlatformMarkup::count();
        $activeMarkups = PlatformMarkup::where('is_active', true)->count();
        $recentMarkups = PlatformMarkup::whereBetween('created_at', [$dateRange['from'], $dateRange['to']])->count();

        return [
            'total_markups' => $totalMarkups,
            'active_markups' => $activeMarkups,
            'recent_markups' => $recentMarkups,
            'activation_rate' => $totalMarkups > 0 ? round(($activeMarkups / $totalMarkups) * 100, 2) : 0,
        ];
    }

    /**
     * Получение топ наценок
     */
    protected function getTopMarkups(array $dateRange): array
    {
        return PlatformMarkup::with(['markupable'])
            ->where('is_active', true)
            ->orderBy('priority', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($markup) {
                return [
                    'id' => $markup->id,
                    'type' => $markup->type,
                    'value' => $markup->value,
                    'priority' => $markup->priority,
                    'entity_type' => $markup->entity_type,
                    'is_active' => $markup->is_active,
                    'markupable_type' => $markup->markupable_type,
                ];
            })
            ->toArray();
    }

    /**
     * Получение распределения по типам
     */
    protected function getTypeDistribution(array $dateRange): array
    {
        return PlatformMarkup::whereBetween('created_at', [$dateRange['from'], $dateRange['to']])
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type')
            ->toArray();
    }

    /**
     * Получение трендов
     */
    protected function getTrends(array $dateRange): array
    {
        $previousPeriod = [
            'from' => $dateRange['from']->copy()->sub($dateRange['to']->diff($dateRange['from'])),
            'to' => $dateRange['from']
        ];

        $currentCount = PlatformMarkup::whereBetween('created_at', [$dateRange['from'], $dateRange['to']])->count();
        $previousCount = PlatformMarkup::whereBetween('created_at', [$previousPeriod['from'], $previousPeriod['to']])->count();

        $growth = $previousCount > 0
            ? (($currentCount - $previousCount) / $previousCount) * 100
            : 0;

        return [
            'growth_rate' => round($growth, 2),
            'period_comparison' => [
                'current' => $currentCount,
                'previous' => $previousCount,
            ]
        ];
    }

    /**
     * Получение статистики времени ответа
     */
    protected function getResponseTimeStats(array $dateRange): array
    {
        // Демо-данные - в реальной системе нужно интегрировать с мониторингом
        return [
            'average_response_time' => 45.2,
            'p95_response_time' => 120.5,
            'p99_response_time' => 250.8,
        ];
    }

    /**
     * Получение статистики эффективности
     */
    protected function getEfficiencyStats(array $dateRange): array
    {
        // Демо-данные
        return [
            'cache_hit_rate' => 98.5,
            'calculation_success_rate' => 99.8,
            'average_calculation_time' => 12.3,
        ];
    }

    /**
     * Получение тренда доходов
     */
    protected function getRevenueTrend(array $dateRange): array
    {
        // Демо-данные
        return [
            'current_period' => 125000,
            'previous_period' => 110000,
            'growth' => 13.6,
        ];
    }

    /**
     * Получение топ наценок по доходу
     */
    protected function getTopRevenueMarkups(array $dateRange): array
    {
        // Демо-данные
        return [
            ['id' => 1, 'type' => 'percent', 'revenue' => 25000],
            ['id' => 2, 'type' => 'fixed', 'revenue' => 18000],
            ['id' => 3, 'type' => 'combined', 'revenue' => 15000],
        ];
    }

    /**
     * Получение последних изменений
     */
    protected function getRecentChanges(array $dateRange): array
    {
        return DB::table('platform_markup_audits')
            ->join('users', 'platform_markup_audits.user_id', '=', 'users.id')
            ->whereBetween('platform_markup_audits.created_at', [$dateRange['from'], $dateRange['to']])
            ->select('platform_markup_audits.*', 'users.name as user_name')
            ->orderBy('platform_markup_audits.created_at', 'desc')
            ->limit(20)
            ->get()
            ->toArray();
    }

    /**
     * Форматирование отчета
     */
    protected function formatReport(array $data, string $format): string
    {
        return match($format) {
            'json' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'html' => $this->formatHtmlReport($data),
            'csv' => $this->formatCsvReport($data),
            default => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        };
    }

    /**
     * Форматирование HTML отчета
     */
    protected function formatHtmlReport(array $data): string
    {
        $title = "Отчет по наценкам: {$data['report_type']}";

        $html = "<!DOCTYPE html>
        <html>
        <head>
            <title>{$title}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { background: #f8f9fa; padding: 20px; border-radius: 5px; }
                .section { margin: 20px 0; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                .metric { display: inline-block; margin: 10px; padding: 10px; background: #e9ecef; border-radius: 3px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>{$title}</h1>
                <p>Период: {$data['period']['from']->format('Y-m-d')} - {$data['period']['to']->format('Y-m-d')}</p>
                <p>Сгенерирован: {$data['generated_at']}</p>
            </div>";

        // Добавление контента в зависимости от типа отчета
        if (isset($data['statistics'])) {
            $html .= $this->renderStatisticsSection($data['statistics']);
        }

        if (isset($data['top_markups'])) {
            $html .= $this->renderTopMarkupsSection($data['top_markups']);
        }

        $html .= "</body></html>";

        return $html;
    }

    /**
     * Рендер секции статистики
     */
    protected function renderStatisticsSection(array $stats): string
    {
        $html = "<div class='section'>
            <h2>Основная статистика</h2>
            <div class='metrics'>";

        foreach ($stats as $key => $value) {
            $label = $this->getStatLabel($key);
            $html .= "<div class='metric'><strong>{$label}:</strong> {$value}</div>";
        }

        $html .= "</div></div>";
        return $html;
    }

    /**
     * Рендер секции топ наценок
     */
    protected function renderTopMarkupsSection(array $markups): string
    {
        $html = "<div class='section'>
            <h2>Топ наценок</h2>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Тип</th>
                    <th>Значение</th>
                    <th>Приоритет</th>
                    <th>Контекст</th>
                </tr>";

        foreach ($markups as $markup) {
            $html .= "<tr>
                <td>{$markup['id']}</td>
                <td>{$markup['type']}</td>
                <td>{$markup['value']}</td>
                <td>{$markup['priority']}</td>
                <td>{$markup['entity_type']}</td>
            </tr>";
        }

        $html .= "</table></div>";
        return $html;
    }

    /**
     * Получение метки для статистики
     */
    protected function getStatLabel(string $key): string
    {
        $labels = [
            'total_markups' => 'Всего наценок',
            'active_markups' => 'Активных наценок',
            'recent_markups' => 'Создано за период',
            'activation_rate' => 'Процент активации',
        ];

        return $labels[$key] ?? $key;
    }

    /**
     * Форматирование CSV отчета
     */
    protected function formatCsvReport(array $data): string
    {
        $output = fopen('php://temp', 'r+');

        // Заголовок
        fputcsv($output, ['Отчет по наценкам', $data['report_type']], ';');
        fputcsv($output, ['Период', $data['period']['from']->format('Y-m-d') . ' - ' . $data['period']['to']->format('Y-m-d')], ';');
        fputcsv($output, ['Сгенерирован', $data['generated_at']], ';');
        fputcsv($output, [], ';');

        // Данные в зависимости от типа отчета
        if (isset($data['statistics'])) {
            fputcsv($output, ['Статистика'], ';');
            foreach ($data['statistics'] as $key => $value) {
                fputcsv($output, [$this->getStatLabel($key), $value], ';');
            }
            fputcsv($output, [], ';');
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return $content;
    }

    /**
     * Вывод отчета
     */
    protected function outputReport(string $output, string $format): void
    {
        if ($format === 'csv') {
            $this->line($output);
        } else {
            $this->line($output);
        }
    }

    /**
     * Сохранение отчета в файл
     */
    protected function saveReport(string $output, string $filename): void
    {
        $directory = 'markups/reports';
        Storage::disk('local')->makeDirectory($directory);

        $fullPath = "{$directory}/{$filename}";
        Storage::disk('local')->put($fullPath, $output);

        $this->info("Отчет сохранен: storage/app/{$fullPath}");
    }

    /**
     * Отправка отчета по email
     */
    protected function sendReportEmail(string $output, string $email, string $type, string $period): void
    {
        // Реализация отправки email
        $this->warn("Отправка отчетов по email находится в разработке");
        $this->line("Будет отправлено на: {$email}");
        $this->line("Тип отчета: {$type}");
        $this->line("Период: {$period}");
    }

    /**
     * Отображение предпросмотра отчета
     */
    protected function displayReportPreview(array $data): void
    {
        $this->info('📋 Предпросмотр отчета:');

        if (isset($data['statistics'])) {
            $this->line('Основная статистика:');
            foreach ($data['statistics'] as $key => $value) {
                $this->line("  - {$this->getStatLabel($key)}: <comment>{$value}</comment>");
            }
        }

        if (isset($data['trends'])) {
            $trend = $data['trends']['growth_rate'];
            $trendIcon = $trend >= 0 ? '📈' : '📉';
            $this->line("Тренд: {$trendIcon} <comment>{$trend}%</comment>");
        }
    }

    /**
     * Дополнительная информация о команде
     */
    public function getHelp(): string
    {
        return <<<HELP
Генерация различных отчетов по системе наценок.

Примеры использования:

  <comment>Сводный отчет за месяц:</comment>
  php artisan markups:report --type=summary --period=month

  <comment>Отчет по производительности за неделю:</comment>
  php artisan markups:report --type=performance --period=week

  <comment>Отчет по доходам за квартал в JSON:</comment>
  php artisan markups:report --type=revenue --period=quarter --format=json

  <comment>Отчет аудита за произвольный период:</comment>
  php artisan markups:report --type=audit --period=custom --date-from=2024-01-01 --date-to=2024-01-31

  <comment>Сохранение отчета в файл:</comment>
  php artisan markups:report --type=summary --output=my_report.csv

  <comment>Подробный вывод:</comment>
  php artisan markups:report --type=summary --verbose

Опции:

  <comment>--type=</comment>        Тип отчета (summary, performance, revenue, audit)
  <comment>--period=</comment>      Период (day, week, month, quarter, year, custom)
  <comment>--date-from=</comment>   Начальная дата (для custom)
  <comment>--date-to=</comment>     Конечная дата (для custom)
  <comment>--format=</comment>      Формат вывода (csv, json, html)
  <comment>--output=</comment>      Файл для сохранения отчета
  <comment>--email=</comment>       Email для отправки отчета
  <comment>--verbose</comment>      Подробный вывод
HELP;
    }
}
