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

    $entryClass = [
        'Validated' => 'bg-[#D1FAE5] text-[#065F46]',
        'Revision Requested' => 'bg-[#FFEDD5] text-[#9A3412]',
        'Pending' => 'bg-[#FEF3C7] text-[#92400E]',
    ];

    $chipClass = [
        'validated' => 'bg-[#D1FAE5] text-[#065F46]',
        'waiting' => 'bg-[#E0E7FF] text-[#3730A3]',
        'revision' => 'bg-[#FFEDD5] text-[#9A3412]',
        'current' => 'bg-[#2F5BFF] text-white',
        'incomplete' => 'bg-[#FFE4E6] text-[#9F1239]',
        'locked' => 'bg-[#EEF1F7] text-[#94A3B8]',
    ];

    $statusText = [
        'validated' => 'Validated',
        'waiting' => 'Waiting for review',
        'revision' => 'Revision needed',
        'current' => 'Current week',
        'incomplete' => 'Entries missing',
        'locked' => 'Not open yet',
    ];

    $initials = \Illuminate\Support\Str::of($student->name)->explode(' ')->filter()->take(2)
        ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('');
@endphp

<div class="space-y-6" style="font-family:'Plus Jakarta Sans',ui-sans-serif,system-ui,sans-serif">
    <a href="/lecturer/dashboard#students" class="inline-flex items-center gap-2 text-sm font-semibold text-[#1F45D6] hover:underline"><i class="fa-solid fa-arrow-left"></i> Back to dashboard</a>

    {{-- Header --}}
    <section class="flex flex-wrap items-center justify-between gap-4 p-5 md:p-6 {{ $card }}">
        <div class="flex min-w-0 items-center gap-4">
            <span class="flex h-14 w-14 flex-none items-center justify-center rounded-full bg-[#2F5BFF] text-lg font-extrabold text-white">{{ $initials }}</span>
            <div class="min-w-0">
                <h1 class="break-words text-2xl font-extrabold text-[#0F1B4D]">{{ $student->name }}</h1>
                <p class="text-sm text-[#5B6478]">{{ $student->matric_no }}{{ $student->course ? ' · '.$student->course : '' }}</p>
                <div class="mt-2 flex flex-wrap gap-2 text-xs">
                    <span class="rounded-full bg-[#EEF2FB] px-2.5 py-1 font-semibold text-[#334155]"><i class="fa-solid fa-building-user mr-1"></i>{{ $row['supervisor_name'] ?? 'No company supervisor' }}</span>
                    @if($row['has_dates'])
                        <span class="rounded-full bg-[#EEF2FB] px-2.5 py-1 font-semibold text-[#334155]"><i class="fa-regular fa-calendar mr-1"></i>{{ $row['start']->format('d M Y') }} to {{ $row['end']->format('d M Y') }}</span>
                        <span class="rounded-full bg-[#EEF2FB] px-2.5 py-1 font-semibold text-[#334155]">{{ $planner->workingDaysLabel() }}</span>
                    @endif
                </div>
            </div>
        </div>
        <a href="/lecturer/students/{{ $student->student_id }}/message" class="flex min-h-11 items-center gap-2 rounded-xl bg-[#2F5BFF] px-5 text-sm font-bold text-white transition hover:bg-[#1F45D6]"><i class="fa-solid fa-comment"></i> Message student</a>
    </section>

    @if(! $row['has_dates'])
        <div class="rounded-2xl border border-dashed border-[#C3CCEA] bg-[#F5F7FF] px-5 py-8 text-center text-sm text-[#5B6478]">
            This student's internship dates have not been set. Ask the administrator to set the start date and working days, and their weeks will appear here.
        </div>
    @endif

    {{-- Numbers --}}
    @if($row['has_dates'])
        <section class="grid grid-cols-2 gap-3.5 xl:grid-cols-4">
            <div class="rounded-[20px] border border-[#BFEBD3] bg-[#EEFAF3] p-4">
                <div class="text-xs font-semibold text-[#334155]">Attendance</div>
                <div class="text-2xl font-extrabold">{{ $row['attendance_done'] }} / {{ $row['expected'] }}</div>
                <p class="text-xs text-[#5B6478]">{{ $row['recent_missing_attendance'] > 0 ? $row['recent_missing_attendance'].' missed in the last 5 working days' : 'On track' }}</p>
            </div>
            <div class="rounded-[20px] border border-[#FDE2B8] bg-[#FFF7EA] p-4">
                <div class="text-xs font-semibold text-[#334155]">Logbook entries</div>
                <div class="text-2xl font-extrabold">{{ $row['entries_done'] }} / {{ $row['expected'] }}</div>
                <p class="text-xs text-[#5B6478]">{{ $row['missing_entries'] > 0 ? $row['missing_entries'].' overdue' : 'None overdue' }}</p>
            </div>
            <div class="rounded-[20px] border border-[#D3E0FF] bg-[#EEF3FF] p-4">
                <div class="text-xs font-semibold text-[#334155]">Company supervisor</div>
                <span class="mt-1 inline-block rounded-full px-2.5 py-1 text-xs font-bold {{ $reviewClass[$row['review_label']] ?? 'bg-[#EEF1F7] text-[#5B6478]' }}">{{ $row['review_label'] }}</span>
                <p class="mt-1 text-xs text-[#5B6478]">Only the supervisor validates entries.</p>
            </div>
            <div class="rounded-[20px] border border-[#DDD0FB] bg-[#F5F0FF] p-4">
                <div class="text-xs font-semibold text-[#334155]">Tasks</div>
                <div class="text-2xl font-extrabold">{{ $row['tasks_total'] > 0 ? $row['tasks_done'].' of '.$row['tasks_total'] : 'None' }}</div>
                <p class="text-xs text-[#5B6478]">{{ $row['tasks_overdue'] > 0 ? $row['tasks_overdue'].' overdue' : 'Done on time' }}. Task details are private to the student.</p>
            </div>
        </section>
    @endif

    @if(count($row['issues']) > 0)
        <section class="p-5 {{ $card }}">
            <h2 class="mb-2 text-base font-extrabold">Needs attention</h2>
            <ul class="space-y-2">
                @foreach($row['issues'] as $issue)
                    <li class="flex flex-wrap items-center gap-2 text-sm">
                        <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $priorityClass[$issue['priority']] }}">{{ $issue['priority'] }}</span>
                        <span class="text-[#334155]">{{ $issue['text'] }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Logbook by week (read only) --}}
    @if($planner->hasStart() && $selected)
        <section id="logbook" class="p-5 md:p-6 {{ $card }}">
            <h2 class="mb-3 text-xl font-extrabold">Logbook by week</h2>

            <div class="flex flex-wrap gap-2">
                @foreach($weeks as $week)
                    <a href="/lecturer/students/{{ $student->student_id }}?week={{ $week['number'] }}#logbook" title="{{ $statusText[$week['status']] }}" class="flex min-h-[42px] min-w-[52px] items-center justify-center rounded-xl px-3 text-sm font-extrabold transition {{ $chipClass[$week['status']] }} {{ $week['number'] === $selected['number'] ? 'ring-2 ring-[#2F5BFF] ring-offset-2' : '' }}">W{{ $week['number'] }}</a>
                @endforeach
            </div>
            <div class="mb-5 mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-[#5B6478]">
                <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-[#34D399]"></span>Validated</span>
                <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-[#818CF8]"></span>Waiting for review</span>
                <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-[#FB923C]"></span>Revision needed</span>
                <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-[#2F5BFF]"></span>Current week</span>
                <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-[#FB7185]"></span>Entries missing</span>
                <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-[#CBD5E1]"></span>Not open yet</span>
            </div>

            @if($selected['status'] === 'locked')
                <div class="rounded-[20px] border border-dashed border-[#C3CCEA] bg-[#F5F7FF] px-6 py-10 text-center">
                    <i class="fa-solid fa-lock mb-2 block text-2xl text-[#94A3B8]"></i>
                    <p class="text-lg font-extrabold">Week {{ $selected['number'] }} opens on {{ $selected['start']->format('l, d F Y') }}</p>
                </div>
            @else
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <div class="flex flex-wrap items-center gap-2.5">
                            <h3 class="text-2xl font-extrabold">Week {{ $selected['number'] }}</h3>
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $chipClass[$selected['status']] }}">{{ $statusText[$selected['status']] }}</span>
                        </div>
                        <p class="mt-1 text-[13px] text-[#5B6478]">{{ $selected['start']->format('d M') }} to {{ $selected['end']->format('d M Y') }}</p>
                    </div>
                    <div class="min-w-[220px]">
                        <div class="flex items-center justify-between text-[13px] font-semibold">
                            <span>{{ $selected['required'] > 0 ? $selected['done'].' of '.$selected['required'].' days complete' : 'No entry needed this week' }}</span>
                            <span class="text-[#5B6478]">{{ $selected['percent'] }}%</span>
                        </div>
                        <div class="mt-1.5 h-2 overflow-hidden rounded-[5px] bg-[#E3E8F2]"><div class="h-full rounded-[5px] bg-[#2F5BFF]" style="width:{{ $selected['percent'] }}%"></div></div>
                    </div>
                </div>

                <div class="space-y-3.5">
                    @foreach($selected['days'] as $day)
                        @php
                            $date = $day['date'];
                            $attendanceRow = $day['attendance'];
                        @endphp
                        <article class="flex flex-wrap items-start gap-5 rounded-[20px] border border-[#E7ECF6] bg-[#FCFDFF] p-[18px]">
                            <div class="w-14 flex-none pt-1 text-center">
                                <div class="text-xs font-extrabold tracking-wider text-[#5B6478]">{{ strtoupper($date->format('D')) }}</div>
                                <div class="text-[28px] font-extrabold leading-none">{{ $date->format('d') }}</div>
                                <div class="text-xs text-[#5B6478]">{{ $date->format('M') }}</div>
                            </div>

                            <div class="min-w-0 flex-[1_1_320px] space-y-3">
                                @if($day['state'] === 'done')
                                    @foreach($day['entries'] as $entry)
                                        <div class="space-y-2 {{ ! $loop->first ? 'border-t border-[#EDF0F7] pt-3' : '' }}">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="rounded-full bg-[#D1FAE5] px-2.5 py-1 text-xs font-bold text-[#065F46]">Done</span>
                                                @if($day['wfh'])
                                                    <span class="rounded-full bg-[#E8EFFF] px-2.5 py-1 text-xs font-bold text-[#1F45D6]"><i class="fa-solid fa-house"></i> Work from home</span>
                                                @endif
                                                <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $entryClass[$entry->supervisor_status ?? 'Pending'] ?? $entryClass['Pending'] }}">Supervisor: {{ $entry->supervisor_status ?? 'Pending' }}</span>
                                            </div>
                                            <p class="whitespace-pre-line break-words text-sm leading-relaxed text-[#334155]">{{ $entry->description }}</p>
                                            @if($entry->attachments->count())
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach($entry->attachments as $attachment)
                                                        <a href="{{ $attachment->url }}" target="_blank" rel="noopener" title="{{ $attachment->file_name }}" class="flex min-h-8 max-w-[260px] items-center gap-1.5 rounded-[9px] bg-[#EEF2FB] px-2.5 text-xs font-medium text-[#1E293B] transition hover:bg-[#E1E8F8]">
                                                            <i class="fa-solid fa-paperclip text-[#94A3B8]"></i>
                                                            <span class="truncate">{{ $attachment->file_name }}</span>
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach

                                @elseif($day['state'] === 'dayoff')
                                    @php
                                        $offType = $attendanceRow->dayOffLabel();
                                        $offChip = $offType === 'MC' ? 'bg-[#FFE4E6] text-[#9F1239]' : ($offType === 'Public holiday' ? 'bg-[#E0E7FF] text-[#3730A3]' : 'bg-[#FEF3C7] text-[#7C2D12]');
                                    @endphp
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $offChip }}">{{ $offType }}</span>
                                        <span class="text-xs font-semibold text-[#5B6478]">No entry needed</span>
                                    </div>
                                    @if($attendanceRow->note)
                                        <p class="break-words text-sm text-[#334155]">{{ $attendanceRow->note }}</p>
                                    @endif
                                    @if($attendanceRow->hasMc())
                                        <a href="/attendance/{{ $attendanceRow->attendance_id }}/mc" target="_blank" rel="noopener" class="inline-flex min-h-8 max-w-[260px] items-center gap-1.5 rounded-[9px] bg-[#EEF2FB] px-2.5 text-xs font-medium text-[#1E293B] transition hover:bg-[#E1E8F8]">
                                            <i class="fa-solid fa-file-medical text-[#BE123C]"></i>
                                            <span class="truncate">{{ $attendanceRow->mc_file_name }}</span>
                                        </a>
                                    @endif

                                @elseif($day['state'] === 'today')
                                    <span class="rounded-full bg-[#E8EFFF] px-2.5 py-1 text-xs font-bold text-[#1F45D6]">Today</span>
                                    <p class="text-sm text-[#5B6478]">Today's entry is not written yet.</p>

                                @elseif($day['state'] === 'missing')
                                    <span class="rounded-full bg-[#FFE4E6] px-2.5 py-1 text-xs font-bold text-[#9F1239]">Missing</span>
                                    <p class="text-sm text-[#5B6478]">No entry for {{ $date->format('l, d F') }}.</p>

                                @else
                                    <span class="rounded-full bg-[#EEF1F7] px-2.5 py-1 text-xs font-bold text-[#5B6478]">Upcoming</span>
                                @endif

                                @if($attendanceRow && $attendanceRow->check_in)
                                    <p class="text-xs text-[#5B6478]">
                                        <i class="fa-solid fa-clock mr-1"></i>Clocked in {{ $attendanceRow->checkInLocal() }}{{ $attendanceRow->check_out ? ', out '.$attendanceRow->checkOutLocal() : '' }}
                                        @if($attendanceRow->workModeLabel()) &middot; {{ $attendanceRow->workModeLabel() }} @endif
                                    </p>
                                @endif
                            </div>
                        </article>
                    @endforeach

                    @if(empty($selected['days']))
                        <div class="rounded-[20px] border border-dashed border-[#C3CCEA] bg-[#F5F7FF] p-8 text-center text-sm text-[#5B6478]">None of this student's working days fall in this week.</div>
                    @endif
                </div>
            @endif
        </section>
    @endif
</div>
@endsection