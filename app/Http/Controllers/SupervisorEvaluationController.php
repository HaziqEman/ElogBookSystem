<?php

namespace App\Http\Controllers;

use App\Models\Logbook;
use App\Models\Student;
use App\Models\SupervisorEvaluation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SupervisorEvaluationController extends Controller
{
    protected function supervisor()
    {
        return Auth::guard('supervisor')->user();
    }

    protected function ownStudent($id): Student
    {
        return Student::where('supervisor_id', $this->supervisor()->supervisor_id)->findOrFail($id);
    }

    /** Flat list of criteria from config/supervisor_rubric.php: key => [label, max, help]. */
    protected function criteria(): array
    {
        $list = [];

        foreach (config('supervisor_rubric.sections', []) as $section) {
            foreach ($section['criteria'] as $key => $criterion) {
                $list[$key] = [
                    'label' => $criterion['label'],
                    'max' => (int) ($criterion['max'] ?? 5),
                    'help' => $criterion['help'] ?? null,
                ];
            }
        }

        return $list;
    }

    public function show($id)
    {
        $student = $this->ownStudent($id);
        $evaluation = SupervisorEvaluation::where('student_id', $student->student_id)->first();

        $entries = Logbook::where('student_id', $student->student_id);

        $stats = [
            'entries' => (clone $entries)->count(),
            'validated' => (clone $entries)->where('supervisor_status', 'Validated')->count(),
            'pending' => (clone $entries)->where(fn ($q) => $q->where('supervisor_status', 'Pending')->orWhereNull('supervisor_status'))->count(),
        ];

        return view('supervisor.evaluation', [
            'student' => $student,
            'evaluation' => $evaluation,
            'rubric' => config('supervisor_rubric'),
            'criteria' => $this->criteria(),
            'stats' => $stats,
            'locked' => $evaluation && ! config('supervisor_rubric.allow_edit', true),
        ]);
    }

    public function store(Request $request, $id)
    {
        $student = $this->ownStudent($id);
        $back = '/supervisor/students/'.$student->student_id.'/evaluation';

        $existing = SupervisorEvaluation::where('student_id', $student->student_id)->first();

        if ($existing && ! config('supervisor_rubric.allow_edit', true)) {
            return redirect($back)->with('error', 'This evaluation was already submitted and can no longer be changed.');
        }

        $criteria = $this->criteria();
        $rules = ['comments' => 'nullable|string|max:3000'];
        $attributes = [];

        foreach ($criteria as $key => $criterion) {
            $rules['scores.'.$key] = 'required|integer|min:1|max:'.$criterion['max'];
            $attributes['scores.'.$key] = '"'.$criterion['label'].'"';
        }

        $data = $request->validate($rules, [], $attributes);

        $scores = [];
        foreach ($criteria as $key => $criterion) {
            $scores[$key] = (int) $data['scores'][$key];
        }

        $total = array_sum($scores);
        $max = array_sum(array_column($criteria, 'max'));
        $weight = (float) config('supervisor_rubric.weight', 100);
        $final = $max > 0 ? round($total / $max * $weight, 2) : 0;

        try {
            SupervisorEvaluation::updateOrCreate(
                ['student_id' => $student->student_id],
                [
                    'supervisor_id' => $this->supervisor()->supervisor_id,
                    'scores' => $scores,
                    'total_marks' => $total,
                    'max_marks' => $max,
                    'final_mark' => $final,
                    'comments' => trim((string) ($data['comments'] ?? '')) ?: null,
                    'submitted_at' => now(),
                ]
            );
        } catch (\Throwable $e) {
            Log::error('Supervisor evaluation failed: '.$e->getMessage());

            return redirect($back)->withInput()->with('error', 'Could not save the evaluation. Please try again.');
        }

        return redirect($back)->with('success', 'Evaluation saved: '.$total.'/'.$max.' marks, final mark '.$final.' out of '.rtrim(rtrim(number_format($weight, 2), '0'), '.').'.');
    }
}
