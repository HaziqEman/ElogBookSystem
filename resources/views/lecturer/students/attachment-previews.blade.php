<div class="mt-2">
    @if ($attachments->count() > 1)
        <div
            data-attachment-carousel
            class="relative h-[150px] w-full max-w-[210px] overflow-hidden rounded-2xl"
            aria-label="Logbook attachments"
        >
            @foreach ($attachments as $attachment)
                <div
                    data-carousel-slide
                    class="{{ $loop->first ? '' : 'hidden' }} absolute inset-0"
                >
                    <a
                        href="{{ $attachment->url }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="Open {{ $attachment->file_name }}"
                        title="{{ $attachment->file_name }}"
                    >@include('student.logbooks.file-preview', ['file' => $attachment])</a>
                </div>
            @endforeach
            <button
                type="button"
                data-carousel-step="-1"
                aria-label="Previous attachment"
                class="absolute left-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full bg-slate-900/65 text-white shadow transition hover:bg-slate-900/85"
            ><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
            <span
                data-carousel-count
                class="absolute bottom-2 left-1/2 -translate-x-1/2 rounded-full bg-slate-900/70 px-2.5 py-1 text-[11px] font-bold text-white"
            >1 / {{ $attachments->count() }}</span>
            <button
                type="button"
                data-carousel-step="1"
                aria-label="Next attachment"
                class="absolute right-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full bg-slate-900/65 text-white shadow transition hover:bg-slate-900/85"
            ><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
        </div>
    @else
        @php
            $attachment = $attachments->first();
        @endphp
        <a
            href="{{ $attachment->url }}"
            target="_blank"
            rel="noopener noreferrer"
            aria-label="Open {{ $attachment->file_name }}"
            title="{{ $attachment->file_name }}"
            class="block w-fit"
        >@include('student.logbooks.file-preview', ['file' => $attachment])</a>
    @endif

    <div class="mt-2 flex flex-wrap gap-2">
        @foreach ($attachments as $attachment)
            <a
                href="{{ $attachment->url }}"
                target="_blank"
                rel="noopener noreferrer"
                title="{{ $attachment->file_name }}"
                class="flex min-h-8 max-w-[260px] items-center gap-1.5 rounded-[9px] bg-[#EEF2FB] px-2.5 text-xs font-medium text-[#1E293B] transition hover:bg-[#E1E8F8]"
            ><i class="fa-solid fa-paperclip text-[#94A3B8]"></i><span
                    class="truncate"
                >{{ $attachment->file_name }}</span></a>
        @endforeach
    </div>
</div>
