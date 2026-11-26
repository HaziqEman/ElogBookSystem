@extends('layouts.app')

@section('content')
    @include('supervisor.shared.portal-font')

    @php
        use Illuminate\Support\Str;

        // ---- View helpers (move to a ViewModel / model accessors if reused elsewhere) ----
        $palette = ['#0F9D8A', '#2F5BFF', '#BE123C', '#6B7280', '#7C3AED', '#C2410C'];
        $avatarColor = fn($student) => $palette[
            crc32((string) $student->student_id) % count($palette)
        ];
        $initials = fn(string $name) => Str::of($name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn($part) => mb_substr($part, 0, 1))
            ->implode('');

        $card =
            'rounded-[22px] border border-[#E3E8F2] bg-white shadow-[0_6px_18px_rgba(15,27,77,0.05)]';

        $statusChip = [
            'Pending review' => 'bg-[#FEF3C7] text-[#7C2D12]',
            'Revision requested' => 'bg-[#FFEDD5] text-[#9A3412]',
            'Up to date' => 'bg-[#D1FAE5] text-[#065F46]',
            'Nothing sent yet' => 'bg-[#EEF1F7] text-[#5B6478]',
        ];

        // Full class strings so Tailwind's JIT can detect them.
        $kpiCards = [
            [
                'label' => 'Assigned students',
                'value' => $kpis['students'],
                'icon' => 'fa-user-group',
                'tile' => 'bg-[#E8EFFF] text-[#1F45D6]',
                'note' => null,
                'alert' => false,
            ],
            [
                'label' => 'Weeks waiting for you',
                'value' => $kpis['weeks_waiting'],
                'icon' => 'fa-hourglass-half',
                'tile' => 'bg-[#FFF3D6] text-[#B45309]',
                'note' =>
                    $kpis['pending'] .
                    ' ' .
                    Str::plural('entry', $kpis['pending']) .
                    ' across ' .
                    $kpis['students_waiting'] .
                    ' ' .
                    Str::plural('student', $kpis['students_waiting']),
                'alert' => false,
            ],
            [
                'label' => 'Validated',
                'value' => $kpis['validated'],
                'icon' => 'fa-circle-check',
                'tile' => 'bg-[#DDF5E8] text-[#047857]',
                'note' =>
                    $kpis['reviewed_rate'] !== null
                        ? $kpis['reviewed_rate'] . '% of sent entries'
                        : 'Nothing sent yet',
                'alert' => false,
            ],
            [
                'label' => 'Revision requested',
                'value' => $kpis['revision'],
                'icon' => 'fa-rotate-left',
                'tile' => 'bg-[#FFE9E7] text-[#BE123C]',
                'note' => 'sent back to students',
                'alert' => false,
            ],
            [
                'label' => 'Overdue weeks',
                'value' => $kpis['overdue'],
                'icon' => 'fa-triangle-exclamation',
                'tile' => 'bg-[#F0EAFF] text-[#6D28D9]',
                'note' => 'waiting more than 7 days',
                'alert' => $kpis['overdue'] > 0,
            ],
        ];
    @endphp

    <div class="portal-font space-y-6">

        {{-- Flash messages --}}
        @if (session('success'))
            <div
                role="status"
                class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
            >
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div
                role="alert"
                class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
            >
                {{ session('error') }}
            </div>
        @endif

        {{-- Hero (same pattern as the student dashboard) --}}
        <section
            class="relative overflow-hidden rounded-[28px] bg-gradient-to-br from-[#4F6BFF] via-[#5E7CFF] to-[#B7C4FF] px-8 py-8 text-white shadow-[0_14px_30px_rgba(79,107,255,0.25)]"
        >
            <div
                class="pointer-events-none absolute -right-16 -top-20 h-72 w-72 rounded-full bg-white/10"
                aria-hidden="true"
            ></div>
            <div
                class="pointer-events-none absolute -bottom-24 right-40 h-64 w-64 rounded-full bg-white/10"
                aria-hidden="true"
            ></div>

            <div class="relative flex flex-wrap items-center justify-between gap-6">
                <div class="max-w-2xl">
                    <span
                        class="inline-flex items-center gap-2 rounded-full bg-white/20 px-3 py-1 text-xs font-bold"
                    >
                        <i
                            class="fa-regular fa-calendar"
                            aria-hidden="true"
                        ></i>
                        {{ $today->format('l, d F Y') }}
                    </span>
                    <h1 class="mt-3 text-[34px] font-extrabold leading-tight tracking-tight">
                        {{ $greeting }}, {{ $supervisor->name }}!
                    </h1>
                    <p class="mt-1.5 text-[15px] text-white/90">
                        {{ $supervisor->company_name }} &middot; {{ $supervisor->position }}.
                        Each student's week reaches you as one batch when it ends.
                    </p>

                    <div class="mt-5 flex flex-wrap gap-3">
                        @if ($kpis['weeks_waiting'] > 0)
                            <a
                                href="{{ route('supervisor.weeks.next') }}"
                                class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-white px-5 text-sm font-bold text-[#1F45D6] transition hover:bg-[#F1F4FF]"
                            >
                                <i
                                    class="fa-solid fa-clipboard-check"
                                    aria-hidden="true"
                                ></i>
                                Review oldest pending week
                            </a>
                        @endif
                        <a
                            href="#students"
                            class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-white/30 bg-white/15 px-5 text-sm font-bold text-white transition hover:bg-white/25"
                        >
                            <i
                                class="fa-solid fa-user-group"
                                aria-hidden="true"
                            ></i>
                            View all students
                        </a>
                    </div>
                </div>

                <div
                    class="hidden h-28 w-28 items-center justify-center rounded-full bg-white/20 text-5xl md:flex"
                    aria-hidden="true"
                >
                    <i class="fa-solid fa-clipboard-user"></i>
                </div>
            </div>
        </section>

        {{-- KPIs --}}
        <section
            aria-label="Key figures"
            class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 xl:grid-cols-5"
        >
            @foreach ($kpiCards as $kpi)
                <div @class([
                    'rounded-[20px] border bg-white px-[18px] py-4 shadow-[0_6px_18px_rgba(15,27,77,0.05)]',
                    'border-[#E3E8F2]' => !$kpi['alert'],
                    'border-[#FBCFCB]' => $kpi['alert'],
                ])>
                    <div class="flex items-center gap-3">
                        <span
                            class="{{ $kpi['tile'] }} flex h-11 w-11 flex-none items-center justify-center rounded-2xl"
                        >
                            <i
                                class="fa-solid {{ $kpi['icon'] }}"
                                aria-hidden="true"
                            ></i>
                        </span>
                        <div class="min-w-0">
                            <div class="truncate text-xs font-semibold text-[#5B6478]">
                                {{ $kpi['label'] }}</div>
                            <div class="text-[28px] font-extrabold leading-tight text-[#0F1B4D]">
                                {{ $kpi['value'] }}</div>
                        </div>
                    </div>
                    @if ($kpi['note'])
                        <p class="mt-2 text-xs text-[#5B6478]">{{ $kpi['note'] }}</p>
                    @endif
                </div>
            @endforeach
        </section>

        {{-- Two rows, two cards each. Cards in the same row stretch to the same height, so there are no gaps. --}}
        <section class="grid gap-5 lg:grid-cols-5">

            {{-- ROW 1 LEFT: Needs your review --}}
            <div class="{{ $card }} flex h-full flex-col p-6 lg:col-span-3">
                <div class="mb-1 flex items-center justify-between gap-3">
                    <h2 class="flex items-center gap-3 text-lg font-extrabold text-[#0F1B4D]">
                        <span
                            class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#FFE9E7] text-[#BE123C]"
                        >
                            <i
                                class="fa-solid fa-inbox text-sm"
                                aria-hidden="true"
                            ></i>
                        </span>
                        Needs your review
                    </h2>
                    <span class="rounded-full bg-[#EEF1F7] px-3 py-1 text-xs font-bold text-[#5B6478]">
                        {{ $needsReview->count() }} of {{ $kpis['students_waiting'] }}
                        {{ Str::plural('student', $kpis['students_waiting']) }}
                    </span>
                </div>
                <p class="mb-2 text-xs text-[#5B6478]">Most urgent first. Each button opens that
                    student's oldest waiting week.</p>

                <ul class="divide-y divide-[#EDF0F7]">
                    @forelse ($needsReview as $item)
                        @php $student = $item['student']; @endphp
                        <li class="flex flex-wrap items-center gap-x-4 gap-y-3 py-3.5">
                            <span
                                class="flex h-11 w-11 flex-none items-center justify-center rounded-full text-sm font-extrabold text-white"
                                style="background: {{ $avatarColor($student) }}"
                            >
                                {{ $initials($student->name) }}
                            </span>

                            <div class="min-w-0 flex-[1_1_200px]">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        class="truncate text-[15px] font-extrabold text-[#0F1B4D]">{{ $student->name }}</span>
                                    <span @class([
                                        'rounded-full px-2 py-0.5 text-[11px] font-bold',
                                        'bg-[#FFE4E6] text-[#9F1239]' => $item['priority'] === 'High',
                                        'bg-[#FEF3C7] text-[#7C2D12]' => $item['priority'] !== 'High',
                                    ])>
                                        {{ $item['priority'] === 'High' ? 'Overdue' : 'New' }}
                                    </span>
                                </div>
                                <div class="mt-0.5 text-xs text-[#5B6478]">
                                    {{ $item['weeks_waiting'] }}
                                    {{ Str::plural('week', $item['weeks_waiting']) }} waiting
                                    &middot; {{ $item['pending'] }}
                                    {{ Str::plural('entry', $item['pending']) }}
                                    &middot; oldest {{ $item['wait_days'] }}d
                                </div>
                            </div>

                            <a
                                href="{{ route('supervisor.students.show', ['id' => $student->student_id, 'week' => $item['oldest_week']]) }}"
                                class="flex min-h-10 items-center gap-2 rounded-xl bg-[#2F5BFF] px-4 text-sm font-bold text-white transition hover:bg-[#1F45D6]"
                            >
                                Review week {{ $item['oldest_week'] }}
                                <i
                                    class="fa-solid fa-arrow-right text-xs"
                                    aria-hidden="true"
                                ></i>
                            </a>
                        </li>
                    @empty
                        <li
                            class="mt-3 rounded-2xl border border-dashed border-[#C3CCEA] bg-[#F5F7FF] px-5 py-10 text-center text-sm text-[#5B6478]">
                            <i
                                class="fa-solid fa-circle-check mb-2 block text-3xl text-[#34D399]"
                                aria-hidden="true"
                            ></i>
                            You are all caught up. New weeks appear here when a student's week ends.
                        </li>
                    @endforelse
                </ul>

                @if ($kpis['students_waiting'] > $needsReview->count())
                    <a
                        href="{{ route('supervisor.dashboard', ['filter' => 'pending']) }}#students"
                        class="mt-auto block pt-3 text-right text-[13px] font-bold text-[#1F45D6] hover:underline"
                    >
                        See all {{ $kpis['students_waiting'] }} students with weeks waiting
                    </a>
                @endif
            </div>

            {{-- ROW 1 RIGHT: This week so far --}}
            <div class="{{ $card }} h-full p-6 lg:col-span-2">
                <h2 class="flex items-center gap-3 text-lg font-extrabold text-[#0F1B4D]">
                    <span
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#E8EFFF] text-[#1F45D6]"
                    >
                        <i
                            class="fa-regular fa-clock text-sm"
                            aria-hidden="true"
                        ></i>
                    </span>
                    This week so far
                </h2>
                <p class="mb-4 mt-1 text-xs text-[#5B6478]">
                    Not sent to you yet. Each week arrives after the student's last working day.
                </p>

                <div class="space-y-4">
                    @forelse ($inProgress as $row)
                        <div>
                            <div class="flex items-center justify-between gap-2 text-[13px]">
                                <span class="min-w-0 truncate font-bold text-[#0F1B4D]">
                                    {{ $row['student']->name }}
                                    <span class="font-medium text-[#5B6478]">&middot;
                                        W{{ $row['week'] }}</span>
                                </span>
                                <span class="flex-none font-bold text-[#334155]">
                                    {{ $row['logged'] }}/{{ $row['required'] }} days
                                    @if ($row['extra'])
                                        <span
                                            class="font-medium text-[#5B6478]">+{{ $row['extra'] }}</span>
                                    @endif
                                </span>
                            </div>
                            <div
                                class="mt-1.5 h-2 overflow-hidden rounded-full bg-[#E3E8F2]"
                                role="progressbar"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                aria-valuenow="{{ $row['percent'] }}"
                            >
                                <div
                                    class="h-full rounded-full bg-[#2F5BFF]"
                                    style="width: {{ min(100, max(0, (int) $row['percent'])) }}%"
                                ></div>
                            </div>
                            <div class="mt-1 text-[11px] text-[#94A3B8]">
                                Sent after {{ $row['closes']->format('D, d M') }}
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-[#5B6478]">No student is in an active internship week.
                        </p>
                    @endforelse
                </div>
            </div>

            {{-- ROW 2 LEFT: Tasks you assigned (partial; the wrapper makes it fill the row) --}}
            <div class="lg:col-span-3 [&>*]:h-full">
                @include('supervisor.dashboard.task-card')
            </div>

            {{-- ROW 2 RIGHT: Review progress --}}
            <div class="{{ $card }} h-full p-6 lg:col-span-2">
                <h2 class="mb-4 flex items-center gap-3 text-lg font-extrabold text-[#0F1B4D]">
                    <span
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#DDF5E8] text-[#047857]"
                    >
                        <i
                            class="fa-solid fa-chart-line text-sm"
                            aria-hidden="true"
                        ></i>
                    </span>
                    Review progress
                </h2>

                <div class="space-y-4">
                    @foreach ($trend as $row)
                        <div>
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-[13px] font-bold text-[#0F1B4D]">
                                        {{ $row['label'] }}</div>
                                    <div class="text-[11px] text-[#5B6478]">
                                        @if ($row['rate'] === null)
                                            No entries
                                        @else
                                            {{ $row['pending'] }} of {{ $row['total'] }} waiting
                                        @endif
                                    </div>
                                </div>
                                @if ($row['rate'] !== null)
                                    <span
                                        class="flex-none rounded-full bg-[#EEF1F7] px-2.5 py-0.5 text-xs font-extrabold text-[#334155]"
                                    >
                                        {{ $row['rate'] }}% reviewed
                                    </span>
                                @endif
                            </div>
                            <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-[#E3E8F2]">
                                <div
                                    class="h-full rounded-full bg-[#10B981]"
                                    style="width: {{ min(100, max(0, (int) ($row['rate'] ?? 0))) }}%"
                                ></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Student progress --}}
        <section
            id="students"
            class="{{ $card }} scroll-mt-6 p-6"
        >
            <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-xl font-extrabold text-[#0F1B4D]">Student progress</h2>
                    <p class="text-[13px] text-[#5B6478]">
                        Showing {{ $students->count() }} of {{ $totalStudents }} students
                    </p>
                </div>

                <form
                    method="GET"
                    action="{{ route('supervisor.dashboard') }}#students"
                    class="flex flex-wrap items-center gap-2"
                >
                    <label
                        for="q"
                        class="sr-only"
                    >Search name or matric</label>
                    <input
                        type="text"
                        id="q"
                        name="q"
                        value="{{ $q }}"
                        placeholder="Search name or matric"
                        class="min-h-10 rounded-xl border border-[#CBD3E3] px-3 text-sm focus:border-[#2F5BFF] focus:outline-none focus:ring-2 focus:ring-[#2F5BFF]/20"
                    >

                    <label
                        for="filter"
                        class="sr-only"
                    >Filter students</label>
                    <select
                        id="filter"
                        name="filter"
                        class="min-h-10 rounded-xl border border-[#CBD3E3] bg-white px-3 text-sm focus:border-[#2F5BFF] focus:outline-none focus:ring-2 focus:ring-[#2F5BFF]/20"
                    >
                        <option
                            value="all"
                            @selected($filter === 'all')
                        >All students</option>
                        <option
                            value="pending"
                            @selected($filter === 'pending')
                        >Weeks waiting</option>
                        <option
                            value="revision"
                            @selected($filter === 'revision')
                        >Revision requested</option>
                        <option
                            value="uptodate"
                            @selected($filter === 'uptodate')
                        >Up to date</option>
                    </select>

                    <button
                        type="submit"
                        class="min-h-10 rounded-xl bg-[#2F5BFF] px-5 text-sm font-bold text-white transition hover:bg-[#1F45D6]"
                    >
                        Apply
                    </button>
                </form>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-[#EDF0F7]">
                <table class="w-full min-w-[820px] text-left text-sm">
                    <caption class="sr-only">Progress of each assigned student</caption>
                    <thead
                        class="bg-[#F7F9FD] text-xs font-extrabold uppercase tracking-wider text-[#5B6478]"
                    >
                        <tr>
                            <th
                                scope="col"
                                class="px-4 py-3"
                            >Student</th>
                            <th
                                scope="col"
                                class="px-4 py-3"
                            >This week</th>
                            <th
                                scope="col"
                                class="px-4 py-3"
                            >Validated</th>
                            <th
                                scope="col"
                                class="px-4 py-3"
                            >Waiting</th>
                            <th
                                scope="col"
                                class="px-4 py-3 text-right"
                            >Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#EDF0F7]">
                        @forelse ($students as $row)
                            @php $student = $row['student']; @endphp
                            <tr class="transition hover:bg-[#F9FAFE]">
                                {{-- Student --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="flex h-10 w-10 flex-none items-center justify-center rounded-full text-xs font-extrabold text-white"
                                            style="background: {{ $avatarColor($student) }}"
                                        >
                                            {{ $initials($student->name) }}
                                        </span>
                                        <div class="min-w-0">
                                            <div class="font-bold text-[#0F1B4D]">{{ $student->name }}
                                            </div>
                                            <div class="text-xs text-[#5B6478]">
                                                {{ $student->matric_no }} &middot;
                                                {{ $student->course }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- This week (with a mini bar) --}}
                                <td class="w-44 px-4 py-3">
                                    @if ($row['live'])
                                        <div
                                            class="flex items-center justify-between text-xs font-bold text-[#334155]">
                                            <span>W{{ $row['live']['week'] }}</span>
                                            <span>
                                                {{ $row['live']['logged'] }}/{{ $row['live']['required'] }}
                                                days
                                                @if ($row['live']['extra'])
                                                    <span
                                                        class="font-medium text-[#5B6478]">+{{ $row['live']['extra'] }}</span>
                                                @endif
                                            </span>
                                        </div>
                                        <div
                                            class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-[#E3E8F2]">
                                            <div
                                                class="h-full rounded-full bg-[#2F5BFF]"
                                                style="width: {{ min(100, max(0, (int) $row['live']['percent'])) }}%"
                                            ></div>
                                        </div>
                                    @else
                                        <span class="text-xs text-[#94A3B8]">Not in a week</span>
                                    @endif
                                </td>

                                {{-- Validated (weeks sent + validated bar) --}}
                                <td class="w-44 px-4 py-3">
                                    @if ($row['total'] > 0)
                                        <div
                                            class="flex items-center justify-between text-xs font-bold text-[#334155]">
                                            <span>{{ $row['validated'] }}/{{ $row['total'] }}</span>
                                            <span
                                                class="font-medium text-[#5B6478]">{{ $row['weeks_sent'] }}
                                                {{ Str::plural('week', $row['weeks_sent']) }}
                                                sent</span>
                                        </div>
                                        <div
                                            class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-[#E3E8F2]">
                                            <div
                                                class="h-full rounded-full bg-[#10B981]"
                                                style="width: {{ min(100, max(0, (int) $row['validated_rate'])) }}%"
                                            ></div>
                                        </div>
                                    @else
                                        <span class="text-xs text-[#94A3B8]">Nothing sent yet</span>
                                    @endif
                                </td>

                                {{-- Waiting / status in ONE column --}}
                                <td class="px-4 py-3">
                                    @if ($row['weeks_waiting'] > 0)
                                        <span
                                            class="inline-block rounded-full bg-[#FEF3C7] px-2.5 py-1 text-xs font-bold text-[#7C2D12]"
                                        >
                                            {{ $row['weeks_waiting'] }}
                                            {{ Str::plural('week', $row['weeks_waiting']) }}
                                            &middot; {{ $row['pending'] }}
                                            {{ Str::plural('entry', $row['pending']) }}
                                        </span>
                                        <div class="mt-1 text-xs text-[#5B6478]">oldest
                                            {{ $row['oldest_days'] }}d</div>
                                    @else
                                        <span
                                            class="{{ $statusChip[$row['review_label']] ?? $statusChip['Nothing sent yet'] }} inline-block rounded-full px-2.5 py-1 text-xs font-bold"
                                        >
                                            {{ $row['review_label'] }}
                                        </span>
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        {{-- A null "week" is dropped from the query string automatically --}}
                                        <a
                                            href="{{ route('supervisor.students.show', ['id' => $student->student_id, 'week' => $row['oldest_week']]) }}"
                                            class="flex min-h-9 items-center rounded-xl bg-[#2F5BFF] px-4 text-xs font-bold text-white transition hover:bg-[#1F45D6]"
                                        >
                                            {{ $row['weeks_waiting'] > 0 ? 'Review' : 'Open' }}
                                        </a>
                                        <a
                                            href="{{ route('supervisor.messages.start', $student->student_id) }}"
                                            aria-label="Message {{ $student->name }}"
                                            title="Message {{ $student->name }}"
                                            class="flex h-9 w-9 items-center justify-center rounded-xl border border-[#CBD3E3] bg-white text-[#1F45D6] transition hover:bg-[#F3F5FC]"
                                        >
                                            <i
                                                class="fa-regular fa-comment"
                                                aria-hidden="true"
                                            ></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="5"
                                    class="px-4 py-10 text-center text-sm text-[#5B6478]"
                                >
                                    No students match.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (method_exists($students, 'links'))
                <div class="mt-4">{{ $students->links() }}</div>
            @endif
        </section>
    </div>
@endsection
