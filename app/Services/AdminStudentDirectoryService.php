<?php

namespace App\Services;

use App\Models\Lecturer;
use App\Models\Student;
use App\Models\Supervisor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AdminStudentDirectoryService
{
    private const PER_PAGE = [10, 25, 50, 100];

    public function indexData(Request $request): array
    {
        $perPage = (int) $request->query('per_page');
        $perPage = in_array($perPage, self::PER_PAGE, true) ? $perPage : 10;

        $query = $this->filteredQuery($request)
            ->with(['lecturer', 'supervisor'])
            ->withCount('logbooks')
            ->withMax('logbooks', 'created_at');

        match ($request->query('sort')) {
            'matric' => $query->orderBy('matric_no'),
            'newest' => $query->orderByDesc('student_id'),
            'start' => $query->orderByRaw('internship_start is null')->orderBy('internship_start'),
            default => $query->orderBy('name'),
        };

        $students = $query->paginate($perPage)->withQueryString();

        $students->getCollection()->each(function ($student) {
            $student->setAttribute('internship_state', $this->stateOf($student));
        });

        $stateCounts = Student::whereNotNull('internship_start')
            ->get(['student_id', 'internship_start', 'internship_weeks'])
            ->groupBy(fn ($student) => $this->stateOf($student)['key'])
            ->map->count();

        $chips = [
            ['key' => 'all', 'label' => 'All', 'count' => Student::count(), 'query' => [], 'tone' => 'slate'],
            ['key' => 'in_progress', 'label' => 'In progress', 'count' => $stateCounts['in_progress'] ?? 0, 'query' => ['status' => 'in_progress'], 'tone' => 'emerald'],
            ['key' => 'not_started', 'label' => 'Not started', 'count' => $stateCounts['not_started'] ?? 0, 'query' => ['status' => 'not_started'], 'tone' => 'sky'],
            ['key' => 'finished', 'label' => 'Finished', 'count' => $stateCounts['finished'] ?? 0, 'query' => ['status' => 'finished'], 'tone' => 'slate'],
            ['key' => 'not_set', 'label' => 'Dates not set', 'count' => Student::whereNull('internship_start')->count(), 'query' => ['status' => 'not_set'], 'tone' => 'amber'],
            ['key' => 'no_supervisor', 'label' => 'No supervisor', 'count' => Student::whereNull('supervisor_id')->count(), 'query' => ['supervisor' => 'none'], 'tone' => 'amber'],
            ['key' => 'no_lecturer', 'label' => 'No lecturer', 'count' => Student::whereNull('lecturer_id')->count(), 'query' => ['lecturer' => 'none'], 'tone' => 'amber'],
        ];

        $filters = collect($request->only(['q', 'course', 'lecturer', 'supervisor', 'status']))
            ->filter(fn ($value) => $value !== null && $value !== '');
        $activeChip = 'custom';

        if ($filters->isEmpty()) {
            $activeChip = 'all';
        } elseif ($filters->count() === 1) {
            foreach ($chips as $chip) {
                if ($chip['query'] && $request->query(array_key_first($chip['query'])) === array_values($chip['query'])[0]) {
                    $activeChip = $chip['key'];
                }
            }
        }

        $courses = Student::whereNotNull('course')->distinct()->orderBy('course')->pluck('course');
        $lecturers = Lecturer::orderBy('name')->get();
        $supervisors = Supervisor::orderBy('name')->get();

        return compact('students', 'chips', 'activeChip', 'courses', 'lecturers', 'supervisors', 'perPage');
    }

    public function filteredQuery(Request $request): Builder
    {
        $query = Student::query();

        $term = trim((string) $request->query('q'));
        if ($term !== '') {
            $like = '%'.mb_strtolower($term).'%';
            $query->where(function ($where) use ($like) {
                $where->whereRaw('lower(name) like ?', [$like])
                    ->orWhereRaw('lower(matric_no) like ?', [$like])
                    ->orWhereRaw('lower(email) like ?', [$like]);
            });
        }

        if ($course = $request->query('course')) {
            $query->where('course', $course);
        }

        $this->applyAssignmentFilter($query, 'lecturer_id', $request->query('lecturer'));
        $this->applyAssignmentFilter($query, 'supervisor_id', $request->query('supervisor'));

        $status = $request->query('status');
        if ($status === 'not_set') {
            $query->whereNull('internship_start');
        } elseif (in_array($status, ['not_started', 'in_progress', 'finished'], true)) {
            $query->whereIn('student_id', $this->idsForState($status));
        }

        return $query;
    }

    private function applyAssignmentFilter(Builder $query, string $column, mixed $value): void
    {
        if ($value === 'none') {
            $query->whereNull($column);
        } elseif ($value !== null && $value !== '' && ctype_digit((string) $value)) {
            $query->where($column, (int) $value);
        }
    }

    private function idsForState(string $state): Collection
    {
        return Student::whereNotNull('internship_start')
            ->get(['student_id', 'internship_start', 'internship_weeks'])
            ->filter(fn ($student) => $this->stateOf($student)['key'] === $state)
            ->pluck('student_id');
    }

    /** @return array{key: string, label: string, detail: string|null} */
    private function stateOf(Student $student): array
    {
        if (! $student->internship_start) {
            return ['key' => 'not_set', 'label' => 'Not set', 'detail' => null];
        }

        $today = now()->startOfDay();
        $start = $student->internship_start->copy()->startOfDay();
        $weeks = max(1, (int) ($student->internship_weeks ?: 14));
        $end = $start->copy()->addWeeks($weeks)->subDay();

        if ($start->gt($today)) {
            $days = (int) $today->diffInDays($start);

            return ['key' => 'not_started', 'label' => 'Starts in '.$days.' '.str('day')->plural($days), 'detail' => null];
        }

        if ($end->lt($today)) {
            return ['key' => 'finished', 'label' => 'Finished', 'detail' => 'Ended '.$end->format('d M Y')];
        }

        $week = min($weeks, intdiv((int) $start->diffInDays($today), 7) + 1);

        return ['key' => 'in_progress', 'label' => 'Week '.$week.' of '.$weeks, 'detail' => null];
    }
}
