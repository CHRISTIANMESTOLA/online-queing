<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStaffRequest;
use App\Http\Requests\UpdateStaffRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminStaffController extends Controller
{
    public function index(): JsonResponse
    {
        $staffUsers = User::query()
            ->where('role', 'staff')
            ->with('offices:id,name,prefix')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $staffUsers->map(fn (User $staff) => $this->formatStaff($staff))->values(),
        ]);
    }

    public function store(StoreStaffRequest $request): JsonResponse
    {
        $data = $request->validated();

        $staff = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'staff',
            'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Staff account created successfully.',
            'data' => $this->formatStaff($staff),
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateStaffRequest $request, User $staff): JsonResponse
    {
        $this->ensureStaffAccount($staff);

        $data = $request->validated();

        if (array_key_exists('password', $data) && $data['password'] === null) {
            unset($data['password']);
        }

        $staff->update($data);

        return response()->json([
            'message' => 'Staff account updated successfully.',
            'data' => $this->formatStaff($staff->fresh()->load('offices:id,name,prefix')),
        ]);
    }

    public function destroy(Request $request, User $staff): JsonResponse
    {
        $this->ensureStaffAccount($staff);

        if ($request->user()?->id === $staff->id) {
            return response()->json([
                'message' => 'You cannot delete your own account.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $staff->delete();

        return response()->json([
            'message' => 'Staff account deleted successfully.',
        ]);
    }

    private function ensureStaffAccount(User $staff): void
    {
        abort_if(! $staff->isStaff(), Response::HTTP_NOT_FOUND, 'Staff account not found.');
    }

    private function formatStaff(User $staff): array
    {
        $staff->loadMissing('offices:id,name,prefix');

        return [
            'id' => $staff->id,
            'name' => $staff->name,
            'email' => $staff->email,
            'role' => $staff->role,
            'is_active' => $staff->is_active,
            'offices' => $staff->offices
                ->map(fn ($office) => [
                    'id' => $office->id,
                    'name' => $office->name,
                    'prefix' => $office->prefix,
                ])
                ->values(),
            'created_at' => $staff->created_at,
            'updated_at' => $staff->updated_at,
        ];
    }
}
