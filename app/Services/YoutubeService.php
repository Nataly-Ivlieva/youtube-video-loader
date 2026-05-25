<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YoutubeService
{
    private const API_URL = 'https://www.googleapis.com/youtube/v3/search';

    public function search(
        string $query,
        ?string $publishedAfter = null,
        ?string $publishedBefore = null,
        ?string $pageToken = null
    ): array {
        $params = [
            'part' => 'snippet',
            'q' => $query,
            'type' => 'video',
            'order' => 'date',
            'maxResults' => 50,
            'key' => config('services.youtube.key'),
        ];

        if ($publishedAfter !== null) {
            $params['publishedAfter'] = $publishedAfter;
        }

        if ($publishedBefore !== null) {
            $params['publishedBefore'] = $publishedBefore;
        }

        if ($pageToken !== null) {
            $params['pageToken'] = $pageToken;
        }

        $response = Http::withoutVerifying()->get(
            self::API_URL,
            $params
        );

        Log::info('YouTube API request', [
            'query' => $query,
            'published_after' => $publishedAfter,
            'published_before' => $publishedBefore,
            'page_token' => $pageToken,
            'status' => $response->status(),
        ]);

        if (! $response->successful()) {
            Log::error('YouTube API error', [
                'query' => $query,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [];
        }

        $data = $response->json();

        Log::info('YouTube API result', [
            'query' => $query,
            'items_count' => count($data['items'] ?? []),
            'next_page_token' => $data['nextPageToken'] ?? null,
        ]);

        return $data;
    }
}