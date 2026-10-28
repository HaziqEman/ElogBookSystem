<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Logbook;
use App\Models\Student;
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

        $students = Student::where('supervisor_id', $supervisor->supervisor_id)
            ->orderBy('name')
            ->get();

        $ids = $students->pluck('student_id');
        $base = Logbook::whereIn('student_id', $ids);

        // Per student + status: count, oldest and newest entry.
        $grouped = (clone $base)
            ->selectRaw('student_id, supervisor_status, COUNT(*) as total, MIN(created_at) as oldest, MAX(created_at) as newest')
            ->groupBy('student_id', 'supervisor_status')
            ->get()
            ->groupBy('student_id');

        $rows = $students->map(function ($student) use ($grouped, $today) {
            $g = ($grouped[$student->student_id] ?? collect())->keyBy(fn ($r) => $r->supervisor_status ?? 'Pending');

            $pending = (int) optional($g->get('Pending'))->total;
            $validated = (int) optional($g->get('Validated'))->total;
            $revision = (int) optional($g->get('Revision Requested'))->total;
            $total = $pending + $validated + $revision;

            $oldest = optional($g->get('Pending'))->oldest;
            $oldestDays = $oldest ? (int) Carbon::parse($oldest)->diffInDays($today) : null;
            $newest = $g->max('newest');

            if ($pending > 0) {
                $priority = $oldestDays >= 7 ? 'High' : 'Medium';
                $issue = $pending.' '.($pending === 1 ? 'entry' : 'entries').' waiting for your review'
                    .($oldestDays >= 1 ? ' (oldest '.$oldestDays.' '.($oldestDays === 1 ? 'day' : 'days').')' : '');
            } else {
                $priority = null;
                $issue = null;
            }

            $label = $total === 0 ? 'No entries'
                : ($pending > 0 ? 'Pending review'
                : ($revision > 0 ? 'Revision requested' : 'Up to date'));

            return [
                'student' => $student,
                'total' => $total,
                'pending' => $pending,
                'validated' => $validated,
                'revision' => $revision,
                'oldest_days' => $oldestDays,
                'last_at' => $newest ? Carbon::parse($newest) : null,
                'priority' => $priority,
                'main_issue' => $issue,
                'review_label' => $label,
            ];
        });

        $kpis = [
            'students' => $students->count(),
            'pending' => $rows->sum('pending'),
            'validated' => $rows->sum('validated'),
            'revision' => $rows->sum('revision'),
            'overdue' => $rows->where('priority', 'High')->count(),
            'students_waiting' => $rows->where('pending', '>', 0)->count(),
            'total_entries' => $rows->sum('total'),
        ];
        $kpis['reviewed_rate'] = $kpis['total_entries'] > 0
            ? (int) round(($kpis['validated'] / $kpis['total_entries']) * 100)
            : null;

        $order = ['High' => 3, 'Medium' => 2];
        $needsAttention = $rows->filter(fn ($r) => $r['priority'])
            ->sortByDesc(fn ($r) => [$order[$r['priority']], $r['pending']])
            ->values()
            ->take(6);

        // Entries this week (Mon-Sun): submitted vs already reviewed.
        $weekStart = $today->copy()->startOfWeek();
        $weekEntries = (clone $base)
            ->whereBetween('activity_date', [$weekStart->toDateString(), $weekStart->copy()->endOfWeek()->toDateString()])
            ->get(['activity_date', 'supervisor_status'])
            ->groupBy(fn ($l) => Carbon::parse($l->activity_date)->toDateString());

        $weekChart = collect(range(0, 6))->map(function ($i) use ($weekStart, $weekEntries, $today) {
            $day = $weekStart->copy()->addDays($i);
            $list = $weekEntries->get($day->toDateString(), collect());

            return [
                'label' => $day->format('D'),
                'date' => $day->format('d M'),
                'submitted' => $list->count(),
                'reviewed' => $list->filter(fn ($l) => ($l->supervisor_status ?? 'Pending') !== 'Pending')->count(),
                'is_today' => $day->isSameDay($today),
            ];
        });

        // Review progress for the last 4 weeks.
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

        $recent = (clone $base)
            ->with('student')
            ->orderByDesc('created_at')
            ->orderByDesc('logbook_id')
            ->take(10)
            ->get();

        $greeting = $today->hour < 12 ? 'Good morning' : ($today->hour < 17 ? 'Good afternoon' : 'Good evening');

        return view('supervisor.dashboard', [
            'supervisor' => $supervisor,
            'today' => $today,
            'greeting' => $greeting,
            'kpis' => $kpis,
            'needsAttention' => $needsAttention,
            'weekChart' => $weekChart,
            'trend' => $trend,
            'students' => $studentRows,
            'totalStudents' => $rows->count(),
            'recent' => $recent,
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