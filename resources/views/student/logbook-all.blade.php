@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

@php
    $pill = function (string $status) {
        if (in_array($status, ['Approved', 'Validated'], true)) {
            return 'bg-[#D1FAE5] text-[#065F46]';
        }
        if (in_array($status, ['Rejected', 'Revision Requested', 'Revision needed'], true)) {
            return 'bg-[#FFEDD5] text-[#9A3412]';
        }
        if ($status === 'Waiting for review') {
            return 'bg-[#E0E7FF] text-[#3730A3]';
        }
        if (in_array($status, ['Not sent yet', 'Not assigned'], true)) {
            return 'bg-[#EEF1F7] text-[#5B6478]';
        }

        return 'bg-[#FEF3C7] text-[#7C2D12]';
    };

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
    $card = 'rounded-[22px] border border-[#E3E8F2] bg-white shadow-[0_6px_18px_rgba(15,27,77,0.05)]';
    $fullLogbookPage = true;
    $plannedLogbookIds = collect($weeks)
        ->flatMap(fn ($week) => collect($week['days'])->flatMap(fn ($day) => $day['entries']->pluck('logbook_id')))
        ->flip();
    $unplannedLogbooks = $visibleLogbooks
        ->reject(fn ($entry) => $plannedLogbookIds->has($entry->logbook_id));
@endphp

<div class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6 lg:px-8" style="font-family:'Plus Jakarta Sans',ui-sans-serif,system-ui,sans-serif">    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800" role="status">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
            <p class="font-bold">Please check the following:</p>
            <ul class="mt-1 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @include('student.partials.dashboard-logbook')

    @if(! $hasFilters && $unplannedLogbooks->isNotEmpty())
        <section class="space-y-3 rounded-[22px] border border-[#E3E8F2] bg-white p-6 shadow-[0_6px_18px_rgba(15,27,77,0.05)]">
            <div>
                <h2 class="text-xl font-extrabold text-[#1E293B]">Other logbook entries</h2>
                <p class="mt-1 text-sm text-[#5B6478]">These entries are outside the configured internship-week dates.</p>
            </div>
            @foreach($unplannedLogbooks as $entry)
                <article class="rounded-2xl border border-[#E7ECF6] bg-[#FCFDFF] p-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <time class="text-xs font-bold text-[#5B6478]">{{ $entry->activity_date ? \Carbon\Carbon::parse($entry->activity_date)->format('D, d M Y') : 'Date not set' }}</time>
                        <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $pill($entry->supervisor_status ?? 'Pending') }}">Supervisor: {{ $entry->supervisor_status ?? 'Pending' }}</span>
                    </div>
                    <p class="mt-2 whitespace-pre-line break-words text-sm leading-relaxed text-[#334155]">{{ $entry->description }}</p>
                    @foreach($entry->attachments as $attachment)
                        <a href="{{ $attachment->url }}" target="_blank" rel="noopener" class="mt-2 inline-flex max-w-full items-center gap-2 rounded-lg bg-[#EEF2FB] px-3 py-2 text-xs font-semibold text-[#1F45D6]">
                            <i class="fa-solid fa-paperclip"></i><span class="truncate">{{ $attachment->file_name }}</span>
                        </a>
                    @endforeach
                    @foreach($entry->feedbacks->sortByDesc('feedback_id') as $feedback)
                        <div class="mt-3 rounded-xl bg-[#F7F9FD] p-3">
                            <p class="text-xs font-bold text-[#334155]">{{ $feedback->supervisor_id ? 'Company supervisor' : 'Lecturer' }} feedback</p>
                            <p class="mt-1 whitespace-pre-line break-words text-xs leading-relaxed text-[#475569]">{{ $feedback->comment }}</p>
                        </div>
                    @endforeach
                </article>
            @endforeach
        </section>
    @endif
</div>
@include('student.partials.dashboard-modals')
@endsection