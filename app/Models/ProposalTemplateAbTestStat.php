<?php
// app/Models/ProposalTemplateAbTestStat.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProposalTemplateAbTestStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'proposal_template_id',
        'variant_index',
        'variant_name',
        'impressions',
        'applications',
        'conversions',
        'total_revenue'
    ];

    protected $casts = [
        'impressions' => 'integer',
        'applications' => 'integer',
        'conversions' => 'integer',
        'total_revenue' => 'decimal:2'
    ];

    public function template()
    {
        return $this->belongsTo(ProposalTemplate::class);
    }

    // Accessors для расчетных полей
    public function getConversionRateAttribute()
    {
        if ($this->applications === 0) return 0;
        return round(($this->conversions / $this->applications) * 100, 2);
    }

    public function getApplicationRateAttribute()
    {
        if ($this->impressions === 0) return 0;
        return round(($this->applications / $this->impressions) * 100, 2);
    }

    public function getAverageRevenueAttribute()
    {
        if ($this->conversions === 0) return 0;
        return round($this->total_revenue / $this->conversions, 2);
    }

    // Методы для увеличения счетчиков
    public function incrementImpressions()
    {
        $this->increment('impressions');
        $this->save();
    }

    public function incrementApplications()
    {
        $this->increment('applications');
        $this->save();
    }

    public function incrementConversions($revenue = 0)
    {
        $this->increment('conversions');
        $this->total_revenue += $revenue;
        $this->save();
    }
}
