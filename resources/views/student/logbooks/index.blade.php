@extends('layouts.app')

@section('content')
    @php
        $pill = function (string $status) {
            if (in_array($status, [\App\Models\Logbook::STATUS_APPROVED, \App\Models\Logbook::SUPERVISOR_STATUS_VALIDATED], true)) {
                return 'bg-[#D1FAE5] text-[#065F46]';
            }
            if (in_array($status, [\App\Models\Logbook::STATUS_REJECTED, \App\Models\Logbook::SUPERVISOR_STATUS_REVISION_REQUESTED, 'Revision needed'], true)) {
                return 'bg-[#FFEDD5] text-[#9A3412]';
            }
            if ($status === 'Waiting for review') {
                return 'bg-[#E0E7FF] text-[#3730A3]';
            }
            if (in_array($status, ['Not sent yet', 'Not assigned'], true)) {
                return 'bg-[#EEF1F7] text-[#5B6478]';
            }

            return 'bg-[#FEF3C7] text-[#7C2D12]';
        };

        $chipClass = [
            'validated' => 'bg-[#D1FAE5] text-[#065F46]',
            'waiting' => 'bg-[#E0E7FF] text-[#3730A3]',
            'revision' => 'bg-[#FFEDD5] text-[#9A3412]',
            'current' => 'bg-[#2F5BFF] text-white',
            'incomplete' => 'bg-[#FFE4E6] text-[#9F1239]',
            'locked' => 'bg-[#EEF1F7] text-[#94A3B8]',
        ];

        $statusText = [
            'validated' => 'Validated',
            'waiting' => 'Waiting for review',
            'revision' => 'Revision needed',
            'current' => 'Current week',
            'incomplete' => 'Entries missing',
            'locked' => 'Not open yet',
        ];

        // Shared card style (used by the included partial too)
        $card = 'rounded-3xl border border-[#E3E8F2] bg-white shadow-sm';
        $fullLogbookPage = true;

        $heroWeek = $planner->hasStart() ? max(1, min($planner->totalWeeks(), $currentWeek)) : null;
    @endphp

    {{-- The layout already gives the page its width and padding, so no extra wrapper padding here --}}
    <div class="space-y-5">

        {{-- Header banner --}}
        <section
            class="relative overflow-hidden rounded-3xl px-6 py-5 text-white shadow-[0_14px_34px_rgba(47,91,255,0.20)] md:px-9"
            style="background: linear-gradient(120deg, #3B5BFF 0%, #5C7CFF 40%, #A9BAFF 75%, #D6DEFF 100%);"
        >
            <div
                class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-white/10"
                aria-hidden="true"
            ></div>

            <div class="relative flex flex-wrap items-center justify-between gap-5">
                <div class="min-w-0 flex-1 basis-72">
                    <div
                        class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-[#E4EAFF]"
                    >
                        <i class="fa-regular fa-calendar"></i>
                        {{ \Carbon\Carbon::parse($todayKey)->format('l, d F Y') }}
                    </div>
                    <h1 class="mb-1.5 mt-2.5 text-3xl font-extrabold tracking-tight">Daily logbook</h1>
                    <p class="max-w-lg text-sm leading-relaxed text-[#E4EAFF]">
                        Record each working day here. Your supervisor reviews entries when the week ends.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2.5">
                    @if ($heroWeek)
                        <div class="min-w-[132px] rounded-2xl border border-white/20 bg-white/15 px-4 py-3 backdrop-blur">
                            <p class="text-2xl font-extrabold leading-none">{{ $heroWeek }}<span class="text-sm font-bold text-blue-100"> / {{ $planner->totalWeeks() }}</span></p>
                            <p class="mt-1 text-[11px] font-medium text-blue-50/90">Current week</p>
                        </div>
                    @endif
                    @if (!$hasFilters)
                        <div class="min-w-[132px] rounded-2xl border border-white/20 bg-white/15 px-4 py-3 backdrop-blur">
                            <p class="text-2xl font-extrabold leading-none">{{ $counts['all'] }}</p>
                            <p class="mt-1 text-[11px] font-medium text-blue-50/90">
                                {{ \Illuminate\Support\Str::plural('Entry', $counts['all']) }} written
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        @if (session('success'))
            <div
                class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800"
                role="status"
            >{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div
                class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
                role="alert"
            >
                <p class="font-bold">Please check the following:</p>
                <ul class="mt-1 list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @include('student.logbooks.list')

        {{-- Older entries: collapsed so the page stays short --}}
        @if (!$hasFilters && $unplannedLogbooks->total() > 0)
            <details class="{{ $card }} group overflow-hidden">
                <summary
                    class="flex cursor-pointer list-none items-center justify-between gap-3 px-6 py-5 [&::-webkit-details-marker]:hidden"
                >
                    <div class="flex items-center gap-3.5">
                        <span
                            class="flex h-11 w-11 flex-none items-center justify-center rounded-2xl bg-amber-50 text-amber-600"
                        >
                            <i class="fa-solid fa-box-archive"></i>
                        </span>
                        <div>
                            <h2 class="text-lg font-extrabold text-[#1E293B]">Other logbook entries
                                <span
                                    class="ml-1 rounded-full bg-[#EEF2FB] px-2.5 py-0.5 text-xs font-bold text-[#5B6478]"
                                >{{ $unplannedLogbooks->total() }}</span>
                            </h2>
                            <p class="mt-0.5 text-[13px] text-[#5B6478]">These entries are outside the
                                configured internship-week dates.</p>
                        </div>
                    </div>
                    <span
                        class="flex h-9 w-9 flex-none items-center justify-center rounded-xl bg-[#E8EFFF] text-[#1F45D6] transition group-open:rotate-180"
                    >
                        <i class="fa-solid fa-chevron-down text-sm"></i>
                    </span>
                </summary>

                <div
                    class="max-h-[36rem] space-y-3 overflow-y-auto border-t border-[#EDF0F7] bg-[#FAFBFF] px-5 py-5 sm:px-6">
                    @foreach ($unplannedLogbooks as $entry)
                        <article
                            class="rounded-2xl border border-[#E7ECF6] bg-white p-4 transition hover:shadow-md"
                        >
                            <div class="flex flex-wrap items-center gap-2">
                                <time
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-[#F1F5FD] px-2.5 py-1 text-xs font-bold text-[#334155]"
                                >
                                    <i class="fa-regular fa-calendar text-[#1F45D6]"></i>
                                    {{ $entry->activity_date ? \Carbon\Carbon::parse($entry->activity_date)->format('D, d M Y') : 'Date not set' }}
                                </time>
                                <span
                                    class="{{ $pill($entry->supervisor_status ?? \App\Models\Logbook::SUPERVISOR_STATUS_PENDING) }} rounded-full px-2.5 py-1 text-xs font-bold"
                                >Supervisor: {{ $entry->supervisor_status ?? \App\Models\Logbook::SUPERVISOR_STATUS_PENDING }}</span>
                            </div>
                            <p
                                class="mt-2.5 whitespace-pre-line break-words text-sm leading-relaxed text-[#334155]">
                                {{ $entry->description }}</p>
                            @foreach ($entry->attachments as $attachment)
                                <a
                                    href="{{ $attachment->url }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="mt-2 inline-flex max-w-full items-center gap-2 rounded-lg bg-[#EEF2FB] px-3 py-2 text-xs font-semibold text-[#1F45D6] transition hover:bg-[#E1E8F8]"
                                >
                                    <i class="fa-solid fa-paperclip"></i><span
                                        class="truncate">{{ $attachment->file_name }}</span>
                                </a>
                            @endforeach
                            @foreach ($entry->feedbacks->sortByDesc('feedback_id') as $feedback)
                                <div class="mt-3 rounded-xl bg-[#F7F9FD] p-3">
                                    <p class="text-xs font-bold text-[#334155]">
                                        {{ $feedback->supervisor_id ? 'Company supervisor' : 'Lecturer' }}
                                        feedback</p>
                                    <p
                                        class="mt-1 whitespace-pre-line break-words text-xs leading-relaxed text-[#475569]">
                                        {{ $feedback->comment }}</p>
                                </div>
                            @endforeach
                        </article>
                    @endforeach
                </div>
                @if ($unplannedLogbooks->hasPages())
                    <div class="border-t border-[#EDF0F7] px-5 py-4 sm:px-6">
                        {{ $unplannedLogbooks->links() }}
                    </div>
                @endif
            </details>
        @endif
    </div>
    @include('student.dashboard.modals')
@endsection
