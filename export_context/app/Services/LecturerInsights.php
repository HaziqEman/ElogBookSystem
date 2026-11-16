<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Lecturer;
use App\Models\Logbook;
use App\Models\Student;
use App\Models\Todo;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class LecturerInsights
{
    private const RANK = ['High' => 3, 'Medium' => 2, 'Low' => 1];

    protected array $weekChart = [];

    protected array $trend = [];

    public function __construct(protected Lecturer $lecturer, protected Carbon $today)
    {
        $this->today = $today->copy();
    }

    public function build(): array
    {
        $students = Student::where('lecturer_id', $this->lecturer->lecturer_id)
            ->with('supervisor')
            ->orderBy('name')
            ->get();

        $ids = $students->pluck('student_id')->all();

        $logbooks = $ids
            ? Logbook::whereIn('student_id', $ids)
                ->get(['logbook_id', 'student_id', 'activity_date', 'supervisor_status', 'created_at'])
                ->groupBy('student_id')
            : collect();

        $attendance = ($ids && Schema::hasTable('attendances'))
            ? Attendance::whereIn('student_id', $ids)
                ->get(['attendance_id', 'student_id', 'attendance_date', 'check_in', 'status', 'created_at'])
                ->groupBy('student_id')
            : collect();

        // Tasks: only the completion columns are loaded. Titles are never read.
        $todos = ($ids && Schema::hasTable('todos'))
            ? Todo::whereIn('student_id', $ids)
                ->get(['todo_id', 'student_id', 'is_done', 'due_date'])
                ->groupBy('student_id')
            : collect();

        $monday = $this->today->copy()->startOfWeek(Carbon::MONDAY);

        foreach (range(0, 6) as $i) {
            $day = $monday->copy()->addDays($i);

            $this->weekChart[$day->toDateString()] = [
                'label' => $day->format('D'),
                'date' => $day->format('d M'),
                'submitted' => 0,
                'expected' => 0,
                'is_today' => $day->isSameDay($this->today),
            ];
        }

        foreach (range(3, 0) as $k) {
            $start = $monday->copy()->subWeeks($k);

            $this->trend[$start->toDateString()] = [
                'label' => $start->format('d M').' - '.$start->copy()->addDays(6)->format('d M'),
                'expected' => 0,
                'present' => 0,
                'leave' => 0,
            ];
        }

        $rows = $students->map(fn (Student $student) => $this->analyse(
            $student,
            $logbooks->get($student->student_id, collect()),
            $attendance->get($student->student_id, collect()),
            $todos->get($student->student_id, collect())
        ))->keyBy('id');

        $tasksTotal = $rows->sum('tasks_total');
        $tasksDone = $rows->sum('tasks_done');

        $kpis = [
            'students' => $rows->count(),
            'no_dates' => $rows->where('has_dates', false)->count(),
            'attendance_alerts' => $rows->where('recent_missing_attendance', '>', 0)->count(),
            'missing_entries' => (int) $rows->sum('missing_entries'),
            'students_missing' => $rows->where('missing_entries', '>', 0)->count(),
            'at_risk' => $rows->where('priority', 'High')->count(),
            'task_rate' => $tasksTotal > 0 ? (int) round($tasksDone / $tasksTotal * 100) : null,
            'students_overdue_tasks' => $rows->where('tasks_overdue', '>', 0)->count(),
        ];

        $attention = $rows
            ->filter(fn ($row) => in_array($row['priority'], ['High', 'Medium'], true))
            ->sort(fn ($a, $b) => [self::RANK[$b['priority']], $b['quiet_days']] <=> [self::RANK[$a['priority']], $a['quiet_days']])
            ->values();

        $trend = collect($this->trend)->map(function ($week) {
            $rate = $week['expected'] > 0 ? (int) round($week['present'] / $week['expected'] * 100) : null;

            return $week + ['rate' => $rate];
        })->values()->all();

        return [
            'students' => $rows,
            'attention' => $attention,
            'kpis' => $kpis,
            'week_chart' => array_values($this->weekChart),
            'trend' => $trend,
        ];
    }

    protected function analyse(Student $student, Collection $entries, Collection $attendance, Collection $todos): array
    {
        $tz = Attendance::TIMEZONE;
        $planner = new WeekPlanner($student, $this->today);
        $todayStart = $this->today->copy()->startOfDay();
        $todayKey = $todayStart->toDateString();
        $assigned = (bool) $student->supervisor_id;

        // Tasks: counts only.
        $tasksTotal = $todos->count();
        $tasksDone = $todos->filter(fn ($todo) => (bool) $todo->is_done)->count();
        $tasksOverdue = $todos->filter(fn ($todo) => ! $todo->is_done && $todo->due_date && $todo->due_date->toDateString() < $todayKey)->count();

        // Company supervisor review.
        $weekStartKey = $todayStart->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $staleKey = $todayStart->copy()->subDays(7)->toDateString();

        $revision = $entries->where('supervisor_status', 'Revision Requested')->count();
        $pending = $entries->filter(fn ($entry) => ($entry->supervisor_status ?? 'Pending') === 'Pending');
        $pendingOld = $pending->filter(fn ($entry) => Carbon::parse($entry->activity_date)->toDateString() < $weekStartKey)->count();
        $stale = $pending->filter(fn ($entry) => Carbon::parse($entry->activity_date)->toDateString() < $staleKey)->count();

        if (! $assigned) {
            $reviewLabel = 'No supervisor';
        } elseif ($revision > 0) {
            $reviewLabel = 'Revision requested';
        } elseif ($pendingOld > 0) {
            $reviewLabel = 'Pending review';
        } elseif ($entries->isEmpty()) {
            $reviewLabel = 'No entries';
        } else {
            $reviewLabel = 'Up to date';
        }

        // Last activity: the newest logbook entry or clock-in.
        $last = null;
        $lastLabel = null;

        foreach ([[$entries->max('created_at'), 'Logbook entry'], [$attendance->max('check_in'), 'Attendance']] as [$at, $name]) {
            if ($at && (! $last || $at->gt($last))) {
                $last = $at->copy();
                $lastLabel = $name;
            }
        }

        if ($last) {
            $last->timezone($tz);
        }

        $row = [
            'id' => $student->student_id,
            'student' => $student,
            'planner' => $planner,
            'has_dates' => $planner->hasStart(),
            'started' => false,
            'finished' => false,
            'current_week' => $planner->currentWeek(),
            'total_weeks' => $planner->totalWeeks(),
            'start' => $planner->start(),
            'end' => $planner->hasStart() ? $planner->weekStart($planner->totalWeeks())->addDays(6) : null,
            'expected' => 0,
            'attendance_done' => 0,
            'entries_done' => 0,
            'missing_entries' => 0,
            'recent_missing_attendance' => 0,
            'today_pending' => false,
            'quiet_days' => 0,
            'last_at' => $last,
            'last_label' => $lastLabel,
            'supervisor_assigned' => $assigned,
            'supervisor_name' => optional($student->supervisor)->name,
            'review_label' => $reviewLabel,
            'revision' => $revision,
            'stale' => $stale,
            'tasks_total' => $tasksTotal,
            'tasks_done' => $tasksDone,
            'tasks_overdue' => $tasksOverdue,
            'issues' => [],
            'priority' => null,
            'main_issue' => null,
            'extra_issues' => 0,
        ];

        $issues = [];
        $add = function (string $priority, string $text) use (&$issues) {
            $issues[] = ['priority' => $priority, 'text' => $text];
        };

        if (! $planner->hasStart()) {
            $add('Low', 'Internship dates are not set (ask the administrator)');

            return $this->finish($row, $issues);
        }

        $week = $planner->currentWeek();
        $started = $week >= 1;
        $finished = $week > $planner->totalWeeks();
        $active = $started && ! $finished;

        $entriesByDate = $entries->groupBy(fn ($entry) => Carbon::parse($entry->activity_date)->toDateString());
        $attByDate = $attendance->keyBy(fn ($record) => $record->attendance_date->toDateString());

        // Days that were due (before today, working, and not an excused day off).
        $counted = [];
        $lastDue = $todayStart->copy()->subDay();
        $end = $row['end'];

        if ($lastDue->gt($end)) {
            $lastDue = $end->copy();
        }

        if ($started) {
            for ($d = $planner->start()->copy(); $d->lte($lastDue); $d->addDay()) {
                if (! $planner->isWorkingDay($d)) {
                    continue;
                }

                $key = $d->toDateString();
                $att = $attByDate->get($key);
                $dayOff = $att && $att->status === 'Leave';
                $present = (bool) ($att && $att->check_in);

                $weekKey = $d->copy()->startOfWeek(Carbon::MONDAY)->toDateString();

                if (isset($this->trend[$weekKey])) {
                    if ($dayOff) {
                        $this->trend[$weekKey]['leave']++;
                    } else {
                        $this->trend[$weekKey]['expected']++;

                        if ($present) {
                            $this->trend[$weekKey]['present']++;
                        }
                    }
                }

                if ($dayOff) {
                    continue;
                }

                $counted[] = [
                    'key' => $key,
                    'week' => $planner->weekNumberFor($d),
                    'present' => $present,
                    'entry' => $entriesByDate->has($key),
                ];
            }
        }

        $expected = count($counted);
        $entriesDone = count(array_filter($counted, fn ($day) => $day['entry']));
        $attendanceDone = count(array_filter($counted, fn ($day) => $day['present']));
        $missingEntries = $expected - $entriesDone;
        $recentMissingAttendance = count(array_filter(array_slice($counted, -5), fn ($day) => ! $day['present']));
        $missingWeeks = collect($counted)->filter(fn ($day) => ! $day['entry'])->pluck('week')->unique()->sort()->values()->all();

        $lastKey = $last ? $last->toDateString() : null;
        $quietDays = count(array_filter($counted, fn ($day) => $lastKey === null || $day['key'] > $lastKey));

        $attToday = $attByDate->get($todayKey);
        $workingToday = $active && $planner->isWorkingDay($todayStart);
        $dayOffToday = $attToday && $attToday->status === 'Leave';
        $todayPending = $workingToday && ! $dayOffToday && ! ($attToday && $attToday->check_in);

        // This week's chart: how many students have an entry for each day.
        foreach (array_keys($this->weekChart) as $key) {
            $date = Carbon::parse($key, $tz)->startOfDay();

            if (! $planner->isWorkingDay($date)) {
                continue;
            }

            $number = $planner->weekNumberFor($date);

            if ($number < 1 || $number > $planner->totalWeeks()) {
                continue;
            }

            $att = $attByDate->get($key);

            if ($att && $att->status === 'Leave') {
                continue;
            }

            $this->weekChart[$key]['expected']++;

            if ($entriesByDate->has($key)) {
                $this->weekChart[$key]['submitted']++;
            }
        }

        // Issues, using the rules shown on the dashboard.
        if ($active && $recentMissingAttendance > 0) {
            $add(
                $recentMissingAttendance >= 3 ? 'High' : 'Medium',
                $recentMissingAttendance.' missing attendance '.Str::plural('day', $recentMissingAttendance).' (last 5 working days)'
            );
        }

        if ($missingEntries > 0) {
            $add(
                $missingEntries >= 3 ? 'High' : 'Medium',
                $missingEntries.' overdue logbook '.Str::plural('entry', $missingEntries)
                    .($missingWeeks ? ' (Week '.implode(', ', $missingWeeks).')' : '')
            );
        }

        if ($active && $quietDays >= 3) {
            $add($quietDays >= 5 ? 'High' : 'Medium', 'No update for '.$quietDays.' working days');
        }

        if ($revision > 0) {
            $add('Medium', $revision.' '.Str::plural('entry', $revision).' need revision (company supervisor)');
        }

        if ($stale > 0) {
            $add('Medium', $stale.' '.Str::plural('entry', $stale).' waiting for the supervisor for over 7 days');
        }

        if ($tasksOverdue > 0) {
            $add($tasksOverdue >= 3 ? 'Medium' : 'Low', $tasksOverdue.' overdue '.Str::plural('task', $tasksOverdue));
        }

        if (! $assigned) {
            $add('Low', 'No company supervisor assigned');
        }

        $row['started'] = $started;
        $row['finished'] = $finished;
        $row['expected'] = $expected;
        $row['attendance_done'] = $attendanceDone;
        $row['entries_done'] = $entriesDone;
        $row['missing_entries'] = $missingEntries;
        $row['recent_missing_attendance'] = $active ? $recentMissingAttendance : 0;
        $row['today_pending'] = $todayPending;
        $row['quiet_days'] = $quietDays;

        return $this->finish($row, $issues);
    }

    protected function finish(array $row, array $issues): array
    {
        usort($issues, fn ($a, $b) => self::RANK[$b['priority']] <=> self::RANK[$a['priority']]);

        $row['issues'] = $issues;
        $row['priority'] = $issues[0]['priority'] ?? null;
        $row['main_issue'] = $issues[0]['text'] ?? null;
        $row['extra_issues'] = max(count($issues) - 1, 0);

        return $row;
    }
}