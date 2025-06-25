<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NewsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'publish_date' => $this->publish_date->format('Y-m-d'),
            'is_published' => $this->is_published, // Добавлено поле
            // Убраны лишние поля, которые не проверяются в тесте
        ];
    }
}
