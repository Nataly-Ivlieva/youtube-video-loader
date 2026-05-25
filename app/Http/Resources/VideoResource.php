<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VideoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'youtube_id' => $this->youtube_id,
            'title' => $this->title,
            'description' => $this->description,
            'language' => $this->language,
            'keyword' => $this->keyword,
            'url' => $this->url,
            'published_at' => $this->published_at,
        ];
    }
}