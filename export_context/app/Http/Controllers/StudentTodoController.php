<?php

namespace App\Http\Controllers;

use App\Models\Todo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentTodoController extends Controller
{
    protected function student()
    {
        return Auth::guard('student')->user();
    }

    public function store(Request $request)
    {
        $student = $this->student();

        $data = $request->validate([
            'title' => 'required|string|max:150',
            'due_date' => 'nullable|date',
        ]);

        if (Todo::where('student_id', $student->student_id)->count() >= 200) {
            return $this->reply($request, false, 'You have 200 tasks. Please delete some completed ones first.');
        }

        Todo::create([
            'student_id' => $student->student_id,
            'title' => trim($data['title']),
            'due_date' => $data['due_date'] ?? null,
            'is_done' => false,
        ]);

        return $this->reply($request, true, 'Task added.');
    }

    public function toggle(Request $request, $id)
    {
        $todo = Todo::where('student_id', $this->student()->student_id)
            ->where('todo_id', $id)
            ->firstOrFail();

        $todo->is_done = ! $todo->is_done;
        $todo->save();

        return $this->reply($request, true, $todo->is_done ? 'Task done.' : 'Task moved back to open.');
    }

    public function destroy(Request $request, $id)
    {
        $todo = Todo::where('student_id', $this->student()->student_id)
            ->where('todo_id', $id)
            ->firstOrFail();

        if (! empty($todo->supervisor_id)) {
            return $this->reply($request, false, 'This task was assigned by your supervisor, so only they can remove it.', 403);
        }

        $todo->delete();

        return $this->reply($request, true, 'Task deleted.');
    }

    /** JSON for the async forms, a normal redirect for everything else. */
    protected function reply(Request $request, bool $ok, string $message, int $failStatus = 422)
    {
        if ($request->wantsJson()) {
            return response()->json(['message' => $message], $ok ? 200 : $failStatus);
        }

        return $ok ? back() : back()->with('error', $message);
    }
}