@php
    $tz = \App\Models\Attendance::TIMEZONE;
    $today = now($tz)->startOfDay();
    $yesterday = $today->copy()->subDay();
    $itemsByDay = collect($pageItems)->groupBy(function ($item) use ($tz, $today, $yesterday) {
        $when = $item['when']?->timezone($tz);

        if (! $when || $when->lt($yesterday)) {
            return 'Older';
        }

        return $when->isToday() ? 'Today' : 'Yesterday';
    });
@endphp

<div id="notif-items" class="space-y-5 sm:space-y-6">
    @forelse ($itemsByDay as $period => $periodItems)
        <section
            data-notification-group
            data-period="{{ $period }}"
        >
            <h2 class="mb-3 flex items-center gap-2 px-1 text-sm font-bold text-slate-800">
                <span class="h-2 w-2 rounded-full bg-blue-600"></span>
                {{ $period }}
                <span class="h-px flex-1 bg-slate-200"></span>
            </h2>
            <div
                data-group-rows
                class="space-y-2.5"
            >
                @foreach ($periodItems as $item)
                    @php
                        $isUnread = $item['unread'];
                        $when = $item['when']?->timezone($tz);
                        $timeLabel = $when
                            ? ($when->isToday() || $when->isYesterday()
                                ? $when->format('g:i A')
                                : $when->format('d M'))
                            : null;
                        [$icon, $iconClass, $accentClass] = match ($item['type']) {
                            'reminder' => ['fa-clock', 'bg-[#FDE9B8] text-[#E8930C]', 'border-l-amber-400'],
                            'week' => ['fa-file-lines', 'bg-[#DCE6FB] text-[#2F5BD7]', 'border-l-blue-400'],
                            'message' => ['fa-comment-dots', 'bg-[#D9F2DF] text-[#23743C]', 'border-l-emerald-400'],
                            'review' => ['fa-circle-check', 'bg-[#ECEFF3] text-[#667085]', 'border-l-slate-300'],
                            'task' => ['fa-list-check', 'bg-[#EEE5FF] text-[#6841A5]', 'border-l-violet-400'],
                            default => ['fa-bell', 'bg-[#ECEFF3] text-[#667085]', 'border-l-slate-300'],
                        };
                    @endphp

                    <div
                        role="button"
                        tabindex="0"
                        data-notif-item
                        data-notification-row
                        data-notification-ids="{{ implode(',', $item['ids']) }}"
                        data-read-url="{{ url("/{$role}/notifications/read") }}"
                        data-open-url="{{ url("/{$role}/notifications/".(int) $item['ids'][0].'/open') }}"
                        data-type="{{ $item['type'] }}"
                        data-unread="{{ $isUnread ? '1' : '0' }}"
                        data-title="{{ $item['title'] }}"
                        data-body="{{ $item['body'] }}"
                        data-time="{{ $timeLabel }}"
                        data-count="{{ $item['merged_count'] }}"
                        data-icon="{{ $icon }}"
                        data-icon-class="{{ $iconClass }}"
                        class="group flex cursor-pointer items-start gap-3 rounded-xl border border-slate-100 border-l-4 {{ $accentClass }} bg-white p-4 shadow-[0_6px_18px_rgba(49,88,216,0.08)] transition hover:-translate-y-0.5 hover:shadow-[0_10px_24px_rgba(49,88,216,0.13)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[#3158D8] sm:gap-4"
                    >
                        <span
                            class="{{ $iconClass }} flex h-10 w-10 flex-none items-center justify-center rounded-full sm:h-11 sm:w-11"
                            aria-hidden="true"
                        ><i class="fa-solid {{ $icon }}"></i></span>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                <p
                                    data-notif-title
                                    class="{{ $isUnread ? 'font-semibold text-slate-900' : 'font-medium text-slate-600' }} break-words text-sm sm:text-[15px]"
                                >
                                    {{ $item['title'] }}
                                    @if ($item['merged_count'] > 1)
                                        <span class="ml-1 text-xs font-semibold text-slate-400">
                                            {{ $item['merged_count'] }}
                                        </span>
                                    @endif
                                </p>
                            </div>
                            @if ($item['body'])
                                <p class="{{ $isUnread ? 'text-slate-600' : 'text-slate-400' }} mt-1 break-words text-sm leading-relaxed">
                                    {{ $item['body'] }}
                                </p>
                            @endif
                            @if ($item['weeks'])
                                <details class="mt-2">
                                    <summary
                                        data-notification-control
                                        class="inline-flex cursor-pointer list-none items-center gap-1.5 text-xs font-bold text-blue-700 hover:text-blue-900 [&::-webkit-details-marker]:hidden"
                                    >
                                        Show weeks
                                        <span class="text-blue-500">({{ count($item['weeks']) }})</span>
                                        <i class="fa-solid fa-chevron-down text-[9px]" aria-hidden="true"></i>
                                    </summary>
                                    <ul class="mt-2 space-y-1.5 border-l-2 border-blue-100 pl-3">
                                        @foreach ($item['weeks'] as $week)
                                            <li class="text-xs text-slate-600">
                                                @if ($week['url'])
                                                    <a
                                                        href="{{ $week['url'] }}"
                                                        data-notification-control
                                                        class="font-semibold text-blue-700 hover:underline"
                                                    >Week {{ $week['week'] }}</a>
                                                @else
                                                    <span class="font-semibold">Week {{ $week['week'] }}</span>
                                                @endif
                                                <span>
                                                    · {{ $week['entries'] }}
                                                    {{ \Illuminate\Support\Str::plural('entry', $week['entries']) }}
                                                    {{ $item['type'] === 'reminder' ? 'not reviewed' : 'ready for review' }}
                                                </span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </details>
                            @endif
                        </div>

                        <div class="ml-auto flex flex-none items-center gap-2.5 pt-0.5">
                            @if ($timeLabel)
                                <span class="rounded-full bg-[#E8EEFD] px-3 py-1 text-[11px] font-semibold text-[#3158D8]">
                                    {{ $timeLabel }}
                                </span>
                            @endif
                            @if ($isUnread)
                                <span
                                    data-notif-dot
                                    class="h-2 w-2 flex-none rounded-full bg-blue-600"
                                    aria-label="Unread"
                                ></span>
                            @endif
                            <button
                                type="button"
                                data-notification-control
                                data-notification-dismiss
                                data-notification-ids="{{ implode(',', $item['ids']) }}"
                                aria-label="Clear notification: {{ $item['title'] }}"
                                class="flex h-7 w-7 flex-none items-center justify-center rounded-lg text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-400"
                            >
                                <i class="fa-solid fa-xmark text-sm" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @empty
        <div
            id="notif-empty-state"
            class="rounded-[24px] border border-dashed border-slate-300 bg-white px-5 py-10 text-center shadow-sm sm:px-8 sm:py-14"
        >
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-2xl text-[#3158D8]">
                <i class="fa-solid {{ $filter === 'unread' && $allCount > 0 ? 'fa-circle-check' : 'fa-bell-slash' }}" aria-hidden="true"></i>
            </span>
            @if ($filter === 'unread' && $allCount > 0)
                <h2 class="mt-4 text-lg font-extrabold text-slate-900">You're all caught up</h2>
                <p class="mx-auto mt-1 max-w-md text-sm leading-relaxed text-slate-500">
                    There are no unread notifications. Your earlier updates are still in your inbox.
                </p>
            @elseif ($allCount === 0)
                <h2 class="mt-4 text-lg font-extrabold text-slate-900">You're all caught up</h2>
                <p class="mx-auto mt-1 max-w-md text-sm leading-relaxed text-slate-500">
                    New messages, reminders and updates will appear here.
                </p>
            @else
                <h2 class="mt-4 text-lg font-extrabold text-slate-900">
                    No {{ \Illuminate\Support\Str::lower($filter) }} yet
                </h2>
                <p class="mx-auto mt-1 max-w-md text-sm leading-relaxed text-slate-500">
                    Try another inbox filter to see more updates.
                </p>
            @endif
        </div>
    @endforelse
</div>

@if ($nextUrl)
    <div class="flex justify-center pt-1">
        <button
            type="button"
            id="notif-load-older"
            data-url="{{ $nextUrl }}"
            class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-[#3158D8] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#3158D8] disabled:opacity-60"
        >
            <span>Load older notifications</span>
            <i class="fa-solid fa-arrow-down text-xs" aria-hidden="true"></i>
        </button>
    </div>
@endif
