<?php

namespace App\Services;

use App\Exceptions\YoutubeQuotaExceededException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YoutubeService
{
    private const API_URL = 'https://www.googleapis.com/youtube/v3/search';

    /**
     * Delay between requests in microseconds.
     *
     * Helps avoid YouTube Search API per-minute rate limits.
     * 500000 μs = 0.5 second.
     */
    private const REQUEST_DELAY_US = 500000;

    public function search(
        string $query,
        ?string $publishedAfter = null,
        ?string $publishedBefore = null,
        ?string $pageToken = null
    ): array {

        /**
         * Add a small delay before every request
         * to reduce the risk of rate limiting.
         */
        usleep(self::REQUEST_DELAY_US);

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

        $response = Http::withoutVerifying()
            ->timeout(30)
            ->get(
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

            $data = $response->json();

            $reason = $data['error']['errors'][0]['reason'] ?? null;

            Log::error('YouTube API error', [
                'query' => $query,
                'status' => $response->status(),
                'reason' => $reason,
                'body' => $response->body(),
            ]);

            /**
             * Stop the import process when:
             * - the daily API quota has been exhausted
             * - the per-minute request limit has been exceeded
             *
             * The import service will catch this exception
             * and stop processing remaining keywords.
             */
            if (
                in_array(
                    $reason,
                    [
                        'quotaExceeded',
                        'rateLimitExceeded',
                    ],
                    true
                )
            ) {
                throw new YoutubeQuotaExceededException(
                    $reason
                );
            }

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