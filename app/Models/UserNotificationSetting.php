<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserNotificationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'markup_created',
        'markup_updated',
        'markup_deleted',
        'markup_activated',
        'markup_deactivated',
        'markup_expired',
        'markup_bulk_operation',
        'markup_calculation_error',
        'markup_daily_report',
        'email_notifications',
        'browser_notifications',
        'push_notifications',
    ];

    protected $casts = [
        'markup_created' => 'boolean',
        'markup_updated' => 'boolean',
        'markup_deleted' => 'boolean',
        'markup_activated' => 'boolean',
        'markup_deactivated' => 'boolean',
        'markup_expired' => 'boolean',
        'markup_bulk_operation' => 'boolean',
        'markup_calculation_error' => 'boolean',
        'markup_daily_report' => 'boolean',
        'email_notifications' => 'boolean',
        'browser_notifications' => 'boolean',
        'push_notifications' => 'boolean',
    ];

    /**
     * Relationship with user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get default settings
     */
    public static function getDefaultSettings(): array
    {
        return [
            'markup_created' => true,
            'markup_updated' => true,
            'markup_deleted' => true,
            'markup_activated' => true,
            'markup_deactivated' => false,
            'markup_expired' => true,
            'markup_bulk_operation' => true,
            'markup_calculation_error' => true,
            'markup_daily_report' => false,
            'email_notifications' => true,
            'browser_notifications' => true,
            'push_notifications' => false,
        ];
    }
}
