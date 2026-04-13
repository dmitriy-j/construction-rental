<?php

return [
    'rate' => (float) env('VAT_RATE', 22.0), // Ставка по умолчанию на будущее
    'effective_date' => env('VAT_EFFECTIVE_DATE', '2026-01-01'), // Дата перехода
];
