@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Edit Student</h2>
                <p class="mt-2 text-sm text-slate-500">Update student account details, contact information, and lecturer assignment.</p>
            </div>
        </div>
    </div>

    <form method="POST" action="/admin/students/{{ $student->student_id }}" class="grid gap-4">
        @csrf
        @method('PUT')

        <div class="grid gap-4 sm:grid-cols-2">
            <label class="space-y-2">
                <span class="text-sm font-semibold text-slate-600">Full Name</span>
                <input type="text" name="name" value="{{ $student->name }}" class="w-full rounded-lg border border-slate-200 p-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            </label>
            <label class="space-y-2">
                <span class="text-sm font-semibold text-slate-600">Matric Number</span>
                <input type="text" name="matric_no" value="{{ $student->matric_no }}" disabled class="w-full rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-500">
            </label>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <label class="space-y-2">
                <span class="text-sm font-semibold text-slate-600">Phone Number</span>
                <input type="text" name="phone_no" value="{{ $student->phone_no }}" class="w-full rounded-lg border border-slate-200 p-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            </label>
            <label class="space-y-2">
                <span class="text-sm font-semibold text-slate-600">Course</span>
                <input type="text" name="course" value="{{ $student->course }}" class="w-full rounded-lg border border-slate-200 p-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            </label>
        </div>

        <label class="space-y-2">
            <span class="text-sm font-semibold text-slate-600">Assigned Lecturer</span>
            <select name="lecturer_id" class="w-full rounded-lg border border-slate-200 bg-white p-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                @foreach($lecturers as $lecturer)
                    <option value="{{ $lecturer->lecturer_id }}" @if($student->lecturer_id == $lecturer->lecturer_id) selected @endif>
                        {{ $lecturer->name }}
                    </option>
                @endforeach
            </select>
        </label>

        <button type="submit" class="inline-flex justify-center rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
            Update Student
        </button>
    </form>
</div>
@endsection