@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

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

    {{-- REMINDER BANNER --}}
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

    {{-- ATTENDANCE + STATS | QUICK ACTIONS + AI --}}
    <section class="flex flex-wrap items-stretch gap-5">
        <div class="flex min-w-0 flex-[3_1_520px] flex-col gap-4">
            @if($extrasReady)
                @include('student.partials.attendance-card')
            @else
                <div class="rounded-[22px] border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800">
                    Attendance and to-do tables are not set up yet. Ask the administrator to run the database update.
                </div>
            @endif

            <div class="grid flex-1 grid-cols-2 gap-3.5">
                <div class="flex items-center gap-3.5 rounded-[20px] border border-[#D3E0FF] bg-[#E8EFFF] px-[18px] py-4">
                    <span class="flex h-[46px] w-[46px] flex-none items-center justify-center rounded-[15px] bg-white text-[#1F45D6]"><i class="fa-solid fa-file-lines"></i></span>
                    <div><div class="text-xs font-semibold text-[#334155]">Total entries</div><div class="text-[28px] font-extrabold leading-tight">{{ $totalLogs }}</div></div>
                </div>
                <div class="flex items-center gap-3.5 rounded-[20px] border border-[#BFEBD3] bg-[#E3F7EC] px-[18px] py-4">
                    <span class="flex h-[46px] w-[46px] flex-none items-center justify-center rounded-[15px] bg-white text-[#047857]"><i class="fa-solid fa-paperclip"></i></span>
                    <div><div class="text-xs font-semibold text-[#334155]">Files uploaded</div><div class="text-[28px] font-extrabold leading-tight">{{ $totalAttachments }}</div></div>
                </div>
                <a href="/student/feedback" class="flex items-center gap-3.5 rounded-[20px] border border-[#DDD0FB] bg-[#F0EAFF] px-[18px] py-4 transition hover:border-[#6D28D9]">
                    <span class="flex h-[46px] w-[46px] flex-none items-center justify-center rounded-[15px] bg-white text-[#6D28D9]"><i class="fa-solid fa-comment-dots"></i></span>
                    <div><div class="text-xs font-semibold text-[#334155]">Reviews</div><div class="text-[28px] font-extrabold leading-tight">{{ $totalReviews }}</div></div>
                </a>
                <a href="/student/messages" class="flex items-center gap-3.5 rounded-[20px] border border-[#FBCFCB] bg-[#FFE9E7] px-[18px] py-4 transition hover:border-[#BE123C]">
                    <span class="flex h-[46px] w-[46px] flex-none items-center justify-center rounded-[15px] bg-white text-[#BE123C]"><i class="fa-solid fa-envelope"></i></span>
                    <div><div class="text-xs font-semibold text-[#334155]">Unread messages</div><div class="text-[28px] font-extrabold leading-tight">{{ $unreadMessages }}</div></div>
                </a>
            </div>
        </div>

        <div class="flex min-w-0 flex-[2_1_340px] flex-col gap-4">
            <div class="p-5 {{ $card }}">
                <h2 class="mb-3.5 text-base font-extrabold">Quick actions</h2>
                <div class="grid grid-cols-2 gap-3">
                    <a href="#new-entry" data-write-date="{{ $todayKey }}" class="flex min-h-[86px] flex-col gap-2 rounded-[18px] border border-[#D3E0FF] bg-[#E8EFFF] p-3.5 transition hover:border-[#2F5BFF]">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#2F5BFF] text-white"><i class="fa-solid fa-plus"></i></span>
                        <span class="text-[13px] font-bold leading-tight">New logbook entry</span>
                    </a>
                    <a href="/student/calendar" class="flex min-h-[86px] flex-col gap-2 rounded-[18px] border border-[#BFEBD3] bg-[#E3F7EC] p-3.5 transition hover:border-[#047857]">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#047857] text-white"><i class="fa-solid fa-calendar-days"></i></span>
                        <span class="text-[13px] font-bold leading-tight">View calendar</span>
                    </a>
                    <a href="#new-entry" data-focus="attachment" class="flex min-h-[86px] flex-col gap-2 rounded-[18px] border border-[#DDD0FB] bg-[#F0EAFF] p-3.5 transition hover:border-[#6D28D9]">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#6D28D9] text-white"><i class="fa-solid fa-arrow-up-from-bracket"></i></span>
                        <span class="text-[13px] font-bold leading-tight">Upload files</span>
                    </a>
                    <a href="{{ $chatHref }}" class="flex min-h-[86px] flex-col gap-2 rounded-[18px] border border-[#FBCFCB] bg-[#FFE9E7] p-3.5 transition hover:border-[#BE123C]">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#BE123C] text-white"><i class="fa-solid fa-comment"></i></span>
                        <span class="text-[13px] font-bold leading-tight">Chat with supervisor</span>
                    </a>
                </div>
            </div>

            @if($aiReady)
                <div class="flex flex-col gap-2 rounded-[20px] border border-[#E3E8F2] bg-white px-4 py-3.5 shadow-[0_6px_18px_rgba(15,27,77,0.05)]">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-8 w-8 flex-none items-center justify-center rounded-[10px] bg-[#F0EAFF] text-[#6D28D9]"><i class="fa-solid fa-wand-magic-sparkles text-sm"></i></span>
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

    {{-- CALENDAR + TO-DO --}}
    <section class="flex flex-wrap items-stretch gap-5">
        <div class="min-w-0 flex-[3_1_520px] px-[22px] py-5 {{ $card }}">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-base font-extrabold">My calendar</h2>
                <div class="flex items-center gap-2.5">
                    <a href="/student/calendar?month={{ $calendar['prev'] }}" aria-label="Previous month" class="flex h-9 w-9 items-center justify-center rounded-[10px] border border-[#E3E8F2] bg-white transition hover:bg-[#F3F5FC]"><i class="fa-solid fa-chevron-left text-xs"></i></a>
                    <div class="min-w-[110px] text-center text-[15px] font-extrabold">{{ $calendar['title'] }}</div>
                    <a href="/student/calendar?month={{ $calendar['next'] }}" aria-label="Next month" class="flex h-9 w-9 items-center justify-center rounded-[10px] border border-[#E3E8F2] bg-white transition hover:bg-[#F3F5FC]"><i class="fa-solid fa-chevron-right text-xs"></i></a>
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

        <div id="todo-list" class="min-w-0 flex-[2_1_340px] px-[22px] py-5 {{ $card }}">
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
                            <button type="submit" aria-label="Mark as done" class="flex h-6 w-6 flex-none items-center justify-center rounded-[7px] border-2 border-[#94A3B8] bg-white text-transparent transition hover:border-[#047857] hover:text-[#047857]"><i class="fa-solid fa-check text-[11px]"></i></button>
                        </form>
                        <div class="min-w-0 flex-1 break-words text-sm font-medium">{{ $todo->title }}</div>
                        @if($due)
                            <div class="whitespace-nowrap text-xs font-semibold {{ $overdue ? 'text-[#BE123C]' : 'text-[#5B6478]' }}">{{ $overdue ? 'Overdue ' : '' }}{{ $due === $todayKey ? 'Today' : $todo->due_date->format('d M') }}</div>
                        @endif
                        <form method="POST" action="/student/todos/{{ $todo->todo_id }}" onsubmit="return confirm('Delete this task?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" aria-label="Delete task" class="text-[#CBD3E3] transition hover:text-[#BE123C]"><i class="fa-solid fa-xmark"></i></button>
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
                                    <button type="submit" aria-label="Mark as not done" class="flex h-6 w-6 flex-none items-center justify-center rounded-[7px] bg-[#047857] text-white"><i class="fa-solid fa-check text-[11px]"></i></button>
                                </form>
                                <div class="min-w-0 flex-1 break-words text-sm font-medium text-[#64748B] line-through">{{ $todo->title }}</div>
                                <form method="POST" action="/student/todos/{{ $todo->todo_id }}" onsubmit="return confirm('Delete this task?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" aria-label="Delete task" class="text-[#CBD3E3] transition hover:text-[#BE123C]"><i class="fa-solid fa-xmark"></i></button>
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

    {{-- NEW ENTRY (opens from "Write entry" and the quick actions) --}}
    <details id="new-entry" class="{{ $card }}" {{ ($errors->any() && old('description') !== null) || old('description') || old('activity_date') ? 'open' : '' }}>
        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-7 py-5 [&::-webkit-details-marker]:hidden">
            <div>
                <h2 class="text-xl font-extrabold">New logbook entry</h2>
                <p class="mt-0.5 text-[13px] text-[#5B6478]">One entry per working day. Pick the day you worked, write the work, and attach a file if you have one.</p>
            </div>
            <span class="flex h-9 w-9 flex-none items-center justify-center rounded-xl bg-[#E8EFFF] text-[#1F45D6]"><i class="fa-solid fa-chevron-down text-sm"></i></span>
        </summary>

        <form id="logbook-form" action="/student/logbook/store" method="POST" enctype="multipart/form-data" data-first-monday="{{ $firstMondayKey }}" data-total-weeks="{{ $planner->totalWeeks() }}" data-working-days="{{ $workingDaysCsv }}" data-today="{{ $todayKey }}" class="space-y-3 border-t border-[#EDF0F7] px-7 pb-6 pt-5">
            @csrf
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#334155]">Date of Training</label>
                    <input type="date" name="activity_date" value="{{ old('activity_date') }}" @if($startKey) min="{{ $startKey }}" @endif max="{{ $todayKey }}" class="w-full rounded-xl border border-[#CBD3E3] p-2.5 text-sm focus:border-[#2F5BFF] focus:outline-none">
                </div>
                @if($planner->hasStart())
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-[#334155]">Week</label>
                        <input type="hidden" name="week_no" value="{{ old('week_no') }}">
                        <p id="week-hint" class="flex min-h-[42px] items-center rounded-xl bg-[#F7F9FD] px-3 text-sm text-[#5B6478]">Pick a date and the week is worked out for you.</p>
                    </div>
                @else
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-[#334155]">Week Number</label>
                        <input type="number" name="week_no" value="{{ old('week_no') }}" class="w-full rounded-xl border border-[#CBD3E3] p-2.5 text-sm focus:border-[#2F5BFF] focus:outline-none">
                    </div>
                @endif
            </div>
            <p id="date-hint" class="hidden rounded-xl bg-[#FFEDD5] px-3 py-2 text-xs font-semibold text-[#9A3412]"></p>

            <div>
                <label class="mb-1 block text-xs font-semibold text-[#334155]">Activity Description</label>
                <div>
                <label for="ai-role" class="mb-1 block text-xs font-semibold text-[#334155]">Your role at the company <span class="font-normal text-[#5B6478]">(AI Help uses it to pick the right wording)</span></label>
                <input type="text" id="ai-role" maxlength="150" value="{{ old('role', $student->internship_role ?? '') }}" placeholder="e.g. Junior web developer, Accounts assistant" class="w-full rounded-xl border border-[#CBD3E3] p-2.5 text-sm focus:border-[#2F5BFF] focus:outline-none">
                </div>  
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
                    <div id="ai-read-file-box" class="hidden">
                    <label class="flex cursor-pointer items-start gap-2.5 rounded-xl border border-[#E3E8F2] bg-[#FCFDFF] p-3 text-xs text-[#334155]">
                        <input type="checkbox" id="ai-read-file" class="mt-0.5 h-4 w-4">
                        <span><strong>Let AI Help read this file</strong> (photo or PDF) when it writes the draft. The file is sent to Google's Gemini service, so do not use documents with confidential company information.</span>
                    </label>
                    </div>
                <input type="file" name="attachment" class="w-full rounded-xl border border-[#CBD3E3] p-2.5 text-sm file:mr-3 file:rounded file:border-0 file:bg-[#E8EFFF] file:px-3 file:py-2 file:text-sm file:font-bold file:text-[#1F45D6]">
            </div>
            <button id="submit-entry-btn" type="submit" class="w-full rounded-xl bg-[#2F5BFF] p-3 text-sm font-bold text-white shadow-sm transition hover:bg-[#1F45D6] disabled:cursor-not-allowed disabled:opacity-60">Submit Entry</button>
        </form>
    </details>

    {{-- LOGBOOK BY WEEK --}}
    <section id="logbook-week" class="px-7 py-6 {{ $card }}">
        <div class="mb-3 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-xl font-extrabold">Logbook by week</h2>
                <div class="mt-0.5 text-[13px] text-[#5B6478]">
                    @if($planner->hasStart())
                        Week {{ max(1, min($planner->totalWeeks(), $currentWeek)) }} of {{ $planner->totalWeeks() }} &middot; one entry per working day &middot; each week is sent to your supervisor when it ends
                    @else
                        Waiting for your administrator to set your internship dates.
                    @endif
                </div>
            </div>
        </div>

        @if(! $planner->hasStart())
            <div class="rounded-2xl border border-dashed border-[#C3CCEA] bg-[#F5F7FF] px-5 py-8 text-center text-sm text-[#5B6478]">
                <i class="fa-regular fa-calendar mb-2 block text-3xl text-[#94A3B8]"></i>
                Your internship dates have not been set yet. Please ask your administrator to set your start date and working days. Your weeks will appear here once they are set.
            </div>
        @else
            <div class="mb-4 flex flex-wrap items-center gap-x-5 gap-y-2 rounded-2xl border border-[#E3E8F2] bg-[#FCFDFF] px-4 py-3 text-sm text-[#334155]">
                <span><i class="fa-regular fa-calendar mr-1.5 text-[#1F45D6]"></i> Starts <strong>{{ $planner->start()->format('d M Y') }}</strong></span>
                <span><i class="fa-solid fa-calendar-week mr-1.5 text-[#1F45D6]"></i> Working days: <strong>{{ $planner->workingDaysLabel() }}</strong></span>
                <span>{{ $planner->totalWeeks() }} weeks</span>
                <span class="text-xs text-[#5B6478]">Set by your administrator</span>
            </div>
        @endif

        @if($planner->hasStart() && $selected)
            <div class="flex flex-wrap gap-2">
                @foreach($weeks as $week)
                    <a href="/student/dashboard?week={{ $week['number'] }}#logbook-week" title="{{ $statusText[$week['status']] }}" class="flex min-h-[42px] min-w-[52px] items-center justify-center rounded-xl px-3 text-sm font-extrabold transition {{ $chipClass[$week['status']] }} {{ $week['number'] === $selected['number'] ? 'ring-2 ring-[#2F5BFF] ring-offset-2' : '' }}">W{{ $week['number'] }}</a>
                @endforeach
            </div>
            <div class="mb-5 mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-[#5B6478]">
                <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-[#34D399]"></span>Validated</span>
                <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-[#818CF8]"></span>Waiting for review</span>
                <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-[#FB923C]"></span>Revision needed</span>
                <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-[#2F5BFF]"></span>Current week</span>
                <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-[#FB7185]"></span>Entries missing</span>
                <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-[#CBD5E1]"></span>Not open yet</span>
            </div>

            @if($selected['status'] === 'locked')
                <div class="flex flex-col items-center gap-3 rounded-[20px] border border-dashed border-[#C3CCEA] bg-[#F5F7FF] px-6 py-12 text-center">
                    <span class="flex h-16 w-16 items-center justify-center rounded-full bg-white text-2xl text-[#94A3B8]"><i class="fa-solid fa-lock"></i></span>
                    <p class="text-lg font-extrabold">Week {{ $selected['number'] }} opens on {{ $selected['start']->format('l, d F Y') }}</p>
                    <p class="text-sm text-[#5B6478]">You can write entries for it once the week starts.</p>
                </div>
            @else
                <div class="mb-4 flex flex-wrap items-center justify-between gap-x-6 gap-y-3">
                    <div class="min-w-0 flex-[1_1_320px]">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <h3 class="text-2xl font-extrabold">Week {{ $selected['number'] }}</h3>
                            @if($selected['is_current'])
                                <span class="rounded-full bg-[#E8EFFF] px-2.5 py-1 text-xs font-bold text-[#1F45D6]">Current week</span>
                            @endif
                            @if(! in_array($selected['status'], ['current', 'locked'], true))
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $chipClass[$selected['status']] }}">{{ $statusText[$selected['status']] }}</span>
                            @endif
                        </div>
                        <p class="mt-1 text-[13px] text-[#5B6478]">{{ $selected['start']->format('d M') }} to {{ $selected['end']->format('d M Y') }}</p>
                    </div>

                    <div class="min-w-[220px] flex-[1_1_240px]">
                        <div class="flex items-center justify-between text-[13px] font-semibold">
                            <span>
                                @if($selected['required'] > 0)
                                    {{ $selected['done'] }} of {{ $selected['required'] }} days complete
                                @else
                                    No entry needed this week
                                @endif
                            </span>
                            <span class="text-[#5B6478]">{{ $selected['percent'] }}%</span>
                        </div>
                        <div class="mt-1.5 h-2 overflow-hidden rounded-[5px] bg-[#E3E8F2]"><div class="h-full rounded-[5px] bg-[#2F5BFF]" style="width:{{ $selected['percent'] }}%"></div></div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $pill($selected['supervisor']) }}">Supervisor: {{ $selected['supervisor'] }}</span>
                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $pill($selected['lecturer']) }}">Lecturer: {{ $selected['lecturer'] }}</span>
                    </div>
                </div>

                @if($selected['is_current'] && $selected['last_working'])
                    <p class="mb-4 text-[13px] text-[#5B6478]">This week is sent to your supervisor automatically after {{ $selected['last_working']->format('l, d M') }}. Days with no entry are shown to them as missing.</p>
                @endif

                <div class="space-y-3.5">
                    @foreach($selected['days'] as $day)
                        @php
                            $date = $day['date'];
                            $entry = $day['entry'];
                            $file = $entry ? $entry->attachments->first() : null;
                            $moreFiles = $entry ? max($entry->attachments->count() - 1, 0) : 0;
                            $attendanceRow = $day['attendance'];
                            $isToday = $day['state'] === 'today';
                            $rowClass = $isToday
                                ? 'border-2 border-[#2F5BFF] bg-white'
                                : ($day['state'] === 'upcoming' ? 'border border-[#E7ECF6] bg-[#F7F9FD]' : 'border border-[#E7ECF6] bg-[#FCFDFF]');
                        @endphp
                        <article class="flex flex-wrap items-start gap-5 rounded-[20px] p-[18px] {{ $rowClass }}">
                            <div class="w-14 flex-none pt-1 text-center">
                                <div class="text-xs font-extrabold tracking-wider text-[#5B6478]">{{ strtoupper($date->format('D')) }}</div>
                                <div class="text-[28px] font-extrabold leading-none">{{ $date->format('d') }}</div>
                                <div class="text-xs text-[#5B6478]">{{ $date->format('M') }}</div>
                            </div>

                            @if($day['state'] === 'done')
                                @include('student.partials.file-preview', ['file' => $file])
                            @elseif($day['state'] === 'dayoff')
                                @if($attendanceRow->hasMc())
                                    @include('student.partials.file-preview', ['file' => $attendanceRow->mcAttachment()])
                                @else
                                    <div class="flex h-[150px] w-full shrink-0 items-center justify-center rounded-2xl bg-white/70 text-4xl text-[#94A3B8] sm:w-[210px]">
                                        <i class="fa-solid {{ $attendanceRow->dayOffLabel() === 'Public holiday' ? 'fa-flag' : 'fa-umbrella-beach' }}"></i>
                                    </div>
                                @endif
                            @elseif($day['state'] === 'upcoming')
                                <div class="flex h-[150px] w-full shrink-0 flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-[#D5DBEA] bg-white/60 text-[#94A3B8] sm:w-[210px]">
                                    <i class="fa-solid fa-lock text-3xl"></i>
                                    <span class="text-xs font-bold">Not open yet</span>
                                </div>
                            @else
                                @include('student.partials.file-preview', ['file' => null])
                            @endif

                            <div class="flex min-w-0 flex-[1_1_300px] flex-col gap-2">
                                @if($day['state'] === 'done')
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded-full bg-[#D1FAE5] px-2.5 py-1 text-xs font-bold text-[#065F46]">Done</span>
                                            @if($day['wfh'])
                                                <span class="rounded-full bg-[#E8EFFF] px-2.5 py-1 text-xs font-bold text-[#1F45D6]"><i class="fa-solid fa-house"></i> Work from home</span>
                                            @endif
                                        </div>
                                        @if($entry->isEditableByStudent())
                                            <a href="/student/logbook/edit/{{ $entry->logbook_id }}" aria-label="{{ $entry->needsRevision() ? 'Revise entry' : 'Edit entry' }}" title="{{ $entry->needsRevision() ? 'Revise entry' : 'Edit entry' }}" class="flex h-[38px] w-[38px] items-center justify-center rounded-[11px] border transition {{ $entry->needsRevision() ? 'border-[#BE123C] bg-[#BE123C] text-white hover:bg-[#9F1239]' : 'border-[#E3E8F2] bg-white text-[#334155] hover:bg-[#F3F5FC]' }}"><i class="fa-solid fa-pen text-sm"></i></a>
                                        @else
                                            <span title="Approved, so it is locked" class="flex h-[38px] w-[38px] items-center justify-center rounded-[11px] border border-[#E3E8F2] bg-[#F7F9FD] text-[#94A3B8]"><i class="fa-solid fa-lock text-sm"></i></span>
                                        @endif
                                    </div>

                                    <p class="line-clamp-3 break-words text-sm leading-relaxed text-[#334155]">{{ $entry->description }}</p>

                                    <div class="mt-auto flex flex-wrap items-center gap-2">
                                        @if($file)
                                            <a href="{{ $file->url }}" target="_blank" rel="noopener" title="{{ $file->file_name }}" class="flex min-h-8 max-w-[260px] items-center gap-1.5 rounded-[9px] bg-[#EEF2FB] px-2.5 text-xs font-medium text-[#1E293B] transition hover:bg-[#E1E8F8]">
                                                <i class="fa-solid fa-paperclip text-[#94A3B8]"></i>
                                                <span class="truncate">{{ $file->file_name }}</span>
                                            </a>
                                            <a href="{{ $file->url }}" target="_blank" rel="noopener" class="flex min-h-8 items-center rounded-[9px] border border-[#CBD3E3] bg-white px-3 text-xs font-bold text-[#1F45D6] transition hover:bg-[#F3F5FC]">Open file</a>
                                            @if($moreFiles > 0)
                                                <span class="text-xs text-[#5B6478]">+{{ $moreFiles }} more</span>
                                            @endif
                                        @elseif($entry->isEditableByStudent())
                                            <a href="/student/logbook/edit/{{ $entry->logbook_id }}" class="flex min-h-8 items-center gap-1.5 rounded-[9px] border border-dashed border-[#9DB4FF] bg-[#F5F7FF] px-3 text-xs font-bold text-[#1F45D6] transition hover:bg-[#E8EFFF]"><i class="fa-solid fa-plus"></i> Add a file</a>
                                        @endif

                                        <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $pill($entry->status ?? 'Pending') }}">Lecturer: {{ $entry->status ?? 'Pending' }}</span>
                                        @if($supervisorAssigned)
                                            <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $pill($entry->supervisor_status ?? 'Pending') }}">Supervisor: {{ $entry->supervisor_status ?? 'Pending' }}</span>
                                        @endif
                                    </div>

                                    @if($day['entries']->count() > 1)
                                        <a href="/student/logbooks" class="text-xs font-bold text-[#1F45D6] hover:underline">{{ $day['entries']->count() }} entries on this day. See them all</a>
                                    @endif

                                @elseif($day['state'] === 'dayoff')
                                    @php
                                        $offType = $attendanceRow->dayOffLabel();
                                        $offChip = $offType === 'MC' ? 'bg-[#FFE4E6] text-[#9F1239]' : ($offType === 'Public holiday' ? 'bg-[#E0E7FF] text-[#3730A3]' : 'bg-[#FEF3C7] text-[#7C2D12]');
                                    @endphp
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $offChip }}">{{ $offType }}</span>
                                        <span class="text-xs font-semibold text-[#5B6478]">No entry needed</span>
                                    </div>
                                    @if($attendanceRow->note)
                                        <p class="break-words text-sm leading-relaxed text-[#334155]">{{ $attendanceRow->note }}</p>
                                    @endif
                                    @if($attendanceRow->hasMc())
                                        <div>
                                            <a href="/attendance/{{ $attendanceRow->attendance_id }}/mc" target="_blank" rel="noopener" class="inline-flex min-h-8 max-w-[260px] items-center gap-1.5 rounded-[9px] bg-[#EEF2FB] px-2.5 text-xs font-medium text-[#1E293B] transition hover:bg-[#E1E8F8]">
                                                <i class="fa-solid fa-file-medical text-[#BE123C]"></i>
                                                <span class="truncate">{{ $attendanceRow->mc_file_name }}</span>
                                            </a>
                                        </div>
                                    @endif

                                @elseif($day['state'] === 'today')
                                    <div><span class="rounded-full bg-[#E8EFFF] px-2.5 py-1 text-xs font-bold text-[#1F45D6]">Today</span></div>
                                    <p class="text-sm leading-relaxed text-[#334155]">Today's entry is not written yet. Add what you worked on, then attach proof if you have it.</p>
                                    <div class="mt-1 flex flex-wrap gap-2">
                                        <a href="#new-entry" data-write-date="{{ $day['key'] }}" class="flex min-h-10 items-center rounded-xl bg-[#2F5BFF] px-4 text-sm font-bold text-white transition hover:bg-[#1F45D6]">Write today's entry</a>
                                        <a href="#new-entry" data-write-date="{{ $day['key'] }}" data-focus="description" class="flex min-h-10 items-center gap-1.5 rounded-xl bg-[#F0EAFF] px-4 text-sm font-bold text-[#6D28D9] transition hover:bg-[#E5DAFF]"><i class="fa-solid fa-wand-magic-sparkles text-xs"></i> Draft with AI Help</a>
                                    </div>

                                @elseif($day['state'] === 'missing')
                                    <div><span class="rounded-full bg-[#FFE4E6] px-2.5 py-1 text-xs font-bold text-[#9F1239]">Missing</span></div>
                                    <p class="text-sm leading-relaxed text-[#334155]">There is no entry for {{ $date->format('l, d F') }}. Your supervisor will see it as missing.</p>
                                    <div class="mt-1">
                                        <a href="#new-entry" data-write-date="{{ $day['key'] }}" class="inline-flex min-h-10 items-center rounded-xl bg-[#BE123C] px-4 text-sm font-bold text-white transition hover:bg-[#9F1239]">Add late entry</a>
                                    </div>

                                @else
                                    <div><span class="rounded-full bg-[#EEF1F7] px-2.5 py-1 text-xs font-bold text-[#5B6478]">Upcoming</span></div>
                                    <p class="text-sm text-[#5B6478]">Opens on {{ $date->format('l, d F') }}.</p>
                                @endif
                            </div>
                        </article>
                    @endforeach

                    @if(empty($selected['days']))
                        <div class="rounded-[20px] border border-dashed border-[#C3CCEA] bg-[#F5F7FF] p-8 text-center text-sm text-[#5B6478]">None of your working days fall in this week.</div>
                    @endif
                </div>
            @endif

            <div class="mt-5 flex justify-end">
                <a href="/student/logbooks" class="flex min-h-9 items-center gap-1.5 text-sm font-bold text-[#1F45D6] hover:underline">Open full logbook <i class="fa-solid fa-arrow-right text-xs"></i></a>
            </div>
        @endif
    </section>
</div>

<script>
    window.__entryDates = @json($entryDates);
</script>

<script>
(function () {
    if (window.__dashboardBound) return;
    window.__dashboardBound = true;

    var COOLDOWN_SECONDS = 10;

    // Reminder banner: hidden for the rest of the day when closed.
    var banner = document.getElementById('reminder-banner');
    if (banner) {
        var bannerKey = banner.getAttribute('data-dismiss-key');
        try { if (localStorage.getItem(bannerKey)) banner.classList.add('hidden'); } catch (e) {}
        var closeBtn = document.getElementById('reminder-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                banner.classList.add('hidden');
                try { localStorage.setItem(bannerKey, '1'); } catch (e) {}
            });
        }
    }

    // New entry form: week number and day checks.
    var form = document.getElementById('logbook-form');
    var dateInput = form ? form.querySelector('input[name="activity_date"]') : null;
    var weekInput = form ? form.querySelector('[name="week_no"]') : null;
    var weekHint = document.getElementById('week-hint');
    var dateHint = document.getElementById('date-hint');
    var submitBtn = document.getElementById('submit-entry-btn');
    var firstMonday = form ? form.getAttribute('data-first-monday') : '';
    var totalWeeks = form ? parseInt(form.getAttribute('data-total-weeks'), 10) || 14 : 14;
    var todayKey = form ? form.getAttribute('data-today') : '';
    var workingDays = form
        ? (form.getAttribute('data-working-days') || '1,2,3,4,5').split(',').map(function (day) { return parseInt(day, 10); })
        : [];
    var existing = window.__entryDates || [];

    function utc(value) {
        var p = value.split('-');
        return Date.UTC(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10));
    }

    function problemFor(value) {
        if (!value) return '';

        if (todayKey && value > todayKey) {
            return 'You cannot write an entry for a day that has not started yet.';
        }

        if (firstMonday) {
            var week = Math.floor((utc(value) - utc(firstMonday)) / 86400000 / 7) + 1;
            if (week < 1 || week > totalWeeks) {
                return 'This date is outside your internship weeks.';
            }

            var weekday = new Date(utc(value)).getUTCDay();
            var iso = weekday === 0 ? 7 : weekday;
            if (workingDays.indexOf(iso) === -1) {
                return 'That day is not one of your working days. Your administrator sets your working days.';
            }
        }

        if (existing.indexOf(value) !== -1) {
            return 'You already have an entry for this day. Edit it from the week list instead.';
        }

        return '';
    }

    function onDate() {
        if (!dateInput) return;
        var value = dateInput.value;

        if (firstMonday && weekInput && weekHint) {
            if (value) {
                var week = Math.floor((utc(value) - utc(firstMonday)) / 86400000 / 7) + 1;
                weekInput.value = week;
                weekHint.textContent = (week >= 1 && week <= totalWeeks)
                    ? 'This entry belongs to Week ' + week + '.'
                    : 'This date is outside your ' + totalWeeks + ' internship weeks.';
            } else {
                weekInput.value = '';
                weekHint.textContent = 'Pick a date and the week is worked out for you.';
            }
        }

        var problem = problemFor(value);
        if (dateHint) {
            dateHint.textContent = problem;
            dateHint.classList.toggle('hidden', !problem);
        }
        if (submitBtn) submitBtn.disabled = !!problem;
    }

    if (dateInput) {
        dateInput.addEventListener('change', onDate);
        onDate();
    }

    function openEntry(focusName, date) {
        var panel = document.getElementById('new-entry');
        if (!panel) return;
        panel.open = true;
        if (date && dateInput) {
            dateInput.value = date;
            onDate();
        }
        panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        if (focusName) {
            var target = panel.querySelector('[name="' + focusName + '"]') || document.getElementById(focusName + '-field');
            if (target) setTimeout(function () { target.focus(); }, 350);
        }
    }

    document.querySelectorAll('a[href="#new-entry"]').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            openEntry(link.getAttribute('data-focus'), link.getAttribute('data-write-date'));
        });
    });
    if (location.hash === '#new-entry') openEntry();

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

        var btn = document.getElementById('ai-help-btn');
    var label = document.getElementById('ai-help-label');
    var field = document.getElementById('description-field');
    var status = document.getElementById('ai-help-status');
    var roleInput = document.getElementById('ai-role');
    var fileInput = form ? form.querySelector('input[name="attachment"]') : null;
    var readBox = document.getElementById('ai-read-file-box');
    var readCheck = document.getElementById('ai-read-file');

    var AI_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
    var AI_MAX_BYTES = 1900000;

    function aiFile() {
        var file = fileInput && fileInput.files && fileInput.files[0];
        return file && AI_TYPES.indexOf(file.type) !== -1 ? file : null;
    }

    if (fileInput && readBox) {
        fileInput.addEventListener('change', function () {
            var usable = !!aiFile();
            readBox.classList.toggle('hidden', !usable);
            if (!usable && readCheck) readCheck.checked = false;
        });
    }

    // Photos are shrunk in the browser so they fit the server's upload limit.
    function shrinkForAi(file) {
        return new Promise(function (resolve) {
            if (file.type === 'application/pdf' || !window.URL) { resolve(file); return; }

            var image = new Image();
            var url = URL.createObjectURL(file);

            image.onload = function () {
                URL.revokeObjectURL(url);
                var scale = Math.min(1, 1280 / Math.max(image.width, image.height));
                var canvas = document.createElement('canvas');
                canvas.width = Math.round(image.width * scale);
                canvas.height = Math.round(image.height * scale);
                canvas.getContext('2d').drawImage(image, 0, 0, canvas.width, canvas.height);
                canvas.toBlob(function (blob) {
                    resolve(blob ? new File([blob], 'photo.jpg', { type: 'image/jpeg' }) : file);
                }, 'image/jpeg', 0.8);
            };
            image.onerror = function () { URL.revokeObjectURL(url); resolve(file); };
            image.src = url;
        });
    }

    if (btn && field && status) {
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

            var notes = field.value.trim();
            var chosen = readCheck && readCheck.checked ? aiFile() : null;

            if (!notes && !chosen) {
                setStatus('Type a few words about what you did, or tick "Let AI Help read this file", so AI has something real to write from.', '#C2410C');
                field.focus();
                return;
            }

            busy = true;
            btn.disabled = true;
            label.textContent = 'Working...';
            setStatus(chosen ? 'Reading your file and writing a draft...' : 'Writing a draft...', '#5B6478');

            try {
                var body = new FormData();
                body.append('week_no', weekInput ? weekInput.value : '');
                body.append('activity_date', dateInput ? dateInput.value : '');
                body.append('description', field.value);
                body.append('role', roleInput ? roleInput.value : '');

                if (chosen) {
                    var prepared = await shrinkForAi(chosen);
                    if (prepared.size > AI_MAX_BYTES) {
                        throw new Error('This file is too large for AI Help (limit about 2 MB). Try a smaller file or a screenshot.');
                    }
                    body.append('use_file', '1');
                    body.append('attachment', prepared, prepared.name || 'file');
                }

                var tokenInput = document.querySelector('input[name="_token"]');

                var response = await fetch('/student/logbook/ai-help', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': tokenInput ? tokenInput.value : '',
                        'Accept': 'application/json'
                    },
                    body: body
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
                setStatus(data.message || 'Draft inserted. Please check it.', data.fallback ? '#C2410C' : '#10b981');
            } catch (error) {
                setStatus('Error: ' + (error.message || 'Unable to generate text.'), '#ef4444');
            } finally {
                busy = false;
                startCooldown();
            }
        });
    }

    if (form && submitBtn) {
        form.addEventListener('submit', function () {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';
        });
        window.addEventListener('pageshow', function () {
            submitBtn.textContent = 'Submit Entry';
            onDate();
        });
    }
})();
</script>
@endsection