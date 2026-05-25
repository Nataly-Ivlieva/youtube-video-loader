<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\VideoImportService;

class ImportYoutubeVideosHistorical extends Command
{
    protected $signature = 'youtube:history {--isolated}';

    protected $description = 'Import historical videos from YouTube';

    public function isolationLockExpiresAt(): \DateTimeInterface|\DateInterval
    {
        return now()->addMinutes(30);
    }
    public function handle(
        VideoImportService $service
    ): int {

        $this->info('Hstory import started');
        $service->importHistoryDay();
        $this->info('Hstory import finished');

        return self::SUCCESS;
    }
}