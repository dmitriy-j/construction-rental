<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class EquipmentImage extends Model
{
    protected $fillable = [
        'equipment_id', 'path', 'is_main', 'media_id',
    ];

    protected $casts = [
        'is_main' => 'boolean',
    ];

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    /**
     * URL миниатюры (150×150). Использует MediaLibrary если есть media_id.
     */
    public function getThumbnailUrl(): string
    {
        if ($this->media_id) {
            try {
                $media = \Spatie\MediaLibrary\MediaCollections\Models\Media::find($this->media_id);
                if ($media) {
                    return $media->getAvailableFullUrl(['thumb']) ?? $this->fallbackUrl();
                }
            } catch (\Throwable $e) {
                return $this->fallbackUrl();
            }
        }
        return $this->fallbackUrl();
    }

    /**
     * URL среднего размера (600×400).
     */
    public function getMediumUrl(): string
    {
        if ($this->media_id) {
            try {
                $media = \Spatie\MediaLibrary\MediaCollections\Models\Media::find($this->media_id);
                if ($media) {
                    return $media->getAvailableFullUrl(['medium']) ?? $this->fallbackUrl();
                }
            } catch (\Throwable $e) {
                return $this->fallbackUrl();
            }
        }
        return $this->fallbackUrl();
    }

    /**
     * URL большого размера (1200×800).
     */
    public function getLargeUrl(): string
    {
        if ($this->media_id) {
            try {
                $media = \Spatie\MediaLibrary\MediaCollections\Models\Media::find($this->media_id);
                if ($media) {
                    return $media->getAvailableFullUrl(['large']) ?? $media->getUrl();
                }
            } catch (\Throwable $e) {
                return $this->fallbackUrl();
            }
        }
        return $this->fallbackUrl();
    }

    /**
     * URL оригинального изображения.
     */
    public function getOriginalUrl(): string
    {
        if ($this->media_id) {
            try {
                $media = \Spatie\MediaLibrary\MediaCollections\Models\Media::find($this->media_id);
                if ($media) {
                    return $media->getUrl();
                }
            } catch (\Throwable $e) {
                // fall through
            }
        }
        return $this->fallbackUrl();
    }

    private function fallbackUrl(): string
    {
        return $this->path
            ? asset('storage/' . $this->path)
            : asset('images/placeholder.svg');
    }
}
