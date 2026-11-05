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
        {--force : Ignore working days and the first-week delay (for testing)}
        {--to= : Send every email to this address instead of the real recipients (for testing)}';

    protected $description = 'Remind students about today, and tell supervisors (in-app and by email) when a student week is ready';

    public function handle(NotificationService $notifications): int
    {
        if (! Schema::hasTable('user_notifications')) {
            $this->error('The user_notifications table does not exist yet.');

            return self::FAILURE;
        }

        $now = Carbon::now(Attendance::TIMEZONE);
        $today = $now->toDateString();
        $force = (bool) $this->option('force');

        $withEntry = Logbook::where('activity_date', $today)->pluck('student_id')->flip();

        $onLeave = collect();
        if (Schema::hasTable('attendances')) {
            $onLeave = Attendance::where('attendance_date', $today)
                ->where('status', 'Leave')
                ->pluck('student_id')
                ->flip();
        }

        $dueCounts = collect();
        if (Schema::hasTable('todos')) {
            $dueCounts = Todo::where('is_done', false)
                ->where('due_date', $today)
                ->selectRaw('student_id, COUNT(*) as total')
                ->groupBy('student_id')
                ->pluck('total', 'student_id');
        }

        $entryReminders = 0;
        $taskReminders = 0;
        $weekNotices = 0;
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
        $supervisorMail = [];

        Student::with(['supervisor', 'lecturer'])->chunkById(100, function ($students) use ($notifications, $withEntry, $onLeave, $dueCounts, $now, $today, $force, &$studentMail, &$supervisorMail, &$entryReminders, &$taskReminders, &$weekNotices) {
            foreach ($students as $student) {
                $planner = new WeekPlanner($student, $now);
                $week = $planner->currentWeek();
                $inRange = $week === null || ($week >= 1 && $week <= $planner->totalWeeks());
                $working = $planner->isWorkingDay($now->copy()->startOfDay()) && $inRange;

                if (! $working && ! $force) {
                    continue;
                }

                // Student reminders start on the Monday AFTER the week they began.
                // e.g. started on a Tuesday -> first reminder the following Monday.
                $remindersStarted = true;

                if ($week !== null && $week >= 1) {
                    $remindFrom = $planner->weekStart(1)->copy()->startOfWeek(Carbon::MONDAY)->addWeek();
                    $remindersStarted = $now->copy()->startOfDay()->gte($remindFrom);
                }

                if (($remindersStarted || $force) && ! $onLeave->has($student->student_id)) {
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

                // Last working day of the week: tell the reviewers the week is ready.
                $lastDay = max($planner->workingDays());

                if (($working && $now->isoWeekday() === $lastDay) || ($force && $week !== null && $week >= 1)) {
                    $weekStart = $planner->weekStart($week);
                    $count = Logbook::where('student_id', $student->student_id)
                        ->whereBetween('activity_date', [$weekStart->toDateString(), $weekStart->copy()->addDays(6)->toDateString()])
                        ->count();

                    if ($count > 0) {
                        $title = "Week {$week} is ready to review: {$student->name}";
                        $body = $count.' '.($count === 1 ? 'entry was' : 'entries were').' written this week.';
                        $key = 'week:'.$student->student_id.':'.$week;

                        if ($student->supervisor && $notifications->notify($student->supervisor, 'review', $title, $body, '/supervisor/logbook', $key)) {
                            $weekNotices++;
                            $supervisorMail[$student->supervisor->getKey()]['to'] = $student->supervisor;
                            $supervisorMail[$student->supervisor->getKey()]['items'][] = [
                                'title' => $student->name,
                                'detail' => "Week {$week}: {$count} ".($count === 1 ? 'entry' : 'entries').' ready for your review',
                            ];
                        }

                        // if ($student->lecturer && $notifications->notify($student->lecturer, 'review', $title, $body, '/lecturer/logbook', $key)) {
                        //     $weekNotices++;
                        // }
                    }
                }
            }
        }, 'student_id');

        foreach ($studentMail as $row) {
            $n = count($row['items']);
            $subject = $n === 1 ? $row['items'][0]['title'] : "{$n} things need your attention today";

            $sendMail($row['to'], 'Smart eLogBook: '.$subject, 'Here is what needs your attention in your logbook today:', $row['items'], '/student/dashboard', 'Open my logbook');
        }

        foreach ($supervisorMail as $row) {
            $n = count($row['items']);
            $subject = $n === 1 ? '1 student logbook is ready for review' : "{$n} student logbooks are ready for review";

            $sendMail($row['to'], 'Smart eLogBook: '.$subject, 'This week\'s logbook entries are ready for your review and validation:', $row['items'], '/supervisor/logbook', 'Review logbooks');
        }

        $this->info("Sent {$entryReminders} entry reminders, {$taskReminders} task reminders and {$weekNotices} week-ready notices. Emails sent: {$emails}".($emailFailures ? " ({$emailFailures} failed, see storage/logs)" : '').'.');

        return self::SUCCESS;
    }
}
