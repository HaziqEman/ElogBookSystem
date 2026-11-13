{{-- Search + review filters + matching entries. Only used on the full logbook page. --}}
<div class="mb-5 rounded-2xl border border-[#E3E8F2] bg-[#FCFDFF] p-4">
    <form
        method="GET"
        action="/student/logbooks#logbook-week"
        class="flex flex-col gap-2 sm:flex-row"
    >
        <input
            type="hidden"
            name="week"
            value="{{ $selected['number'] ?? request('week', 1) }}"
        >
        <input
            type="hidden"
            name="review"
            value="{{ $review }}"
        >
        <label
            for="logbook-search"
            class="sr-only"
        >Search entries, files or feedback</label>
        <div class="relative min-w-0 flex-1">
            <i
                class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-[#94A3B8]"></i>
            <input
                id="logbook-search"
                type="search"
                name="search"
                value="{{ $search }}"
                placeholder="Search entries, files or supervisor comments..."
                class="h-10 w-full rounded-xl border border-[#CBD3E3] bg-white pl-9 pr-3 text-xs text-[#334155] focus:border-[#2F5BFF] focus:outline-none"
            >
        </div>
        <button
            type="submit"
            class="h-10 rounded-xl bg-[#2F5BFF] px-4 text-xs font-bold text-white transition hover:bg-[#1F45D6]"
        >Search</button>
        @if ($search !== '')
            <a
                href="{{ request()->fullUrlWithQuery(['search' => null]) }}#logbook-week"
                class="flex h-10 items-center justify-center rounded-xl border border-[#CBD3E3] bg-white px-4 text-xs font-bold text-[#5B6478] hover:bg-[#F7F9FD]"
            >Clear</a>
        @endif
    </form>

    <nav
        class="mt-3 flex flex-wrap gap-2"
        aria-label="Filter logbook entries by supervisor review"
    >
        @foreach ([
        'all' => ['All', $counts['all']],
        'action' => ['Needs action', $counts['action']],
        'pending' => ['Pending', $counts['pending']],
        'validated' => ['Validated', $counts['validated']],
    ] as $filter => [$label, $count])
            <a
                href="{{ request()->fullUrlWithQuery(['review' => $filter, 'week' => $selected['number'] ?? request('week', 1)]) }}#logbook-week"
                aria-current="{{ $review === $filter ? 'page' : 'false' }}"
                class="{{ $review === $filter ? 'bg-[#2F5BFF] text-white' : 'bg-[#EEF1F7] text-[#334155] hover:bg-[#E8EFFF]' }} inline-flex min-h-9 items-center gap-2 rounded-full px-3.5 text-xs font-bold transition"
            >
                {{ $label }}
                <span
                    class="{{ $review === $filter ? 'bg-white/25 text-white' : 'bg-white text-[#5B6478]' }} rounded-full px-1.5 py-0.5 text-[10px]"
                >{{ $count }}</span>
            </a>
        @endforeach
    </nav>
</div>

@if ($hasFilters)
    <div class="mb-6 space-y-3 rounded-2xl border border-[#D3E0FF] bg-[#F5F7FF] p-4">
        <h3 class="text-sm font-extrabold uppercase tracking-wide text-[#5B6478]">Matching entries
            ({{ $visibleLogbooks->count() }})</h3>
        @forelse($visibleLogbooks as $entry)
            <article class="rounded-[20px] border border-[#E7ECF6] bg-white p-4">
                <div class="flex flex-wrap items-center gap-2">
                    <time
                        class="text-xs font-bold text-[#5B6478]">{{ $entry->activity_date ? \Carbon\Carbon::parse($entry->activity_date)->format('D, d M Y') : 'Date not set' }}</time>
                    <span
                        class="{{ $pill($entry->supervisor_status ?? 'Pending') }} rounded-full px-2.5 py-1 text-xs font-bold"
                    >Supervisor: {{ $entry->supervisor_status ?? 'Pending' }}</span>
                    @if ($entry->isEditableByStudent())
                        <a
                            href="/student/logbook/edit/{{ $entry->logbook_id }}"
                            data-edit-entry
                            class="ml-auto text-xs font-bold text-[#1F45D6] hover:underline"
                        >Edit entry</a>
                    @endif
                </div>
                <p
                    class="mt-2 whitespace-pre-line break-words text-sm leading-relaxed text-[#334155]">
                    {{ $entry->description }}</p>
                @foreach ($entry->attachments as $attachment)
                    <a
                        href="{{ $attachment->url }}"
                        target="_blank"
                        rel="noopener"
                        class="mt-2 inline-flex max-w-full items-center gap-2 rounded-lg bg-[#EEF2FB] px-3 py-2 text-xs font-semibold text-[#1F45D6]"
                    >
                        <i class="fa-solid fa-paperclip"></i><span
                            class="truncate">{{ $attachment->file_name }}</span>
                    </a>
                @endforeach
                @forelse($entry->feedbacks->sortByDesc('feedback_id') as $feedback)
                    <div class="mt-3 rounded-xl bg-[#F7F9FD] p-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-xs font-bold text-[#334155]">
                                {{ $feedback->supervisor_id ? 'Company supervisor' : 'Lecturer' }}
                                feedback</p>
                            <time
                                class="text-[10px] text-[#94A3B8]">{{ $feedback->feedback_date ? \Carbon\Carbon::parse($feedback->feedback_date)->format('d M Y') : optional($feedback->created_at)->format('d M Y') }}</time>
                        </div>
                        <p
                            class="mt-1 whitespace-pre-line break-words text-xs leading-relaxed text-[#475569]">
                            {{ $feedback->comment }}</p>
                    </div>
                @empty
                    <p class="mt-3 text-xs text-[#94A3B8]">No written feedback yet.</p>
                @endforelse
            </article>
        @empty
            <div
                class="rounded-2xl border border-dashed border-[#C3CCEA] bg-white p-7 text-center text-sm text-[#5B6478]">
                No entries match this search or review filter.</div>
        @endforelse
    </div>
@endif
