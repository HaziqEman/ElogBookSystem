@extends('layouts.app')

@section('content')
@php
    $criterionCount = collect($rubric['sections'] ?? [])->sum(fn ($section) => count($section['criteria'] ?? []));
    $maxMarks = collect($rubric['sections'] ?? [])
        ->flatMap(fn ($section) => $section['criteria'] ?? [])
        ->sum(fn ($criterion) => (int) ($criterion['max'] ?? 5));
@endphp

<div class="mx-auto max-w-5xl space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Supervisor marking rubric</h1>
        <p class="mt-1 text-sm text-slate-500">Import the criteria and score limits company supervisors use when evaluating students.</p>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <p class="font-bold">The rubric was not changed. Please correct the following:</p>
            <ul class="mt-1 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold">{{ $rubric['title'] }}</h2>
                <p class="mt-1 text-sm text-slate-600">{{ $rubric['scale_hint'] }}</p>
                <p class="mt-2 text-xs text-slate-500">
                    {{ $criterionCount }} criteria &middot; {{ $maxMarks }} available marks &middot;
                    Weighted at {{ rtrim(rtrim(number_format((float) $rubric['weight'], 2), '0'), '.') }}%
                </p>
            </div>
            <a href="/admin/supervisor-rubric/template" class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-4 text-sm font-semibold text-blue-700 hover:bg-blue-100">
                <i class="fa-solid fa-download"></i> Download Excel template
            </a>
        </div>

        <div class="mt-5 space-y-4">
            @foreach($rubric['sections'] as $section)
                <div class="overflow-hidden rounded-xl border border-slate-200">
                    <h3 class="bg-slate-50 px-4 py-3 text-sm font-bold">{{ $section['title'] }}</h3>
                    <div class="divide-y divide-slate-100">
                        @foreach($section['criteria'] as $criterion)
                            <div class="flex flex-wrap justify-between gap-2 px-4 py-3 text-sm">
                                <span class="font-medium">{{ $criterion['label'] }}</span>
                                <span class="text-slate-500">Max {{ $criterion['max'] ?? 5 }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-lg font-bold">Import a rubric</h2>
        <p class="mt-1 text-sm text-slate-600">
            Download the template, edit the rows, then upload it. Keep one row per criterion and preserve the heading row.
            Each criterion needs a unique key made from lowercase letters, numbers, and underscores.
        </p>
        <p class="mt-2 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">
            Importing affects new evaluations only. Existing evaluations keep their saved rubric, marks, and edit rules.
        </p>

        <form action="/admin/supervisor-rubric/import" method="POST" enctype="multipart/form-data" class="mt-4 flex flex-wrap items-end gap-3">
            @csrf
            <div class="min-w-[240px] flex-1">
                <label for="rubric-file" class="mb-1 block text-sm font-semibold text-slate-700">Excel or CSV file</label>
                <input id="rubric-file" type="file" name="file" required accept=".xlsx,.xls,.csv" class="w-full rounded-lg border border-slate-300 p-2 text-sm file:mr-3 file:rounded file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:font-semibold file:text-blue-700">
            </div>
            <button type="submit" class="min-h-10 rounded-lg bg-blue-600 px-5 text-sm font-semibold text-white hover:bg-blue-700">Import rubric</button>
        </form>
    </section>
</div>
@endsection
