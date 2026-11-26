@extends('layouts.app')

@section('content')
    @include('partials.google-fonts', ['includeCaveat' => false])
    @php
        $chipClass = [
            'waiting' => 'bg-[#FEF3C7] text-[#7C2D12]',
            'reviewed' => 'bg-[#D1FAE5] text-[#065F46]',
            'revision' => 'bg-[#FFEDD5] text-[#9A3412]',
            'current' => 'bg-[#E8EFFF] text-[#1F45D6]',
            'empty' => 'bg-[#EEF1F7] text-[#94A3B8]',
            'locked' => 'bg-[#EEF1F7] text-[#CBD3E3]',
        ];
        $chipHint = [
            'waiting' => 'Waiting for the company supervisor',
            'reviewed' => 'Reviewed',
            'revision' => 'Sent back for revision',
            'current' => 'In progress, not sent yet',
            'empty' => 'No entries',
            'locked' => 'Not open yet',
        ];
        $statusBadge = [
            \App\Models\Logbook::SUPERVISOR_STATUS_PENDING => 'bg-[#FEF3C7] text-[#92400E]',
            \App\Models\Logbook::SUPERVISOR_STATUS_VALIDATED => 'bg-[#D1FAE5] text-[#065F46]',
            \App\Models\Logbook::SUPERVISOR_STATUS_REVISION_REQUESTED => 'bg-[#FFE4E6] text-[#9F1239]',
        ];
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
        $card =
            'rounded-[22px] border border-[#E3E8F2] bg-white shadow-[0_6px_18px_rgba(15,27,77,0.05)]';
        $initials = \Illuminate\Support\Str::of($student->name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn($p) => mb_substr($p, 0, 1))
            ->implode('');
    @endphp

    <div
        class="space-y-5"
        style="font-family:'Plus Jakarta Sans',ui-sans-serif,system-ui,sans-serif"
    >
        <a
            href="/lecturer/dashboard#students"
            class="inline-flex items-center gap-2 text-sm font-bold text-[#1F45D6] hover:underline"
        ><i class="fa-solid fa-arrow-left"></i> Back to dashboard</a>

        @if (session('success'))
            <div
                class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div
                class="rounded-2xl border border-[#FBCFCB] bg-[#FFF1EF] px-4 py-3 text-sm text-[#9F1239]">
                {{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div
                class="rounded-2xl border border-[#FBCFCB] bg-[#FFF1EF] px-4 py-3 text-sm text-[#9F1239]">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Student + week picker --}}
        <section class="{{ $card }} px-6 py-5">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <span
                        class="flex h-12 w-12 flex-none items-center justify-center rounded-full bg-[#2F5BFF] text-sm font-extrabold text-white"
                    >{{ $initials }}</span>
                    <div>
                        <h1 class="text-xl font-extrabold leading-tight">{{ $student->name }}</h1>
                        <p class="text-[13px] text-[#5B6478]">{{ $student->matric_no }} &middot;
                            {{ $student->course }}</p>
                        <div class="mt-2 flex flex-wrap gap-2 text-xs">
                            <span class="rounded-full bg-[#EEF2FB] px-2.5 py-1 font-semibold text-[#334155]">
                                <i class="fa-solid fa-building-user mr-1"></i>{{ $row['supervisor_name'] ?? 'No company supervisor' }}
                            </span>
                            @if ($row['has_dates'])
                                <span class="rounded-full bg-[#EEF2FB] px-2.5 py-1 font-semibold text-[#334155]">
                                    <i class="fa-regular fa-calendar mr-1"></i>{{ $row['start']->format('d M Y') }} to {{ $row['end']->format('d M Y') }}
                                </span>
                                <span class="rounded-full bg-[#EEF2FB] px-2.5 py-1 font-semibold text-[#334155]">{{ $planner->workingDaysLabel() }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a
                        href="{{ route('lecturer.student.logbooks.pdf', $student->student_id) }}"
                        class="flex min-h-10 items-center gap-2 rounded-xl border border-[#CBD3E3] bg-white px-4 text-sm font-bold text-[#1F45D6] transition hover:bg-[#F3F5FC]"
                    ><i class="fa-solid fa-file-pdf"></i> Export logbook PDF</a>
                    <a
                        href="/lecturer/students/{{ $student->student_id }}/message"
                        class="flex min-h-10 items-center gap-2 rounded-xl border border-[#CBD3E3] bg-white px-4 text-sm font-bold text-[#1F45D6] transition hover:bg-[#F3F5FC]"
                    ><i class="fa-solid fa-comment"></i> Message</a>
                </div>
            </div>

            @if ($total > 0)
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($chips as $chip)
                        @php $active = $selected && $selected['number'] === $chip['number']; @endphp
                        @if ($chip['state'] === 'locked')
                            <span
                                title="{{ $chipHint['locked'] }}"
                                class="{{ $chipClass['locked'] }} flex min-h-[40px] min-w-[50px] items-center justify-center rounded-xl px-3 text-sm font-extrabold"
                            >W{{ $chip['number'] }}</span>
                        @else
                            <a
                                href="/lecturer/students/{{ $student->student_id }}?week={{ $chip['number'] }}#logbook"
                                title="{{ $chipHint[$chip['state']] }}{{ $chip['pending'] ? ' (' . $chip['pending'] . ' waiting)' : '' }}"
                                class="{{ $chipClass[$chip['state']] }} {{ $active ? 'ring-2 ring-[#2F5BFF] ring-offset-2' : '' }} flex min-h-[40px] min-w-[50px] items-center justify-center gap-1 rounded-xl px-3 text-sm font-extrabold transition"
                            >W{{ $chip['number'] }}@if ($chip['state'] === 'waiting')
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#C2410C]"></span>
                                @endif
                            </a>
                        @endif
                    @endforeach
                </div>
                <div
                    class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-[11px] font-semibold text-[#5B6478]">
                    <span><span
                            class="mr-1 inline-block h-2 w-2 rounded-full bg-[#FDBA3B]"></span>Waiting</span>
                    <span><span
                            class="mr-1 inline-block h-2 w-2 rounded-full bg-[#34D399]"></span>Reviewed</span>
                    <span><span
                            class="mr-1 inline-block h-2 w-2 rounded-full bg-[#FB923C]"></span>Revision</span>
                    <span><span class="mr-1 inline-block h-2 w-2 rounded-full bg-[#2F5BFF]"></span>In
                        progress (not sent yet)</span>
                </div>
            @endif
        </section>

        @if ($row['has_dates'])
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

        @if (count($row['issues']) > 0)
            <section class="p-5 {{ $card }}">
                <h2 class="mb-2 text-base font-extrabold">Needs attention</h2>
                <ul class="space-y-2">
                    @foreach ($row['issues'] as $issue)
                        <li class="flex flex-wrap items-center gap-2 text-sm">
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $priorityClass[$issue['priority']] }}">{{ $issue['priority'] }}</span>
                            <span class="text-[#334155]">{{ $issue['text'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if (!$selected)
            <div
                class="rounded-[22px] border border-dashed border-[#C3CCEA] bg-[#F5F7FF] px-6 py-12 text-center text-sm text-[#5B6478]">
                This student's internship dates have not been set yet.</div>
        @else
            <div
                id="logbook"
                class="grid grid-cols-1 items-start gap-5 lg:grid-cols-3"
            >
                <div class="space-y-5 lg:col-span-2">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <div>
                            <h2 class="text-2xl font-extrabold">Week {{ $selected['number'] }}</h2>
                            <p class="text-[13px] text-[#5B6478]">
                                {{ $selected['start']->format('d M') }} to
                                {{ $selected['end']->format('d M Y') }}
                                @if ($selected['sent'])
                                    &middot; sent
                                    {{ optional($selected['submission']->sent_at)->timezone(\App\Models\Attendance::TIMEZONE)->format('d M, H:i') }}
                                @endif
                            </p>
                        </div>
                        @if (!$selected['sent'])
                            <span
                                class="rounded-full bg-[#E8EFFF] px-3 py-1 text-xs font-bold text-[#1F45D6]"
                            >In progress &middot; sent to you after
                                {{ $selected['closes']->format('l, d M') }}</span>
                        @endif
                    </div>

                    {{-- Read-only weekly summary --}}
                    @if ($selected['entries']->isNotEmpty())
                        <section
                            class="rounded-[22px] border border-[#DDD0FB] bg-gradient-to-br from-[#F7F3FF] to-white p-5"
                        >
                            <div class="mb-2 flex items-center gap-2">
                                <span
                                    class="flex h-8 w-8 items-center justify-center rounded-[10px] bg-[#F0EAFF] text-[#6D28D9]"
                                ><i class="fa-solid fa-wand-magic-sparkles text-sm"></i></span>
                                <h3 class="text-base font-extrabold">Week at a glance</h3>
                            </div>
                            <div id="week-summary-body">
                                @include('lecturer.students.week-summary', [
                                    'summary' => $selected['summary'],
                                    'missing' => $selected['missing'],
                                    'generatedAt' => optional($selected['submission'])->ai_generated_at,
                                ])
                            </div>
                        </section>
                    @endif

                    {{-- Day by day --}}
                    <div class="space-y-3">
                        @foreach ($selected['days'] as $day)
                            <article
                                class="{{ $day['state'] === 'missing' ? 'border-[#FBCFCB] bg-[#FFF8F7]' : ($day['state'] === 'upcoming' ? 'border-[#E7ECF6] bg-[#F7F9FD]' : 'border-[#E7ECF6] bg-white') }} flex flex-wrap items-start gap-4 rounded-[20px] border p-4"
                            >
                                <div class="w-14 flex-none pt-0.5 text-center">
                                    <div class="text-xs font-extrabold tracking-wider text-[#5B6478]">
                                        {{ strtoupper($day['date']->format('D')) }}</div>
                                    <div class="text-[26px] font-extrabold leading-none">
                                        {{ $day['date']->format('d') }}</div>
                                    @if ($day['extra'])
                                        <div
                                            class="mx-auto mt-1 w-fit rounded bg-[#FEF3C7] px-1.5 text-[10px] font-extrabold text-[#7C2D12]">
                                            Extra day</div>
                                    @endif
                                </div>

                                <div class="min-w-0 flex-[1_1_300px] space-y-3">
                                    @if ($day['state'] === 'entries')
                                        @foreach ($day['entries'] as $entry)
                                            @php
                                                $status = $entry->supervisor_status ?? \App\Models\Logbook::SUPERVISOR_STATUS_PENDING;
                                                $long =
                                                    mb_strlen((string) $entry->description) > 260;
                                                $lastFeedback = $entry->feedbacks
                                                    ->sortByDesc('feedback_id')
                                                    ->first();
                                            @endphp
                                            <div
                                                class="{{ $loop->first ? '' : 'border-t border-[#EDF0F7] pt-3' }}"
                                                data-entry
                                            >
                                                <div class="mb-1.5 flex flex-wrap items-center gap-2">
                                                    <span
                                                        class="{{ $statusBadge[$status] ?? $statusBadge[\App\Models\Logbook::SUPERVISOR_STATUS_PENDING] }} rounded-full px-2.5 py-1 text-xs font-bold"
                                                    >{{ $status === \App\Models\Logbook::SUPERVISOR_STATUS_PENDING && !$selected['sent'] ? 'Not sent yet' : $status }}</span>
                                                    <a
                                                        href="/lecturer/logbook/{{ $entry->logbook_id }}"
                                                        class="text-xs font-bold text-[#5B6478] hover:text-[#1F45D6] hover:underline"
                                                    >View entry</a>
                                                </div>

                                                <p
                                                    data-body
                                                    class="{{ $long ? 'line-clamp-4' : '' }} whitespace-pre-line break-words text-sm leading-relaxed text-[#334155]"
                                                >{{ $entry->description }}</p>
                                                @if ($long)
                                                    <button
                                                        type="button"
                                                        data-expand
                                                        class="mt-1 text-xs font-bold text-[#1F45D6] hover:underline"
                                                    >Read full entry</button>
                                                @endif

                                                @if ($entry->attachments->isNotEmpty())
                                                    @include('lecturer.students.attachment-previews', [
                                                        'attachments' => $entry->attachments,
                                                    ])
                                                @endif

                                                @if ($lastFeedback)
                                                    <p
                                                        class="mt-2 rounded-xl bg-[#F7F9FD] px-3 py-2 text-xs text-[#5B6478]">
                                                        <strong>Latest reviewer feedback:</strong>
                                                        {{ $lastFeedback->comment }}</p>
                                                @endif
                                            </div>
                                        @endforeach
                                    @elseif($day['state'] === 'dayoff')
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span
                                                class="rounded-full bg-[#FEF3C7] px-2.5 py-1 text-xs font-bold text-[#7C2D12]"
                                            >{{ $day['off']->dayOffLabel() }}</span>
                                            <span class="text-xs font-semibold text-[#5B6478]">No entry
                                                needed</span>
                                        </div>
                                        @if ($day['off']->note)
                                            <p class="break-words text-sm text-[#334155]">
                                                {{ $day['off']->note }}</p>
                                        @endif
                                        @if ($day['off']->hasMc())
                                            <a
                                                href="/attendance/{{ $day['off']->attendance_id }}/mc"
                                                target="_blank"
                                                rel="noopener"
                                                class="inline-flex min-h-8 items-center gap-1.5 rounded-[9px] bg-[#EEF2FB] px-2.5 text-xs font-medium text-[#1E293B] hover:bg-[#E1E8F8]"
                                            ><i class="fa-solid fa-file-medical text-[#BE123C]"></i>
                                                {{ $day['off']->mc_file_name }}</a>
                                        @endif
                                    @elseif($day['state'] === 'missing')
                                        <span
                                            class="rounded-full bg-[#FFE4E6] px-2.5 py-1 text-xs font-bold text-[#9F1239]"
                                        >No entry</span>
                                        <p class="text-sm text-[#5B6478]">The student did not write an
                                            entry for this day.</p>
                                    @elseif($day['state'] === 'today')
                                        <span
                                            class="rounded-full bg-[#E8EFFF] px-2.5 py-1 text-xs font-bold text-[#1F45D6]"
                                        >Today</span>
                                        <p class="text-sm text-[#5B6478]">Not written yet.</p>
                                    @else
                                        <span
                                            class="rounded-full bg-[#EEF1F7] px-2.5 py-1 text-xs font-bold text-[#5B6478]"
                                        >Upcoming</span>
                                    @endif
                                </div>
                            </article>
                        @endforeach

                        @if ($selected['days']->isEmpty())
                            <div
                                class="rounded-[20px] border border-dashed border-[#C3CCEA] bg-[#F5F7FF] p-8 text-center text-sm text-[#5B6478]">
                                Nothing to show for this week.</div>
                        @endif
                    </div>
                </div>

                {{-- Read-only review status --}}
                <aside class="{{ $card }} space-y-4 p-5 lg:sticky lg:top-6">
                    <h3 class="text-base font-extrabold">Review status</h3>

                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="rounded-xl bg-[#FEF3C7] px-2 py-2.5">
                            <div class="text-xl font-extrabold text-[#7C2D12]">
                                {{ $selected['pending'] }}</div>
                            <div class="text-[11px] font-semibold text-[#7C2D12]">Waiting</div>
                        </div>
                        <div class="rounded-xl bg-[#D1FAE5] px-2 py-2.5">
                            <div class="text-xl font-extrabold text-[#065F46]">
                                {{ $selected['validated'] }}</div>
                            <div class="text-[11px] font-semibold text-[#065F46]">Validated</div>
                        </div>
                        <div class="rounded-xl bg-[#FFEDD5] px-2 py-2.5">
                            <div class="text-xl font-extrabold text-[#9A3412]">
                                {{ $selected['revision'] }}</div>
                            <div class="text-[11px] font-semibold text-[#9A3412]">Revision</div>
                        </div>
                    </div>

                    @if (!$selected['sent'])
                        <p class="rounded-xl bg-[#F7F9FD] px-3.5 py-3 text-[13px] text-[#5B6478]">This
                            week is still open. It is sent to the company supervisor automatically after
                            {{ $selected['closes']->format('l, d M') }}.</p>
                    @elseif($selected['pending'] === 0)
                        <p class="rounded-xl bg-[#ECFDF5] px-3.5 py-3 text-[13px] text-[#065F46]"><i
                                class="fa-solid fa-circle-check mr-1"
                            ></i> The company supervisor has reviewed every entry in this week.</p>
                    @else
                        <p class="text-[13px] leading-relaxed text-[#5B6478]">
                            {{ $selected['pending'] }} {{ \Illuminate\Support\Str::plural('entry', $selected['pending']) }}
                            {{ $selected['pending'] === 1 ? 'is' : 'are' }} waiting for the company supervisor.
                        </p>
                    @endif
                    <p class="rounded-xl border border-[#E3E8F2] bg-[#F7F9FD] px-3.5 py-3 text-xs text-[#5B6478]">
                        Lecturer view only. The company supervisor is responsible for reviewing entries.
                    </p>
                </aside>
            </div>
        @endif
    </div>

    <script>
        (function() {
            document.addEventListener('click', function(event) {
                var button = event.target.closest ? event.target.closest('[data-expand]') : null;
                if (!button) return;
                var body = button.closest('[data-entry]').querySelector('[data-body]');
                var expanded = body.classList.toggle('line-clamp-4');
                button.textContent = expanded ? 'Read full entry' : 'Show less';
            });
        })();
    </script>
@endsection
