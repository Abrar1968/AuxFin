<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Liability;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceOverviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $monthDate = Carbon::parse((string) ($request->query('month') ?? now()->toDateString()))->startOfMonth();

        $bookedRevenue = (float) Invoice::query()->sum('amount');
        $recognizedRevenue = (float) Invoice::query()->whereNotNull('payment_completed_at')->sum('amount');
        $accountsReceivable = max(0, $bookedRevenue - $recognizedRevenue);

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
            ->withSum('invoices as booked_revenue', 'amount')
            ->withSum([
                'invoices as recognized_revenue' => fn ($query) => $query->whereNotNull('payment_completed_at'),
            ], 'amount')
            ->get()
            ->map(function (Project $project): array {
                $booked = (float) ($project->booked_revenue ?? 0);
                $recognized = (float) ($project->recognized_revenue ?? 0);

                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'client_name' => $project->client?->name,
                    'status' => $project->status,
                    'booked_revenue' => round($booked, 2),
                    'recognized_revenue' => round($recognized, 2),
                    'accounts_receivable' => round(max(0, $booked - $recognized), 2),
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
                'booked_revenue' => round($bookedRevenue, 2),
                'recognized_revenue' => round($recognizedRevenue, 2),
                'accounts_receivable' => round($accountsReceivable, 2),
                'collection_rate_percent' => $bookedRevenue > 0
                    ? round(($recognizedRevenue / $bookedRevenue) * 100, 2)
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
