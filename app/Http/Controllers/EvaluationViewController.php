<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\SupervisorEvaluation;
use App\Services\SupervisorRubricService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Read-only view of the company supervisor's evaluation marks,
 * for lecturers (their own students) and for admins (all students).
 */
class EvaluationViewController extends Controller
{
    // ---------- Lecturer ----------

    public function lecturerIndex(Request $request, SupervisorRubricService $rubrics)
    {
        $lecturer = Auth::guard('lecturer')->user();

        $query = Student::with('supervisor')
            ->where('lecturer_id', $lecturer->lecturer_id)
            ->orderBy('name');

        return $this->index($request, $query, '/lecturer/evaluations', 'Supervisor marks for your students', $rubrics->current());
    }

    public function lecturerShow($id, SupervisorRubricService $rubrics)
    {
        $lecturer = Auth::guard('lecturer')->user();
        $student = Student::with('supervisor')->where('lecturer_id', $lecturer->lecturer_id)->findOrFail($id);

        return $this->detail($student, '/lecturer/evaluations', $rubrics->current());
    }

    // ---------- Admin ----------

    public function adminIndex(Request $request, SupervisorRubricService $rubrics)
    {
        $query = Student::with('supervisor')->orderBy('name');

        return $this->index($request, $query, '/admin/evaluations', 'Supervisor marks for all students', $rubrics->current());
    }

    public function adminShow($id, SupervisorRubricService $rubrics)
    {
        $student = Student::with('supervisor')->findOrFail($id);

        return $this->detail($student, '/admin/evaluations', $rubrics->current());
    }

    // ---------- Shared ----------

    protected function index(Request $request, $query, string $base, string $title, array $rubric)
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
            'weight' => (float) ($rubric['weight'] ?? 100),
        ]);
    }

    protected function detail(Student $student, string $base, array $currentRubric)
    {
        $evaluation = SupervisorEvaluation::where('student_id', $student->student_id)->first();
        $rubric = $evaluation?->rubric_snapshot ?: $currentRubric;

        return view('evaluation.show', [
            'student' => $student,
            'evaluation' => $evaluation,
            'supervisor' => $student->supervisor,
            'rubric' => $rubric,
            'base' => $base,
            'weight' => (float) ($rubric['weight'] ?? 100),
        ]);
    }
}
