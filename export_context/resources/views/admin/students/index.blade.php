@extends('layouts.app')

@section('content')
@php
    $chipTone = [
        'slate' => 'bg-slate-100 text-slate-700 border-slate-200',
        'emerald' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'sky' => 'bg-sky-50 text-sky-700 border-sky-200',
        'amber' => 'bg-amber-50 text-amber-800 border-amber-200',
        'rose' => 'bg-rose-50 text-rose-700 border-rose-200',
    ];

    $stateBadge = [
        'not_set' => 'bg-amber-100 text-amber-800',
        'not_started' => 'bg-sky-100 text-sky-700',
        'in_progress' => 'bg-emerald-100 text-emerald-700',
        'finished' => 'bg-slate-200 text-slate-700',
    ];

    $dayNames = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];
    $hasFilters = collect(request()->only(['q', 'course', 'lecturer', 'supervisor', 'status']))->filter()->isNotEmpty();
    $inputClass = 'w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500';
@endphp

<div class="space-y-5">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Student Management</h2>
            <p class="mt-2 text-sm text-slate-500">Search, fix and update students one by one or in batches: internship dates, lecturers and company supervisors.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="/admin/students/export?{{ http_build_query(request()->except('page')) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                <i class="fa-solid fa-file-csv"></i> Export CSV
            </a>
            <a href="/admin/students/import" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                <i class="fa-solid fa-file-excel"></i> Import Excel
            </a>
            <a href="/admin/students/create" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
                <i class="fa-solid fa-plus"></i> Add Student
            </a>
        </div>
    </div>

    @include('admin.partials.flash')

    {{-- Quick filters --}}
    <div class="flex flex-wrap gap-2">
        @foreach($chips as $chip)
            <a href="/admin/students{{ $chip['query'] ? '?'.http_build_query($chip['query']) : '' }}"
               class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold transition hover:shadow-sm {{ $chipTone[$chip['tone']] }} {{ $activeChip === $chip['key'] ? 'ring-2 ring-blue-500' : '' }}">
                {{ $chip['label'] }}
                <span class="rounded-full bg-white/80 px-2 py-0.5 text-[11px]">{{ $chip['count'] }}</span>
            </a>
        @endforeach
    </div>

    {{-- Search and filters --}}
    <form method="GET" action="/admin/students" class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-6">
            <div class="relative lg:col-span-2">
                <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name, matric or email" class="{{ $inputClass }} pl-9">
            </div>

            <select name="course" onchange="this.form.submit()" class="{{ $inputClass }}">
                <option value="">All courses</option>
                @foreach($courses as $course)
                    <option value="{{ $course }}" {{ request('course') === $course ? 'selected' : '' }}>{{ $course }}</option>
                @endforeach
            </select>

            <select name="lecturer" onchange="this.form.submit()" class="{{ $inputClass }}">
                <option value="">All lecturers</option>
                <option value="none" {{ request('lecturer') === 'none' ? 'selected' : '' }}>Unassigned</option>
                @foreach($lecturers as $lecturer)
                    <option value="{{ $lecturer->lecturer_id }}" {{ (string) request('lecturer') === (string) $lecturer->lecturer_id ? 'selected' : '' }}>{{ $lecturer->name }}</option>
                @endforeach
            </select>

            <select name="supervisor" onchange="this.form.submit()" class="{{ $inputClass }}">
                <option value="">All supervisors</option>
                <option value="none" {{ request('supervisor') === 'none' ? 'selected' : '' }}>Unassigned</option>
                @foreach($supervisors as $supervisor)
                    <option value="{{ $supervisor->supervisor_id }}" {{ (string) request('supervisor') === (string) $supervisor->supervisor_id ? 'selected' : '' }}>{{ $supervisor->name }}{{ $supervisor->company_name ? ' - '.$supervisor->company_name : '' }}</option>
                @endforeach
            </select>

            <select name="status" onchange="this.form.submit()" class="{{ $inputClass }}">
                <option value="">Any internship status</option>
                <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In progress</option>
                <option value="not_started" {{ request('status') === 'not_started' ? 'selected' : '' }}>Not started</option>
                <option value="finished" {{ request('status') === 'finished' ? 'selected' : '' }}>Finished</option>
                <option value="not_set" {{ request('status') === 'not_set' ? 'selected' : '' }}>Dates not set</option>
            </select>
        </div>

        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                <label for="sort">Sort</label>
                <select id="sort" name="sort" onchange="this.form.submit()" class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs text-slate-700">
                    <option value="name" {{ request('sort', 'name') === 'name' ? 'selected' : '' }}>Name A-Z</option>
                    <option value="matric" {{ request('sort') === 'matric' ? 'selected' : '' }}>Matric</option>
                    <option value="start" {{ request('sort') === 'start' ? 'selected' : '' }}>Start date</option>
                    <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Newest added</option>
                </select>
                <label for="per_page" class="ml-2">Show</label>
                <select id="per_page" name="per_page" onchange="this.form.submit()" class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs text-slate-700">
                    @foreach([10, 25, 50, 100] as $size)
                        <option value="{{ $size }}" {{ $perPage === $size ? 'selected' : '' }}>{{ $size }} per page</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                @if($hasFilters)
                    <a href="/admin/students" class="rounded-lg px-3 py-2 text-xs font-semibold text-slate-500 hover:text-slate-800">Clear filters</a>
                @endif
                <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-xs font-semibold text-white transition hover:bg-slate-900">Search</button>
            </div>
        </div>
    </form>

    {{-- Bulk actions (appears once rows are ticked) --}}
    <form id="bulk-form" method="POST" action="/admin/students/bulk?{{ http_build_query(request()->except('page')) }}" data-total="{{ $students->total() }}" data-noun="students">
        @csrf
        <div id="bulk-bar" class="sticky top-2 z-20 hidden rounded-xl border border-blue-200 bg-blue-50 p-4 shadow-lg">
            <div class="flex flex-wrap items-center gap-x-5 gap-y-2">
                <p class="text-sm font-semibold text-blue-900"><span id="selected-count">0</span> selected</p>
                <div id="apply-all-wrap" class="hidden">
                    <label class="flex items-center gap-2 text-xs text-blue-900">
                        <input type="checkbox" id="apply-all" name="apply_to_all" value="1" class="h-4 w-4 rounded border-blue-300">
                        Apply to all {{ $students->total() }} students matching the current filters, not just this page
                    </label>
                </div>
                <button type="button" id="bulk-clear" class="ml-auto text-xs font-semibold text-blue-700 hover:underline">Clear selection</button>
            </div>

            <div class="mt-3 flex flex-col gap-3 lg:flex-row lg:items-end">
                <div class="lg:w-72">
                    <label for="bulk-action" class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Action</label>
                    <select id="bulk-action" name="action" class="{{ $inputClass }}">
                        <option value="">Choose an action...</option>
                        <option value="set_internship">Set internship dates / duration / days</option>
                        <option value="shift_start">Move start date earlier or later</option>
                        <option value="assign_lecturer">Assign lecturer</option>
                        <option value="assign_supervisor">Assign company supervisor</option>
                        <option value="delete" data-danger="1" data-confirm="Permanently delete {n} students and ALL of their logbook entries? This cannot be undone.">Delete students</option>
                    </select>
                </div>

                <div class="min-w-0 flex-1">
                    <div data-panel="set_internship" class="hidden">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div>
                                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Start date</label>
                                <input type="date" name="start_date" min="2020-01-01" max="2100-12-31" class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Duration (weeks)</label>
                                <input type="number" name="weeks" min="1" max="52" placeholder="Leave blank to keep" class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Working days</label>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach($dayNames as $number => $dayName)
                                        <label class="cursor-pointer">
                                            <input type="checkbox" name="working_days[]" value="{{ $number }}" class="peer sr-only">
                                            <span class="inline-block rounded-md border border-slate-200 bg-white px-2 py-1 text-xs font-semibold text-slate-600 peer-checked:border-blue-600 peer-checked:bg-blue-600 peer-checked:text-white">{{ $dayName }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">Only the fields you fill in are changed. Leave everything else blank to keep it as it is.</p>
                    </div>

                    <div data-panel="shift_start" class="hidden">
                        <div class="grid grid-cols-2 gap-3 sm:max-w-md">
                            <div>
                                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Move by</label>
                                <input type="number" name="shift_amount" min="-365" max="365" placeholder="e.g. 7 or -3" class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Unit</label>
                                <select name="shift_unit" class="{{ $inputClass }}">
                                    <option value="days">days</option>
                                    <option value="weeks">weeks</option>
                                </select>
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">Positive moves the start date later, negative moves it earlier. Students without a start date are skipped.</p>
                    </div>

                    <div data-panel="assign_lecturer" class="hidden">
                        <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Lecturer</label>
                        <select name="lecturer_id" class="{{ $inputClass }} sm:max-w-md">
                            @foreach($lecturers as $lecturer)
                                <option value="{{ $lecturer->lecturer_id }}">{{ $lecturer->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div data-panel="assign_supervisor" class="hidden">
                        <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Company supervisor</label>
                        <select name="supervisor_id" class="{{ $inputClass }} sm:max-w-md">
                            <option value="none">No supervisor (unassign)</option>
                            @foreach($supervisors as $supervisor)
                                <option value="{{ $supervisor->supervisor_id }}">{{ $supervisor->name }}{{ $supervisor->company_name ? ' - '.$supervisor->company_name : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div data-panel="delete" class="hidden">
                        <p class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700">
                            This permanently deletes the selected students and all of their logbook entries.
                        </p>
                    </div>
                </div>

                <button type="submit" id="bulk-submit" disabled class="cursor-not-allowed rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white opacity-50 transition hover:bg-blue-700">Apply</button>
            </div>
        </div>
    </form>

    {{-- Table --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="w-10 px-4 py-3 text-left">
                            <input type="checkbox" id="select-all" class="h-4 w-4 rounded border-slate-300" aria-label="Select all students on this page">
                        </th>
                        <th class="px-4 py-3 text-left">Student</th>
                        <th class="px-4 py-3 text-left">Course</th>
                        <th class="px-4 py-3 text-left">Lecturer</th>
                        <th class="px-4 py-3 text-left">Company Supervisor</th>
                        <th class="px-4 py-3 text-left">Internship</th>
                        <th class="px-4 py-3 text-left">Logbook</th>
                        <th class="px-4 py-3 text-left">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                    @forelse($students as $student)
                        @php $state = $student->internship_state; @endphp
                        <tr class="transition hover:bg-slate-50/70">
                            <td class="px-4 py-4">
                                <input type="checkbox" form="bulk-form" name="ids[]" value="{{ $student->student_id }}" class="row-check h-4 w-4 rounded border-slate-300" aria-label="Select {{ $student->name }}">
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-semibold text-slate-800">{{ $student->name }}</p>
                                <p class="text-xs text-slate-400">{{ $student->matric_no }}</p>
                            </td>
                            <td class="px-4 py-4">{{ $student->course }}</td>
                            <td class="px-4 py-4">
                                @if($student->lecturer)
                                    {{ $student->lecturer->name }}
                                @else
                                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">Unassigned</span>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                @if($student->supervisor)
                                    {{ $student->supervisor->name }}
                                    <span class="block text-xs text-slate-400">{{ $student->supervisor->company_name }}</span>
                                @else
                                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">Unassigned</span>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $stateBadge[$state['key']] }}">{{ $state['label'] }}</span>
                                @if($student->internship_start)
                                    <span class="mt-1.5 block text-xs text-slate-500">{{ $student->internship_start->format('d M Y') }}</span>
                                    <span class="block text-xs text-slate-400">{{ $student->internship_weeks ?: 14 }} weeks &middot; {{ implode(', ', array_map(fn ($day) => \App\Services\WeekPlanner::DAY_NAMES[$day], \App\Services\WeekPlanner::parseDays($student->working_days))) }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-semibold text-slate-800">{{ $student->logbooks_count }} {{ $student->logbooks_count === 1 ? 'entry' : 'entries' }}</p>
                                <p class="text-xs text-slate-400">
                                    @if($student->logbooks_max_created_at)
                                        Last {{ \Carbon\Carbon::parse($student->logbooks_max_created_at)->format('d M Y') }}
                                    @else
                                        No entries yet
                                    @endif
                                </p>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <a href="/admin/students/{{ $student->student_id }}/edit" class="inline-flex items-center rounded-lg bg-amber-500 px-3 py-1.5 text-xs font-semibold text-slate-900 transition hover:bg-amber-600">
                                        Edit
                                    </a>
                                    <form action="/admin/students/{{ $student->student_id }}/reset-password" method="POST" class="inline" onsubmit="return confirm('Reset the password for {{ addslashes($student->name) }}? A new temporary password will be shown once.');">
                                        @csrf
                                        <button type="submit" title="Reset password" aria-label="Reset password for {{ $student->name }}" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100">
                                            <i class="fa-solid fa-key"></i>
                                        </button>
                                    </form>
                                    <form action="/admin/students/{{ $student->student_id }}" method="POST" class="inline" onsubmit="return confirm('Delete this student and all of their logbook entries?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center rounded-lg bg-rose-500 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-rose-600">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-slate-500">
                                No students found.
                                @if($hasFilters)
                                    <a href="/admin/students" class="font-semibold text-blue-600 hover:underline">Clear filters</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="flex justify-end">
        {{ $students->links() }}
    </div>
</div>

@include('admin.partials.bulk-js')
@endsection
