<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\Attendance;
use App\Models\Lecturer;
use App\Models\Logbook;
use App\Models\Student;
use App\Models\Supervisor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminDashboardService
{
    /** Entries waiting longer than this many days count as overdue. */
    private const OVERDUE_DAYS = 7;

    /** A student with no entry for this many days is flagged as inactive. */
    private const INACTIVE_DAYS = 7;

    public function dashboardData(): array
    {
        $today = now()->startOfDay();
        $localNow = now(Attendance::TIMEZONE);

        $hour = (int) $localNow->format('G');
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
        $adminName = optional(Auth::guard('admin')->user())->name ?? 'Administrator';
        $todayLabel = $localNow->format('l, j F Y');

        $students = Student::count();
        $lecturers = Lecturer::count();
        $supervisors = Supervisor::count();
        $logbooks = Logbook::count();

        $studentsWithSupervisor = Student::whereNotNull('supervisor_id')->count();
        $supervisorsWithStudents = Supervisor::has('students')->count();

        $entriesToday = Logbook::whereDate('created_at', today())->count();
        $entriesThisWeek = Logbook::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();

        $validated = Logbook::where('supervisor_status', Logbook::SUPERVISOR_STATUS_VALIDATED)->count();
        $revision = Logbook::where('supervisor_status', Logbook::SUPERVISOR_STATUS_REVISION_REQUESTED)->count();
        $pending = max(0, $logbooks - $validated - $revision);
        $validatedPercent = $logbooks > 0 ? (int) round($validated / $logbooks * 100) : 0;

        $awaitingReview = $this->pending(
            Logbook::query()->whereHas('student', fn ($query) => $query->whereNotNull('supervisor_id'))
        )->count();

        $overdueReviews = $this->pending(
            Logbook::query()->whereHas('student', fn ($query) => $query->whereNotNull('supervisor_id'))
        )->where('created_at', '<', now()->subDays(self::OVERDUE_DAYS))->count();

        $entriesWithoutReviewer = max(0, $pending - $awaitingReview);

        $months = collect(range(5, 0))->map(fn ($index) => now()->startOfMonth()->subMonths($index));

        $entriesByMonth = Logbook::where('created_at', '>=', $months->first())
            ->pluck('created_at')
            ->groupBy(fn ($date) => Carbon::parse($date)->format('Y-m'))
            ->map->count();

        $trendLabels = $months->map(fn ($month) => $month->format('M'))->values();
        $trendValues = $months->map(fn ($month) => (int) ($entriesByMonth[$month->format('Y-m')] ?? 0))->values();

        $weekRows = Logbook::where('created_at', '>=', now()->startOfWeek()->subWeeks(3))
            ->get(['created_at', 'supervisor_status']);

        $reviewProgress = collect(range(3, 0))->map(function ($index) use ($weekRows) {
            $start = now()->startOfWeek()->subWeeks($index);
            $end = $start->copy()->endOfWeek();

            $inWeek = $weekRows->filter(fn ($row) => $row->created_at >= $start && $row->created_at <= $end);
            $total = $inWeek->count();
            $reviewed = $inWeek->whereIn('supervisor_status', [
                Logbook::SUPERVISOR_STATUS_VALIDATED,
                Logbook::SUPERVISOR_STATUS_REVISION_REQUESTED,
            ])->count();

            return [
                'label' => $start->format('d M').' - '.$end->format('d M'),
                'total' => $total,
                'reviewed' => $reviewed,
                'waiting' => $total - $reviewed,
                'percent' => $total > 0 ? (int) round($reviewed / $total * 100) : 0,
            ];
        });

        $latestEntries = Logbook::with(['student.lecturer', 'student.supervisor'])
            ->latest('created_at')
            ->take(8)
            ->get();

        $lecturerWorkload = Lecturer::withCount('students')
            ->orderByDesc('students_count')
            ->orderBy('name')
            ->take(5)
            ->get();

        $waitingBySupervisor = $this->pending(
            DB::table('logbooks')
                ->join('students', 'students.student_id', '=', 'logbooks.student_id')
                ->whereNotNull('students.supervisor_id'),
            'logbooks.supervisor_status'
        )
            ->select('students.supervisor_id', DB::raw('COUNT(*) as waiting'))
            ->groupBy('students.supervisor_id')
            ->pluck('waiting', 'supervisor_id');

        $supervisorWorkload = Supervisor::withCount('students')
            ->orderByDesc('students_count')
            ->orderBy('name')
            ->take(5)
            ->get()
            ->each(fn ($supervisor) => $supervisor->waiting_count = (int) ($waitingBySupervisor[$supervisor->supervisor_id] ?? 0));

        $attention = [];

        if ($overdueReviews > 0) {
            $attention[] = [
                'tone' => 'rose',
                'icon' => 'fa-hourglass-half',
                'title' => 'Overdue supervisor reviews',
                'detail' => $overdueReviews.' '.str('entry')->plural($overdueReviews).' waiting more than '.self::OVERDUE_DAYS.' days for a supervisor.',
                'count' => $overdueReviews,
                'href' => '/admin/supervisors?state=waiting',
            ];
        }

        $inactive = Student::whereNotNull('internship_start')
            ->withMax('logbooks', 'created_at')
            ->get()
            ->filter(function ($student) use ($today) {
                if (! $student->internship_start) {
                    return false;
                }

                $start = $student->internship_start->copy()->startOfDay();
                $end = $start->copy()->addWeeks(max(1, (int) ($student->internship_weeks ?: 14)));

                if ($start->gt($today) || $end->lt($today)) {
                    return false;
                }

                $reference = $student->logbooks_max_created_at
                    ? Carbon::parse($student->logbooks_max_created_at)
                    : $start;

                return $reference->lt($today->copy()->subDays(self::INACTIVE_DAYS));
            })
            ->sortBy('name')
            ->values();

        if ($inactive->isNotEmpty()) {
            $attention[] = [
                'tone' => 'rose',
                'icon' => 'fa-user-clock',
                'title' => 'Students with no recent entry',
                'detail' => $this->nameList($inactive->pluck('name'), $inactive->count()).' - nothing logged for '.self::INACTIVE_DAYS.'+ days.',
                'count' => $inactive->count(),
                'href' => '/admin/students?status=in_progress&sort=start',
            ];
        }

        [$noSupervisorCount, $noSupervisorNames] = $this->summarise(Student::whereNull('supervisor_id'));

        if ($noSupervisorCount > 0) {
            $attention[] = [
                'tone' => 'amber',
                'icon' => 'fa-building-user',
                'title' => 'Students without a company supervisor',
                'detail' => $noSupervisorNames.($entriesWithoutReviewer > 0 ? ' - '.$entriesWithoutReviewer.' entries cannot be reviewed yet.' : '.'),
                'count' => $noSupervisorCount,
                'href' => '/admin/students?supervisor=none',
            ];
        }

        [$noLecturerCount, $noLecturerNames] = $this->summarise(Student::whereNull('lecturer_id'));

        if ($noLecturerCount > 0) {
            $attention[] = [
                'tone' => 'amber',
                'icon' => 'fa-user-tie',
                'title' => 'Students without a lecturer',
                'detail' => $noLecturerNames.'.',
                'count' => $noLecturerCount,
                'href' => '/admin/students?lecturer=none',
            ];
        }

        if (Schema::hasColumn('students', 'internship_start')) {
            [$noDatesCount, $noDatesNames] = $this->summarise(Student::whereNull('internship_start'));

            if ($noDatesCount > 0) {
                $attention[] = [
                    'tone' => 'amber',
                    'icon' => 'fa-calendar-xmark',
                    'title' => 'Internship dates not set',
                    'detail' => $noDatesNames.' - weeks and reminders will not work until dates are set.',
                    'count' => $noDatesCount,
                    'href' => '/admin/students?status=not_set',
                ];
            }
        }

        [$idleSupervisorCount, $idleSupervisorNames] = $this->summarise(Supervisor::doesntHave('students'));

        if ($idleSupervisorCount > 0) {
            $attention[] = [
                'tone' => 'blue',
                'icon' => 'fa-user-slash',
                'title' => 'Supervisors with no students',
                'detail' => $idleSupervisorNames.'.',
                'count' => $idleSupervisorCount,
                'href' => '/admin/supervisors?state=no_students',
            ];
        }

        [$idleLecturerCount, $idleLecturerNames] = $this->summarise(Lecturer::doesntHave('students'));

        if ($idleLecturerCount > 0) {
            $attention[] = [
                'tone' => 'blue',
                'icon' => 'fa-user-slash',
                'title' => 'Lecturers with no students',
                'detail' => $idleLecturerNames.'.',
                'count' => $idleLecturerCount,
                'href' => '/admin/lecturers?state=no_students',
            ];
        }

        try {
            DB::connection()->getPdo();
            $databaseStatus = 'Connected';
        } catch (\Throwable $e) {
            $databaseStatus = 'Unavailable';
        }

        $attachments = Schema::hasTable('attachments') ? Attachment::count() : 0;
        $laravelVersion = app()->version();
        $phpVersion = PHP_VERSION;

        return compact(
            'greeting',
            'adminName',
            'todayLabel',
            'students',
            'lecturers',
            'supervisors',
            'logbooks',
            'studentsWithSupervisor',
            'supervisorsWithStudents',
            'entriesToday',
            'entriesThisWeek',
            'validated',
            'revision',
            'pending',
            'validatedPercent',
            'awaitingReview',
            'overdueReviews',
            'trendLabels',
            'trendValues',
            'reviewProgress',
            'latestEntries',
            'lecturerWorkload',
            'supervisorWorkload',
            'attention',
            'databaseStatus',
            'attachments',
            'laravelVersion',
            'phpVersion'
        );
    }

    /**
     * @param EloquentBuilder<Logbook>|QueryBuilder $query
     * @return EloquentBuilder<Logbook>|QueryBuilder
     */
    private function pending(EloquentBuilder|QueryBuilder $query, string $column = 'supervisor_status'): EloquentBuilder|QueryBuilder
    {
        return $query->where(function ($where) use ($column) {
            $where->whereNull($column)->orWhere($column, Logbook::SUPERVISOR_STATUS_PENDING);
        });
    }

    /**
     * @return array{int, string}
     */
    /**
     * @return array{int, string}
     */
    private function summarise(EloquentBuilder|QueryBuilder $query): array
    {
        $count = (clone $query)->count();
        $names = $count > 0 ? (clone $query)->orderBy('name')->limit(3)->pluck('name') : collect();

        return [$count, $this->nameList($names, $count)];
    }

    private function nameList(iterable $names, int $total): string
    {
        $names = collect($names)->take(3)->values();
        $more = $total - $names->count();

        if ($names->isEmpty()) {
            return '';
        }

        $text = $names->implode(', ');

        return $more > 0 ? $text.' and '.$more.' more' : $text;
    }
}
