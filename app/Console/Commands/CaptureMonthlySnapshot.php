<?php

namespace App\Console\Commands;

use App\Services\SnapshotService;
use Illuminate\Console\Command;

class CaptureMonthlySnapshot extends Command
{
    protected $signature = 'auxfin:snapshot {month? : Optional month in Y-m-d format}';

    protected $description = 'Capture monthly company financial snapshot';

    public function handle(SnapshotService $snapshotService): int
    {
        $month = $this->argument('month');
        $snapshot = $snapshotService->capture($month);

        $this->info('Snapshot captured for '.$snapshot->snapshot_month);

        return self::SUCCESS;
    }
}
