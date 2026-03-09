<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOfficeRequest;
use App\Http\Requests\UpdateOfficeRequest;
use App\Models\Office;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class OfficeController extends Controller
{
    public function publicIndex(): JsonResponse
    {
        $offices = Office::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'prefix']);

        return response()->json([
            'data' => $offices,
        ]);
    }

    public function index(): JsonResponse
    {
        $offices = Office::query()
            ->with('staff:id,name,email,is_active')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $offices->map(fn (Office $office) => $this->formatOffice($office, true))->values(),
        ]);
    }

    public function store(StoreOfficeRequest $request): JsonResponse
    {
        $office = Office::query()->create($request->validated());

        return response()->json([
            'message' => 'Office created successfully.',
            'data' => $this->formatOffice($office),
        ], Response::HTTP_CREATED);
    }

    public function show(Office $office): JsonResponse
    {
        $office->load('staff:id,name,email,is_active');

        return response()->json([
            'data' => $this->formatOffice($office, true),
        ]);
    }

    public function update(UpdateOfficeRequest $request, Office $office): JsonResponse
    {
        $office->update($request->validated());

        return response()->json([
            'message' => 'Office updated successfully.',
            'data' => $this->formatOffice($office->fresh()),
        ]);
    }

    public function destroy(Office $office): JsonResponse
    {
        if ($office->queueTickets()->exists()) {
            return response()->json([
                'message' => 'This office already has queue history and cannot be deleted.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $office->delete();

        return response()->json([
            'message' => 'Office deleted successfully.',
        ]);
    }

    public function assignStaff(Request $request, Office $office): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id'),
            ],
        ]);

        $staff = User::query()->findOrFail($validated['user_id']);

        if (! $staff->isStaff()) {
            return response()->json([
                'message' => 'Selected user is not a staff account.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $office->staff()->syncWithoutDetaching([$staff->id]);

        return response()->json([
            'message' => 'Staff assigned successfully.',
            'data' => $this->formatOffice($office->fresh()->load('staff:id,name,email,is_active'), true),
        ]);
    }

    public function unassignStaff(Office $office, User $staff): JsonResponse
    {
        if (! $staff->isStaff()) {
            return response()->json([
                'message' => 'Selected user is not a staff account.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $office->staff()->detach($staff->id);

        return response()->json([
            'message' => 'Staff unassigned successfully.',
            'data' => $this->formatOffice($office->fresh()->load('staff:id,name,email,is_active'), true),
        ]);
    }

    private function formatOffice(Office $office, bool $includeStaff = false): array
    {
        $payload = [
            'id' => $office->id,
            'name' => $office->name,
            'prefix' => $office->prefix,
            'is_active' => $office->is_active,
            'created_at' => $office->created_at,
            'updated_at' => $office->updated_at,
        ];

        if ($includeStaff) {
            $payload['staff'] = $office->staff
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_active' => $user->is_active,
                ])
                ->values();
        }

        return $payload;
    }
}
