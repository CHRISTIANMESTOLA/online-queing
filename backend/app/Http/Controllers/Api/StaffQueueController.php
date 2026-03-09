<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\QueueTicket;
use App\Models\User;
use App\Services\QueueNumberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffQueueController extends Controller
{
    public function offices(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->isAdmin()) {
            $offices = Office::query()->active()->orderBy('name')->get();
        } else {
            $offices = $user->offices()->where('is_active', true)->orderBy('name')->get();
        }

        return response()->json([
            'data' => $offices
                ->map(fn (Office $office) => [
                    'id' => $office->id,
                    'name' => $office->name,
                    'prefix' => $office->prefix,
                ])
                ->values(),
        ]);
    }

    public function dashboard(Request $request, Office $office): JsonResponse
    {
        $this->ensureOfficeAccess($request->user(), $office);

        $today = now()->toDateString();

        $serving = QueueTicket::query()
            ->where('office_id', $office->id)
            ->where('queue_date', $today)
            ->where('status', QueueTicket::STATUS_SERVING)
            ->orderBy('queue_sequence')
            ->first();

        $waitingTickets = QueueTicket::query()
            ->where('office_id', $office->id)
            ->where('queue_date', $today)
            ->where('status', QueueTicket::STATUS_WAITING)
            ->orderBy('queue_sequence')
            ->limit(50)
            ->get();

        $counts = QueueTicket::query()
            ->selectRaw('status, COUNT(*) as total')
            ->where('office_id', $office->id)
            ->where('queue_date', $today)
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json([
            'data' => [
                'office' => [
                    'id' => $office->id,
                    'name' => $office->name,
                    'prefix' => $office->prefix,
                ],
                'serving' => $this->formatTicket($serving),
                'waiting' => $waitingTickets->map(fn (QueueTicket $ticket) => $this->formatTicket($ticket))->values(),
                'counts' => [
                    'waiting' => (int) ($counts[QueueTicket::STATUS_WAITING] ?? 0),
                    'serving' => (int) ($counts[QueueTicket::STATUS_SERVING] ?? 0),
                    'done' => (int) ($counts[QueueTicket::STATUS_DONE] ?? 0),
                    'skipped' => (int) ($counts[QueueTicket::STATUS_SKIPPED] ?? 0),
                ],
            ],
        ]);
    }

    public function callNext(Request $request, Office $office, QueueNumberService $queueNumberService): JsonResponse
    {
        $this->ensureOfficeAccess($request->user(), $office);

        $ticket = $queueNumberService->callNext($office, $request->user());

        if (! $ticket) {
            return response()->json([
                'message' => 'No waiting queue for this office.',
                'data' => null,
            ]);
        }

        return response()->json([
            'message' => 'Next queue number is now serving.',
            'data' => $this->formatTicket($ticket),
        ]);
    }

    public function markServing(Request $request, QueueTicket $queueTicket, QueueNumberService $queueNumberService): JsonResponse
    {
        $queueTicket->loadMissing('office');
        $this->ensureOfficeAccess($request->user(), $queueTicket->office);

        $updatedTicket = $queueNumberService->markServing($queueTicket, $request->user());

        return response()->json([
            'message' => 'Queue number marked as serving.',
            'data' => $this->formatTicket($updatedTicket),
        ]);
    }

    public function markDone(Request $request, QueueTicket $queueTicket, QueueNumberService $queueNumberService): JsonResponse
    {
        $queueTicket->loadMissing('office');
        $this->ensureOfficeAccess($request->user(), $queueTicket->office);

        $updatedTicket = $queueNumberService->markDone($queueTicket, $request->user());

        return response()->json([
            'message' => 'Queue number marked as done.',
            'data' => $this->formatTicket($updatedTicket),
        ]);
    }

    public function skip(Request $request, QueueTicket $queueTicket, QueueNumberService $queueNumberService): JsonResponse
    {
        $queueTicket->loadMissing('office');
        $this->ensureOfficeAccess($request->user(), $queueTicket->office);

        $updatedTicket = $queueNumberService->skip($queueTicket, $request->user());

        return response()->json([
            'message' => 'Queue number skipped.',
            'data' => $this->formatTicket($updatedTicket),
        ]);
    }

    private function ensureOfficeAccess(User $user, Office $office): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $isAssigned = $user->offices()->whereKey($office->id)->exists();

        abort_if(! $isAssigned, 403, 'You are not assigned to this office.');
    }

    private function formatTicket(?QueueTicket $queueTicket): ?array
    {
        if (! $queueTicket) {
            return null;
        }

        return [
            'id' => $queueTicket->id,
            'office_id' => $queueTicket->office_id,
            'queue_number' => $queueTicket->queue_number,
            'queue_sequence' => $queueTicket->queue_sequence,
            'queue_date' => $queueTicket->queue_date?->toDateString(),
            'status' => $queueTicket->status,
            'served_by' => $queueTicket->served_by,
            'called_at' => $queueTicket->called_at,
            'started_at' => $queueTicket->started_at,
            'completed_at' => $queueTicket->completed_at,
            'created_at' => $queueTicket->created_at,
            'updated_at' => $queueTicket->updated_at,
        ];
    }
}
