<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\SupervisorEvaluation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Read-only view of the company supervisor's evaluation marks,
 * for lecturers (their own students) and for admins (all students).
 */
class EvaluationViewController extends Controller
{
    // ---------- Lecturer ----------

    public function lecturerIndex(Request $request)
    {
        $lecturer = Auth::guard('lecturer')->user();

        $query = Student::with('supervisor')
            ->where('lecturer_id', $lecturer->lecturer_id)
            ->orderBy('name');

        return $this->index($request, $query, '/lecturer/evaluations', 'Supervisor marks for your students');
    }

    public function lecturerShow($id)
    {
        $lecturer = Auth::guard('lecturer')->user();
        $student = Student::with('supervisor')->where('lecturer_id', $lecturer->lecturer_id)->findOrFail($id);

        return $this->detail($student, '/lecturer/evaluations');
    }

    // ---------- Admin ----------

    public function adminIndex(Request $request)
    {
        $query = Student::with('supervisor')->orderBy('name');

        return $this->index($request, $query, '/admin/evaluations', 'Supervisor marks for all students');
    }

    public function adminShow($id)
    {
        $student = Student::with('supervisor')->findOrFail($id);

        return $this->detail($student, '/admin/evaluations');
    }

    // ---------- Shared ----------

    protected function index(Request $request, $query, string $base, string $title)
    {
        $search = trim((string) $request->query('q', ''));

        if ($search !== '') {
            $like = '%'.mb_strtolower($search).'%';
            $query->where(function ($w) use ($like) {
                $w->whereRaw('LOWER(name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(matric_no) LIKE ?', [$like]);
            });
        }

        $allIds = (clone $query)->pluck('student_id');
        $evaluatedCount = SupervisorEvaluation::whereIn('student_id', $allIds)->count();

        $students = $query->paginate(25)->withQueryString();

        $evaluations = SupervisorEvaluation::whereIn('student_id', $students->pluck('student_id'))
            ->get()
            ->keyBy('student_id');

        return view('evaluation.index', [
            'students' => $students,
            'evaluations' => $evaluations,
            'base' => $base,
            'title' => $title,
            'search' => $search,
            'total' => $allIds->count(),
            'evaluatedCount' => $evaluatedCount,
            'weight' => (float) config('supervisor_rubric.weight', 100),
        ]);
    }

    protected function detail(Student $student, string $base)
    {
        $evaluation = SupervisorEvaluation::where('student_id', $student->student_id)->first();

        return view('evaluation.show', [
            'student' => $student,
            'evaluation' => $evaluation,
            'supervisor' => $student->supervisor,
            'rubric' => config('supervisor_rubric'),
            'base' => $base,
            'weight' => (float) config('supervisor_rubric.weight', 100),
        ]);
    }
}
