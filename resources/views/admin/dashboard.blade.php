@extends('layouts.app')

@section('content')
    @include('partials.google-fonts', ['includeCaveat' => false])

    @php
        use Illuminate\Support\Str;

        // Initials for avatars, e.g. "Farah Ismail" -> "FI"
        $initials = fn($name) => strtoupper(
            collect(preg_split('/\s+/', trim((string) $name)))
                ->filter()
                ->take(2)
                ->map(fn($part) => mb_substr($part, 0, 1))
                ->implode(''),
        ) ?:
        '?';

        $avatarColors = [
            'bg-blue-600',
            'bg-emerald-600',
            'bg-violet-600',
            'bg-slate-500',
            'bg-teal-600',
        ];

        $statCards = [
            [
                'label' => 'Students',
                'value' => $students,
                'note' => $studentsWithSupervisor . ' with a company supervisor',
                'icon' => 'fa-user-graduate',
                'surface' => 'border-[#D3E0FF] bg-[#EEF3FF]',
                'iconColor' => 'text-[#1F45D6]',
                'href' => '/admin/students',
            ],
            [
                'label' => 'Lecturers',
                'value' => $lecturers,
                'note' => 'faculty evaluators',
                'icon' => 'fa-user-tie',
                'surface' => 'border-[#DDD0FB] bg-[#F5F0FF]',
                'iconColor' => 'text-[#6D28D9]',
                'href' => '/admin/lecturers',
            ],
            [
                'label' => 'Company supervisors',
                'value' => $supervisors,
                'note' => $supervisorsWithStudents . ' with students',
                'icon' => 'fa-building-user',
                'surface' => 'border-[#BFEBD3] bg-[#EEFAF3]',
                'iconColor' => 'text-[#047857]',
                'href' => '/admin/supervisors',
            ],
            [
                'label' => 'Pending reviews',
                'value' => $awaitingReview,
                'note' => $overdueReviews . ' overdue (over 7 days)',
                'icon' => 'fa-hourglass-half',
                'surface' => 'border-[#FDE2B8] bg-[#FFF7EA]',
                'iconColor' => 'text-[#B45309]',
                'href' => '/admin/supervisors?state=waiting',
            ],
        ];

        $tone = [
            'rose' => [
                'icon' => 'bg-rose-100 text-rose-600',
                'pill' => 'bg-rose-100 text-rose-700',
            ],
            'amber' => [
                'icon' => 'bg-amber-100 text-amber-600',
                'pill' => 'bg-amber-100 text-amber-800',
            ],
            'blue' => [
                'icon' => 'bg-blue-100 text-blue-600',
                'pill' => 'bg-blue-100 text-blue-700',
            ],
        ];

        $statusBadge = [
            \App\Models\Logbook::SUPERVISOR_STATUS_VALIDATED => 'bg-emerald-100 text-emerald-700',
            \App\Models\Logbook::SUPERVISOR_STATUS_REVISION_REQUESTED =>
                'bg-rose-100 text-rose-700',
            \App\Models\Logbook::SUPERVISOR_STATUS_PENDING => 'bg-amber-100 text-amber-800',
        ];

        // 2. Needs attention: merge all "assignment" problems into ONE item
        $gapWords = ['without a company', 'with no student'];
        $allAttention = collect($attention);
        $gapItems = $allAttention->filter(
            fn($i) => Str::contains(Str::lower($i['title']), $gapWords),
        );
        $attentionItems = $allAttention
            ->reject(fn($i) => Str::contains(Str::lower($i['title']), $gapWords))
            ->values();

        if ($gapItems->isNotEmpty()) {
            $attentionItems->push([
                'title' => 'Assignment gaps',
                'detail' => $gapItems
                    ->map(fn($i) => (int) $i['count'] . ' ' . Str::lower($i['title']))
                    ->implode(', '),
                'count' => $gapItems->sum(fn($i) => (int) $i['count']),
                'href' => $gapItems->first()['href'],
                'icon' => 'fa-link-slash',
                'tone' => 'amber',
            ]);
        }

        $attentionVisible = $attentionItems->take(4);
        $attentionHidden = $attentionItems->slice(4);

        // 5. Workload lists: remove duplicate names, busiest first, top 5 only
        $lecturerTop = collect($lecturerWorkload)
            ->unique('name')
            ->sortByDesc('students_count')
            ->take(5)
            ->values();
        $supervisorTop = collect($supervisorWorkload)
            ->unique('name')
            ->sortByDesc('waiting_count')
            ->take(5)
            ->values();

        // 7. Latest entries: ONE row per student (their most recent entry), top 5.
        // Set to false if you want every entry listed, even several from the same student.
        $onePerStudent = true;
        $latestRows = collect($latestEntries);
        if ($onePerStudent) {
            $latestRows = $latestRows->unique(
                fn($e) => optional($e->student)->id ?? 'entry-' . $e->id,
            );
        }
        $latestRows = $latestRows->take(5);
    @endphp

    <div
        class="space-y-6"
        style="font-family:'Plus Jakarta Sans',ui-sans-serif,system-ui,sans-serif"
    >

        {{-- Hero card follows the lecturer dashboard while retaining admin shortcuts. --}}
        <section
            class="relative overflow-hidden rounded-[24px] bg-gradient-to-br from-[#4F6BFF] via-[#5E7CFF] to-[#B7C4FF] px-6 py-5 text-white shadow-[0_10px_24px_rgba(79,107,255,0.22)]"
        >
            <div
                class="pointer-events-none absolute -right-12 -top-16 h-52 w-52 rounded-full bg-white/10"
                aria-hidden="true"
            ></div>
            <div
                class="pointer-events-none absolute -bottom-20 right-32 h-44 w-44 rounded-full bg-white/10"
                aria-hidden="true"
            ></div>

            <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <h1 class="text-2xl font-extrabold leading-tight tracking-tight">
                        {{ $greeting }}, {{ $adminName }}
                    </h1>
                    <p class="mt-1 flex flex-wrap items-center gap-x-2 text-sm text-white/90">
                        <span>{{ $todayLabel }}</span>
                        <span aria-hidden="true">&middot;</span>
                        <span
                            class="{{ $databaseStatus === 'Connected' ? 'text-emerald-100' : 'text-rose-100' }} inline-flex items-center gap-1.5"
                            title="Database: {{ $databaseStatus }}"
                        >
                            <i class="fa-solid fa-circle text-[7px]"></i>
                            {{ $databaseStatus === 'Connected' ? 'System online' : 'Database problem' }}
                        </span>
                    </p>
                </div>
                <div class="flex shrink-0 flex-wrap gap-2">
                    <a
                        href="/admin/reports"
                        class="inline-flex min-h-10 items-center gap-2 rounded-xl border border-white/40 bg-white/10 px-4 text-sm font-bold text-white transition hover:bg-white/20"
                    >
                        <i class="fa-solid fa-chart-line"></i> Reports
                    </a>
                    <a
                        href="/admin/students/create"
                        class="inline-flex min-h-10 items-center gap-2 rounded-xl bg-white px-4 text-sm font-bold text-[#1F45D6] shadow-sm transition hover:bg-[#F3F5FC]"
                    >
                        <i class="fa-solid fa-plus"></i> Add Student
                    </a>
                </div>
            </div>
        </section>

        @if (session('success'))
            <div
                class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}</div>
        @endif

        {{-- KPI cards use the lecturer dashboard's tinted card treatment. --}}
        <section class="grid grid-cols-2 gap-3.5 xl:grid-cols-4">
            @foreach ($statCards as $card)
                <a
                    href="{{ $card['href'] }}"
                    class="{{ $card['surface'] }} rounded-[20px] border p-4 transition hover:shadow-md"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="{{ $card['iconColor'] }} flex h-11 w-11 flex-none items-center justify-center rounded-2xl bg-white"
                        >
                            <i class="fa-solid {{ $card['icon'] }}"></i>
                        </div>
                        <p class="min-w-0 text-xs font-semibold text-[#334155]">{{ $card['label'] }}</p>
                    </div>
                    <p class="mt-2 text-[28px] font-extrabold leading-tight text-slate-900">{{ $card['value'] }}</p>
                    <p class="mt-2 text-xs text-[#5B6478]">{{ $card['note'] }}</p>
                </a>
            @endforeach
        </section>

        {{-- 2 + 4. Needs attention  |  Review status --}}
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <div
                class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/40 xl:col-span-2">
                <div class="mb-2 flex items-center justify-between border-b border-slate-100 pb-4">
                    <h3 class="flex items-center gap-2 text-base font-bold text-rose-700">
                        <i class="fa-solid fa-triangle-exclamation"></i> Needs your attention
                    </h3>
                    <span class="text-xs font-semibold text-slate-400">{{ $attentionItems->count() }}
                        {{ $attentionItems->count() === 1 ? 'item' : 'items' }}</span>
                </div>

                @forelse($attentionVisible as $item)
                    <div
                        class="{{ !$loop->last || $attentionHidden->isNotEmpty() ? 'border-b border-slate-100' : '' }} flex flex-col gap-3 py-3 sm:flex-row sm:items-center">
                        <div class="flex min-w-0 flex-1 items-center gap-3">
                            <div
                                class="{{ $tone[$item['tone']]['icon'] ?? $tone['blue']['icon'] }} flex h-10 w-10 shrink-0 items-center justify-center rounded-full">
                                <i class="fa-solid {{ $item['icon'] }}"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-800">{{ $item['title'] }}
                                </p>
                                <p
                                    class="truncate text-xs text-slate-500"
                                    title="{{ $item['detail'] }}"
                                >{{ $item['detail'] }}</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-3 sm:justify-end">
                            <span
                                class="{{ $tone[$item['tone']]['pill'] ?? $tone['blue']['pill'] }} rounded-full px-3 py-1 text-xs font-semibold"
                            >{{ $item['count'] }}</span>
                            <a
                                href="{{ $item['href'] }}"
                                class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-blue-700"
                            >View</a>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center py-10 text-center">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                            <i class="fa-solid fa-check text-lg"></i>
                        </div>
                        <p class="mt-3 text-sm font-semibold text-slate-800">All clear</p>
                        <p class="text-xs text-slate-500">Every student is assigned, dated and up to
                            date.</p>
                    </div>
                @endforelse

                @if ($attentionHidden->isNotEmpty())
                    <div
                        id="attentionMore"
                        class="hidden"
                    >
                        @foreach ($attentionHidden as $item)
                            <div
                                class="{{ !$loop->last ? 'border-b border-slate-100' : '' }} flex flex-col gap-3 py-3 sm:flex-row sm:items-center">
                                <div class="flex min-w-0 flex-1 items-center gap-3">
                                    <div
                                        class="{{ $tone[$item['tone']]['icon'] ?? $tone['blue']['icon'] }} flex h-10 w-10 shrink-0 items-center justify-center rounded-full">
                                        <i class="fa-solid {{ $item['icon'] }}"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-slate-800">
                                            {{ $item['title'] }}</p>
                                        <p
                                            class="truncate text-xs text-slate-500"
                                            title="{{ $item['detail'] }}"
                                        >{{ $item['detail'] }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-3 sm:justify-end">
                                    <span
                                        class="{{ $tone[$item['tone']]['pill'] ?? $tone['blue']['pill'] }} rounded-full px-3 py-1 text-xs font-semibold"
                                    >{{ $item['count'] }}</span>
                                    <a
                                        href="{{ $item['href'] }}"
                                        class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-blue-700"
                                    >View</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <button
                        type="button"
                        id="attentionToggle"
                        class="mt-3 text-xs font-semibold text-blue-600 hover:underline"
                    >
                        View all ({{ $attentionItems->count() }})
                    </button>
                @endif
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/40">
                <h3 class="mb-4 text-base font-bold text-slate-800">Review status</h3>
                @if ($logbooks > 0)
                    <div class="relative mx-auto h-36 w-36">
                        <canvas id="statusChart"></canvas>
                        <div
                            class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                            <span
                                class="text-2xl font-extrabold text-slate-900">{{ $validatedPercent }}%</span>
                            <span class="text-[11px] text-slate-500">validated</span>
                        </div>
                    </div>
                    <div class="mt-5 space-y-2 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-2 font-medium text-slate-700"><span
                                    class="h-2.5 w-2.5 rounded-full bg-emerald-500"
                                ></span>Validated</span>
                            <span class="font-semibold text-slate-800">{{ $validated }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-2 font-medium text-slate-700"><span
                                    class="h-2.5 w-2.5 rounded-full bg-amber-400"
                                ></span>Pending</span>
                            <span class="font-semibold text-slate-800">{{ $pending }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-2 font-medium text-slate-700"><span
                                    class="h-2.5 w-2.5 rounded-full bg-rose-500"
                                ></span>Revision requested</span>
                            <span class="font-semibold text-slate-800">{{ $revision }}</span>
                        </div>
                    </div>
                @else
                    <p class="py-10 text-center text-sm text-slate-500">No logbook entries yet.</p>
                @endif
            </div>
        </div>

        {{-- 4 + 5. Entries chart  |  Workload (tabs) --}}
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <div
                class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/40 xl:col-span-2">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-base font-bold text-slate-800">Logbook entries, last 6 months</h3>
                    <span class="text-xs font-semibold text-slate-400">{{ $entriesThisWeek }} this week
                        &middot; {{ $entriesToday }} today</span>
                </div>
                <div class="relative h-60">
                    <canvas id="monthlyTrendChart"></canvas>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/40">
                <div class="mb-4 flex items-center justify-between">
                    <div
                        class="inline-flex rounded-lg bg-slate-100 p-1 text-xs font-semibold"
                        role="tablist"
                    >
                        <button
                            type="button"
                            data-tab="lecturers"
                            class="workload-tab rounded-md bg-white px-3 py-1.5 text-slate-800 shadow-sm"
                        >Lecturers</button>
                        <button
                            type="button"
                            data-tab="supervisors"
                            class="workload-tab rounded-md px-3 py-1.5 text-slate-500"
                        >Supervisors</button>
                    </div>
                    <a
                        id="workloadViewAll"
                        href="/admin/lecturers"
                        class="text-xs font-semibold text-blue-600 hover:underline"
                    >View all</a>
                </div>

                <div id="panel-lecturers">
                    @forelse($lecturerTop as $lecturer)
                        <div
                            class="{{ !$loop->last ? 'border-b border-slate-100' : '' }} flex items-center gap-3 py-2.5">
                            <div
                                class="{{ $avatarColors[$loop->index % count($avatarColors)] }} flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white">
                                {{ $initials($lecturer->name) }}</div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-800">
                                    {{ $lecturer->name }}</p>
                                <p class="truncate text-xs text-slate-400">
                                    {{ $lecturer->faculty ?: 'No faculty set' }}</p>
                            </div>
                            <span
                                class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700"
                            >{{ $lecturer->students_count }}
                                {{ $lecturer->students_count === 1 ? 'student' : 'students' }}</span>
                        </div>
                    @empty
                        <p class="py-6 text-center text-sm text-slate-500">No lecturers yet.</p>
                    @endforelse
                </div>

                <div
                    id="panel-supervisors"
                    class="hidden"
                >
                    @forelse($supervisorTop as $supervisor)
                        <div
                            class="{{ !$loop->last ? 'border-b border-slate-100' : '' }} flex items-center gap-3 py-2.5">
                            <div
                                class="{{ $avatarColors[($loop->index + 2) % count($avatarColors)] }} flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white">
                                {{ $initials($supervisor->name) }}</div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-800">
                                    {{ $supervisor->name }}</p>
                                <p class="truncate text-xs text-slate-400">
                                    {{ $supervisor->company_name ?: 'No company' }} &middot;
                                    {{ $supervisor->students_count }}
                                    {{ $supervisor->students_count === 1 ? 'student' : 'students' }}
                                </p>
                            </div>
                            @if ($supervisor->waiting_count > 0)
                                <span
                                    class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800"
                                >{{ $supervisor->waiting_count }} waiting</span>
                            @else
                                <span
                                    class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700"
                                >Up to date</span>
                            @endif
                        </div>
                    @empty
                        <p class="py-6 text-center text-sm text-slate-500">No company supervisors yet.
                        </p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- 7. Latest entries: one row per student --}}
        <div
            class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-200/40">
            <div class="flex items-center justify-between px-6 py-5">
                <div>
                    <h3 class="text-base font-bold text-slate-800">Latest logbook entries</h3>
                    <p class="mt-0.5 text-xs text-slate-400">
                        {{ $onePerStudent ? 'Most recent entry for each student' : 'Most recent submissions' }}
                    </p>
                </div>
                <a
                    href="/admin/reports"
                    class="text-xs font-semibold text-blue-600 hover:underline"
                >View all</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-6 py-3 text-left">Student</th>
                            <th class="px-6 py-3 text-left">Company supervisor</th>
                            <th class="px-6 py-3 text-left">Submitted</th>
                            <th class="px-6 py-3 text-left">Status</th>
                            <th class="px-6 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                        @forelse($latestRows as $entry)
                            @php
                                $status =
                                    $entry->supervisor_status ?:
                                    \App\Models\Logbook::SUPERVISOR_STATUS_PENDING;
                                $studentName = optional($entry->student)->name ?? 'Unknown';
                                $dot =
                                    [
                                        \App\Models\Logbook::SUPERVISOR_STATUS_VALIDATED =>
                                            'bg-emerald-500',
                                        \App\Models\Logbook::SUPERVISOR_STATUS_REVISION_REQUESTED =>
                                            'bg-rose-500',
                                        \App\Models\Logbook::SUPERVISOR_STATUS_PENDING =>
                                            'bg-amber-500',
                                    ][$status] ?? 'bg-slate-400';
                            @endphp
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-6 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="{{ $avatarColors[$loop->index % count($avatarColors)] }} flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white">
                                            {{ $initials($studentName) }}</div>
                                        <span
                                            class="font-semibold text-slate-800">{{ $studentName }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-3.5">
                                    @if (optional($entry->student)->supervisor)
                                        <span
                                            class="font-medium text-slate-700">{{ $entry->student->supervisor->name }}</span>
                                        <span
                                            class="block text-xs text-slate-400">{{ $entry->student->supervisor->company_name }}</span>
                                    @else
                                        <span
                                            class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-400"
                                        >
                                            <i class="fa-solid fa-user-slash"></i> Unassigned
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-3.5">
                                    <span
                                        class="font-medium text-slate-700">{{ $entry->created_at?->format('d M Y') }}</span>
                                    <span
                                        class="block text-xs text-slate-400">{{ $entry->created_at?->diffForHumans() }}</span>
                                </td>
                                <td class="px-6 py-3.5">
                                    <span
                                        class="{{ $statusBadge[$status] ?? 'bg-slate-100 text-slate-600' }} inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
                                    >
                                        <span
                                            class="{{ $dot }} h-1.5 w-1.5 rounded-full"></span>
                                        {{ $status }}
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 text-right">
                                    @if (optional($entry->student)->id)
                                        {{-- Adjust this URL if your student detail route is different --}}
                                        <a
                                            href="/admin/students/{{ $entry->student->id }}"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-blue-300 hover:text-blue-600"
                                        >
                                            View <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="5"
                                    class="px-6 py-10 text-center text-slate-500"
                                >No logbook entries yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        (function() {
            // Entries chart (bar chart reads better than a flat line)
            var trendCanvas = document.getElementById('monthlyTrendChart');
            if (trendCanvas) {
                new Chart(trendCanvas, {
                    type: 'bar',
                    data: {
                        labels: @json($trendLabels),
                        datasets: [{
                            label: 'Entries',
                            data: @json($trendValues),
                            backgroundColor: '#2563eb',
                            borderRadius: 6,
                            maxBarThickness: 36
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                },
                                grid: {
                                    color: '#f1f5f9'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            }

            // Review status donut
            var statusCanvas = document.getElementById('statusChart');
            if (statusCanvas) {
                new Chart(statusCanvas, {
                    type: 'doughnut',
                    data: {
                        labels: ['Validated', 'Pending', 'Revision requested'],
                        datasets: [{
                            data: [{{ $validated }}, {{ $pending }},
                                {{ $revision }}
                            ],
                            backgroundColor: ['#10b981', '#fbbf24', '#f43f5e'],
                            borderColor: '#ffffff',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '72%',
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            }

            // Workload tabs: Lecturers | Supervisors
            var tabs = document.querySelectorAll('.workload-tab');
            var viewAll = document.getElementById('workloadViewAll');
            var links = {
                lecturers: '/admin/lecturers',
                supervisors: '/admin/supervisors'
            };
            tabs.forEach(function(tab) {
                tab.addEventListener('click', function() {
                    var name = tab.getAttribute('data-tab');
                    tabs.forEach(function(t) {
                        var active = t === tab;
                        t.classList.toggle('bg-white', active);
                        t.classList.toggle('text-slate-800', active);
                        t.classList.toggle('shadow-sm', active);
                        t.classList.toggle('text-slate-500', !active);
                    });
                    document.getElementById('panel-lecturers').classList.toggle(
                        'hidden', name !== 'lecturers');
                    document.getElementById('panel-supervisors').classList.toggle(
                        'hidden', name !== 'supervisors');
                    viewAll.setAttribute('href', links[name]);
                });
            });

            // "View all" for attention items
            var toggle = document.getElementById('attentionToggle');
            var more = document.getElementById('attentionMore');
            if (toggle && more) {
                toggle.addEventListener('click', function() {
                    var open = more.classList.toggle('hidden') === false;
                    toggle.textContent = open ? 'Show less' :
                        'View all ({{ $attentionItems->count() }})';
                });
            }
        })();
    </script>
@endsection
