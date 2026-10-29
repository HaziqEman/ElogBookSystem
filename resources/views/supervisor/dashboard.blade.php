@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

@php
    $card = 'rounded-[22px] border border-[#E3E8F2] bg-white shadow-[0_6px_18px_rgba(15,27,77,0.05)]';
    $palette = ['#0F9D8A', '#2F5BFF', '#BE123C', '#6B7280', '#7C3AED', '#C2410C'];
    $avatar = fn ($student) => $palette[crc32((string) $student->student_id) % count($palette)];
    $initials = fn ($name) => \Illuminate\Support\Str::of($name)->explode(' ')->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('');
    $ago = fn ($days) => $days <= 0 ? 'today' : ($days === 1 ? '1 day ago' : $days.' days ago');
    $labelChip = [
        'Pending review' => 'bg-[#FEF3C7] text-[#7C2D12]',
        'Revision requested' => 'bg-[#FFEDD5] text-[#9A3412]',
        'Up to date' => 'bg-[#D1FAE5] text-[#065F46]',
        'Nothing sent yet' => 'bg-[#EEF1F7] text-[#5B6478]',
    ];
@endphp

<div class="space-y-6" style="font-family:'Plus Jakarta Sans',ui-sans-serif,system-ui,sans-serif">

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
    @endif

    {{-- Header --}}
    <section class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-[28px] font-extrabold tracking-tight">{{ $greeting }}, {{ $supervisor->name }}</h1>
            <p class="mt-0.5 text-sm text-[#5B6478]">{{ $today->format('l, d F Y') }} &middot; {{ $supervisor->company_name }} &middot; {{ $supervisor->position }}. Each student's week reaches you as one batch when it ends.</p>
        </div>
        @if($kpis['weeks_waiting'] > 0)
            <a href="/supervisor/weeks/next" class="flex min-h-11 items-center gap-2 rounded-xl bg-[#2F5BFF] px-5 text-sm font-bold text-white transition hover:bg-[#1F45D6]"><i class="fa-solid fa-clipboard-check"></i> Review next week</a>
        @endif
    </section>

    {{-- KPIs --}}
    <section class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-[20px] border border-[#D3E0FF] bg-[#E8EFFF] px-[18px] py-4">
            <div class="flex items-center gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-[#1F45D6]"><i class="fa-solid fa-user-group"></i></span>
            <div><div class="text-xs font-semibold text-[#334155]">Assigned students</div><div class="text-[28px] font-extrabold leading-tight">{{ $kpis['students'] }}</div></div></div>
        </div>
        <div class="rounded-[20px] border border-[#FDE68A] bg-[#FFF7E6] px-[18px] py-4">
            <div class="flex items-center gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-[#B45309]"><i class="fa-solid fa-hourglass-half"></i></span>
            <div><div class="text-xs font-semibold text-[#334155]">Weeks waiting for you</div><div class="text-[28px] font-extrabold leading-tight">{{ $kpis['weeks_waiting'] }}</div></div></div>
            <p class="mt-2 text-xs text-[#5B6478]">{{ $kpis['pending'] }} entries across {{ $kpis['students_waiting'] }} {{ \Illuminate\Support\Str::plural('student', $kpis['students_waiting']) }}</p>
        </div>
        <div class="rounded-[20px] border border-[#BFEBD3] bg-[#E3F7EC] px-[18px] py-4">
            <div class="flex items-center gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-[#047857]"><i class="fa-solid fa-circle-check"></i></span>
            <div><div class="text-xs font-semibold text-[#334155]">Validated</div><div class="text-[28px] font-extrabold leading-tight">{{ $kpis['validated'] }}</div></div></div>
            <p class="mt-2 text-xs text-[#5B6478]">{{ $kpis['reviewed_rate'] !== null ? $kpis['reviewed_rate'].'% of sent entries' : 'Nothing sent yet' }}</p>
        </div>
        <div class="rounded-[20px] border border-[#FBCFCB] bg-[#FFE9E7] px-[18px] py-4">
            <div class="flex items-center gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-[#BE123C]"><i class="fa-solid fa-rotate-left"></i></span>
            <div><div class="text-xs font-semibold text-[#334155]">Revision requested</div><div class="text-[28px] font-extrabold leading-tight">{{ $kpis['revision'] }}</div></div></div>
            <p class="mt-2 text-xs text-[#5B6478]">sent back to students</p>
        </div>
        <div class="rounded-[20px] border border-[#DDD0FB] bg-[#F0EAFF] px-[18px] py-4">
            <div class="flex items-center gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-[#6D28D9]"><i class="fa-solid fa-triangle-exclamation"></i></span>
            <div><div class="text-xs font-semibold text-[#334155]">Overdue weeks</div><div class="text-[28px] font-extrabold leading-tight">{{ $kpis['overdue'] }}</div></div></div>
            <p class="mt-2 text-xs text-[#5B6478]">waiting more than 7 days</p>
        </div>
    </section>

    {{-- Weeks to review | in progress + trend --}}
    <section class="flex flex-wrap items-stretch gap-5">
        <div class="min-w-0 flex-[1.5_1_460px] p-6 {{ $card }}">
            <div class="mb-1 flex items-center justify-between gap-3">
                <h2 class="flex items-center gap-2 text-lg font-extrabold text-[#BE123C]"><i class="fa-solid fa-inbox text-base"></i> Weeks ready for review</h2>
                <span class="text-xs font-semibold text-[#5B6478]">{{ $needsReview->count() }} of {{ $kpis['weeks_waiting'] }}</span>
            </div>

            @forelse($needsReview as $batch)
                @php $student = $batch['student']; @endphp
                <div class="flex flex-wrap items-center gap-x-4 gap-y-3 border-t border-[#EDF0F7] py-3.5 first:mt-2">
                    <span class="flex h-11 w-11 flex-none items-center justify-center rounded-full text-sm font-extrabold text-white" style="background:{{ $avatar($student) }}">{{ $initials($student->name) }}</span>
                    <div class="min-w-0 flex-[1_1_180px]">
                        <div class="truncate text-[15px] font-extrabold">{{ $student->name }}</div>
                        <div class="text-xs text-[#5B6478]">Week {{ $batch['week'] }} &middot; {{ $batch['pending'] }} of {{ $batch['total'] }} {{ \Illuminate\Support\Str::plural('entry', $batch['total']) }} waiting &middot; sent {{ $ago($batch['wait_days']) }}</div>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $batch['priority'] === 'High' ? 'bg-[#FFE4E6] text-[#9F1239]' : 'bg-[#FEF3C7] text-[#7C2D12]' }}">{{ $batch['priority'] === 'High' ? 'Overdue' : 'New' }}</span>
                    <a href="/supervisor/students/{{ $student->student_id }}?week={{ $batch['week'] }}" class="flex min-h-10 items-center rounded-xl bg-[#2F5BFF] px-5 text-sm font-bold text-white transition hover:bg-[#1F45D6]">Review week</a>
                </div>
            @empty
                <div class="mt-3 rounded-2xl border border-dashed border-[#C3CCEA] bg-[#F5F7FF] px-5 py-10 text-center text-sm text-[#5B6478]">
                    <i class="fa-solid fa-circle-check mb-2 block text-3xl text-[#34D399]"></i>
                    You are all caught up. New weeks appear here when a student's week ends.
                </div>
            @endforelse

            @if($kpis['weeks_waiting'] > $needsReview->count())
                <a href="#students" class="mt-3 block text-right text-[13px] font-bold text-[#1F45D6] hover:underline">See every student below</a>
            @endif
        </div>

        <div class="flex min-w-0 flex-[1_1_360px] flex-col gap-5">
            <div class="p-6 {{ $card }}">
                <h2 class="text-lg font-extrabold">This week so far</h2>
                <p class="mb-3 text-xs text-[#5B6478]">Not sent to you yet. Each week arrives after the student's last working day.</p>
                <div class="space-y-3.5">
                    @forelse($inProgress as $row)
                        <div>
                            <div class="flex items-center justify-between gap-2 text-[13px]">
                                <span class="min-w-0 truncate font-bold">{{ $row['student']->name }} <span class="font-medium text-[#5B6478]">&middot; W{{ $row['week'] }}</span></span>
                                <span class="flex-none font-semibold text-[#334155]">{{ $row['logged'] }}/{{ $row['required'] }} days @if($row['extra']) +{{ $row['extra'] }} extra @endif</span>                            </div>
                            <div class="mt-1 h-2 overflow-hidden rounded-[5px] bg-[#E3E8F2]"><div class="h-full rounded-[5px] bg-[#2F5BFF]" style="width:{{ $row['percent'] }}%"></div></div>
                            <div class="mt-0.5 text-[11px] text-[#5B6478]">Sent after {{ $row['closes']->format('D, d M') }}</div>
                        </div>
                    @empty
                        <p class="text-sm text-[#5B6478]">No student is in an active internship week.</p>
                    @endforelse
                </div>
            </div>

            <div class="p-6 {{ $card }}">
                <h2 class="mb-3 text-lg font-extrabold">Review progress, last 4 weeks</h2>
                <div class="space-y-3">
                    @foreach($trend as $row)
                        <div>
                            <div class="flex items-center justify-between text-[13px]">
                                <span class="font-semibold">{{ $row['label'] }}</span>
                                <span class="text-[#5B6478]">@if($row['rate'] === null) no entries @else <strong class="text-[#1E293B]">{{ $row['rate'] }}%</strong> reviewed &middot; {{ $row['pending'] }} waiting @endif</span>
                            </div>
                            <div class="mt-1 h-2 overflow-hidden rounded-[5px] bg-[#FBD5D5]"><div class="h-full rounded-[5px] bg-[#10B981]" style="width:{{ $row['rate'] ?? 0 }}%"></div></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- Student progress --}}
    <section id="students" class="p-6 {{ $card }}">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-xl font-extrabold">Student progress</h2>
                <p class="text-[13px] text-[#5B6478]">Showing {{ $students->count() }} of {{ $totalStudents }} students</p>
            </div>
            <form method="GET" action="/supervisor/dashboard#students" class="flex flex-wrap items-center gap-2">
                <input type="text" name="q" value="{{ $q }}" placeholder="Search name or matric" class="min-h-10 rounded-xl border border-[#CBD3E3] px-3 text-sm focus:border-[#2F5BFF] focus:outline-none">
                <select name="filter" class="min-h-10 rounded-xl border border-[#CBD3E3] bg-white px-3 text-sm focus:border-[#2F5BFF] focus:outline-none">
                    <option value="all" @selected($filter === 'all')>All students</option>
                    <option value="pending" @selected($filter === 'pending')>Weeks waiting</option>
                    <option value="revision" @selected($filter === 'revision')>Revision requested</option>
                    <option value="uptodate" @selected($filter === 'uptodate')>Up to date</option>
                </select>
                <button type="submit" class="min-h-10 rounded-xl bg-[#2F5BFF] px-5 text-sm font-bold text-white transition hover:bg-[#1F45D6]">Apply</button>
            </form>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-[#EDF0F7]">
            <table class="w-full min-w-[820px] text-left text-sm">
                <thead class="bg-[#F7F9FD] text-xs font-extrabold uppercase tracking-wider text-[#5B6478]">
                    <tr><th class="px-4 py-3">Student</th><th class="px-4 py-3">This week</th><th class="px-4 py-3">Weeks sent</th><th class="px-4 py-3">Waiting</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Action</th></tr>
                </thead>
                <tbody>
                    @forelse($students as $row)
                        @php $student = $row['student']; @endphp
                        <tr class="border-t border-[#EDF0F7]">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full text-xs font-extrabold text-white" style="background:{{ $avatar($student) }}">{{ $initials($student->name) }}</span>
                                    <div class="min-w-0"><div class="font-bold">{{ $student->name }}</div><div class="text-xs text-[#5B6478]">{{ $student->matric_no }} &middot; {{ $student->course }}</div></div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                @if($row['live'])
                                <div class="font-semibold">W{{ $row['live']['week'] }} &middot; {{ $row['live']['logged'] }}/{{ $row['live']['required'] }} days @if($row['live']['extra']) +{{ $row['live']['extra'] }} extra @endif</div>                                    <div class="text-xs text-[#5B6478]">in progress</div>
                                @else
                                    <span class="text-xs text-[#94A3B8]">Not in a week</span>
                                @endif
                            </td>
                            <td class="px-4 py-3"><div class="font-semibold">{{ $row['weeks_sent'] }}</div><div class="text-xs text-[#5B6478]">{{ $row['validated'] }}/{{ $row['total'] }} validated</div></td>
                            <td class="px-4 py-3">
                                @if($row['weeks_waiting'] > 0)
                                    <span class="rounded-full bg-[#FEF3C7] px-2 py-0.5 text-xs font-bold text-[#7C2D12]">{{ $row['weeks_waiting'] }} {{ \Illuminate\Support\Str::plural('week', $row['weeks_waiting']) }} &middot; {{ $row['pending'] }} entries</span>
                                    <div class="mt-0.5 text-xs text-[#5B6478]">oldest {{ $row['oldest_days'] }}d</div>
                                @else
                                    <span class="text-xs text-[#94A3B8]">Nothing waiting</span>
                                @endif
                            </td>
                            <td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $labelChip[$row['review_label']] }}">{{ $row['review_label'] }}</span></td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    <a href="/supervisor/students/{{ $student->student_id }}{{ $row['oldest_week'] ? '?week='.$row['oldest_week'] : '' }}" class="flex min-h-9 items-center rounded-xl bg-[#2F5BFF] px-4 text-xs font-bold text-white transition hover:bg-[#1F45D6]">{{ $row['weeks_waiting'] > 0 ? 'Review' : 'Open' }}</a>
                                    <a href="/supervisor/messages/with/{{ $student->student_id }}" class="flex min-h-9 items-center rounded-xl border border-[#CBD3E3] bg-white px-4 text-xs font-bold text-[#1F45D6] transition hover:bg-[#F3F5FC]">Message</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-sm text-[#5B6478]">No students match.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $students->links() }}</div>
    </section>
</div>
@endsection
