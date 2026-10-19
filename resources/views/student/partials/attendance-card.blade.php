@php
    $hasIn = $attendance && $attendance->check_in;
    $hasOut = $attendance && $attendance->check_out;
    $dayOff = $attendance && $attendance->isDayOff();
    $cardClass = 'rounded-[22px] border border-[#E3E8F2] bg-white shadow-[0_6px_18px_rgba(15,27,77,0.05)]';

    $dayType = $dayOff ? $attendance->dayOffLabel() : null;
    $dayChip = $dayType === 'MC'
        ? 'bg-[#FFE4E6] text-[#9F1239]'
        : ($dayType === 'Public holiday' ? 'bg-[#E0E7FF] text-[#3730A3]' : 'bg-[#FEF3C7] text-[#7C2D12]');
    $dayTone = $dayType === 'MC'
        ? 'bg-[#FFF5F4] border border-[#FBCFCB]'
        : ($dayType === 'Public holiday' ? 'bg-[#F5F7FF] border border-[#C7D2FE]' : 'bg-[#FFFBEB] border border-[#FDE68A]');

    $canChange = $dayOff && $attendance->attendance_date->toDateString() >= $today->toDateString();
@endphp

<div id="attendance-card" class="px-[22px] py-5 {{ $cardClass }}">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#E8EFFF] text-[#1F45D6]">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
            </span>
            <div>
                <h2 class="text-base font-extrabold">Today's attendance</h2>
                <p class="text-xs text-[#5B6478]">{{ $today->format('l, d M Y') }} &middot; This month: {{ $monthPresent }} present, {{ $monthLeave }} off</p>
            </div>
        </div>

        @if($dayOff)
            <span class="whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-bold {{ $dayChip }}">{{ $attendance->dayOffTitle() }}</span>
        @elseif($hasOut)
            <span class="whitespace-nowrap rounded-full bg-[#E0E7FF] px-2.5 py-1 text-xs font-bold text-[#3730A3]">Day complete</span>
        @elseif($hasIn)
            <span class="whitespace-nowrap rounded-full bg-[#D1FAE5] px-2.5 py-1 text-xs font-bold text-[#065F46]">Present</span>
        @else
            <span class="whitespace-nowrap rounded-full bg-[#FEF3C7] px-2.5 py-1 text-xs font-bold text-[#7C2D12]">Not clocked in</span>
        @endif
    </div>

    @if($dayOff)
        <div class="mt-4 flex flex-wrap items-start justify-between gap-4 rounded-2xl p-4 {{ $dayTone }}">
            <div class="min-w-0 flex-1">
                <p class="text-lg font-extrabold">{{ $attendance->dayOffTitle() }}</p>
                @if($attendance->note)
                    <p class="mt-1 break-words text-sm text-[#334155]">{{ $attendance->note }}</p>
                @endif
                @if($attendance->hasMc())
                    <a href="/attendance/{{ $attendance->attendance_id }}/mc" target="_blank" rel="noopener" class="mt-2 inline-flex min-h-8 max-w-full items-center gap-1.5 rounded-[9px] bg-white px-2.5 text-xs font-medium text-[#1E293B] transition hover:bg-[#F3F5FC]">
                        <i class="fa-solid fa-file-medical text-[#BE123C]"></i>
                        <span class="max-w-[14rem] truncate">{{ $attendance->mc_file_name }}</span>
                    </a>
                @endif
            </div>
            @if($canChange)
                <form method="POST" action="/student/attendance/day-off/{{ $attendance->attendance_id }}/cancel" onsubmit="return confirm('Remove this day off so you can clock in?');">
                    @csrf
                    <button type="submit" class="min-h-10 rounded-xl border border-[#CBD3E3] bg-white px-4 text-sm font-bold text-[#1E293B] transition hover:bg-[#F3F5FC]">Change</button>
                </form>
            @endif
        </div>

    @elseif($hasIn)
        <div class="mt-4 flex flex-wrap items-center gap-x-7 gap-y-3">
            <div>
                <div class="text-xs text-[#5B6478]">Clock in</div>
                <div class="text-2xl font-extrabold">{{ $attendance->checkInLocal() }}</div>
            </div>
            <div>
                <div class="text-xs text-[#5B6478]">Clock out</div>
                <div class="text-2xl font-extrabold">{{ $hasOut ? $attendance->checkOutLocal() : '--:--' }}</div>
            </div>
            @if($hasOut)
                <div>
                    <div class="text-xs text-[#5B6478]">Worked</div>
                    <div class="text-2xl font-extrabold">{{ $attendance->workedLabel() }}</div>
                </div>
            @endif
            @if($attendance->workModeLabel())
                <span class="rounded-full bg-[#E8EFFF] px-3 py-1 text-xs font-bold text-[#1F45D6]">
                    <i class="fa-solid {{ $attendance->work_mode === 'Home' ? 'fa-house' : 'fa-building' }}"></i> {{ $attendance->workModeLabel() }}
                </span>
            @endif
            <div class="ml-auto min-w-[160px]">
                @if($hasOut)
                    <button type="button" disabled class="min-h-12 w-full cursor-not-allowed rounded-[14px] bg-[#64748B] px-4 text-[15px] font-bold text-white">Done for today</button>
                @else
                    <form method="POST" action="/student/attendance/clock-out" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                        @csrf
                        <button type="submit" class="min-h-12 w-full rounded-[14px] bg-[#334155] px-4 text-[15px] font-bold text-white transition hover:bg-[#1E293B] disabled:opacity-60">Clock out</button>
                    </form>
                @endif
            </div>
        </div>

        @if($attendance->location_status === 'shared' && $attendance->hasLocation())
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                <iframe title="Map showing where you clocked in" src="{{ $attendance->mapEmbedUrl() }}" loading="lazy" referrerpolicy="no-referrer" class="h-44 w-full rounded-2xl border border-[#E3E8F2]"></iframe>
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wider text-[#5B6478]">Clock-in location</p>
                    <p class="mt-1 break-words text-sm font-bold text-[#1E293B]">{{ $attendance->location_name ?: 'Place name not available' }}</p>
                    <p class="mt-1 text-xs text-[#5B6478]">
                        {{ number_format((float) $attendance->latitude, 4) }}, {{ number_format((float) $attendance->longitude, 4) }}
                        @if($attendance->location_accuracy)
                            &middot; accurate to about {{ $attendance->location_accuracy }} m
                        @endif
                    </p>
                    @if($attendance->location_accuracy && $attendance->location_accuracy > 500)
                        <p class="mt-1 text-xs text-[#7C2D12]">Low accuracy: this position may be well off.</p>
                    @endif
                    <a href="{{ $attendance->mapLink() }}" target="_blank" rel="noopener" class="mt-2 inline-block text-xs font-bold text-[#1F45D6] hover:underline">Open in OpenStreetMap</a>
                </div>
            </div>
        @else
            <p class="mt-4 rounded-xl bg-[#F7F9FD] px-3 py-2 text-xs text-[#5B6478]">
                <i class="fa-solid fa-location-crosshairs mr-1"></i>
                Location not shared{{ $attendance->location_status === 'denied' ? ' (permission was refused)' : '' }}. Your supervisor will see that no location was recorded.
            </p>
        @endif

    @else
        <form id="clock-in-form" method="POST" action="/student/attendance/clock-in" class="mt-4 space-y-3">
            @csrf
            <input type="hidden" name="location_status" value="unavailable">
            <input type="hidden" name="latitude" value="">
            <input type="hidden" name="longitude" value="">
            <input type="hidden" name="accuracy" value="">

            <div class="grid grid-cols-2 gap-2.5" role="radiogroup" aria-label="Where are you working today?">
                <label class="cursor-pointer">
                    <input type="radio" name="work_mode" value="Workplace" class="peer sr-only" checked>
                    <span class="flex min-h-12 items-center justify-center gap-2 rounded-[14px] border border-[#CBD3E3] bg-white px-3 text-sm font-bold text-[#334155] transition peer-checked:border-[#2F5BFF] peer-checked:bg-[#E8EFFF] peer-checked:text-[#1F45D6] peer-focus-visible:ring-2 peer-focus-visible:ring-[#2F5BFF]/40"><i class="fa-solid fa-building"></i> At workplace</span>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="work_mode" value="Home" class="peer sr-only">
                    <span class="flex min-h-12 items-center justify-center gap-2 rounded-[14px] border border-[#CBD3E3] bg-white px-3 text-sm font-bold text-[#334155] transition peer-checked:border-[#2F5BFF] peer-checked:bg-[#E8EFFF] peer-checked:text-[#1F45D6] peer-focus-visible:ring-2 peer-focus-visible:ring-[#2F5BFF]/40"><i class="fa-solid fa-house"></i> Work from home</span>
                </label>
            </div>

            <button type="submit" id="clock-in-btn" class="min-h-12 w-full rounded-[14px] bg-[#047857] px-4 text-[15px] font-bold text-white transition hover:bg-[#065F46] disabled:opacity-70">Clock in</button>
            <p id="geo-status" class="text-xs text-[#5B6478]" aria-live="polite">Your browser will ask to share your location. It is saved with today's attendance so your supervisors can review it. You can still clock in if you say no.</p>
        </form>

        <details class="mt-4 rounded-2xl border border-[#E3E8F2] bg-[#FCFDFF]" {{ $errors->has('day_off_type') || $errors->has('leave_date') || $errors->has('note') || $errors->has('mc_file') ? 'open' : '' }}>
            <summary class="flex min-h-11 cursor-pointer list-none items-center justify-between gap-2 px-4 text-sm font-bold text-[#1F45D6] [&::-webkit-details-marker]:hidden">
                <span><i class="fa-solid fa-notes-medical mr-1.5"></i> MC / Leave / Public holiday</span>
                <i class="fa-solid fa-chevron-down text-xs"></i>
            </summary>

            <form id="day-off-form" method="POST" action="/student/attendance/day-off" enctype="multipart/form-data" class="space-y-3 border-t border-[#E3E8F2] px-4 pb-4 pt-3">
                @csrf
                @if($errors->any())
                    <ul class="list-disc rounded-xl bg-rose-50 py-2 pl-7 pr-3 text-xs text-rose-700">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif

                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold text-[#334155]">Type</span>
                        <select name="day_off_type" id="day-off-type" class="w-full rounded-xl border border-[#CBD3E3] bg-white p-2.5 text-sm focus:border-[#2F5BFF] focus:outline-none">
                            @foreach(\App\Models\Attendance::DAY_OFF_TYPES as $type)
                                <option value="{{ $type }}" {{ old('day_off_type', 'MC') === $type ? 'selected' : '' }}>{{ $type }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-semibold text-[#334155]">Date</span>
                        <input type="date" name="leave_date" value="{{ old('leave_date', $today->toDateString()) }}" required class="w-full rounded-xl border border-[#CBD3E3] p-2.5 text-sm focus:border-[#2F5BFF] focus:outline-none">
                    </label>
                </div>

                <label class="block">
                    <span id="day-off-note-label" class="mb-1 block text-xs font-semibold text-[#334155]">Reason</span>
                    <input type="text" name="note" id="day-off-note" maxlength="255" value="{{ old('note') }}" placeholder="e.g. fever, family matter, Hari Raya" class="w-full rounded-xl border border-[#CBD3E3] p-2.5 text-sm focus:border-[#2F5BFF] focus:outline-none">
                </label>

                <label id="day-off-file-box" class="block">
                    <span class="mb-1 block text-xs font-semibold text-[#334155]">Upload your MC (photo or PDF, max {{ round(config('attendance.mc_max_kb', 2048) / 1024, 1) }} MB)</span>
                    <input type="file" name="mc_file" id="day-off-file" accept="image/jpeg,image/png,image/webp,application/pdf" class="w-full rounded-xl border border-[#CBD3E3] p-2.5 text-sm file:mr-3 file:rounded file:border-0 file:bg-[#E8EFFF] file:px-3 file:py-2 file:text-sm file:font-bold file:text-[#1F45D6]">
                    <span class="mt-1 block text-[11px] text-[#5B6478]">Only you, your lecturer, your company supervisor and the administrator can open it.</span>
                </label>

                <button type="submit" class="min-h-11 w-full rounded-xl bg-[#2F5BFF] px-4 text-sm font-bold text-white transition hover:bg-[#1F45D6] disabled:opacity-60">Save</button>
            </form>
        </details>
    @endif
</div>

<script>
(function () {
    if (window.__attendanceCardBound) return;
    window.__attendanceCardBound = true;

    // Clock in: ask for the position first, then submit. Refusing never blocks clocking in.
    var form = document.getElementById('clock-in-form');
    if (form) {
        var button = document.getElementById('clock-in-btn');
        var statusText = document.getElementById('geo-status');
        var armed = false;

        function setField(name, value) {
            var field = form.querySelector('[name="' + name + '"]');
            if (field) field.value = value;
        }

        function finish(state) {
            setField('location_status', state);
            armed = true;
            form.submit();
        }

        form.addEventListener('submit', function (e) {
            if (armed) return;
            e.preventDefault();

            button.disabled = true;
            button.textContent = 'Detecting your location...';

            if (!navigator.geolocation) {
                statusText.textContent = 'This browser cannot share a location, so it will be recorded as not shared.';
                finish('unavailable');
                return;
            }

            navigator.geolocation.getCurrentPosition(
                function (position) {
                    setField('latitude', position.coords.latitude);
                    setField('longitude', position.coords.longitude);
                    setField('accuracy', position.coords.accuracy);
                    finish('shared');
                },
                function (error) {
                    finish(error && error.code === 1 ? 'denied' : 'unavailable');
                },
                { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
            );
        });

        window.addEventListener('pageshow', function () {
            armed = false;
            button.disabled = false;
            button.textContent = 'Clock in';
        });
    }

    // Day off form: MC needs a certificate, the other types need a reason.
    var typeSelect = document.getElementById('day-off-type');
    if (typeSelect) {
        var note = document.getElementById('day-off-note');
        var noteLabel = document.getElementById('day-off-note-label');
        var fileBox = document.getElementById('day-off-file-box');
        var fileInput = document.getElementById('day-off-file');

        function syncType() {
            var isMc = typeSelect.value === 'MC';
            fileBox.classList.toggle('hidden', !isMc);
            fileInput.required = isMc;
            note.required = !isMc;
            noteLabel.textContent = isMc ? 'Reason (optional)' : 'Reason';
        }

        typeSelect.addEventListener('change', syncType);
        syncType();

        // Phone photos are large, so shrink them in the browser before uploading.
        fileInput.addEventListener('change', function () {
            var file = fileInput.files && fileInput.files[0];
            if (!file || !/^image\/(jpeg|png|webp)$/.test(file.type) || !window.DataTransfer) return;

            var image = new Image();
            var url = URL.createObjectURL(file);

            image.onload = function () {
                URL.revokeObjectURL(url);

                var scale = Math.min(1, 1600 / Math.max(image.width, image.height));
                if (scale === 1 && file.size < 1500000) return;

                var canvas = document.createElement('canvas');
                canvas.width = Math.round(image.width * scale);
                canvas.height = Math.round(image.height * scale);
                canvas.getContext('2d').drawImage(image, 0, 0, canvas.width, canvas.height);

                canvas.toBlob(function (blob) {
                    if (!blob) return;
                    var transfer = new DataTransfer();
                    transfer.items.add(new File([blob], file.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' }));
                    fileInput.files = transfer.files;
                }, 'image/jpeg', 0.82);
            };
            image.onerror = function () { URL.revokeObjectURL(url); };
            image.src = url;
        });
    }
})();
</script>