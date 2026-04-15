<?php

namespace App\Http\Controllers\Admin;

use App\Events\LeaveDecision;
use App\Http\Controllers\Controller;
use App\Models\Leave;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $rows = Leave::query()
            ->with(['employee.user', 'employee.department', 'reviewer'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->integer('employee_id')))
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 20));

        return response()->json($rows);
    }

    public function show(int $id): JsonResponse
    {
        $leave = Leave::query()
            ->with(['employee.user', 'employee.department', 'reviewer'])
            ->findOrFail($id);

        return response()->json($leave);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'leave_type' => ['required', 'in:casual,sick,earned,unpaid'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'reason' => ['required', 'string', 'min:3'],
            'status' => ['nullable', 'in:pending,approved,rejected'],
            'admin_note' => ['nullable', 'string', 'required_if:status,rejected'],
        ]);

        $from = Carbon::parse($payload['from_date']);
        $to = Carbon::parse($payload['to_date']);
        $status = $payload['status'] ?? 'pending';

        $leave = Leave::query()->create([
            'employee_id' => $payload['employee_id'],
            'leave_type' => $payload['leave_type'],
            'from_date' => $payload['from_date'],
            'to_date' => $payload['to_date'],
            'days' => $from->diffInDays($to) + 1,
            'reason' => $payload['reason'],
            'status' => $status,
            'admin_note' => $payload['admin_note'] ?? null,
            'reviewed_by' => $status === 'pending' ? null : $request->user()->id,
            'reviewed_at' => $status === 'pending' ? null : now(),
        ]);

        if (in_array($leave->status, ['approved', 'rejected'], true)) {
            event(new LeaveDecision($leave->employee_id, [
                'leave_id' => $leave->id,
                'status' => $leave->status,
                'admin_note' => $leave->admin_note,
            ]));
        }

        return response()->json([
            'message' => 'Leave record created successfully.',
            'leave' => $leave->fresh(['employee.user', 'employee.department', 'reviewer']),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $payload = $request->validate([
            'leave_type' => ['sometimes', 'in:casual,sick,earned,unpaid'],
            'from_date' => ['sometimes', 'date'],
            'to_date' => ['sometimes', 'date'],
            'reason' => ['sometimes', 'string', 'min:3'],
            'status' => ['sometimes', 'in:pending,approved,rejected'],
            'admin_note' => ['nullable', 'string'],
        ]);

        $leave = Leave::query()->findOrFail($id);

        $fromDate = $payload['from_date'] ?? $leave->from_date?->toDateString();
        $toDate = $payload['to_date'] ?? $leave->to_date?->toDateString();

        $from = Carbon::parse((string) $fromDate);
        $to = Carbon::parse((string) $toDate);
        if ($to->lt($from)) {
            abort(422, 'The to_date field must be after or equal to from_date.');
        }

        $nextStatus = $payload['status'] ?? $leave->status;
        $updates = [
            'leave_type' => $payload['leave_type'] ?? $leave->leave_type,
            'from_date' => $from->toDateString(),
            'to_date' => $to->toDateString(),
            'days' => $from->diffInDays($to) + 1,
            'reason' => $payload['reason'] ?? $leave->reason,
            'status' => $nextStatus,
            'admin_note' => array_key_exists('admin_note', $payload) ? $payload['admin_note'] : $leave->admin_note,
        ];

        if ($nextStatus === 'pending') {
            $updates['reviewed_by'] = null;
            $updates['reviewed_at'] = null;
        } else {
            $updates['reviewed_by'] = $request->user()->id;
            $updates['reviewed_at'] = now();
        }

        $leave->update($updates);

        if (in_array($leave->status, ['approved', 'rejected'], true)) {
            event(new LeaveDecision($leave->employee_id, [
                'leave_id' => $leave->id,
                'status' => $leave->status,
                'admin_note' => $leave->admin_note,
            ]));
        }

        return response()->json([
            'message' => 'Leave record updated successfully.',
            'leave' => $leave->fresh(['employee.user', 'employee.department', 'reviewer']),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $leave = Leave::query()->findOrFail($id);

        if ($leave->status === 'approved') {
            abort(422, 'Approved leave records cannot be deleted.');
        }

        $leave->delete();

        return response()->json([
            'message' => 'Leave record deleted successfully.',
        ]);
    }

    public function decision(Request $request, int $id): JsonResponse
    {
        $payload = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'admin_note' => ['nullable', 'string', 'min:3', 'required_if:status,rejected'],
        ]);

        $leave = Leave::query()->findOrFail($id);

        $leave->update([
            'status' => $payload['status'],
            'admin_note' => $payload['admin_note'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        event(new LeaveDecision($leave->employee_id, [
            'leave_id' => $leave->id,
            'status' => $leave->status,
            'admin_note' => $leave->admin_note,
        ]));

        return response()->json([
            'message' => 'Leave decision submitted successfully.',
            'leave' => $leave->fresh(['employee.user', 'reviewer']),
        ]);
    }
}
