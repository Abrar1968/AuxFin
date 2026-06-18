<?php

namespace App\Jobs;

use App\Services\PayrollProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessPayrollJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $employeeId,
        public readonly string $month,
        public readonly int $processedBy,
        public readonly array $overrides = [],
    ) {
    }

    public function handle(PayrollProcessingService $payrollProcessingService): void
    {
        $payrollProcessingService->processEmployee(
            $this->employeeId,
            $this->month,
            $this->processedBy,
            $this->overrides,
        );
    }
}
