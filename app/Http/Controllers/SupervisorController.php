<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Logbook;
use App\Models\Student;
use App\Models\WeekSubmission;
use App\Services\WeekBatchService;
use App\Services\WeekPlanner;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SupervisorController extends Controller
{
    public function dashboard(Request $request)
    {
        $supervisor = Auth::guard('supervisor')->user();
        $today = Carbon::now(Attendance::TIMEZONE);
        $batchService = app(WeekBatchService::class);

        $students = Student::where('supervisor_id', $supervisor->supervisor_id)
            ->orderBy('name')
            ->get();

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

        // Days with at least one entry, per student and week (for the "in progress" panel).
        $daysMap = [];
        foreach ((clone $base)
            ->selectRaw('student_id, week_no, COUNT(DISTINCT activity_date) as d')
            ->groupBy('student_id', 'week_no')
            ->get() as $row) {
            $daysMap[$row->student_id][(int) $row->week_no] = (int) $row->d;
        }

        // Weeks that were sent to this supervisor. Only these count as "waiting".
        $batches = WeekSubmission::whereIn('student_id', $ids)->get()
            ->map(function ($sub) use ($statMap, $students, $today) {
                $g = $statMap[$sub->student_id][$sub->week_no] ?? [];
                $pending = $g['Pending'] ?? 0;
                $waitDays = $sub->sent_at ? (int) abs($sub->sent_at->diffInDays($today)) : 0;

                return [
                    'student' => $students->firstWhere('student_id', $sub->student_id),
                    'week' => (int) $sub->week_no,
                    'pending' => $pending,
                    'validated' => $g['Validated'] ?? 0,
                    'revision' => $g['Revision Requested'] ?? 0,
                    'total' => array_sum($g),
                    'sent_at' => $sub->sent_at,
                    'wait_days' => $waitDays,
                    'priority' => $pending > 0 ? ($waitDays >= 7 ? 'High' : 'Medium') : null,
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

        $needsReview = $waiting
            ->sortBy(fn ($b) => [$b['priority'] === 'High' ? 0 : 1, $b['sent_at'] ? $b['sent_at']->timestamp : 0])
            ->values()
            ->take(6);

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

        // Review progress for the last 4 weeks (by activity date).
        $weekStart = $today->copy()->startOfWeek();
        $trend = collect(range(3, 0))->map(function ($ago) use ($base, $weekStart) {
            $from = $weekStart->copy()->subWeeks($ago);
            $to = $from->copy()->endOfWeek();
            $list = (clone $base)->whereBetween('activity_date', [$from->toDateString(), $to->toDateString()])
                ->get(['supervisor_status']);
            $reviewed = $list->filter(fn ($l) => ($l->supervisor_status ?? 'Pending') !== 'Pending')->count();

            return [
                'label' => $from->format('d M').' - '.$to->format('d M'),
                'rate' => $list->count() ? (int) round($reviewed / $list->count() * 100) : null,
                'total' => $list->count(),
                'pending' => $list->count() - $reviewed,
            ];
        });

        // One row per student for the progress table.
        $rows = $students->map(function ($student) use ($batches, $inProgress) {
            $mine = $batches->where('student.student_id', $student->student_id);
            $waitingWeeks = $mine->where('pending', '>', 0);
            $pending = $mine->sum('pending');
            $revision = $mine->sum('revision');
            $total = $mine->sum('total');
            $live = $inProgress->firstWhere('student.student_id', $student->student_id);

            $label = $pending > 0 ? 'Pending review'
                : ($revision > 0 ? 'Revision requested'
                : ($total > 0 ? 'Up to date' : 'Nothing sent yet'));

            return [
                'student' => $student,
                'weeks_sent' => $mine->count(),
                'weeks_waiting' => $waitingWeeks->count(),
                'pending' => $pending,
                'validated' => $mine->sum('validated'),
                'revision' => $revision,
                'total' => $total,
                'oldest_days' => $waitingWeeks->max('wait_days'),
                'oldest_week' => optional($waitingWeeks->sortBy(fn ($b) => $b['sent_at'] ? $b['sent_at']->timestamp : 0)->first())['week'] ?? null,
                'last_sent' => $mine->max(fn ($b) => $b['sent_at'] ? $b['sent_at']->timestamp : 0),
                'live' => $live,
                'review_label' => $label,
            ];
        });

        // Search + filter + paginate the student table.
        $q = trim((string) $request->query('q', ''));
        $filter = (string) $request->query('filter', 'all');
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
        $perPage = 8;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $studentRows = new LengthAwarePaginator(
            $table->forPage($page, $perPage)->values(),
            $table->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query(), 'fragment' => 'students']
        );

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
        ]);
    }

    public function showPassword()
    {
        $supervisor = Auth::guard('supervisor')->user();

        return view('supervisor.password', compact('supervisor'));
    }

    public function updatePassword(Request $request)
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

        return redirect('/supervisor/dashboard')->with('success', 'Password updated.');
    }
}
