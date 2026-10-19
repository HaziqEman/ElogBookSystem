@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Edit Student</h2>
                <p class="mt-2 text-sm text-slate-500">Update student details, reviewers and internship dates.</p>
            </div>
        </div>
    </div>

    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="/admin/students/{{ $student->student_id }}" class="grid gap-4">
        @csrf
        @method('PUT')

        <div class="grid gap-4 sm:grid-cols-2">
            <label class="space-y-2">
                <span class="text-sm font-semibold text-slate-600">Full Name</span>
                <input type="text" name="name" value="{{ old('name', $student->name) }}" class="w-full rounded-lg border border-slate-200 p-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            </label>
            <label class="space-y-2">
                <span class="text-sm font-semibold text-slate-600">Matric Number</span>
                <input type="text" name="matric_no" value="{{ $student->matric_no }}" disabled class="w-full rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-500">
            </label>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <label class="space-y-2">
                <span class="text-sm font-semibold text-slate-600">Phone Number</span>
                <input type="text" name="phone_no" value="{{ old('phone_no', $student->phone_no) }}" class="w-full rounded-lg border border-slate-200 p-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            </label>
            <label class="space-y-2">
                <span class="text-sm font-semibold text-slate-600">Course</span>
                <input type="text" name="course" value="{{ old('course', $student->course) }}" class="w-full rounded-lg border border-slate-200 p-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            </label>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <label class="space-y-2">
                <span class="text-sm font-semibold text-slate-600">Assigned Lecturer (Faculty Evaluator)</span>
                <select name="lecturer_id" class="w-full rounded-lg border border-slate-200 bg-white p-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    @foreach($lecturers as $lecturer)
                        <option value="{{ $lecturer->lecturer_id }}" @if(old('lecturer_id', $student->lecturer_id) == $lecturer->lecturer_id) selected @endif>
                            {{ $lecturer->name }}
                        </option>
                    @endforeach
                </select>
            </label>
            <label class="space-y-2">
                <span class="text-sm font-semibold text-slate-600">Assigned Company Supervisor</span>
                <select name="supervisor_id" class="w-full rounded-lg border border-slate-200 bg-white p-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    <option value="">Not assigned</option>
                    @foreach($supervisors as $supervisor)
                        <option value="{{ $supervisor->supervisor_id }}" @if(old('supervisor_id', $student->supervisor_id) == $supervisor->supervisor_id) selected @endif>
                            {{ $supervisor->name }} - {{ $supervisor->company_name }}
                        </option>
                    @endforeach
                </select>
            </label>
        </div>

        @php
            $currentDays = \App\Services\WeekPlanner::parseDays($student->working_days);
        @endphp

        <div class="space-y-4 rounded-xl border border-slate-200 bg-white p-5">
            <div>
                <h3 class="text-sm font-bold text-slate-700">Internship dates</h3>
                <p class="mt-1 text-xs text-slate-500">The student's weeks, reminders and weekly review notices are worked out from these. Only the administrator can change them.</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="space-y-2">
                    <span class="text-sm font-semibold text-slate-600">Internship start date</span>
                    <input type="date" name="internship_start" value="{{ old('internship_start', optional($student->internship_start)->toDateString()) }}" class="w-full rounded-lg border border-slate-200 p-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </label>
                <label class="space-y-2">
                    <span class="text-sm font-semibold text-slate-600">Total weeks</span>
                    <input type="number" name="internship_weeks" min="1" max="52" value="{{ old('internship_weeks', $student->internship_weeks ?: 14) }}" class="w-full rounded-lg border border-slate-200 p-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </label>
            </div>
            <fieldset>
                <legend class="mb-2 text-sm font-semibold text-slate-600">Working days (none ticked means Mon to Fri)</legend>
                <div class="flex flex-wrap gap-2">
                    @foreach(\App\Services\WeekPlanner::DAY_NAMES as $number => $name)
                        <label class="cursor-pointer">
                            <input type="checkbox" name="working_days[]" value="{{ $number }}" class="peer sr-only" {{ in_array($number, old('working_days', $currentDays)) ? 'checked' : '' }}>
                            <span class="flex min-h-10 min-w-[52px] items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-600 transition peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700 peer-focus-visible:ring-2 peer-focus-visible:ring-blue-500/40">{{ $name }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        </div>

        <button type="submit" class="inline-flex justify-center rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
            Update Student
        </button>
    </form>
</div>
@endsection