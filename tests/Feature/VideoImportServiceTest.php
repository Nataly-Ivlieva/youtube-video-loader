<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\Models\Video;
use App\Services\KeywordService;

class VideoImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_video_is_saved()
    {
        Video::create([
            'youtube_id' => 'abc123',
            'keyword' => 'climate change',
            'title' => 'Test',
            'description' => 'Description',
            'language' => 'en',
            'url' => 'https://youtube.com/watch?v=abc123',
            'published_at' => now(),
         ]);

        $this->assertDatabaseHas('videos', [
            'youtube_id' => 'abc123',
        ]);
    }
    public function test_duplicate_video_is_not_created()
    {
        Video::factory()->create([
            'youtube_id' => 'abc123',
        ]);

        Video::firstOrCreate([
         'youtube_id' => 'abc123',
        ]);

        $this->assertEquals(
             1,
            Video::where('youtube_id', 'abc123')->count()
        );
    }

    public function test_keywords_file_is_loaded()
    {
        $service = new KeywordService();

        $keywords = $service->all();

        $this->assertArrayHasKey('en', $keywords);
    }
}
