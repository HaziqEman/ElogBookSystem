@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Student Review Management</h2>
                <p class="mt-2 text-slate-500">Manage assigned students and review their latest submissions in a clean admin-style interface.</p>
            </div>
            <div class="flex flex-wrap gap-3 items-center">
                <a href="/lecturer/logbook" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
                    Add Review
                </a>
                <a href="/lecturer/dashboard" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Refresh list
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
    @endif

    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Summary</p>
                <h3 class="mt-1 text-2xl font-bold text-slate-800">{{ $students ?? 0 }} students assigned</h3>
            </div>
            <div class="grid grid-cols-3 gap-3 sm:w-auto">
                <div class="rounded-xl bg-slate-50 p-4 text-center">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Pending</p>
                    <p class="mt-1 text-lg font-bold text-amber-600">{{ $pending ?? 0 }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4 text-center">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Reviewed</p>
                    <p class="mt-1 text-lg font-bold text-emerald-600">{{ $reviewed ?? 0 }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4 text-center">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Open</p>
                    <p class="mt-1 text-lg font-bold text-slate-700">{{ max(($students ?? 0) - ($reviewed ?? 0), 0) }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1.2fr_0.8fr]">
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs">
            <div class="border-b border-slate-100 bg-slate-50 px-5 py-4">
                <h4 class="text-base font-bold text-slate-800">Assigned Student Progress</h4>
                <p class="mt-1 text-sm text-slate-500">All submitted logbook entries are shown here, grouped by student.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Student</th>
                            <th class="px-4 py-3">Submission</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody class="font-medium">
                        @php($previousStudentId = null)
                        @forelse($studentSummaries ?? [] as $summary)
                            @php($student = $summary['student'])
                            @php($latestLogbook = $summary['latest_logbook'])
                            @php($status = optional($latestLogbook)->status ?? 'Pending')

                            @if($previousStudentId !== $student->student_id)
                                <tr class="bg-slate-50">
                                    <td colspan="5" class="px-4 py-3 text-sm font-semibold text-slate-700">
                                        {{ $student->name }} · {{ $student->matric_no ?? 'N/A' }}
                                    </td>
                                </tr>
                                @php($previousStudentId = $student->student_id)
                            @endif

                            <tr class="divide-y divide-slate-100">
                                <td class="px-4 py-4 text-slate-500">{{ $student->course ? 'Course: ' . $student->course : 'No course' }}</td>
                                <td class="px-4 py-4">
                                    <p class="font-semibold text-slate-800">{{ optional($latestLogbook)->title ?? 'No submission yet' }}</p>
                                    <p class="text-xs text-slate-500">{{ optional($latestLogbook)->description ? \Illuminate\Support\Str::limit(optional($latestLogbook)->description, 80) : 'No logbook entry submitted yet.' }}</p>
                                </td>
                                <td class="px-4 py-4 text-slate-600">
                                    {{ $summary['submission_date'] ? \Carbon\Carbon::parse($summary['submission_date'])->format('d M Y') : '—' }}
                                </td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $status === 'Approved' ? 'bg-emerald-100 text-emerald-700' : ($status === 'Rejected' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">
                                        {{ $status }}
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    @if($latestLogbook)
                                        <a href="/lecturer/logbook/{{ $latestLogbook->logbook_id }}" class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-blue-700">
                                            Review
                                        </a>
                                    @else
                                        <span class="text-xs font-semibold text-slate-400">No submission</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-4 text-slate-500">No assigned students are available yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
                <h4 class="text-base font-bold text-slate-800">Current status</h4>
                <div class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2">
                        <span class="text-slate-500">Pending</span>
                        <span class="font-semibold text-amber-600">{{ $pending ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2">
                        <span class="text-slate-500">Approved</span>
                        <span class="font-semibold text-emerald-600">{{ $reviewed ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2">
                        <span class="text-slate-500">Assigned</span>
                        <span class="font-semibold text-slate-700">{{ $students ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection