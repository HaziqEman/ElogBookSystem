<?php

namespace App\Http\Controllers;

use App\Models\Lecturer;
use App\Models\Student;
use App\Models\Supervisor;
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
        $data = $request->validate([
            'matric_no' => 'required|string|max:255|unique:students,matric_no',
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', $this->uniqueEmailRule()],
            'password' => 'required|string|min:6',
            'phone_no' => 'nullable|string|max:50',
            'course' => 'nullable|string|max:255',
            'lecturer_id' => 'required|integer|exists:lecturers,lecturer_id',
            'supervisor_id' => 'nullable|integer|exists:supervisors,supervisor_id',
        ]);

        Student::create([
            'matric_no' => trim($data['matric_no']),
            'name' => trim($data['name']),
            'email' => trim($data['email']),
            'password' => Hash::make($data['password']),
            'phone_no' => $data['phone_no'] ?? 'N/A',
            'course' => $data['course'] ?? 'Unknown',
            'lecturer_id' => $data['lecturer_id'],
            'supervisor_id' => $data['supervisor_id'] ?? null,
        ]);

        return redirect('/admin/students')->with('success', 'Student created.');
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

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone_no' => 'nullable|string|max:50',
            'course' => 'nullable|string|max:255',
            'lecturer_id' => 'required|integer|exists:lecturers,lecturer_id',
            'supervisor_id' => 'nullable|integer|exists:supervisors,supervisor_id',
        ]);

        $student->update([
            'name' => trim($data['name']),
            'phone_no' => $data['phone_no'] ?? $student->phone_no,
            'course' => $data['course'] ?? $student->course,
            'lecturer_id' => $data['lecturer_id'],
            'supervisor_id' => $data['supervisor_id'] ?? null,
        ]);

        return redirect('/admin/students')->with('success', 'Student updated.');
    }

    public function destroy($id)
    {
        Student::findOrFail($id)->delete();

        return back();
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