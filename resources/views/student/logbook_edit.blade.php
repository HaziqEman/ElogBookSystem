@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <a href="/student/dashboard" class="inline-flex items-center gap-2 text-sm font-semibold text-blue-700 hover:underline">
        <i class="fa-solid fa-arrow-left"></i> Back to dashboard
    </a>

    @if(session('error'))
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs md:p-6">
        <h2 class="mb-3 text-xl font-bold text-slate-800">Edit logbook entry</h2>
        @include('student.partials.logbook-edit-form')
    </div>
</div>

<script>
    // On this full page, go back to the dashboard once the entry is saved.
    document.addEventListener('async:success', function () {
        setTimeout(function () { window.location.assign('/student/dashboard'); }, 700);
    });
</script>
@endsection