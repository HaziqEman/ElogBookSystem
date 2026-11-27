@extends('layouts.app')

@section('content')
    @php
        use Illuminate\Support\Str;
    @endphp

    <div class="space-y-5">
        {{-- Header banner --}}
        <section
            class="relative overflow-hidden rounded-3xl px-6 py-4 text-white shadow-[0_14px_34px_rgba(47,91,255,0.20)] md:px-9"
            style="background: linear-gradient(120deg, #3B5BFF 0%, #5C7CFF 40%, #A9BAFF 75%, #D6DEFF 100%);"
        >
            <div
                class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-white/10"
                aria-hidden="true"
            ></div>

            <div class="relative flex flex-wrap items-center justify-between gap-5">
                <div class="min-w-0 flex-1 basis-72">
                    <div
                        class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-[#E4EAFF]"
                    >
                        <i class="fa-regular fa-calendar"></i> My calendar
                    </div>
                    <h1 class="mb-1 mt-2 text-2xl font-extrabold tracking-tight md:text-3xl">
                        {{ $month->format('F Y') }}
                    </h1>
                    <p class="max-w-lg text-sm leading-relaxed text-[#E4EAFF]">
                        Track your logbook entries, attendance and task deadlines in one place.
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="grid grid-cols-3 gap-2">
                        <div class="min-w-[76px] rounded-xl border border-white/15 bg-white/15 px-3 py-2 backdrop-blur">
                            <p class="text-xl font-extrabold leading-none">{{ $monthLogs }}</p>
                            <p class="mt-1 text-[10px] font-medium leading-tight text-blue-50/90">
                                {{ Str::plural('Entry', $monthLogs) }}
                            </p>
                        </div>
                        <div class="min-w-[76px] rounded-xl border border-white/15 bg-white/15 px-3 py-2 backdrop-blur">
                            <p class="text-xl font-extrabold leading-none">{{ $monthPresent }}</p>
                            <p class="mt-1 text-[10px] font-medium leading-tight text-blue-50/90">
                                {{ $monthPresent === 1 ? 'Day present' : 'Days present' }}
                            </p>
                        </div>
                        <div class="min-w-[76px] rounded-xl border border-white/15 bg-white/15 px-3 py-2 backdrop-blur">
                            <p class="text-xl font-extrabold leading-none">{{ $monthTasks }}</p>
                            <p class="mt-1 text-[10px] font-medium leading-tight text-blue-50/90">
                                Open {{ Str::plural('task', $monthTasks) }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <a
                            href="/student/calendar?month={{ $prevMonth }}"
                            class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/15 text-white backdrop-blur transition hover:bg-white/25"
                            aria-label="Previous month"
                        ><i class="fa-solid fa-chevron-left"></i></a>
                        @unless ($isCurrentMonth)
                            <a
                                href="/student/calendar"
                                class="flex h-9 items-center rounded-xl bg-white px-3 text-xs font-bold text-blue-700 shadow-sm transition hover:bg-blue-50"
                            >Today</a>
                        @endunless
                        <a
                            href="/student/calendar?month={{ $nextMonth }}"
                            class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/15 text-white backdrop-blur transition hover:bg-white/25"
                            aria-label="Next month"
                        ><i class="fa-solid fa-chevron-right"></i></a>
                    </div>
                </div>
            </div>
        </section>

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
                            $payload = [
                                'label' => $day['date']->format('l, j F Y'),
                                'logs' => $day['log_items'],
                                'tasks' => $day['task_items'],
                                'attendance' => $attendance
                                    ? [
                                        'status' => $attendance->status === \App\Models\Attendance::STATUS_LEAVE ? 'Leave' : 'Present',
                                        'in' => $attendance->checkInLocal(),
                                        'note' => $attendance->note,
                                    ]
                                    : null,
                            ];
                        @endphp
                        <div
                            @if ($day['in_month'])
                                data-day="{{ json_encode($payload) }}"
                                role="button" tabindex="0"
                            @endif
                            class="{{ $cellClass }} {{ $day['in_month'] ? 'day-cell cursor-pointer' : '' }} relative min-h-[4.75rem] border-b border-r border-slate-100 p-1.5 transition sm:min-h-[7rem] sm:p-2.5">
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

    <div id="dayModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4">
        <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-xl">
            <div class="flex items-start justify-between">
                <h3 id="dayTitle" class="text-lg font-extrabold text-slate-800"></h3>
                <button type="button" id="dayClose" class="text-slate-400 hover:text-slate-600" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div id="dayBody" class="mt-4 space-y-4 text-sm"></div>
        </div>
    </div>

    <script>
        (() => {
            const modal = document.getElementById('dayModal');
            const body = document.getElementById('dayBody');
            const esc = s => String(s ?? '').replace(/[&<>"']/g, c =>
                ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

            const section = (title, items) => items
                ? `<div><p class="mb-1.5 text-xs font-bold uppercase tracking-wide text-slate-400">${title}</p><div class="space-y-1.5">${items}</div></div>` : '';

            function open(d) {
                document.getElementById('dayTitle').textContent = d.label;
                let html = '';

                html += section('Logbook', d.logs.map(l =>
                    `<a href="/student/logbooks#logbook-week" class="block rounded-lg bg-blue-50 px-3 py-2 hover:bg-blue-100">
                        <span class="font-semibold text-blue-700">${esc(l.title)}</span>${l.locked ? '<span class="text-xs text-emerald-600"> · Validated</span>' : ''}
                        <br><span class="text-slate-600">${esc(l.summary)}</span>
                    </a>`).join(''));

                if (d.attendance) {
                    html += section('Attendance',
                        `<div class="rounded-lg bg-slate-50 px-3 py-2">${esc(d.attendance.status)}${d.attendance.in ? ' · In ' + esc(d.attendance.in) : ''}${d.attendance.note ? '<br><span class="text-slate-500">' + esc(d.attendance.note) + '</span>' : ''}</div>`);
                }

                html += section('Tasks', d.tasks.map(t =>
                    `<div class="rounded-lg bg-indigo-50 px-3 py-2 text-indigo-700 ${t.done ? 'line-through opacity-60' : ''}">
                        ${esc(t.title)}${t.assigned ? ' <span class="text-xs text-indigo-400">· From supervisor</span>' : ''}
                    </div>`).join(''));

                body.innerHTML = html || '<p class="text-slate-400">Nothing recorded for this day.</p>';
                modal.classList.replace('hidden', 'flex');
            }
            const close = () => modal.classList.replace('flex', 'hidden');

            document.querySelectorAll('.day-cell').forEach(c => {
                const go = () => open(JSON.parse(c.dataset.day));
                c.addEventListener('click', go);
                c.addEventListener('keydown', e => (e.key === 'Enter' || e.key === ' ') && (e.preventDefault(), go()));
            });
            document.getElementById('dayClose').onclick = close;
            modal.addEventListener('click', e => e.target === modal && close());
            document.addEventListener('keydown', e => e.key === 'Escape' && close());
        })();
    </script>
@endsection
