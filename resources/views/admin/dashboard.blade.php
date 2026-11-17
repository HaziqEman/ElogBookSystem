@extends('layouts.app')

@section('content')
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

        // 1. Four KPI cards only (white cards, one accent colour per icon)
        $statCards = [
            [
                'label' => 'Students',
                'value' => $students,
                'note' => $studentsWithSupervisor . ' with a company supervisor',
                'icon' => 'fa-user-graduate',
                'iconColor' => 'bg-blue-50 text-blue-600',
                'href' => '/admin/students',
            ],
            [
                'label' => 'Lecturers',
                'value' => $lecturers,
                'note' => 'faculty evaluators',
                'icon' => 'fa-user-tie',
                'iconColor' => 'bg-emerald-50 text-emerald-600',
                'href' => '/admin/lecturers',
            ],
            [
                'label' => 'Company supervisors',
                'value' => $supervisors,
                'note' => $supervisorsWithStudents . ' with students',
                'icon' => 'fa-building-user',
                'iconColor' => 'bg-violet-50 text-violet-600',
                'href' => '/admin/supervisors',
            ],
            [
                'label' => 'Pending reviews',
                'value' => $awaitingReview,
                'note' => $overdueReviews . ' overdue (over 7 days)',
                'icon' => 'fa-hourglass-half',
                'iconColor' => 'bg-amber-50 text-amber-600',
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
            'Validated' => 'bg-emerald-100 text-emerald-700',
            'Revision Requested' => 'bg-rose-100 text-rose-700',
            'Pending' => 'bg-amber-100 text-amber-800',
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
    @endphp

    <div class="space-y-6">

        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">{{ $greeting }}, {{ $adminName }}
                </h2>
                <p class="mt-1 flex flex-wrap items-center gap-x-3 text-sm text-slate-500">
                    <span>{{ $todayLabel }}</span>
                    <span
                        class="{{ $databaseStatus === 'Connected' ? 'text-emerald-600' : 'text-rose-600' }} inline-flex items-center gap-1.5"
                        title="Database: {{ $databaseStatus }}"
                    >
                        <i class="fa-solid fa-circle text-[7px]"></i>
                        {{ $databaseStatus === 'Connected' ? 'System online' : 'Database problem' }}
                    </span>
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a
                    href="/admin/reports"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                    <i class="fa-solid fa-chart-line"></i> Reports
                </a>
                <a
                    href="/admin/students/create"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700"
                >
                    <i class="fa-solid fa-plus"></i> Add Student
                </a>
            </div>
        </div>

        @if (session('success'))
            <div
                class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}</div>
        @endif

        {{-- 1. KPI row (4 cards) --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ($statCards as $card)
                <a
                    href="{{ $card['href'] }}"
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/40 transition hover:border-slate-300 hover:shadow-md"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="{{ $card['iconColor'] }} flex h-10 w-10 shrink-0 items-center justify-center rounded-xl">
                            <i class="fa-solid {{ $card['icon'] }}"></i>
                        </div>
                        <p class="text-sm font-semibold text-slate-600">{{ $card['label'] }}</p>
                    </div>
                    <p class="mt-4 text-4xl font-extrabold text-slate-900">{{ $card['value'] }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $card['note'] }}</p>
                </a>
            @endforeach
        </div>

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

        {{-- 7. Latest 5 entries, fewer columns --}}
        <div
            class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-200/40">
            <div class="flex items-center justify-between px-6 py-5">
                <h3 class="text-base font-bold text-slate-800">Latest logbook entries</h3>
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
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                        @forelse($latestEntries->take(5) as $entry)
                            @php $status = $entry->supervisor_status ?: 'Pending'; @endphp
                            <tr>
                                <td class="px-6 py-3.5 font-medium text-slate-800">
                                    {{ optional($entry->student)->name ?? 'Unknown' }}</td>
                                <td class="px-6 py-3.5">
                                    @if (optional($entry->student)->supervisor)
                                        {{ $entry->student->supervisor->name }}
                                        <span
                                            class="block text-xs text-slate-400">{{ $entry->student->supervisor->company_name }}</span>
                                    @else
                                        <span class="text-slate-400">Unassigned</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3.5">
                                    {{ $entry->created_at?->format('d M Y') }}
                                    <span
                                        class="block text-xs text-slate-400">{{ $entry->created_at?->diffForHumans() }}</span>
                                </td>
                                <td class="px-6 py-3.5">
                                    <span
                                        class="{{ $statusBadge[$status] ?? 'bg-slate-100 text-slate-600' }} rounded-full px-2.5 py-1 text-xs font-semibold"
                                    >{{ $status }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="4"
                                    class="px-6 py-6 text-center text-slate-500"
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
