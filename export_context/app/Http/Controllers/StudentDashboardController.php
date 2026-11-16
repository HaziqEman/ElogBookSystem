<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Attendance;
use App\Models\Feedback;
use App\Models\Logbook;
use App\Models\Message;
use App\Models\Student;
use App\Models\Todo;
use App\Services\WeekPlanner;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StudentDashboardController extends Controller
{
    public function index(Request $request)
    {
        $student = Auth::guard('student')->user();
        $today = Carbon::now(Attendance::TIMEZONE);
        $todayKey = $today->toDateString();
        $planner = new WeekPlanner($student, $today);

        $totalLogs = Logbook::where('student_id', $student->student_id)->count();

        $totalAttachments = Attachment::whereHas('logbook', function ($query) use ($student) {
            $query->where('student_id', $student->student_id);
        })->count();

        $totalReviews = Feedback::whereHas('logbook', function ($query) use ($student) {
            $query->where('student_id', $student->student_id);
        })->count();

        $unreadMessages = Message::whereNull('read_at')
            ->where('sender_type', '!=', Student::class)
            ->where('deleted_for_student', false)
            ->whereHas('conversation', function ($query) use ($student) {
                $query->where('student_id', $student->student_id);
            })
            ->count();

        // The attendance and to-do tables come from SQL. Until then the dashboard still loads.
        $extrasReady = Schema::hasTable('attendances') && Schema::hasTable('todos');

        $attendance = null;
        $monthPresent = 0;
        $monthLeave = 0;
        $openTodos = collect();
        $doneTodos = collect();
        $openCount = 0;
        $doneCount = 0;
        $tasksSoon = 0;
        $overdueCount = 0;
        $upcomingCount = 0;

        if ($extrasReady) {
            $monthRecords = Attendance::where('student_id', $student->student_id)
                ->whereBetween('attendance_date', [
                    $today->copy()->startOfMonth()->toDateString(),
                    $today->copy()->endOfMonth()->toDateString(),
                ])
                ->get();

            $attendance = $monthRecords->first(fn ($record) => $record->attendance_date->toDateString() === $todayKey);
            $monthPresent = $monthRecords->where('status', 'Present')->count();
            $monthLeave = $monthRecords->where('status', 'Leave')->count();

            $openCount = Todo::where('student_id', $student->student_id)->where('is_done', false)->count();
            $doneCount = Todo::where('student_id', $student->student_id)->where('is_done', true)->count();

            $overdueCount = Todo::where('student_id', $student->student_id)
                ->where('is_done', false)
                ->whereNotNull('due_date')
                ->where('due_date', '<', $todayKey)
                ->count();
            $upcomingCount = max($openCount - $overdueCount, 0);

            $openTodos = Todo::with('supervisor')
                ->where('student_id', $student->student_id)
                ->where('is_done', false)
                ->orderByRaw('due_date IS NULL')
                ->orderBy('due_date')
                ->orderBy('todo_id')
                ->take(50)
                ->get();

            $doneTodos = Todo::with('supervisor')
                ->where('student_id', $student->student_id)
                ->where('is_done', true)
                ->orderByDesc('updated_at')
                ->take(10)
                ->get();

            $tasksSoon = Todo::where('student_id', $student->student_id)
                ->where('is_done', false)
                ->whereNotNull('due_date')
                ->where('due_date', '<=', $today->copy()->addDays(2)->toDateString())
                ->count();
        }

        // Reminder banner: only on a working day that is not MC, leave or a holiday.
        $currentWeek = $planner->currentWeek();
        $workingToday = $planner->isWorkingDay($today->copy()->startOfDay());

        if ($currentWeek !== null && ($currentWeek < 1 || $currentWeek > $planner->totalWeeks())) {
            $workingToday = false;
        }

        $dayOffToday = $attendance && $attendance->isDayOff();
        $remind = $extrasReady && $workingToday && ! $dayOffToday;
        $hasEntryToday = Logbook::where('student_id', $student->student_id)->where('activity_date', $todayKey)->exists();

        $banner = [
            'attendance' => $remind && ! ($attendance && $attendance->check_in),
            'logbook' => $remind && ! $hasEntryToday,
            'tasks' => $remind ? $tasksSoon : 0,
        ];
        $banner['show'] = $banner['attendance'] || $banner['logbook'] || $banner['tasks'] > 0;

        // Logbook by week.
        $weeks = [];
        $selected = null;

        if ($planner->hasStart()) {
            $from = $planner->firstMonday()->toDateString();
            $to = $planner->weekStart($planner->totalWeeks())->addDays(6)->toDateString();

            $logbooksByDate = Logbook::with('attachments')
                ->where('student_id', $student->student_id)
                ->whereBetween('activity_date', [$from, $to])
                ->orderBy('logbook_id')
                ->get()
                ->groupBy(fn ($log) => Carbon::parse($log->activity_date)->toDateString());

            $attendanceByDate = $extrasReady
                ? Attendance::where('student_id', $student->student_id)
                    ->whereBetween('attendance_date', [$from, $to])
                    ->get()
                    ->keyBy(fn ($record) => $record->attendance_date->toDateString())
                : collect();

            $weeks = $planner->weeks($logbooksByDate, $attendanceByDate);

            $default = max(1, min($planner->totalWeeks(), $currentWeek ?? 1));
            $number = max(1, min($planner->totalWeeks(), (int) $request->query('week', $default)));
            $selected = $weeks[$number - 1] ?? null;
        }

        $entryDates = Logbook::where('student_id', $student->student_id)
            ->pluck('activity_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->unique()
            ->values();

        // Small calendar for the current month (Monday first).
        $monthStart = $today->copy()->startOfMonth();
        $gridStart = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $monthStart->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $gridLogs = Logbook::where('student_id', $student->student_id)
            ->whereBetween('activity_date', [$gridStart->toDateString(), $gridEnd->toDateString()])
            ->orderBy('logbook_id')
            ->get(['logbook_id', 'activity_date', 'description'])
            ->groupBy(fn ($log) => Carbon::parse($log->activity_date)->toDateString());

        $gridTodos = collect();

        if ($extrasReady) {
            $gridTodos = Todo::with('supervisor')
                ->where('student_id', $student->student_id)
                ->where('is_done', false)
                ->whereBetween('due_date', [$gridStart->toDateString(), $gridEnd->toDateString()])
                ->orderBy('todo_id')
                ->get()
                ->groupBy(fn ($todo) => $todo->due_date->toDateString());
        }

        $cells = [];
        $agenda = [];

        for ($cursor = $gridStart->copy(); $cursor->lte($gridEnd); $cursor->addDay()) {
            $key = $cursor->toDateString();
            $inMonth = $cursor->month === $monthStart->month;

            $cells[] = [
                'key' => $key,
                'label' => $cursor->day,
                'long' => $cursor->format('l, d F'),
                'in_month' => $inMonth,
                'is_today' => $key === $todayKey,
                'has_log' => $gridLogs->has($key),
                'has_due' => $gridTodos->has($key),
            ];

            if (! $inMonth) {
                continue;
            }

            $items = [];

            // Today: what still needs to be done.
            if ($key === $todayKey && $remind) {
                if (! $hasEntryToday) {
                    $items[] = [
                        'type' => 'todo',
                        'title' => 'Logbook entry',
                        'meta' => 'Not written yet. Add what you worked on today.',
                        'action' => ['label' => 'Write entry', 'href' => '#new-entry', 'date' => $todayKey],
                    ];
                }

                if (! ($attendance && $attendance->check_in)) {
                    $items[] = [
                        'type' => 'todo',
                        'title' => 'Attendance',
                        'meta' => 'You have not clocked in yet.',
                        'action' => ['label' => 'Clock in', 'href' => '#attendance-card'],
                    ];
                }
            }

            foreach ($gridLogs->get($key, collect()) as $log) {
                $items[] = [
                    'type' => 'log',
                    'title' => 'Logbook entry',
                    'meta' => \Illuminate\Support\Str::limit((string) $log->description, 90) ?: 'Entry submitted',
                    'action' => null,
                ];
            }

            foreach ($gridTodos->get($key, collect()) as $todo) {
                $items[] = [
                    'type' => 'due',
                    'title' => $todo->title,
                    'meta' => 'Task due'.(! empty($todo->supervisor_id) ? ' · From '.(optional($todo->supervisor)->name ?? 'your supervisor') : ''),
                    'action' => null,
                ];
            }

            $agenda[$key] = $items;
        }

        $calendar = [
            'title' => $monthStart->format('F Y'),
            'prev' => $monthStart->copy()->subMonthNoOverflow()->format('Y-m'),
            'next' => $monthStart->copy()->addMonthNoOverflow()->format('Y-m'),
            'cells' => $cells,
            'agenda' => $agenda,
        ];

        // Upcoming deadlines: open tasks that are still ahead, plus the weekly logbook dates.
        $deadlineItems = collect();

        if ($extrasReady) {
            Todo::with('supervisor')
                ->where('student_id', $student->student_id)
                ->where('is_done', false)
                ->whereNotNull('due_date')
                ->where('due_date', '>=', $todayKey)
                ->orderBy('due_date')
                ->orderBy('todo_id')
                ->take(6)
                ->get()
                ->each(function ($todo) use ($deadlineItems) {
                    $deadlineItems->push([
                        'date' => $todo->due_date->copy(),
                        'title' => $todo->title,
                        'meta' => ! empty($todo->supervisor_id)
                            ? 'Assigned by '.(optional($todo->supervisor)->name ?? 'your supervisor')
                            : 'Your task',
                    ]);
                });
        }

        if ($currentWeek !== null && $currentWeek >= 1) {
            $thisWeek = $weeks[$currentWeek - 1] ?? null;

            if ($thisWeek && $thisWeek['last_working'] && $thisWeek['last_working']->toDateString() >= $todayKey) {
                $deadlineItems->push([
                    'date' => $thisWeek['last_working']->copy(),
                    'title' => 'Week '.$thisWeek['number'].' sent to supervisor',
                    'meta' => 'Automatic, after your last working day',
                ]);
            }
        }

        if ($currentWeek !== null && $currentWeek >= 0) {
            $nextWeek = $weeks[$currentWeek] ?? null;

            if ($nextWeek) {
                $deadlineItems->push([
                    'date' => $nextWeek['start']->copy(),
                    'title' => 'Week '.$nextWeek['number'].' logbook opens',
                    'meta' => 'You can write entries once it starts',
                ]);
            }
        }

        $deadlines = $deadlineItems
            ->sortBy(fn ($item) => $item['date']->toDateString())
            ->take(4)
            ->map(function ($item) use ($todayKey) {
                $item['days'] = max((int) Carbon::parse($todayKey)->diffInDays(Carbon::parse($item['date']->toDateString()), false), 0);

                return $item;
            })
            ->values()
            ->all();

        // Daily AI draft allowance.
        $aiLimit = max(1, (int) config('aihelp.daily_limit', 10));
        $aiReady = Schema::hasTable('ai_help_usages');
        $aiUsed = $aiReady
            ? (int) DB::table('ai_help_usages')
                ->where('student_id', $student->student_id)
                ->where('used_on', $todayKey)
                ->value('used_count')
            : 0;
        $aiLeft = max($aiLimit - $aiUsed, 0);

        return view('student.dashboard', compact(
            'student',
            'today',
            'planner',
            'currentWeek',
            'weeks',
            'selected',
            'banner',
            'entryDates',
            'totalLogs',
            'totalAttachments',
            'totalReviews',
            'unreadMessages',
            'extrasReady',
            'attendance',
            'monthPresent',
            'monthLeave',
            'openTodos',
            'doneTodos',
            'openCount',
            'doneCount',
            'overdueCount',
            'upcomingCount',
            'deadlines',
            'calendar',
            'aiReady',
            'aiLimit',
            'aiLeft'
        ));
    }

    /** Every entry, newest first (the "Open full logbook" page). */
    public function logbooks()
    {
        $student = Auth::guard('student')->user();

        $logbooks = Logbook::with('attachments')
            ->where('student_id', $student->student_id)
            ->orderByDesc('activity_date')
            ->orderByDesc('logbook_id')
            ->paginate(15);

        return view('student.logbook-all', compact('student', 'logbooks'));
    }
}
