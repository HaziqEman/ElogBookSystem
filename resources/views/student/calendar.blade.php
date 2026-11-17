@extends('layouts.app')

@section('content')
    @php
        use Illuminate\Support\Str;
    @endphp

    <div class="space-y-5">
        {{-- Hero: month + quick stats + navigation --}}
        <div
            class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-blue-500 via-blue-600 to-indigo-600 p-5 text-white shadow-lg shadow-blue-900/10 md:p-8">
            <div
                class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-white/10"
                aria-hidden="true"
            ></div>
            <div
                class="pointer-events-none absolute -bottom-28 right-40 h-56 w-56 rounded-full bg-white/[0.07]"
                aria-hidden="true"
            ></div>

            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div
                        class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white/95 backdrop-blur">
                        <i class="fa-regular fa-calendar"></i> My calendar
                    </div>
                    <h2 class="mt-3 text-3xl font-extrabold tracking-tight md:text-4xl">
                        {{ $month->format('F Y') }}</h2>
                    <p class="mt-1.5 max-w-md text-sm text-blue-50/90">
                        Track your logbook entries, attendance and task deadlines in one place.
                    </p>
                </div>

                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                    {{-- Stats --}}
                    <div class="grid grid-cols-3 gap-2.5">
                        <div class="rounded-2xl bg-white/15 px-4 py-2.5 backdrop-blur">
                            <p class="text-2xl font-extrabold leading-none">{{ $monthLogs }}</p>
                            <p class="mt-1 text-[11px] font-medium text-blue-50/90">
                                {{ Str::plural('Entry', $monthLogs) }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/15 px-4 py-2.5 backdrop-blur">
                            <p class="text-2xl font-extrabold leading-none">{{ $monthPresent }}</p>
                            <p class="mt-1 text-[11px] font-medium text-blue-50/90">
                                {{ $monthPresent === 1 ? 'Day present' : 'Days present' }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/15 px-4 py-2.5 backdrop-blur">
                            <p class="text-2xl font-extrabold leading-none">{{ $monthTasks }}</p>
                            <p class="mt-1 text-[11px] font-medium text-blue-50/90">Open
                                {{ Str::plural('task', $monthTasks) }}</p>
                        </div>
                    </div>

                    {{-- Navigation --}}
                    <div class="flex items-center gap-2">
                        <a
                            href="/student/calendar?month={{ $prevMonth }}"
                            class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15 text-white backdrop-blur transition hover:bg-white/25"
                            aria-label="Previous month"
                        ><i class="fa-solid fa-chevron-left"></i></a>
                        @unless ($isCurrentMonth)
                            <a
                                href="/student/calendar"
                                class="flex h-11 items-center rounded-xl bg-white px-4 text-sm font-bold text-blue-700 shadow-sm transition hover:bg-blue-50"
                            >Today</a>
                        @endunless
                        <a
                            href="/student/calendar?month={{ $nextMonth }}"
                            class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15 text-white backdrop-blur transition hover:bg-white/25"
                            aria-label="Next month"
                        ><i class="fa-solid fa-chevron-right"></i></a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Calendar grid --}}
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div
                class="grid grid-cols-7 border-b border-slate-100 bg-slate-50/80 text-center text-xs font-bold text-slate-500">
                @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dayName)
                    <div class="{{ in_array($dayName, ['Sat', 'Sun']) ? 'text-slate-400' : '' }} py-3">
                        {{ $dayName }}</div>
                @endforeach
            </div>

            <div class="grid grid-cols-7">
                @foreach ($weeks as $week)
                    @foreach ($week as $day)
                        @php
                            $attendance = $day['attendance'];
                            $cellClass = !$day['in_month']
                                ? 'bg-slate-50/60'
                                : ($day['is_today']
                                    ? 'bg-blue-50/70'
                                    : ($day['is_weekend']
                                        ? 'bg-slate-50/50'
                                        : 'bg-white hover:bg-slate-50/60'));
                        @endphp
                        <div
                            class="{{ $cellClass }} relative min-h-[4.75rem] border-b border-r border-slate-100 p-1.5 transition sm:min-h-[7rem] sm:p-2.5">
                            @if ($day['is_today'])
                                <span
                                    class="absolute inset-x-0 top-0 h-0.5 bg-blue-600"
                                    aria-hidden="true"
                                ></span>
                            @endif

                            <span
                                class="{{ $day['is_today'] ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30' : ($day['in_month'] ? 'text-slate-700' : 'text-slate-300') }} inline-flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold sm:text-[13px]"
                            >{{ $day['date']->day }}</span>

                            @if ($day['in_month'])
                                <div class="mt-1.5 space-y-1">
                                    @if ($day['logs'] > 0)
                                        <div
                                            class="flex items-center gap-1.5 rounded-lg bg-blue-50 px-1.5 py-1 text-[10px] font-semibold text-blue-700 ring-1 ring-inset ring-blue-100 sm:text-[11px]">
                                            <i class="fa-solid fa-book text-[10px]"></i>
                                            <span class="truncate">{{ $day['logs'] }}<span
                                                    class="hidden sm:inline"
                                                >
                                                    {{ Str::plural('entry', $day['logs']) }}</span></span>
                                        </div>
                                    @endif

                                    @if ($attendance)
                                        @if ($attendance->status === \App\Models\Attendance::STATUS_LEAVE)
                                            <div
                                                class="flex items-center gap-1.5 rounded-lg bg-amber-50 px-1.5 py-1 text-[10px] font-semibold text-amber-700 ring-1 ring-inset ring-amber-100 sm:text-[11px]"
                                                title="{{ $attendance->note }}"
                                            >
                                                <i class="fa-solid fa-umbrella-beach text-[10px]"></i>
                                                <span class="hidden sm:inline">Leave</span>
                                            </div>
                                        @else
                                            <div
                                                class="flex items-center gap-1.5 rounded-lg bg-emerald-50 px-1.5 py-1 text-[10px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-100 sm:text-[11px]">
                                                <i class="fa-solid fa-circle-check text-[10px]"></i>
                                                <span
                                                    class="hidden truncate sm:inline">{{ $attendance->checkInLocal() ? 'In ' . $attendance->checkInLocal() : 'Present' }}</span>
                                            </div>
                                        @endif
                                    @endif

                                    @if ($day['tasks_open'] > 0)
                                        <div
                                            class="flex items-center gap-1.5 rounded-lg bg-indigo-50 px-1.5 py-1 text-[10px] font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-100 sm:text-[11px]">
                                            <i class="fa-solid fa-list-check text-[10px]"></i>
                                            <span class="truncate">{{ $day['tasks_open'] }}<span
                                                    class="hidden sm:inline"
                                                >
                                                    {{ Str::plural('task', $day['tasks_open']) }}</span></span>
                                        </div>
                                    @elseif($day['tasks_done'] > 0)
                                        <div
                                            class="flex items-center gap-1.5 rounded-lg bg-slate-100 px-1.5 py-1 text-[10px] font-semibold text-slate-500 sm:text-[11px]">
                                            <i class="fa-solid fa-list-check text-[10px]"></i>
                                            <span class="hidden sm:inline">Done</span>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                @endforeach
            </div>

            {{-- Legend --}}
            <div
                class="flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-slate-100 bg-slate-50/60 px-5 py-3.5 text-xs font-medium text-slate-500">
                <span class="inline-flex items-center gap-2"><span
                        class="h-2.5 w-2.5 rounded-full bg-blue-500"
                    ></span> Logbook entry</span>
                <span class="inline-flex items-center gap-2"><span
                        class="h-2.5 w-2.5 rounded-full bg-emerald-500"
                    ></span> Present</span>
                <span class="inline-flex items-center gap-2"><span
                        class="h-2.5 w-2.5 rounded-full bg-amber-500"
                    ></span> Leave</span>
                <span class="inline-flex items-center gap-2"><span
                        class="h-2.5 w-2.5 rounded-full bg-indigo-500"
                    ></span> Task due</span>
            </div>
        </div>
    </div>
@endsection
