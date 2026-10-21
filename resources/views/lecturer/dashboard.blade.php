@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

@php
    $card = 'rounded-[22px] border border-[#E3E8F2] bg-white shadow-[0_6px_18px_rgba(15,27,77,0.05)]';

    $priorityClass = [
        'High' => 'bg-[#FFE4E6] text-[#9F1239]',
        'Medium' => 'bg-[#FEF3C7] text-[#92400E]',
        'Low' => 'bg-[#EEF1F7] text-[#5B6478]',
    ];

    $reviewClass = [
        'Up to date' => 'bg-[#D1FAE5] text-[#065F46]',
        'Pending review' => 'bg-[#FEF3C7] text-[#92400E]',
        'Revision requested' => 'bg-[#FFEDD5] text-[#9A3412]',
        'No entries' => 'bg-[#EEF1F7] text-[#5B6478]',
        'No supervisor' => 'bg-[#EEF1F7] text-[#5B6478]',
    ];

    $palette = ['bg-[#2F5BFF]', 'bg-[#F97316]', 'bg-[#DC2626]', 'bg-[#8B5CF6]', 'bg-[#0D9488]', 'bg-[#64748B]'];

    $initials = function (string $name) {
        return \Illuminate\Support\Str::of($name)->explode(' ')->filter()->take(2)
            ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('');
    };

    $barMax = max(1, (int) collect($weekChart)->max('expected'), (int) collect($weekChart)->max('submitted'));
@endphp

<div class="space-y-6" style="font-family:'Plus Jakarta Sans',ui-sans-serif,system-ui,sans-serif">

    <div>
        <h1 class="text-2xl font-extrabold text-[#0F1B4D]">{{ $greeting }}, {{ $lecturer->name }}</h1>
        <p class="mt-1 text-sm text-[#5B6478]">{{ $today->format('l, d F Y') }} &middot; Here is how your assigned students are doing.</p>
    </div>

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
            <p class="mt-2 text-xs text-[#5B6478]">{{ $kpis['no_dates'] > 0 ? $kpis['no_dates'].' without internship dates' : 'All have internship dates' }}</p>
        </div>

        <div class="rounded-[20px] border border-[#FBCFCB] bg-[#FFF1EF] p-4">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 flex-none items-center justify-center rounded-2xl bg-white text-[#BE123C]"><i class="fa-solid fa-calendar-xmark"></i></span>
                <div class="min-w-0">
                    <div class="text-xs font-semibold text-[#334155]">Attendance alerts</div>
                    <div class="text-[28px] font-extrabold leading-tight">{{ $kpis['attendance_alerts'] }}</div>
                </div>
            </div>
            <p class="mt-2 text-xs text-[#5B6478]">students missed a day in the last 5 working days</p>
        </div>

        <div class="rounded-[20px] border border-[#FDE2B8] bg-[#FFF7EA] p-4">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 flex-none items-center justify-center rounded-2xl bg-white text-[#B45309]"><i class="fa-solid fa-file-circle-exclamation"></i></span>
                <div class="min-w-0">
                    <div class="text-xs font-semibold text-[#334155]">Overdue logbooks</div>
                    <div class="text-[28px] font-extrabold leading-tight">{{ $kpis['missing_entries'] }}</div>
                </div>
            </div>
            <p class="mt-2 text-xs text-[#5B6478]">missing entries across {{ $kpis['students_missing'] }} {{ \Illuminate\Support\Str::plural('student', $kpis['students_missing']) }}</p>
        </div>

        <div class="rounded-[20px] border border-[#BFEBD3] bg-[#EEFAF3] p-4" title="At risk means at least one high-priority issue: 3 or more missing attendance days in the last 5 working days, 3 or more overdue entries, or no update for 5 working days.">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 flex-none items-center justify-center rounded-2xl bg-white text-[#047857]"><i class="fa-solid fa-triangle-exclamation"></i></span>
                <div class="min-w-0">
                    <div class="text-xs font-semibold text-[#334155]">At-risk students</div>
                    <div class="text-[28px] font-extrabold leading-tight">{{ $kpis['at_risk'] }}</div>
                </div>
            </div>
            <p class="mt-2 text-xs text-[#5B6478]">with a high-priority issue</p>
        </div>

        <div class="col-span-2 rounded-[20px] border border-[#DDD0FB] bg-[#F5F0FF] p-4 xl:col-span-1" title="Counts only. Task titles stay private to each student.">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 flex-none items-center justify-center rounded-2xl bg-white text-[#6D28D9]"><i class="fa-solid fa-list-check"></i></span>
                <div class="min-w-0">
                    <div class="text-xs font-semibold text-[#334155]">Tasks completed</div>
                    <div class="text-[28px] font-extrabold leading-tight">{{ $kpis['task_rate'] === null ? '-' : $kpis['task_rate'].'%' }}</div>
                </div>
            </div>
            <p class="mt-2 text-xs text-[#5B6478]">{{ $kpis['students_overdue_tasks'] }} {{ \Illuminate\Support\Str::plural('student', $kpis['students_overdue_tasks']) }} with overdue tasks</p>
        </div>
    </section>

    {{-- Needs attention + charts --}}
    <section class="grid gap-5 xl:grid-cols-[1.5fr_1fr]">
        <div class="p-5 {{ $card }}">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 class="flex items-center gap-2 text-base font-extrabold text-[#BE123C]"><i class="fa-solid fa-triangle-exclamation"></i> Needs attention</h2>
                <a href="/lecturer/dashboard?filter=attention#students" class="text-[13px] font-bold text-[#1F45D6] hover:underline">View all</a>
            </div>

            @forelse($needsAttention as $row)
                @php $student = $row['student']; @endphp
                <div class="flex flex-wrap items-center gap-3 border-t border-[#EDF0F7] py-3">
                    <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full text-sm font-extrabold text-white {{ $palette[$student->student_id % count($palette)] }}">{{ $initials($student->name) }}</span>
                    <div class="min-w-[140px] flex-[1_1_160px]">
                        <p class="truncate text-sm font-bold">{{ $student->name }}</p>
                        <p class="text-xs text-[#5B6478]">{{ $student->matric_no }}</p>
                    </div>
                    <div class="min-w-[180px] flex-[2_1_220px] text-sm text-[#334155]">
                        {{ $row['main_issue'] }}
                        @if($row['extra_issues'] > 0)
                            <span class="text-xs text-[#5B6478]">+{{ $row['extra_issues'] }} more</span>
                        @endif
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $priorityClass[$row['priority']] }}">{{ $row['priority'] }}</span>
                    <a href="/lecturer/students/{{ $student->student_id }}" class="flex min-h-9 items-center rounded-xl bg-[#2F5BFF] px-4 text-sm font-bold text-white transition hover:bg-[#1F45D6]">View</a>
                </div>
            @empty
                <div class="rounded-2xl bg-[#EEFAF3] p-6 text-center text-sm text-[#065F46]">
                    <i class="fa-solid fa-circle-check mb-2 block text-2xl"></i>
                    All clear. None of your students need attention right now.
                </div>
            @endforelse
        </div>

        <div class="space-y-5">
            <div class="p-5 {{ $card }}">
                <div class="mb-1 flex items-center justify-between gap-2">
                    <h2 class="text-base font-extrabold">Logbook entries this week</h2>
                    <div class="flex gap-3 text-[11px] text-[#5B6478]">
                        <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-[#2F5BFF]"></span>Submitted</span>
                        <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-[#C4B5FD]"></span>Expected</span>
                    </div>
                </div>
                <div class="mt-3 flex items-end gap-2">
                    @foreach($weekChart as $day)
                        <div class="flex flex-1 flex-col items-center gap-1" title="{{ $day['date'] }}: {{ $day['submitted'] }} of {{ $day['expected'] }} submitted">
                            <div class="flex h-32 w-full items-end justify-center gap-1">
                                <div class="w-3 rounded-t bg-[#2F5BFF]" style="height:{{ round($day['submitted'] / $barMax * 100) }}%;min-height:{{ $day['submitted'] > 0 ? 4 : 0 }}px"></div>
                                <div class="w-3 rounded-t bg-[#C4B5FD]" style="height:{{ round($day['expected'] / $barMax * 100) }}%;min-height:{{ $day['expected'] > 0 ? 4 : 0 }}px"></div>
                            </div>
                            <div class="text-[11px] font-bold {{ $day['is_today'] ? 'text-[#2F5BFF]' : 'text-[#5B6478]' }}">{{ $day['label'] }}</div>
                            <div class="text-[10px] text-[#94A3B8]">{{ $day['date'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="p-5 {{ $card }}">
                <h2 class="mb-3 text-base font-extrabold">Attendance, last 4 weeks</h2>
                <div class="space-y-3">
                    @foreach($trend as $week)
                        <div>
                            <div class="mb-1 flex items-center justify-between text-xs">
                                <span class="font-semibold text-[#334155]">{{ $week['label'] }}</span>
                                <span class="text-[#5B6478]">
                                    @if($week['rate'] === null)
                                        No data
                                    @else
                                        <strong class="text-[#0F1B4D]">{{ $week['rate'] }}%</strong> present
                                        @if($week['leave'] > 0) &middot; {{ $week['leave'] }} leave {{ \Illuminate\Support\Str::plural('day', $week['leave']) }} @endif
                                    @endif
                                </span>
                            </div>
                            <div class="h-2.5 overflow-hidden rounded-full bg-[#FFE4E6]">
                                <div class="h-full rounded-full bg-[#10B981]" style="width:{{ $week['rate'] ?? 0 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <p class="mt-3 text-[11px] text-[#5B6478]">Counts working days that are over. Leave, MC and holidays are not counted against students.</p>
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
            <form method="GET" action="/lecturer/dashboard#students" class="flex flex-wrap items-center gap-2">
                <input type="search" name="q" value="{{ $q }}" placeholder="Search name or matric" aria-label="Search students" class="min-h-10 w-52 rounded-xl border border-[#CBD3E3] px-3 text-sm focus:border-[#2F5BFF] focus:outline-none">
                <select name="filter" aria-label="Filter students" class="min-h-10 rounded-xl border border-[#CBD3E3] bg-white px-3 text-sm focus:border-[#2F5BFF] focus:outline-none">
                    <option value="all" {{ $filter === 'all' ? 'selected' : '' }}>All students</option>
                    <option value="attention" {{ $filter === 'attention' ? 'selected' : '' }}>Needs attention</option>
                    <option value="review" {{ $filter === 'review' ? 'selected' : '' }}>Pending supervisor review</option>
                    <option value="revision" {{ $filter === 'revision' ? 'selected' : '' }}>Revision requested</option>
                    <option value="tasks" {{ $filter === 'tasks' ? 'selected' : '' }}>Overdue tasks</option>
                    <option value="nodates" {{ $filter === 'nodates' ? 'selected' : '' }}>Dates not set</option>
                </select>
                <button type="submit" class="min-h-10 rounded-xl bg-[#2F5BFF] px-4 text-sm font-bold text-white transition hover:bg-[#1F45D6]">Apply</button>
                @if($q !== '' || $filter !== 'all')
                    <a href="/lecturer/dashboard#students" class="text-sm font-semibold text-[#5B6478] hover:underline">Clear</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-[#E7ECF6]">
            <table class="min-w-[980px] w-full text-left text-sm">
                <thead class="bg-[#F7F9FD] text-xs font-semibold uppercase tracking-wider text-[#5B6478]">
                    <tr>
                        <th class="px-4 py-3">Student</th>
                        <th class="px-4 py-3">Internship</th>
                        <th class="px-4 py-3">Attendance</th>
                        <th class="px-4 py-3">Logbook</th>
                        <th class="px-4 py-3">Tasks</th>
                        <th class="px-4 py-3">Last activity</th>
                        <th class="px-4 py-3">Supervisor</th>
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
                                        @if($row['priority'])
                                            <span class="mt-0.5 inline-block rounded-full px-2 py-0.5 text-[10px] font-bold {{ $priorityClass[$row['priority']] }}">{{ $row['priority'] }} priority</span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-3 text-[13px]">
                                @if($row['has_dates'])
                                    <p class="font-semibold">
                                        @if(! $row['started'])
                                            Starts {{ $row['start']->format('d M Y') }}
                                        @elseif($row['finished'])
                                            Finished
                                        @else
                                            Week {{ $row['current_week'] }} of {{ $row['total_weeks'] }}
                                        @endif
                                    </p>
                                    <p class="text-xs text-[#5B6478]">{{ $row['total_weeks'] }} weeks &middot; {{ $row['start']->format('d M') }} to {{ $row['end']->format('d M Y') }}</p>
                                @else
                                    <span class="rounded-full bg-[#FEF3C7] px-2.5 py-1 text-xs font-bold text-[#92400E]">Dates not set</span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-[13px]">
                                @if($row['has_dates'] && $row['started'])
                                    <p class="font-semibold">{{ $row['attendance_done'] }} / {{ $row['expected'] }} days</p>
                                    <p class="text-xs {{ $row['recent_missing_attendance'] > 0 ? 'text-[#BE123C]' : 'text-[#5B6478]' }}">
                                        {{ $row['recent_missing_attendance'] > 0 ? $row['recent_missing_attendance'].' missed recently' : 'On track' }}{{ $row['today_pending'] ? ' · today pending' : '' }}
                                    </p>
                                @else
                                    <span class="text-[#94A3B8]">-</span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-[13px]">
                                @if($row['has_dates'] && $row['started'])
                                    <p class="font-semibold">{{ $row['entries_done'] }} / {{ $row['expected'] }} entries</p>
                                    @if($row['missing_entries'] > 0)
                                        <span class="rounded-full bg-[#FFE4E6] px-2 py-0.5 text-[11px] font-bold text-[#9F1239]">{{ $row['missing_entries'] }} overdue</span>
                                    @else
                                        <p class="text-xs text-[#5B6478]">None overdue</p>
                                    @endif
                                @else
                                    <span class="text-[#94A3B8]">-</span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-[13px]">
                                @if($row['tasks_total'] > 0)
                                    <p class="font-semibold">{{ $row['tasks_done'] }} of {{ $row['tasks_total'] }} done</p>
                                    @if($row['tasks_overdue'] > 0)
                                        <span class="rounded-full bg-[#FFE4E6] px-2 py-0.5 text-[11px] font-bold text-[#9F1239]">{{ $row['tasks_overdue'] }} overdue</span>
                                    @else
                                        <p class="text-xs text-[#5B6478]">None overdue</p>
                                    @endif
                                @else
                                    <span class="text-xs text-[#94A3B8]">No tasks</span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-[13px]">
                                @if($row['last_at'])
                                    <p class="font-semibold">{{ $row['last_at']->format('d M Y') }}</p>
                                    <p class="text-xs text-[#5B6478]">{{ $row['last_label'] }}</p>
                                @else
                                    <span class="text-xs text-[#94A3B8]">No activity yet</span>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <span class="whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-bold {{ $reviewClass[$row['review_label']] ?? 'bg-[#EEF1F7] text-[#5B6478]' }}">{{ $row['review_label'] }}</span>
                                @if($row['supervisor_name'])
                                    <p class="mt-1 text-xs text-[#5B6478]">{{ $row['supervisor_name'] }}</p>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <a href="/lecturer/students/{{ $student->student_id }}" class="inline-flex min-h-9 items-center whitespace-nowrap rounded-xl border border-[#CBD3E3] bg-white px-3 text-sm font-bold text-[#1F45D6] transition hover:bg-[#F3F5FC]">View logbook</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-sm text-[#5B6478]">
                                {{ $totalStudents === 0 ? 'No students are assigned to you yet. Please ask the administrator.' : 'No students match this search or filter.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex justify-end">{{ $students->links() }}</div>
    </section>
</div>
@endsection