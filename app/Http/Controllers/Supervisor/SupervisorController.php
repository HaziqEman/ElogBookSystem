<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller; //
use App\Models\Attendance;
use App\Models\Logbook;
use App\Models\Student;
use App\Models\Todo;
use App\Models\WeekSubmission;
use App\Services\WeekBatchService;
use App\Services\WeekPlanner;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SupervisorController extends Controller
{
    private const OVERDUE_DAYS = 7;

    private const PER_PAGE = 8;

    private const REVIEW_QUEUE_SIZE = 6;

    private const FILTERS = ['all', 'pending', 'revision', 'uptodate'];

    public function dashboard(Request $request): View
    {
        $supervisor = Auth::guard('supervisor')->user();
        $today = Carbon::now(Attendance::TIMEZONE);
        $batchService = app(WeekBatchService::class);

        $students = Student::where('supervisor_id', $supervisor->supervisor_id)
            ->orderBy('name')
            ->get();

        $studentsById = $students->keyBy('student_id');
        $ids = $students->pluck('student_id');
        $base = Logbook::whereIn('student_id', $ids);

        // Entry counts per student, week and status.
        $statMap = [];
        foreach ((clone $base)
            ->selectRaw('student_id, week_no, supervisor_status, COUNT(*) as c')
            ->groupBy('student_id', 'week_no', 'supervisor_status')
            ->get() as $row) {
            $statMap[$row->student_id][(int) $row->week_no][$row->supervisor_status ?: 'Pending'] = (int) $row->c;
        }

        // Weeks that were sent to this supervisor. Only these count as "waiting".
        // A student + week can only be one batch, so duplicate submission rows are collapsed
        // into the most recent one. Without this, every duplicate row doubles the KPI numbers.
        $batches = WeekSubmission::whereIn('student_id', $ids)->get()
            ->groupBy(fn ($sub) => $sub->student_id.'-'.(int) $sub->week_no)
            ->map(fn ($group) => $group->sortByDesc(fn ($sub) => $sub->sent_at?->timestamp ?? 0)->first())
            ->map(function ($sub) use ($statMap, $studentsById, $today) {
                $g = $statMap[$sub->student_id][(int) $sub->week_no] ?? [];
                $pending = $g['Pending'] ?? 0;
                $waitDays = $sub->sent_at ? (int) abs($sub->sent_at->diffInDays($today)) : 0;

                return [
                    'student' => $studentsById->get($sub->student_id),
                    'week' => (int) $sub->week_no,
                    'pending' => $pending,
                    'validated' => $g['Validated'] ?? 0,
                    'revision' => $g['Revision Requested'] ?? 0,
                    'total' => array_sum($g),
                    'sent_at' => $sub->sent_at,
                    'wait_days' => $waitDays,
                    'priority' => $pending > 0
                        ? ($waitDays >= self::OVERDUE_DAYS ? 'High' : 'Medium')
                        : null,
                ];
            })
            ->filter(fn ($b) => $b['student'] && $b['total'] > 0)
            ->values();

        $waiting = $batches->where('pending', '>', 0);

        $kpis = [
            'students' => $students->count(),
            'weeks_waiting' => $waiting->count(),
            'students_waiting' => $waiting->pluck('student.student_id')->unique()->count(),
            'pending' => $batches->sum('pending'),
            'validated' => $batches->sum('validated'),
            'revision' => $batches->sum('revision'),
            'overdue' => $waiting->where('priority', 'High')->count(),
            'sent_entries' => $batches->sum('total'),
        ];
        $kpis['reviewed_rate'] = $kpis['sent_entries'] > 0
            ? (int) round($kpis['validated'] / $kpis['sent_entries'] * 100)
            : null;

        // Review queue: ONE row per student (not one per week), most urgent first.
        $needsReview = $waiting
            ->groupBy(fn ($b) => $b['student']->student_id)
            ->map(function ($weeks) {
                $oldest = $weeks->sortBy(fn ($b) => $b['sent_at'] ? $b['sent_at']->timestamp : 0)->first();

                return [
                    'student' => $oldest['student'],
                    'weeks_waiting' => $weeks->count(),
                    'pending' => $weeks->sum('pending'),
                    'oldest_week' => $oldest['week'],
                    'wait_days' => $weeks->max('wait_days'),
                    'priority' => $weeks->contains('priority', 'High') ? 'High' : 'Medium',
                ];
            })
            ->sortByDesc(fn ($r) => [$r['priority'] === 'High' ? 1 : 0, $r['wait_days']])
            ->values()
            ->take(self::REVIEW_QUEUE_SIZE);

        // This week, not sent yet: how far along is each student?
        $inProgress = $students->map(function ($student) use ($today, $batchService) {
            $planner = new WeekPlanner($student, $today);

            if (! $planner->hasStart()) {
                return null;
            }

            $week = $planner->currentWeek();

            if ($week === null || $week < 1 || $week > $planner->totalWeeks()) {
                return null;
            }

            $start = $planner->weekStart($week)->copy()->startOfDay();
            $end = $start->copy()->addDays(6);

            $dates = Logbook::where('student_id', $student->student_id)
                ->whereBetween('activity_date', [$start->toDateString(), $end->toDateString()])
                ->pluck('activity_date')
                ->map(fn ($d) => Carbon::parse($d)->toDateString())
                ->unique();

            $required = 0;
            $logged = 0;
            $extra = 0;

            for ($i = 0; $i < 7; $i++) {
                $day = $start->copy()->addDays($i);
                $has = $dates->contains($day->toDateString());

                if ($planner->isWorkingDay($day)) {
                    $required++;
                    $logged += $has ? 1 : 0;
                } elseif ($has) {
                    $extra++;
                }
            }

            return [
                'student' => $student,
                'week' => $week,
                'logged' => $logged,
                'required' => $required,
                'extra' => $extra,
                'percent' => $required > 0 ? min(100, (int) round($logged / $required * 100)) : 0,
                'closes' => $batchService->lastWorkingDay($planner, $week),
            ];
        })->filter()->values();

        // Review progress for the last 4 weeks (by activity date), counted in the database.
        $weekStart = $today->copy()->startOfWeek();
        $trend = collect(range(3, 0))->map(function ($ago) use ($base, $weekStart) {
            $from = $weekStart->copy()->subWeeks($ago);
            $to = $from->copy()->endOfWeek();

            $counts = (clone $base)
                ->whereBetween('activity_date', [$from->toDateString(), $to->toDateString()])
                ->selectRaw("COUNT(*) as total, SUM(CASE WHEN COALESCE(supervisor_status, 'Pending') <> 'Pending' THEN 1 ELSE 0 END) as reviewed")
                ->first();

            $total = (int) ($counts->total ?? 0);
            $reviewed = (int) ($counts->reviewed ?? 0);

            return [
                'label' => $from->format('d M').' - '.$to->format('d M'),
                'rate' => $total > 0 ? (int) round($reviewed / $total * 100) : null,
                'total' => $total,
                'pending' => $total - $reviewed,
            ];
        });

        // One row per student for the progress table.
        $rows = $students->map(function ($student) use ($batches, $inProgress) {
            $mine = $batches->where('student.student_id', $student->student_id);
            $waitingWeeks = $mine->where('pending', '>', 0);
            $pending = $mine->sum('pending');
            $validated = $mine->sum('validated');
            $revision = $mine->sum('revision');
            $total = $mine->sum('total');

            $label = $pending > 0 ? 'Pending review'
                : ($revision > 0 ? 'Revision requested'
                    : ($total > 0 ? 'Up to date' : 'Nothing sent yet'));

            return [
                'student' => $student,
                'weeks_sent' => $mine->count(),
                'weeks_waiting' => $waitingWeeks->count(),
                'pending' => $pending,
                'validated' => $validated,
                'validated_rate' => $total > 0 ? (int) round($validated / $total * 100) : null,
                'revision' => $revision,
                'total' => $total,
                'oldest_days' => $waitingWeeks->max('wait_days'),
                'oldest_week' => $waitingWeeks
                    ->sortBy(fn ($b) => $b['sent_at'] ? $b['sent_at']->timestamp : 0)
                    ->first()['week'] ?? null,
                'last_sent' => $mine->max(fn ($b) => $b['sent_at'] ? $b['sent_at']->timestamp : 0),
                'live' => $inProgress->firstWhere('student.student_id', $student->student_id),
                'review_label' => $label,
            ];
        });

        // Search + filter + paginate the student table.
        $q = trim((string) $request->query('q', ''));
        $filter = (string) $request->query('filter', 'all');
        $filter = in_array($filter, self::FILTERS, true) ? $filter : 'all';
        $table = $rows;

        if ($q !== '') {
            $needle = mb_strtolower($q);
            $table = $table->filter(fn ($r) => str_contains(mb_strtolower((string) $r['student']->name), $needle)
                || str_contains(mb_strtolower((string) $r['student']->matric_no), $needle));
        }

        $table = match ($filter) {
            'pending' => $table->filter(fn ($r) => $r['pending'] > 0),
            'revision' => $table->filter(fn ($r) => $r['revision'] > 0),
            'uptodate' => $table->filter(fn ($r) => $r['review_label'] === 'Up to date'),
            default => $table,
        };

        $table = $table->sortByDesc('pending')->values();
        $page = LengthAwarePaginator::resolveCurrentPage();
        $studentRows = new LengthAwarePaginator(
            $table->forPage($page, self::PER_PAGE)->values(),
            $table->count(),
            self::PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => $request->query(), 'fragment' => 'students']
        );

        // Tasks this supervisor assigned. Students' own tasks have supervisor_id = null, so they never show here.
        $todayKey = $today->toDateString();
        $tasks = Todo::query()
            ->where('supervisor_id', $supervisor->supervisor_id)
            ->whereIn('student_id', $ids);

        $taskCounts = [
            'done' => (clone $tasks)->where('is_done', true)->count(),
            'overdue' => (clone $tasks)->where('is_done', false)->whereDate('due_date', '<', $todayKey)->count(),
            'open' => (clone $tasks)->where('is_done', false)
                ->where(fn ($q) => $q->whereNull('due_date')->orWhereDate('due_date', '>=', $todayKey))
                ->count(),
        ];

        // Four tasks that need attention: most overdue first, no-deadline tasks last.
        $taskPreview = (clone $tasks)->with('student')
            ->where('is_done', false)
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->limit(4)
            ->get();

        $greeting = $today->hour < 12 ? 'Good morning' : ($today->hour < 17 ? 'Good afternoon' : 'Good evening');

        return view('supervisor.dashboard', [
            'supervisor' => $supervisor,
            'today' => $today,
            'greeting' => $greeting,
            'kpis' => $kpis,
            'needsReview' => $needsReview,
            'inProgress' => $inProgress,
            'trend' => $trend,
            'students' => $studentRows,
            'totalStudents' => $rows->count(),
            'q' => $q,
            'filter' => $filter,
            'taskCounts' => $taskCounts,
            'taskPreview' => $taskPreview,
        ]);
    }

    public function showPassword(): View
    {
        $supervisor = Auth::guard('supervisor')->user();

        return view('supervisor.password', compact('supervisor'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $supervisor = Auth::guard('supervisor')->user();

        $data = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed|different:current_password',
        ], [
            'password.different' => 'The new password must be different from the current one.',
        ]);

        if (! Hash::check($data['current_password'], $supervisor->password)) {
            return back()->withErrors(['current_password' => 'The current password is not correct.']);
        }

        $supervisor->password = Hash::make($data['password']);
        $supervisor->must_change_password = false;
        $supervisor->save();

        return redirect()->route('supervisor.dashboard')->with('success', 'Password updated.');
    }
}
