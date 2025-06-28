<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;


class News extends Model
{
    use HasFactory, SoftDeletes;


     public function searchableAs()
    {
            return 'news_index';
        }
        protected $fillable = [
            'title', 'slug', 'excerpt', 'content',
            'publish_date', 'is_published', 'author_id'
        ];

        protected $casts = [
            'publish_date' => 'datetime',
            'is_published' => 'boolean'
        ];

        protected static function booted()
        {
            static::creating(function ($news) {
                $news->slug = Str::slug($news->title);
            });

            static::updating(function ($news) {
                if ($news->isDirty('title')) {
                    $news->slug = Str::slug($news->title);
                }
            });
        }

        public function author()
        {
             return $this->belongsTo(Admin::class, 'author_id');
        }

        public function scopePublished($query)
        {
            return $query->where('is_published', true)
                ->where('publish_date', '<=', now());
        }
    }

