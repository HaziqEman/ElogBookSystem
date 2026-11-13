<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Lecturer;
use App\Models\Logbook;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\SupervisorEvaluation;
use App\Models\Todo;
use App\Models\University;
use App\Models\WeekSubmission;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Builds every number the internship coordinator needs, for all students at once.
 * It reuses WeekPlanner so the totals match the lecturer and student dashboards.
 * Task titles and logbook text are never read (counts only).
 */
class InternshipReportService
{
    public const STATUSES = ['On track', 'Completed', 'Behind', 'At risk', 'Incomplete', 'Not started', 'Dates not set'];

    private const RANK = ['High' => 3, 'Medium' => 2, 'Low' => 1];

    protected Carbon $today;

    /** @var array<int, array> */
    protected array $weekly = [];

    /** @var array<int, int> week number => students who sent that week */
    protected array $sentPerWeek = [];

    public function __construct(?Carbon $today = null)
    {
        $this->today = ($today ?? Carbon::now(Attendance::TIMEZONE))->copy()->startOfDay();
    }

    /**
     * @param  array{lecturer_id?:int|string,supervisor_id?:int|string,company?:string,course?:string,university_id?:int|string}  $filters
     */
    public function build(array $filters = []): array
    {
        $this->weekly = [];
        $this->sentPerWeek = [];

        $students = $this->students($filters);
        $ids = $students->pluck('student_id')->all();

        $logbooks = $ids
            ? Logbook::whereIn('student_id', $ids)
                ->get(['logbook_id', 'student_id', 'week_no', 'activity_date', 'supervisor_status', 'created_at'])
                ->groupBy('student_id')
            : collect();

        $attendance = ($ids && Schema::hasTable('attendances'))
            ? Attendance::whereIn('student_id', $ids)->get()->groupBy('student_id')
            : collect();

        // Tasks: completion columns only. Titles are never loaded.
        $todos = ($ids && Schema::hasTable('todos'))
            ? Todo::whereIn('student_id', $ids)->get(['todo_id', 'student_id', 'is_done', 'due_date'])->groupBy('student_id')
            : collect();

        $submissions = ($ids && Schema::hasTable('week_submissions'))
            ? WeekSubmission::whereIn('student_id', $ids)
                ->get(['submission_id', 'student_id', 'week_no', 'sent_at', 'reminded_at', 'reviewed_at'])
                ->groupBy('student_id')
            : collect();

        $evaluations = ($ids && Schema::hasTable('supervisor_evaluations'))
            ? SupervisorEvaluation::whereIn('student_id', $ids)->get()->keyBy('student_id')
            : collect();

        $rows = [];

        foreach ($students as $student) {
            $rows[] = $this->analyse(
                $student,
                $logbooks->get($student->student_id, collect()),
                $attendance->get($student->student_id, collect()),
                $todos->get($student->student_id, collect()),
                $submissions->get($student->student_id, collect()),
                $evaluations->get($student->student_id)
            );
        }

        $attention = collect($rows)
            ->filter(fn ($row) => in_array($row['priority'], ['High', 'Medium'], true))
            ->sort(fn ($a, $b) => [self::RANK[$b['priority']], $b['quiet_days']] <=> [self::RANK[$a['priority']], $a['quiet_days']])
            ->values()
            ->all();

        $lecturers = $this->lecturerRows($rows, empty($filters));
        $supervisors = $this->supervisorRows($rows, empty($filters));
        $companies = $this->companyRows($rows);

        return [
            'generated_at' => Carbon::now(Attendance::TIMEZONE),
            'filters_label' => $this->filtersLabel($filters),
            'students' => $rows,
            'weekly' => $this->weekly,
            'attention' => $attention,
            'lecturers' => $lecturers,
            'supervisors' => $supervisors,
            'companies' => $companies,
            'kpis' => $this->kpis($rows),
            'status_counts' => $this->statusCounts($rows),
            'top_pending' => $this->topPending($supervisors),
            'weekly_sent' => $this->weeklySent($rows),
        ];
    }

    protected function students(array $filters): Collection
    {
        return Student::with(['lecturer', 'supervisor', 'university'])
            ->when(! empty($filters['lecturer_id']), fn ($q) => $q->where('lecturer_id', $filters['lecturer_id']))
            ->when(! empty($filters['supervisor_id']), fn ($q) => $q->where('supervisor_id', $filters['supervisor_id']))
            ->when(! empty($filters['course']), fn ($q) => $q->where('course', $filters['course']))
            ->when(! empty($filters['university_id']), fn ($q) => $q->where('university_id', $filters['university_id']))
            ->when(! empty($filters['company']), fn ($q) => $q->whereHas('supervisor', fn ($s) => $s->where('company_name', $filters['company'])))
            ->orderBy('name')
            ->get();
    }

    protected function filtersLabel(array $filters): string
    {
        if (! $filters) {
            return 'All students';
        }

        $parts = [];

        if (! empty($filters['lecturer_id'])) {
            $parts[] = 'Lecturer: '.(optional(Lecturer::find($filters['lecturer_id']))->name ?? $filters['lecturer_id']);
        }

        if (! empty($filters['supervisor_id'])) {
            $parts[] = 'Supervisor: '.(optional(Supervisor::find($filters['supervisor_id']))->name ?? $filters['supervisor_id']);
        }

        if (! empty($filters['company'])) {
            $parts[] = 'Company: '.$filters['company'];
        }

        if (! empty($filters['course'])) {
            $parts[] = 'Course: '.$filters['course'];
        }

        if (! empty($filters['university_id'])) {
            $parts[] = 'University: '.(optional(University::find($filters['university_id']))->name ?? $filters['university_id']);
        }

        return implode(' | ', $parts);
    }

    protected function analyse(
        Student $student,
        Collection $entries,
        Collection $attendance,
        Collection $todos,
        Collection $submissions,
        ?SupervisorEvaluation $evaluation
    ): array {
        $tz = Attendance::TIMEZONE;
        $planner = new WeekPlanner($student, $this->today);
        $todayKey = $this->today->toDateString();
        $assigned = (bool) $student->supervisor_id;
        $supervisor = $student->supervisor;

        // Tasks: counts only.
        $tasksTotal = $todos->count();
        $tasksDone = $todos->filter(fn ($todo) => (bool) $todo->is_done)->count();
        $tasksOverdue = $todos->filter(fn ($todo) => ! $todo->is_done && $todo->due_date && $todo->due_date->toDateString() < $todayKey)->count();

        // Company supervisor review of the entries.
        $weekStartKey = $this->today->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $staleKey = $this->today->copy()->subDays(7)->toDateString();

        $revision = $entries->where('supervisor_status', 'Revision Requested')->count();
        $pending = $entries->filter(fn ($entry) => ($entry->supervisor_status ?? 'Pending') === 'Pending');
        $pendingOld = $pending->filter(fn ($entry) => Carbon::parse($entry->activity_date)->toDateString() < $weekStartKey)->count();
        $stale = $pending->filter(fn ($entry) => Carbon::parse($entry->activity_date)->toDateString() < $staleKey)->count();

        // Day-off and work-from-home counts.
        $mc = $leave = $holiday = $wfh = 0;

        foreach ($attendance as $record) {
            if ($record->status === 'Leave') {
                match ($record->day_off_type) {
                    'MC' => $mc++,
                    'Public holiday' => $holiday++,
                    default => $leave++,
                };
            } elseif ($record->work_mode === 'Home') {
                $wfh++;
            }
        }

        // Last activity: newest logbook entry or clock-in.
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

        $evaluationSubmitted = $evaluation && $evaluation->submitted_at ? $evaluation->submitted_at->copy()->timezone($tz) : null;
        $evaluationWeight = $evaluationSubmitted
            ? (float) ($evaluation->rubric_snapshot['weight'] ?? config('supervisor_rubric.weight', 100))
            : null;
        $evaluationMark = $evaluationSubmitted ? (float) $evaluation->final_mark : null;
        $evaluationPercentage = $evaluationMark !== null && $evaluationWeight > 0
            ? $evaluationMark / $evaluationWeight
            : null;

        $row = [
            'id' => $student->student_id,
            'matric' => $student->matric_no,
            'name' => $student->name,
            'course' => $student->course,
            'university' => optional($student->university)->name,
            'email' => $student->email,
            'phone' => $student->phone_no,
            'lecturer_id' => $student->lecturer_id,
            'lecturer' => optional($student->lecturer)->name,
            'supervisor_id' => $student->supervisor_id,
            'supervisor' => optional($supervisor)->name,
            'supervisor_email' => optional($supervisor)->email,
            'supervisor_phone' => optional($supervisor)->phone,
            'company' => optional($supervisor)->company_name ?: null,
            'company_address' => $student->organization_address,
            'department' => $student->department,
            'has_dates' => $planner->hasStart(),
            'start' => $planner->hasStart() ? $planner->start()->copy() : null,
            'end' => $planner->hasStart() ? $planner->weekStart($planner->totalWeeks())->addDays(6) : null,
            'total_weeks' => $planner->totalWeeks(),
            'current_week' => null,
            'started' => false,
            'finished' => false,
            'status' => 'Dates not set',
            'weeks_sent' => $submissions->filter(fn ($s) => $s->sent_at)->count(),
            'weeks_validated' => 0,
            'weeks_revision' => 0,
            'weeks_waiting' => 0,
            'entries_total' => $entries->count(),
            'expected' => 0,
            'entries_done' => 0,
            'missing_entries' => 0,
            'logbook_rate' => null,
            'attendance_done' => 0,
            'attendance_rate' => null,
            'recent_missing_attendance' => 0,
            'mc_days' => $mc,
            'leave_days' => $leave,
            'holiday_days' => $holiday,
            'wfh_days' => $wfh,
            'last_at' => $last,
            'last_label' => $lastLabel,
            'quiet_days' => 0,
            'tasks_total' => $tasksTotal,
            'tasks_done' => $tasksDone,
            'tasks_overdue' => $tasksOverdue,
            'pending_entries' => $pendingOld,
            'overdue_reviews' => $stale,
            'revision_entries' => $revision,
            'eval_mark' => $evaluationMark,
            'eval_weight' => $evaluationWeight,
            'eval_percentage' => $evaluationPercentage,
            'eval_status' => $evaluationSubmitted ? 'Submitted' : 'Not submitted',
            'eval_submitted' => $evaluationSubmitted,
            'issues' => [],
            'priority' => null,
            'main_issue' => null,
        ];

        foreach ($submissions as $submission) {
            if ($submission->sent_at) {
                $this->sentPerWeek[(int) $submission->week_no] = ($this->sentPerWeek[(int) $submission->week_no] ?? 0) + 1;
            }
        }

        $issues = [];
        $add = function (string $priority, string $text) use (&$issues) {
            $issues[] = ['priority' => $priority, 'text' => $text];
        };

        if (! $assigned) {
            $add('Low', 'No company supervisor assigned');
        }

        if (! $planner->hasStart()) {
            $add('Low', 'Internship dates are not set');

            return $this->finish($row, $issues);
        }

        $week = $planner->currentWeek();
        $started = $week >= 1;
        $finished = $week > $planner->totalWeeks();
        $active = $started && ! $finished;

        $entriesByDate = $entries->groupBy(fn ($entry) => Carbon::parse($entry->activity_date)->toDateString());
        $attByDate = $attendance->keyBy(fn ($record) => $record->attendance_date->toDateString());

        // Working days that were already due (before today, excluding excused days off).
        $counted = [];
        $lastDue = $this->today->copy()->subDay();

        if ($lastDue->gt($row['end'])) {
            $lastDue = $row['end']->copy();
        }

        if ($started) {
            for ($d = $planner->start()->copy(); $d->lte($lastDue); $d->addDay()) {
                if (! $planner->isWorkingDay($d)) {
                    continue;
                }

                $key = $d->toDateString();
                $att = $attByDate->get($key);

                if ($att && $att->status === 'Leave') {
                    continue;
                }

                $counted[] = [
                    'key' => $key,
                    'week' => $planner->weekNumberFor($d),
                    'present' => (bool) ($att && $att->check_in),
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

        // Week by week (same status rules as the student and lecturer pages).
        $submissionByWeek = $submissions->keyBy(fn ($s) => (int) $s->week_no);
        $statusNames = [
            'current' => 'In progress',
            'incomplete' => 'Incomplete',
            'revision' => 'Revision requested',
            'waiting' => 'Waiting for review',
            'validated' => 'Validated',
        ];

        $weeksValidated = $weeksRevision = $weeksWaiting = 0;

        foreach ($planner->weeks($entriesByDate, $attByDate) as $w) {
            if ($w['status'] === 'locked') {
                continue;
            }

            $submission = $submissionByWeek->get($w['number']);
            $sentAt = $submission && $submission->sent_at ? $submission->sent_at->copy()->timezone($tz) : null;
            $reviewedAt = $submission && $submission->reviewed_at ? $submission->reviewed_at->copy()->timezone($tz) : null;

            if ($w['status'] === 'validated' && $w['entry_count'] > 0) {
                $weeksValidated++;
            } elseif ($w['status'] === 'revision') {
                $weeksRevision++;
            } elseif ($w['status'] === 'waiting') {
                $weeksWaiting++;
            }

            $daysWaiting = null;

            if ($sentAt && ! $reviewedAt && in_array($w['status'], ['waiting', 'revision'], true)) {
                $daysWaiting = (int) $sentAt->copy()->startOfDay()->diffInDays($this->today);
            }

            $this->weekly[] = [
                'matric' => $row['matric'],
                'name' => $row['name'],
                'lecturer' => $row['lecturer'],
                'company' => $row['company'],
                'supervisor' => $row['supervisor'],
                'week' => $w['number'],
                'week_start' => $w['start']->copy(),
                'week_end' => $w['end']->copy(),
                'required' => $w['required'],
                'done' => $w['done'],
                'missing' => $w['missing'],
                'entries' => $w['entry_count'],
                'rate' => $w['required'] > 0 ? $w['done'] / $w['required'] : null,
                'status' => $statusNames[$w['status']] ?? ucfirst($w['status']),
                'sent_at' => $sentAt,
                'reviewed_at' => $reviewedAt,
                'days_waiting' => $daysWaiting,
            ];
        }

        // Issues, using the same rules as the lecturer dashboard.
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
            $add('Medium', $revision.' '.Str::plural('entry', $revision).' need revision');
        }

        if ($stale > 0) {
            $add('Medium', $stale.' '.Str::plural('entry', $stale).' waiting for the supervisor for over 7 days');
        }

        if ($tasksOverdue > 0) {
            $add($tasksOverdue >= 3 ? 'Medium' : 'Low', $tasksOverdue.' overdue '.Str::plural('task', $tasksOverdue));
        }

        $row['current_week'] = $started ? min($week, $planner->totalWeeks()) : null;
        $row['started'] = $started;
        $row['finished'] = $finished;
        $row['weeks_validated'] = $weeksValidated;
        $row['weeks_revision'] = $weeksRevision;
        $row['weeks_waiting'] = $weeksWaiting;
        $row['expected'] = $expected;
        $row['entries_done'] = $entriesDone;
        $row['missing_entries'] = $missingEntries;
        $row['logbook_rate'] = $expected > 0 ? $entriesDone / $expected : null;
        $row['attendance_done'] = $attendanceDone;
        $row['attendance_rate'] = $expected > 0 ? $attendanceDone / $expected : null;
        $row['recent_missing_attendance'] = $active ? $recentMissingAttendance : 0;
        $row['quiet_days'] = $quietDays;

        return $this->finish($row, $issues);
    }

    protected function finish(array $row, array $issues): array
    {
        usort($issues, fn ($a, $b) => self::RANK[$b['priority']] <=> self::RANK[$a['priority']]);

        $row['issues'] = array_column($issues, 'text');
        $row['priority'] = $issues[0]['priority'] ?? null;
        $row['main_issue'] = $issues[0]['text'] ?? null;

        if (! $row['has_dates']) {
            $row['status'] = 'Dates not set';
        } elseif (! $row['started']) {
            $row['status'] = 'Not started';
        } elseif ($row['finished']) {
            $row['status'] = in_array($row['priority'], ['High', 'Medium'], true) ? 'Incomplete' : 'Completed';
        } elseif ($row['priority'] === 'High') {
            $row['status'] = 'At risk';
        } elseif ($row['priority'] === 'Medium') {
            $row['status'] = 'Behind';
        } else {
            $row['status'] = 'On track';
        }

        return $row;
    }

    protected function avg(Collection $rows, string $key): ?float
    {
        $values = $rows->pluck($key)->filter(fn ($value) => $value !== null);

        return $values->isEmpty() ? null : (float) $values->avg();
    }

    protected function lecturerRows(array $students, bool $includeEmpty): array
    {
        $groups = collect($students)->groupBy('lecturer_id');
        $out = [];

        $lecturers = $includeEmpty
            ? Lecturer::orderBy('name')->get(['lecturer_id', 'name', 'faculty'])
            : Lecturer::whereIn('lecturer_id', $groups->keys()->filter()->all())->orderBy('name')->get(['lecturer_id', 'name', 'faculty']);

        foreach ($lecturers as $lecturer) {
            $rows = collect($groups->get($lecturer->lecturer_id, []));
            $status = $rows->countBy('status');

            $out[] = [
                'name' => $lecturer->name,
                'faculty' => $lecturer->faculty,
                'students' => $rows->count(),
                'on_track' => (int) ($status['On track'] ?? 0) + (int) ($status['Completed'] ?? 0),
                'behind' => (int) ($status['Behind'] ?? 0) + (int) ($status['Incomplete'] ?? 0),
                'at_risk' => (int) ($status['At risk'] ?? 0),
                'not_started' => (int) ($status['Not started'] ?? 0) + (int) ($status['Dates not set'] ?? 0),
                'attendance_rate' => $this->avg($rows, 'attendance_rate'),
                'logbook_rate' => $this->avg($rows, 'logbook_rate'),
                'pending' => (int) $rows->sum('pending_entries'),
                'evaluations' => $rows->filter(fn ($row) => $row['eval_submitted'] !== null)->count(),
            ];
        }

        return $out;
    }

    protected function supervisorRows(array $students, bool $includeEmpty): array
    {
        $groups = collect($students)->groupBy('supervisor_id');
        $out = [];

        $supervisors = $includeEmpty
            ? Supervisor::orderBy('name')->get()
            : Supervisor::whereIn('supervisor_id', $groups->keys()->filter()->all())->orderBy('name')->get();

        foreach ($supervisors as $supervisor) {
            $rows = collect($groups->get($supervisor->supervisor_id, []));
            $evaluated = $rows->filter(fn ($row) => $row['eval_submitted'] !== null)->count();

            $out[] = [
                'name' => $supervisor->name,
                'company' => $supervisor->company_name,
                'position' => $supervisor->position,
                'email' => $supervisor->email,
                'phone' => $supervisor->phone,
                'students' => $rows->count(),
                'pending' => (int) $rows->sum('pending_entries'),
                'overdue' => (int) $rows->sum('overdue_reviews'),
                'revision' => (int) $rows->sum('revision_entries'),
                'evaluated' => $evaluated,
                'not_evaluated' => $rows->count() - $evaluated,
            ];
        }

        return $out;
    }

    protected function companyRows(array $students): array
    {
        $groups = collect($students)->groupBy(fn ($row) => $row['company'] ?: 'No company / supervisor');
        $out = [];

        foreach ($groups->sortKeys() as $company => $rows) {
            $out[] = [
                'company' => $company,
                'interns' => $rows->count(),
                'supervisors' => $rows->pluck('supervisor_id')->filter()->unique()->count(),
                'pending' => (int) $rows->sum('pending_entries'),
                'overdue' => (int) $rows->sum('overdue_reviews'),
                'attendance_rate' => $this->avg($rows, 'attendance_rate'),
            ];
        }

        return $out;
    }

    protected function statusCounts(array $students): array
    {
        $counts = collect($students)->countBy('status');

        return collect(self::STATUSES)->mapWithKeys(fn ($status) => [$status => (int) ($counts[$status] ?? 0)])->all();
    }

    protected function topPending(array $supervisors): array
    {
        return collect($supervisors)
            ->filter(fn ($row) => $row['pending'] > 0)
            ->sortByDesc('pending')
            ->take(10)
            ->map(fn ($row) => ['name' => $row['name'], 'pending' => $row['pending']])
            ->values()
            ->all();
    }

    protected function weeklySent(array $students): array
    {
        $weeks = max(14, (int) collect($students)->max('total_weeks'));
        $weeks = min($weeks, 20);
        $out = [];

        for ($i = 1; $i <= $weeks; $i++) {
            $out[$i] = (int) ($this->sentPerWeek[$i] ?? 0);
        }

        return $out;
    }

    /** @return array<int, array{label:string,value:mixed,percent:bool}> */
    protected function kpis(array $students): array
    {
        $rows = collect($students);
        $status = $rows->countBy('status');

        $tasksTotal = (int) $rows->sum('tasks_total');
        $tasksDone = (int) $rows->sum('tasks_done');
        $evaluated = $rows->filter(fn ($row) => $row['eval_submitted'] !== null)->count();

        return [
            ['label' => 'Students in this report', 'value' => $rows->count(), 'percent' => false],
            ['label' => 'With a company supervisor', 'value' => $rows->filter(fn ($row) => $row['supervisor_id'])->count(), 'percent' => false],
            ['label' => 'Without a company supervisor', 'value' => $rows->filter(fn ($row) => ! $row['supervisor_id'])->count(), 'percent' => false],
            ['label' => 'Internship dates not set', 'value' => (int) ($status['Dates not set'] ?? 0), 'percent' => false],
            ['label' => 'On track or completed', 'value' => (int) ($status['On track'] ?? 0) + (int) ($status['Completed'] ?? 0), 'percent' => false],
            ['label' => 'Behind or incomplete', 'value' => (int) ($status['Behind'] ?? 0) + (int) ($status['Incomplete'] ?? 0), 'percent' => false],
            ['label' => 'At risk', 'value' => (int) ($status['At risk'] ?? 0), 'percent' => false],
            ['label' => 'Average attendance', 'value' => $this->avg($rows, 'attendance_rate'), 'percent' => true],
            ['label' => 'Average logbook completion', 'value' => $this->avg($rows, 'logbook_rate'), 'percent' => true],
            ['label' => 'Entries waiting for review (earlier weeks)', 'value' => (int) $rows->sum('pending_entries'), 'percent' => false],
            ['label' => 'Reviews overdue (over 7 days)', 'value' => (int) $rows->sum('overdue_reviews'), 'percent' => false],
            ['label' => 'Entries needing revision', 'value' => (int) $rows->sum('revision_entries'), 'percent' => false],
            ['label' => 'Supervisor evaluations submitted', 'value' => $evaluated.' / '.$rows->count(), 'percent' => false],
            ['label' => 'Average supervisor evaluation mark', 'value' => $this->avg($rows, 'eval_percentage'), 'percent' => true],
            ['label' => 'Task completion rate', 'value' => $tasksTotal > 0 ? $tasksDone / $tasksTotal : null, 'percent' => true],
        ];
    }
}
