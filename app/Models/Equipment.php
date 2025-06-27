<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{

    protected $fillable = [
    'title', 'slug', 'description', 'company_id', 'category_id',
    'location_id', 'brand', 'model', 'year', 'hours_worked',
    'rating', 'is_featured', 'is_approved'
    ];


    protected $casts = [
        'specifications' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function specifications()
    {
        return $this->hasMany(Specification::class);
    }

    public function rentalTerms()
    {
        return $this->hasMany(RentalTerm::class);
    }

    public function images()
    {
        return $this->hasMany(EquipmentImage::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function getMainImageAttribute()
    {
        return $this->images()->where('is_main', true)->first();
    }
}
