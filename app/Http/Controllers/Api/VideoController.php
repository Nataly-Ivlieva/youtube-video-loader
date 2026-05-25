<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Video;
use Illuminate\Http\Request;
use App\Http\Resources\VideoResource;
use Illuminate\Support\Facades\DB;


class VideoController extends Controller
{   /**
     * Get paginated list of videos.
     *
     * Supports filtering by language and keyword,
     * sorting and pagination.
     *
     * @queryParam language string Filter videos by language code. Example: en
     * @queryParam keyword string Search videos by keyword. Example: climate change
     * @queryParam sort string Sort field. Example: published_at
     * @queryParam direction string Sort direction (asc|desc). Example: desc
     * @queryParam per_page integer Number of items per page (max 100). Example: 20
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "youtube_id": "abc123",
     *       "keyword": "climate change",
     *       "title": "Climate Change Documentary",
     *       "description": "Video description",
     *       "language": "en",
     *       "url": "https://www.youtube.com/watch?v=abc123",
     *       "published_at": "2025-05-01T10:00:00Z"
     *     }
     *   ]
     * }
     */
    public function index(Request $request)
    {
        $this->ensureVideosExist();

        $query = Video::query();

        if ($request->filled('language')) {
            $query->where(
                'language',
                $request->language
            );
        }

        if ($request->filled('keyword')) {
            $query->where(
                'keyword',
                'like',
                '%' . $request->keyword . '%'
            );
        }

        $sort = $request->get(
            'sort',
            'published_at'
        );

        $direction = $request->get(
            'direction',
            'desc'
        );

        $perPage = min(
            $request->integer('per_page', 20),
            100
        );

        return VideoResource::collection(
            $query
                ->orderBy($sort, $direction)
                ->paginate($perPage)
        );
    }
    /**
     * Get a single video by ID.
     *
     * @urlParam video integer required Video ID. Example: 1
     *
     * @response 200 {
     *   "data": {
     *     "id": 1,
     *     "youtube_id": "abc123",
     *     "keyword": "climate change",
     *     "title": "Climate Change Documentary",
     *     "description": "Video description",
     *     "language": "en",
     *     "url": "https://www.youtube.com/watch?v=abc123",
     *     "published_at": "2025-05-01T10:00:00Z"
     *   }
     * }
     *
     * @response 404 {
     *   "message": "No query results for model [App\\Models\\Video]"
     * }
     */
    public function show(Video $video)
    {
        return new VideoResource($video);
    }
    /**
     * Get video statistics.
     *
     * Returns total number of videos and distribution by language.
     *
     * @response 200 {
     *   "total_videos": 1250,
     *   "languages": [
     *     {
     *       "language": "en",
     *       "count": 850
     *     },
     *     {
     *       "language": "de",
     *       "count": 250
     *     }
     *   ]
     * }
     */
    public function stats()
    {
        $this->ensureVideosExist();
        
        return response()->json([
            'total_videos' => Video::count(),

            'languages' => Video::query()
                ->select('language')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('language')
                ->orderByDesc('count')
                ->get(),
        ]);
    }

    private function ensureVideosExist(): void
    {
        if (Video::exists()) {
            return;
        }

        $this->importService->importYesterday();
    }
}