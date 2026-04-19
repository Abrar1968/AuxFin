<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessOwner;
use App\Models\OwnerEquityEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class OwnerEquityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $rows = OwnerEquityEntry::query()
            ->with([
                'recorder:id,name,email',
                'owner:id,name,ownership_percentage,initial_investment,is_active',
            ])
            ->when($request->filled('entry_type'), fn ($query) => $query->where('entry_type', $request->query('entry_type')))
            ->when($request->filled('from_date'), fn ($query) => $query->whereDate('entry_date', '>=', (string) $request->query('from_date')))
            ->when($request->filled('to_date'), fn ($query) => $query->whereDate('entry_date', '<=', (string) $request->query('to_date')))
            ->when($request->filled('business_owner_id'), fn ($query) => $query->where('business_owner_id', (int) $request->query('business_owner_id')))
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 20));

        $ownerPayload = $this->ownerPayload();

        return response()->json([
            ...$rows->toArray(),
            'owners' => $ownerPayload['owners'],
            'ownership_summary' => $ownerPayload['summary'],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $entry = OwnerEquityEntry::query()
            ->with([
                'recorder:id,name,email',
                'owner:id,name,ownership_percentage,initial_investment,is_active',
            ])
            ->findOrFail($id);

        return response()->json($entry);
    }

    public function owners(): JsonResponse
    {
        return response()->json($this->ownerPayload());
    }

    public function storeOwner(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'ownership_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'initial_investment' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $ownershipPercentage = round((float) $payload['ownership_percentage'], 2);
        $isActive = (bool) ($payload['is_active'] ?? true);

        $this->ensureOwnershipCapacity($ownershipPercentage, null, $isActive);

        $owner = BusinessOwner::query()->create([
            'name' => trim((string) $payload['name']),
            'ownership_percentage' => $ownershipPercentage,
            'initial_investment' => round((float) ($payload['initial_investment'] ?? 0), 2),
            'notes' => $payload['notes'] ?? null,
            'is_active' => $isActive,
        ]);

        return response()->json([
            'message' => 'Business owner created successfully.',
            'owner' => $owner,
        ], 201);
    }

    public function updateOwner(Request $request, int $id): JsonResponse
    {
        $owner = BusinessOwner::query()->findOrFail($id);

        $payload = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'ownership_percentage' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'initial_investment' => ['sometimes', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $ownershipPercentage = array_key_exists('ownership_percentage', $payload)
            ? round((float) $payload['ownership_percentage'], 2)
            : (float) $owner->ownership_percentage;

        $isActive = array_key_exists('is_active', $payload)
            ? (bool) $payload['is_active']
            : (bool) $owner->is_active;

        $this->ensureOwnershipCapacity($ownershipPercentage, $owner->id, $isActive);

        if (array_key_exists('name', $payload)) {
            $payload['name'] = trim((string) $payload['name']);
        }

        if (array_key_exists('initial_investment', $payload)) {
            $payload['initial_investment'] = round((float) $payload['initial_investment'], 2);
        }

        if (array_key_exists('ownership_percentage', $payload)) {
            $payload['ownership_percentage'] = $ownershipPercentage;
        }

        $owner->update($payload);

        return response()->json([
            'message' => 'Business owner updated successfully.',
            'owner' => $owner->fresh(),
        ]);
    }

    public function destroyOwner(int $id): JsonResponse
    {
        $owner = BusinessOwner::query()->findOrFail($id);

        $hasLinkedEntries = OwnerEquityEntry::query()
            ->where('business_owner_id', $owner->id)
            ->exists();

        if ($hasLinkedEntries) {
            throw ValidationException::withMessages([
                'owner_id' => ['Cannot delete owner with linked equity entries. Deactivate the owner instead.'],
            ]);
        }

        $owner->delete();

        return response()->json([
            'message' => 'Business owner deleted successfully.',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'business_owner_id' => ['nullable', 'integer', 'exists:business_owners,id'],
            'entry_date' => ['required', 'date'],
            'entry_type' => ['required', 'in:capital_contribution,drawing'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->ensureOwnerRulesForEntry($payload['business_owner_id'] ?? null);

        $entry = OwnerEquityEntry::query()->create([
            ...$payload,
            'recorded_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Owner equity entry created successfully.',
            'entry' => $entry->load([
                'recorder:id,name,email',
                'owner:id,name,ownership_percentage,initial_investment,is_active',
            ]),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $entry = OwnerEquityEntry::query()->findOrFail($id);

        $payload = $request->validate([
            'business_owner_id' => ['nullable', 'integer', 'exists:business_owners,id'],
            'entry_date' => ['sometimes', 'date'],
            'entry_type' => ['sometimes', 'in:capital_contribution,drawing'],
            'amount' => ['sometimes', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string'],
        ]);

        $resolvedOwnerId = array_key_exists('business_owner_id', $payload)
            ? $payload['business_owner_id']
            : $entry->business_owner_id;

        $this->ensureOwnerRulesForEntry($resolvedOwnerId ? (int) $resolvedOwnerId : null);

        $entry->update($payload);

        return response()->json([
            'message' => 'Owner equity entry updated successfully.',
            'entry' => $entry->fresh()->load([
                'recorder:id,name,email',
                'owner:id,name,ownership_percentage,initial_investment,is_active',
            ]),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $entry = OwnerEquityEntry::query()->findOrFail($id);
        $entry->delete();

        return response()->json(['message' => 'Owner equity entry deleted successfully.']);
    }

    /**
     * @return array{owners: array<int, array<string, mixed>>, summary: array<string, mixed>}
     */
    private function ownerPayload(): array
    {
        /** @var Collection<int, BusinessOwner> $owners */
        $owners = BusinessOwner::query()
            ->orderBy('name')
            ->get();

        $metricsByOwnerId = OwnerEquityEntry::query()
            ->selectRaw("business_owner_id, SUM(CASE WHEN entry_type = 'capital_contribution' THEN amount ELSE 0 END) as contributions")
            ->selectRaw("SUM(CASE WHEN entry_type = 'drawing' THEN amount ELSE 0 END) as drawings")
            ->whereNotNull('business_owner_id')
            ->groupBy('business_owner_id')
            ->get()
            ->keyBy('business_owner_id');

        $ownerRows = $owners
            ->map(function (BusinessOwner $owner) use ($metricsByOwnerId): array {
                $metrics = $metricsByOwnerId->get($owner->id);

                $contributions = round((float) ($metrics->contributions ?? 0), 2);
                $drawings = round((float) ($metrics->drawings ?? 0), 2);
                $initialInvestment = round((float) $owner->initial_investment, 2);

                return [
                    'id' => (int) $owner->id,
                    'name' => $owner->name,
                    'ownership_percentage' => round((float) $owner->ownership_percentage, 2),
                    'initial_investment' => $initialInvestment,
                    'capital_contributions' => $contributions,
                    'drawings' => $drawings,
                    'net_investment' => round($initialInvestment + $contributions - $drawings, 2),
                    'is_active' => (bool) $owner->is_active,
                    'notes' => $owner->notes,
                ];
            })
            ->values();

        $activeOwnershipPercent = round((float) $ownerRows
            ->where('is_active', true)
            ->sum('ownership_percentage'), 2);

        $unassignedCapital = round((float) OwnerEquityEntry::query()
            ->whereNull('business_owner_id')
            ->where('entry_type', 'capital_contribution')
            ->sum('amount'), 2);

        $unassignedDrawings = round((float) OwnerEquityEntry::query()
            ->whereNull('business_owner_id')
            ->where('entry_type', 'drawing')
            ->sum('amount'), 2);

        return [
            'owners' => $ownerRows->all(),
            'summary' => [
                'owner_count' => $ownerRows->count(),
                'active_owner_count' => $ownerRows->where('is_active', true)->count(),
                'total_active_ownership_percent' => $activeOwnershipPercent,
                'unallocated_ownership_percent' => round(max(0, 100 - $activeOwnershipPercent), 2),
                'is_fully_allocated' => abs($activeOwnershipPercent - 100) <= 0.01,
                'total_initial_investment' => round((float) $ownerRows->sum('initial_investment'), 2),
                'total_net_investment' => round((float) $ownerRows->sum('net_investment'), 2),
                'total_capital_contributions' => round((float) $ownerRows->sum('capital_contributions'), 2),
                'total_drawings' => round((float) $ownerRows->sum('drawings'), 2),
                'unassigned_capital_contributions' => $unassignedCapital,
                'unassigned_drawings' => $unassignedDrawings,
                'by_owner' => $ownerRows->all(),
            ],
        ];
    }

    private function ensureOwnerRulesForEntry(?int $ownerId): void
    {
        $activeOwnersCount = BusinessOwner::query()
            ->where('is_active', true)
            ->count();

        if ($activeOwnersCount > 0 && ! $ownerId) {
            throw ValidationException::withMessages([
                'business_owner_id' => ['Select an owner for this equity entry.'],
            ]);
        }

        if (! $ownerId) {
            return;
        }

        $owner = BusinessOwner::query()->find($ownerId);
        if (! $owner || ! $owner->is_active) {
            throw ValidationException::withMessages([
                'business_owner_id' => ['Selected owner is not active.'],
            ]);
        }
    }

    private function ensureOwnershipCapacity(float $ownershipPercentage, ?int $ignoreOwnerId = null, bool $isActive = true): void
    {
        if (! $isActive) {
            return;
        }

        $allocated = (float) BusinessOwner::query()
            ->when($ignoreOwnerId, fn ($query) => $query->whereKeyNot($ignoreOwnerId))
            ->where('is_active', true)
            ->sum('ownership_percentage');

        $projected = round($allocated + $ownershipPercentage, 2);
        if ($projected > 100.00) {
            throw ValidationException::withMessages([
                'ownership_percentage' => [
                    "Total active ownership cannot exceed 100%. Projected active ownership: {$projected}%.",
                ],
            ]);
        }
    }
}
