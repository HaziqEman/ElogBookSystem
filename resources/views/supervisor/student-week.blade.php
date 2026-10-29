@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

@php
    $chipClass = [
        'waiting'  => 'bg-[#FEF3C7] text-[#7C2D12]',
        'reviewed' => 'bg-[#D1FAE5] text-[#065F46]',
        'revision' => 'bg-[#FFEDD5] text-[#9A3412]',
        'current'  => 'bg-[#E8EFFF] text-[#1F45D6]',
        'empty'    => 'bg-[#EEF1F7] text-[#94A3B8]',
        'locked'   => 'bg-[#EEF1F7] text-[#CBD3E3]',
    ];
    $chipHint = [
        'waiting' => 'Waiting for your review', 'reviewed' => 'Reviewed', 'revision' => 'Sent back for revision',
        'current' => 'In progress, not sent yet', 'empty' => 'No entries', 'locked' => 'Not open yet',
    ];
    $statusBadge = [
        'Pending' => 'bg-[#FEF3C7] text-[#92400E]',
        'Validated' => 'bg-[#D1FAE5] text-[#065F46]',
        'Revision Requested' => 'bg-[#FFE4E6] text-[#9F1239]',
    ];
    $card = 'rounded-[22px] border border-[#E3E8F2] bg-white shadow-[0_6px_18px_rgba(15,27,77,0.05)]';
    $initials = \Illuminate\Support\Str::of($student->name)->explode(' ')->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('');
@endphp

<div class="space-y-5" style="font-family:'Plus Jakarta Sans',ui-sans-serif,system-ui,sans-serif">
    <a href="/supervisor/dashboard" class="inline-flex items-center gap-2 text-sm font-bold text-[#1F45D6] hover:underline"><i class="fa-solid fa-arrow-left"></i> Back to dashboard</a>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-2xl border border-[#FBCFCB] bg-[#FFF1EF] px-4 py-3 text-sm text-[#9F1239]">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-2xl border border-[#FBCFCB] bg-[#FFF1EF] px-4 py-3 text-sm text-[#9F1239]">
            <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Student + week picker --}}
    <section class="px-6 py-5 {{ $card }}">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <span class="flex h-12 w-12 flex-none items-center justify-center rounded-full bg-[#2F5BFF] text-sm font-extrabold text-white">{{ $initials }}</span>
                <div>
                    <h1 class="text-xl font-extrabold leading-tight">{{ $student->name }}</h1>
                    <p class="text-[13px] text-[#5B6478]">{{ $student->matric_no }} &middot; {{ $student->course }}</p>
                </div>
            </div>
            <a href="/supervisor/messages/with/{{ $student->student_id }}" class="flex min-h-10 items-center gap-2 rounded-xl border border-[#CBD3E3] bg-white px-4 text-sm font-bold text-[#1F45D6] transition hover:bg-[#F3F5FC]"><i class="fa-solid fa-comment"></i> Message</a>
        </div>

        @if($total > 0)
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach($chips as $chip)
                    @php $active = $selected && $selected['number'] === $chip['number']; @endphp
                    @if($chip['state'] === 'locked')
                        <span title="{{ $chipHint['locked'] }}" class="flex min-h-[40px] min-w-[50px] items-center justify-center rounded-xl px-3 text-sm font-extrabold {{ $chipClass['locked'] }}">W{{ $chip['number'] }}</span>
                    @else
                        <a href="/supervisor/students/{{ $student->student_id }}?week={{ $chip['number'] }}" title="{{ $chipHint[$chip['state']] }}{{ $chip['pending'] ? ' ('.$chip['pending'].' waiting)' : '' }}" class="flex min-h-[40px] min-w-[50px] items-center justify-center gap-1 rounded-xl px-3 text-sm font-extrabold transition {{ $chipClass[$chip['state']] }} {{ $active ? 'ring-2 ring-[#2F5BFF] ring-offset-2' : '' }}">W{{ $chip['number'] }}@if($chip['state'] === 'waiting')<span class="h-1.5 w-1.5 rounded-full bg-[#C2410C]"></span>@endif</a>
                    @endif
                @endforeach
            </div>
            <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-[11px] font-semibold text-[#5B6478]">
                <span><span class="mr-1 inline-block h-2 w-2 rounded-full bg-[#FDBA3B]"></span>Waiting</span>
                <span><span class="mr-1 inline-block h-2 w-2 rounded-full bg-[#34D399]"></span>Reviewed</span>
                <span><span class="mr-1 inline-block h-2 w-2 rounded-full bg-[#FB923C]"></span>Revision</span>
                <span><span class="mr-1 inline-block h-2 w-2 rounded-full bg-[#2F5BFF]"></span>In progress (not sent yet)</span>
            </div>
        @endif
    </section>

    @if(! $selected)
        <div class="rounded-[22px] border border-dashed border-[#C3CCEA] bg-[#F5F7FF] px-6 py-12 text-center text-sm text-[#5B6478]">This student's internship dates have not been set yet.</div>
    @else
        <form id="week-form" method="POST" action="/supervisor/students/{{ $student->student_id }}/weeks/{{ $selected['number'] }}/review" class="grid grid-cols-1 items-start gap-5 lg:grid-cols-3">
            @csrf

            <div class="space-y-5 lg:col-span-2">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <div>
                        <h2 class="text-2xl font-extrabold">Week {{ $selected['number'] }}</h2>
                        <p class="text-[13px] text-[#5B6478]">{{ $selected['start']->format('d M') }} to {{ $selected['end']->format('d M Y') }}
                            @if($selected['sent']) &middot; sent {{ optional($selected['submission']->sent_at)->timezone(\App\Models\Attendance::TIMEZONE)->format('d M, H:i') }} @endif
                        </p>
                    </div>
                    @if(! $selected['sent'])
                        <span class="rounded-full bg-[#E8EFFF] px-3 py-1 text-xs font-bold text-[#1F45D6]">In progress &middot; sent to you after {{ $selected['closes']->format('l, d M') }}</span>
                    @endif
                </div>

                {{-- AI summary --}}
                @if($selected['entries']->isNotEmpty())
                    <section id="week-summary" data-url="/supervisor/students/{{ $student->student_id }}/weeks/{{ $selected['number'] }}/summary" data-auto="{{ ($selected['summary'] || ! $selected['sent']) ? '0' : '1' }}" class="rounded-[22px] border border-[#DDD0FB] bg-gradient-to-br from-[#F7F3FF] to-white p-5">
                        <div class="mb-2 flex items-center gap-2">
                            <span class="flex h-8 w-8 items-center justify-center rounded-[10px] bg-[#F0EAFF] text-[#6D28D9]"><i class="fa-solid fa-wand-magic-sparkles text-sm"></i></span>
                            <h3 class="text-base font-extrabold">Week at a glance</h3>
                        </div>
                        <div id="week-summary-body">
                            @if($selected['summary'])
                                @include('supervisor.partials.week-summary', ['summary' => $selected['summary'], 'missing' => $selected['missing'], 'generatedAt' => optional($selected['submission'])->ai_generated_at])
                            @elseif($selected['sent'])
                                <p class="text-sm text-[#5B6478]"><i class="fa-solid fa-spinner fa-spin mr-1"></i> Writing the summary...</p>
                            @else
                                @include('supervisor.partials.week-summary', ['summary' => null, 'missing' => [], 'generatedAt' => null])
                            @endif
                        </div>
                    </section>
                @endif

                {{-- Day by day --}}
                <div class="space-y-3">
                    @foreach($selected['days'] as $day)
                        <article class="flex flex-wrap items-start gap-4 rounded-[20px] border p-4 {{ $day['state'] === 'missing' ? 'border-[#FBCFCB] bg-[#FFF8F7]' : ($day['state'] === 'upcoming' ? 'border-[#E7ECF6] bg-[#F7F9FD]' : 'border-[#E7ECF6] bg-white') }}">
                            <div class="w-14 flex-none pt-0.5 text-center">
                                <div class="text-xs font-extrabold tracking-wider text-[#5B6478]">{{ strtoupper($day['date']->format('D')) }}</div>
                                <div class="text-[26px] font-extrabold leading-none">{{ $day['date']->format('d') }}</div>
                                @if($day['extra'])<div class="mx-auto mt-1 w-fit rounded bg-[#FEF3C7] px-1.5 text-[10px] font-extrabold text-[#7C2D12]">Extra day</div>@endif
                            </div>

                            <div class="min-w-0 flex-[1_1_300px] space-y-3">
                                @if($day['state'] === 'entries')
                                    @foreach($day['entries'] as $entry)
                                        @php
                                            $status = $entry->supervisor_status ?? 'Pending';
                                            $isPending = $status === 'Pending';
                                            $canDecide = $selected['sent'] && $isPending;
                                            $choice = old('decision.'.$entry->logbook_id, 'Validated');
                                            $long = mb_strlen((string) $entry->description) > 260;
                                            $lastFeedback = $entry->feedbacks->sortByDesc('feedback_id')->first();
                                        @endphp
                                        <div class="{{ $loop->first ? '' : 'border-t border-[#EDF0F7] pt-3' }}" data-entry>
                                            <div class="mb-1.5 flex flex-wrap items-center gap-2">
                                                <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $statusBadge[$status] ?? $statusBadge['Pending'] }}">{{ $status === 'Pending' && ! $selected['sent'] ? 'Not sent yet' : $status }}</span>
                                                <a href="/supervisor/logbook/{{ $entry->logbook_id }}" class="text-xs font-bold text-[#5B6478] hover:text-[#1F45D6] hover:underline">Open single entry</a>
                                            </div>

                                            <p data-body class="whitespace-pre-line break-words text-sm leading-relaxed text-[#334155] {{ $long ? 'line-clamp-4' : '' }}">{{ $entry->description }}</p>
                                            @if($long)<button type="button" data-expand class="mt-1 text-xs font-bold text-[#1F45D6] hover:underline">Read full entry</button>@endif

                                            @if($entry->attachments->count())
                                                <div class="mt-2 flex flex-wrap gap-2">
                                                    @foreach($entry->attachments as $file)
                                                        <a href="{{ $file->url }}" target="_blank" rel="noopener" title="{{ $file->file_name }}" class="flex min-h-8 max-w-[260px] items-center gap-1.5 rounded-[9px] bg-[#EEF2FB] px-2.5 text-xs font-medium text-[#1E293B] transition hover:bg-[#E1E8F8]"><i class="fa-solid fa-paperclip text-[#94A3B8]"></i><span class="truncate">{{ $file->file_name }}</span></a>
                                                    @endforeach
                                                </div>
                                            @endif

                                            @if(! $isPending && $lastFeedback)
                                                <p class="mt-2 rounded-xl bg-[#F7F9FD] px-3 py-2 text-xs text-[#5B6478]"><strong>Your comment:</strong> {{ $lastFeedback->comment }}</p>
                                            @endif

                                            @if($canDecide)
                                                <div class="mt-3" data-decision-box>
                                                    <div class="inline-flex overflow-hidden rounded-xl border border-[#CBD3E3] text-xs font-bold">
                                                        <label class="cursor-pointer">
                                                            <input type="radio" class="peer sr-only" name="decision[{{ $entry->logbook_id }}]" value="Validated" {{ $choice === 'Validated' ? 'checked' : '' }}>
                                                            <span class="flex min-h-9 items-center gap-1.5 px-3.5 text-[#334155] transition peer-checked:bg-[#10B981] peer-checked:text-white"><i class="fa-solid fa-check"></i> Validate</span>
                                                        </label>
                                                        <label class="cursor-pointer border-l border-[#CBD3E3]">
                                                            <input type="radio" class="peer sr-only" name="decision[{{ $entry->logbook_id }}]" value="Revision Requested" {{ $choice === 'Revision Requested' ? 'checked' : '' }}>
                                                            <span class="flex min-h-9 items-center gap-1.5 px-3.5 text-[#334155] transition peer-checked:bg-[#F59E0B] peer-checked:text-[#1F2937]"><i class="fa-solid fa-rotate-left"></i> Needs revision</span>
                                                        </label>
                                                    </div>
                                                    <div data-comment-box class="mt-2 {{ $choice === 'Revision Requested' ? '' : 'hidden' }}">
                                                        <textarea name="comment[{{ $entry->logbook_id }}]" rows="2" maxlength="2000" placeholder="What does the student need to change?" class="w-full rounded-xl border border-[#CBD3E3] p-2.5 text-sm focus:border-[#2F5BFF] focus:outline-none">{{ old('comment.'.$entry->logbook_id) }}</textarea>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach

                                @elseif($day['state'] === 'dayoff')
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full bg-[#FEF3C7] px-2.5 py-1 text-xs font-bold text-[#7C2D12]">{{ $day['off']->dayOffLabel() }}</span>
                                        <span class="text-xs font-semibold text-[#5B6478]">No entry needed</span>
                                    </div>
                                    @if($day['off']->note)<p class="break-words text-sm text-[#334155]">{{ $day['off']->note }}</p>@endif
                                    @if($day['off']->hasMc())
                                        <a href="/attendance/{{ $day['off']->attendance_id }}/mc" target="_blank" rel="noopener" class="inline-flex min-h-8 items-center gap-1.5 rounded-[9px] bg-[#EEF2FB] px-2.5 text-xs font-medium text-[#1E293B] hover:bg-[#E1E8F8]"><i class="fa-solid fa-file-medical text-[#BE123C]"></i> {{ $day['off']->mc_file_name }}</a>
                                    @endif

                                @elseif($day['state'] === 'missing')
                                    <span class="rounded-full bg-[#FFE4E6] px-2.5 py-1 text-xs font-bold text-[#9F1239]">No entry</span>
                                    <p class="text-sm text-[#5B6478]">The student did not write an entry for this day.</p>

                                @elseif($day['state'] === 'today')
                                    <span class="rounded-full bg-[#E8EFFF] px-2.5 py-1 text-xs font-bold text-[#1F45D6]">Today</span>
                                    <p class="text-sm text-[#5B6478]">Not written yet.</p>

                                @else
                                    <span class="rounded-full bg-[#EEF1F7] px-2.5 py-1 text-xs font-bold text-[#5B6478]">Upcoming</span>
                                @endif
                            </div>
                        </article>
                    @endforeach

                    @if($selected['days']->isEmpty())
                        <div class="rounded-[20px] border border-dashed border-[#C3CCEA] bg-[#F5F7FF] p-8 text-center text-sm text-[#5B6478]">Nothing to show for this week.</div>
                    @endif
                </div>
            </div>

            {{-- Review panel --}}
            <aside class="space-y-4 p-5 lg:sticky lg:top-6 {{ $card }}">
                <h3 class="text-base font-extrabold">Your review</h3>

                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-xl bg-[#FEF3C7] px-2 py-2.5"><div class="text-xl font-extrabold text-[#7C2D12]">{{ $selected['pending'] }}</div><div class="text-[11px] font-semibold text-[#7C2D12]">Waiting</div></div>
                    <div class="rounded-xl bg-[#D1FAE5] px-2 py-2.5"><div class="text-xl font-extrabold text-[#065F46]">{{ $selected['validated'] }}</div><div class="text-[11px] font-semibold text-[#065F46]">Validated</div></div>
                    <div class="rounded-xl bg-[#FFEDD5] px-2 py-2.5"><div class="text-xl font-extrabold text-[#9A3412]">{{ $selected['revision'] }}</div><div class="text-[11px] font-semibold text-[#9A3412]">Revision</div></div>
                </div>

                @if(! $selected['sent'])
                    <p class="rounded-xl bg-[#F7F9FD] px-3.5 py-3 text-[13px] text-[#5B6478]">This week is still open. It is sent to you automatically after {{ $selected['closes']->format('l, d M') }}, and you will get a notification.</p>
                @elseif($selected['pending'] === 0)
                    <p class="rounded-xl bg-[#ECFDF5] px-3.5 py-3 text-[13px] text-[#065F46]"><i class="fa-solid fa-circle-check mr-1"></i> Every entry in this week has a decision. If the student revises an entry, it comes back here as waiting.</p>
                @else
                    <p class="text-[13px] leading-relaxed text-[#5B6478]">Every waiting entry is set to <strong>Validate</strong>. Switch the ones that need changes to <strong>Needs revision</strong> and add a comment, then submit once.</p>

                    <p id="decision-tally" class="text-sm font-bold text-[#1E293B]"></p>

                    <button id="submit-review" type="submit" class="min-h-11 w-full rounded-xl bg-[#10B981] px-4 text-sm font-bold text-white transition hover:bg-[#059669] disabled:opacity-60"></button>
                    <button type="button" id="reset-decisions" class="w-full text-center text-xs font-bold text-[#5B6478] hover:underline">Reset all to Validate</button>
                @endif
            </aside>
        </form>
    @endif
</div>

<script>
(function () {
    var form = document.getElementById('week-form');
    if (!form) return;

    // Read full entry
    form.addEventListener('click', function (event) {
        var button = event.target.closest ? event.target.closest('[data-expand]') : null;
        if (!button) return;
        var body = button.closest('[data-entry]').querySelector('[data-body]');
        var open = body.classList.toggle('line-clamp-4');
        button.textContent = open ? 'Read full entry' : 'Show less';
    });

    // Decisions: show the comment box, keep the tally and the button label up to date.
    var tally = document.getElementById('decision-tally');
    var submit = document.getElementById('submit-review');

    function refresh() {
        var boxes = form.querySelectorAll('[data-decision-box]');
        var revise = 0;

        boxes.forEach(function (box) {
            var picked = box.querySelector('input[type="radio"]:checked');
            var isRevise = picked && picked.value === 'Revision Requested';
            var comment = box.querySelector('[data-comment-box]');
            if (isRevise) revise++;
            if (comment) comment.classList.toggle('hidden', !isRevise);
        });

        if (!submit) return;
        var ok = boxes.length - revise;

        if (tally) tally.textContent = ok + ' to validate' + (revise ? ', ' + revise + ' to revise' : '');
        submit.style.background = revise ? '#F59E0B' : '#10B981';
        submit.style.color = revise ? '#1F2937' : '#FFFFFF';
        submit.textContent = revise
            ? 'Submit review (' + ok + ' validate, ' + revise + ' revise)'
            : (boxes.length === 1 ? 'Validate this entry' : 'Validate all ' + boxes.length + ' entries');
    }

    form.addEventListener('change', function (event) {
        if (event.target.matches && event.target.matches('input[type="radio"]')) refresh();
    });

    var reset = document.getElementById('reset-decisions');
    if (reset) reset.addEventListener('click', function () {
        form.querySelectorAll('input[type="radio"][value="Validated"]').forEach(function (radio) { radio.checked = true; });
        refresh();
    });

    form.addEventListener('submit', function (event) {
        var missing = null;

        form.querySelectorAll('[data-decision-box]').forEach(function (box) {
            var picked = box.querySelector('input[type="radio"]:checked');
            var area = box.querySelector('textarea');

            if (picked && picked.value === 'Revision Requested' && area && area.value.trim() === '' && !missing) missing = area;
        });

        if (missing) {
            event.preventDefault();
            missing.classList.add('border-rose-400');
            missing.focus();
            return;
        }

        if (submit) setTimeout(function () { submit.disabled = true; }, 0);
    });

    window.addEventListener('pageshow', function () { if (submit) submit.disabled = false; refresh(); });
    refresh();

    // AI summary: runs once automatically for a sent week, then on demand.
    var holder = document.getElementById('week-summary');
    var body = document.getElementById('week-summary-body');
    var running = false;

    async function runSummary(force) {
        if (!holder || !body || running) return;
        running = true;
        body.innerHTML = '<p class="text-sm text-[#5B6478]"><i class="fa-solid fa-spinner fa-spin mr-1"></i> Writing the summary...</p>';

        try {
            var token = form.querySelector('input[name="_token"]');
            var data = new FormData();
            if (force) data.append('force', '1');

            var response = await fetch(holder.getAttribute('data-url'), {
                method: 'POST',
                body: data,
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token ? token.value : '' }
            });
            var json = await response.json().catch(function () { return {}; });

            if (response.status === 429) throw new Error('Too many requests. Please wait a minute and try again.');
            if (!response.ok || !json.html) throw new Error(json.message || 'Could not write the summary.');

            body.innerHTML = json.html;
        } catch (error) {
            body.innerHTML = '<p class="text-sm text-[#9F1239]">' + (error.message || 'Could not write the summary.') + '</p>'
                + '<button type="button" data-summary-run class="mt-2 text-xs font-bold text-[#6D28D9] hover:underline">Try again</button>';
        } finally {
            running = false;
        }
    }

    if (holder) {
        holder.addEventListener('click', function (event) {
            var button = event.target.closest ? event.target.closest('[data-summary-run]') : null;
            if (button) runSummary(button.getAttribute('data-force') === '1');
        });

        if (holder.getAttribute('data-auto') === '1') runSummary(false);
    }
})();
</script>
@endsection
