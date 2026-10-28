@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

@php
    $card = 'rounded-[22px] border border-[#E3E8F2] bg-white shadow-[0_6px_18px_rgba(15,27,77,0.05)]';

    $priorityClass = [
        'High' => 'bg-[#FFE4E6] text-[#9F1239]',
        'Medium' => 'bg-[#FEF3C7] text-[#92400E]',
    ];

    $reviewClass = [
        'Up to date' => 'bg-[#D1FAE5] text-[#065F46]',
        'Pending review' => 'bg-[#FEF3C7] text-[#92400E]',
        'Revision requested' => 'bg-[#FFEDD5] text-[#9A3412]',
        'No entries' => 'bg-[#EEF1F7] text-[#5B6478]',
    ];

    $statusClass = [
        'Validated' => 'bg-[#D1FAE5] text-[#065F46]',
        'Revision Requested' => 'bg-[#FFE4E6] text-[#9F1239]',
        'Pending' => 'bg-[#FEF3C7] text-[#92400E]',
    ];

    $palette = ['bg-[#2F5BFF]', 'bg-[#F97316]', 'bg-[#DC2626]', 'bg-[#8B5CF6]', 'bg-[#0D9488]', 'bg-[#64748B]'];

    $initials = function (string $name) {
        return \Illuminate\Support\Str::of($name)->explode(' ')->filter()->take(2)
            ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('');
    };

    $barMax = max(1, (int) collect($weekChart)->max('submitted'));
@endphp

<div class="space-y-6" style="font-family:'Plus Jakarta Sans',ui-sans-serif,system-ui,sans-serif">

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-[#0F1B4D]">{{ $greeting }}, {{ $supervisor->name }}</h1>
            <p class="mt-1 text-sm text-[#5B6478]">
                {{ $today->format('l, d F Y') }} &middot;
                {{ $supervisor->company_name }}@if($supervisor->position) &middot; {{ $supervisor->position }}@endif.
                Here is how your students' logbooks are doing.
            </p>
        </div>
        @if($kpis['pending'] > 0)
            <a href="/supervisor/logbook" class="inline-flex min-h-10 items-center gap-2 rounded-xl bg-[#2F5BFF] px-4 text-sm font-bold text-white transition hover:bg-[#1F45D6]">
                <i class="fa-solid fa-clipboard-check"></i> Review next pending entry
            </a>
        @endif
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-[#BFEBD3] bg-[#EEFAF3] px-4 py-3 text-sm text-[#065F46]">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-2xl border border-[#FBCFCB] bg-[#FFF1EF] px-4 py-3 text-sm text-[#9F1239]">{{ session('error') }}</div>
    @endif

    {{-- KPI cards --}}
    <section class="grid grid-cols-2 gap-3.5 xl:grid-cols-5">
        <div class="rounded-[20px] border border-[#D3E0FF] bg-[#EEF3FF] p-4">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 flex-none items-center justify-center rounded-2xl bg-white text-[#1F45D6]"><i class="fa-solid fa-users"></i></span>
                <div class="min-w-0">
                    <div class="text-xs font-semibold text-[#334155]">Assigned students</div>
                    <div class="text-[28px] font-extrabold leading-tight">{{ $kpis['students'] }}</div>
                </div>
            </div>
            <p class="mt-2 text-xs text-[#5B6478]">{{ $kpis['total_entries'] }} logbook {{ \Illuminate\Support\Str::plural('entry', $kpis['total_entries']) }} in total</p>
        </div>

        <div class="rounded-[20px] border border-[#FDE2B8] bg-[#FFF7EA] p-4">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 flex-none items-center justify-center rounded-2xl bg-white text-[#B45309]"><i class="fa-solid fa-hourglass-half"></i></span>
                <div class="min-w-0">
                    <div class="text-xs font-semibold text-[#334155]">Waiting for you</div>
                    <div class="text-[28px] font-extrabold leading-tight">{{ $kpis['pending'] }}</div>
                </div>
            </div>
            <p class="mt-2 text-xs text-[#5B6478]">entries across {{ $kpis['students_waiting'] }} {{ \Illuminate\Support\Str::plural('student', $kpis['students_waiting']) }}</p>
        </div>

        <div class="rounded-[20px] border border-[#BFEBD3] bg-[#EEFAF3] p-4">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 flex-none items-center justify-center rounded-2xl bg-white text-[#047857]"><i class="fa-solid fa-circle-check"></i></span>
                <div class="min-w-0">
                    <div class="text-xs font-semibold text-[#334155]">Validated</div>
                    <div class="text-[28px] font-extrabold leading-tight">{{ $kpis['validated'] }}</div>
                </div>
            </div>
            <p class="mt-2 text-xs text-[#5B6478]">{{ $kpis['reviewed_rate'] === null ? 'No entries yet' : $kpis['reviewed_rate'].'% of all entries' }}</p>
        </div>

        <div class="rounded-[20px] border border-[#FBCFCB] bg-[#FFF1EF] p-4">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 flex-none items-center justify-center rounded-2xl bg-white text-[#BE123C]"><i class="fa-solid fa-rotate-left"></i></span>
                <div class="min-w-0">
                    <div class="text-xs font-semibold text-[#334155]">Revision requested</div>
                    <div class="text-[28px] font-extrabold leading-tight">{{ $kpis['revision'] }}</div>
                </div>
            </div>
            <p class="mt-2 text-xs text-[#5B6478]">sent back to students</p>
        </div>

        <div class="col-span-2 rounded-[20px] border border-[#DDD0FB] bg-[#F5F0FF] p-4 xl:col-span-1" title="Entries that have been waiting 7 days or more.">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 flex-none items-center justify-center rounded-2xl bg-white text-[#6D28D9]"><i class="fa-solid fa-triangle-exclamation"></i></span>
                <div class="min-w-0">
                    <div class="text-xs font-semibold text-[#334155]">Overdue reviews</div>
                    <div class="text-[28px] font-extrabold leading-tight">{{ $kpis['overdue'] }}</div>
                </div>
            </div>
            <p class="mt-2 text-xs text-[#5B6478]">{{ \Illuminate\Support\Str::plural('student', $kpis['overdue']) }} waiting over 7 days</p>
        </div>
    </section>

    {{-- Needs attention + charts --}}
    <section class="grid gap-5 xl:grid-cols-[1.5fr_1fr]">
        <div class="p-5 {{ $card }}">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 class="flex items-center gap-2 text-base font-extrabold text-[#BE123C]"><i class="fa-solid fa-triangle-exclamation"></i> Needs your review</h2>
                <a href="/supervisor/dashboard?filter=pending#students" class="text-[13px] font-bold text-[#1F45D6] hover:underline">View all</a>
            </div>

            @forelse($needsAttention as $row)
                @php $student = $row['student']; @endphp
                <div class="flex flex-wrap items-center gap-3 border-t border-[#EDF0F7] py-3">
                    <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full text-sm font-extrabold text-white {{ $palette[$student->student_id % count($palette)] }}">{{ $initials($student->name) }}</span>
                    <div class="min-w-[140px] flex-[1_1_160px]">
                        <p class="truncate text-sm font-bold">{{ $student->name }}</p>
                        <p class="text-xs text-[#5B6478]">{{ $student->matric_no }}</p>
                    </div>
                    <div class="min-w-[180px] flex-[2_1_220px] text-sm text-[#334155]">{{ $row['main_issue'] }}</div>
                    <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $priorityClass[$row['priority']] }}">{{ $row['priority'] }}</span>
                    <a href="/supervisor/dashboard?q={{ urlencode($student->matric_no) }}#students" class="flex min-h-9 items-center rounded-xl bg-[#2F5BFF] px-4 text-sm font-bold text-white transition hover:bg-[#1F45D6]">View</a>
                </div>
            @empty
                <div class="rounded-2xl bg-[#EEFAF3] p-6 text-center text-sm text-[#065F46]">
                    <i class="fa-solid fa-circle-check mb-2 block text-2xl"></i>
                    All clear. No entries are waiting for your validation.
                </div>
            @endforelse
        </div>

        <div class="space-y-5">
            <div class="p-5 {{ $card }}">
                <div class="mb-1 flex items-center justify-between gap-2">
                    <h2 class="text-base font-extrabold">Logbook entries this week</h2>
                    <div class="flex gap-3 text-[11px] text-[#5B6478]">
                        <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-[#2F5BFF]"></span>Submitted</span>
                        <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-[#10B981]"></span>Reviewed</span>
                    </div>
                </div>
                <div class="mt-3 flex items-end gap-2">
                    @foreach($weekChart as $day)
                        <div class="flex flex-1 flex-col items-center gap-1" title="{{ $day['date'] }}: {{ $day['reviewed'] }} of {{ $day['submitted'] }} reviewed">
                            <div class="flex h-32 w-full items-end justify-center gap-1">
                                <div class="w-3 rounded-t bg-[#2F5BFF]" style="height:{{ round($day['submitted'] / $barMax * 100) }}%;min-height:{{ $day['submitted'] > 0 ? 4 : 0 }}px"></div>
                                <div class="w-3 rounded-t bg-[#10B981]" style="height:{{ round($day['reviewed'] / $barMax * 100) }}%;min-height:{{ $day['reviewed'] > 0 ? 4 : 0 }}px"></div>
                            </div>
                            <div class="text-[11px] font-bold {{ $day['is_today'] ? 'text-[#2F5BFF]' : 'text-[#5B6478]' }}">{{ $day['label'] }}</div>
                            <div class="text-[10px] text-[#94A3B8]">{{ $day['date'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="p-5 {{ $card }}">
                <h2 class="mb-3 text-base font-extrabold">Review progress, last 4 weeks</h2>
                <div class="space-y-3">
                    @foreach($trend as $week)
                        <div>
                            <div class="mb-1 flex items-center justify-between text-xs">
                                <span class="font-semibold text-[#334155]">{{ $week['label'] }}</span>
                                <span class="text-[#5B6478]">
                                    @if($week['rate'] === null)
                                        No entries
                                    @else
                                        <strong class="text-[#0F1B4D]">{{ $week['rate'] }}%</strong> reviewed
                                        @if($week['pending'] > 0) &middot; {{ $week['pending'] }} waiting @endif
                                    @endif
                                </span>
                            </div>
                            <div class="h-2.5 overflow-hidden rounded-full bg-[#FFE4E6]">
                                <div class="h-full rounded-full bg-[#10B981]" style="width:{{ $week['rate'] ?? 0 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <p class="mt-3 text-[11px] text-[#5B6478]">Based on the activity date of each entry. Validated and revision-requested entries count as reviewed.</p>
            </div>
        </div>
    </section>

    {{-- Students table --}}
    <section id="students" class="p-5 md:p-6 {{ $card }}">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-extrabold">Student progress</h2>
                <p class="text-[13px] text-[#5B6478]">Showing {{ $students->count() }} of {{ $students->total() }} {{ \Illuminate\Support\Str::plural('student', $students->total()) }}</p>
            </div>
            <form method="GET" action="/supervisor/dashboard#students" class="flex flex-wrap items-center gap-2">
                <input type="search" name="q" value="{{ $q }}" placeholder="Search name or matric" aria-label="Search students" class="min-h-10 w-52 rounded-xl border border-[#CBD3E3] px-3 text-sm focus:border-[#2F5BFF] focus:outline-none">
                <select name="filter" aria-label="Filter students" class="min-h-10 rounded-xl border border-[#CBD3E3] bg-white px-3 text-sm focus:border-[#2F5BFF] focus:outline-none">
                    <option value="all" {{ $filter === 'all' ? 'selected' : '' }}>All students</option>
                    <option value="pending" {{ $filter === 'pending' ? 'selected' : '' }}>Waiting for my review</option>
                    <option value="revision" {{ $filter === 'revision' ? 'selected' : '' }}>Revision requested</option>
                    <option value="uptodate" {{ $filter === 'uptodate' ? 'selected' : '' }}>Up to date</option>
                </select>
                <button type="submit" class="min-h-10 rounded-xl bg-[#2F5BFF] px-4 text-sm font-bold text-white transition hover:bg-[#1F45D6]">Apply</button>
                @if($q !== '' || $filter !== 'all')
                    <a href="/supervisor/dashboard#students" class="text-sm font-semibold text-[#5B6478] hover:underline">Clear</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-[#E7ECF6]">
            <table class="min-w-[820px] w-full text-left text-sm">
                <thead class="bg-[#F7F9FD] text-xs font-semibold uppercase tracking-wider text-[#5B6478]">
                    <tr>
                        <th class="px-4 py-3">Student</th>
                        <th class="px-4 py-3">Course</th>
                        <th class="px-4 py-3">Entries</th>
                        <th class="px-4 py-3">Waiting</th>
                        <th class="px-4 py-3">Last entry</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EDF0F7]">
                    @forelse($students as $row)
                        @php $student = $row['student']; @endphp
                        <tr>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 flex-none items-center justify-center rounded-full text-xs font-extrabold text-white {{ $palette[$student->student_id % count($palette)] }}">{{ $initials($student->name) }}</span>
                                    <div class="min-w-0">
                                        <p class="font-bold">{{ $student->name }}</p>
                                        <p class="text-xs text-[#5B6478]">{{ $student->matric_no }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-[13px] text-[#334155]">{{ $student->course }}</td>
                            <td class="px-4 py-3 text-[13px]">
                                <p class="font-semibold">{{ $row['total'] }} total</p>
                                <p class="text-xs text-[#5B6478]">{{ $row['validated'] }} validated @if($row['revision'] > 0)&middot; {{ $row['revision'] }} revision @endif</p>
                            </td>
                            <td class="px-4 py-3 text-[13px]">
                                @if($row['pending'] > 0)
                                    <span class="rounded-full bg-[#FEF3C7] px-2 py-0.5 text-[11px] font-bold text-[#92400E]">{{ $row['pending'] }} pending</span>
                                    @if($row['oldest_days'] >= 1)<p class="mt-1 text-xs text-[#5B6478]">oldest {{ $row['oldest_days'] }}d</p>@endif
                                @else
                                    <span class="text-xs text-[#5B6478]">None</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-[13px]">
                                @if($row['last_at'])
                                    <p class="font-semibold">{{ $row['last_at']->format('d M Y') }}</p>
                                @else
                                    <span class="text-xs text-[#94A3B8]">No entries yet</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-bold {{ $reviewClass[$row['review_label']] ?? 'bg-[#EEF1F7] text-[#5B6478]' }}">{{ $row['review_label'] }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <a href="/supervisor/messages/with/{{ $student->student_id }}" class="inline-flex min-h-9 items-center whitespace-nowrap rounded-xl border border-[#CBD3E3] bg-white px-3 text-sm font-bold text-[#1F45D6] transition hover:bg-[#F3F5FC]">Message</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-[#5B6478]">
                                {{ $totalStudents === 0 ? 'No students are assigned to you yet. Please ask the administrator.' : 'No students match this search or filter.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex justify-end">{{ $students->links() }}</div>
    </section>

    {{-- Latest entries: this is where the supervisor reviews --}}
    <section class="p-5 md:p-6 {{ $card }}">
        <h2 class="mb-4 text-xl font-extrabold">Latest logbook entries</h2>
        <div class="overflow-x-auto rounded-2xl border border-[#E7ECF6]">
            <table class="min-w-[760px] w-full text-left text-sm">
                <thead class="bg-[#F7F9FD] text-xs font-semibold uppercase tracking-wider text-[#5B6478]">
                    <tr>
                        <th class="px-4 py-3">Student</th>
                        <th class="px-4 py-3">Week</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Entry</th>
                        <th class="px-4 py-3">Your status</th>
                        <th class="px-4 py-3">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EDF0F7]">
                    @forelse($recent as $logbook)
                        @php $mine = $logbook->supervisor_status ?? 'Pending'; @endphp
                        <tr>
                            <td class="whitespace-nowrap px-4 py-3 font-bold">{{ optional($logbook->student)->name }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-[13px]">Week {{ $logbook->week_no }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-[13px] text-[#5B6478]">{{ $logbook->activity_date ? \Carbon\Carbon::parse($logbook->activity_date)->format('d M Y') : '-' }}</td>
                            <td class="min-w-[14rem] px-4 py-3 text-[13px] text-[#334155]">{{ \Illuminate\Support\Str::limit($logbook->description, 90) }}</td>
                            <td class="px-4 py-3"><span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-bold {{ $statusClass[$mine] ?? $statusClass['Pending'] }}">{{ $mine }}</span></td>
                            <td class="px-4 py-3">
                                <a href="/supervisor/logbook/{{ $logbook->logbook_id }}" class="inline-flex min-h-9 items-center rounded-xl bg-[#2F5BFF] px-4 text-sm font-bold text-white transition hover:bg-[#1F45D6]">{{ $mine === 'Pending' ? 'Review' : 'Open' }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-sm text-[#5B6478]">No logbook entries from your students yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
