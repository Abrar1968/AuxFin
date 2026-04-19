<?php

namespace App\Console\Commands;

use App\Models\Liability;
use App\Services\FinanceService;
use Illuminate\Console\Command;

class ProcessLiabilityAmortization extends Command
{
    protected $signature = 'auxfin:liabilities:amortize';

    protected $description = 'Process due liability amortization installments';

    public function handle(FinanceService $financeService): int
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, Liability> $rows */
        $rows = Liability::query()
            ->where('status', 'active')
            ->whereNotNull('next_due_date')
            ->whereDate('next_due_date', '<=', now()->toDateString())
            ->get();

        foreach ($rows as $liability) {
            $financeService->processLiabilityPayment($liability);
        }

        $this->info('Processed '.$rows->count().' liability installment(s).');

        return self::SUCCESS;
    }
}
