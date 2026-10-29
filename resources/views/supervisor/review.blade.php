@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<div class="space-y-6" style="font-family:'Plus Jakarta Sans',ui-sans-serif,system-ui,sans-serif">
    <a href="{{ $logbook->student_id && $logbook->week_no ? '/supervisor/students/'.$logbook->student_id.'?week='.$logbook->week_no : '/supervisor/dashboard' }}" class="inline-flex items-center gap-2 text-sm font-bold text-[#1F45D6] hover:underline">
        <i class="fa-solid fa-arrow-left"></i> Back to Week {{ $logbook->week_no }}
    </a>

    @if(session('error'))
        <div class="rounded-2xl border border-[#FBCFCB] bg-[#FFF1EF] px-4 py-3 text-sm text-[#9F1239]">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="rounded-2xl border border-[#FBCFCB] bg-[#FFF1EF] px-4 py-3 text-sm text-[#9F1239]">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $mine = $logbook->supervisor_status ?? 'Pending';
        $mineBadge = $mine === 'Validated'
            ? 'bg-[#D1FAE5] text-[#065F46]'
            : ($mine === 'Revision Requested' ? 'bg-[#FFE4E6] text-[#9F1239]' : 'bg-[#FEF3C7] text-[#92400E]');
        $lec = $logbook->status ?? 'Pending';
        $lecBadge = $lec === 'Approved'
            ? 'bg-[#D1FAE5] text-[#065F46]'
            : ($lec === 'Rejected' ? 'bg-[#FFE4E6] text-[#9F1239]' : 'bg-[#FEF3C7] text-[#92400E]');
    @endphp

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 items-start">
        <div id="review-main" data-live class="lg:col-span-2 rounded-[22px] border border-[#E3E8F2] bg-white p-5 md:p-6 shadow-[0_6px_18px_rgba(15,27,77,0.05)] space-y-4">
            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-[#EEF1F7] px-2.5 py-1 text-xs font-bold uppercase tracking-wide text-[#5B6478]">Week {{ $logbook->week_no }}</span>
                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $mineBadge }}">Your status: {{ $mine }}</span>
                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $lecBadge }}">Lecturer: {{ $lec }}</span>
                @if($logbook->activity_date)
                    <span class="text-xs text-[#94A3B8]">Entry date: {{ \Carbon\Carbon::parse($logbook->activity_date)->format('d M Y') }}</span>
                @endif
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-[#94A3B8]">Student</p>
                <p class="mt-1 text-base font-semibold text-[#0F1B4D]">
                    {{ optional($logbook->student)->name ?? 'Unknown student' }}
                    <span class="text-sm font-normal text-[#5B6478]">- {{ optional($logbook->student)->matric_no ?? 'N/A' }}</span>
                </p>
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-[#94A3B8]">Activity description</p>
                <p class="mt-2 whitespace-pre-line break-words text-sm leading-6 text-[#334155]">{{ $logbook->description }}</p>
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-[#94A3B8]">Attachment</p>
                @if($logbook->attachments->count())
                    @foreach($logbook->attachments as $attachment)
                        <a href="{{ $attachment->url }}" target="_blank" class="mt-1 block break-all text-sm text-[#1F45D6] underline">{{ $attachment->file_name }}</a>
                    @endforeach
                @else
                    <p class="mt-1 text-sm text-[#5B6478]">No file attached.</p>
                @endif
            </div>

            @if($logbook->feedbacks->count())
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-[#94A3B8]">Earlier feedback</p>
                    <div class="mt-2 space-y-2">
                        @foreach($logbook->feedbacks->sortByDesc('feedback_id') as $feedback)
                            @php
                                $fromSupervisor = ! empty($feedback->supervisor_id);
                                $who = $fromSupervisor
                                    ? (optional($feedback->supervisor)->name ?? 'Company Supervisor')
                                    : (optional($feedback->lecturer)->name ?? 'Lecturer');
                            @endphp
                            <div class="rounded-2xl bg-[#F7F9FD] p-3 text-sm text-[#5B6478]">
                                <p class="text-xs text-[#94A3B8]">
                                    {{ $who }} ({{ $fromSupervisor ? 'Company Supervisor' : 'Lecturer' }})
                                    - {{ $feedback->feedback_date ? \Carbon\Carbon::parse($feedback->feedback_date)->format('d M Y') : '' }}
                                </p>
                                <p class="mt-1 break-words">{{ $feedback->comment }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="rounded-[22px] border border-[#E3E8F2] bg-white p-5 md:p-6 shadow-[0_6px_18px_rgba(15,27,77,0.05)]">
            <h3 class="text-base font-bold text-[#0F1B4D]">Your review</h3>
            <form data-async id="review-form" action="/supervisor/logbook/{{ $logbook->logbook_id }}/review" method="POST" class="mt-4 space-y-3">
                @csrf
                <div>
                    <label for="comment" class="block text-xs font-semibold text-[#5B6478] mb-1">Comment</label>
                    <textarea id="comment" name="comment" rows="6" placeholder="Required when requesting a revision. Optional when validating." class="w-full text-sm border border-[#CBD3E3] rounded-xl p-2.5 focus:outline-none focus:border-[#2F5BFF]">{{ old('comment') }}</textarea>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                    <button type="submit" name="decision" value="Validated" class="review-btn rounded-xl bg-[#10B981] px-3 py-2.5 text-sm font-bold text-white transition hover:bg-[#059669] disabled:opacity-60">
                        <i class="fa-solid fa-check"></i> Validate
                    </button>
                    <button type="submit" name="decision" value="Revision Requested" class="review-btn rounded-xl bg-[#F59E0B] px-3 py-2.5 text-sm font-bold text-[#1F2937] transition hover:bg-[#D97706] disabled:opacity-60">
                        <i class="fa-solid fa-rotate-left"></i> Request revision
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    var form = document.getElementById('review-form');
    if (!form) return;
    form.addEventListener('submit', function (e) {
        var submitter = e.submitter;
        var comment = document.getElementById('comment');
        if (submitter && submitter.value === 'Revision Requested' && comment && comment.value.trim() === '') {
            e.preventDefault();
            comment.focus();
            comment.classList.add('border-rose-400');
            return;
        }
        setTimeout(function () {
            form.querySelectorAll('.review-btn').forEach(function (b) { b.disabled = true; });
        }, 0);
    });
    window.addEventListener('pageshow', function () {
        form.querySelectorAll('.review-btn').forEach(function (b) { b.disabled = false; });
    });

    // After a review is saved, clear the comment box. The status and feedback list refresh by themselves.
    document.addEventListener('async:success', function (event) {
        if (event.target && event.target.id === 'review-form') {
            var box = document.getElementById('comment');
            if (box) box.value = '';
        }
    });


})();
</script>
@endsection