<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Logbook;
use App\Models\Student;
use App\Models\Todo;
use App\Services\NotificationService;
use App\Services\WeekPlanner;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SendLogbookReminders extends Command
{
    protected $signature = 'reminders:send {--force : Ignore working days (for testing)}';

    protected $description = 'Remind students about today, and tell reviewers when a student week is ready';

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

        Student::with(['supervisor', 'lecturer'])->chunkById(100, function ($students) use ($notifications, $withEntry, $onLeave, $dueCounts, $now, $today, $force, &$entryReminders, &$taskReminders, &$weekNotices) {
            foreach ($students as $student) {
                $planner = new WeekPlanner($student, $now);
                $week = $planner->currentWeek();
                $inRange = $week === null || ($week >= 1 && $week <= $planner->totalWeeks());
                $working = $planner->isWorkingDay($now->copy()->startOfDay()) && $inRange;

                if (! $working && ! $force) {
                    continue;
                }

                if (! $onLeave->has($student->student_id)) {
                    if (! $withEntry->has($student->student_id)) {
                        $sent = $notifications->notify(
                            $student,
                            'reminder',
                            'No logbook entry for today',
                            'You have not written an entry for '.$now->format('d M Y').' yet. Add it before you finish for the day.',
                            '/student/dashboard#new-entry',
                            'logbook:'.$today
                        );

                        if ($sent) {
                            $entryReminders++;
                        }
                    }

                    $due = (int) ($dueCounts[$student->student_id] ?? 0);

                    if ($due > 0) {
                        $sent = $notifications->notify(
                            $student,
                            'task',
                            $due === 1 ? '1 task is due today' : "{$due} tasks are due today",
                            null,
                            '/student/dashboard',
                            'tasks:'.$today
                        );

                        if ($sent) {
                            $taskReminders++;
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
                        }

                        // if ($student->lecturer && $notifications->notify($student->lecturer, 'review', $title, $body, '/lecturer/logbook', $key)) {
                        //     $weekNotices++;
                        // }
                    }
                }
            }
        }, 'student_id');

        $this->info("Sent {$entryReminders} entry reminders, {$taskReminders} task reminders and {$weekNotices} week-ready notices.");

        return self::SUCCESS;
    }
}