<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Настройки системы наценок платформы
    |--------------------------------------------------------------------------
    |
    | Этот файл содержит все конфигурационные параметры системы наценок.
    | Значения могут быть переопределены в .env файле.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Настройки приоритетов
    |--------------------------------------------------------------------------
    */
    'priorities' => [
        'general' => [
            'min' => env('MARKUP_PRIORITY_GENERAL_MIN', 0),
            'max' => env('MARKUP_PRIORITY_GENERAL_MAX', 99),
            'default' => env('MARKUP_PRIORITY_GENERAL_DEFAULT', 50),
        ],
        'company' => [
            'min' => env('MARKUP_PRIORITY_COMPANY_MIN', 100),
            'max' => env('MARKUP_PRIORITY_COMPANY_MAX', 199),
            'default' => env('MARKUP_PRIORITY_COMPANY_DEFAULT', 150),
        ],
        'category' => [
            'min' => env('MARKUP_PRIORITY_CATEGORY_MIN', 200),
            'max' => env('MARKUP_PRIORITY_CATEGORY_MAX', 299),
            'default' => env('MARKUP_PRIORITY_CATEGORY_DEFAULT', 250),
        ],
        'equipment' => [
            'min' => env('MARKUP_PRIORITY_EQUIPMENT_MIN', 300),
            'max' => env('MARKUP_PRIORITY_EQUIPMENT_MAX', 399),
            'default' => env('MARKUP_PRIORITY_EQUIPMENT_DEFAULT', 350),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Ограничения системы
    |--------------------------------------------------------------------------
    */
    'limits' => [
        'max_percent_markup' => env('MARKUP_MAX_PERCENT', 50),
        'max_fixed_markup' => env('MARKUP_MAX_FIXED', 1000),
        'max_active_markups' => env('MARKUP_MAX_ACTIVE', 1000),
        'max_equipment_markups' => env('MARKUP_MAX_EQUIPMENT', 5),
        'min_reason_length' => env('MARKUP_MIN_REASON_LENGTH', 10),
        'max_reason_length' => env('MARKUP_MAX_REASON_LENGTH', 500),
    ],

    /*
    |--------------------------------------------------------------------------
    | Настройки кэширования
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => env('MARKUP_CACHE_ENABLED', true),
        'duration' => env('MARKUP_CACHE_DURATION', 3600),
        'prefix' => env('MARKUP_CACHE_PREFIX', 'markup_'),
        'clear_on_update' => env('MARKUP_CACHE_CLEAR_ON_UPDATE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Настройки уведомлений
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'enabled' => env('MARKUP_NOTIFICATIONS_ENABLED', true),
        'expiration_warning_days' => env('MARKUP_EXPIRATION_WARNING_DAYS', 7),
        'daily_report_time' => env('MARKUP_DAILY_REPORT_TIME', '08:00'),
        'channels' => [
            'mail' => env('MARKUP_NOTIFY_MAIL', true),
            'database' => env('MARKUP_NOTIFY_DATABASE', true),
            'broadcast' => env('MARKUP_NOTIFY_BROADCAST', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Настройки экспорта/импорта
    |--------------------------------------------------------------------------
    */
    'export' => [
        'csv_delimiter' => env('MARKUP_CSV_DELIMITER', ';'),
        'date_format' => env('MARKUP_EXPORT_DATE_FORMAT', 'Y-m-d H:i:s'),
        'timezone' => env('MARKUP_EXPORT_TIMEZONE', 'Europe/Moscow'),
        'max_file_size' => env('MARKUP_MAX_FILE_SIZE', 10485760), // 10MB
    ],

    /*
    |--------------------------------------------------------------------------
    | Настройки производительности
    |--------------------------------------------------------------------------
    */
    'performance' => [
        'batch_size' => env('MARKUP_BATCH_SIZE', 100),
        'timeout' => env('MARKUP_OPERATION_TIMEOUT', 300),
        'memory_limit' => env('MARKUP_MEMORY_LIMIT', '256M'),
        'max_calculations_per_minute' => env('MARKUP_MAX_CALCULATIONS_PER_MINUTE', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Настройки безопасности
    |--------------------------------------------------------------------------
    */
    'security' => [
        'audit_retention_days' => env('MARKUP_AUDIT_RETENTION_DAYS', 1825), // 5 лет
        'max_login_attempts' => env('MARKUP_MAX_LOGIN_ATTEMPTS', 5),
        'password_min_length' => env('MARKUP_PASSWORD_MIN_LENGTH', 8),
        'session_timeout' => env('MARKUP_SESSION_TIMEOUT', 120),
    ],

    /*
    |--------------------------------------------------------------------------
    | Настройки API
    |--------------------------------------------------------------------------
    */
    'api' => [
        'rate_limit' => env('MARKUP_API_RATE_LIMIT', 1000),
        'version' => env('MARKUP_API_VERSION', 'v1'),
        'cors_enabled' => env('MARKUP_API_CORS_ENABLED', true),
        'cors_origins' => explode(',', env('MARKUP_API_CORS_ORIGINS', '')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Настройки отладки
    |--------------------------------------------------------------------------
    */
    'debug' => [
        'log_calculations' => env('MARKUP_LOG_CALCULATIONS', false),
        'log_audit_changes' => env('MARKUP_LOG_AUDIT_CHANGES', true),
        'log_performance' => env('MARKUP_LOG_PERFORMANCE', false),
        'verbose_errors' => env('MARKUP_VERBOSE_ERRORS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Настройки по умолчанию
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'markup_type' => env('MARKUP_DEFAULT_TYPE', 'fixed'),
        'calculation_type' => env('MARKUP_DEFAULT_CALCULATION_TYPE', 'addition'),
        'entity_type' => env('MARKUP_DEFAULT_ENTITY_TYPE', 'order'),
        'value' => env('MARKUP_DEFAULT_VALUE', 100),
        'is_active' => env('MARKUP_DEFAULT_ACTIVE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Сезонные коэффициенты по умолчанию
    |--------------------------------------------------------------------------
    */
    'seasonal_coefficients' => [
        'high_season' => [
            'months' => [5, 6, 7, 8, 9], // Май-Сентябрь
            'coefficient' => env('MARKUP_HIGH_SEASON_COEFFICIENT', 1.5),
        ],
        'medium_season' => [
            'months' => [3, 4, 10], // Март-Апрель, Октябрь
            'coefficient' => env('MARKUP_MEDIUM_SEASON_COEFFICIENT', 1.0),
        ],
        'low_season' => [
            'months' => [1, 2, 11, 12], // Январь-Февраль, Ноябрь-Декабрь
            'coefficient' => env('MARKUP_LOW_SEASON_COEFFICIENT', 0.7),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Настройки ступенчатых наценок
    |--------------------------------------------------------------------------
    */
    'tiered' => [
        'max_tiers' => env('MARKUP_MAX_TIERS', 10),
        'default_tiers' => [
            [
                'min' => 0,
                'max' => 100,
                'type' => 'fixed',
                'value' => 50,
            ],
            [
                'min' => 101,
                'max' => 200,
                'type' => 'fixed',
                'value' => 40,
            ],
            [
                'min' => 201,
                'max' => 9999,
                'type' => 'percent',
                'value' => 5,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Настройки массовых операций
    |--------------------------------------------------------------------------
    */
    'bulk_operations' => [
        'max_items' => env('MARKUP_BULK_MAX_ITEMS', 1000),
        'timeout' => env('MARKUP_BULK_TIMEOUT', 600),
        'confirm_threshold' => env('MARKUP_BULK_CONFIRM_THRESHOLD', 10),
        'enable_preview' => env('MARKUP_BULK_ENABLE_PREVIEW', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Настройки UI/UX
    |--------------------------------------------------------------------------
    */
    'ui' => [
        'items_per_page' => env('MARKUP_ITEMS_PER_PAGE', 25),
        'enable_search' => env('MARKUP_ENABLE_SEARCH', true),
        'enable_filters' => env('MARKUP_ENABLE_FILTERS', true),
        'default_view' => env('MARKUP_DEFAULT_VIEW', 'table'), // table, grid, cards
        'theme' => env('MARKUP_THEME', 'light'), // light, dark, auto
    ],

    /*
    |--------------------------------------------------------------------------
    | Интеграции с внешними системами
    |--------------------------------------------------------------------------
    */
    'integrations' => [
        'accounting_system' => [
            'enabled' => env('MARKUP_ACCOUNTING_INTEGRATION', false),
            'endpoint' => env('MARKUP_ACCOUNTING_ENDPOINT'),
            'api_key' => env('MARKUP_ACCOUNTING_API_KEY'),
            'sync_interval' => env('MARKUP_ACCOUNTING_SYNC_INTERVAL', 60),
        ],
        'crm_system' => [
            'enabled' => env('MARKUP_CRM_INTEGRATION', false),
            'endpoint' => env('MARKUP_CRM_ENDPOINT'),
            'api_key' => env('MARKUP_CRM_API_KEY'),
        ],
        'analytics' => [
            'enabled' => env('MARKUP_ANALYTICS_ENABLED', true),
            'track_page_views' => env('MARKUP_TRACK_PAGE_VIEWS', true),
            'track_user_actions' => env('MARKUP_TRACK_USER_ACTIONS', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Резервное копирование
    |--------------------------------------------------------------------------
    */
    'backup' => [
        'enabled' => env('MARKUP_BACKUP_ENABLED', true),
        'schedule' => env('MARKUP_BACKUP_SCHEDULE', '0 2 * * *'), // Ежедневно в 2:00
        'retention_days' => env('MARKUP_BACKUP_RETENTION_DAYS', 30),
        'include_audit_logs' => env('MARKUP_BACKUP_INCLUDE_AUDIT', true),
        'storage_disk' => env('MARKUP_BACKUP_STORAGE_DISK', 'local'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Мониторинг и метрики
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'enabled' => env('MARKUP_MONITORING_ENABLED', true),
        'health_check_interval' => env('MARKUP_HEALTH_CHECK_INTERVAL', 300),
        'performance_metrics' => [
            'response_time_threshold' => env('MARKUP_RESPONSE_TIME_THRESHOLD', 1000),
            'error_rate_threshold' => env('MARKUP_ERROR_RATE_THRESHOLD', 1),
            'memory_usage_threshold' => env('MARKUP_MEMORY_USAGE_THRESHOLD', 80),
        ],
        'alerts' => [
            'enabled' => env('MARKUP_ALERTS_ENABLED', true),
            'email' => env('MARKUP_ALERTS_EMAIL'),
            'slack_webhook' => env('MARKUP_ALERTS_SLACK_WEBHOOK'),
        ],
    ],
];
