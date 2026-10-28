@extends('layouts.app')

@section('content')
@php
    // Initials for avatars, e.g. "Farah Ismail" -> "FI"
    $initials = fn ($name) => strtoupper(collect(preg_split('/\s+/', trim((string) $name)))
        ->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode('')) ?: '?';

    $avatarColors = ['bg-emerald-600', 'bg-rose-600', 'bg-blue-600', 'bg-slate-500', 'bg-teal-600'];

    $statCards = [
        ['label' => 'Students', 'value' => $students, 'note' => $studentsWithSupervisor.' with a company supervisor', 'icon' => 'fa-user-graduate', 'card' => 'bg-blue-50 border-blue-100', 'iconColor' => 'text-blue-600', 'href' => '/admin/students'],
        ['label' => 'Lecturers', 'value' => $lecturers, 'note' => 'faculty evaluators', 'icon' => 'fa-user-tie', 'card' => 'bg-emerald-50 border-emerald-100', 'iconColor' => 'text-emerald-600', 'href' => '/admin/lecturers'],
        ['label' => 'Company supervisors', 'value' => $supervisors, 'note' => $supervisorsWithStudents.' with students', 'icon' => 'fa-building-user', 'card' => 'bg-violet-50 border-violet-100', 'iconColor' => 'text-violet-600', 'href' => '/admin/supervisors'],
        ['label' => 'Logbook entries', 'value' => $logbooks, 'note' => $entriesToday.' submitted today', 'icon' => 'fa-book', 'card' => 'bg-sky-50 border-sky-100', 'iconColor' => 'text-sky-600', 'href' => '/admin/reports'],
        ['label' => 'Waiting for review', 'value' => $awaitingReview, 'note' => $overdueReviews.' overdue (over 7 days)', 'icon' => 'fa-hourglass-half', 'card' => 'bg-amber-50 border-amber-100', 'iconColor' => 'text-amber-600', 'href' => '/admin/supervisors?state=waiting'],
    ];

    $tone = [
        'rose' => ['icon' => 'bg-rose-100 text-rose-600', 'pill' => 'bg-rose-100 text-rose-700'],
        'amber' => ['icon' => 'bg-amber-100 text-amber-600', 'pill' => 'bg-amber-100 text-amber-800'],
        'blue' => ['icon' => 'bg-blue-100 text-blue-600', 'pill' => 'bg-blue-100 text-blue-700'],
    ];

    $statusBadge = [
        'Validated' => 'bg-emerald-100 text-emerald-700',
        'Revision Requested' => 'bg-rose-100 text-rose-700',
        'Pending' => 'bg-amber-100 text-amber-800',
    ];
@endphp

<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">{{ $greeting }}, {{ $adminName }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $todayLabel }} &middot; Here is how the internship programme is doing.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="/admin/reports" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                <i class="fa-solid fa-chart-line"></i> Reports
            </a>
            <a href="/admin/students/create" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
                <i class="fa-solid fa-plus"></i> Add Student
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    {{-- Headline numbers --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-5">
        @foreach($statCards as $card)
            <a href="{{ $card['href'] }}" class="rounded-2xl border {{ $card['card'] }} p-5 transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white shadow-sm {{ $card['iconColor'] }}">
                        <i class="fa-solid {{ $card['icon'] }}"></i>
                    </div>
                    <p class="text-xs font-semibold text-slate-600">{{ $card['label'] }}</p>
                </div>
                <p class="mt-4 text-4xl font-extrabold text-slate-900">{{ $card['value'] }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $card['note'] }}</p>
            </a>
        @endforeach
    </div>

    {{-- Needs attention + Quick actions --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="rounded-[16px] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/40 xl:col-span-2">
            <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-4">
                <h3 class="flex items-center gap-2 text-base font-bold text-rose-700">
                    <i class="fa-solid fa-triangle-exclamation"></i> Needs your attention
                </h3>
                <span class="text-xs font-semibold text-slate-400">{{ count($attention) }} {{ count($attention) === 1 ? 'item' : 'items' }}</span>
            </div>

            @forelse($attention as $item)
                <div class="flex flex-col gap-3 py-3 sm:flex-row sm:items-center {{ !$loop->last ? 'border-b border-slate-100' : '' }}">
                    <div class="flex min-w-0 flex-1 items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $tone[$item['tone']]['icon'] }}">
                            <i class="fa-solid {{ $item['icon'] }}"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-800">{{ $item['title'] }}</p>
                            <p class="truncate text-xs text-slate-500" title="{{ $item['detail'] }}">{{ $item['detail'] }}</p>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-3 sm:justify-end">
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $tone[$item['tone']]['pill'] }}">{{ $item['count'] }}</span>
                        <a href="{{ $item['href'] }}" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-blue-700">View</a>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center py-10 text-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                        <i class="fa-solid fa-check text-lg"></i>
                    </div>
                    <p class="mt-3 text-sm font-semibold text-slate-800">All clear</p>
                    <p class="text-xs text-slate-500">Every student is assigned, dated and up to date.</p>
                </div>
            @endforelse
        </div>

        <div class="rounded-[16px] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/40">
            <h3 class="mb-4 text-base font-bold text-slate-800">Quick actions</h3>
            <div class="grid grid-cols-2 gap-3">
                <a href="/admin/students/create" class="rounded-2xl border border-blue-100 bg-blue-50 p-4 text-sm font-semibold text-slate-800 transition hover:-translate-y-0.5 hover:shadow-md">
                    <span class="mb-3 flex h-9 w-9 items-center justify-center rounded-xl bg-blue-600 text-white"><i class="fa-solid fa-user-plus"></i></span>
                    Add student
                </a>
                <a href="/admin/lecturers/create" class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 text-sm font-semibold text-slate-800 transition hover:-translate-y-0.5 hover:shadow-md">
                    <span class="mb-3 flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-600 text-white"><i class="fa-solid fa-user-tie"></i></span>
                    Add lecturer
                </a>
                <a href="/admin/supervisors/create" class="rounded-2xl border border-violet-100 bg-violet-50 p-4 text-sm font-semibold text-slate-800 transition hover:-translate-y-0.5 hover:shadow-md">
                    <span class="mb-3 flex h-9 w-9 items-center justify-center rounded-xl bg-violet-600 text-white"><i class="fa-solid fa-building-user"></i></span>
                    Add supervisor
                </a>
                <a href="/admin/reports/export/excel" class="rounded-2xl border border-rose-100 bg-rose-50 p-4 text-sm font-semibold text-slate-800 transition hover:-translate-y-0.5 hover:shadow-md">
                    <span class="mb-3 flex h-9 w-9 items-center justify-center rounded-xl bg-rose-600 text-white"><i class="fa-solid fa-file-export"></i></span>
                    Export report
                </a>
            </div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="rounded-[16px] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/40 xl:col-span-2">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-800">Logbook entries, last 6 months</h3>
                <span class="text-xs font-semibold text-slate-400">{{ $entriesThisWeek }} this week</span>
            </div>
            <div class="relative h-64">
                <canvas id="monthlyTrendChart"></canvas>
            </div>
        </div>

        <div class="rounded-[16px] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/40">
            <h3 class="mb-4 text-base font-bold text-slate-800">Review status</h3>
            @if($logbooks > 0)
                <div class="relative mx-auto h-44 w-44">
                    <canvas id="statusChart"></canvas>
                    <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-3xl font-extrabold text-slate-900">{{ $validatedPercent }}%</span>
                        <span class="text-[11px] text-slate-500">validated</span>
                    </div>
                </div>
                <div class="mt-4 space-y-2 text-sm">
                    <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                        <span class="flex items-center gap-2 font-medium text-slate-700"><span class="h-3 w-3 rounded-full bg-emerald-500"></span>Validated</span>
                        <span class="font-semibold text-slate-800">{{ $validated }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                        <span class="flex items-center gap-2 font-medium text-slate-700"><span class="h-3 w-3 rounded-full bg-amber-400"></span>Pending</span>
                        <span class="font-semibold text-slate-800">{{ $pending }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                        <span class="flex items-center gap-2 font-medium text-slate-700"><span class="h-3 w-3 rounded-full bg-rose-500"></span>Revision requested</span>
                        <span class="font-semibold text-slate-800">{{ $revision }}</span>
                    </div>
                </div>
            @else
                <p class="py-10 text-center text-sm text-slate-500">No logbook entries yet.</p>
            @endif
        </div>
    </div>

    {{-- Review progress + workloads --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="rounded-[16px] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/40">
            <h3 class="mb-4 text-base font-bold text-slate-800">Review progress, last 4 weeks</h3>
            <div class="space-y-4">
                @foreach($reviewProgress as $week)
                    <div>
                        <div class="mb-1 flex items-center justify-between gap-2 text-xs">
                            <span class="font-semibold text-slate-700">{{ $week['label'] }}</span>
                            <span class="text-slate-500"><span class="font-bold text-slate-800">{{ $week['percent'] }}%</span> reviewed &middot; {{ $week['waiting'] }} waiting</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-rose-100">
                            <div class="h-full rounded-full bg-emerald-500" style="width: {{ $week['percent'] }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="mt-5 text-xs text-slate-400">Validated and revision-requested entries count as reviewed.</p>
        </div>

        <div class="rounded-[16px] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/40">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-800">Lecturer workload</h3>
                <a href="/admin/lecturers" class="text-xs font-semibold text-blue-600 hover:underline">View all</a>
            </div>
            @forelse($lecturerWorkload as $lecturer)
                <div class="flex items-center gap-3 py-2.5 {{ !$loop->last ? 'border-b border-slate-100' : '' }}">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white {{ $avatarColors[$loop->index % count($avatarColors)] }}">{{ $initials($lecturer->name) }}</div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-slate-800">{{ $lecturer->name }}</p>
                        <p class="truncate text-xs text-slate-400">{{ $lecturer->faculty ?: 'No faculty set' }}</p>
                    </div>
                    <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">{{ $lecturer->students_count }} {{ $lecturer->students_count === 1 ? 'student' : 'students' }}</span>
                </div>
            @empty
                <p class="py-6 text-center text-sm text-slate-500">No lecturers yet.</p>
            @endforelse
        </div>

        <div class="rounded-[16px] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/40">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-800">Supervisor workload</h3>
                <a href="/admin/supervisors" class="text-xs font-semibold text-blue-600 hover:underline">View all</a>
            </div>
            @forelse($supervisorWorkload as $supervisor)
                <div class="flex items-center gap-3 py-2.5 {{ !$loop->last ? 'border-b border-slate-100' : '' }}">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white {{ $avatarColors[($loop->index + 2) % count($avatarColors)] }}">{{ $initials($supervisor->name) }}</div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-slate-800">{{ $supervisor->name }}</p>
                        <p class="truncate text-xs text-slate-400">{{ $supervisor->company_name ?: 'No company' }} &middot; {{ $supervisor->students_count }} {{ $supervisor->students_count === 1 ? 'student' : 'students' }}</p>
                    </div>
                    @if($supervisor->waiting_count > 0)
                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">{{ $supervisor->waiting_count }} waiting</span>
                    @else
                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Up to date</span>
                    @endif
                </div>
            @empty
                <p class="py-6 text-center text-sm text-slate-500">No company supervisors yet.</p>
            @endforelse
        </div>
    </div>

    {{-- Latest entries --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs">
        <div class="flex items-center justify-between px-6 py-5">
            <h3 class="text-base font-bold text-slate-800">Latest logbook entries</h3>
            <span class="text-xs font-semibold text-slate-400">Latest {{ $latestEntries->count() }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left">Student</th>
                        <th class="px-4 py-3 text-left">Lecturer</th>
                        <th class="px-4 py-3 text-left">Company Supervisor</th>
                        <th class="px-4 py-3 text-left">Submitted</th>
                        <th class="px-4 py-3 text-left">Supervisor status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                    @forelse($latestEntries as $entry)
                        @php $status = $entry->supervisor_status ?: 'Pending'; @endphp
                        <tr>
                            <td class="px-4 py-4 font-medium text-slate-800">{{ optional($entry->student)->name ?? 'Unknown' }}</td>
                            <td class="px-4 py-4">{{ optional(optional($entry->student)->lecturer)->name ?? 'Unassigned' }}</td>
                            <td class="px-4 py-4">
                                @if(optional($entry->student)->supervisor)
                                    {{ $entry->student->supervisor->name }}
                                    <span class="block text-xs text-slate-400">{{ $entry->student->supervisor->company_name }}</span>
                                @else
                                    <span class="text-slate-400">Unassigned</span>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                {{ $entry->created_at?->format('d M Y') }}
                                <span class="block text-xs text-slate-400">{{ $entry->created_at?->diffForHumans() }}</span>
                            </td>
                            <td class="px-4 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusBadge[$status] ?? 'bg-slate-100 text-slate-600' }}">{{ $status }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-slate-500">No logbook entries yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- System --}}
    <div class="rounded-[16px] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/40">
        <h3 class="mb-4 text-base font-bold text-slate-800">System</h3>
        <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Database</p>
                <p class="mt-2 flex items-center gap-2 text-lg font-bold {{ $databaseStatus === 'Connected' ? 'text-emerald-700' : 'text-rose-700' }}">
                    <i class="fa-solid fa-circle text-[8px]"></i> {{ $databaseStatus }}
                </p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Attachments</p>
                <p class="mt-2 text-lg font-bold text-slate-900">{{ $attachments }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Laravel</p>
                <p class="mt-2 text-lg font-bold text-slate-900">{{ $laravelVersion }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">PHP</p>
                <p class="mt-2 text-lg font-bold text-slate-900">{{ $phpVersion }}</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    (function () {
        var trendCanvas = document.getElementById('monthlyTrendChart');
        if (trendCanvas) {
            new Chart(trendCanvas, {
                type: 'line',
                data: {
                    labels: @json($trendLabels),
                    datasets: [{
                        label: 'Entries',
                        data: @json($trendValues),
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37,99,235,0.12)',
                        pointBackgroundColor: '#2563eb',
                        fill: true,
                        tension: 0.35
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f1f5f9' } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        var statusCanvas = document.getElementById('statusChart');
        if (statusCanvas) {
            new Chart(statusCanvas, {
                type: 'doughnut',
                data: {
                    labels: ['Validated', 'Pending', 'Revision requested'],
                    datasets: [{
                        data: [{{ $validated }}, {{ $pending }}, {{ $revision }}],
                        backgroundColor: ['#10b981', '#fbbf24', '#f43f5e'],
                        borderColor: '#ffffff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: { legend: { display: false } }
                }
            });
        }
    })();
</script>
@endsection
