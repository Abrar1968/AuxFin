<?php

namespace App\Console\Commands;

use App\Events\LiabilityDueSoon;
use App\Models\Liability;
use Illuminate\Console\Command;

class NotifyLiabilitiesDueSoon extends Command
{
    protected $signature = 'finerp:liabilities:notify-due-soon {--days=3 : Number of days window for due soon notification}';

    protected $description = 'Broadcast liabilities that are due soon to admin channel';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));

        $rows = Liability::query()
            ->where('status', 'active')
            ->whereNotNull('next_due_date')
            ->whereBetween('next_due_date', [now()->toDateString(), now()->addDays($days)->toDateString()])
            ->orderBy('next_due_date')
            ->get(['id', 'name', 'outstanding', 'monthly_payment', 'next_due_date']);

        if ($rows->isEmpty()) {
            $this->info('No liabilities due soon.');

            return self::SUCCESS;
        }

        event(new LiabilityDueSoon([
            'days' => $days,
            'count' => $rows->count(),
            'rows' => $rows->toArray(),
        ]));

        $this->info('Broadcasted due soon liabilities: '.$rows->count());

        return self::SUCCESS;
    }
}
