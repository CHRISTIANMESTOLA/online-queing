<?php

namespace App\Services;

use App\Models\Office;
use App\Models\QueueTicket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QueueNumberService
{
    public function generateForOffice(Office $office): QueueTicket
    {
        return DB::transaction(function () use ($office) {
            $office = Office::query()->whereKey($office->id)->lockForUpdate()->firstOrFail();
            $today = now()->toDateString();

            $lastSequence = QueueTicket::query()
                ->where('office_id', $office->id)
                ->where('queue_date', $today)
                ->lockForUpdate()
                ->max('queue_sequence');

            $nextSequence = (int) ($lastSequence ?? 0) + 1;

            $ticket = QueueTicket::query()->create([
                'office_id' => $office->id,
                'queue_date' => $today,
                'queue_sequence' => $nextSequence,
                'queue_number' => $this->formatQueueNumber($office->prefix, $nextSequence),
                'status' => QueueTicket::STATUS_WAITING,
            ]);

            return $ticket->fresh('office');
        });
    }

    public function callNext(Office $office, User $staff): ?QueueTicket
    {
        return DB::transaction(function () use ($office, $staff) {
            $today = now()->toDateString();

            $currentlyServing = QueueTicket::query()
                ->where('office_id', $office->id)
                ->where('queue_date', $today)
                ->where('status', QueueTicket::STATUS_SERVING)
                ->lockForUpdate()
                ->first();

            if ($currentlyServing) {
                throw ValidationException::withMessages([
                    'queue' => 'There is an active queue currently serving. Mark it done or skipped first.',
                ]);
            }

            $nextTicket = QueueTicket::query()
                ->where('office_id', $office->id)
                ->where('queue_date', $today)
                ->where('status', QueueTicket::STATUS_WAITING)
                ->orderBy('queue_sequence')
                ->lockForUpdate()
                ->first();

            if (! $nextTicket) {
                return null;
            }

            $nextTicket->update([
                'status' => QueueTicket::STATUS_SERVING,
                'served_by' => $staff->id,
                'called_at' => now(),
                'started_at' => now(),
            ]);

            return $nextTicket->fresh(['office', 'servedBy']);
        });
    }

    public function markServing(QueueTicket $queueTicket, User $staff): QueueTicket
    {
        return DB::transaction(function () use ($queueTicket, $staff) {
            $ticket = QueueTicket::query()->whereKey($queueTicket->id)->lockForUpdate()->firstOrFail();
            $this->ensureTodayQueue($ticket);

            if (! in_array($ticket->status, [QueueTicket::STATUS_WAITING, QueueTicket::STATUS_SERVING], true)) {
                throw ValidationException::withMessages([
                    'queue' => 'Only waiting or serving queue can be marked as serving.',
                ]);
            }

            $this->ensureNoOtherServing($ticket->office_id, $ticket->queue_date->toDateString(), $ticket->id);

            $ticket->update([
                'status' => QueueTicket::STATUS_SERVING,
                'served_by' => $staff->id,
                'called_at' => $ticket->called_at ?? now(),
                'started_at' => now(),
            ]);

            return $ticket->fresh(['office', 'servedBy']);
        });
    }

    public function markDone(QueueTicket $queueTicket, User $staff): QueueTicket
    {
        return DB::transaction(function () use ($queueTicket, $staff) {
            $ticket = QueueTicket::query()->whereKey($queueTicket->id)->lockForUpdate()->firstOrFail();
            $this->ensureTodayQueue($ticket);

            if ($ticket->status !== QueueTicket::STATUS_SERVING) {
                throw ValidationException::withMessages([
                    'queue' => 'Only serving queue can be marked done.',
                ]);
            }

            $ticket->update([
                'status' => QueueTicket::STATUS_DONE,
                'served_by' => $staff->id,
                'completed_at' => now(),
            ]);

            return $ticket->fresh(['office', 'servedBy']);
        });
    }

    public function skip(QueueTicket $queueTicket, User $staff): QueueTicket
    {
        return DB::transaction(function () use ($queueTicket, $staff) {
            $ticket = QueueTicket::query()->whereKey($queueTicket->id)->lockForUpdate()->firstOrFail();
            $this->ensureTodayQueue($ticket);

            if (! in_array($ticket->status, [QueueTicket::STATUS_WAITING, QueueTicket::STATUS_SERVING], true)) {
                throw ValidationException::withMessages([
                    'queue' => 'Only waiting or serving queue can be skipped.',
                ]);
            }

            $ticket->update([
                'status' => QueueTicket::STATUS_SKIPPED,
                'served_by' => $staff->id,
                'completed_at' => now(),
            ]);

            return $ticket->fresh(['office', 'servedBy']);
        });
    }

    private function ensureTodayQueue(QueueTicket $queueTicket): void
    {
        if ($queueTicket->queue_date->toDateString() !== now()->toDateString()) {
            throw ValidationException::withMessages([
                'queue' => 'Queue actions are only allowed for today\'s queue tickets.',
            ]);
        }
    }

    private function ensureNoOtherServing(int $officeId, string $date, ?int $exceptId = null): void
    {
        $query = QueueTicket::query()
            ->where('office_id', $officeId)
            ->where('queue_date', $date)
            ->where('status', QueueTicket::STATUS_SERVING)
            ->lockForUpdate();

        if ($exceptId) {
            $query->whereKeyNot($exceptId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'queue' => 'There is already a queue number marked as serving.',
            ]);
        }
    }

    private function formatQueueNumber(string $prefix, int $sequence): string
    {
        return sprintf('%s-%03d', strtoupper($prefix), $sequence);
    }
}
