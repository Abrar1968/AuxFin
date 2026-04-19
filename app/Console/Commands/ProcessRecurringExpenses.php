<?php

namespace App\Console\Commands;

use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessRecurringExpenses extends Command
{
    protected $signature = 'finerp:expenses:process-recurring';

    protected $description = 'Create monthly/quarterly/yearly recurring expense instances';

    public function handle(): int
    {
        $today = now()->toDateString();

        /** @var \Illuminate\Database\Eloquent\Collection<int, Expense> $expenses */
        $expenses = Expense::query()
            ->where('is_recurring', true)
            ->whereNotNull('next_due_date')
            ->whereDate('next_due_date', '<=', $today)
            ->get();

        foreach ($expenses as $expense) {
            Expense::query()->create([
                'category' => $expense->category,
                'description' => $expense->description,
                'amount' => $expense->amount,
                'expense_date' => $today,
                'is_recurring' => false,
                'created_by' => $expense->created_by,
            ]);

            $dueDate = Carbon::parse($expense->next_due_date);
            $expense->update([
                'next_due_date' => match ($expense->recurrence) {
                    'yearly' => $dueDate->copy()->addYear()->toDateString(),
                    'quarterly' => $dueDate->copy()->addMonths(3)->toDateString(),
                    default => $dueDate->copy()->addMonth()->toDateString(),
                },
            ]);
        }

        $this->info('Processed '.$expenses->count().' recurring expenses.');

        return self::SUCCESS;
    }
}
