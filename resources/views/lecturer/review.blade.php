@extends('layouts.lecturer')

@section('content')
<div class="space-y-6">
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-xs">
        <h2 class="text-2xl font-bold text-slate-800">Review Logbook</h2>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1.2fr_0.8fr]">
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs">
            <div class="border-b border-slate-100 bg-slate-50 px-5 py-4">
                <h4 class="text-base font-bold text-slate-800">Student Information</h4>
                <p class="mt-1 text-sm text-slate-500">Details for the selected logbook submission.</p>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                    <div>
                        <p class="text-xs text-slate-400">Student Name</p>
                        <p class="font-semibold text-slate-900">{{ optional($logbook->student)->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Week Number</p>
                        <p class="font-semibold text-slate-900">{{ $logbook->week_no }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Submission Date</p>
                        <p class="font-semibold text-slate-900">{{ $logbook->activity_date ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Current Status</p>
                        <p class="inline-flex items-center mt-1 rounded-full px-2.5 py-1 text-xs font-semibold {{ $logbook->status === 'Approved' ? 'bg-emerald-100 text-emerald-700' : ($logbook->status === 'Rejected' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">{{ $logbook->status }}</p>
                    </div>
                </div>

                <div class="mt-6">
                    <h5 class="text-sm font-semibold text-slate-700">Activity Description</h5>
                    <div class="mt-3">
                        <textarea readonly class="w-full rounded-lg border border-slate-200 p-4 text-sm text-slate-700 focus:outline-none min-h-[150px]">{{ $logbook->description }}</textarea>
                    </div>
                </div>

                <div class="mt-6">
                    <h5 class="text-sm font-semibold text-slate-700">Attachment</h5>
                    <div class="mt-3">
                        @if($logbook->attachments->count())
                            @php($att = $logbook->attachments->first())
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 rounded-lg bg-slate-50 flex items-center justify-center text-slate-700">
                                        <i class="fa-solid fa-file-pdf text-lg text-sky-700"></i>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-slate-800">{{ $att->file_name }}</div>
                                        <div class="text-xs text-slate-500">{{ $att->file_path }}</div>
                                    </div>
                                </div>
                                <div>
                                    <a href="/{{ $att->file_path }}" target="_blank" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                                        <i class="fa-solid fa-eye"></i>
                                        View Attachment
                                    </a>
                                </div>
                            </div>
                        @else
                            <p class="text-sm text-slate-500">No attachment available.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-xl border border-emerald-200 bg-white p-4 shadow-xs">
                <h5 class="text-sm font-semibold text-emerald-700">Approve Review</h5>
                <form action="/lecturer/approve/{{ $logbook->logbook_id }}" method="POST" class="mt-3">
                    @csrf
                    <div class="mb-4">
                        <textarea name="comment" placeholder="Enter approval comment (optional)" class="w-full rounded-lg border border-slate-200 p-3 text-sm" rows="5"></textarea>
                    </div>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                        <i class="fa-solid fa-check-circle"></i>
                        Approve
                    </button>
                </form>
            </div>

            <div class="rounded-xl border border-rose-200 bg-white p-4 shadow-xs">
                <h5 class="text-sm font-semibold text-rose-700">Reject Review</h5>
                <form action="/lecturer/reject/{{ $logbook->logbook_id }}" method="POST" class="mt-3">
                    @csrf
                    <div class="mb-4">
                        <textarea name="comment" placeholder="Enter rejection reason" class="w-full rounded-lg border border-slate-200 p-3 text-sm" rows="5"></textarea>
                    </div>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">
                        <i class="fa-solid fa-times-circle"></i>
                        Reject
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection