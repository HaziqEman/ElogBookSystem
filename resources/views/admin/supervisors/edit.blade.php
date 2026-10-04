@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-xs">
        <h2 class="text-2xl font-bold text-slate-800">Edit Company Supervisor</h2>
        <p class="mt-2 text-sm text-slate-500">Setting a new password makes the supervisor choose their own at next login.</p>
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

    <form method="POST" action="/admin/supervisors/{{ $supervisor->supervisor_id }}" class="grid gap-4">
        @csrf
        @method('PUT')

        <div class="grid gap-4 sm:grid-cols-2">
            <label class="space-y-2">
                <span class="text-sm font-semibold text-slate-600">Full Name</span>
                <input type="text" name="name" value="{{ old('name', $supervisor->name) }}" required class="w-full rounded-lg border border-slate-200 p-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            </label>
            <label class="space-y-2">
                <span class="text-sm font-semibold text-slate-600">Email Address</span>
                <input type="email" name="email" value="{{ old('email', $supervisor->email) }}" required class="w-full rounded-lg border border-slate-200 p-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            </label>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <label class="space-y-2">
                <span class="text-sm font-semibold text-slate-600">Company</span>
                <input type="text" name="company_name" value="{{ old('company_name', $supervisor->company_name) }}" required class="w-full rounded-lg border border-slate-200 p-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            </label>
            <label class="space-y-2">
                <span class="text-sm font-semibold text-slate-600">Position</span>
                <input type="text" name="position" value="{{ old('position', $supervisor->position) }}" class="w-full rounded-lg border border-slate-200 p-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            </label>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <label class="space-y-2">
                <span class="text-sm font-semibold text-slate-600">Phone</span>
                <input type="text" name="phone" value="{{ old('phone', $supervisor->phone) }}" class="w-full rounded-lg border border-slate-200 p-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            </label>
            <label class="space-y-2">
                <span class="text-sm font-semibold text-slate-600">New temporary password</span>
                <input type="password" name="password" minlength="8" autocomplete="new-password" placeholder="Leave blank to keep current password" class="w-full rounded-lg border border-slate-200 p-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            </label>
        </div>

        <button type="submit" class="inline-flex justify-center rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
            Update Supervisor
        </button>
    </form>
</div>
@endsection