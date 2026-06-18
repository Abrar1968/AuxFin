<?php

namespace App\Jobs;

use App\Services\PayrollProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BulkProcessPayrollJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $month,
        public readonly int $processedBy,
    ) {
    }

    public function handle(PayrollProcessingService $payrollProcessingService): void
    {
        $payrollProcessingService->processBulk($this->month, $this->processedBy);
    }
}
