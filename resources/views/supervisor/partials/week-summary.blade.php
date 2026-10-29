{{-- Variables: $summary (array|null), $missing (array of strings), $generatedAt (Carbon|null) --}}
@php
    $flags = array_merge($missing ?? [], $summary['flags'] ?? []);
@endphp

@if(! $summary)
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-[#5B6478]">Get a short overview of the week and one line per day.</p>
        <button type="button" data-summary-run class="rounded-xl bg-[#6D28D9] px-4 py-2 text-sm font-bold text-white transition hover:bg-[#5B21B6]">
            <i class="fa-solid fa-wand-magic-sparkles mr-1"></i> Summarise this week
        </button>
    </div>
@else
    <p class="text-sm leading-relaxed text-[#1E293B]">{{ $summary['overview'] }}</p>

    @if(! empty($summary['days']))
        <ul class="mt-3 space-y-1.5">
            @foreach($summary['days'] as $day)
                <li class="flex gap-3 text-[13px] leading-snug text-[#334155]">
                    <span class="w-[74px] flex-none font-extrabold text-[#6D28D9]">{{ \Carbon\Carbon::parse($day['date'])->format('D d M') }}</span>
                    <span class="min-w-0 break-words">{{ $day['summary'] }}</span>
                </li>
            @endforeach
        </ul>
    @endif

    @if(! empty($summary['skills']))
        <div class="mt-3 flex flex-wrap gap-1.5">
            @foreach($summary['skills'] as $skill)
                <span class="rounded-full bg-[#F0EAFF] px-2.5 py-1 text-[11px] font-bold text-[#6D28D9]">{{ $skill }}</span>
            @endforeach
        </div>
    @endif

    @if(count($flags))
        <div class="mt-3 rounded-xl border border-[#FDE68A] bg-[#FFFBEB] px-3.5 py-2.5">
            <p class="text-[11px] font-extrabold uppercase tracking-wider text-[#92400E]"><i class="fa-solid fa-triangle-exclamation mr-1"></i> Worth a look</p>
            <ul class="mt-1 list-disc space-y-0.5 pl-4 text-[13px] text-[#78350F]">
                @foreach($flags as $flag)
                    <li>{{ $flag }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-[11px] text-[#5B6478]">
        <span>
            @if(! empty($summary['fallback']))
                AI is unavailable right now, so these are the first lines of each entry.
            @else
                Written by AI from the student's text only (files are not read). Check it against the entries below.
            @endif
            @if(! empty($generatedAt)) &middot; {{ $generatedAt->timezone(\App\Models\Attendance::TIMEZONE)->format('d M, H:i') }} @endif
        </span>
        <button type="button" data-summary-run data-force="1" class="font-bold text-[#6D28D9] hover:underline">
            <i class="fa-solid fa-rotate mr-1"></i> Regenerate
        </button>
    </div>
@endif
