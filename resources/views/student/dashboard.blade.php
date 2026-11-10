@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    .cal-day.is-today { color: #2F5BFF; background: #fff; box-shadow: inset 0 0 0 2px #2F5BFF; }
    .cal-day.is-sel { color: #fff; background: #2F5BFF; border-color: #2F5BFF; box-shadow: none; }
    .cal-day.is-sel .cal-dot { background: #fff !important; }
</style>

@php
    $pill = function (string $status) {
        if (in_array($status, ['Approved', 'Validated'], true)) {
            return 'bg-[#D1FAE5] text-[#065F46]';
        }
        if (in_array($status, ['Rejected', 'Revision Requested', 'Revision needed'], true)) {
            return 'bg-[#FFEDD5] text-[#9A3412]';
        }
        if ($status === 'Waiting for review') {
            return 'bg-[#E0E7FF] text-[#3730A3]';
        }
        if (in_array($status, ['Not sent yet', 'Not assigned'], true)) {
            return 'bg-[#EEF1F7] text-[#5B6478]';
        }
        return 'bg-[#FEF3C7] text-[#7C2D12]';
    };

    $chipClass = [
        'validated' => 'bg-[#D1FAE5] text-[#065F46]',
        'waiting' => 'bg-[#E0E7FF] text-[#3730A3]',
        'revision' => 'bg-[#FFEDD5] text-[#9A3412]',
        'current' => 'bg-[#2F5BFF] text-white',
        'incomplete' => 'bg-[#FFE4E6] text-[#9F1239]',
        'locked' => 'bg-[#EEF1F7] text-[#94A3B8]',
    ];

    $statusText = [
        'validated' => 'Validated',
        'waiting' => 'Waiting for review',
        'revision' => 'Revision needed',
        'current' => 'Current week',
        'incomplete' => 'Entries missing',
        'locked' => 'Not open yet',
    ];

    $todayKey = $today->toDateString();
    $supervisorAssigned = (bool) optional($student)->supervisor_id;
    $chatHref = $supervisorAssigned ? '/student/messages/supervisor' : '/student/messages';
    $firstName = \Illuminate\Support\Str::words((string) optional($student)->name, 2, '');
    $aiPct = $aiLimit > 0 ? (int) round($aiLeft / $aiLimit * 100) : 0;
    $aiColor = $aiLeft <= (int) ceil($aiLimit * 0.2) ? '#C2410C' : '#6D28D9';
    $card = 'rounded-[22px] border border-[#E3E8F2] bg-white shadow-[0_6px_18px_rgba(15,27,77,0.05)]';
    $firstMondayKey = $planner->hasStart() ? $planner->firstMonday()->toDateString() : '';
    $startKey = $planner->hasStart() ? $planner->start()->toDateString() : '';
    $workingDaysCsv = implode(',', $planner->workingDays());
@endphp

<div class="space-y-6" style="font-family:'Plus Jakarta Sans',ui-sans-serif,system-ui,sans-serif">

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
    @endif

    @if($errors->any() && ! old('day_off_type'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- {{-- REMINDER BANNER --}}
    <div id="reminder-slot" data-live>
        @if($banner['show'])
            <section id="reminder-banner" data-dismiss-key="elogbook.banner.{{ $student->student_id }}.{{ $todayKey }}" class="relative rounded-[22px] border border-[#FBCFCB] bg-gradient-to-r from-[#FFE9E7] to-[#FFF3F0] px-5 py-5 md:px-7">
                <button type="button" id="reminder-close" aria-label="Hide reminders for today" class="absolute right-3 top-3 flex h-8 w-8 items-center justify-center rounded-lg text-[#5B6478] transition hover:bg-white/70">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
                </button>
                <div class="flex flex-wrap items-center gap-x-6 gap-y-4 pr-8">
                    <div class="flex min-w-0 flex-[1_1_260px] items-center gap-4">
                        <span class="flex h-14 w-14 flex-none items-center justify-center rounded-full bg-[#FDBA3B]/30 text-3xl text-[#D97706]"><i class="fa-solid fa-bell"></i></span>
                        <div>
                            <span class="rounded-full bg-[#BE123C] px-2.5 py-0.5 text-[10px] font-extrabold tracking-wider text-white">REMINDER</span>
                            <h2 class="mt-1 text-xl font-extrabold leading-tight">You have pending tasks!</h2>
                            <p class="mt-0.5 text-[13px] text-[#5B6478]">Don't forget to complete these before they become overdue.</p>
                        </div>
                    </div>

                    <div class="flex min-w-0 flex-[3_1_520px] flex-wrap gap-3">
                        @if($banner['attendance'])
                            <div class="flex min-w-[200px] flex-1 flex-col gap-2 rounded-2xl border border-[#F3C4BF] bg-white p-3.5">
                                <div class="flex items-center gap-2.5">
                                    <span class="flex h-8 w-8 flex-none items-center justify-center rounded-[10px] bg-[#FFE4E6] text-[#BE123C]"><i class="fa-solid fa-clock text-sm"></i></span>
                                    <div class="min-w-0">
                                        <p class="text-sm font-extrabold">Attendance</p>
                                        <p class="text-xs text-[#5B6478]">Today's attendance is not yet marked.</p>
                                    </div>
                                </div>
                                <a href="#attendance-card" class="flex min-h-9 items-center justify-center rounded-xl bg-[#BE123C] text-sm font-bold text-white transition hover:bg-[#9F1239]">Mark now</a>
                            </div>
                        @endif

                        @if($banner['logbook'])
                            <div class="flex min-w-[200px] flex-1 flex-col gap-2 rounded-2xl border border-[#FDE68A] bg-white p-3.5">
                                <div class="flex items-center gap-2.5">
                                    <span class="flex h-8 w-8 flex-none items-center justify-center rounded-[10px] bg-[#FEF3C7] text-[#B45309]"><i class="fa-solid fa-file-pen text-sm"></i></span>
                                    <div class="min-w-0">
                                        <p class="text-sm font-extrabold">Logbook</p>
                                        <p class="text-xs text-[#5B6478]">Today's entry ({{ $today->format('D, d M') }}) is not written yet.</p>
                                    </div>
                                </div>
                                <a href="#new-entry" data-write-date="{{ $todayKey }}" class="flex min-h-9 items-center justify-center rounded-xl bg-[#B45309] text-sm font-bold text-white transition hover:bg-[#92400E]">Write entry</a>
                            </div>
                        @endif

                        @if($banner['tasks'] > 0)
                            <div class="flex min-w-[200px] flex-1 flex-col gap-2 rounded-2xl border border-[#C7D2FE] bg-white p-3.5">
                                <div class="flex items-center gap-2.5">
                                    <span class="flex h-8 w-8 flex-none items-center justify-center rounded-[10px] bg-[#E0E7FF] text-[#3730A3]"><i class="fa-solid fa-list-check text-sm"></i></span>
                                    <div class="min-w-0">
                                        <p class="text-sm font-extrabold">Task</p>
                                        <p class="text-xs text-[#5B6478]">{{ $banner['tasks'] }} {{ \Illuminate\Support\Str::plural('task', $banner['tasks']) }} due soon or overdue.</p>
                                    </div>
                                </div>
                                <a href="#todo-list" class="flex min-h-9 items-center justify-center rounded-xl bg-[#2F5BFF] text-sm font-bold text-white transition hover:bg-[#1F45D6]">View tasks</a>
                            </div>
                        @endif
                    </div>
                </div>
            </section>
        @endif
    </div> -->

    @include('partials.reminder-popup')

    @include('student.partials.dashboard-overview')
    @include('student.partials.dashboard-logbook')
</div>

@include('student.partials.dashboard-modals')

@endsection
