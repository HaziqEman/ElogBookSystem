@extends('layouts.guest')

@section('content')
<div class="flex min-h-screen items-center justify-center">
    <div class="w-full max-w-md overflow-hidden rounded-2xl border border-white/10 bg-white p-8 shadow-2xl">
        <div class="space-y-2 text-center">
            <div class="mb-2 inline-flex rounded-xl bg-blue-50 p-3 text-blue-600">
                <i class="fa-solid fa-book-open text-3xl"></i>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">UiTM E-Logbook Portal</h1>
            <p class="text-sm text-slate-500">Sign in to update or evaluate logbooks</p>
        </div>

        @if($errors->any())
            <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(session('error'))
            <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                {{ session('error') }}
            </div>
        @endif

        <form action="/login" method="POST" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">Email Address</label>
                <input type="email" name="email" value="{{ old('email') }}" required placeholder="you@example.com" class="w-full rounded-lg border border-slate-200 p-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">Password</label>
                <input type="password" name="password" required placeholder="••••••••" class="w-full rounded-lg border border-slate-200 p-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            </div>

            <button type="submit" class="w-full rounded-lg bg-blue-600 p-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                Secure Login
            </button>
        </form>

        <div class="mt-6 border-t border-slate-100 pt-3 text-center">
            <p class="text-xs text-slate-500">New to the platform? <a href="/register" class="font-semibold text-blue-600 hover:underline">Create an account</a></p>
        </div>
    </div>
</div>
@endsection