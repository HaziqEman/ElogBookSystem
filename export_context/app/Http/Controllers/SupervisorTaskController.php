<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\Todo;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class SupervisorTaskController extends Controller
{
    protected function supervisor()
    {
        return Auth::guard('supervisor')->user();
    }

    protected function today(): string
    {
        return Carbon::now(Attendance::TIMEZONE)->toDateString();
    }

    protected function reply(Request $request, bool $ok, string $message)
    {
        if ($request->wantsJson()) {
            return response()->json(['message' => $message], $ok ? 200 : 422);
        }

        return back()->with($ok ? 'success' : 'error', $message);
    }

    public function index()
    {
        $supervisor = $this->supervisor();
        $today = $this->today();
        $ready = Schema::hasTable('todos') && Schema::hasColumn('todos', 'supervisor_id');

        $students = Student::where('supervisor_id', $supervisor->supervisor_id)
            ->orderBy('name')
            ->get(['student_id', 'name', 'matric_no']);

        $tasks = null;
        $counts = ['open' => 0, 'overdue' => 0, 'done' => 0];

        if ($ready) {
            $mine = fn () => Todo::where('supervisor_id', $supervisor->supervisor_id);

            $tasks = $mine()
                ->with('student:student_id,name,matric_no')
                ->orderBy('is_done')
                ->orderByRaw('due_date IS NULL')
                ->orderBy('due_date')
                ->orderByDesc('todo_id')
                ->paginate(12);

            $counts = [
                'open' => $mine()->where('is_done', false)->count(),
                'overdue' => $mine()->where('is_done', false)->whereNotNull('due_date')->where('due_date', '<', $today)->count(),
                'done' => $mine()->where('is_done', true)->count(),
            ];
        }

        return view('supervisor.tasks', compact('supervisor', 'students', 'tasks', 'counts', 'today', 'ready'));
    }

    public function store(Request $request)
    {
        $supervisor = $this->supervisor();

        $data = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'integer',
            'title' => 'required|string|max:150',
            'due_date' => 'nullable|date',
        ], [
            'student_ids.required' => 'Choose at least one student.',
        ]);

        if (! empty($data['due_date']) && $data['due_date'] < $this->today()) {
            throw ValidationException::withMessages(['due_date' => 'The deadline cannot be in the past.']);
        }

        $ids = array_values(array_unique($data['student_ids']));

        $students = Student::where('supervisor_id', $supervisor->supervisor_id)
            ->whereIn('student_id', $ids)
            ->get();

        if ($students->count() !== count($ids)) {
            throw ValidationException::withMessages(['student_ids' => 'You can only assign tasks to your own students.']);
        }

        $title = trim($data['title']);
        $notifications = app(NotificationService::class);

        foreach ($students as $student) {
            Todo::create([
                'student_id' => $student->student_id,
                'supervisor_id' => $supervisor->supervisor_id,
                'title' => $title,
                'due_date' => $data['due_date'] ?? null,
                'is_done' => false,
            ]);

            $notifications->notify(
                $student,
                'task',
                'New task from '.$supervisor->name,
                $title.(! empty($data['due_date']) ? ' (due '.Carbon::parse($data['due_date'])->format('d M').')' : ''),
                '/student/dashboard#todo-list'
            );
        }

        return $this->reply($request, true, 'Task assigned to '.$students->count().' '.($students->count() === 1 ? 'student' : 'students').'.');
    }

    public function update(Request $request, $id)
    {
        $supervisor = $this->supervisor();

        $task = Todo::with('student')
            ->where('todo_id', $id)
            ->where('supervisor_id', $supervisor->supervisor_id)
            ->firstOrFail();

        $data = $request->validate([
            'title' => 'required|string|max:150',
            'due_date' => 'nullable|date',
        ]);

        $before = $task->due_date ? $task->due_date->toDateString() : null;
        $after = $data['due_date'] ?? null;

        $task->title = trim($data['title']);
        $task->due_date = $after;
        $task->save();

        if ($before !== $after && $task->student) {
            app(NotificationService::class)->notify(
                $task->student,
                'task',
                'Deadline changed by '.$supervisor->name,
                $task->title.' - '.($after ? 'now due '.Carbon::parse($after)->format('d M') : 'no deadline'),
                '/student/dashboard#todo-list'
            );
        }

        return $this->reply($request, true, 'Task updated.');
    }

    public function destroy(Request $request, $id)
    {
        Todo::where('todo_id', $id)
            ->where('supervisor_id', $this->supervisor()->supervisor_id)
            ->firstOrFail()
            ->delete();

        return $this->reply($request, true, 'Task removed.');
    }
}