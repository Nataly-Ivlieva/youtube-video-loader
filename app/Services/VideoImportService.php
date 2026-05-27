<?php

namespace App\Services;

use App\Exceptions\YoutubeQuotaExceededException;
use App\Models\Video;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;


class VideoImportService
{
    private const MAX_PAGES_DAILY = 2;
    private const MAX_PAGES_HISTORY = 10;

    private bool $quotaExceeded = false;

    public function __construct(
        private YoutubeService $youtube,
        private KeywordService $keywords
    ) {
    }

    public function importYesterday(): void
    {
        $from = now()
            ->subDay()
            ->startOfDay();

        $to = now()
            ->subDay()
            ->endOfDay();

        Log::info('Daily import started', [
            'from' => $from->toIso8601String(),
            'to' => $to->toIso8601String(),
        ]);

        $this->importPeriod(
            $from,
            $to,
            self::MAX_PAGES_DAILY
        );

        Log::info('Daily import finished');
    }

    public function importHistoryDay(): void
    {
        Log::info('History import started');

        $keywords = $this->keywords->all();

        foreach ($keywords as $language => $words) {

            foreach ($words as $word) {

                if ($this->quotaExceeded) {
                    break 2;
                }

                $this->importHistoryKeyword(
                    $word,
                    $language
                );
            }
        }

        Log::info('History import finished');
    }

    private function importHistoryKeyword(
        string $word,
        string $language
    ): void {

        $oldestDate = Video::query()
            ->where('keyword', $word)
            ->where('language', $language)
            ->min('published_at');

        if ($oldestDate) {

            $to = Carbon::parse($oldestDate)
                ->subSecond();

        } else {

            $to = now()
                ->subDay()
                ->endOfDay();
        }

        $from = $to
            ->copy()
            ->subDay();

        Log::info('History keyword import', [
            'keyword' => $word,
            'language' => $language,
            'from' => $from->toIso8601String(),
            'to' => $to->toIso8601String(),
        ]);

        $this->importKeyword(
            $word,
            $language,
            $from,
            $to,
            self::MAX_PAGES_HISTORY
        );
    }

    private function importPeriod(
        Carbon $from,
        Carbon $to,
        int $maxPages
    ): void {

        $keywords = $this->keywords->all();

        foreach ($keywords as $language => $words) {

            foreach ($words as $word) {

                if ($this->quotaExceeded) {
                    break 2;
                }

                $this->importKeyword(
                    $word,
                    $language,
                    $from,
                    $to,
                    $maxPages
                );
            }
        }
    }

    private function importKeyword(
        string $word,
        string $language,
        Carbon $from,
        Carbon $to,
        int $maxPages
    ): void {

        if ($this->quotaExceeded) {
            return;
        }

        Log::info('Processing keyword', [
            'keyword' => $word,
            'language' => $language,
        ]);

        $page = 0;
        $pageToken = null;
        $totalNewVideos = 0;

        try {

            do {

                $page++;

                Log::info('Loading page', [
                    'keyword' => $word,
                    'language' => $language,
                    'page' => $page,
                ]);

                $result = $this->youtube->search(
                    query: $word,
                    publishedAfter: $from->toIso8601String(),
                    publishedBefore: $to->toIso8601String(),
                    pageToken: $pageToken
                );

                if (empty($result)) {

                    Log::warning('Empty YouTube response', [
                        'keyword' => $word,
                        'language' => $language,
                    ]);

                    break;
                }

                $totalNewVideos += $this->saveVideos(
                    $result,
                    $word,
                    $language
                );

                $pageToken = $result['nextPageToken'] ?? null;

            } while (
                $pageToken !== null
                && $page < $maxPages
            );

            Log::info('Keyword import completed', [
                'keyword' => $word,
                'language' => $language,
                'pages_loaded' => $page,
                'new_videos' => $totalNewVideos,
            ]);

        } catch (YoutubeQuotaExceededException $e) {

            $this->quotaExceeded = true;

            Log::warning('YouTube quota exceeded. Import stopped.', [
                'keyword' => $word,
                'language' => $language,
            ]);

        } catch (\Throwable $e) {

            Log::error('Import failed', [
                'keyword' => $word,
                'language' => $language,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function saveVideos(
        array $result,
        string $keyword,
        string $language
    ): int {

        $newVideos = 0;

        foreach ($result['items'] ?? [] as $item) {

            $videoId = $item['id']['videoId'] ?? null;

            if (!$videoId) {
                continue;
            }

            $video = Video::firstOrCreate(
                [
                    'youtube_id' => $videoId,
                ],
                $this->createVideoData(
                    $item,
                    $keyword,
                    $language
                )
            );

            if ($video->wasRecentlyCreated) {
                $newVideos++;
            }

            Log::info('Video processed', [
                'youtube_id' => $videoId,
                'published_at' => $item['snippet']['publishedAt'] ?? null,
                'created' => $video->wasRecentlyCreated,
            ]);
        }

        return $newVideos;
    }

    private function createVideoData(
        array $item,
        string $keyword,
        string $language
    ): array {

        $videoId = $item['id']['videoId'];

        return [
            'keyword' => $keyword,
            'title' => $item['snippet']['title'] ?? '',
            'description' => $item['snippet']['description'] ?? '',
            'language' => $language,
            'url' => "https://www.youtube.com/watch?v={$videoId}",
            'published_at' => $item['snippet']['publishedAt'] ?? null,
        ];
    }
}