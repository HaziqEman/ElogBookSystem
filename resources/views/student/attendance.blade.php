@extends('layouts.app')

@section('content')
    {{-- Plain CSS so the layout works even if the Tailwind build is out of date --}}
    <style>
        .att-scroll {
            max-height: 620px;
            overflow: auto;
        }
    </style>

    @php
        $shell =
            'rounded-[22px] border border-[#E3E8F2] bg-white shadow-[0_6px_18px_rgba(15,27,77,0.05)]';
        $statCard =
            'flex items-center gap-3.5 rounded-[20px] border border-[#E3E8F2] bg-white px-4 py-4 shadow-[0_6px_18px_rgba(15,27,77,0.05)]';
        $todayKey = $today->toDateString();
        $activeStartedAt =
            $attendance && $attendance->check_in && ! $attendance->check_out
                ? $attendance->check_in->timestamp
                : null;
        $activeMinutes = $activeStartedAt ? ($attendance->workedMinutes($today) ?? 0) : 0;

        // Colored pill for each row of the history table
        $statusPill = function ($record) {
            if ($record->status === \App\Models\Attendance::STATUS_MISSING) {
                return ['Missing', 'bg-[#FFE4E6] text-[#9F1239]'];
            }

            if ($record->status === \App\Models\Attendance::STATUS_LEAVE) {
                $label = $record->dayOffLabel();
                $class = match ($label) {
                    'MC' => 'bg-[#FFE4E6] text-[#9F1239]',
                    'Public holiday' => 'bg-[#E0E7FF] text-[#3730A3]',
                    default => 'bg-[#FEF3C7] text-[#7C2D12]',
                };

                return [$label, $class];
            }

            return [$record->status, 'bg-[#D1FAE5] text-[#065F46]'];
        };

        $tabBase =
            'flex h-8 items-center gap-1.5 whitespace-nowrap rounded-[9px] px-3 text-xs font-bold transition';
    @endphp

    <div class="space-y-6">

        @if (session('success'))
            <div
                class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ session('error') }}</div>
        @endif

        @if ($errors->any() && !old('day_off_type'))
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- HEADER BANNER --}}
        <section
            class="relative overflow-hidden rounded-3xl px-6 py-5 text-white shadow-[0_14px_34px_rgba(47,91,255,0.20)] md:px-9"
            style="background: linear-gradient(120deg, #3B5BFF 0%, #5C7CFF 40%, #A9BAFF 75%, #D6DEFF 100%);"
        >
            <div
                class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-white/10"
                aria-hidden="true"
            ></div>

            <div class="relative flex flex-wrap items-center justify-between gap-4">
                <div class="min-w-0 flex-1 basis-72">
                    <div
                        class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-[#E4EAFF]">
                        <i class="fa-regular fa-calendar"></i> {{ $today->format('l, d F Y') }}
                    </div>
                    <h1 class="mb-1.5 mt-2.5 text-3xl font-extrabold tracking-tight">Attendance</h1>
                    <p class="max-w-lg text-sm leading-relaxed text-[#E4EAFF]">Clock in when you start
                        work and clock out when you finish. Times are in Malaysia time.</p>
                </div>

                <div
                    class="hidden h-20 w-20 flex-none items-center justify-center rounded-full bg-white/15 text-3xl text-white md:flex"
                    aria-hidden="true"
                >
                    <i class="fa-solid fa-user-clock"></i>
                </div>
            </div>
        </section>

        {{-- MONTH SUMMARY --}}
        <div
            id="attendance-stats"
            data-live
            class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 xl:grid-cols-4"
        >
            <div class="{{ $statCard }}">
                <span
                    class="flex h-12 w-12 flex-none items-center justify-center rounded-2xl bg-[#D1FAE5] text-[#047857]"
                ><i
                        class="fa-solid fa-user-check"
                        aria-hidden="true"
                    ></i></span>
                <div class="min-w-0">
                    <div class="truncate text-xs font-semibold text-[#5B6478]">Present</div>
                    <div class="flex items-baseline gap-1.5">
                        <span
                            class="text-[28px] font-extrabold leading-tight text-[#0F1B4D]">{{ $monthPresent }}</span>
                        <span
                            class="text-sm font-semibold text-[#5B6478]">{{ $monthPresent === 1 ? 'day' : 'days' }}</span>
                    </div>
                    <div class="mt-0.5 truncate text-xs font-medium text-[#5B6478]">This month</div>
                </div>
            </div>

            <div class="{{ $statCard }}">
                <span
                    class="flex h-12 w-12 flex-none items-center justify-center rounded-2xl bg-[#FEF3C7] text-[#B45309]"
                ><i
                        class="fa-solid fa-mug-hot"
                        aria-hidden="true"
                    ></i></span>
                <div class="min-w-0">
                    <div class="truncate text-xs font-semibold text-[#5B6478]">Days off</div>
                    <div class="flex items-baseline gap-1.5">
                        <span
                            class="text-[28px] font-extrabold leading-tight text-[#0F1B4D]">{{ $monthLeave }}</span>
                        <span
                            class="text-sm font-semibold text-[#5B6478]">{{ $monthLeave === 1 ? 'day' : 'days' }}</span>
                    </div>
                    <div class="mt-0.5 truncate text-xs font-medium text-[#5B6478]">MC, leave and
                        holidays</div>
                </div>
            </div>

            <div class="{{ $statCard }}">
                <span
                    class="flex h-12 w-12 flex-none items-center justify-center rounded-2xl bg-[#E8EFFF] text-[#1F45D6]"
                ><i
                        class="fa-regular fa-clock"
                        aria-hidden="true"
                    ></i></span>
                <div class="min-w-0">
                    <div class="truncate text-xs font-semibold text-[#5B6478]">Hours worked</div>
                    <div
                        data-worked-total
                        data-base-minutes="{{ max(0, $monthMinutes - $activeMinutes) }}"
                        data-active-start="{{ $activeStartedAt ?? '' }}"
                        class="text-[28px] font-extrabold leading-tight text-[#0F1B4D]"
                    ><span data-worked-hours>{{ intdiv($monthMinutes, 60) }}</span>h
                        <span data-worked-minutes>{{ str_pad((string) ($monthMinutes % 60), 2, '0', STR_PAD_LEFT) }}</span>m</div>
                    <div class="mt-0.5 truncate text-xs font-medium text-[#5B6478]">This month</div>
                </div>
            </div>

            <div class="{{ $statCard }}">
                <span
                    class="flex h-12 w-12 flex-none items-center justify-center rounded-2xl bg-[#FFE4E6] text-[#BE123C]"
                ><i class="fa-solid fa-calendar-xmark" aria-hidden="true"></i></span>
                <div class="min-w-0">
                    <div class="truncate text-xs font-semibold text-[#5B6478]">Missed clock-ins</div>
                    <div class="flex items-baseline gap-1.5">
                        <span class="text-[28px] font-extrabold leading-tight text-[#0F1B4D]">{{ $monthMissing }}</span>
                        <span class="text-sm font-semibold text-[#5B6478]">{{ $monthMissing === 1 ? 'day' : 'days' }}</span>
                    </div>
                    <div class="mt-0.5 truncate text-xs font-medium text-[#5B6478]">Scheduled workdays this month</div>
                </div>
            </div>
        </div>

        {{-- TODAY'S ATTENDANCE | HISTORY --}}
        <div class="space-y-5">
            @include('student.attendance.card', ['attendancePage' => true])

            <div
                id="attendance-history"
                data-live
                class="{{ $shell }} min-w-0 overflow-hidden"
            >
                {{-- Header, filters and status tabs --}}
                <div class="space-y-4 border-b border-[#EDF0F7] px-5 py-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base font-extrabold text-[#1E293B]">Attendance history</h2>
                            <p class="mt-0.5 text-xs text-[#5B6478]">{{ $historyLabel }}</p>
                        </div>

                        <div
                            class="flex flex-wrap gap-1 rounded-xl bg-[#F7F9FD] p-1"
                            role="group"
                            aria-label="Filter attendance status"
                        >
                            <button
                                type="button"
                                data-att-filter="all"
                                aria-pressed="true"
                                class="{{ $tabBase }} bg-white text-[#1E293B] shadow-sm"
                            >
                                All <span class="rounded-lg bg-[#E8EFFF] px-1.5 py-0.5 text-[11px] text-[#1F45D6]">{{ $records->count() }}</span>
                            </button>
                            <button
                                type="button"
                                data-att-filter="present"
                                aria-pressed="false"
                                class="{{ $tabBase }} text-[#5B6478]"
                            >
                                Present <span class="rounded-lg bg-[#D1FAE5] px-1.5 py-0.5 text-[11px] text-[#065F46]">{{ $presentCount }}</span>
                            </button>
                            <button
                                type="button"
                                data-att-filter="off"
                                aria-pressed="false"
                                class="{{ $tabBase }} text-[#5B6478]"
                            >
                                Days off <span class="rounded-lg bg-[#FEF3C7] px-1.5 py-0.5 text-[11px] text-[#7C2D12]">{{ $offCount }}</span>
                            </button>
                            <button
                                type="button"
                                data-att-filter="missing"
                                aria-pressed="false"
                                class="{{ $tabBase }} text-[#5B6478]"
                            >
                                Missing <span class="rounded-lg bg-[#FFE4E6] px-1.5 py-0.5 text-[11px] text-[#9F1239]">{{ $missingCount }}</span>
                            </button>
                        </div>
                    </div>

                    <form
                        id="attendance-history-filter"
                        method="GET"
                        action="/student/attendance"
                        class="flex flex-wrap items-end gap-3"
                    >
                        <label class="min-w-[220px] flex-1 text-xs font-semibold text-[#5B6478]">
                            Week
                            <select
                                name="week"
                                class="mt-1 min-h-10 w-full rounded-xl border border-[#CBD3E3] bg-white px-3 text-sm font-semibold text-[#1E293B] focus:border-[#2F5BFF] focus:outline-none focus:ring-2 focus:ring-[#2F5BFF]/20"
                            >
                                @foreach ($weekOptions as $option)
                                    <option
                                        value="{{ $option['value'] }}"
                                        @selected($selectedWeek === $option['value'])
                                    >{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="min-w-[170px] flex-1 text-xs font-semibold text-[#5B6478]">
                            Or find a date
                            <input
                                type="date"
                                name="date"
                                value="{{ $selectedDate }}"
                                max="{{ $todayKey }}"
                                class="mt-1 min-h-10 w-full rounded-xl border border-[#CBD3E3] bg-white px-3 text-sm font-semibold text-[#1E293B] focus:border-[#2F5BFF] focus:outline-none focus:ring-2 focus:ring-[#2F5BFF]/20"
                            >
                        </label>
                        <button
                            type="submit"
                            class="min-h-10 rounded-xl bg-[#2F5BFF] px-4 text-sm font-bold text-white transition hover:bg-[#1F45D6]"
                        >Search</button>
                        <a
                            href="/student/attendance?week=recent"
                            class="inline-flex min-h-10 items-center rounded-xl border border-[#CBD3E3] bg-white px-4 text-sm font-bold text-[#334155] transition hover:bg-[#F7F9FD]"
                        >Recent</a>
                    </form>
                </div>

                {{-- Table scrolls inside this box, so the page never gets long --}}
                <div class="att-scroll [scrollbar-width:thin]">
                    <table class="w-full min-w-[800px] text-left text-sm">
                        <thead
                            class="sticky top-0 z-10 bg-[#F7F9FD] text-[11px] font-bold uppercase tracking-wider text-[#5B6478]"
                        >
                            <tr>
                                <th class="whitespace-nowrap px-5 py-3">Date</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">In</th>
                                <th class="px-4 py-3">Out</th>
                                <th class="px-4 py-3">Worked</th>
                                <th class="px-4 py-3">Location</th>
                                <th class="px-4 py-3">Note</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#EDF0F7]">
                            @forelse($records as $record)
                                @php
                                    [$pillText, $pillClass] = $statusPill($record);
                                    $isOff = $record->status === \App\Models\Attendance::STATUS_LEAVE;
                                    $isMissing = $record->status === \App\Models\Attendance::STATUS_MISSING;
                                    $isToday =
                                        $record->attendance_date->toDateString() === $todayKey;
                                    $isActive = $isToday && $record->check_in && ! $record->check_out;
                                    $mode = $record->workModeLabel();
                                @endphp
                                <tr
                                    data-att-row
                                    data-kind="{{ $isMissing ? 'missing' : ($isOff ? 'off' : 'present') }}"
                                    class="{{ $isToday ? 'bg-[#F4F7FF]' : '' }} transition hover:bg-[#F7F9FD]"
                                >
                                    <td class="whitespace-nowrap px-5 py-3">
                                        <div class="font-bold text-[#1E293B]">
                                            {{ $record->attendance_date->format('D, d M Y') }}</div>
                                        @if ($isToday)
                                            <span
                                                class="mt-0.5 inline-block rounded-full bg-[#2F5BFF] px-2 py-0.5 text-[10px] font-bold text-white"
                                            >Today</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="{{ $pillClass }} inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-bold"
                                        >{{ $pillText }}</span>
                                        @if (!$isOff && $mode)
                                            <div
                                                class="mt-1 flex items-center gap-1.5 whitespace-nowrap text-[11px] font-medium text-[#5B6478]">
                                                <i
                                                    class="fa-solid {{ $record->work_mode === 'Home' ? 'fa-house' : 'fa-building' }} text-[#94A3B8]"></i>
                                                {{ $mode }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-[#475569]">
                                        {{ $record->checkInLocal() ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-[#475569]">
                                        {{ $record->checkOutLocal() ?? '-' }}</td>
                                    <td
                                        class="whitespace-nowrap px-4 py-3 font-semibold text-[#1E293B]">
                                        @if ($isActive)
                                            <span
                                                data-worked-live
                                                data-worked-since="{{ $record->check_in->timestamp }}"
                                            >{{ $record->workedLabel($today) }}</span>
                                        @else
                                            {{ $record->workedLabel() ?? '-' }}
                                        @endif
                                    </td>
                                    <td class="max-w-[13rem] px-4 py-3 text-xs text-[#5B6478]">
                                        @if ($record->hasLocation())
                                            <a
                                                href="{{ $record->mapLink() }}"
                                                target="_blank"
                                                rel="noopener"
                                                title="{{ number_format((float) $record->latitude, 4) }}, {{ number_format((float) $record->longitude, 4) }}"
                                                class="font-bold text-[#1F45D6] hover:underline"
                                            >View map</a>
                                            <span
                                                class="mt-1 block truncate"
                                                title="{{ $record->location_name ?: '' }}"
                                            >{{ $record->location_name ?: number_format((float) $record->latitude, 4).', '.number_format((float) $record->longitude, 4) }}</span>
                                        @elseif ($record->work_mode === 'Home')
                                            <span>Not collected (WFH)</span>
                                        @elseif ($record->location_status === 'denied')
                                            <span>Not shared</span>
                                        @elseif ($record->location_status === 'unavailable')
                                            <span>Unavailable</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="max-w-[12rem] px-4 py-3 text-[#5B6478]"><span
                                            class="block truncate"
                                            title="{{ $record->note ?: '' }}"
                                        >{{ $record->note ?: '-' }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="7"
                                        class="px-5 py-12 text-center text-sm text-[#5B6478]"
                                    >
                                        <span
                                            class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#F7F9FD] text-xl text-[#94A3B8]"
                                        ><i class="fa-regular fa-calendar-xmark"></i></span>
                                        No attendance or missed scheduled days for this filter.
                                    </td>
                                </tr>
                            @endforelse

                            {{-- Shown by the filter tabs when a tab has no rows --}}
                            <tr
                                data-att-empty
                                class="hidden"
                            >
                                <td
                                    colspan="7"
                                    class="px-5 py-10 text-center text-sm text-[#5B6478]"
                                >Nothing to show for this filter.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            if (window.__attFilterBound) return;
            window.__attFilterBound = true;
            window.__attFilter = 'all';

            var ON = ['bg-white', 'text-[#1E293B]', 'shadow-sm'];
            var OFF = ['text-[#5B6478]'];

            function apply() {
                var box = document.getElementById('attendance-history');
                if (!box) return;

                var rows = box.querySelectorAll('[data-att-row]');
                var shown = 0;

                rows.forEach(function(row) {
                    var show = window.__attFilter === 'all' || row.getAttribute(
                        'data-kind') === window.__attFilter;
                    row.classList.toggle('hidden', !show);
                    if (show) shown++;
                });

                box.querySelectorAll('[data-att-filter]').forEach(function(btn) {
                    var on = btn.getAttribute('data-att-filter') === window.__attFilter;
                    btn.setAttribute('aria-pressed', on ? 'true' : 'false');
                    ON.forEach(function(c) {
                        btn.classList.toggle(c, on);
                    });
                    OFF.forEach(function(c) {
                        btn.classList.toggle(c, !on);
                    });
                });

                var empty = box.querySelector('[data-att-empty]');
                if (empty) empty.classList.toggle('hidden', !(rows.length > 0 && shown === 0));
            }

            document.addEventListener('click', function(event) {
                var btn = event.target && event.target.closest ? event.target.closest(
                    '[data-att-filter]') : null;
                if (!btn) return;

                window.__attFilter = btn.getAttribute('data-att-filter');
                apply();
            });

            document.addEventListener('change', function(event) {
                var form = event.target && event.target.closest ? event.target.closest(
                    '#attendance-history-filter') : null;
                if (!form) return;

                if (event.target.name === 'week') {
                    form.querySelector('[name="date"]').value = '';
                } else if (event.target.name === 'date' && event.target.value) {
                    form.querySelector('[name="week"]').value = 'recent';
                }
            });

            // The live refresh replaces the history block, so re-apply the chosen tab afterwards.
            document.addEventListener('async:swapped', apply);
        })();

        (function() {
            if (window.__attWorkedTimerBound) return;
            window.__attWorkedTimerBound = true;

            function formatMinutes(total) {
                return {
                    hours: Math.floor(total / 60),
                    minutes: String(total % 60).padStart(2, '0')
                };
            }

            function updateWorkedTimes() {
                var now = Math.floor(Date.now() / 1000);

                document.querySelectorAll('[data-worked-live]').forEach(function(element) {
                    var start = Number(element.dataset.workedSince);
                    var parts = formatMinutes(Math.max(0, Math.floor((now - start) / 60)));
                    element.textContent = parts.hours + 'h ' + parts.minutes + 'm';
                });

                document.querySelectorAll('[data-worked-total]').forEach(function(element) {
                    var total = Number(element.dataset.baseMinutes);
                    var start = Number(element.dataset.activeStart);
                    if (start) total += Math.max(0, Math.floor((now - start) / 60));

                    var parts = formatMinutes(total);
                    var hours = element.querySelector('[data-worked-hours]');
                    var minutes = element.querySelector('[data-worked-minutes]');
                    if (hours) hours.textContent = parts.hours;
                    if (minutes) minutes.textContent = parts.minutes;
                });
            }

            updateWorkedTimes();
            window.setInterval(updateWorkedTimes, 15000);
            document.addEventListener('async:swapped', updateWorkedTimes);
            document.addEventListener('page:loaded', updateWorkedTimes);
        })();
    </script>
@endsection
