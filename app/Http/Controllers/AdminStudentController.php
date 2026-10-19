<?php

namespace App\Http\Controllers;

use App\Models\Lecturer;
use App\Models\Student;
use App\Models\Supervisor;
use App\Services\WeekPlanner;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class AdminStudentController extends Controller
{
    public function index()
    {
        $students = Student::with(['lecturer', 'supervisor'])->paginate(10);

        return view('admin.students.index', compact('students'));
    }

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

        $student->forceFill($payload)->save();

        return redirect('/admin/students')->with('success', 'Student updated.'.$this->datesMessage($payload));
    }

    public function destroy($id)
    {
        Student::findOrFail($id)->delete();

        return back();
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

    /**
     * Login stops at the first matching table, so one email must never exist in two role tables.
     */
    protected function uniqueEmailRule()
    {
        return function (string $attribute, mixed $value, \Closure $fail) {
            $email = strtolower(trim((string) $value));

            foreach (['students', 'lecturers', 'admins', 'supervisors'] as $table) {
                if (Schema::hasTable($table) && DB::table($table)->whereRaw('lower(email) = ?', [$email])->exists()) {
                    $fail('This email is already used by another account.');

                    return;
                }
            }
        };
    }
}