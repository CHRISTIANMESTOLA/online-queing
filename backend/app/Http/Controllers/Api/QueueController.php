<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateQueueRequest;
use App\Models\Office;
use App\Models\QueueTicket;
use App\Services\QueueNumberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QueueController extends Controller
{
    public function generate(GenerateQueueRequest $request, QueueNumberService $queueNumberService): JsonResponse
    {
        $office = Office::query()
            ->active()
            ->findOrFail($request->integer('office_id'));

        $queueTicket = $queueNumberService->generateForOffice($office);

        return response()->json([
            'message' => 'Queue number generated successfully.',
            'data' => [
                'queue_ticket' => $this->formatTicket($queueTicket),
                'office' => [
                    'id' => $office->id,
                    'name' => $office->name,
                    'prefix' => $office->prefix,
                ],
            ],
        ], 201);
    }

    public function monitor(Request $request): JsonResponse
    {
        $today = now()->toDateString();

        if ($request->filled('office_id')) {
            $request->validate([
                'office_id' => ['required', 'integer', 'exists:offices,id'],
            ]);

            $office = Office::query()
                ->active()
                ->findOrFail($request->integer('office_id'));

            return response()->json([
                'data' => $this->formatOfficeMonitor($office, $today, true),
            ]);
        }

        $offices = Office::query()
            ->active()
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $offices->map(fn (Office $office) => $this->formatOfficeMonitor($office, $today))->values(),
        ]);
    }

    private function formatOfficeMonitor(Office $office, string $today, bool $withQueuePreview = false): array
    {
        $currentServing = QueueTicket::query()
            ->where('office_id', $office->id)
            ->where('queue_date', $today)
            ->where('status', QueueTicket::STATUS_SERVING)
            ->orderBy('queue_sequence')
            ->first();

        $nextInLine = QueueTicket::query()
            ->where('office_id', $office->id)
            ->where('queue_date', $today)
            ->where('status', QueueTicket::STATUS_WAITING)
            ->orderBy('queue_sequence')
            ->first();

        $counts = QueueTicket::query()
            ->selectRaw('status, COUNT(*) as total')
            ->where('office_id', $office->id)
            ->where('queue_date', $today)
            ->groupBy('status')
            ->pluck('total', 'status');

        $payload = [
            'id' => $office->id,
            'name' => $office->name,
            'prefix' => $office->prefix,
            'now_serving' => $currentServing?->queue_number,
            'next_in_line' => $nextInLine?->queue_number,
            'waiting_count' => (int) ($counts[QueueTicket::STATUS_WAITING] ?? 0),
            'done_count' => (int) ($counts[QueueTicket::STATUS_DONE] ?? 0),
            'skipped_count' => (int) ($counts[QueueTicket::STATUS_SKIPPED] ?? 0),
            'total_issued' => (int) array_sum($counts->all()),
            'updated_at' => now()->toDateTimeString(),
        ];

        if ($withQueuePreview) {
            $payload['waiting_queue'] = QueueTicket::query()
                ->where('office_id', $office->id)
                ->where('queue_date', $today)
                ->where('status', QueueTicket::STATUS_WAITING)
                ->orderBy('queue_sequence')
                ->limit(20)
                ->pluck('queue_number');
        }

        return $payload;
    }

    private function formatTicket(QueueTicket $queueTicket): array
    {
        return [
            'id' => $queueTicket->id,
            'office_id' => $queueTicket->office_id,
            'queue_number' => $queueTicket->queue_number,
            'queue_sequence' => $queueTicket->queue_sequence,
            'queue_date' => $queueTicket->queue_date?->toDateString(),
            'status' => $queueTicket->status,
            'called_at' => $queueTicket->called_at,
            'started_at' => $queueTicket->started_at,
            'completed_at' => $queueTicket->completed_at,
            'created_at' => $queueTicket->created_at,
        ];
    }
}
