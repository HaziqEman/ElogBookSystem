<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Todo;
use Carbon\Carbon;
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
            return $this->fail($request, 'You have 200 tasks. Please delete some completed ones first.');
        }

        Todo::create([
            'student_id' => $student->student_id,
            'title' => trim($data['title']),
            'due_date' => $data['due_date'] ?? null,
            'is_done' => false,
        ]);

        return $this->respond($request, $student, 'Task added.');
    }

    public function toggle(Request $request, $id)
    {
        $student = $this->student();

        $todo = Todo::where('student_id', $student->student_id)
            ->where('todo_id', $id)
            ->firstOrFail();

        $todo->is_done = ! $todo->is_done;
        $todo->save();

        return $this->respond($request, $student, $todo->is_done ? 'Task done.' : 'Task moved back to open.');
    }

    public function destroy(Request $request, $id)
    {
        $student = $this->student();

        Todo::where('student_id', $student->student_id)
            ->where('todo_id', $id)
            ->firstOrFail()
            ->delete();

        return $this->respond($request, $student, 'Task deleted.');
    }

    /** JSON for the async forms, a normal redirect for everything else. */
    protected function respond(Request $request, $student, ?string $message)
    {
        if (! $request->wantsJson()) {
            return back();
        }

        return response()->json([
            'target' => 'todo-list',
            'html' => $this->card($student),
            'message' => $message,
        ]);
    }

    protected function fail(Request $request, string $message)
    {
        if ($request->wantsJson()) {
            return response()->json(['message' => $message], 422);
        }

        return back()->with('error', $message);
    }

    protected function card($student): string
    {
        $mine = fn () => Todo::where('student_id', $student->student_id);

        return view('student.partials.todo-card', [
            'extrasReady' => true,
            'todayKey' => Carbon::now(Attendance::TIMEZONE)->toDateString(),
            'openCount' => $mine()->where('is_done', false)->count(),
            'doneCount' => $mine()->where('is_done', true)->count(),
            'openTodos' => $mine()->where('is_done', false)
                ->orderByRaw('due_date IS NULL')
                ->orderBy('due_date')
                ->orderBy('todo_id')
                ->take(8)
                ->get(),
            'doneTodos' => $mine()->where('is_done', true)->orderByDesc('updated_at')->take(5)->get(),
        ])->render();
    }
}