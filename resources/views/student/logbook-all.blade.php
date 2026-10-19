@extends('layouts.app')

@section('content')
@php
    $pill = function (string $status) {
        if (in_array($status, ['Approved', 'Validated'], true)) {
            return 'bg-emerald-100 text-emerald-800';
        }
        if (in_array($status, ['Rejected', 'Revision Requested'], true)) {
            return 'bg-orange-100 text-orange-800';
        }
        return 'bg-amber-100 text-amber-800';
    };
    $supervisorAssigned = (bool) optional($student)->supervisor_id;
@endphp

<div class="mx-auto max-w-4xl space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-5 shadow-xs md:p-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Full logbook</h2>
            <p class="mt-1 text-sm text-slate-500">Every entry you have written, newest first.</p>
        </div>
        <a href="/student/dashboard#logbook-week" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Back to weeks</a>
    </div>

    <div class="space-y-3">
        @forelse($logbooks as $logbook)
            @php
                $file = $logbook->attachments->first();
                $moreFiles = max($logbook->attachments->count() - 1, 0);
            @endphp
            <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs md:p-5 {{ $logbook->needsRevision() ? 'border-orange-200 bg-orange-50/40' : '' }}">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700">Week {{ $logbook->week_no }}</span>
                    <span class="text-xs font-semibold text-slate-500">{{ $logbook->activity_date ? \Carbon\Carbon::parse($logbook->activity_date)->format('d M Y') : '-' }}</span>
                    <span class="ml-auto">
                        @if($logbook->isEditableByStudent())
                            <a href="/student/logbook/edit/{{ $logbook->logbook_id }}" class="inline-flex items-center rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ $logbook->needsRevision() ? 'bg-rose-600 text-white hover:bg-rose-700' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">{{ $logbook->needsRevision() ? 'Revise' : 'Edit' }}</a>
                        @else
                            <span class="text-xs text-slate-400"><i class="fa-solid fa-lock"></i> Locked</span>
                        @endif
                    </span>
                </div>

                <p class="mt-2 break-words text-sm leading-relaxed text-slate-600">{{ $logbook->description }}</p>

                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                    @if($file)
                        <a href="{{ $file->url }}" target="_blank" rel="noopener" title="{{ $file->file_name }}" class="inline-flex max-w-full items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-slate-700 transition hover:bg-slate-200">
                            <i class="fa-solid fa-paperclip text-slate-400"></i>
                            <span class="max-w-[12rem] truncate sm:max-w-[18rem]">{{ $file->file_name }}</span>
                        </a>
                        @if($moreFiles > 0)
                            <span class="text-slate-400">+{{ $moreFiles }} more</span>
                        @endif
                    @else
                        <span class="rounded-full bg-slate-50 px-2.5 py-1 text-slate-400">No file</span>
                    @endif
                    <span class="rounded-full px-2.5 py-1 font-semibold {{ $pill($logbook->status ?? 'Pending') }}">Lecturer: {{ $logbook->status ?? 'Pending' }}</span>
                    @if($supervisorAssigned)
                        <span class="rounded-full px-2.5 py-1 font-semibold {{ $pill($logbook->supervisor_status ?? 'Pending') }}">Supervisor: {{ $logbook->supervisor_status ?? 'Pending' }}</span>
                    @endif
                </div>
            </article>
        @empty
            <div class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">No entries yet.</div>
        @endforelse
    </div>

    <div>{{ $logbooks->links() }}</div>
</div>
@endsection