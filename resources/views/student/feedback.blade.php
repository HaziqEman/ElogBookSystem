@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="rounded-3xl bg-gradient-to-r from-indigo-700 via-blue-700 to-slate-800 p-6 text-white shadow-md">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-blue-100">Supervisor feedback</p>
                <h3 class="mt-2 text-2xl font-bold">Your review updates</h3>
                <p class="mt-1 text-sm text-blue-100">Track lecturer comments and the status of each logbook submission in one place.</p>
            </div>
            <div class="rounded-2xl border border-white/20 bg-white/10 px-4 py-3 backdrop-blur">
                <p class="text-xs uppercase tracking-[0.2em] text-blue-100">Total feedback</p>
                <p class="mt-1 text-sm font-semibold">{{ $feedbacks->count() }} item{{ $feedbacks->count() === 1 ? '' : 's' }}</p>
            </div>
        </div>
    </div>

    @if($feedbacks->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center shadow-xs">
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-blue-50 text-blue-600">
                <i class="fa-solid fa-comments text-xl"></i>
            </div>
            <h4 class="text-lg font-semibold text-slate-800">No feedback yet</h4>
            <p class="mt-1 text-sm text-slate-500">Your supervisor feedback will appear here once a lecturer reviews your logbook.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($feedbacks as $feedback)
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold uppercase tracking-wide text-slate-600">Week {{ $feedback->logbook->week_no }}</span>
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $feedback->logbook->status === 'Approved' ? 'bg-emerald-100 text-emerald-700' : ($feedback->logbook->status === 'Rejected' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">
                                    {{ $feedback->logbook->status }}
                                </span>
                            </div>
                            <h4 class="mt-3 text-lg font-semibold text-slate-800">{{ $feedback->lecturer->name ?? 'Supervisor' }}</h4>
                            <p class="mt-1 text-sm text-slate-500">Reviewed on {{ optional($feedback->feedback_date)->format('d M Y') ?? 'recently' }}</p>
                        </div>

                        <div class="max-w-2xl rounded-xl bg-slate-50 p-4">
                            <p class="text-sm font-semibold text-slate-700">Feedback</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $feedback->comment }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection