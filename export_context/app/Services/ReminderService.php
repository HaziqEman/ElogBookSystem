<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Logbook;
use App\Models\Student;
use App\Models\Todo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ReminderService
{
    private const RANK = ['high' => 3, 'medium' => 2, 'info' => 1];

    protected array $items = [];

    public function __construct(protected Student $student, protected Carbon $now)
    {
        $this->now = $now->copy();
    }

    /**
     * The reminders that apply right now, most urgent first.
     * A reminder's key changes when what it is about changes (a new task, a new deadline, a task
     * becoming overdue), so a reminder the student dismissed earlier comes back when it is a new situation.
     */
    public function items(): array
    {
        $this->items = [];

        $id = $this->student->student_id;
        $todayStart = $this->now->copy()->startOfDay();
        $todayKey = $todayStart->toDateString();
        $planner = new WeekPlanner($this->student, $this->now);
        $week = $planner->currentWeek();

        $inInternship = ! $planner->hasStart() || ($week >= 1 && $week <= $planner->totalWeeks());
        $started = $planner->hasStart() && $week >= 1;
        $workingToday = $inInternship && $planner->isWorkingDay($todayStart);

        $attendanceReady = Schema::hasTable('attendances');
        $attendance = $attendanceReady
            ? Attendance::where('student_id', $id)->where('attendance_date', $todayKey)->first()
            : null;
        $dayOff = $attendance && $attendance->status === 'Leave';

        // On MC, leave or a holiday only a new task from the supervisor is shown.
        if (! $dayOff) {
            $this->attendanceAndToday($workingToday, $attendance, $todayKey);

            if ($started) {
                $this->missingEntries($planner, $todayStart, $attendanceReady);
            }

            $this->revisions();
        }

        $this->tasks($todayStart, $dayOff);

        usort($this->items, fn ($a, $b) => self::RANK[$b['level']] <=> self::RANK[$a['level']]);

        return $this->items;
    }

    protected function add(string $key, string $kind, string $level, string $title, ?string $text, ?string $url, ?string $action, string $snooze = 'day'): void
    {
        $this->items[] = compact('key', 'kind', 'level', 'title', 'text', 'url', 'action', 'snooze');
    }

    protected function attendanceAndToday(bool $workingToday, $attendance, string $todayKey): void
    {
        if (! $workingToday) {
            return;
        }

        $hour = (int) $this->now->format('G');

        if (! ($attendance && $attendance->check_in)) {
            $this->add(
                'attendance:'.$todayKey,
                'attendance',
                $hour >= 10 ? 'high' : 'medium',
                'You have not clocked in yet',
                'Clock in to record your attendance for today.',
                '/student/dashboard#attendance-card',
                'Clock in'
            );
        } elseif (! $attendance->check_out && $hour >= 17) {
            $this->add(
                'clockout:'.$todayKey,
                'clockout',
                'medium',
                'Remember to clock out',
                'You clocked in at '.$attendance->checkInLocal().' and have not clocked out yet.',
                '/student/dashboard#attendance-card',
                'Clock out'
            );
        }

        $hasEntry = Logbook::where('student_id', $this->student->student_id)->where('activity_date', $todayKey)->exists();

        if (! $hasEntry) {
            $this->add(
                'logbook:'.$todayKey,
                'logbook',
                $hour >= 17 ? 'high' : 'medium',
                "Today's logbook entry is not written yet",
                $this->now->format('l, d M').'. Write it before you finish for the day.',
                '/student/dashboard?write='.$todayKey.'#new-entry',
                'Write entry'
            );
        }
    }

    protected function missingEntries(WeekPlanner $planner, Carbon $todayStart, bool $attendanceReady): void
    {
        $id = $this->student->student_id;

        $entryDates = Logbook::where('student_id', $id)
            ->pluck('activity_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->flip();

        $offDates = $attendanceReady
            ? Attendance::where('student_id', $id)->where('status', 'Leave')
                ->pluck('attendance_date')
                ->map(fn ($date) => Carbon::parse($date)->toDateString())
                ->flip()
            : collect();

        $end = $planner->weekStart($planner->totalWeeks())->addDays(6);
        $last = $todayStart->copy()->subDay();

        if ($last->gt($end)) {
            $last = $end->copy();
        }

        $missing = [];

        for ($d = $planner->start()->copy(); $d->lte($last); $d->addDay()) {
            if (! $planner->isWorkingDay($d)) {
                continue;
            }

            $key = $d->toDateString();

            if ($entryDates->has($key) || $offDates->has($key)) {
                continue;
            }

            $missing[] = $key;
        }

        if (! $missing) {
            return;
        }

        $count = count($missing);
        $latest = end($missing);

        $this->add(
            'missing:'.$count.':'.$latest,
            'missing',
            $count >= 3 ? 'high' : 'medium',
            $count === 1 ? '1 past day has no logbook entry' : $count.' past days have no logbook entry',
            'Most recent: '.Carbon::parse($latest)->format('l, d M').'. Your supervisor sees these as missing.',
            '/student/dashboard?write='.$latest.'#new-entry',
            'Add late entry'
        );
    }

    protected function revisions(): void
    {
        $revisions = Logbook::where('student_id', $this->student->student_id)
            ->where('supervisor_status', 'Revision Requested')
            ->orderByDesc('activity_date')
            ->get(['logbook_id', 'week_no', 'activity_date']);

        if ($revisions->isEmpty()) {
            return;
        }

        $first = $revisions->first();
        $count = $revisions->count();

        $this->add(
            'revision:'.$count.':'.$revisions->max('logbook_id'),
            'revision',
            'high',
            $count === 1 ? 'Your supervisor asked for a revision' : "Your supervisor asked for {$count} revisions",
            'Week '.$first->week_no.', '.Carbon::parse($first->activity_date)->format('l, d M').'. Update the entry and send it back.',
            '/student/dashboard?week='.$first->week_no.'#logbook-week',
            'Open entry'
        );
    }

    protected function tasks(Carbon $todayStart, bool $dayOff): void
    {
        if (! Schema::hasTable('todos')) {
            return;
        }

        $assignedReady = Schema::hasColumn('todos', 'supervisor_id');
        $columns = ['todo_id', 'title', 'due_date'];

        if ($assignedReady) {
            $columns = array_merge($columns, ['supervisor_id', 'created_at', 'updated_at']);
        }

        $query = Todo::where('student_id', $this->student->student_id)->where('is_done', false);

        if ($assignedReady) {
            $query->with('supervisor:supervisor_id,name');
        }

        $open = $query->get($columns);
        $todayKey = $todayStart->toDateString();
        $soonKey = $todayStart->copy()->addDays(2)->toDateString();

        if (! $dayOff) {
            $dated = $open->filter(fn ($task) => $task->due_date);
            $overdue = $dated->filter(fn ($task) => $task->due_date->toDateString() < $todayKey)->sortBy(fn ($task) => $task->due_date->toDateString());
            $dueToday = $dated->filter(fn ($task) => $task->due_date->toDateString() === $todayKey);
            $soon = $dated->filter(fn ($task) => $task->due_date->toDateString() > $todayKey && $task->due_date->toDateString() <= $soonKey);

            if ($overdue->isNotEmpty()) {
                $count = $overdue->count();

                $this->add(
                    'overdue:'.$this->fingerprint($overdue),
                    'overdue',
                    'high',
                    $count === 1 ? '1 task is overdue' : "{$count} tasks are overdue",
                    $this->describe($overdue),
                    '/student/dashboard#todo-list',
                    'View tasks'
                );
            }

            if ($dueToday->isNotEmpty()) {
                $count = $dueToday->count();

                $this->add(
                    'today:'.$this->fingerprint($dueToday),
                    'today',
                    'medium',
                    $count === 1 ? '1 task is due today' : "{$count} tasks are due today",
                    $this->describe($dueToday),
                    '/student/dashboard#todo-list',
                    'View tasks'
                );
            }

            if ($soon->isNotEmpty()) {
                $count = $soon->count();

                $this->add(
                    'soon:'.$this->fingerprint($soon),
                    'soon',
                    'info',
                    $count === 1 ? '1 task is due in the next 2 days' : "{$count} tasks are due in the next 2 days",
                    $this->describe($soon),
                    '/student/dashboard#todo-list',
                    'View tasks'
                );
            }
        }

        // A new task or a new deadline from the company supervisor (shown even on a day off).
        if ($assignedReady) {
            $recent = $this->now->copy()->subDays(3);

            foreach ($open as $task) {
                if (empty($task->supervisor_id) || ! $task->updated_at || $task->updated_at->lt($recent)) {
                    continue;
                }

                if ($task->due_date && $task->due_date->toDateString() < $todayKey) {
                    continue;   // already covered by the overdue reminder
                }

                $name = optional($task->supervisor)->name ?? 'your supervisor';
                $due = $task->due_date ? 'Due '.$task->due_date->format('D, d M') : 'No deadline';

                $this->add(
                    'assigned:'.$task->todo_id.':'.($task->due_date ? $task->due_date->toDateString() : 'none'),
                    'assigned',
                    'medium',
                    'New task from '.$name,
                    Str::limit($task->title, 80).' - '.$due.'.',
                    '/student/dashboard#todo-list',
                    'View task',
                    'forever'
                );
            }
        }
    }

    /** Changes whenever the set of tasks, or any of their deadlines, changes. */
    protected function fingerprint($tasks): string
    {
        return substr(md5($tasks->map(fn ($task) => $task->todo_id.'@'.$task->due_date->toDateString())->sort()->implode('|')), 0, 10);
    }

    protected function describe($tasks): string
    {
        $parts = $tasks->take(2)->map(fn ($task) => Str::limit($task->title, 40).' (due '.$task->due_date->format('d M').')')->all();
        $more = $tasks->count() - count($parts);

        return implode(', ', $parts).($more > 0 ? " and {$more} more" : '').'.';
    }
}