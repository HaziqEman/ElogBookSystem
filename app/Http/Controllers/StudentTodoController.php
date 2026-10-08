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
            return back()->with('error', 'You have 200 tasks. Please delete some completed ones first.');
        }

        Todo::create([
            'student_id' => $student->student_id,
            'title' => trim($data['title']),
            'due_date' => $data['due_date'] ?? null,
            'is_done' => false,
        ]);

        return back();
    }

    public function toggle($id)
    {
        $todo = Todo::where('student_id', $this->student()->student_id)
            ->where('todo_id', $id)
            ->firstOrFail();

        $todo->is_done = ! $todo->is_done;
        $todo->save();

        return back();
    }

    public function destroy($id)
    {
        Todo::where('student_id', $this->student()->student_id)
            ->where('todo_id', $id)
            ->firstOrFail()
            ->delete();

        return back();
    }
}