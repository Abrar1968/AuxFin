<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OwnerEquityEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OwnerEquityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $rows = OwnerEquityEntry::query()
            ->with('recorder:id,name,email')
            ->when($request->filled('entry_type'), fn ($query) => $query->where('entry_type', $request->query('entry_type')))
            ->when($request->filled('from_date'), fn ($query) => $query->whereDate('entry_date', '>=', (string) $request->query('from_date')))
            ->when($request->filled('to_date'), fn ($query) => $query->whereDate('entry_date', '<=', (string) $request->query('to_date')))
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 20));

        return response()->json($rows);
    }

    public function show(int $id): JsonResponse
    {
        $entry = OwnerEquityEntry::query()
            ->with('recorder:id,name,email')
            ->findOrFail($id);

        return response()->json($entry);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'entry_date' => ['required', 'date'],
            'entry_type' => ['required', 'in:capital_contribution,drawing'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string'],
        ]);

        $entry = OwnerEquityEntry::query()->create([
            ...$payload,
            'recorded_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Owner equity entry created successfully.',
            'entry' => $entry->load('recorder:id,name,email'),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $entry = OwnerEquityEntry::query()->findOrFail($id);

        $payload = $request->validate([
            'entry_date' => ['sometimes', 'date'],
            'entry_type' => ['sometimes', 'in:capital_contribution,drawing'],
            'amount' => ['sometimes', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string'],
        ]);

        $entry->update($payload);

        return response()->json([
            'message' => 'Owner equity entry updated successfully.',
            'entry' => $entry->fresh()->load('recorder:id,name,email'),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $entry = OwnerEquityEntry::query()->findOrFail($id);
        $entry->delete();

        return response()->json(['message' => 'Owner equity entry deleted successfully.']);
    }
}
