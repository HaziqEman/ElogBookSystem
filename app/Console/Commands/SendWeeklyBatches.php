<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Services\WeekBatchService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendWeeklyBatches extends Command
{
    protected $signature = 'weeks:send {--backfill : Create batches for past weeks without notifying anyone}';

    protected $description = 'Send each finished logbook week to the company supervisor as one batch';

    public function handle(WeekBatchService $service): int
    {
        $now = Carbon::now(Attendance::TIMEZONE);

        if ($this->option('backfill')) {
            $this->info('Back-filled '.$service->sendDue($now, false).' week batch(es).');

            return self::SUCCESS;
        }

        $this->info('Sent '.$service->sendDue($now).' week batch(es).');
        $this->info('Sent '.$service->remindStale($now).' follow-up reminder(s).');

        return self::SUCCESS;
    }
}
