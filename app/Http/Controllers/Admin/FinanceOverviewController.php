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
        $monthDate = Carbon::parse((string) ($request->query('month') ?? now()->toDateString()))->startOfMonth();

        $accruedRevenue = (float) Invoice::query()
            ->whereIn('status', self::ACCRUAL_INVOICE_STATUSES)
            ->sum('amount');

        $cashCollected = (float) ProjectPayment::query()->sum('amount');
        $accountsReceivable = max(0, $accruedRevenue - $cashCollected);

        $expenseMtd = (float) Expense::query()
            ->whereYear('expense_date', $monthDate->year)
            ->whereMonth('expense_date', $monthDate->month)
            ->sum('amount');

        $liabilitiesOutstanding = (float) Liability::query()
            ->where('status', 'active')
            ->sum('outstanding');

        $assetsBookValue = (float) Asset::query()->sum('current_book_value');

        $invoiceFunnel = Invoice::query()
            ->selectRaw('status, COUNT(*) as count, COALESCE(SUM(amount), 0) as amount')
            ->groupBy('status')
            ->get();

        $projectRows = Project::query()
            ->with('client')
            ->withSum([
                'invoices as booked_revenue' => fn ($query) => $query->whereIn('status', self::ACCRUAL_INVOICE_STATUSES),
            ], 'amount')
            ->withSum([
                'invoices as recognized_revenue' => fn ($query) => $query->whereIn('status', self::ACCRUAL_INVOICE_STATUSES),
            ], 'amount')
            ->withSum('payments as cash_collected', 'amount')
            ->get()
            ->map(function (Project $project): array {
                $booked = (float) ($project->booked_revenue ?? 0);
                $recognized = (float) ($project->recognized_revenue ?? 0);
                $cash = (float) ($project->cash_collected ?? 0);

                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'client_name' => $project->client?->name,
                    'status' => $project->status,
                    'booked_revenue' => round($booked, 2),
                    'recognized_revenue' => round($recognized, 2),
                    'accrued_revenue' => round($recognized, 2),
                    'cash_collected' => round($cash, 2),
                    'accounts_receivable' => round(max(0, $recognized - $cash), 2),
                ];
            })
            ->sortByDesc('booked_revenue')
            ->take(10)
            ->values();

        $expenseByCategory = Expense::query()
            ->whereYear('expense_date', $monthDate->year)
            ->whereMonth('expense_date', $monthDate->month)
            ->selectRaw('category, COALESCE(SUM(amount), 0) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        return response()->json([
            'month' => $monthDate->toDateString(),
            'kpis' => [
                'clients' => Client::query()->count(),
                'projects' => Project::query()->count(),
                'active_projects' => Project::query()->where('status', 'active')->count(),
                'overdue_invoices' => Invoice::query()->where('status', 'overdue')->count(),
                'booked_revenue' => round($accruedRevenue, 2),
                'recognized_revenue' => round($accruedRevenue, 2),
                'accrued_revenue' => round($accruedRevenue, 2),
                'cash_collected' => round($cashCollected, 2),
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
