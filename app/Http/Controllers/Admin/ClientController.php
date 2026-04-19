<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if ($request->boolean('options')) {
            $limit = max(1, min(1000, $request->integer('limit', 500)));

            $rows = Client::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->limit($limit)
                ->get();

            return response()->json($rows);
        }

        $perPage = max(1, min(100, $request->integer('per_page', 20)));

        $rows = Client::query()
            ->select(['id', 'name', 'email', 'phone', 'contact_person', 'created_at'])
            ->withCount('projects')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = (string) $request->query('search');
                $query->where(function ($sub) use ($search): void {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('contact_person', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate($perPage);

        return response()->json($rows);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'email' => ['nullable', 'email', 'max:200'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'contact_person' => ['nullable', 'string', 'max:150'],
        ]);

        $client = Client::query()->create($payload);

        return response()->json([
            'message' => 'Client created successfully.',
            'client' => $client,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $client = Client::query()
            ->with([
                'projects' => fn ($query) => $query
                    ->withCount('invoices')
                    ->withSum('invoices as booked_revenue', 'amount')
                    ->latest('id'),
            ])
            ->findOrFail($id);

        return response()->json($client);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['sometimes', 'string', 'max:200'],
            'email' => ['nullable', 'email', 'max:200'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'contact_person' => ['nullable', 'string', 'max:150'],
        ]);

        $client = Client::query()->findOrFail($id);
        $client->update($payload);

        return response()->json([
            'message' => 'Client updated successfully.',
            'client' => $client->fresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $client = Client::query()->findOrFail($id);
        $client->delete();

        return response()->json(['message' => 'Client deleted successfully.']);
    }
}
