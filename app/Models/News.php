<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;

class News extends Model
{
    use HasFactory;

    protected $table = 'news';

    protected $fillable = [
        'title', 'slug', 'content', 'excerpt', 'category',
        'is_active', 'published_at', 'created_by', 'views_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'published_at' => 'datetime',
        'views_count' => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function ($news) {
            if (empty($news->slug)) {
                $slug = Str::slug($news->title);
                $counter = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = Str::slug($news->title) . '-' . $counter;
                    $counter++;
                }
                $news->slug = $slug;
            }
            if (empty($news->excerpt) && !empty($news->content)) {
                $news->excerpt = Str::limit(strip_tags($news->content), 200);
            }
            if (empty($news->created_by)) {
                $news->created_by = auth()->id();
            }
        });
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Видимые новости для пользователя.
     */
    public function scopeVisibleFor($query, ?User $user)
    {
        $query->where('is_active', true)
            ->where(function ($q) use ($user) {
                $q->where('category', 'all');
                if ($user && $user->company) {
                    if ($user->isLessee()) {
                        $q->orWhere('category', 'lessee');
                    }
                    if ($user->isLessor()) {
                        $q->orWhere('category', 'lessor');
                    }
                }
            })
            ->where(function ($q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    /**
     * Получатели для массовой рассылки.
     */
    public function getTargetUsers()
    {
        $query = User::query()->whereHas('roles', function ($q) {
            $q->whereIn('name', ['company_admin', 'platform_super', 'platform_admin']);
        });

        // Добавляем фильтр по категории
        if ($this->category === 'lessee') {
            $query->whereHas('company', fn($q) => $q->where('is_lessee', true));
        } elseif ($this->category === 'lessor') {
            $query->whereHas('company', fn($q) => $q->where('is_lessor', true));
        }

        return $query->whereNotNull('email')->get();
    }

    public function scopePublished($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }
}
