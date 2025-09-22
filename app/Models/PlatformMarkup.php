<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PlatformMarkup extends Model
{
    protected $fillable = [
        'platform_id',
        'markupable_id',
        'markupable_type',
        'type',
        'value',
    ];

    public function markupable(): MorphTo
    {
        return $this->morphTo();
    }
}
