@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="rounded-2xl bg-gradient-to-r from-blue-700 to-indigo-800 p-5 md:p-6 text-white shadow-md">
        <h3 class="text-xl font-bold">Welcome, {{ $supervisor->name }}</h3>
        <p class="mt-1 text-xs text-blue-100">
            {{ $supervisor->company_name }}@if($supervisor->position) - {{ $supervisor->position }}@endif.
            Validate the activities your students record in their logbooks.
        </p>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Assigned students</p>
            <p class="mt-1 text-2xl font-bold">{{ $students->count() }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Waiting for you</p>
            <p class="mt-1 text-2xl font-bold text-amber-600">{{ $pending }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Validated</p>
            <p class="mt-1 text-2xl font-bold text-emerald-600">{{ $validated }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Revision requested</p>
            <p class="mt-1 text-2xl font-bold text-rose-600">{{ $revision }}</p>
        </div>
    </div>

    <div>
        @if($pending > 0)
            <a href="/supervisor/logbook" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
                <i class="fa-solid fa-clipboard-check"></i> Review next pending entry
            </a>
        @else
            <p class="text-sm text-slate-500">No entries are waiting for your validation.</p>
        @endif
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs">
        <div class="border-b border-slate-100 bg-slate-50 px-5 py-4 font-bold text-sm text-slate-700">Assigned students</div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Matric no</th>
                        <th class="px-4 py-3">Course</th>
                        <th class="px-4 py-3">Entries</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($students as $student)
                        <tr>
                            <td class="px-4 py-3 font-semibold text-slate-800">{{ $student->name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $student->matric_no }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $student->course }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $student->logbooks_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">No students are assigned to you yet. Please ask the administrator.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs">
        <div class="border-b border-slate-100 bg-slate-50 px-5 py-4 font-bold text-sm text-slate-700">Latest logbook entries</div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Student</th>
                        <th class="px-4 py-3">Week</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Entry</th>
                        <th class="px-4 py-3">Your status</th>
                        <th class="px-4 py-3">Lecturer</th>
                        <th class="px-4 py-3">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recent as $logbook)
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
                        <tr>
                            <td class="px-4 py-3 font-semibold text-slate-800 whitespace-nowrap">{{ optional($logbook->student)->name }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">Week {{ $logbook->week_no }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ $logbook->activity_date ? \Carbon\Carbon::parse($logbook->activity_date)->format('d M Y') : '-' }}</td>
                            <td class="px-4 py-3 text-slate-600 min-w-[14rem]">{{ \Illuminate\Support\Str::limit($logbook->description, 90) }}</td>
                            <td class="px-4 py-3"><span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold {{ $mineBadge }}">{{ $mine }}</span></td>
                            <td class="px-4 py-3"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $lecBadge }}">{{ $lec }}</span></td>
                            <td class="px-4 py-3">
                                <a href="/supervisor/logbook/{{ $logbook->logbook_id }}" class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-blue-700">Review</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-6 text-center text-slate-500">No logbook entries from your students yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection