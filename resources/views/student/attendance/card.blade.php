@php
    $hasIn = $attendance && $attendance->check_in;
    $hasOut = $attendance && $attendance->check_out;
    $dayOff = $attendance && $attendance->isDayOff();
    $isHome = $attendance && $attendance->work_mode === 'Home';
    $cardClass =
        'rounded-[22px] border border-[#E3E8F2] bg-white shadow-[0_6px_18px_rgba(15,27,77,0.05)]';

    $dayType = $dayOff ? $attendance->dayOffLabel() : null;

    $dayChip = match ($dayType) {
        'MC' => 'bg-[#FFE4E6] text-[#9F1239]',
        'Public holiday' => 'bg-[#E0E7FF] text-[#3730A3]',
        default => 'bg-[#FEF3C7] text-[#7C2D12]',
    };

    $dayTone = match ($dayType) {
        'MC' => 'border border-[#FBCFCB] bg-[#FFF5F4]',
        'Public holiday' => 'border border-[#C7D2FE] bg-[#F5F7FF]',
        default => 'border border-[#FDE68A] bg-[#FFFBEB]',
    };

    $dayIcon = match ($dayType) {
        'MC' => 'fa-notes-medical text-[#BE123C]',
        'Public holiday' => 'fa-flag text-[#3730A3]',
        default => 'fa-suitcase-rolling text-[#B45309]',
    };

    $dayHeading = match ($dayType) {
        'MC' => 'You are on medical leave',
        'Public holiday' => 'Public holiday',
        default => 'You are on leave',
    };

    $dayHint = match ($dayType) {
        'MC' => 'Rest well. No need to clock in today.',
        'Public holiday' => 'Enjoy your day off. No need to clock in today.',
        default => 'No need to clock in today.',
    };

    $canChange = $dayOff && $attendance->attendance_date->toDateString() >= $today->toDateString();

    // Keep the popover open when the server sent validation errors back.
    $popOpen =
        $errors->has('day_off_type') ||
        $errors->has('leave_date') ||
        $errors->has('note') ||
        $errors->has('mc_file');
    $mcMaxKb = (int) config('attendance.mc_max_kb', 2048);
@endphp

{{-- CHANGE 1: flex column (and no self-start) so the card fills the row and its content is spread out --}}
<div
    id="attendance-card"
    data-live
    class="{{ $cardClass }} relative flex flex-col px-5 py-4"
>
    {{-- ===== Header ===== --}}
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <span
                class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#E8EFFF] text-[#1F45D6]"
            >
                <svg
                    width="19"
                    height="19"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <circle
                        cx="12"
                        cy="12"
                        r="9"
                    />
                    <path d="M12 7v5l3 2" />
                </svg>
            </span>
            <div>
                <h2 class="text-base font-extrabold">Today's attendance</h2>
                <p class="text-xs text-[#5B6478]">{{ $today->format('l, d M Y') }} &middot; This
                    month: {{ $monthPresent }} present, {{ $monthLeave }} off</p>
            </div>
        </div>

        @if ($dayOff)
            <span
                class="{{ $dayChip }} whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-bold"
            >{{ $attendance->dayOffTitle() }}</span>
        @elseif($hasOut)
            <span
                class="whitespace-nowrap rounded-full bg-[#E0E7FF] px-2.5 py-1 text-xs font-bold text-[#3730A3]"
            >Day complete</span>
        @elseif($hasIn)
            <span
                class="whitespace-nowrap rounded-full bg-[#D1FAE5] px-2.5 py-1 text-xs font-bold text-[#065F46]"
            >Present</span>
        @else
            <span
                class="whitespace-nowrap rounded-full bg-[#FEF3C7] px-2.5 py-1 text-xs font-bold text-[#7C2D12]"
            >Not clocked in</span>
        @endif
    </div>

    {{-- =====================================================================
         STATE: day off (MC / Leave / Public holiday)
         ===================================================================== --}}
    @if ($dayOff)
        <div
            class="{{ $dayTone }} mt-3 flex flex-1 flex-col items-center justify-center gap-2.5 rounded-2xl px-4 py-5 text-center">
            <span
                class="flex h-12 w-12 items-center justify-center rounded-xl bg-white text-xl shadow-[0_2px_8px_rgba(15,27,77,0.10)]"
            >
                <i class="fa-solid {{ $dayIcon }}"></i>
            </span>

            <div>
                <p class="text-lg font-extrabold leading-tight">{{ $dayHeading }}</p>
                <p class="mt-0.5 text-[13px] text-[#475569]">{{ $dayHint }}</p>
            </div>

            <div
                class="flex w-full max-w-sm flex-wrap items-center justify-center gap-x-3 gap-y-1.5 rounded-xl border border-white/70 bg-white/80 px-3 py-2 text-left">
                <p class="min-w-0 break-words text-[13px] text-[#1E293B]">
                    <span class="font-semibold text-[#5B6478]">Reason:</span>
                    <span class="font-bold">{{ $attendance->note ?: 'No reason given' }}</span>
                </p>
                @if ($attendance->hasMc())
                    <a
                        href="/attendance/{{ $attendance->attendance_id }}/mc"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex min-h-7 max-w-full items-center gap-1.5 rounded-full border border-[#E3E8F2] bg-white px-2.5 text-xs font-semibold text-[#1E293B] transition hover:bg-[#F3F5FC]"
                    >
                        <i class="fa-solid fa-paperclip text-[#64748B]"></i>
                        <span class="max-w-[12rem] truncate">{{ $attendance->mc_file_name }}</span>
                    </a>
                @endif
            </div>

            @if ($canChange)
                <form
                    method="POST"
                    action="/student/attendance/day-off/{{ $attendance->attendance_id }}/cancel"
                    data-async
                    onsubmit="return confirm('Remove this day off so you can clock in?');"
                >
                    @csrf
                    <button
                        type="submit"
                        class="min-h-9 rounded-xl border border-[#CBD3E3] bg-white px-5 text-[13px] font-bold text-[#1E293B] transition hover:bg-[#F3F5FC] disabled:opacity-60"
                    >Change</button>
                </form>
            @endif
        </div>
    @elseif($hasIn)
        {{-- CHANGE 2: flex-1 so the times sit in the middle of the card when the card is taller than its content --}}
        <div class="mt-4 flex flex-1 flex-wrap items-center gap-x-7 gap-y-3">
            <div>
                <div class="text-xs text-[#5B6478]">Clock in</div>
                <div class="text-2xl font-extrabold">{{ $attendance->checkInLocal() }}</div>
            </div>
            <div>
                <div class="text-xs text-[#5B6478]">Clock out</div>
                <div class="text-2xl font-extrabold">
                    {{ $hasOut ? $attendance->checkOutLocal() : '--:--' }}</div>
            </div>
            @if ($hasOut)
                <div>
                    <div class="text-xs text-[#5B6478]">Worked</div>
                    <div class="text-2xl font-extrabold">{{ $attendance->workedLabel() }}</div>
                </div>
            @endif
            @if ($attendance->workModeLabel())
                <span class="rounded-full bg-[#E8EFFF] px-3 py-1 text-xs font-bold text-[#1F45D6]">
                    <i class="fa-solid {{ $isHome ? 'fa-house' : 'fa-building' }}"></i>
                    {{ $attendance->workModeLabel() }}
                </span>
            @endif
            <div class="ml-auto min-w-[160px]">
                @if ($hasOut)
                    <button
                        type="button"
                        disabled
                        class="min-h-12 w-full cursor-not-allowed rounded-[14px] bg-[#64748B] px-4 text-[15px] font-bold text-white"
                    >Done for today</button>
                @else
                    <form
                        method="POST"
                        action="/student/attendance/clock-out"
                        data-async
                    >
                        @csrf
                        <button
                            type="submit"
                            class="min-h-12 w-full rounded-[14px] bg-[#334155] px-4 text-[15px] font-bold text-white transition hover:bg-[#1E293B] disabled:opacity-60"
                        >Clock out</button>
                    </form>
                @endif
            </div>
        </div>

        @if ($isHome)
            {{-- Work from home: no map and no coordinates are shown, for privacy. --}}
            <p class="mt-4 rounded-xl bg-[#F7F9FD] px-3 py-2 text-xs text-[#5B6478]">
                <i class="fa-solid fa-lock mr-1"></i>
                Location is not recorded when you work from home.
            </p>
        @elseif ($attendance->location_status === 'shared' && $attendance->hasLocation())
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                <iframe
                    title="Map showing where you clocked in"
                    src="{{ $attendance->mapEmbedUrl() }}"
                    loading="lazy"
                    referrerpolicy="no-referrer"
                    class="h-28 w-full rounded-2xl border border-[#E3E8F2] sm:h-36 md:h-44"
                ></iframe>
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wider text-[#5B6478]">
                        Clock-in location</p>
                    <p class="mt-1 break-words text-sm font-bold text-[#1E293B]">
                        {{ $attendance->location_name ?: 'Place name not available' }}</p>
                    <p class="mt-1 text-xs text-[#5B6478]">
                        {{ number_format((float) $attendance->latitude, 4) }},
                        {{ number_format((float) $attendance->longitude, 4) }}
                        @if ($attendance->location_accuracy)
                            &middot; accurate to about {{ $attendance->location_accuracy }} m
                        @endif
                    </p>
                    @if ($attendance->location_accuracy && $attendance->location_accuracy > 500)
                        <p class="mt-1 text-xs text-[#7C2D12]">Low accuracy: this position may be
                            well off.</p>
                    @endif
                    <a
                        href="{{ $attendance->mapLink() }}"
                        target="_blank"
                        rel="noopener"
                        class="mt-2 inline-block text-xs font-bold text-[#1F45D6] hover:underline"
                    >Open in OpenStreetMap</a>
                </div>
            </div>
        @else
            <p class="mt-4 rounded-xl bg-[#F7F9FD] px-3 py-2 text-xs text-[#5B6478]">
                <i class="fa-solid fa-location-crosshairs mr-1"></i>
                Location not
                shared{{ $attendance->location_status === 'denied' ? ' (permission was refused)' : '' }}.
                Your supervisor will see that no location was recorded.
            </p>
        @endif

        {{-- =====================================================================
         STATE: not clocked in yet (default)
         ===================================================================== --}}
    @else
        <form
            id="clock-in-form"
            method="POST"
            action="/student/attendance/clock-in"
            data-async
            class="mt-4 space-y-3"
        >
            @csrf
            <input
                type="hidden"
                name="location_status"
                value="unavailable"
            >
            <input
                type="hidden"
                name="latitude"
                value=""
            >
            <input
                type="hidden"
                name="longitude"
                value=""
            >
            <input
                type="hidden"
                name="accuracy"
                value=""
            >

            <div
                class="grid grid-cols-2 gap-2.5"
                role="radiogroup"
                aria-label="Where are you working today?"
            >
                <label class="cursor-pointer">
                    <input
                        type="radio"
                        name="work_mode"
                        value="Workplace"
                        class="peer sr-only"
                        checked
                    >
                    <span
                        class="flex min-h-12 items-center justify-center gap-2 rounded-[14px] border border-[#CBD3E3] bg-white px-3 text-sm font-bold text-[#334155] transition peer-checked:border-[#2F5BFF] peer-checked:bg-[#E8EFFF] peer-checked:text-[#1F45D6] peer-focus-visible:ring-2 peer-focus-visible:ring-[#2F5BFF]/40"
                    ><i class="fa-solid fa-building"></i> At workplace</span>
                </label>
                <label class="cursor-pointer">
                    <input
                        type="radio"
                        name="work_mode"
                        value="Home"
                        class="peer sr-only"
                    >
                    <span
                        class="flex min-h-12 items-center justify-center gap-2 rounded-[14px] border border-[#CBD3E3] bg-white px-3 text-sm font-bold text-[#334155] transition peer-checked:border-[#2F5BFF] peer-checked:bg-[#E8EFFF] peer-checked:text-[#1F45D6] peer-focus-visible:ring-2 peer-focus-visible:ring-[#2F5BFF]/40"
                    ><i class="fa-solid fa-house"></i> Work from home</span>
                </label>
            </div>

            <button
                type="submit"
                id="clock-in-btn"
                class="min-h-12 w-full rounded-[14px] bg-[#047857] px-4 text-[15px] font-bold text-white transition hover:bg-[#065F46] disabled:opacity-70"
            >Clock in</button>
            <p
                id="geo-status"
                class="text-xs text-[#5B6478]"
                aria-live="polite"
            >Your browser will ask to share your location. It is saved with today's attendance so
                your supervisors can review it. You can still clock in if you say no.</p>
        </form>

        {{-- ===== MC / Leave / Public holiday: floating popover (does not push the page down) ===== --}}
        <div
            id="day-off-backdrop"
            data-pop-close
            class="{{ $popOpen ? '' : 'hidden' }} fixed inset-0 z-30 bg-slate-900/30 sm:bg-transparent"
        ></div>

        {{-- CHANGE 3: mt-auto pins this button to the bottom of the card, so there is no blank gap under it --}}
        <div class="relative mt-auto pt-4">
            <button
                type="button"
                id="day-off-trigger"
                data-pop-toggle
                aria-haspopup="dialog"
                aria-expanded="{{ $popOpen ? 'true' : 'false' }}"
                aria-controls="day-off-pop"
                class="flex min-h-11 w-full items-center justify-between gap-2 rounded-2xl border border-[#E3E8F2] bg-[#FCFDFF] px-4 text-sm font-bold text-[#1F45D6] transition hover:bg-[#F3F5FC]"
            >
                <span><i class="fa-solid fa-notes-medical mr-1.5"></i> MC / Leave / Public
                    holiday</span>
                <i
                    id="day-off-caret"
                    class="fa-solid fa-chevron-down {{ $popOpen ? 'rotate-180' : '' }} text-xs transition-transform"
                ></i>
            </button>

            <div
                id="day-off-pop"
                role="dialog"
                aria-labelledby="day-off-title"
                class="{{ $popOpen ? '' : 'hidden' }} absolute left-0 right-0 top-full z-50 mt-2 rounded-2xl border border-[#E3E8F2] bg-white p-4 shadow-[0_18px_50px_rgba(15,27,77,0.22)] max-sm:fixed max-sm:inset-x-0 max-sm:bottom-0 max-sm:top-auto max-sm:mt-0 max-sm:max-h-[88vh] max-sm:overflow-y-auto max-sm:rounded-b-none"
            >
                <div class="mb-3 flex items-center justify-between">
                    <h3
                        id="day-off-title"
                        class="text-base font-extrabold"
                    >Mark a day off</h3>
                    <button
                        type="button"
                        data-pop-close
                        aria-label="Close"
                        class="flex h-8 w-8 items-center justify-center rounded-lg text-[#64748B] transition hover:bg-[#F3F5FC]"
                    ><i class="fa-solid fa-xmark"></i></button>
                </div>

                <form
                    id="day-off-form"
                    method="POST"
                    action="/student/attendance/day-off"
                    enctype="multipart/form-data"
                    data-async
                    class="space-y-3"
                >
                    @csrf
                    @if ($errors->any())
                        <ul
                            class="list-disc rounded-xl bg-rose-50 py-2 pl-7 pr-3 text-xs text-rose-700">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif

                    <div
                        class="flex flex-wrap gap-2"
                        role="radiogroup"
                        aria-label="Type"
                    >
                        @foreach (\App\Models\Attendance::DAY_OFF_TYPES as $type)
                            <label class="cursor-pointer">
                                <input
                                    type="radio"
                                    name="day_off_type"
                                    value="{{ $type }}"
                                    class="peer sr-only"
                                    {{ old('day_off_type', 'MC') === $type ? 'checked' : '' }}
                                >
                                <span
                                    class="block rounded-full border border-[#CBD3E3] bg-white px-4 py-2 text-sm font-bold text-[#334155] transition peer-checked:border-[#2F5BFF] peer-checked:bg-[#2F5BFF] peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-[#2F5BFF]/40"
                                >{{ $type }}</span>
                            </label>
                        @endforeach
                    </div>

                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold text-[#334155]">Date</span>
                        <input
                            type="date"
                            name="leave_date"
                            value="{{ old('leave_date', $today->toDateString()) }}"
                            required
                            class="w-full rounded-xl border border-[#CBD3E3] p-2.5 text-sm focus:border-[#2F5BFF] focus:outline-none"
                        >
                    </label>

                    <label class="block">
                        <span
                            id="day-off-note-label"
                            class="mb-1 block text-xs font-semibold text-[#334155]"
                        >Reason</span>
                        <input
                            type="text"
                            name="note"
                            id="day-off-note"
                            maxlength="255"
                            value="{{ old('note') }}"
                            placeholder="e.g. fever, family matter, Hari Raya"
                            class="w-full rounded-xl border border-[#CBD3E3] p-2.5 text-sm focus:border-[#2F5BFF] focus:outline-none"
                        >
                    </label>

                    <label
                        id="day-off-file-box"
                        class="block"
                    >
                        <span class="mb-1 block text-xs font-semibold text-[#334155]">Upload your
                            MC
                            (photo or PDF, max {{ round($mcMaxKb / 1024, 1) }} MB)</span>
                        <input
                            type="file"
                            name="mc_file"
                            id="day-off-file"
                            data-max-kb="{{ $mcMaxKb }}"
                            accept="image/jpeg,image/png,image/webp,application/pdf"
                            class="w-full rounded-xl border border-[#CBD3E3] p-2.5 text-sm file:mr-3 file:rounded file:border-0 file:bg-[#E8EFFF] file:px-3 file:py-2 file:text-sm file:font-bold file:text-[#1F45D6]"
                        >
                        <span
                            id="day-off-file-error"
                            class="mt-1 hidden text-xs font-semibold text-rose-600"
                        >That file is too large. Please choose a smaller photo or PDF.</span>
                        <span class="mt-1 block text-[11px] text-[#5B6478]">Only you, your
                            lecturer,
                            your company supervisor and the administrator can open it.</span>
                    </label>

                    <div class="flex gap-2 pt-1">
                        <button
                            type="button"
                            data-pop-close
                            class="min-h-11 rounded-xl border border-[#CBD3E3] bg-white px-4 text-sm font-bold text-[#334155] transition hover:bg-[#F3F5FC]"
                        >Close</button>
                        <button
                            type="submit"
                            class="min-h-11 flex-1 rounded-xl bg-[#2F5BFF] px-4 text-sm font-bold text-white transition hover:bg-[#1F45D6] disabled:opacity-60"
                        >Save</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

<script>
    (function() {
        if (window.__attendanceCardBound) return;
        window.__attendanceCardBound = true;

        // ---------- Floating MC / Leave popover ----------
        function setPopover(open) {
            var pop = document.getElementById('day-off-pop');
            if (!pop) return;

            var backdrop = document.getElementById('day-off-backdrop');
            var trigger = document.getElementById('day-off-trigger');
            var caret = document.getElementById('day-off-caret');

            pop.classList.toggle('hidden', !open);
            if (backdrop) backdrop.classList.toggle('hidden', !open);
            if (trigger) trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (caret) caret.classList.toggle('rotate-180', open);

            if (open) {
                var first = pop.querySelector('input[type="date"]');
                if (first) first.focus({
                    preventScroll: true
                });
            } else if (trigger) {
                trigger.focus({
                    preventScroll: true
                });
            }
        }

        document.addEventListener('click', function(event) {
            if (!event.target || !event.target.closest) return;

            if (event.target.closest('[data-pop-toggle]')) {
                var pop = document.getElementById('day-off-pop');
                setPopover(pop ? pop.classList.contains('hidden') : false);
                return;
            }

            if (event.target.closest('[data-pop-close]')) setPopover(false);
        });

        document.addEventListener('keydown', function(event) {
            var pop = document.getElementById('day-off-pop');
            if (event.key === 'Escape' && pop && !pop.classList.contains('hidden'))
                setPopover(false);
        });

        // ---------- Clock in: location is only requested at the workplace ----------
        function workMode(form) {
            var checked = form.querySelector('input[name="work_mode"]:checked');
            return checked ? checked.value : 'Workplace';
        }

        var HINT_WORKPLACE =
            "Your browser will ask to share your location. It is saved with today's attendance so your supervisors can review it. You can still clock in if you say no.";
        var HINT_HOME = 'Your location is not requested or saved when you work from home.';

        document.addEventListener('change', function(event) {
            var target = event.target;
            if (!target) return;

            if (target.name === 'work_mode') {
                var hint = document.getElementById('geo-status');
                if (hint) hint.textContent = target.value === 'Home' ? HINT_HOME :
                    HINT_WORKPLACE;
            }
        });

        // This listener runs in the capture phase, so it can pause the first submit.
        document.addEventListener('submit', function(event) {
            var form = event.target;

            if (!form || form.id !== 'clock-in-form' || form.dataset.located) return;

            event.preventDefault();

            var button = form.querySelector('#clock-in-btn');
            var statusText = document.getElementById('geo-status');

            function setField(name, value) {
                var field = form.querySelector('[name="' + name + '"]');
                if (field) field.value = value;
            }

            function send(state) {
                setField('location_status', state);
                form.dataset.located = '1';

                if (form.requestSubmit) {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            }

            // Work from home: never ask for the position, never send coordinates.
            if (workMode(form) === 'Home') {
                setField('latitude', '');
                setField('longitude', '');
                setField('accuracy', '');
                if (button) {
                    button.disabled = true;
                    button.textContent = 'Clocking in...';
                }
                send('unavailable');
                return;
            }

            if (button) {
                button.disabled = true;
                button.textContent = 'Detecting your location...';
            }

            if (!navigator.geolocation) {
                if (statusText) statusText.textContent =
                    'This browser cannot share a location, so it will be recorded as not shared.';
                send('unavailable');
                return;
            }

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    setField('latitude', position.coords.latitude);
                    setField('longitude', position.coords.longitude);
                    setField('accuracy', position.coords.accuracy);
                    send('shared');
                },
                function(error) {
                    send(error && error.code === 1 ? 'denied' : 'unavailable');
                }, {
                    enableHighAccuracy: true,
                    timeout: 12000,
                    maximumAge: 0
                }
            );
        }, true);

        // If the server refused the clock in, let the student try again.
        function resetClockIn(event) {
            var form = event.target;

            if (!form || form.id !== 'clock-in-form') return;

            delete form.dataset.located;

            var button = form.querySelector('#clock-in-btn');
            if (button) {
                button.disabled = false;
                button.textContent = 'Clock in';
            }
        }

        document.addEventListener('async:error', resetClockIn);
        document.addEventListener('async:invalid', resetClockIn);

        // ---------- Day-off form: MC needs a certificate, the other types need a reason ----------
        function dayOffType() {
            var checked = document.querySelector('input[name="day_off_type"]:checked');
            return checked ? checked.value : 'MC';
        }

        function syncDayOff() {
            if (!document.getElementById('day-off-form')) return;

            var isMc = dayOffType() === 'MC';
            var note = document.getElementById('day-off-note');
            var noteLabel = document.getElementById('day-off-note-label');
            var fileBox = document.getElementById('day-off-file-box');
            var fileInput = document.getElementById('day-off-file');

            if (fileBox) fileBox.classList.toggle('hidden', !isMc);
            if (fileInput) fileInput.required = isMc;
            if (note) note.required = !isMc;
            if (noteLabel) noteLabel.textContent = isMc ? 'Reason (optional)' : 'Reason';
        }

        // Reject files above the limit straight away instead of waiting for the server.
        function checkFileSize(input) {
            var file = input.files && input.files[0];
            var error = document.getElementById('day-off-file-error');
            var maxKb = parseInt(input.dataset.maxKb || '0', 10);
            var tooBig = !!(file && maxKb && file.size > maxKb * 1024);

            if (error) error.classList.toggle('hidden', !tooBig);
            if (tooBig) input.value = '';
        }

        // Phone photos are large, so shrink them in the browser before uploading.
        function shrinkPhoto(input) {
            var file = input.files && input.files[0];

            if (!file || !/^image\/(jpeg|png|webp)$/.test(file.type) || !window.DataTransfer) {
                checkFileSize(input);
                return;
            }

            var image = new Image();
            var url = URL.createObjectURL(file);

            image.onload = function() {
                URL.revokeObjectURL(url);

                var scale = Math.min(1, 1600 / Math.max(image.width, image.height));
                if (scale === 1 && file.size < 1500000) {
                    checkFileSize(input);
                    return;
                }

                var canvas = document.createElement('canvas');
                canvas.width = Math.round(image.width * scale);
                canvas.height = Math.round(image.height * scale);
                canvas.getContext('2d').drawImage(image, 0, 0, canvas.width, canvas.height);

                canvas.toBlob(function(blob) {
                    if (!blob) {
                        checkFileSize(input);
                        return;
                    }

                    var transfer = new DataTransfer();
                    transfer.items.add(new File([blob], file.name.replace(
                        /\.[^.]+$/, '') + '.jpg', {
                        type: 'image/jpeg'
                    }));
                    input.files = transfer.files;
                    checkFileSize(input);
                }, 'image/jpeg', 0.82);
            };

            image.onerror = function() {
                URL.revokeObjectURL(url);
                checkFileSize(input);
            };
            image.src = url;
        }

        document.addEventListener('change', function(event) {
            if (!event.target) return;

            if (event.target.name === 'day_off_type') syncDayOff();
            if (event.target.id === 'day-off-file') shrinkPhoto(event.target);
        });

        document.addEventListener('async:swapped', syncDayOff);
        syncDayOff();
    })();
</script>
