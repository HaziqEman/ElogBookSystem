@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Add Student</h2>
                <p class="mt-2 text-sm text-slate-500">Create a new student and assign them to a faculty supervisor.</p>
            </div>
        </div>
    </div>

    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="/admin/students" class="grid gap-4">
        @csrf

        <div class="grid gap-4 sm:grid-cols-2">
            <label class="space-y-2">
                <span class="text-sm font-semibold text-slate-600">Matric Number</span>
                <input type="text" name="matric_no" value="{{ old('matric_no') }}" placeholder="2025110501" class="w-full rounded-lg border border-slate-200 p-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            </label>
            <label class="space-y-2">
                <span class="text-sm font-semibold text-slate-600">Full Name</span>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="Student Name" class="w-full rounded-lg border border-slate-200 p-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            </label>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <label class="space-y-2">
                <span class="text-sm font-semibold text-slate-600">Email Address</span>
                <input type="email" name="email" value="{{ old('email') }}" placeholder="student@example.com" class="w-full rounded-lg border border-slate-200 p-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            </label>
            <label class="space-y-2">
                <span class="text-sm font-semibold text-slate-600">Password</span>
                <input type="password" name="password" placeholder="Create a password" class="w-full rounded-lg border border-slate-200 p-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            </label>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <label class="space-y-2">
                <span class="text-sm font-semibold text-slate-600">Phone Number</span>
                <input type="text" name="phone_no" value="{{ old('phone_no') }}" placeholder="0123456789" class="w-full rounded-lg border border-slate-200 p-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            </label>
            <label class="space-y-2">
                <span class="text-sm font-semibold text-slate-600">Course</span>
                <input type="text" name="course" value="{{ old('course') }}" placeholder="Computer Science" class="w-full rounded-lg border border-slate-200 p-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            </label>
        </div>

        <label class="space-y-2">
            <span class="text-sm font-semibold text-slate-600">Assign Lecturer</span>
            <select name="lecturer_id" class="w-full rounded-lg border border-slate-200 bg-white p-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                @forelse($lecturers as $lecturer)
                    <option value="{{ $lecturer->lecturer_id }}" {{ old('lecturer_id') == $lecturer->lecturer_id ? 'selected' : '' }}>{{ $lecturer->name }}</option>
                @empty
                    <option value="">No lecturers available</option>
                @endforelse
            </select>
        </label>

        <button type="submit" class="inline-flex justify-center rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
            Save Student
        </button>
    </form>
</div>
@endsection