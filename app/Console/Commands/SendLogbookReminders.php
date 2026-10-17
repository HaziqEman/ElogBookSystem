<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Logbook;
use App\Models\Student;
use App\Models\Todo;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SendLogbookReminders extends Command
{
    protected $signature = 'reminders:send {--force : Send even on weekends}';

    protected $description = 'Remind students who have no logbook entry today, and about tasks due today';

    public function handle(NotificationService $notifications): int
    {
        if (! Schema::hasTable('user_notifications')) {
            $this->error('The user_notifications table does not exist yet.');

            return self::FAILURE;
        }

        $now = Carbon::now(Attendance::TIMEZONE);
        $today = $now->toDateString();

        if ($now->isWeekend() && ! $this->option('force')) {
            $this->info('Weekend: no reminders sent. Use --force to send anyway.');

            return self::SUCCESS;
        }

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

        Student::query()->chunkById(100, function ($students) use ($notifications, $withEntry, $onLeave, $dueCounts, $now, $today, &$entryReminders, &$taskReminders) {
            foreach ($students as $student) {
                if ($onLeave->has($student->student_id)) {
                    continue;
                }

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
        }, 'student_id');

        $this->info("Sent {$entryReminders} entry reminders and {$taskReminders} task reminders.");

        return self::SUCCESS;
    }
}