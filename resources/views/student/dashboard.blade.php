@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

@php
    $fmtDate = function ($value) {
        if (! $value) {
            return '-';
        }
        try {
            return \Carbon\Carbon::parse($value)->format('d M Y');
        } catch (\Throwable $e) {
            return '-';
        }
    };

    $pill = function (string $status) {
        if (in_array($status, ['Approved', 'Validated'], true)) {
            return 'bg-[#D1FAE5] text-[#065F46]';
        }
        if (in_array($status, ['Rejected', 'Revision Requested'], true)) {
            return 'bg-[#FFE4E6] text-[#9F1239]';
        }
        return 'bg-[#FEF3C7] text-[#7C2D12]';
    };

    $todayKey = $today->toDateString();
    $supervisorAssigned = (bool) optional($student)->supervisor_id;
    $chatHref = $supervisorAssigned ? '/student/messages/supervisor' : '/student/messages';
    $firstName = \Illuminate\Support\Str::words((string) optional($student)->name, 2, '');

    $hasIn = $attendance && $attendance->check_in;
    $hasOut = $attendance && $attendance->check_out;
    $onLeave = $attendance && $attendance->status === 'Leave';

    if ($hasOut) {
        $statusText = 'Day complete';
        $statusClass = 'bg-[#E0E7FF] text-[#3730A3]';
    } elseif ($hasIn) {
        $statusText = 'Present';
        $statusClass = 'bg-[#D1FAE5] text-[#065F46]';
    } elseif ($onLeave) {
        $statusText = 'On leave';
        $statusClass = 'bg-[#FEF3C7] text-[#7C2D12]';
    } else {
        $statusText = 'Not clocked in';
        $statusClass = 'bg-[#FEF3C7] text-[#7C2D12]';
    }

    $aiPct = $aiLimit > 0 ? (int) round($aiLeft / $aiLimit * 100) : 0;
    $aiColor = $aiLeft <= (int) ceil($aiLimit * 0.2) ? '#C2410C' : '#6D28D9';

    $card = 'rounded-[22px] border border-[#E3E8F2] bg-white shadow-[0_6px_18px_rgba(15,27,77,0.05)]';
@endphp

<div class="space-y-6" style="font-family:'Plus Jakarta Sans',ui-sans-serif,system-ui,sans-serif">

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- HERO --}}
    <section class="flex flex-wrap items-center justify-between gap-3 overflow-hidden rounded-3xl bg-gradient-to-r from-[#2F5BFF] to-[#4F46E5] px-6 py-7 text-white md:px-9">
        <div class="min-w-0 flex-1 basis-72">
            <div class="text-[13px] font-medium text-[#DCE4FF]">{{ $today->format('l, d F Y') }}</div>
            <h1 class="mb-2 mt-1.5 break-words text-3xl font-extrabold tracking-tight">Welcome back, {{ $firstName }}!</h1>
            <p class="max-w-md text-sm leading-relaxed text-[#E4EAFF]">Keep up the great work. Stay consistent with your logbook, attend your sessions and make the most of your internship journey.</p>
        </div>
        <div class="hidden shrink-0 items-center gap-1.5 md:flex">
            <div class="text-right text-[28px] leading-[1.05] text-white" style="font-family:'Caveat',cursive;transform:rotate(-6deg)">Better<br>skills,<br>brighter<br>future</div>
            <svg width="200" height="136" viewBox="0 0 190 130" aria-hidden="true">
                <ellipse cx="95" cy="118" rx="80" ry="8" fill="#1B2A7A" opacity=".45"/>
                <rect x="38" y="30" width="96" height="62" rx="7" fill="#0F1B4D"/>
                <rect x="44" y="36" width="84" height="50" rx="4" fill="#5B8CFF"/>
                <circle cx="86" cy="61" r="9" fill="#fff" opacity=".9"/>
                <path d="M24 94h124l-8 12H32z" fill="#C7D4FF"/>
                <rect x="146" y="88" width="30" height="28" rx="6" fill="#EEF2FF"/>
                <path d="M161 88c-10-8-12-20-6-30 6 10 14 12 8 30zM161 88c10-6 18-4 20-14-10 0-18 6-20 14z" fill="#34D399"/>
                <rect x="12" y="84" width="22" height="26" rx="5" fill="#0F1B4D"/>
                <rect x="16" y="90" width="14" height="4" rx="2" fill="#7FA1FF"/>
            </svg>
        </div>
    </section>

    {{-- ROW 2: ATTENDANCE + STATS | QUICK ACTIONS + AI --}}
    <section class="flex flex-wrap items-stretch gap-5">
        <div class="flex min-w-0 flex-[3_1_520px] flex-col gap-4">

            {{-- Attendance strip --}}
            @if($extrasReady)
                <div class="flex flex-wrap items-center gap-x-7 gap-y-4 px-[22px] py-5 {{ $card }}">
                    <div class="flex min-w-0 flex-[1_1_200px] flex-col gap-2">
                        <div class="flex items-center gap-2.5">
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#E8EFFF] text-[#1F45D6]">
                                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                            </span>
                            <h2 class="text-base font-extrabold">Today's attendance</h2>
                        </div>
                        <div class="flex flex-wrap items-center gap-2.5">
                            <span class="whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-bold {{ $statusClass }}">{{ $statusText }}</span>
                            <span class="text-[13px] text-[#5B6478]">This month: {{ $monthPresent }} present, {{ $monthLeave }} leave</span>
                        </div>
                        @if($onLeave && ! $hasIn)
                            <p class="text-xs text-[#7C2D12]">Leave: {{ $attendance->note }}</p>
                        @endif
                    </div>

                    <div class="flex gap-7">
                        <div>
                            <div class="text-xs text-[#5B6478]">Clock in</div>
                            <div class="text-2xl font-extrabold">{{ $hasIn ? $attendance->checkInLocal() : '--:--' }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-[#5B6478]">Clock out</div>
                            <div class="text-2xl font-extrabold">{{ $hasOut ? $attendance->checkOutLocal() : '--:--' }}</div>
                        </div>
                    </div>

                    <div class="flex min-w-0 flex-[1_1_200px] flex-col gap-1.5">
                        @if($hasOut)
                            <button type="button" disabled class="min-h-12 cursor-not-allowed rounded-[14px] bg-[#64748B] text-[15px] font-bold text-white">Done for today</button>
                        @elseif($hasIn)
                            <form method="POST" action="/student/attendance/clock-out" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                                @csrf
                                <button type="submit" class="min-h-12 w-full rounded-[14px] bg-[#334155] text-[15px] font-bold text-white transition hover:bg-[#1E293B] disabled:opacity-60">Clock out</button>
                            </form>
                        @else
                            <form method="POST" action="/student/attendance/clock-in" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                                @csrf
                                <button type="submit" class="min-h-12 w-full rounded-[14px] bg-[#047857] text-[15px] font-bold text-white transition hover:bg-[#065F46] disabled:opacity-60">{{ $onLeave ? 'Clock in anyway' : 'Clock in' }}</button>
                            </form>
                        @endif

                        @unless($hasIn)
                            <details class="text-center">
                                <summary class="flex min-h-8 cursor-pointer list-none items-center justify-center text-[13px] font-semibold text-[#1F45D6] [&::-webkit-details-marker]:hidden">Mark a day as leave</summary>
                                <form method="POST" action="/student/attendance/leave" class="mt-2 space-y-2 text-left">
                                    @csrf
                                    <input type="date" name="leave_date" value="{{ $todayKey }}" required class="w-full rounded-xl border border-[#CBD3E3] p-2 text-sm focus:border-[#2F5BFF] focus:outline-none">
                                    <input type="text" name="note" maxlength="255" required placeholder="Reason, e.g. medical leave" class="w-full rounded-xl border border-[#CBD3E3] p-2 text-sm focus:border-[#2F5BFF] focus:outline-none">
                                    <button type="submit" class="w-full rounded-xl bg-amber-500 px-3 py-2 text-sm font-bold text-slate-900 transition hover:bg-amber-600">Save leave</button>
                                </form>
                            </details>
                        @endunless
                    </div>
                </div>
            @else
                <div class="rounded-[22px] border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800">
                    Attendance and to-do tables are not set up yet. Ask the administrator to run the database update.
                </div>
            @endif

            {{-- Stats 2x2 --}}
            <div class="grid flex-1 grid-cols-2 gap-3.5">
                <div class="flex items-center gap-3.5 rounded-[20px] border border-[#D3E0FF] bg-[#E8EFFF] px-[18px] py-4">
                    <span class="flex h-[46px] w-[46px] flex-none items-center justify-center rounded-[15px] bg-white text-[#1F45D6]">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 3h9l4 4v14H6z"/><path d="M9 12h7M9 16h7"/></svg>
                    </span>
                    <div><div class="text-xs font-semibold text-[#334155]">Total entries</div><div class="text-[28px] font-extrabold leading-tight">{{ $totalLogs }}</div></div>
                </div>
                <div class="flex items-center gap-3.5 rounded-[20px] border border-[#BFEBD3] bg-[#E3F7EC] px-[18px] py-4">
                    <span class="flex h-[46px] w-[46px] flex-none items-center justify-center rounded-[15px] bg-white text-[#047857]">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12l-8.5 8.5a5 5 0 0 1-7-7L14 5a3.3 3.3 0 0 1 4.7 4.7L10.2 18.2a1.7 1.7 0 0 1-2.4-2.4L15 8.5"/></svg>
                    </span>
                    <div><div class="text-xs font-semibold text-[#334155]">Files uploaded</div><div class="text-[28px] font-extrabold leading-tight">{{ $totalAttachments }}</div></div>
                </div>
                <a href="/student/feedback" class="flex items-center gap-3.5 rounded-[20px] border border-[#DDD0FB] bg-[#F0EAFF] px-[18px] py-4 transition hover:border-[#6D28D9]">
                    <span class="flex h-[46px] w-[46px] flex-none items-center justify-center rounded-[15px] bg-white text-[#6D28D9]">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.5 7.2L4 20l1-4.5A8 8 0 1 1 21 12z"/><path d="M8.5 12h.01M12 12h.01M15.5 12h.01"/></svg>
                    </span>
                    <div><div class="text-xs font-semibold text-[#334155]">Reviews</div><div class="text-[28px] font-extrabold leading-tight">{{ $totalReviews }}</div></div>
                </a>
                <a href="/student/messages" class="flex items-center gap-3.5 rounded-[20px] border border-[#FBCFCB] bg-[#FFE9E7] px-[18px] py-4 transition hover:border-[#BE123C]">
                    <span class="flex h-[46px] w-[46px] flex-none items-center justify-center rounded-[15px] bg-white text-[#BE123C]">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>
                    </span>
                    <div><div class="text-xs font-semibold text-[#334155]">Unread messages</div><div class="text-[28px] font-extrabold leading-tight">{{ $unreadMessages }}</div></div>
                </a>
            </div>
        </div>

        {{-- Right: quick actions + AI card --}}
        <div class="flex min-w-0 flex-[2_1_340px] flex-col gap-4">
            <div class="p-5 {{ $card }}">
                <h2 class="mb-3.5 text-base font-extrabold">Quick actions</h2>
                <div class="grid grid-cols-2 gap-3">
                    <a href="#new-entry" class="flex min-h-[86px] flex-col gap-2 rounded-[18px] border border-[#D3E0FF] bg-[#E8EFFF] p-3.5 transition hover:border-[#2F5BFF]">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#2F5BFF] text-white"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg></span>
                        <span class="text-[13px] font-bold leading-tight">New logbook entry</span>
                    </a>
                    <a href="/student/calendar" class="flex min-h-[86px] flex-col gap-2 rounded-[18px] border border-[#BFEBD3] bg-[#E3F7EC] p-3.5 transition hover:border-[#047857]">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#047857] text-white"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg></span>
                        <span class="text-[13px] font-bold leading-tight">View calendar</span>
                    </a>
                    <a href="#new-entry" data-focus="attachment" class="flex min-h-[86px] flex-col gap-2 rounded-[18px] border border-[#DDD0FB] bg-[#F0EAFF] p-3.5 transition hover:border-[#6D28D9]">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#6D28D9] text-white"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V4M7 9l5-5 5 5"/><path d="M4 16v4h16v-4"/></svg></span>
                        <span class="text-[13px] font-bold leading-tight">Upload files</span>
                    </a>
                    <a href="{{ $chatHref }}" class="flex min-h-[86px] flex-col gap-2 rounded-[18px] border border-[#FBCFCB] bg-[#FFE9E7] p-3.5 transition hover:border-[#BE123C]">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#BE123C] text-white"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.5 7.2L4 20l1-4.5A8 8 0 1 1 21 12z"/></svg></span>
                        <span class="text-[13px] font-bold leading-tight">Chat with supervisor</span>
                    </a>
                </div>
            </div>

            @if($aiReady)
                <div class="flex flex-col gap-2 rounded-[20px] border border-[#E3E8F2] bg-white px-4 py-3.5 shadow-[0_6px_18px_rgba(15,27,77,0.05)]">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-8 w-8 flex-none items-center justify-center rounded-[10px] bg-[#F0EAFF] text-[#6D28D9]"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l1.8 4.7L18.5 9.5l-4.7 1.8L12 16l-1.8-4.7L5.5 9.5l4.7-1.8z"/></svg></span>
                        <div class="min-w-0 flex-1 text-sm font-extrabold">AI Help &middot; Gemini</div>
                        <div class="whitespace-nowrap text-[13px] font-bold text-[#334155]"><span id="ai-left" data-limit="{{ $aiLimit }}" class="text-base font-extrabold" style="color:{{ $aiColor }}">{{ $aiLeft }}</span> / {{ $aiLimit }} left</div>
                    </div>
                    <div class="h-2 overflow-hidden rounded-[5px] bg-[#EEE9FB]">
                        <div id="ai-bar" class="h-full rounded-[5px]" style="width:{{ $aiPct }}%;background:{{ $aiColor }}"></div>
                    </div>
                    <div class="text-xs text-[#5B6478]">Resets every day at midnight</div>
                </div>
            @endif
        </div>
    </section>

    {{-- ROW 3: CALENDAR + TO-DO --}}
    <section class="flex flex-wrap items-stretch gap-5">
        <div class="min-w-0 flex-[3_1_520px] px-[22px] py-5 {{ $card }}">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-base font-extrabold">My calendar</h2>
                <div class="flex items-center gap-2.5">
                    <a href="/student/calendar?month={{ $calendar['prev'] }}" aria-label="Previous month" class="flex h-9 w-9 items-center justify-center rounded-[10px] border border-[#E3E8F2] bg-white text-[#0F172A] transition hover:bg-[#F3F5FC]">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>
                    </a>
                    <div class="min-w-[110px] text-center text-[15px] font-extrabold">{{ $calendar['title'] }}</div>
                    <a href="/student/calendar?month={{ $calendar['next'] }}" aria-label="Next month" class="flex h-9 w-9 items-center justify-center rounded-[10px] border border-[#E3E8F2] bg-white text-[#0F172A] transition hover:bg-[#F3F5FC]">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
                    </a>
                </div>
                <a href="/student/calendar" class="flex min-h-8 items-center text-[13px] font-bold text-[#1F45D6] hover:underline">Full calendar</a>
            </div>

            <div class="mb-1 grid grid-cols-7 gap-1 text-center text-[11px] font-bold text-[#5B6478]">
                <div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div><div>Sun</div>
            </div>
            <div class="grid grid-cols-7 gap-1">
                @foreach($calendar['cells'] as $cell)
                    @php
                        $cellClass = $cell['is_today']
                            ? 'bg-[#2F5BFF] font-extrabold text-white'
                            : ($cell['in_month'] ? 'bg-[#F7F9FD] text-[#1E293B]' : 'text-[#94A3B8]');

                        $dotClass = 'bg-transparent';
                        if ($cell['in_month'] && ($cell['has_log'] || $cell['has_due'])) {
                            $dotClass = $cell['is_today'] ? 'bg-white' : ($cell['has_log'] ? 'bg-[#2F5BFF]' : 'bg-[#C2410C]');
                        }
                    @endphp
                    <div class="flex min-h-[46px] flex-col items-center justify-center gap-[3px] rounded-[14px] text-[13px] font-semibold {{ $cellClass }}">
                        <span>{{ $cell['label'] }}</span>
                        <span class="h-[5px] w-[5px] rounded-full {{ $dotClass }}"></span>
                    </div>
                @endforeach
            </div>

            <div class="mt-3 flex flex-wrap gap-3.5 text-xs text-[#5B6478]">
                <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-[#2F5BFF]"></span>Log entry</span>
                <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-[#C2410C]"></span>Task due</span>
            </div>
        </div>

        <div class="min-w-0 flex-[2_1_340px] px-[22px] py-5 {{ $card }}">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-base font-extrabold">To-do list</h2>
                <span class="text-xs font-bold text-[#5B6478]">{{ $openCount }} open</span>
            </div>

            @if($extrasReady)
                <form method="POST" action="/student/todos" class="mb-2 space-y-2">
                    @csrf
                    <div class="flex gap-2">
                        <input type="text" name="title" required maxlength="150" placeholder="Add a task" aria-label="New task" class="min-h-11 min-w-0 flex-1 rounded-xl border border-[#CBD3E3] px-3 text-sm focus:border-[#2F5BFF] focus:outline-none">
                        <button type="submit" class="min-h-11 rounded-xl bg-[#2F5BFF] px-4 text-sm font-bold text-white transition hover:bg-[#1F45D6]">Add</button>
                    </div>
                    <input type="date" name="due_date" aria-label="Due date (optional)" class="w-full rounded-xl border border-[#E3E8F2] px-3 py-2 text-xs text-[#5B6478] focus:border-[#2F5BFF] focus:outline-none">
                </form>

                @forelse($openTodos as $todo)
                    @php
                        $due = $todo->due_date ? $todo->due_date->toDateString() : null;
                        $overdue = $due && $due < $todayKey;
                    @endphp
                    <div class="flex min-h-12 items-center gap-3 border-t border-[#EDF0F7]">
                        <form method="POST" action="/student/todos/{{ $todo->todo_id }}/toggle">
                            @csrf
                            <button type="submit" aria-label="Mark as done" class="flex h-6 w-6 flex-none items-center justify-center rounded-[7px] border-2 border-[#94A3B8] bg-white text-transparent transition hover:border-[#047857] hover:text-[#047857]">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5L20 7"/></svg>
                            </button>
                        </form>
                        <div class="min-w-0 flex-1 break-words text-sm font-medium">{{ $todo->title }}</div>
                        @if($due)
                            <div class="whitespace-nowrap text-xs font-semibold {{ $overdue ? 'text-[#BE123C]' : 'text-[#5B6478]' }}">{{ $overdue ? 'Overdue ' : '' }}{{ $due === $todayKey ? 'Today' : $todo->due_date->format('d M') }}</div>
                        @endif
                        <form method="POST" action="/student/todos/{{ $todo->todo_id }}" onsubmit="return confirm('Delete this task?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" aria-label="Delete task" class="text-[#CBD3E3] transition hover:text-[#BE123C]">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
                            </button>
                        </form>
                    </div>
                @empty
                    <p class="border-t border-[#EDF0F7] pt-3 text-sm text-[#5B6478]">No open tasks. Add one above.</p>
                @endforelse

                @if($openCount > $openTodos->count())
                    <p class="border-t border-[#EDF0F7] pt-2 text-xs text-[#5B6478]">+ {{ $openCount - $openTodos->count() }} more open tasks</p>
                @endif

                @if($doneCount > 0)
                    <details class="border-t border-[#EDF0F7] pt-2">
                        <summary class="cursor-pointer text-xs font-bold text-[#5B6478]">Completed ({{ $doneCount }})</summary>
                        @foreach($doneTodos as $todo)
                            <div class="flex min-h-11 items-center gap-3">
                                <form method="POST" action="/student/todos/{{ $todo->todo_id }}/toggle">
                                    @csrf
                                    <button type="submit" aria-label="Mark as not done" class="flex h-6 w-6 flex-none items-center justify-center rounded-[7px] bg-[#047857] text-white">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5L20 7"/></svg>
                                    </button>
                                </form>
                                <div class="min-w-0 flex-1 break-words text-sm font-medium text-[#64748B] line-through">{{ $todo->title }}</div>
                                <form method="POST" action="/student/todos/{{ $todo->todo_id }}" onsubmit="return confirm('Delete this task?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" aria-label="Delete task" class="text-[#CBD3E3] transition hover:text-[#BE123C]">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </details>
                @endif
            @else
                <p class="text-sm text-amber-800">The to-do table is not set up yet.</p>
            @endif
        </div>
    </section>

    {{-- NEW ENTRY (opens from the quick actions) --}}
    <details id="new-entry" class="{{ $card }}" {{ ($errors->any() || old('description') || old('week_no') || old('activity_date')) ? 'open' : '' }}>
        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-7 py-5 [&::-webkit-details-marker]:hidden">
            <div>
                <h2 class="text-xl font-extrabold">New logbook entry</h2>
                <p class="mt-0.5 text-[13px] text-[#5B6478]">Write today's work, get a draft from AI Help, and attach a file.</p>
            </div>
            <span class="flex h-9 w-9 flex-none items-center justify-center rounded-xl bg-[#E8EFFF] text-[#1F45D6]"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg></span>
        </summary>

        <form id="logbook-form" action="/student/logbook/store" method="POST" enctype="multipart/form-data" class="space-y-3 border-t border-[#EDF0F7] px-7 pb-6 pt-5">
            @csrf
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#334155]">Date of Training</label>
                    <input type="date" name="activity_date" value="{{ old('activity_date') }}" class="w-full rounded-xl border border-[#CBD3E3] p-2.5 text-sm focus:border-[#2F5BFF] focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#334155]">Week Number</label>
                    <input type="number" name="week_no" value="{{ old('week_no') }}" class="w-full rounded-xl border border-[#CBD3E3] p-2.5 text-sm focus:border-[#2F5BFF] focus:outline-none">
                </div>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-[#334155]">Activity Description</label>
                <div class="mb-2 flex items-center justify-between gap-2">
                    <span class="text-[11px] text-[#5B6478]">Need help drafting a polished entry?</span>
                    <button type="button" id="ai-help-btn" class="shrink-0 rounded-lg border border-[#D3E0FF] bg-[#E8EFFF] px-3 py-1.5 text-xs font-bold text-[#1F45D6] transition hover:bg-[#D3E0FF] disabled:cursor-not-allowed disabled:opacity-60">
                        <i class="fa-solid fa-wand-magic-sparkles"></i> <span id="ai-help-label">AI Help</span>
                    </button>
                </div>
                <textarea id="description-field" rows="5" name="description" placeholder="Detail tasks performed..." class="w-full rounded-xl border border-[#CBD3E3] p-2.5 text-sm focus:border-[#2F5BFF] focus:outline-none">{{ old('description') }}</textarea>
                <p id="ai-help-status" class="mt-2 text-xs text-[#5B6478]"></p>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-[#334155]">Upload Attachment (image, PDF or Word, max 5 MB)</label>
                <input type="file" name="attachment" class="w-full rounded-xl border border-[#CBD3E3] p-2.5 text-sm file:mr-3 file:rounded file:border-0 file:bg-[#E8EFFF] file:px-3 file:py-2 file:text-sm file:font-bold file:text-[#1F45D6]">
            </div>
            <button id="submit-entry-btn" type="submit" class="w-full rounded-xl bg-[#2F5BFF] p-3 text-sm font-bold text-white shadow-sm transition hover:bg-[#1F45D6] disabled:cursor-not-allowed disabled:opacity-60">Submit Entry</button>
        </form>
    </details>

    {{-- ROW 4: RECENT LOGBOOK ENTRIES --}}
    <section class="px-7 py-6 {{ $card }}">
        <div class="mb-2 flex flex-wrap items-center justify-between gap-3.5">
            <div>
                <h2 class="text-xl font-extrabold">Recent logbook entries</h2>
                <div class="mt-0.5 text-[13px] text-[#5B6478]">Your latest work, with a preview of each attached file</div>
            </div>
            <div id="entry-filters" class="flex flex-wrap items-center gap-1.5">
                <button type="button" data-filter="all" class="entry-chip min-h-[38px] rounded-full border px-3.5 text-[13px] font-semibold">All</button>
                <button type="button" data-filter="pending" class="entry-chip min-h-[38px] rounded-full border px-3.5 text-[13px] font-semibold">Pending</button>
                <button type="button" data-filter="validated" class="entry-chip min-h-[38px] rounded-full border px-3.5 text-[13px] font-semibold">Validated</button>
                <button type="button" data-filter="action" class="entry-chip min-h-[38px] rounded-full border px-3.5 text-[13px] font-semibold">Needs action</button>
            </div>
        </div>

        @forelse($logbooks as $logbook)
            @php
                $lec = $logbook->status ?? 'Pending';
                $sup = $logbook->supervisor_status ?? 'Pending';
                $file = $logbook->attachments->first();
                $moreFiles = max($logbook->attachments->count() - 1, 0);
                $needs = $logbook->needsRevision();
                $approved = $lec === 'Approved' && (! $supervisorAssigned || $sup === 'Validated');
                $stateText = $needs ? 'Needs revision' : ($approved ? 'Approved' : 'Submitted');
                $stateClass = $needs ? 'bg-[#FFE4E6] text-[#9F1239]' : ($approved ? 'bg-[#D1FAE5] text-[#065F46]' : 'bg-[#E0E7FF] text-[#3730A3]');
                $filterKey = $needs ? 'action' : (($lec === 'Approved' || $sup === 'Validated') ? 'validated' : 'pending');
            @endphp
            <article data-state="{{ $filterKey }}" class="entry-row mt-3.5 flex flex-wrap gap-5 rounded-[20px] border border-[#E7ECF6] bg-[#FCFDFF] p-[18px]">
                @include('student.partials.file-preview', ['file' => $file])

                <div class="flex min-w-0 flex-[1_1_320px] flex-col gap-2">
                    <div class="flex flex-wrap items-center justify-between gap-2.5">
                        <div class="flex items-center gap-2.5">
                            <span class="rounded-full bg-[#EAF0FF] px-3 py-1 text-[13px] font-extrabold text-[#1F45D6]">Week {{ $logbook->week_no }}</span>
                            <span class="text-[13px] font-semibold text-[#5B6478]">{{ $fmtDate($logbook->activity_date) }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $stateClass }}">{{ $stateText }}</span>
                            @if($logbook->isEditableByStudent())
                                <a href="/student/logbook/edit/{{ $logbook->logbook_id }}" aria-label="{{ $needs ? 'Revise entry' : 'Edit entry' }}" title="{{ $needs ? 'Revise entry' : 'Edit entry' }}" class="flex h-[38px] w-[38px] items-center justify-center rounded-[11px] border transition {{ $needs ? 'border-[#BE123C] bg-[#BE123C] text-white hover:bg-[#9F1239]' : 'border-[#E3E8F2] bg-white text-[#334155] hover:bg-[#F3F5FC]' }}">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                                </a>
                            @else
                                <span title="Approved, so it is locked" class="flex h-[38px] w-[38px] items-center justify-center rounded-[11px] border border-[#E3E8F2] bg-[#F7F9FD] text-[#94A3B8]"><i class="fa-solid fa-lock text-sm"></i></span>
                            @endif
                        </div>
                    </div>

                    <p class="line-clamp-3 break-words text-sm leading-relaxed text-[#334155]">{{ $logbook->description }}</p>

                    <div class="mt-auto flex flex-wrap items-center gap-2">
                        @if($file)
                            <a href="{{ $file->url }}" target="_blank" rel="noopener" title="{{ $file->file_name }}" class="flex min-h-8 max-w-[260px] items-center gap-1.5 rounded-[9px] bg-[#EEF2FB] px-2.5 text-xs font-medium text-[#1E293B] transition hover:bg-[#E1E8F8]">
                                <svg class="flex-none" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12l-8.5 8.5a5 5 0 0 1-7-7L14 5a3.3 3.3 0 0 1 4.7 4.7L10.2 18.2a1.7 1.7 0 0 1-2.4-2.4L15 8.5"/></svg>
                                <span class="truncate">{{ $file->file_name }}</span>
                            </a>
                            <a href="{{ $file->url }}" target="_blank" rel="noopener" class="flex min-h-8 items-center rounded-[9px] border border-[#CBD3E3] bg-white px-3 text-xs font-bold text-[#1F45D6] transition hover:bg-[#F3F5FC]">Open file</a>
                            @if($moreFiles > 0)
                                <span class="text-xs text-[#5B6478]">+{{ $moreFiles }} more</span>
                            @endif
                        @elseif($logbook->isEditableByStudent())
                            <a href="/student/logbook/edit/{{ $logbook->logbook_id }}" class="flex min-h-8 items-center gap-1.5 rounded-[9px] border border-dashed border-[#9DB4FF] bg-[#F5F7FF] px-3 text-xs font-bold text-[#1F45D6] transition hover:bg-[#E8EFFF]">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                                Add a file
                            </a>
                        @endif

                        <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $pill($lec) }}">Lecturer: {{ $lec }}</span>
                        @if($supervisorAssigned)
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $pill($sup) }}">Supervisor: {{ $sup }}</span>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <div class="mt-3.5 rounded-[20px] border border-dashed border-[#C3CCEA] bg-[#F5F7FF] p-8 text-center text-sm text-[#5B6478]">
                No logbook entries yet. Open "New logbook entry" above to write your first one.
            </div>
        @endforelse

        <div id="entry-empty" class="mt-3.5 hidden rounded-[20px] border border-dashed border-[#C3CCEA] bg-[#F5F7FF] p-6 text-center text-sm text-[#5B6478]">No entries in this category.</div>

        @if($totalLogs > $logbooks->count())
            <div class="mt-4 flex justify-end">
                <a href="/student/logbooks?all=1" class="flex min-h-9 items-center gap-1.5 text-sm font-bold text-[#1F45D6] hover:underline">
                    View all {{ $totalLogs }} entries
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </a>
            </div>
        @endif
    </section>
</div>

<script>
(function () {
    if (window.__dashboardBound) return;
    window.__dashboardBound = true;

    var COOLDOWN_SECONDS = 10;

    // Open the "New logbook entry" panel from the quick actions.
    function openEntry(focusName) {
        var panel = document.getElementById('new-entry');
        if (!panel) return;
        panel.open = true;
        panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        if (focusName) {
            var el = panel.querySelector('[name="' + focusName + '"]');
            if (el) setTimeout(function () { el.focus(); }, 350);
        }
    }

    document.querySelectorAll('a[href="#new-entry"]').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            openEntry(link.getAttribute('data-focus'));
        });
    });
    if (location.hash === '#new-entry') openEntry();

    // Filter chips for the entry list.
    var chips = document.querySelectorAll('.entry-chip');
    var rows = document.querySelectorAll('.entry-row');
    var emptyBox = document.getElementById('entry-empty');
    var activeClass = ['bg-[#2F5BFF]', 'border-[#2F5BFF]', 'text-white'];
    var idleClass = ['bg-white', 'border-[#CBD3E3]', 'text-[#1E293B]'];

    function applyFilter(filter) {
        var visible = 0;
        rows.forEach(function (row) {
            var show = filter === 'all' || row.getAttribute('data-state') === filter;
            row.classList.toggle('hidden', !show);
            if (show) visible += 1;
        });
        if (emptyBox && rows.length) emptyBox.classList.toggle('hidden', visible !== 0);

        chips.forEach(function (chip) {
            var isActive = chip.getAttribute('data-filter') === filter;
            activeClass.forEach(function (c) { chip.classList.toggle(c, isActive); });
            idleClass.forEach(function (c) { chip.classList.toggle(c, !isActive); });
        });
    }

    chips.forEach(function (chip) {
        chip.addEventListener('click', function () { applyFilter(chip.getAttribute('data-filter')); });
    });
    applyFilter('all');

    // AI Help
    function updateMeter(left) {
        var leftEl = document.getElementById('ai-left');
        var bar = document.getElementById('ai-bar');
        if (!leftEl || !bar || isNaN(left)) return;

        var limit = parseInt(leftEl.getAttribute('data-limit'), 10) || 10;
        left = Math.max(0, Math.min(limit, left));
        var color = left <= Math.ceil(limit * 0.2) ? '#C2410C' : '#6D28D9';

        leftEl.textContent = left;
        leftEl.style.color = color;
        bar.style.width = Math.round(left / limit * 100) + '%';
        bar.style.background = color;
    }

    function initAi() {
        var btn = document.getElementById('ai-help-btn');
        var label = document.getElementById('ai-help-label');
        var field = document.getElementById('description-field');
        var status = document.getElementById('ai-help-status');
        var form = document.getElementById('logbook-form');
        var submitBtn = document.getElementById('submit-entry-btn');
        if (!btn || !field || !status) return;

        var busy = false;

        function setStatus(text, color) {
            status.textContent = text;
            status.style.color = color;
        }

        function startCooldown() {
            var left = COOLDOWN_SECONDS;
            btn.disabled = true;
            label.textContent = 'Wait ' + left + 's';
            var timer = setInterval(function () {
                left -= 1;
                if (left <= 0) {
                    clearInterval(timer);
                    btn.disabled = false;
                    label.textContent = 'AI Help';
                } else {
                    label.textContent = 'Wait ' + left + 's';
                }
            }, 1000);
        }

        btn.addEventListener('click', async function (e) {
            e.preventDefault();
            if (busy || btn.disabled) return;

            busy = true;
            btn.disabled = true;
            label.textContent = 'Working...';
            setStatus('Generating a draft...', '#5B6478');

            try {
                var weekInput = document.querySelector('input[name="week_no"]');
                var dateInput = document.querySelector('input[name="activity_date"]');
                var tokenInput = document.querySelector('input[name="_token"]');

                var response = await fetch('/student/logbook/ai-help', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': tokenInput ? tokenInput.value : '',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        week_no: weekInput ? weekInput.value : '',
                        activity_date: dateInput ? dateInput.value : '',
                        description: field.value
                    })
                });

                var remaining = response.headers.get('X-AI-Remaining');
                if (remaining !== null) updateMeter(parseInt(remaining, 10));

                var data = null;
                try { data = await response.json(); } catch (parseError) { data = null; }

                if (response.status === 429) {
                    throw new Error((data && data.message) || 'Too many requests. Please wait a moment and try again.');
                }
                if (!response.ok) {
                    throw new Error((data && data.message) || ('HTTP ' + response.status));
                }
                if (!data || !data.text) {
                    throw new Error('No text field in response');
                }

                field.value = data.text;
                field.focus();
                setStatus(data.message || 'Draft inserted successfully.', '#10b981');
            } catch (error) {
                setStatus('Error: ' + (error.message || 'Unable to generate text.'), '#ef4444');
            } finally {
                busy = false;
                startCooldown();
            }
        });

        if (form && submitBtn) {
            form.addEventListener('submit', function () {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Submitting...';
            });
            window.addEventListener('pageshow', function () {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Entry';
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAi);
    } else {
        initAi();
    }
})();
</script>
@endsection