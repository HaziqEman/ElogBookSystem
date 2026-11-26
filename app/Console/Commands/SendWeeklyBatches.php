<?php

namespace App\Console\Commands;

use App\Mail\ReminderMail;
use App\Models\Attendance;
use App\Models\Logbook;
use App\Models\WeekSubmission;
use App\Services\WeekBatchService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendWeeklyBatches extends Command
{
    protected $signature = 'weeks:send
        {--backfill : Create batches for past weeks without notifying anyone}
        {--reminders-only : Send follow-up reminders without creating new batches}';

    protected $description = 'Send each finished logbook week to the company supervisor as one batch';

    public function handle(WeekBatchService $service): int
    {
        $now = Carbon::now(Attendance::TIMEZONE);

        if ($this->option('backfill')) {
            $this->info('Back-filled '.$service->sendDue($now, false).' week batch(es).');

            return self::SUCCESS;
        }

        if ($this->option('reminders-only')) {
            $this->info('Sent '.$service->remindStale($now).' follow-up reminder(s).');

            return self::SUCCESS;
        }

        $created = $service->sendDue($now);
        $emails = $created > 0 ? $this->emailNewBatches($now) : 0;

        $this->info('Sent '.$created.' week batch(es).');
        $this->info("Sent {$emails} supervisor email(s).");
        $this->info('Sent '.$service->remindStale($now).' follow-up reminder(s).');

        return self::SUCCESS;
    }

    private function emailNewBatches(Carbon $now): int
    {
        $submissions = WeekSubmission::whereBetween('sent_at', [
            $now->copy()->startOfSecond(),
            $now,
        ])->with('student.supervisor')->get();
        $mailBySupervisor = [];

        foreach ($submissions as $submission) {
            $student = $submission->student;
            $supervisor = $student ? $student->supervisor : null;

            if (! $student || ! $supervisor || empty($supervisor->email)) {
                continue;
            }

            $entryCount = Logbook::where('student_id', $student->student_id)
                ->where('week_no', $submission->week_no)
                ->count();

            $mailBySupervisor[$supervisor->getKey()]['to'] = $supervisor;
            $mailBySupervisor[$supervisor->getKey()]['items'][] = [
                'title' => $student->name,
                'detail' => "Week {$submission->week_no}: {$entryCount} ".($entryCount === 1 ? 'entry' : 'entries').' ready for your review',
            ];
        }

        $sent = 0;

        foreach ($mailBySupervisor as $row) {
            $count = count($row['items']);
            $subject = $count === 1
                ? '1 student logbook is ready for review'
                : "{$count} student logbooks are ready for review";

            Mail::to($row['to']->email)->send(new ReminderMail(
                $row['to']->name,
                'Smart eLogBook: '.$subject,
                'This week\'s logbook entries are ready for your review and validation:',
                $row['items'],
                url('/supervisor/logbook'),
                'Review logbooks'
            ));
            $sent++;
        }

        return $sent;
    }
}
