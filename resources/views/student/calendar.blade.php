@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">{{ $month->format('F Y') }}</h2>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $monthLogs }} {{ \Illuminate\Support\Str::plural('entry', $monthLogs) }},
                    {{ $monthPresent }} {{ \Illuminate\Support\Str::plural('day', $monthPresent) }} present,
                    {{ $monthTasks }} open {{ \Illuminate\Support\Str::plural('task', $monthTasks) }} due
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="/student/calendar?month={{ $prevMonth }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50" aria-label="Previous month"><i class="fa-solid fa-chevron-left"></i></a>
                @unless($isCurrentMonth)
                    <a href="/student/calendar" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Today</a>
                @endunless
                <a href="/student/calendar?month={{ $nextMonth }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50" aria-label="Next month"><i class="fa-solid fa-chevron-right"></i></a>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-3 text-xs text-slate-500">
            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-blue-500"></span> Logbook entry</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-emerald-500"></span> Present</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-amber-500"></span> Leave</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-indigo-500"></span> Task due</span>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs">
        <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500 sm:text-xs">
            @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dayName)
                <div class="py-2">{{ $dayName }}</div>
            @endforeach
        </div>

        <div class="grid grid-cols-7">
            @foreach($weeks as $week)
                @foreach($week as $day)
                    @php
                        $attendance = $day['attendance'];
                        $cellClass = ! $day['in_month']
                            ? 'bg-slate-50/70 text-slate-300'
                            : ($day['is_weekend'] ? 'bg-slate-50' : 'bg-white');
                    @endphp
                    <div class="min-h-[4.5rem] border-b border-r border-slate-100 p-1 sm:min-h-[6.5rem] sm:p-2 {{ $cellClass }}">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold {{ $day['is_today'] ? 'bg-blue-600 text-white' : ($day['in_month'] ? 'text-slate-700' : 'text-slate-300') }}">{{ $day['date']->day }}</span>

                        @if($day['in_month'])
                            <div class="mt-1 space-y-1">
                                @if($day['logs'] > 0)
                                    <div class="flex items-center gap-1 rounded bg-blue-50 px-1 py-0.5 text-[10px] font-medium text-blue-700 sm:text-[11px]">
                                        <i class="fa-solid fa-book"></i>
                                        <span>{{ $day['logs'] }}<span class="hidden sm:inline"> {{ \Illuminate\Support\Str::plural('entry', $day['logs']) }}</span></span>
                                    </div>
                                @endif

                                @if($attendance)
                                    @if($attendance->status === 'Leave')
                                        <div class="flex items-center gap-1 rounded bg-amber-50 px-1 py-0.5 text-[10px] font-medium text-amber-700 sm:text-[11px]" title="{{ $attendance->note }}">
                                            <i class="fa-solid fa-umbrella-beach"></i>
                                            <span class="hidden sm:inline">Leave</span>
                                        </div>
                                    @else
                                        <div class="flex items-center gap-1 rounded bg-emerald-50 px-1 py-0.5 text-[10px] font-medium text-emerald-700 sm:text-[11px]">
                                            <i class="fa-solid fa-check"></i>
                                            <span class="hidden sm:inline">{{ $attendance->checkInLocal() ? 'In '.$attendance->checkInLocal() : 'Present' }}</span>
                                        </div>
                                    @endif
                                @endif

                                @if($day['tasks_open'] > 0)
                                    <div class="flex items-center gap-1 rounded bg-indigo-50 px-1 py-0.5 text-[10px] font-medium text-indigo-700 sm:text-[11px]">
                                        <i class="fa-solid fa-list-check"></i>
                                        <span>{{ $day['tasks_open'] }}<span class="hidden sm:inline"> {{ \Illuminate\Support\Str::plural('task', $day['tasks_open']) }}</span></span>
                                    </div>
                                @elseif($day['tasks_done'] > 0)
                                    <div class="flex items-center gap-1 rounded bg-slate-100 px-1 py-0.5 text-[10px] font-medium text-slate-500 sm:text-[11px]">
                                        <i class="fa-solid fa-list-check"></i>
                                        <span class="hidden sm:inline">Done</span>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            @endforeach
        </div>
    </div>
</div>
@endsection