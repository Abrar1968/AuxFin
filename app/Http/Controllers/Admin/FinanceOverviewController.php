<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Liability;
use App\Models\ProjectPayment;
use App\Models\Project;
use App\Support\TimeframeRange;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceOverviewController extends Controller
{
    /**
     * @var array<int, string>
     */
    private const ACCRUAL_INVOICE_STATUSES = ['sent', 'partial', 'paid', 'overdue'];

    public function index(Request $request): JsonResponse
    {
        $hasTimeframe = $request->has('timeframe');
        $timeframe = TimeframeRange::normalize((string) $request->query('timeframe', 'month'));
        $anchorDate = $request->query('anchor_date');

        if ($hasTimeframe) {
            [$periodFrom, $periodTo] = TimeframeRange::bounds(
                $timeframe,
                is_string($anchorDate) ? $anchorDate : null,
            );
        } else {
            $timeframe = 'all';
            $periodFrom = Carbon::create(1970, 1, 1)->startOfDay();
            $periodTo = now()->endOfDay();
        }

        $periodFromDate = $periodFrom->toDateString();
        $periodToDate = $periodTo->toDateString();

        $accruedRevenue = (float) Invoice::query()
            ->whereIn('status', self::ACCRUAL_INVOICE_STATUSES)
            ->when($hasTimeframe, fn ($query) => $query->whereBetween('invoice_date', [$periodFromDate, $periodToDate]))
            ->sum('amount');

        $accruedRevenueToDate = (float) Invoice::query()
            ->whereIn('status', self::ACCRUAL_INVOICE_STATUSES)
            ->when($hasTimeframe, fn ($query) => $query->whereDate('invoice_date', '<=', $periodToDate))
            ->sum('amount');

        $cashCollected = (float) ProjectPayment::query()
            ->whereNotNull('invoice_id')
            ->when($hasTimeframe, fn ($query) => $query->whereBetween('payment_date', [$periodFromDate, $periodToDate]))
            ->sum('amount');

        $cashCollectedToDate = (float) ProjectPayment::query()
            ->whereNotNull('invoice_id')
            ->when($hasTimeframe, fn ($query) => $query->whereDate('payment_date', '<=', $periodToDate))
            ->sum('amount');

        $advanceCollections = (float) ProjectPayment::query()
            ->whereNull('invoice_id')
            ->when($hasTimeframe, fn ($query) => $query->whereBetween('payment_date', [$periodFromDate, $periodToDate]))
            ->sum('amount');

        $accountsReceivable = max(0, $accruedRevenueToDate - $cashCollectedToDate);

        $expenseMtd = (float) Expense::query()
            ->when($hasTimeframe, fn ($query) => $query->whereBetween('expense_date', [$periodFromDate, $periodToDate]))
            ->sum('amount');

        $liabilitiesOutstanding = (float) Liability::query()
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $periodToDate)
            ->where(function ($query) use ($periodFrom): void {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $periodFrom->toDateString());
            })
            ->sum('outstanding');

        $assetsBookValue = (float) Asset::query()->sum('current_book_value');

        $invoiceFunnel = Invoice::query()
            ->when($hasTimeframe, fn ($query) => $query->whereBetween('invoice_date', [$periodFromDate, $periodToDate]))
            ->selectRaw('status, COUNT(*) as count, COALESCE(SUM(amount), 0) as amount')
            ->groupBy('status')
            ->get();

        $projectRows = Project::query()
            ->select(['id', 'client_id', 'name', 'status'])
            ->with(['client:id,name'])
            ->withSum([
                'invoices as recognized_revenue' => fn ($query) => $query
                    ->whereIn('status', self::ACCRUAL_INVOICE_STATUSES)
                    ->when($hasTimeframe, fn ($inner) => $inner->whereBetween('invoice_date', [$periodFromDate, $periodToDate])),
            ], 'amount')
            ->withSum([
                'payments as cash_collected' => fn ($query) => $query
                    ->whereNotNull('invoice_id')
                    ->when($hasTimeframe, fn ($inner) => $inner->whereBetween('payment_date', [$periodFromDate, $periodToDate])),
            ], 'amount')
            ->withSum([
                'payments as advance_collections' => fn ($query) => $query
                    ->whereNull('invoice_id')
                    ->when($hasTimeframe, fn ($inner) => $inner->whereBetween('payment_date', [$periodFromDate, $periodToDate])),
            ], 'amount')
            ->orderByDesc('recognized_revenue')
            ->limit(10)
            ->get()
            ->map(function (Project $project): array {
                $recognized = (float) ($project->recognized_revenue ?? 0);
                $booked = $recognized;
                $cash = (float) ($project->cash_collected ?? 0);
                $advances = (float) ($project->advance_collections ?? 0);

                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'client_name' => $project->client?->name,
                    'status' => $project->status,
                    'booked_revenue' => round($booked, 2),
                    'recognized_revenue' => round($recognized, 2),
                    'accrued_revenue' => round($recognized, 2),
                    'cash_collected' => round($cash, 2),
                    'advance_collections' => round($advances, 2),
                    'accounts_receivable' => round(max(0, $recognized - $cash), 2),
                ];
            })
            ->values();

        $expenseByCategory = Expense::query()
            ->when($hasTimeframe, fn ($query) => $query->whereBetween('expense_date', [$periodFromDate, $periodToDate]))
            ->selectRaw('category, COALESCE(SUM(amount), 0) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $projectCounts = Project::query()
            ->selectRaw('COUNT(*) as total_projects')
            ->selectRaw("SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_projects")
            ->first();

        return response()->json([
            'timeframe' => $timeframe,
            'period_from' => $periodFromDate,
            'period_to' => $periodToDate,
            'month' => $periodFrom->copy()->startOfMonth()->toDateString(),
            'kpis' => [
                'clients' => Client::query()->count(),
                'projects' => (int) ($projectCounts?->total_projects ?? 0),
                'active_projects' => (int) ($projectCounts?->active_projects ?? 0),
                'overdue_invoices' => Invoice::query()
                    ->where('status', 'overdue')
                    ->when($hasTimeframe, fn ($query) => $query->whereBetween('due_date', [$periodFromDate, $periodToDate]))
                    ->count(),
                'booked_revenue' => round($accruedRevenue, 2),
                'recognized_revenue' => round($accruedRevenue, 2),
                'accrued_revenue' => round($accruedRevenue, 2),
                'cash_collected' => round($cashCollected, 2),
                'advance_collections' => round($advanceCollections, 2),
                'accounts_receivable' => round($accountsReceivable, 2),
                'collection_rate_percent' => $accruedRevenue > 0
                    ? round(($cashCollected / $accruedRevenue) * 100, 2)
                    : 0,
                'expense_mtd' => round($expenseMtd, 2),
                'liabilities_outstanding' => round($liabilitiesOutstanding, 2),
                'assets_book_value' => round($assetsBookValue, 2),
            ],
            'invoice_funnel' => $invoiceFunnel,
            'project_rows' => $projectRows,
            'expense_by_category' => $expenseByCategory,
        ]);
    }
}
