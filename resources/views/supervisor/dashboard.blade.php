@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="rounded-2xl bg-gradient-to-r from-blue-700 to-indigo-800 p-5 md:p-6 text-white shadow-md">
        <h3 class="text-xl font-bold">Welcome, {{ $supervisor->name }}</h3>
        <p class="mt-1 text-xs text-blue-100">
            {{ $supervisor->company_name }}@if($supervisor->position) - {{ $supervisor->position }}@endif.
            Here are the students assigned to you and their latest logbook entries.
        </p>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Assigned students</p>
            <p class="mt-1 text-2xl font-bold">{{ $students->count() }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Total logbook entries</p>
            <p class="mt-1 text-2xl font-bold">{{ $totalEntries }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Validation</p>
            <p class="mt-1 text-sm text-slate-500">Coming in the next update.</p>
        </div>
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
                        <th class="px-4 py-3">Lecturer status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recent as $logbook)
                        @php
                            $status = $logbook->status ?? 'Pending';
                            $badge = $status === 'Approved'
                                ? 'bg-emerald-100 text-emerald-700'
                                : ($status === 'Rejected' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700');
                        @endphp
                        <tr>
                            <td class="px-4 py-3 font-semibold text-slate-800 whitespace-nowrap">{{ optional($logbook->student)->name }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">Week {{ $logbook->week_no }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ $logbook->activity_date ? \Carbon\Carbon::parse($logbook->activity_date)->format('d M Y') : '-' }}</td>
                            <td class="px-4 py-3 text-slate-600 min-w-[14rem]">{{ \Illuminate\Support\Str::limit($logbook->description, 90) }}</td>
                            <td class="px-4 py-3"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $badge }}">{{ $status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">No logbook entries from your students yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection