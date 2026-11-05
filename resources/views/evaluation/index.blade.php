@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

@php
    $card = 'rounded-[22px] border border-[#E3E8F2] bg-white shadow-[0_6px_18px_rgba(15,27,77,0.05)]';
    $weightText = rtrim(rtrim(number_format($weight, 2), '0'), '.');
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
@endphp

<div class="space-y-5" style="font-family:'Plus Jakarta Sans',ui-sans-serif,system-ui,sans-serif">
    <section class="px-6 py-5 {{ $card }}">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold">{{ $title }}</h1>
                <p class="mt-1 text-[13px] text-[#5B6478]">Marks given by the company supervisor. This page is read only.</p>
            </div>
            <div class="flex gap-3 text-center">
                <div class="rounded-2xl bg-[#F7F9FD] px-4 py-2"><p class="text-xl font-extrabold">{{ $total }}</p><p class="text-[11px] font-semibold text-[#5B6478]">Students</p></div>
                <div class="rounded-2xl bg-[#D1FAE5] px-4 py-2"><p class="text-xl font-extrabold text-[#065F46]">{{ $evaluatedCount }}</p><p class="text-[11px] font-semibold text-[#065F46]">Evaluated</p></div>
                <div class="rounded-2xl bg-[#FEF3C7] px-4 py-2"><p class="text-xl font-extrabold text-[#92400E]">{{ $total - $evaluatedCount }}</p><p class="text-[11px] font-semibold text-[#92400E]">Waiting</p></div>
            </div>
        </div>

        <form method="GET" action="{{ $base }}" class="mt-4 flex flex-wrap gap-2">
            <input type="text" name="q" value="{{ $search }}" placeholder="Search name or matric number" class="min-h-10 w-full max-w-sm rounded-xl border border-[#CBD3E3] px-3 text-sm focus:border-[#2F5BFF] focus:outline-none">
            <button type="submit" class="min-h-10 rounded-xl bg-[#2F5BFF] px-4 text-sm font-bold text-white transition hover:bg-[#1F45D6]">Search</button>
            @if($search !== '')
                <a href="{{ $base }}" class="flex min-h-10 items-center rounded-xl border border-[#CBD3E3] bg-white px-4 text-sm font-bold text-[#334155] transition hover:bg-[#F3F5FC]">Clear</a>
            @endif
        </form>
    </section>

    <section class="overflow-hidden {{ $card }}">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="bg-[#F7F9FD] text-xs uppercase tracking-wide text-[#5B6478]">
                    <tr>
                        <th class="px-5 py-3 font-bold">Student</th>
                        <th class="px-5 py-3 font-bold">Company supervisor</th>
                        <th class="px-5 py-3 font-bold">Status</th>
                        <th class="px-5 py-3 font-bold">Marks</th>
                        <th class="px-5 py-3 font-bold">Final mark</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EDF0F7]">
                    @forelse($students as $student)
                        @php $evaluation = $evaluations->get($student->student_id); @endphp
                        <tr>
                            <td class="px-5 py-3">
                                <p class="font-bold">{{ $student->name }}</p>
                                <p class="text-xs text-[#5B6478]">{{ $student->matric_no }}</p>
                            </td>
                            <td class="px-5 py-3 text-[#334155]">{{ optional($student->supervisor)->name ?? 'Not assigned' }}</td>
                            <td class="px-5 py-3">
                                @if($evaluation)
                                    <span class="rounded-full bg-[#D1FAE5] px-2.5 py-1 text-xs font-bold text-[#065F46]">Evaluated</span>
                                @else
                                    <span class="rounded-full bg-[#FEF3C7] px-2.5 py-1 text-xs font-bold text-[#92400E]">Waiting</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-[#334155]">{{ $evaluation ? $evaluation->total_marks.' / '.$evaluation->max_marks : '-' }}</td>
                            <td class="px-5 py-3 font-extrabold text-[#1F45D6]">{{ $evaluation ? $fmt($evaluation->final_mark).' / '.$weightText : '-' }}</td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ $base }}/{{ $student->student_id }}" class="rounded-lg bg-[#E8EFFF] px-3 py-1.5 text-xs font-bold text-[#1F45D6] transition hover:bg-[#D3E0FF]">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-sm text-[#5B6478]">No students found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div>{{ $students->links() }}</div>
</div>
@endsection
