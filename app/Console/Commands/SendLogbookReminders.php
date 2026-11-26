<?php

namespace App\Console\Commands;

use App\Mail\ReminderMail;
use App\Models\Attendance;
use App\Models\Logbook;
use App\Models\Student;
use App\Models\Todo;
use App\Services\NotificationService;
use App\Services\WeekPlanner;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SendLogbookReminders extends Command
{
    protected $signature = 'reminders:send
        {--force : Ignore internship dates and working days (for testing)}
        {--to= : Send every email to this address instead of the real recipients (for testing)}';

    protected $description = 'Remind students about missing logbook entries and tasks for today';

    public function handle(NotificationService $notifications): int
    {
        if (! Schema::hasTable('user_notifications')) {
            $this->error('The user_notifications table does not exist yet.');

            return self::FAILURE;
        }

        $now = Carbon::now(Attendance::TIMEZONE);
        $today = $now->toDateString();
        $force = (bool) $this->option('force');

        $withEntry = Logbook::whereDate('activity_date', $today)->pluck('student_id')->flip();

        $onLeave = collect();
        if (Schema::hasTable('attendances')) {
            $onLeave = Attendance::whereDate('attendance_date', $today)
                ->where('status', Attendance::STATUS_LEAVE)
                ->pluck('student_id')
                ->flip();
        }

        $dueCounts = collect();
        if (Schema::hasTable('todos')) {
            $dueCounts = Todo::where('is_done', false)
                ->whereDate('due_date', $today)
                ->selectRaw('student_id, COUNT(*) as total')
                ->groupBy('student_id')
                ->pluck('total', 'student_id');
        }

        $entryReminders = 0;
        $taskReminders = 0;
        $emails = 0;
        $emailFailures = 0;

        // One summary email per person. A mail failure must never stop the run.
        $testTo = $this->option('to');

        $sendMail = function ($recipient, string $subject, string $intro, array $items, string $path, string $button = 'Open Smart eLogBook') use (&$emails, &$emailFailures, $testTo) {
            $address = $testTo ?: ($recipient->email ?? null);

            if (empty($address)) {
                return;
            }

            try {
                Mail::to($address)->send(new ReminderMail($recipient->name, $subject, $intro, $items, url($path), $button));
                $emails++;
                $this->line("  Emailed {$recipient->name} <{$address}>: {$subject}");
            } catch (Throwable $e) {
                $emailFailures++;
                $this->error("  Failed for {$address}: ".$e->getMessage());
                report($e);
            }
        };

        $studentMail = [];

        Student::chunkById(100, function ($students) use ($notifications, $withEntry, $onLeave, $dueCounts, $now, $today, $force, &$studentMail, &$entryReminders, &$taskReminders) {
            foreach ($students as $student) {
                $planner = new WeekPlanner($student, $now);
                $week = $planner->currentWeek();
                $inRange = $planner->hasStart()
                    && $week !== null
                    && $week >= 1
                    && $week <= $planner->totalWeeks();
                $working = $inRange && $planner->isWorkingDay($now->copy()->startOfDay());

                if (! $working && ! $force) {
                    continue;
                }

                if (! $onLeave->has($student->student_id)) {
                    if (! $withEntry->has($student->student_id)) {
                        $title = 'No logbook entry for today';
                        $body = 'You have not written an entry for '.$now->format('d M Y').' yet. Add it before you finish for the day.';
                        $path = '/student/dashboard#new-entry';

                        $sent = $notifications->notify($student, 'reminder', $title, $body, $path, 'logbook:'.$today);

                        if ($sent) {
                            $entryReminders++;
                            $studentMail[$student->student_id]['to'] = $student;
                            $studentMail[$student->student_id]['items'][] = [
                                'title' => 'No logbook entry for today',
                                'detail' => 'Add your '.$now->format('d M Y').' entry before you finish for the day.',
                            ];
                        }
                    }

                    $due = (int) ($dueCounts[$student->student_id] ?? 0);

                    if ($due > 0) {
                        $title = $due === 1 ? '1 task is due today' : "{$due} tasks are due today";
                        $path = '/student/dashboard';

                        $sent = $notifications->notify($student, 'task', $title, null, $path, 'tasks:'.$today);

                        if ($sent) {
                            $taskReminders++;
                            $studentMail[$student->student_id]['to'] = $student;
                            $studentMail[$student->student_id]['items'][] = [
                                'title' => $title,
                                'detail' => 'Open your task list to finish or reschedule them.',
                            ];
                        }
                    }
                }
            }
        }, 'student_id');

        foreach ($studentMail as $row) {
            $n = count($row['items']);
            $subject = $n === 1 ? $row['items'][0]['title'] : "{$n} things need your attention today";

            $sendMail($row['to'], 'Smart eLogBook: '.$subject, 'Here is what needs your attention in your logbook today:', $row['items'], '/student/dashboard', 'Open my logbook');
        }

        $this->info("Sent {$entryReminders} entry reminders and {$taskReminders} task reminders. Emails sent: {$emails}".($emailFailures ? " ({$emailFailures} failed, see storage/logs)" : '').'.');

        return self::SUCCESS;
    }
}
