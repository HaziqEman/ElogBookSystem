@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Add Lecturer</h2>
                <p class="mt-2 text-sm text-slate-500">Create a new lecturer account for the internship system.</p>
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

    <form method="POST" action="/admin/lecturers" class="grid gap-4">
        @csrf

        <div class="grid gap-4 sm:grid-cols-2">
            <label class="space-y-2">
                <span class="text-sm font-semibold text-slate-600">Full Name</span>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="Lecturer Name" class="w-full rounded-lg border border-slate-200 p-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            </label>
            <label class="space-y-2">
                <span class="text-sm font-semibold text-slate-600">Email Address</span>
                <input type="email" name="email" value="{{ old('email') }}" placeholder="lecturer@example.com" class="w-full rounded-lg border border-slate-200 p-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            </label>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <label class="space-y-2">
                <span class="text-sm font-semibold text-slate-600">Password</span>
                <input type="password" name="password" placeholder="Create a password" class="w-full rounded-lg border border-slate-200 p-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            </label>
            <label class="space-y-2">
                <span class="text-sm font-semibold text-slate-600">Faculty</span>
                <input type="text" name="faculty" value="{{ old('faculty') }}" placeholder="Faculty Name" class="w-full rounded-lg border border-slate-200 p-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            </label>
        </div>

        <button type="submit" class="inline-flex justify-center rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
            Save Lecturer
        </button>
    </form>
</div>
@endsection
