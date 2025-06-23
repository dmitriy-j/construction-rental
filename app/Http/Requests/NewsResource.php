<?php


class NewsResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'publish_date' => $this->publish_date->format('d.m.Y'),
            'author' => $this->author->name,
            'created_at' => $this->created_at->format('c'),
        ];
    }
}
