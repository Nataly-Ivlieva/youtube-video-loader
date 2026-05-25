<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\VideoImportService;

class ImportYoutubeVideos extends Command
{
    protected $signature = 'youtube:import {--isolated}';

    protected $description = 'Import videos from YouTube';

    public function isolationLockExpiresAt(): \DateTimeInterface|\DateInterval
    {
        return now()->addMinutes(30);
    }
    public function handle(
        VideoImportService $service
    ): int {

        $this->info('Import started');
        $service->importYesterday();
        $this->info('Import finished');

        return self::SUCCESS;
    }
}