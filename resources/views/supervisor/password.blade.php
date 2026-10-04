@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-lg space-y-6">
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs">
        <h2 class="text-2xl font-bold text-slate-800">Change password</h2>
        @if($supervisor->must_change_password)
            <p class="mt-2 text-sm text-amber-700">Your administrator gave you a temporary password. Please choose your own before continuing.</p>
        @else
            <p class="mt-2 text-sm text-slate-500">Choose a new password of at least 8 characters.</p>
        @endif
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

    <form method="POST" action="/supervisor/password" class="grid gap-4 rounded-xl border border-slate-200 bg-white p-6 shadow-xs">
        @csrf
        <label class="space-y-2">
            <span class="text-sm font-semibold text-slate-600">Current (temporary) password</span>
            <input type="password" name="current_password" required autocomplete="current-password" class="w-full rounded-lg border border-slate-200 p-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
        </label>
        <label class="space-y-2">
            <span class="text-sm font-semibold text-slate-600">New password</span>
            <input type="password" name="password" required minlength="8" autocomplete="new-password" class="w-full rounded-lg border border-slate-200 p-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
        </label>
        <label class="space-y-2">
            <span class="text-sm font-semibold text-slate-600">Confirm new password</span>
            <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" class="w-full rounded-lg border border-slate-200 p-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
        </label>
        <button type="submit" class="inline-flex justify-center rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
            Save new password
        </button>
    </form>
</div>
@endsection