<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class VideoFactory extends Factory
{
    public function definition(): array
    {
        $youtubeId = fake()->uuid();

        return [
            'youtube_id' => $youtubeId,
            'keyword' => 'climate change',
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'language' => 'en',
            'url' => "https://youtube.com/watch?v={$youtubeId}",
            'published_at' => now(),
        ];
    }
}