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

    <div class="flex flex-wrap items-center justify-between gap-4 rounded-3xl border border-[#E3E8F2] bg-white px-5 py-5 shadow-[0_6px_18px_rgba(15,27,77,0.05)] md:px-7">
        <div class="flex min-w-0 items-center gap-4">
            <span class="flex h-12 w-12 flex-none items-center justify-center rounded-2xl bg-[#E8EFFF] text-xl text-[#1F45D6]">
                <i class="fa-solid fa-user-clock" aria-hidden="true"></i>
            </span>
            <div class="min-w-0">
                <h1 class="text-2xl font-extrabold tracking-tight text-[#1E293B]">Attendance</h1>
                <p class="mt-1 text-sm text-[#5B6478]">Clock in when you start work and clock out when you finish. Times are in Malaysia time.</p>
            </div>
        </div>
        <span class="inline-flex items-center gap-2 rounded-full bg-[#F7F9FD] px-3 py-2 text-xs font-bold text-[#475569]">
            <i class="fa-regular fa-calendar text-[#1F45D6]" aria-hidden="true"></i>
            {{ $today->format('l, d M Y') }}
        </span>
    </div>

    <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-[minmax(300px,0.88fr)_minmax(0,1.62fr)]">
        <div>
            @include('student.partials.attendance-card')
        </div>

        <div class="space-y-6 xl:col-span-1">
            <div id="attendance-stats" data-live class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="flex items-center gap-3 rounded-2xl border border-[#D1FAE5] bg-white p-4 shadow-[0_4px_12px_rgba(15,27,77,0.04)]">
                    <span class="flex h-10 w-10 flex-none items-center justify-center rounded-xl bg-[#D1FAE5] text-[#047857]"><i class="fa-solid fa-check" aria-hidden="true"></i></span>
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-[#64748B]">Present</p>
                        <p class="mt-0.5 text-2xl font-extrabold leading-none text-[#047857]">{{ $monthPresent }}</p>
                        <p class="mt-1 text-xs text-[#94A3B8]">days this month</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-2xl border border-[#FDE68A] bg-white p-4 shadow-[0_4px_12px_rgba(15,27,77,0.04)]">
                    <span class="flex h-10 w-10 flex-none items-center justify-center rounded-xl bg-[#FEF3C7] text-[#B45309]"><i class="fa-solid fa-mug-hot" aria-hidden="true"></i></span>
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-[#64748B]">Leave</p>
                        <p class="mt-0.5 text-2xl font-extrabold leading-none text-[#B45309]">{{ $monthLeave }}</p>
                        <p class="mt-1 text-xs text-[#94A3B8]">days this month</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-2xl border border-[#D3E0FF] bg-white p-4 shadow-[0_4px_12px_rgba(15,27,77,0.04)]">
                    <span class="flex h-10 w-10 flex-none items-center justify-center rounded-xl bg-[#E8EFFF] text-[#1F45D6]"><i class="fa-regular fa-clock" aria-hidden="true"></i></span>
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-[#64748B]">Hours</p>
                        <p class="mt-0.5 text-2xl font-extrabold leading-none text-[#1E293B]">{{ intdiv($monthMinutes, 60) }}h {{ str_pad((string) ($monthMinutes % 60), 2, '0', STR_PAD_LEFT) }}m</p>
                        <p class="mt-1 text-xs text-[#94A3B8]">this month</p>
                    </div>
                </div>
            </div>

            <div id="attendance-history" data-live class="overflow-hidden rounded-2xl border border-[#E3E8F2] bg-white shadow-[0_6px_18px_rgba(15,27,77,0.05)]">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#EDF0F7] px-5 py-4">
                    <div>
                        <h2 class="text-base font-extrabold text-[#1E293B]">Attendance history</h2>
                        <p class="mt-0.5 text-xs text-[#64748B]">Your latest 60 recorded days</p>
                    </div>
                    <span class="rounded-full bg-[#F7F9FD] px-3 py-1 text-xs font-semibold text-[#64748B]">{{ $records->count() }} records</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-[700px] w-full text-left text-sm">
                        <thead class="bg-[#F7F9FD] text-[11px] font-bold uppercase tracking-wider text-[#64748B]">
                            <tr>
                                <th class="whitespace-nowrap px-5 py-3.5">Date</th>
                                <th class="px-4 py-3.5">Status</th>
                                <th class="px-4 py-3.5">In</th>
                                <th class="px-4 py-3.5">Out</th>
                                <th class="px-4 py-3.5">Worked</th>
                                <th class="min-w-40 px-4 py-3.5">Note</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#EDF0F7]">
                            @forelse($records as $record)
                                <tr class="transition hover:bg-[#FCFDFF]">
                                    <td class="whitespace-nowrap px-5 py-3.5 font-bold text-[#334155]">{{ $record->attendance_date->format('D, d M Y') }}</td>
                                    <td class="px-4 py-3.5">
                                        <span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-bold {{ $record->status === 'Leave' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">{{ $record->status === 'Leave' ? $record->dayOffLabel() : $record->status }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3.5 text-[#475569]">{{ $record->checkInLocal() ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3.5 text-[#475569]">{{ $record->checkOutLocal() ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3.5 font-semibold text-[#475569]">{{ $record->workedLabel() ?? '-' }}</td>
                                    <td class="max-w-64 px-4 py-3.5 text-[#64748B]"><span class="block truncate" title="{{ $record->note ?: '' }}">{{ $record->note ?: '-' }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-5 py-10 text-center text-sm text-[#64748B]"><i class="fa-regular fa-calendar-xmark mb-2 block text-xl text-[#94A3B8]"></i>No attendance recorded yet. Clock in to start.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection