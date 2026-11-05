@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

@php
    $card = 'rounded-[22px] border border-[#E3E8F2] bg-white shadow-[0_6px_18px_rgba(15,27,77,0.05)]';
    $weightText = rtrim(rtrim(number_format($weight, 2), '0'), '.');
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
    $scores = $evaluation ? ($evaluation->scores ?? []) : [];
@endphp

<div class="mx-auto max-w-4xl space-y-5" style="font-family:'Plus Jakarta Sans',ui-sans-serif,system-ui,sans-serif">
    <a href="{{ $base }}" class="inline-flex items-center gap-2 text-sm font-bold text-[#1F45D6] hover:underline"><i class="fa-solid fa-arrow-left"></i> Back to the list</a>

    <section class="px-6 py-5 {{ $card }}">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-extrabold">{{ $rubric['title'] ?? 'Supervisor Evaluation' }}</h1>
                <p class="mt-1 text-[13px] text-[#5B6478]">{{ $student->name }} &middot; {{ $student->matric_no }} &middot; {{ $student->course }}</p>
                <p class="text-[13px] text-[#5B6478]">Company supervisor: <span class="font-semibold text-[#334155]">{{ optional($supervisor)->name ?? 'Not assigned' }}</span></p>
            </div>
            @if($evaluation)
                <div class="rounded-2xl bg-[#D1FAE5] px-5 py-3 text-right text-[#065F46]">
                    <p class="text-xs font-bold">Final mark</p>
                    <p class="text-2xl font-extrabold">{{ $fmt($evaluation->final_mark) }} / {{ $weightText }}</p>
                    <p class="text-xs font-semibold">{{ $evaluation->total_marks }} / {{ $evaluation->max_marks }} marks</p>
                </div>
            @endif
        </div>
        @if($evaluation)
            <p class="mt-3 text-xs text-[#5B6478]">Submitted {{ optional($evaluation->submitted_at)->timezone(\App\Models\Attendance::TIMEZONE)->format('d M Y, H:i') }}</p>
        @endif
    </section>

    @if(! $evaluation)
        <div class="rounded-[22px] border border-dashed border-[#C3CCEA] bg-[#F5F7FF] px-6 py-12 text-center text-sm text-[#5B6478]">The company supervisor has not submitted an evaluation for this student yet.</div>
    @else
        @foreach($rubric['sections'] as $section)
            <section class="{{ $card }}">
                <h2 class="border-b border-[#EDF0F7] px-6 py-4 text-base font-extrabold">{{ $section['title'] }}</h2>
                <div class="divide-y divide-[#EDF0F7]">
                    @foreach($section['criteria'] as $key => $criterion)
                        @php
                            $max = (int) ($criterion['max'] ?? 5);
                            $score = $scores[$key] ?? null;
                            $percent = ($score !== null && $max > 0) ? min(100, round($score / $max * 100)) : 0;
                        @endphp
                        <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-3.5">
                            <p class="min-w-[200px] flex-1 text-sm font-bold">{{ $criterion['label'] }}</p>
                            <div class="flex items-center gap-3">
                                <div class="h-2 w-28 overflow-hidden rounded-full bg-[#EEF1F7]"><div class="h-full rounded-full bg-[#2F5BFF]" style="width: {{ $percent }}%"></div></div>
                                <span class="w-14 text-right text-sm font-extrabold">{{ $score !== null ? $score.' / '.$max : '-' }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach

        <section class="px-6 py-5 {{ $card }}">
            <h2 class="text-sm font-extrabold">Overall comments</h2>
            <p class="mt-2 whitespace-pre-line break-words text-sm text-[#334155]">{{ $evaluation->comments ?: 'The supervisor did not write any comments.' }}</p>
        </section>
    @endif
</div>
@endsection
