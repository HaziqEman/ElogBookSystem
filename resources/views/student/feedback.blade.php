@extends('layouts.app')

@section('content')
@php
    $fmt = function ($value, $format = 'd M Y') {
        if (! $value) {
            return null;
        }
        try {
            return \Carbon\Carbon::parse($value)->format($format);
        } catch (\Throwable $e) {
            return null;
        }
    };

    // Raw status shown to the student, depending on who wrote the feedback.
    $rawStatus = function ($feedback) {
        $logbook = $feedback->logbook;
        return ! empty($feedback->supervisor_id)
            ? (optional($logbook)->supervisor_status ?? 'Pending')
            : (optional($logbook)->status ?? 'Pending');
    };

    // Group into the three filter buckets.
    $bucket = function ($raw) {
        if (in_array($raw, ['Approved', 'Validated'], true)) {
            return 'Approved';
        }
        if (in_array($raw, ['Rejected', 'Revision Requested'], true)) {
            return 'Rejected';
        }
        return 'Pending';
    };

    $items = $feedbacks->sortByDesc('feedback_id')->values();
    $total = $items->count();
    $approved = $items->filter(fn ($f) => $bucket($rawStatus($f)) === 'Approved')->count();
    $needsAction = $items->filter(fn ($f) => $bucket($rawStatus($f)) === 'Rejected')->count();
    $pending = $total - $approved - $needsAction;
@endphp

<div class="space-y-6">
    <div class="rounded-3xl bg-gradient-to-r from-indigo-700 via-blue-700 to-slate-800 p-5 md:p-6 text-white shadow-md">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-blue-100">Supervisor feedback</p>
                <h3 class="mt-2 text-xl md:text-2xl font-bold">Your review updates</h3>
                <p class="mt-1 text-sm text-blue-100">Comments from your lecturer and your company supervisor, in one place.</p>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 md:gap-3">
                <div class="rounded-2xl border border-white/20 bg-white/10 px-4 py-3 backdrop-blur">
                    <p class="text-[10px] uppercase tracking-[0.2em] text-blue-100">Total</p>
                    <p class="mt-1 text-lg font-bold">{{ $total }}</p>
                </div>
                <div class="rounded-2xl border border-white/20 bg-white/10 px-4 py-3 backdrop-blur">
                    <p class="text-[10px] uppercase tracking-[0.2em] text-blue-100">Approved</p>
                    <p class="mt-1 text-lg font-bold">{{ $approved }}</p>
                </div>
                <div class="rounded-2xl border border-white/20 bg-white/10 px-4 py-3 backdrop-blur">
                    <p class="text-[10px] uppercase tracking-[0.2em] text-blue-100">Needs action</p>
                    <p class="mt-1 text-lg font-bold">{{ $needsAction }}</p>
                </div>
                <div class="rounded-2xl border border-white/20 bg-white/10 px-4 py-3 backdrop-blur">
                    <p class="text-[10px] uppercase tracking-[0.2em] text-blue-100">Pending</p>
                    <p class="mt-1 text-lg font-bold">{{ $pending }}</p>
                </div>
            </div>
        </div>
    </div>

    @if($items->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center shadow-xs">
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-blue-50 text-blue-600">
                <i class="fa-solid fa-comments text-xl"></i>
            </div>
            <h4 class="text-lg font-semibold text-slate-800">No feedback yet</h4>
            <p class="mt-1 text-sm text-slate-500">Feedback will appear here once your lecturer or company supervisor reviews your logbook.</p>
            <a href="/student/dashboard" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
                <i class="fa-solid fa-circle-plus"></i> Go to my logbook
            </a>
        </div>
    @else
        <div id="feedback-tabs" class="flex flex-wrap gap-2">
            <button type="button" data-filter="all" class="feedback-tab rounded-full border px-4 py-1.5 text-xs font-semibold transition">All ({{ $total }})</button>
            <button type="button" data-filter="Rejected" class="feedback-tab rounded-full border px-4 py-1.5 text-xs font-semibold transition">Needs action ({{ $needsAction }})</button>
            <button type="button" data-filter="Approved" class="feedback-tab rounded-full border px-4 py-1.5 text-xs font-semibold transition">Approved ({{ $approved }})</button>
            <button type="button" data-filter="Pending" class="feedback-tab rounded-full border px-4 py-1.5 text-xs font-semibold transition">Pending ({{ $pending }})</button>
        </div>

        <div id="feedback-list" class="space-y-4">
            @foreach($items as $feedback)
                @php
                    $logbook = $feedback->logbook;
                    $fromSupervisor = ! empty($feedback->supervisor_id);
                    $raw = $rawStatus($feedback);
                    $key = $bucket($raw);
                    $badge = $key === 'Approved'
                        ? 'bg-emerald-100 text-emerald-700'
                        : ($key === 'Rejected' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700');
                    $label = $key === 'Rejected' ? $raw.' - needs action' : $raw;
                    $reviewerName = $fromSupervisor
                        ? (optional($feedback->supervisor)->name ?? 'Company Supervisor')
                        : (optional($feedback->lecturer)->name ?? 'Lecturer');
                    $reviewerRole = $fromSupervisor ? 'Company Supervisor' : 'Faculty Evaluator';
                @endphp
                <div class="feedback-card rounded-2xl border border-slate-200 bg-white p-4 md:p-5 shadow-xs" data-status="{{ $key }}">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold uppercase tracking-wide text-slate-600">Week {{ optional($logbook)->week_no ?? '-' }}</span>
                        <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">{{ $reviewerRole }}</span>
                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $badge }}">{{ $label }}</span>
                        @if($fmt(optional($logbook)->activity_date))
                            <span class="text-xs text-slate-400">Entry date: {{ $fmt(optional($logbook)->activity_date) }}</span>
                        @endif
                    </div>

                    @if(optional($logbook)->description)
                        <div class="mt-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Your entry</p>
                            <p class="mt-1 text-sm leading-6 text-slate-600 break-words">{{ \Illuminate\Support\Str::limit($logbook->description, 280) }}</p>
                        </div>
                    @endif

                    <div class="mt-4 rounded-xl p-4 {{ $key === 'Rejected' ? 'bg-rose-50 border border-rose-100' : 'bg-slate-50' }}">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm font-semibold text-slate-700">
                                <i class="fa-solid {{ $fromSupervisor ? 'fa-building-user' : 'fa-user-tie' }} mr-1 text-slate-400"></i>
                                {{ $reviewerName }}
                            </p>
                            <p class="text-xs text-slate-500">Reviewed on {{ $fmt($feedback->feedback_date) ?? $fmt($feedback->created_at) ?? 'recently' }}</p>
                        </div>
                        <p class="mt-2 text-sm leading-6 text-slate-600 break-words">{{ $feedback->comment }}</p>
                    </div>

                    @if($key === 'Rejected')
                        <p class="mt-3 text-xs text-rose-600">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            Please address the points above and submit an updated entry.
                        </p>
                    @endif
                </div>
            @endforeach
        </div>

        <div id="feedback-empty-filter" class="hidden rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-center text-sm text-slate-500">
            No feedback in this category.
        </div>
    @endif
</div>

<script>
(function () {
    var tabs = document.querySelectorAll('.feedback-tab');
    var cards = document.querySelectorAll('.feedback-card');
    var emptyMsg = document.getElementById('feedback-empty-filter');
    if (!tabs.length) return;

    var activeClass = ['bg-blue-600', 'text-white', 'border-blue-600'];
    var idleClass = ['bg-white', 'text-slate-600', 'border-slate-200', 'hover:bg-slate-50'];

    function apply(filter) {
        var visible = 0;
        cards.forEach(function (card) {
            var show = filter === 'all' || card.getAttribute('data-status') === filter;
            card.classList.toggle('hidden', !show);
            if (show) visible += 1;
        });
        if (emptyMsg) emptyMsg.classList.toggle('hidden', visible !== 0);

        tabs.forEach(function (tab) {
            var isActive = tab.getAttribute('data-filter') === filter;
            activeClass.forEach(function (c) { tab.classList.toggle(c, isActive); });
            idleClass.forEach(function (c) { tab.classList.toggle(c, !isActive); });
        });
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            apply(tab.getAttribute('data-filter'));
        });
    });

    apply('all');
})();
</script>
@endsection