@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <a href="/supervisor/dashboard" class="inline-flex items-center gap-2 text-sm font-semibold text-blue-700 hover:underline">
        <i class="fa-solid fa-arrow-left"></i> Back to dashboard
    </a>

    @if(session('error'))
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
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
            ? 'bg-emerald-100 text-emerald-700'
            : ($mine === 'Revision Requested' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700');
        $lec = $logbook->status ?? 'Pending';
        $lecBadge = $lec === 'Approved'
            ? 'bg-emerald-100 text-emerald-700'
            : ($lec === 'Rejected' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700');
    @endphp

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 items-start">
        <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-white p-5 md:p-6 shadow-xs space-y-4">
            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold uppercase tracking-wide text-slate-600">Week {{ $logbook->week_no }}</span>
                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $mineBadge }}">Your status: {{ $mine }}</span>
                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $lecBadge }}">Lecturer: {{ $lec }}</span>
                @if($logbook->activity_date)
                    <span class="text-xs text-slate-400">Entry date: {{ \Carbon\Carbon::parse($logbook->activity_date)->format('d M Y') }}</span>
                @endif
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Student</p>
                <p class="mt-1 text-base font-semibold text-slate-800">
                    {{ optional($logbook->student)->name ?? 'Unknown student' }}
                    <span class="text-sm font-normal text-slate-500">- {{ optional($logbook->student)->matric_no ?? 'N/A' }}</span>
                </p>
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Activity description</p>
                <p class="mt-2 whitespace-pre-line break-words text-sm leading-6 text-slate-700">{{ $logbook->description }}</p>
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Attachment</p>
                @if($logbook->attachments->count())
                    @foreach($logbook->attachments as $attachment)
                        <a href="/{{ $attachment->file_path }}" target="_blank" class="mt-1 block break-all text-sm text-blue-600 underline">{{ $attachment->file_name }}</a>
                    @endforeach
                @else
                    <p class="mt-1 text-sm text-slate-500">No file attached.</p>
                @endif
            </div>

            @if($logbook->feedbacks->count())
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Earlier feedback</p>
                    <div class="mt-2 space-y-2">
                        @foreach($logbook->feedbacks->sortByDesc('feedback_id') as $feedback)
                            @php
                                $fromSupervisor = ! empty($feedback->supervisor_id);
                                $who = $fromSupervisor
                                    ? (optional($feedback->supervisor)->name ?? 'Company Supervisor')
                                    : (optional($feedback->lecturer)->name ?? 'Lecturer');
                            @endphp
                            <div class="rounded-lg bg-slate-50 p-3 text-sm text-slate-600">
                                <p class="text-xs text-slate-400">
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

        <div class="rounded-xl border border-slate-200 bg-white p-5 md:p-6 shadow-xs">
            <h3 class="text-base font-bold text-slate-800">Your review</h3>
            <form id="review-form" action="/supervisor/logbook/{{ $logbook->logbook_id }}/review" method="POST" class="mt-4 space-y-3">
                @csrf
                <div>
                    <label for="comment" class="block text-xs font-semibold text-slate-600 mb-1">Comment</label>
                    <textarea id="comment" name="comment" rows="6" placeholder="Required when requesting a revision. Optional when validating." class="w-full text-sm border border-slate-200 rounded-lg p-2.5 focus:outline-none focus:border-blue-500">{{ old('comment') }}</textarea>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                    <button type="submit" name="decision" value="Validated" class="review-btn rounded-lg bg-emerald-600 px-3 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-60">
                        <i class="fa-solid fa-check"></i> Validate
                    </button>
                    <button type="submit" name="decision" value="Revision Requested" class="review-btn rounded-lg bg-amber-500 px-3 py-2.5 text-sm font-semibold text-slate-900 transition hover:bg-amber-600 disabled:opacity-60">
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
})();
</script>
@endsection