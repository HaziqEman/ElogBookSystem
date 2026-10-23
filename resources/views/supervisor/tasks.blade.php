@extends('layouts.app')

@section('content')
@php
    $chip = [
        'Done' => 'bg-emerald-100 text-emerald-800',
        'Overdue' => 'bg-rose-100 text-rose-800',
        'Open' => 'bg-amber-100 text-amber-800',
    ];
@endphp

<div class="space-y-6">
    @if(session('success'))
        <div data-flash="success" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div data-flash="error" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div data-flash="error" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs md:p-6">
        <h2 class="text-2xl font-bold text-slate-800">Tasks for your students</h2>
        <p class="mt-1 text-sm text-slate-500">Assign a task with a deadline. Students see it in their to-do list, get a notification and a reminder, and you see when they finish it. You only see the tasks you assigned. Students' own tasks stay private.</p>
    </div>

    @if(! $ready)
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800">The task feature is not set up yet. Ask the administrator to run the database update.</div>
    @else
        <div class="grid items-start gap-6 lg:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
                <h3 class="mb-3 text-base font-bold text-slate-800">Assign a task</h3>

                @if($students->isEmpty())
                    <p class="text-sm text-slate-500">No students are assigned to you yet. Please ask the administrator.</p>
                @else
                    <form id="task-form" method="POST" action="/supervisor/tasks" data-async class="space-y-3">
                        @csrf
                        <fieldset>
                            <legend class="mb-1 text-xs font-semibold text-slate-600">Students</legend>
                            <label class="mb-1 flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <input type="checkbox" id="task-all" class="h-4 w-4"> Select all
                            </label>
                            <div class="max-h-44 space-y-1 overflow-y-auto rounded-lg border border-slate-200 p-2">
                                @foreach($students as $student)
                                    <label class="flex items-center gap-2 text-sm text-slate-700">
                                        <input type="checkbox" name="student_ids[]" value="{{ $student->student_id }}" class="h-4 w-4">
                                        <span class="min-w-0 truncate">{{ $student->name }} <span class="text-xs text-slate-400">{{ $student->matric_no }}</span></span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600" for="task-title">Task</label>
                            <input type="text" id="task-title" name="title" required maxlength="150" placeholder="e.g. Submit the weekly report" class="w-full rounded-lg border border-slate-200 p-2.5 text-sm focus:border-blue-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600" for="task-due">Deadline (optional)</label>
                            <input type="date" id="task-due" name="due_date" min="{{ $today }}" class="w-full rounded-lg border border-slate-200 p-2.5 text-sm focus:border-blue-500 focus:outline-none">
                        </div>
                        <button type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60">Assign task</button>
                    </form>
                @endif
            </div>

            <div id="task-list" data-live class="space-y-4 lg:col-span-2">
                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4"><p class="text-xs font-semibold text-slate-600">Open</p><p class="text-2xl font-bold text-amber-800">{{ $counts['open'] }}</p></div>
                    <div class="rounded-xl border border-rose-200 bg-rose-50 p-4"><p class="text-xs font-semibold text-slate-600">Overdue</p><p class="text-2xl font-bold text-rose-800">{{ $counts['overdue'] }}</p></div>
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4"><p class="text-xs font-semibold text-slate-600">Done</p><p class="text-2xl font-bold text-emerald-800">{{ $counts['done'] }}</p></div>
                </div>

                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs">
                    <div class="overflow-x-auto">
                        <table class="min-w-[640px] w-full text-left text-sm">
                            <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">Student</th>
                                    <th class="px-4 py-3">Task</th>
                                    <th class="px-4 py-3">Deadline</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($tasks as $task)
                                    @php
                                        $due = $task->due_date ? $task->due_date->toDateString() : null;
                                        $status = $task->is_done ? 'Done' : ($due && $due < $today ? 'Overdue' : 'Open');
                                    @endphp
                                    <tr>
                                        <td class="px-4 py-3">
                                            <p class="font-semibold text-slate-800">{{ optional($task->student)->name }}</p>
                                            <p class="text-xs text-slate-400">{{ optional($task->student)->matric_no }}</p>
                                        </td>
                                        <td class="max-w-[16rem] break-words px-4 py-3 text-slate-700">{{ $task->title }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $due ? $task->due_date->format('d M Y') : 'No deadline' }}</td>
                                        <td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $chip[$status] }}">{{ $status }}</span></td>
                                        <td class="px-4 py-3">
                                            <div class="flex flex-wrap items-start gap-2">
                                                <details>
                                                    <summary class="cursor-pointer rounded-lg bg-amber-500 px-3 py-1.5 text-xs font-semibold text-slate-900 hover:bg-amber-600">Edit</summary>
                                                    <form method="POST" action="/supervisor/tasks/{{ $task->todo_id }}" data-async class="mt-2 w-56 space-y-2 rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="text" name="title" required maxlength="150" value="{{ $task->title }}" class="w-full rounded-lg border border-slate-200 p-2 text-sm">
                                                        <input type="date" name="due_date" value="{{ $due }}" class="w-full rounded-lg border border-slate-200 p-2 text-sm">
                                                        <button type="submit" class="w-full rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700 disabled:opacity-60">Save</button>
                                                    </form>
                                                </details>
                                                <form method="POST" action="/supervisor/tasks/{{ $task->todo_id }}" data-async onsubmit="return confirm('Remove this task?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="rounded-lg bg-rose-500 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-600 disabled:opacity-60">Remove</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No tasks yet. Assign the first one on the left.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div>{{ $tasks->links() }}</div>
            </div>
        </div>
    @endif
</div>

<script>
    document.addEventListener('change', function (event) {
        if (event.target && event.target.id === 'task-all') {
            document.querySelectorAll('input[name="student_ids[]"]').forEach(function (box) {
                box.checked = event.target.checked;
            });
        }
    });

    document.addEventListener('async:success', function (event) {
        if (event.target && event.target.id === 'task-form') event.target.reset();
    });
</script>
@endsection