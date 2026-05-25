<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\Models\Video;

class VideoApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_videos_endpoint_returns_data()
    {
        Video::factory()->create([
             'language' => 'en',
        ]);

        $response = $this->getJson('/api/videos');

        $response->assertStatus(200);
    }

    public function test_show_endpoint_returns_single_video()
    {
        $video = Video::factory()->create([
            'title' => 'Climate Change',
            'language' => 'en',
        ]);

        $response = $this->getJson(
            "/api/videos/{$video->id}"
        );

        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'title' => 'Climate Change',
                'language' => 'en',
            ]);
    }

    public function test_show_returns_404_for_missing_video()
    {
        $response = $this->getJson(
            '/api/videos/999999'
        );

        $response->assertStatus(404);
    }

    public function test_stats_endpoint_returns_language_counts()
    {
        Video::factory()->create([
            'language' => 'en',
        ]);

        Video::factory()->create([
            'language' => 'en',
        ]);

        Video::factory()->create([
            'language' => 'de',
        ]);

        $response = $this->getJson(
            '/api/stats'
        );

        $response
            ->assertStatus(200)
            ->assertJson([
                'total_videos' => 3,
            ]);

        $response->assertJsonFragment([
            'language' => 'en',
            'count' => 2,
        ]);

        $response->assertJsonFragment([
            'language' => 'de',
            'count' => 1,
        ]);
    }
}
