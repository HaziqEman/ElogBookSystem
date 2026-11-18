@extends('layouts.app')

@section('content')
    @include('partials.google-fonts', ['includeCaveat' => false])

    {{-- Plain CSS so the layout works even if the Tailwind build is out of date --}}
    <style>
        .att-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 1.25rem;
            align-items: start;
        }

        @media (min-width: 1280px) {
            .att-grid {
                grid-template-columns: 440px minmax(0, 1fr);
            }
        }

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

        // Colored pill for each row of the history table
        $statusPill = function ($record) {
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

    <div
        class="space-y-6"
        style="font-family:'Plus Jakarta Sans',ui-sans-serif,system-ui,sans-serif"
    >

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
            class="grid grid-cols-1 gap-3.5 sm:grid-cols-3"
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
                    <div class="text-[28px] font-extrabold leading-tight text-[#0F1B4D]">
                        {{ intdiv($monthMinutes, 60) }}h
                        {{ str_pad((string) ($monthMinutes % 60), 2, '0', STR_PAD_LEFT) }}m</div>
                    <div class="mt-0.5 truncate text-xs font-medium text-[#5B6478]">This month</div>
                </div>
            </div>
        </div>

        {{-- CLOCK-IN CARD | HISTORY --}}
        <div class="att-grid">
            <div class="min-w-0">
                @include('student.partials.attendance-card')
            </div>

            <div
                id="attendance-history"
                data-live
                class="{{ $shell }} min-w-0 overflow-hidden"
            >
                {{-- Header + filter tabs --}}
                <div
                    class="flex flex-wrap items-center justify-between gap-3 border-b border-[#EDF0F7] px-5 py-4">
                    <div>
                        <h2 class="text-base font-extrabold text-[#1E293B]">Attendance history</h2>
                        <p class="mt-0.5 text-xs text-[#5B6478]">Your latest 60 recorded days</p>
                    </div>

                    <div
                        class="flex gap-1 rounded-xl bg-[#F7F9FD] p-1"
                        role="group"
                        aria-label="Filter history"
                    >
                        <button
                            type="button"
                            data-att-filter="all"
                            aria-pressed="true"
                            class="{{ $tabBase }} bg-white text-[#1E293B] shadow-sm"
                        >
                            All <span
                                class="rounded-lg bg-[#E8EFFF] px-1.5 py-0.5 text-[11px] text-[#1F45D6]"
                            >{{ $records->count() }}</span>
                        </button>
                        <button
                            type="button"
                            data-att-filter="present"
                            aria-pressed="false"
                            class="{{ $tabBase }} text-[#5B6478]"
                        >
                            Present <span
                                class="rounded-lg bg-[#D1FAE5] px-1.5 py-0.5 text-[11px] text-[#065F46]"
                            >{{ $presentCount }}</span>
                        </button>
                        <button
                            type="button"
                            data-att-filter="off"
                            aria-pressed="false"
                            class="{{ $tabBase }} text-[#5B6478]"
                        >
                            Days off <span
                                class="rounded-lg bg-[#FEF3C7] px-1.5 py-0.5 text-[11px] text-[#7C2D12]"
                            >{{ $offCount }}</span>
                        </button>
                    </div>
                </div>

                {{-- Table scrolls inside this box, so the page never gets long --}}
                <div class="att-scroll [scrollbar-width:thin]">
                    <table class="w-full min-w-[620px] text-left text-sm">
                        <thead
                            class="sticky top-0 z-10 bg-[#F7F9FD] text-[11px] font-bold uppercase tracking-wider text-[#5B6478]"
                        >
                            <tr>
                                <th class="whitespace-nowrap px-5 py-3">Date</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">In</th>
                                <th class="px-4 py-3">Out</th>
                                <th class="px-4 py-3">Worked</th>
                                <th class="px-4 py-3">Note</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#EDF0F7]">
                            @forelse($records as $record)
                                @php
                                    [$pillText, $pillClass] = $statusPill($record);
                                    $isOff = $record->status === \App\Models\Attendance::STATUS_LEAVE;
                                    $isToday =
                                        $record->attendance_date->toDateString() === $todayKey;
                                    $mode = $record->workModeLabel();
                                @endphp
                                <tr
                                    data-att-row
                                    data-kind="{{ $isOff ? 'off' : 'present' }}"
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
                                        {{ $record->workedLabel() ?? '-' }}</td>
                                    <td class="max-w-[12rem] px-4 py-3 text-[#5B6478]"><span
                                            class="block truncate"
                                            title="{{ $record->note ?: '' }}"
                                        >{{ $record->note ?: '-' }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="6"
                                        class="px-5 py-12 text-center text-sm text-[#5B6478]"
                                    >
                                        <span
                                            class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#F7F9FD] text-xl text-[#94A3B8]"
                                        ><i class="fa-regular fa-calendar-xmark"></i></span>
                                        No attendance recorded yet. Clock in to start.
                                    </td>
                                </tr>
                            @endforelse

                            {{-- Shown by the filter tabs when a tab has no rows --}}
                            <tr
                                data-att-empty
                                class="hidden"
                            >
                                <td
                                    colspan="6"
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

            // The live refresh replaces the history block, so re-apply the chosen tab afterwards.
            document.addEventListener('async:swapped', apply);
        })();
    </script>
@endsection
