<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksUniqueEmail;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\Supervisor;
use App\Services\WeekPlanner;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AdminStudentController extends Controller
{
    use ChecksUniqueEmail;

    private const PER_PAGE = [10, 25, 50, 100];

    // ------------------------------------------------------------------
    // List
    // ------------------------------------------------------------------

    public function index(Request $request)
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

        // Quick-filter chips with live counts.
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

        $filters = collect($request->only(['q', 'course', 'lecturer', 'supervisor', 'status']))->filter(fn ($v) => $v !== null && $v !== '');
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

        return view('admin.students.index', compact(
            'students',
            'chips',
            'activeChip',
            'courses',
            'lecturers',
            'supervisors',
            'perPage'
        ));
    }

    // ------------------------------------------------------------------
    // Bulk actions
    // ------------------------------------------------------------------

    public function bulk(Request $request)
    {
        $request->validate([
            'action' => 'required|in:set_internship,shift_start,assign_lecturer,assign_supervisor,delete',
        ]);

        // "Apply to all matching" re-uses the filters that came along in the query string.
        $ids = $request->boolean('apply_to_all')
            ? $this->filteredQuery($request)->pluck('student_id')
            : collect($request->input('ids', []))->map(fn ($id) => (int) $id)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return back()->with('error', 'No students were selected.');
        }

        $count = $ids->count();
        $noun = $count === 1 ? 'student' : 'students';

        switch ($request->input('action')) {
            case 'set_internship':
                return back()->with($this->setInternship($request, $ids));

            case 'shift_start':
                $data = $request->validate([
                    'shift_amount' => 'required|integer|not_in:0|between:-365,365',
                    'shift_unit' => 'required|in:days,weeks',
                ]);

                $days = (int) $data['shift_amount'] * ($data['shift_unit'] === 'weeks' ? 7 : 1);
                $moved = 0;

                Student::whereIn('student_id', $ids)->whereNotNull('internship_start')->get()
                    ->each(function ($student) use ($days, &$moved) {
                        $student->forceFill(['internship_start' => $student->internship_start->copy()->addDays($days)->toDateString()])->save();
                        $moved++;
                    });

                $skipped = $count - $moved;
                $direction = $days > 0 ? 'later' : 'earlier';

                return back()->with('success', 'Moved the start date of '.$moved.' '.str('student')->plural($moved).' '.abs($days).' '.str('day')->plural(abs($days)).' '.$direction.'.'
                    .($skipped > 0 ? ' '.$skipped.' skipped because no start date was set.' : ''));

            case 'assign_lecturer':
                $data = $request->validate(['lecturer_id' => 'required|integer|exists:lecturers,lecturer_id']);
                Student::whereIn('student_id', $ids)->update(['lecturer_id' => $data['lecturer_id']]);

                return back()->with('success', 'Assigned '.optional(Lecturer::find($data['lecturer_id']))->name.' to '.$count.' '.$noun.'.');

            case 'assign_supervisor':
                $data = $request->validate(['supervisor_id' => 'required']);
                $supervisorId = $data['supervisor_id'] === 'none' ? null : (int) $data['supervisor_id'];

                if ($supervisorId !== null && ! Supervisor::whereKey($supervisorId)->exists()) {
                    return back()->with('error', 'That company supervisor does not exist.');
                }

                Student::whereIn('student_id', $ids)->update(['supervisor_id' => $supervisorId]);

                return back()->with('success', $supervisorId
                    ? 'Assigned '.optional(Supervisor::find($supervisorId))->name.' to '.$count.' '.$noun.'.'
                    : 'Removed the company supervisor from '.$count.' '.$noun.'.');

            case 'delete':
                Student::whereIn('student_id', $ids)->get()->each->delete();

                return redirect('/admin/students')->with('success', 'Deleted '.$count.' '.$noun.' and their logbook entries.');
        }

        return back();
    }

    /** Blank fields are left unchanged, so one form can change just the start date, just the weeks, or both. */
    protected function setInternship(Request $request, $ids): array
    {
        if (! Schema::hasColumn('students', 'internship_start')) {
            return ['error' => 'The internship date columns do not exist yet. Run the database update first.'];
        }

        $data = $request->validate([
            'start_date' => 'nullable|date|after_or_equal:2020-01-01|before_or_equal:2100-12-31',
            'weeks' => 'nullable|integer|between:1,52',
            'working_days' => 'nullable|array',
            'working_days.*' => 'integer|between:1,7',
        ]);

        $updates = [];
        $changes = [];

        if (filled($data['start_date'] ?? null)) {
            $updates['internship_start'] = $data['start_date'];
            $changes[] = 'start date '.Carbon::parse($data['start_date'])->format('d M Y');
        }

        if (filled($data['weeks'] ?? null)) {
            $updates['internship_weeks'] = (int) $data['weeks'];
            $changes[] = $data['weeks'].' weeks';
        }

        $days = collect($data['working_days'] ?? [])->map(fn ($d) => (int) $d)->unique()->sort()->values()->all();

        if (! $updates && ! $days) {
            return ['error' => 'Pick a start date, a duration or working days to apply.'];
        }

        if ($updates) {
            Student::whereIn('student_id', $ids)->update($updates);
        }

        if ($days) {
            $dayString = implode(',', $days);
            $hasSchedule = Schema::hasColumn('students', 'working_schedule');

            Student::whereIn('student_id', $ids)->get()->each(function ($student) use ($dayString, $hasSchedule) {
                $payload = ['working_days' => $dayString];

                if ($hasSchedule && $dayString !== implode(',', WeekPlanner::parseDays($student->working_days))) {
                    $payload['working_schedule'] = json_encode([['from' => '1970-01-01', 'days' => $dayString]]);
                }

                $student->forceFill($payload)->save();
            });

            $changes[] = 'working days '.implode(', ', array_map(fn ($d) => WeekPlanner::DAY_NAMES[$d], $days));
        }

        $count = $ids->count();

        return ['success' => 'Updated '.$count.' '.str('student')->plural($count).': '.implode(', ', $changes).'.'];
    }

    // ------------------------------------------------------------------
    // Export + password reset
    // ------------------------------------------------------------------

    public function export(Request $request)
    {
        $query = $this->filteredQuery($request)
            ->with(['lecturer', 'supervisor'])
            ->withCount('logbooks')
            ->orderBy('name');

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // so Excel reads UTF-8 correctly
            fputcsv($out, ['Matric', 'Name', 'Email', 'Phone', 'Course', 'Lecturer', 'Company supervisor', 'Company', 'Internship start', 'Weeks', 'Working days', 'Entries']);

            $query->chunkById(200, function ($students) use ($out) {
                foreach ($students as $student) {
                    fputcsv($out, [
                        $student->matric_no,
                        $student->name,
                        $student->email,
                        $student->phone_no,
                        $student->course,
                        optional($student->lecturer)->name,
                        optional($student->supervisor)->name,
                        optional($student->supervisor)->company_name,
                        $student->internship_start ? $student->internship_start->format('Y-m-d') : '',
                        $student->internship_start ? ($student->internship_weeks ?: 14) : '',
                        $student->internship_start ? implode(' ', array_map(fn ($d) => WeekPlanner::DAY_NAMES[$d], WeekPlanner::parseDays($student->working_days))) : '',
                        $student->logbooks_count,
                    ]);
                }
            }, 'student_id');

            fclose($out);
        }, 'students-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function resetPassword($id)
    {
        $student = Student::findOrFail($id);
        $temporary = Str::random(10);

        $student->forceFill(['password' => Hash::make($temporary)])->save();

        return back()->with([
            'success' => 'Password reset for '.$student->name.'.',
            'temp_password' => $temporary,
            'temp_for' => $student->name,
        ]);
    }

    // ------------------------------------------------------------------
    // Create / edit / delete (same behaviour as before)
    // ------------------------------------------------------------------

    public function create()
    {
        $lecturers = Lecturer::orderBy('name')->get();
        $supervisors = Supervisor::orderBy('name')->get();

        return view('admin.students.create', compact('lecturers', 'supervisors'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(array_merge([
            'matric_no' => 'required|string|max:255|unique:students,matric_no',
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', $this->uniqueEmailRule()],
            'password' => 'required|string|min:6',
            'phone_no' => 'nullable|string|max:50',
            'course' => 'nullable|string|max:255',
            'lecturer_id' => 'required|integer|exists:lecturers,lecturer_id',
            'supervisor_id' => 'nullable|integer|exists:supervisors,supervisor_id',
        ], $this->internshipRules()));

        $ready = Schema::hasColumn('students', 'internship_start');

        if (! $ready && filled($data['internship_start'] ?? null)) {
            return back()->withInput()->with('error', 'The internship date columns do not exist yet. Run the database update first.');
        }

        $payload = [
            'matric_no' => trim($data['matric_no']),
            'name' => trim($data['name']),
            'email' => trim($data['email']),
            'password' => Hash::make($data['password']),
            'phone_no' => $data['phone_no'] ?? 'N/A',
            'course' => $data['course'] ?? 'Unknown',
            'lecturer_id' => $data['lecturer_id'],
            'supervisor_id' => $data['supervisor_id'] ?? null,
        ];

        if ($ready) {
            $payload += $this->internshipValues($data);
        }

        // The schedule needs working_days, so it is built after the internship values above.
        if (isset($payload['working_days']) && Schema::hasColumn('students', 'working_schedule')) {
            $payload['working_schedule'] = json_encode([['from' => '1970-01-01', 'days' => $payload['working_days']]]);
        }

        // forceFill writes every field, even if the model's fillable list is out of date.
        $student = new Student();
        $student->forceFill($payload)->save();

        return redirect('/admin/students')->with('success', 'Student created.'.$this->datesMessage($payload));
    }

    public function edit($id)
    {
        $student = Student::findOrFail($id);
        $lecturers = Lecturer::orderBy('name')->get();
        $supervisors = Supervisor::orderBy('name')->get();

        return view('admin.students.edit', compact('student', 'lecturers', 'supervisors'));
    }

    public function update(Request $request, $id)
    {
        $student = Student::findOrFail($id);

        $data = $request->validate(array_merge([
            'name' => 'required|string|max:255',
            'phone_no' => 'nullable|string|max:50',
            'course' => 'nullable|string|max:255',
            'lecturer_id' => 'required|integer|exists:lecturers,lecturer_id',
            'supervisor_id' => 'nullable|integer|exists:supervisors,supervisor_id',
        ], $this->internshipRules()));

        $ready = Schema::hasColumn('students', 'internship_start');

        if (! $ready && filled($data['internship_start'] ?? null)) {
            return back()->withInput()->with('error', 'The internship date columns do not exist yet. Run the database update first.');
        }

        $payload = [
            'name' => trim($data['name']),
            'phone_no' => $data['phone_no'] ?? $student->phone_no,
            'course' => $data['course'] ?? $student->course,
            'lecturer_id' => $data['lecturer_id'],
            'supervisor_id' => $data['supervisor_id'] ?? null,
        ];

        if ($ready) {
            $payload += $this->internshipValues($data);
        }

        if (array_key_exists('working_days', $payload)) {
            $sameDays = $payload['working_days'] === implode(',', WeekPlanner::parseDays($student->working_days));

            if (! $sameDays && Schema::hasColumn('students', 'working_schedule')) {
                $payload['working_schedule'] = json_encode([['from' => '1970-01-01', 'days' => $payload['working_days']]]);
            }
        }

        $student->forceFill($payload)->save();

        return redirect('/admin/students')->with('success', 'Student updated.'.$this->datesMessage($payload));
    }

    public function destroy($id)
    {
        Student::findOrFail($id)->delete();

        return back()->with('success', 'Student deleted.');
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /** Applies the search box and dropdown filters from the query string. */
    protected function filteredQuery(Request $request)
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
            // Worked out in PHP so it behaves the same on MySQL (local) and PostgreSQL (Neon).
            $query->whereIn('student_id', $this->idsForState($status));
        }

        return $query;
    }

    protected function applyAssignmentFilter($query, string $column, $value): void
    {
        if ($value === 'none') {
            $query->whereNull($column);
        } elseif ($value !== null && $value !== '' && ctype_digit((string) $value)) {
            $query->where($column, (int) $value);
        }
    }

    protected function idsForState(string $state)
    {
        return Student::whereNotNull('internship_start')
            ->get(['student_id', 'internship_start', 'internship_weeks'])
            ->filter(fn ($student) => $this->stateOf($student)['key'] === $state)
            ->pluck('student_id');
    }

    /** Where a student is in their internship: not_set, not_started, in_progress or finished. */
    protected function stateOf(Student $student): array
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

    protected function internshipRules(): array
    {
        return [
            'internship_start' => 'nullable|date|after_or_equal:2020-01-01|before_or_equal:2100-12-31',
            'internship_weeks' => 'nullable|integer|between:1,52',
            'working_days' => 'nullable|array',
            'working_days.*' => 'integer|between:1,7',
        ];
    }

    /** No working day ticked means the usual Monday to Friday. */
    protected function internshipValues(array $data): array
    {
        $days = collect($data['working_days'] ?? [])
            ->map(fn ($day) => (int) $day)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return [
            'internship_start' => $data['internship_start'] ?? null,
            'internship_weeks' => (int) ($data['internship_weeks'] ?? 14) ?: 14,
            'working_days' => implode(',', $days ?: WeekPlanner::DEFAULT_DAYS),
        ];
    }

    /** Shows what was saved, so the administrator can see it worked. */
    protected function datesMessage(array $payload): string
    {
        if (empty($payload['internship_start'])) {
            return ' No internship start date is set yet.';
        }

        return ' Internship starts '.Carbon::parse($payload['internship_start'])->format('d M Y')
            .' for '.$payload['internship_weeks'].' weeks.';
    }
}
