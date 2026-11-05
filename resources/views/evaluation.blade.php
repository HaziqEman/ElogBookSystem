@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

@php
    $card = 'rounded-[22px] border border-[#E3E8F2] bg-white shadow-[0_6px_18px_rgba(15,27,77,0.05)]';
    $initials = \Illuminate\Support\Str::of($student->name)->explode(' ')->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('');
    $saved = $evaluation ? ($evaluation->scores ?? []) : [];
    $weight = (float) ($rubric['weight'] ?? 100);
    $weightText = rtrim(rtrim(number_format($weight, 2), '0'), '.');
    $maxTotal = array_sum(array_column($criteria, 'max'));
@endphp

<div class="mx-auto max-w-4xl space-y-5" style="font-family:'Plus Jakarta Sans',ui-sans-serif,system-ui,sans-serif">
    <a href="/supervisor/students/{{ $student->student_id }}" class="inline-flex items-center gap-2 text-sm font-bold text-[#1F45D6] hover:underline"><i class="fa-solid fa-arrow-left"></i> Back to {{ $student->name }}</a>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-2xl border border-[#FBCFCB] bg-[#FFF1EF] px-4 py-3 text-sm text-[#9F1239]">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-2xl border border-[#FBCFCB] bg-[#FFF1EF] px-4 py-3 text-sm text-[#9F1239]">
            <p class="mb-1 font-bold">Please fix these first:</p>
            <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Student --}}
    <section class="px-6 py-5 {{ $card }}">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <span class="flex h-12 w-12 flex-none items-center justify-center rounded-full bg-[#2F5BFF] text-sm font-extrabold text-white">{{ $initials }}</span>
                <div>
                    <h1 class="text-xl font-extrabold leading-tight">{{ $rubric['title'] ?? 'Supervisor Evaluation' }}</h1>
                    <p class="text-[13px] text-[#5B6478]">{{ $student->name }} &middot; {{ $student->matric_no }} &middot; {{ $student->course }}</p>
                </div>
            </div>
            @if($evaluation)
                <div class="rounded-2xl bg-[#D1FAE5] px-4 py-2 text-right text-[#065F46]">
                    <p class="text-xs font-bold">Submitted {{ optional($evaluation->submitted_at)->timezone(\App\Models\Attendance::TIMEZONE)->format('d M Y, H:i') }}</p>
                    <p class="text-lg font-extrabold">{{ rtrim(rtrim(number_format($evaluation->final_mark, 2), '0'), '.') }} / {{ $weightText }}</p>
                </div>
            @else
                <span class="rounded-full bg-[#FEF3C7] px-3 py-1 text-xs font-bold text-[#92400E]">Not evaluated yet</span>
            @endif
        </div>

        <div class="mt-4 grid grid-cols-3 gap-3 text-center">
            <div class="rounded-2xl bg-[#F7F9FD] p-3"><p class="text-2xl font-extrabold">{{ $stats['entries'] }}</p><p class="text-[11px] font-semibold text-[#5B6478]">Logbook entries</p></div>
            <div class="rounded-2xl bg-[#F7F9FD] p-3"><p class="text-2xl font-extrabold text-[#065F46]">{{ $stats['validated'] }}</p><p class="text-[11px] font-semibold text-[#5B6478]">Validated by you</p></div>
            <div class="rounded-2xl bg-[#F7F9FD] p-3"><p class="text-2xl font-extrabold text-[#92400E]">{{ $stats['pending'] }}</p><p class="text-[11px] font-semibold text-[#5B6478]">Still waiting</p></div>
        </div>
        @if($stats['pending'] > 0)
            <p class="mt-3 rounded-xl bg-[#FFF7ED] px-3 py-2 text-xs text-[#9A3412]">Some entries are still waiting for your review. You can still evaluate now, but it is best to finish reviewing the logbook first.</p>
        @endif
    </section>

    @if($locked)
        <div class="rounded-2xl border border-[#C3CCEA] bg-[#F5F7FF] px-4 py-3 text-sm text-[#334155]"><i class="fa-solid fa-lock mr-1"></i> This evaluation is submitted and locked. The marks below cannot be changed.</div>
    @endif

    <form id="eval-form" method="POST" action="/supervisor/students/{{ $student->student_id }}/evaluation" data-max="{{ $maxTotal }}" data-weight="{{ $weight }}" data-count="{{ count($criteria) }}" class="space-y-5">
        @csrf

        @if(! empty($rubric['scale_hint']))
            <p class="rounded-2xl bg-[#E8EFFF] px-4 py-3 text-sm font-semibold text-[#1F45D6]"><i class="fa-solid fa-circle-info mr-1"></i> {{ $rubric['scale_hint'] }}</p>
        @endif

        @foreach($rubric['sections'] as $section)
            <section class="{{ $card }}">
                <h2 class="border-b border-[#EDF0F7] px-6 py-4 text-base font-extrabold">{{ $section['title'] }}</h2>
                <div class="divide-y divide-[#EDF0F7]">
                    @foreach($section['criteria'] as $key => $criterion)
                        @php
                            $max = (int) ($criterion['max'] ?? 5);
                            $current = (int) old('scores.'.$key, $saved[$key] ?? 0);
                        @endphp
                        <div data-criterion class="flex flex-wrap items-center justify-between gap-3 px-6 py-4">
                            <div class="min-w-[200px] flex-1">
                                <p class="text-sm font-bold">{{ $criterion['label'] }} <span class="font-semibold text-[#94A3B8]">(max {{ $max }})</span></p>
                                @if(! empty($criterion['help']))
                                    <p class="mt-0.5 text-xs text-[#5B6478]">{{ $criterion['help'] }}</p>
                                @endif
                            </div>
                            <div class="flex flex-wrap gap-1.5">
                                @for($n = 1; $n <= $max; $n++)
                                    <label class="{{ $locked ? 'cursor-not-allowed' : 'cursor-pointer' }}">
                                        <input type="radio" name="scores[{{ $key }}]" value="{{ $n }}" class="peer sr-only" data-score {{ $current === $n ? 'checked' : '' }} {{ $locked ? 'disabled' : '' }}>
                                        <span class="flex h-10 min-w-10 items-center justify-center rounded-xl border border-[#CBD3E3] bg-white px-2 text-sm font-bold text-[#334155] transition hover:bg-[#F3F5FC] peer-checked:border-[#2F5BFF] peer-checked:bg-[#2F5BFF] peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-[#2F5BFF]/40">{{ $n }}</span>
                                    </label>
                                @endfor
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach

        <section class="px-6 py-5 {{ $card }}">
            <label for="comments" class="mb-1 block text-sm font-extrabold">Overall comments <span class="font-semibold text-[#94A3B8]">(optional)</span></label>
            <textarea id="comments" name="comments" rows="4" maxlength="3000" {{ $locked ? 'disabled' : '' }} placeholder="Strengths, areas to improve, and anything the faculty should know..." class="w-full rounded-xl border border-[#CBD3E3] p-2.5 text-sm focus:border-[#2F5BFF] focus:outline-none">{{ old('comments', optional($evaluation)->comments) }}</textarea>
        </section>

        <section class="sticky bottom-3 flex flex-wrap items-center justify-between gap-3 rounded-[22px] border border-[#C3CCEA] bg-white px-6 py-4 shadow-[0_10px_30px_rgba(15,27,77,0.12)]">
            <div>
                <p class="text-xs font-semibold text-[#5B6478]">Marks <span id="eval-answered">0</span>/{{ count($criteria) }} answered</p>
                <p class="text-lg font-extrabold"><span id="eval-total">0</span> / {{ $maxTotal }} <span class="text-sm font-semibold text-[#5B6478]">&rarr; final</span> <span id="eval-final" class="text-[#1F45D6]">0</span> / {{ $weightText }}</p>
            </div>
            @if(! $locked)
                <button type="submit" class="min-h-11 rounded-xl bg-[#2F5BFF] px-6 text-sm font-bold text-white shadow-sm transition hover:bg-[#1F45D6]">{{ $evaluation ? 'Update evaluation' : 'Submit evaluation' }}</button>
            @endif
        </section>
    </form>
</div>

<script>
(function () {
    var form = document.getElementById('eval-form');
    if (!form) return;

    var maxTotal = parseInt(form.getAttribute('data-max'), 10) || 0;
    var weight = parseFloat(form.getAttribute('data-weight')) || 0;
    var totalEl = document.getElementById('eval-total');
    var finalEl = document.getElementById('eval-final');
    var answeredEl = document.getElementById('eval-answered');

    function update() {
        var total = 0;
        var answered = 0;

        form.querySelectorAll('[data-criterion]').forEach(function (row) {
            var picked = row.querySelector('input[type="radio"]:checked');
            if (picked) {
                total += parseInt(picked.value, 10);
                answered += 1;
            }
        });

        totalEl.textContent = total;
        answeredEl.textContent = answered;
        finalEl.textContent = maxTotal > 0 ? (Math.round(total / maxTotal * weight * 100) / 100) : 0;
    }

    form.addEventListener('change', update);
    update();
})();
</script>
@endsection
