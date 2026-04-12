<?php

namespace App\Http\Controllers\Employee;

use App\Events\MessageNew;
use App\Http\Controllers\Controller;
use App\Models\EmployeeMessage;
use App\Models\MessageRead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;
        abort_if(! $employee, 404, 'Employee profile not found.');

        $userId = (int) $request->user()->id;

        $rows = EmployeeMessage::query()
            ->where('employee_id', $employee->id)
            ->with(['reads' => fn ($query) => $query->where('user_id', $userId)])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->query('type')))
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 15));

        $rows->getCollection()->transform(function (EmployeeMessage $message) {
            $read = $message->reads->first();
            $isRead = $read && $read->read_at && $read->read_at->greaterThanOrEqualTo($message->updated_at);
            $message->setAttribute('is_read', (bool) $isRead);

            return $message;
        });

        $payload = $rows->toArray();
        $payload['unread_count'] = $this->unreadCount($employee->id, $userId);

        return response()->json($payload);
    }

    public function store(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;
        abort_if(! $employee, 404, 'Employee profile not found.');

        $payload = $request->validate([
            'type' => ['required', 'in:late_appeal,deduction_dispute,leave_clarification,salary_query,loan_query,general_hr'],
            'subject' => ['required', 'string', 'max:300'],
            'body' => ['required', 'string', 'min:5'],
            'reference_date' => ['nullable', 'date'],
            'reference_month' => ['nullable', 'date'],
            'priority' => ['nullable', 'in:normal,high'],
        ]);

        $message = EmployeeMessage::query()->create([
            'employee_id' => $employee->id,
            'type' => $payload['type'],
            'subject' => $payload['subject'],
            'body' => $payload['body'],
            'reference_date' => $payload['reference_date'] ?? null,
            'reference_month' => $payload['reference_month'] ?? null,
            'priority' => $payload['priority'] ?? 'normal',
            'status' => 'open',
            'action_taken' => 'none',
        ]);

        event(new MessageNew([
            'message_id' => $message->id,
            'employee_id' => $employee->id,
            'type' => $message->type,
            'subject' => $message->subject,
        ]));

        return response()->json([
            'message' => 'Query submitted successfully.',
            'record' => $message,
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $employee = $request->user()->employee;
        abort_if(! $employee, 404, 'Employee profile not found.');

        $message = EmployeeMessage::query()
            ->where('employee_id', $employee->id)
            ->findOrFail($id);

        MessageRead::query()->updateOrCreate(
            [
                'message_id' => $message->id,
                'user_id' => $request->user()->id,
            ],
            [
                'read_at' => now(),
            ],
        );

        return response()->json($message);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;
        abort_if(! $employee, 404, 'Employee profile not found.');

        $rows = EmployeeMessage::query()
            ->where('employee_id', $employee->id)
            ->get(['id']);

        if ($rows->isNotEmpty()) {
            $now = now();
            $payload = $rows->map(fn (EmployeeMessage $message): array => [
                'message_id' => $message->id,
                'user_id' => (int) $request->user()->id,
                'read_at' => $now,
            ])->all();

            MessageRead::query()->upsert($payload, ['message_id', 'user_id'], ['read_at']);
        }

        return response()->json(['message' => 'Inbox marked as read.']);
    }

    private function unreadCount(int $employeeId, int $userId): int
    {
        return EmployeeMessage::query()
            ->where('employee_id', $employeeId)
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
