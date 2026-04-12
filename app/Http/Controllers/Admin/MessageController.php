<?php

namespace App\Http\Controllers\Admin;

use App\Events\MessageActionTaken;
use App\Events\MessageReplied;
use App\Events\MessageResolved;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Attendance;
use App\Models\EmployeeMessage;
use App\Models\MessageRead;
use App\Models\SalaryMonth;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MessageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;

        $messages = EmployeeMessage::query()
            ->with([
                'employee.user',
                'replier',
                'reads' => fn ($query) => $query->where('user_id', $userId),
            ])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->query('type')))
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->integer('employee_id')))
            ->orderBy('status')
            ->orderBy('created_at')
            ->paginate($request->integer('per_page', 20));

        $messages->getCollection()->transform(function (EmployeeMessage $message) {
            $read = $message->reads->first();
            $isRead = $read && $read->read_at && $read->read_at->greaterThanOrEqualTo($message->updated_at);
            $message->setAttribute('is_read', (bool) $isRead);

            return $message;
        });

        $payload = $messages->toArray();
        $payload['unread_count'] = $this->unreadCount($userId);

        return response()->json($payload);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $message = EmployeeMessage::query()
            ->with(['employee.user', 'employee.department', 'replier'])
            ->findOrFail($id);

        if ($message->status === 'open') {
            $message->update(['status' => 'under_review']);
        }

        $this->markRead($message->id, (int) $request->user()->id);

        return response()->json($message->fresh()->load(['employee.user', 'employee.department', 'replier']));
    }

    public function reply(Request $request, int $id): JsonResponse
    {
        $payload = $request->validate([
            'admin_reply' => ['required', 'string', 'min:3'],
            'action_taken' => ['nullable', 'in:none,deduction_reversed,mark_excused,salary_adjusted,noted'],
            'status' => ['nullable', 'in:under_review,resolved,rejected'],
        ]);

        $message = EmployeeMessage::query()->findOrFail($id);

        DB::transaction(function () use ($message, $payload, $request): void {
            $action = $payload['action_taken'] ?? 'none';

            $message->update([
                'admin_reply' => $payload['admin_reply'],
                'action_taken' => $action,
                'status' => $payload['status'] ?? 'under_review',
                'replied_by' => $request->user()->id,
                'replied_at' => now(),
                'resolved_at' => ($payload['status'] ?? null) === 'resolved' ? now() : null,
            ]);

            $this->handleSystemAction($message, $action);
        });

        event(new MessageReplied($message->employee_id, [
            'message_id' => $message->id,
            'status' => $message->status,
            'action_taken' => $message->action_taken,
        ]));

        if ($message->status === 'resolved') {
            event(new MessageResolved($message->employee_id, ['message_id' => $message->id]));
        }

        if ($message->action_taken !== 'none') {
            event(new MessageActionTaken($message->employee_id, [
                'message_id' => $message->id,
                'action_taken' => $message->action_taken,
            ]));
        }

        $this->markRead($message->id, (int) $request->user()->id);

        return response()->json([
            'message' => 'Reply saved successfully.',
            'record' => $message->fresh(),
        ]);
    }

    public function resolve(Request $request, int $id): JsonResponse
    {
        $message = EmployeeMessage::query()->findOrFail($id);

        $message->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'replied_by' => $request->user()->id,
            'replied_at' => now(),
        ]);

        event(new MessageResolved($message->employee_id, ['message_id' => $message->id]));

        $this->markRead($message->id, (int) $request->user()->id);

        return response()->json(['message' => 'Message resolved successfully.']);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $payload = $request->validate([
            'reason' => ['required', 'string', 'min:3'],
        ]);

        $message = EmployeeMessage::query()->findOrFail($id);

        $message->update([
            'status' => 'rejected',
            'admin_reply' => $payload['reason'],
            'replied_by' => $request->user()->id,
            'replied_at' => now(),
        ]);

        event(new MessageReplied($message->employee_id, [
            'message_id' => $message->id,
            'status' => $message->status,
        ]));

        $this->markRead($message->id, (int) $request->user()->id);

        return response()->json(['message' => 'Message rejected successfully.']);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;

        $rows = EmployeeMessage::query()->get(['id']);

        if ($rows->isNotEmpty()) {
            $now = now();
            $payload = $rows->map(fn (EmployeeMessage $message): array => [
                'message_id' => $message->id,
                'user_id' => $userId,
                'read_at' => $now,
            ])->all();

            MessageRead::query()->upsert($payload, ['message_id', 'user_id'], ['read_at']);
        }

        return response()->json(['message' => 'All messages marked as read.']);
    }

    private function handleSystemAction(EmployeeMessage $message, string $action): void
    {
        if ($action === 'mark_excused' && $message->reference_date) {
            $affected = Attendance::query()
                ->where('employee_id', $message->employee_id)
                ->whereDate('date', $message->reference_date)
                ->update(['is_late' => false]);

            if ($affected > 0) {
                $this->recalculateLatePenaltyForMonth($message->employee_id, $message->reference_date);

                AuditLog::query()->create([
                    'user_id' => request()?->user()?->id,
                    'action' => 'late.excused',
                    'model_type' => 'Attendance',
                    'model_id' => null,
                    'old_values' => ['is_late' => true, 'reference_date' => $message->reference_date],
                    'new_values' => ['is_late' => false, 'reference_date' => $message->reference_date],
                    'ip_address' => request()?->ip(),
                    'user_agent' => request()?->userAgent(),
                    'created_at' => now(),
                ]);
            }
        }

        if ($action === 'deduction_reversed' && $message->reference_month) {
            /** @var SalaryMonth|null $salaryMonth */
            $salaryMonth = SalaryMonth::query()
                ->where('employee_id', $message->employee_id)
                ->whereDate('month', $message->reference_month)
                ->first();

            if ($salaryMonth) {
                if ($salaryMonth->status === 'paid') {
                    throw ValidationException::withMessages([
                        'action_taken' => 'Cannot modify deductions for a paid salary month.',
                    ]);
                }

                $before = [
                    'late_penalty_deduction' => (float) $salaryMonth->late_penalty_deduction,
                    'total_deductions' => (float) $salaryMonth->total_deductions,
                    'net_payable' => (float) $salaryMonth->net_payable,
                ];

                $gross = (float) $salaryMonth->gross_earnings;
                $salaryMonth->late_penalty_deduction = 0;
                $salaryMonth->total_deductions = (float) $salaryMonth->tds_deduction
                    + (float) $salaryMonth->pf_deduction
                    + (float) $salaryMonth->professional_tax
                    + (float) $salaryMonth->unpaid_leave_deduction
                    + (float) $salaryMonth->loan_emi_deduction;
                $salaryMonth->net_payable = $gross - (float) $salaryMonth->total_deductions;
                $salaryMonth->save();

                AuditLog::query()->create([
                    'user_id' => request()?->user()?->id,
                    'action' => 'deduction.reversed',
                    'model_type' => 'SalaryMonth',
                    'model_id' => $salaryMonth->id,
                    'old_values' => $before,
                    'new_values' => [
                        'late_penalty_deduction' => (float) $salaryMonth->late_penalty_deduction,
                        'total_deductions' => (float) $salaryMonth->total_deductions,
                        'net_payable' => (float) $salaryMonth->net_payable,
                    ],
                    'ip_address' => request()?->ip(),
                    'user_agent' => request()?->userAgent(),
                    'created_at' => now(),
                ]);
            }
        }
    }

    private function recalculateLatePenaltyForMonth(int $employeeId, string $date): void
    {
        $monthStart = Carbon::parse($date)->startOfMonth()->toDateString();

        /** @var SalaryMonth|null $salaryMonth */
        $salaryMonth = SalaryMonth::query()
            ->where('employee_id', $employeeId)
            ->whereDate('month', $monthStart)
            ->first();

        if (! $salaryMonth) {
            return;
        }

        if ($salaryMonth->status === 'paid') {
            throw ValidationException::withMessages([
                'action_taken' => 'Cannot modify late penalty for a paid salary month.',
            ]);
        }

        $policyDefaults = [
            'late_days_per_unit' => 2,
            'deduction_unit_type' => 'full_day',
        ];
        $policy = Setting::getValue('late_policy', $policyDefaults);
        if (! is_array($policy)) {
            $policy = $policyDefaults;
        }

        $daysPerUnit = max(1, (int) ($policy['late_days_per_unit'] ?? 2));
        $unitRate = ((string) ($policy['deduction_unit_type'] ?? 'full_day')) === 'half_day' ? 0.5 : 1.0;

        $lateDays = Attendance::query()
            ->where('employee_id', $employeeId)
            ->whereYear('date', Carbon::parse($monthStart)->year)
            ->whereMonth('date', Carbon::parse($monthStart)->month)
            ->where('is_late', true)
            ->count();

        $dailyRate = (int) $salaryMonth->expected_working_days > 0
            ? ((float) $salaryMonth->gross_earnings / (int) $salaryMonth->expected_working_days)
            : 0.0;

        $lateUnits = (int) floor($lateDays / $daysPerUnit);
        $latePenalty = round($dailyRate * $lateUnits * $unitRate, 2);

        $salaryMonth->late_entries = $lateDays;
        $salaryMonth->late_penalty_deduction = $latePenalty;
        $salaryMonth->total_deductions = round(
            (float) $salaryMonth->tds_deduction
            + (float) $salaryMonth->pf_deduction
            + (float) $salaryMonth->professional_tax
            + (float) $salaryMonth->unpaid_leave_deduction
            + (float) $salaryMonth->loan_emi_deduction
            + $latePenalty,
            2
        );
        $salaryMonth->net_payable = round((float) $salaryMonth->gross_earnings - (float) $salaryMonth->total_deductions, 2);
        $salaryMonth->save();
    }

    private function markRead(int $messageId, int $userId): void
    {
        MessageRead::query()->updateOrCreate(
            [
                'message_id' => $messageId,
                'user_id' => $userId,
            ],
            [
                'read_at' => now(),
            ],
        );
    }

    private function unreadCount(int $userId): int
    {
        return EmployeeMessage::query()
            ->where(function ($query) use ($userId): void {
                $query->whereDoesntHave('reads', fn ($reads) => $reads->where('user_id', $userId))
                    ->orWhereHas('reads', function ($reads) use ($userId): void {
                        $reads->where('user_id', $userId)
                            ->whereColumn('read_at', '<', 'employee_messages.updated_at');
                    });
            })
            ->count();
    }
}
