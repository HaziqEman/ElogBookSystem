@php
    $hasIn = $attendance && $attendance->check_in;
    $hasOut = $attendance && $attendance->check_out;
    $onLeave = $attendance && $attendance->status === 'Leave';
@endphp
<div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h3 class="flex items-center gap-2 text-base font-bold text-slate-800">
                <i class="fa-solid fa-clock text-blue-600"></i> Today's attendance
            </h3>
            <p class="mt-1 text-xs text-slate-500">{{ $today->format('l, d M Y') }}</p>
        </div>
        @if($hasOut)
            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Completed</span>
        @elseif($hasIn)
            <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">Clocked in</span>
        @elseif($onLeave)
            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">On leave</span>
        @else
            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">Not clocked in</span>
        @endif
    </div>

    <div class="mt-4">
        @if($hasOut)
            <div class="grid grid-cols-3 gap-2 text-center">
                <div class="rounded-lg bg-slate-50 p-2">
                    <p class="text-[10px] uppercase tracking-wider text-slate-400">In</p>
                    <p class="text-sm font-bold text-slate-800">{{ $attendance->checkInLocal() }}</p>
                </div>
                <div class="rounded-lg bg-slate-50 p-2">
                    <p class="text-[10px] uppercase tracking-wider text-slate-400">Out</p>
                    <p class="text-sm font-bold text-slate-800">{{ $attendance->checkOutLocal() }}</p>
                </div>
                <div class="rounded-lg bg-slate-50 p-2">
                    <p class="text-[10px] uppercase tracking-wider text-slate-400">Worked</p>
                    <p class="text-sm font-bold text-slate-800">{{ $attendance->workedLabel() }}</p>
                </div>
            </div>
        @elseif($hasIn)
            <p class="mb-3 text-sm text-slate-600">You clocked in at <span class="font-semibold text-slate-800">{{ $attendance->checkInLocal() }}</span>.</p>
            <form method="POST" action="/student/attendance/clock-out" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                @csrf
                <button type="submit" class="w-full rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-700 disabled:opacity-60">
                    <i class="fa-solid fa-right-from-bracket"></i> Clock out
                </button>
            </form>
        @else
            @if($onLeave)
                <p class="mb-3 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">Marked as leave: {{ $attendance->note }}</p>
            @endif
            <form method="POST" action="/student/attendance/clock-in" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                @csrf
                <button type="submit" class="w-full rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-60">
                    <i class="fa-solid fa-right-to-bracket"></i> {{ $onLeave ? 'Clock in anyway' : 'Clock in' }}
                </button>
            </form>

            <details class="mt-3">
                <summary class="cursor-pointer text-xs font-semibold text-slate-500 hover:text-slate-700">Mark a day as leave</summary>
                <form method="POST" action="/student/attendance/leave" class="mt-2 space-y-2">
                    @csrf
                    <input type="date" name="leave_date" value="{{ $today->toDateString() }}" required class="w-full rounded-lg border border-slate-200 p-2 text-sm focus:border-blue-500 focus:outline-none">
                    <input type="text" name="note" maxlength="255" required placeholder="Reason, e.g. medical leave" class="w-full rounded-lg border border-slate-200 p-2 text-sm focus:border-blue-500 focus:outline-none">
                    <button type="submit" class="w-full rounded-lg bg-amber-500 px-3 py-2 text-sm font-semibold text-slate-900 transition hover:bg-amber-600">Save leave</button>
                </form>
            </details>
        @endif
    </div>

    <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3 text-xs text-slate-500">
        <span>This month: <span class="font-semibold text-slate-700">{{ $monthPresent }}</span> present, <span class="font-semibold text-slate-700">{{ $monthLeave }}</span> leave</span>
        <a href="/student/attendance" class="font-semibold text-blue-600 hover:underline">History</a>
    </div>
</div>