<?php

namespace App\Services;

use App\Models\Feedback;
use App\Models\Logbook;
use App\Models\Supervisor;
use Illuminate\Support\Facades\DB;

class SupervisorReviewService
{
    public function save(Logbook $logbook, Supervisor $supervisor, string $decision, ?string $comment): void
    {
        DB::transaction(function () use ($logbook, $supervisor, $decision, $comment) {
            $logbook->update(['supervisor_status' => $decision]);

            $comment = trim((string) $comment);

            if ($comment === '') {
                return;
            }

            Feedback::create([
                'logbook_id' => $logbook->logbook_id,
                'supervisor_id' => $supervisor->supervisor_id,
                'comment' => $comment,
                'feedback_date' => now()->toDateString(),
            ]);
        });
    }
}
