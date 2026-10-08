@extends('layouts.app')

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

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

    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs md:p-6">
        <h2 class="text-2xl font-bold text-slate-800">Attendance</h2>
        <p class="mt-1 text-sm text-slate-500">Clock in when you start work and clock out when you finish. Times are in Malaysia time.</p>
    </div>

    <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-3">
        <div>
            @include('student.partials.attendance-card')
        </div>

        <div class="space-y-6 lg:col-span-2">
            <div class="grid grid-cols-3 gap-3">
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
                    <p class="text-[11px] font-medium uppercase tracking-wider text-slate-400">Present</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-600">{{ $monthPresent }}</p>
                    <p class="text-xs text-slate-400">days this month</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
                    <p class="text-[11px] font-medium uppercase tracking-wider text-slate-400">Leave</p>
                    <p class="mt-1 text-2xl font-bold text-amber-600">{{ $monthLeave }}</p>
                    <p class="text-xs text-slate-400">days this month</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
                    <p class="text-[11px] font-medium uppercase tracking-wider text-slate-400">Hours</p>
                    <p class="mt-1 text-2xl font-bold text-slate-800">{{ intdiv($monthMinutes, 60) }}h {{ str_pad((string) ($monthMinutes % 60), 2, '0', STR_PAD_LEFT) }}m</p>
                    <p class="text-xs text-slate-400">this month</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs">
                <div class="border-b border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700">History (latest 60 days)</div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">In</th>
                                <th class="px-4 py-3">Out</th>
                                <th class="px-4 py-3">Worked</th>
                                <th class="px-4 py-3">Note</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($records as $record)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-800">{{ $record->attendance_date->format('D, d M Y') }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $record->status === 'Leave' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">{{ $record->status }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $record->checkInLocal() ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $record->checkOutLocal() ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $record->workedLabel() ?? '-' }}</td>
                                    <td class="min-w-[10rem] px-4 py-3 text-slate-500">{{ $record->note ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">No attendance recorded yet. Clock in to start.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection