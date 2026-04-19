<?php

namespace App\Console\Commands;

use App\Events\InvoiceOverdue;
use App\Models\Invoice;
use Illuminate\Console\Command;

class FlagOverdueInvoices extends Command
{
    protected $signature = 'finerp:invoices:flag-overdue';

    protected $description = 'Flag invoices as overdue when due date has passed and payment is not completed';

    public function handle(): int
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, Invoice> $rows */
        $rows = Invoice::query()
            ->whereDate('due_date', '<', now()->toDateString())
            ->whereNotIn('status', ['paid', 'overdue'])
            ->get();

        foreach ($rows as $invoice) {
            $invoice->update(['status' => 'overdue']);

            event(new InvoiceOverdue([
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'project_id' => $invoice->project_id,
                'amount' => (float) $invoice->amount,
                'due_date' => optional($invoice->due_date)->toDateString(),
            ]));
        }

        $this->info('Flagged '.$rows->count().' invoices as overdue.');

        return self::SUCCESS;
    }
}
