<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\Attendance;
use App\Models\Feedback;
use App\Models\Logbook;
use App\Models\Message;
use App\Models\Student;
use App\Models\Todo;
use App\Models\University;
use App\Services\WeekPlanner;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class StudentDashboardController extends Controller
{
    private const LOGBOOKS_PER_PAGE = 20;

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
            $monthPresent = $monthRecords->where('status', Attendance::STATUS_PRESENT)->count();
            $monthLeave = $monthRecords->where('status', Attendance::STATUS_LEAVE)->count();

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
                    'meta' => Str::limit((string) $log->description, 90) ?: 'Entry submitted',
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
        $universities = $this->visibleUniversitiesFor($student);

        return view('student.dashboard', compact(
            'student',
            'universities',
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

    /** Full weekly logbook review, including the entry feedback history. */
    public function logbooks(Request $request)
    {
        $student = Auth::guard('student')->user();
        $universities = $this->visibleUniversitiesFor($student);
        $today = Carbon::now(Attendance::TIMEZONE)->startOfDay();
        $planner = new WeekPlanner($student, $today);
        $todayKey = $today->toDateString();
        $search = trim((string) $request->query('search', ''));
        $review = in_array($request->query('review'), ['action', 'pending', 'validated'], true)
            ? $request->query('review')
            : 'all';

        $hasFilters = $search !== '' || $review !== 'all';
        $countRow = DB::table('logbooks')
            ->where('student_id', $student->student_id)
            ->selectRaw('COUNT(*) as all_count')
            ->selectRaw(
                'SUM(CASE WHEN supervisor_status = ? THEN 1 ELSE 0 END) as action_count',
                [Logbook::SUPERVISOR_STATUS_REVISION_REQUESTED]
            )
            ->selectRaw(
                'SUM(CASE WHEN supervisor_status = ? OR supervisor_status IS NULL THEN 1 ELSE 0 END) as pending_count',
                [Logbook::SUPERVISOR_STATUS_PENDING]
            )
            ->selectRaw(
                'SUM(CASE WHEN supervisor_status = ? THEN 1 ELSE 0 END) as validated_count',
                [Logbook::SUPERVISOR_STATUS_VALIDATED]
            )
            ->first();
        $counts = [
            'all' => (int) ($countRow->all_count ?? 0),
            'action' => (int) ($countRow->action_count ?? 0),
            'pending' => (int) ($countRow->pending_count ?? 0),
            'validated' => (int) ($countRow->validated_count ?? 0),
        ];

        if ($search !== '' && preg_match('/[^\x00-\x7F]/', $search) === 1) {
            $matchesSearch = function (Logbook $logbook) use ($search): bool {
                $values = collect([
                    $logbook->description,
                    $logbook->supervisor_status,
                ])->concat($logbook->attachments->pluck('file_name'));

                foreach ($logbook->feedbacks as $feedback) {
                    $values->push($feedback->comment);
                    $values->push(optional($feedback->lecturer)->name);
                    $values->push(optional($feedback->supervisor)->name);
                }

                return $values->contains(
                    fn ($value) => $value !== null && mb_stripos((string) $value, $search) !== false
                );
            };
            $reviewMatches = fn (Logbook $logbook): bool => match ($review) {
                'action' => $logbook->supervisor_status === Logbook::SUPERVISOR_STATUS_REVISION_REQUESTED,
                'pending' => ($logbook->supervisor_status ?? Logbook::SUPERVISOR_STATUS_PENDING) === Logbook::SUPERVISOR_STATUS_PENDING,
                'validated' => $logbook->supervisor_status === Logbook::SUPERVISOR_STATUS_VALIDATED,
                default => true,
            };
            $matches = Logbook::with([
                'attachments',
                'feedbacks.lecturer',
                'feedbacks.supervisor',
            ])
                ->where('student_id', $student->student_id)
                ->get()
                ->filter(fn (Logbook $logbook): bool => $matchesSearch($logbook) && $reviewMatches($logbook))
                ->sortByDesc('activity_date')
                ->sortByDesc('logbook_id')
                ->values();
            $currentPage = LengthAwarePaginator::resolveCurrentPage();

            $visibleLogbooks = new LengthAwarePaginator(
                $matches->forPage($currentPage, self::LOGBOOKS_PER_PAGE)->values(),
                $matches->count(),
                self::LOGBOOKS_PER_PAGE,
                $currentPage,
                [
                    'path' => LengthAwarePaginator::resolveCurrentPath(),
                    'query' => $request->query(),
                ]
            );
            $visibleLogbooks->withQueryString();
        } else {
            $logbooksQuery = Logbook::query()->where('student_id', $student->student_id);

            if ($search !== '') {
                $searchPattern = '%'.str_replace(
                    ['!', '%', '_'],
                    ['!!', '!%', '!_'],
                    mb_strtolower($search)
                ).'%';

                $logbooksQuery->where(function (Builder $query) use ($searchPattern): void {
                    $query->whereRaw("LOWER(description) LIKE ? ESCAPE '!'", [$searchPattern])
                        ->orWhereRaw("LOWER(supervisor_status) LIKE ? ESCAPE '!'", [$searchPattern])
                        ->orWhereHas('attachments', fn (Builder $attachments) => $attachments
                            ->whereRaw("LOWER(file_name) LIKE ? ESCAPE '!'", [$searchPattern]))
                        ->orWhereHas('feedbacks', function (Builder $feedbacks) use ($searchPattern): void {
                            $feedbacks->whereRaw("LOWER(comment) LIKE ? ESCAPE '!'", [$searchPattern])
                                ->orWhereHas('lecturer', fn (Builder $lecturers) => $lecturers
                                    ->whereRaw("LOWER(name) LIKE ? ESCAPE '!'", [$searchPattern]))
                                ->orWhereHas('supervisor', fn (Builder $supervisors) => $supervisors
                                    ->whereRaw("LOWER(name) LIKE ? ESCAPE '!'", [$searchPattern]));
                        });
                });
            }

            if ($review === 'action') {
                $logbooksQuery->where(
                    'supervisor_status',
                    Logbook::SUPERVISOR_STATUS_REVISION_REQUESTED
                );
            } elseif ($review === 'pending') {
                $logbooksQuery->where(function (Builder $query): void {
                    $query->whereNull('supervisor_status')
                        ->orWhere('supervisor_status', Logbook::SUPERVISOR_STATUS_PENDING);
                });
            } elseif ($review === 'validated') {
                $logbooksQuery->where('supervisor_status', Logbook::SUPERVISOR_STATUS_VALIDATED);
            }

            $visibleLogbooks = $logbooksQuery
                ->when($hasFilters, fn (Builder $query) => $query->with([
                    'attachments',
                    'feedbacks.lecturer',
                    'feedbacks.supervisor',
                ]))
                ->orderByDesc('logbook_id')
                ->paginate(self::LOGBOOKS_PER_PAGE)
                ->withQueryString();
        }

        $entryDates = Logbook::where('student_id', $student->student_id)
            ->whereNotNull('activity_date')
            ->distinct()
            ->orderBy('activity_date')
            ->pluck('activity_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->values();

        $weeks = [];
        $selected = null;

        if ($planner->hasStart()) {
            $from = $planner->firstMonday()->toDateString();
            $to = $planner->weekStart($planner->totalWeeks())->addDays(6)->toDateString();
            $logbooks = Logbook::where('student_id', $student->student_id)
                ->orderBy('activity_date')
                ->orderBy('logbook_id')
                ->get(['logbook_id', 'activity_date', 'supervisor_status']);
            $logbooksByDate = $logbooks
                ->filter(fn (Logbook $logbook): bool => (bool) $logbook->activity_date)
                ->groupBy(fn ($logbook) => Carbon::parse($logbook->activity_date)->toDateString());
            $attendanceByDate = Schema::hasTable('attendances')
                ? Attendance::where('student_id', $student->student_id)
                    ->whereBetween('attendance_date', [$from, $to])
                    ->get()
                    ->keyBy(fn ($record) => $record->attendance_date->toDateString())
                : collect();

            $weeks = $planner->weeks($logbooksByDate, $attendanceByDate);
            $default = max(1, min($planner->totalWeeks(), $planner->currentWeek() ?? 1));
            $weekNumber = max(1, min($planner->totalWeeks(), (int) $request->query('week', $default)));
            $selected = $weeks[$weekNumber - 1] ?? null;

            if ($selected !== null) {
                $selectedIds = collect($selected['days'])
                    ->flatMap(fn (array $day) => $day['entries']->pluck('logbook_id'))
                    ->unique()
                    ->values();

                if ($selectedIds->isNotEmpty()) {
                    $entriesById = Logbook::with([
                        'attachments',
                        'feedbacks.lecturer',
                        'feedbacks.supervisor',
                    ])
                        ->whereIn('logbook_id', $selectedIds)
                        ->get()
                        ->keyBy('logbook_id');

                    foreach ($selected['days'] as $index => $day) {
                        $entries = $day['entries']
                            ->map(fn (Logbook $entry) => $entriesById->get($entry->logbook_id))
                            ->filter()
                            ->values();
                        $selected['days'][$index]['entries'] = $entries;
                        $selected['days'][$index]['entry'] = $entries->last();
                    }

                    $weeks[$weekNumber - 1] = $selected;
                }
            }
        }

        $supervisorAssigned = (bool) $student->supervisor_id;
        $currentWeek = $planner->currentWeek() ?? 1;
        $firstMondayKey = $planner->hasStart() ? $planner->firstMonday()->toDateString() : '';
        $startKey = $planner->hasStart() ? $planner->start()->toDateString() : '';
        $workingDaysCsv = implode(',', $planner->workingDays());
        $unplannedLogbooks = null;

        if (! $hasFilters) {
            $unplannedQuery = Logbook::with([
                'attachments',
                'feedbacks.lecturer',
                'feedbacks.supervisor',
            ])->where('student_id', $student->student_id);
            $internshipStart = $planner->start();

            if ($internshipStart !== null) {
                $lastWeekEnd = $planner->weekStart($planner->totalWeeks())
                    ->addDays(6)
                    ->toDateString();

                $unplannedQuery->where(function (Builder $query) use ($internshipStart, $lastWeekEnd): void {
                    $query->whereNull('activity_date')
                        ->orWhereDate('activity_date', '<', $internshipStart->toDateString())
                        ->orWhereDate('activity_date', '>', $lastWeekEnd);
                });
            }

            $unplannedLogbooks = $unplannedQuery
                ->orderByDesc('logbook_id')
                ->paginate(self::LOGBOOKS_PER_PAGE, ['*'], 'archive_page')
                ->withQueryString();
        }

        return view('student.logbooks.index', compact(
            'student',
            'universities',
            'today',
            'todayKey',
            'planner',
            'currentWeek',
            'weeks',
            'selected',
            'search',
            'review',
            'counts',
            'hasFilters',
            'visibleLogbooks',
            'unplannedLogbooks',
            'supervisorAssigned',
            'firstMondayKey',
            'startKey',
            'workingDaysCsv',
            'entryDates'
        ));
    }

    /** @return Collection<int, University> */
    private function visibleUniversitiesFor(Student $student): Collection
    {
        return University::visibleTo($student->student_id)
            ->orderBy('name')
            ->get();
    }
}
