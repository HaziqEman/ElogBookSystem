    {{-- NEW ENTRY (opens from "Write entry" and the quick actions) --}}
    <details id="new-entry" class="{{ $card }}" {{ ($errors->any() && old('description') !== null) || old('description') || old('activity_date') ? 'open' : '' }}>
        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-7 py-5 [&::-webkit-details-marker]:hidden">
            <div>
                <h2 class="text-xl font-extrabold">New logbook entry</h2>
                <p class="mt-0.5 text-[13px] text-[#5B6478]">One entry per working day. Pick the day you worked, write the work, and attach a file if you have one.</p>
            </div>
            <span class="flex h-9 w-9 flex-none items-center justify-center rounded-xl bg-[#E8EFFF] text-[#1F45D6]"><i class="fa-solid fa-chevron-down text-sm"></i></span>
        </summary>

        <form id="logbook-form" action="/student/logbook/store" method="POST" enctype="multipart/form-data" data-async data-first-monday="{{ $firstMondayKey }}" data-total-weeks="{{ $planner->totalWeeks() }}" data-working-days="{{ $workingDaysCsv }}" data-today="{{ $todayKey }}" class="space-y-3 border-t border-[#EDF0F7] px-7 pb-6 pt-5">
            @csrf
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#334155]">Date of Training</label>
                    <input type="date" name="activity_date" value="{{ old('activity_date', $todayKey) }}" @if($startKey) min="{{ $startKey }}" @endif max="{{ $todayKey }}" class="w-full rounded-xl border border-[#CBD3E3] p-2.5 text-sm focus:border-[#2F5BFF] focus:outline-none">                </div>
                @if($planner->hasStart())
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-[#334155]">Week</label>
                        <input type="hidden" name="week_no" value="{{ old('week_no') }}">
                        <p id="week-hint" class="flex min-h-[42px] items-center rounded-xl bg-[#F7F9FD] px-3 text-sm text-[#5B6478]">Pick a date and the week is worked out for you.</p>
                    </div>
                @else
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-[#334155]">Week Number</label>
                        <input type="number" name="week_no" value="{{ old('week_no') }}" class="w-full rounded-xl border border-[#CBD3E3] p-2.5 text-sm focus:border-[#2F5BFF] focus:outline-none">
                    </div>
                @endif
            </div>
            <p id="date-hint" class="hidden rounded-xl bg-[#FFEDD5] px-3 py-2 text-xs font-semibold text-[#9A3412]"></p>

            <div data-ai-help-form>
                <label for="ai-role" class="mb-1 block text-xs font-semibold text-[#334155]">Your role at the company <span class="font-normal text-[#5B6478]">(AI Help uses it to pick the right wording)</span></label>
                <input type="text" id="ai-role" data-ai-role maxlength="150" value="{{ old('role', $student->internship_role ?? '') }}" placeholder="e.g. Junior web developer, Accounts assistant" class="w-full rounded-xl border border-[#CBD3E3] p-2.5 text-sm focus:border-[#2F5BFF] focus:outline-none">
            </div>

            <div>
                <label for="description-field" class="mb-1 block text-xs font-semibold text-[#334155]">Activity Description <span class="text-red-600">*</span></label>
                <div class="mb-2 flex items-center justify-between gap-2">
                    <span class="text-[11px] text-[#5B6478]">Need help drafting a polished entry?</span>
                    <button type="button" id="ai-help-btn" data-ai-help-button class="shrink-0 rounded-lg border border-[#D3E0FF] bg-[#E8EFFF] px-3 py-1.5 text-xs font-bold text-[#1F45D6] transition hover:bg-[#D3E0FF] disabled:cursor-not-allowed disabled:opacity-60">
                        <i class="fa-solid fa-wand-magic-sparkles"></i> <span id="ai-help-label" data-ai-help-label>AI Help</span>
                    </button>
                </div>
                <textarea id="description-field" data-ai-description rows="5" name="description" placeholder="Detail tasks performed..." class="w-full rounded-xl border p-2.5 text-sm focus:outline-none {{ $errors->has('description') ? 'border-red-400 focus:border-red-500' : 'border-[#CBD3E3] focus:border-[#2F5BFF]' }}">{{ old('description') }}</textarea>
                <p id="description-error" class="{{ $errors->has('description') ? '' : 'hidden ' }}mt-1.5 text-xs font-semibold text-red-600">{{ $errors->first('description') }}</p>
                <p id="ai-help-status" data-ai-help-status class="mt-2 text-xs text-[#5B6478]"></p>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-[#334155]">Upload Attachment (image, PDF or Word, max 5 MB)</label>
                <input type="file" name="attachment" data-ai-file class="w-full rounded-xl border border-[#CBD3E3] p-2.5 text-sm file:mr-3 file:rounded file:border-0 file:bg-[#E8EFFF] file:px-3 file:py-2 file:text-sm file:font-bold file:text-[#1F45D6]">
            </div>
            <div id="ai-read-file-box" data-ai-read-file-box class="hidden">
                <label class="flex cursor-pointer items-start gap-2.5 rounded-xl border border-[#E3E8F2] bg-[#FCFDFF] p-3 text-xs text-[#334155]">
                    <input type="checkbox" id="ai-read-file" data-ai-read-file class="mt-0.5 h-4 w-4">
                    <span><strong>Let AI Help read this file</strong> (photo or PDF) when it writes the draft. The file is sent to Google's Gemini service, so do not use documents with confidential company information.</span>
                </label>
            </div>
            <button id="submit-entry-btn" type="submit" class="w-full rounded-xl bg-[#2F5BFF] p-3 text-sm font-bold text-white shadow-sm transition hover:bg-[#1F45D6] disabled:cursor-not-allowed disabled:opacity-60">Submit Entry</button>
        </form>
    </details>

    {{-- LOGBOOK BY WEEK --}}
    <section id="logbook-week" data-live class="px-7 py-6 {{ $card }}">
        <div class="mb-3 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-xl font-extrabold">Logbook by week</h2>
                <div class="mt-0.5 text-[13px] text-[#5B6478]">
                    @if($planner->hasStart())
                        Week {{ max(1, min($planner->totalWeeks(), $currentWeek)) }} of {{ $planner->totalWeeks() }} &middot; one entry per working day &middot; each week is sent to your supervisor when it ends
                    @endif
                </div>
            </div>
            <button
            type="button"
            onclick="openUniversityModal()"
            class="inline-flex min-h-9 items-center gap-2 rounded-xl border border-[#CBD3E3] bg-white px-3 text-sm font-bold text-[#1F45D6] transition hover:bg-[#F3F5FC]"
        >
            <i class="fa-solid fa-file-pdf"></i>
            Export PDF
            </button>
        </div>

        @if(! $planner->hasStart())
            <div class="rounded-2xl border border-dashed border-[#C3CCEA] bg-[#F5F7FF] px-5 py-8 text-center text-sm text-[#5B6478]">
                <i class="fa-regular fa-calendar mb-2 block text-3xl text-[#94A3B8]"></i>
                Your internship dates have not been set yet. Please ask your administrator to set your start date and working days. Your weeks will appear here once they are set.
            </div>
        @else
                    <div class="mb-4 flex flex-wrap items-center gap-x-5 gap-y-1 rounded-2xl border border-[#E3E8F2] bg-[#FCFDFF] px-4 py-3 text-sm text-[#334155]">
                        <span><i class="fa-regular fa-calendar mr-1.5 text-[#1F45D6]"></i> Starts <strong>{{ $planner->start()->format('d M Y') }}</strong></span>
                        <span><i class="fa-solid fa-calendar-week mr-1.5 text-[#1F45D6]"></i> <strong>{{ $planner->workingDaysLabel() }}</strong></span>
                        <span>{{ $planner->totalWeeks() }} weeks</span>
                    </div>
        @endif

        @if($planner->hasStart() && $selected)
            <div class="flex flex-wrap gap-2">
                @foreach($weeks as $week)
                    <a href="/student/dashboard?week={{ $week['number'] }}#logbook-week" data-live-nav title="{{ $statusText[$week['status']] }}" class="flex min-h-[42px] min-w-[52px] items-center justify-center rounded-xl px-3 text-sm font-extrabold transition {{ $chipClass[$week['status']] }} {{ $week['number'] === $selected['number'] ? 'ring-2 ring-[#2F5BFF] ring-offset-2' : '' }}">W{{ $week['number'] }}</a>
                @endforeach
            </div>
            <form id="schedule-bar" method="POST" action="/student/dashboard/working-days" data-async data-saved="{{ implode(',', $planner->workingDays()) }}" class="mb-5 mt-4 rounded-2xl border border-[#E3E8F2] bg-[#F7F9FD] p-4">
                @csrf
                <div class="mb-3 flex flex-wrap items-center justify-between gap-x-4 gap-y-1">
                    <p class="text-xs font-extrabold uppercase tracking-wider text-[#5B6478]"><i class="fa-regular fa-calendar mr-1 text-[#1F45D6]"></i> My working days</p>
                    <span id="schedule-count" class="text-xs font-semibold text-[#334155]">{{ count($planner->workingDays()) }} {{ count($planner->workingDays()) === 1 ? 'day' : 'days' }} a week</span>
                </div>

                <div class="grid grid-cols-7 gap-2" role="group" aria-label="Working days">
                    @foreach(\App\Services\WeekPlanner::DAY_NAMES as $number => $name)
                        <button type="button" data-working-day="{{ $number }}" aria-pressed="{{ in_array($number, $planner->workingDays(), true) ? 'true' : 'false' }}" title="{{ $number >= 6 ? ($number === 6 ? 'Saturday' : 'Sunday').' counts as day '.$number.' of your week' : '' }}" class="min-h-[56px] w-full rounded-xl border-2 text-sm font-extrabold transition focus-visible:ring-2 focus-visible:ring-[#2F5BFF]/40 sm:min-h-[60px] sm:text-base {{ in_array($number, $planner->workingDays(), true) ? 'border-[#2F5BFF] bg-[#E8EFFF] text-[#1F45D6]' : 'border-[#CBD3E3] bg-white text-[#334155] hover:border-[#9DB4FF]' }}">{{ $name }}</button>
                    @endforeach
                </div>

                <div class="mt-3 flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
                    <p class="min-w-0 flex-[1_1_260px] text-[11px] leading-relaxed text-[#5B6478]">Tap a day to switch it on or off. Saturday and Sunday count as day 6 and day 7, so your list below can show up to 7 days. Changes start today; days that already passed keep their old schedule.</p>
                    <button id="schedule-save" type="button" disabled class="min-h-10 rounded-xl bg-[#2F5BFF] px-6 text-sm font-bold text-white transition hover:bg-[#1F45D6] disabled:cursor-not-allowed disabled:opacity-40">Save</button>
                </div>
                <p id="schedule-message" class="mt-2 hidden text-xs font-semibold text-[#BE123C]" role="status" aria-live="polite"></p>
            </form>

            @if($selected['status'] === 'locked')
                <div class="flex flex-col items-center gap-3 rounded-[20px] border border-dashed border-[#C3CCEA] bg-[#F5F7FF] px-6 py-12 text-center">
                    <span class="flex h-16 w-16 items-center justify-center rounded-full bg-white text-2xl text-[#94A3B8]"><i class="fa-solid fa-lock"></i></span>
                    <p class="text-lg font-extrabold">Week {{ $selected['number'] }} opens on {{ $selected['start']->format('l, d F Y') }}</p>
                    <p class="text-sm text-[#5B6478]">You can write entries for it once the week starts.</p>
                </div>
            @else
                <div class="mb-4 flex flex-wrap items-center justify-between gap-x-6 gap-y-3">
                    <div class="min-w-0 flex-[1_1_320px]">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <h3 class="text-2xl font-extrabold">Week {{ $selected['number'] }}</h3>
                            @if($selected['is_current'])
                                <span class="rounded-full bg-[#E8EFFF] px-2.5 py-1 text-xs font-bold text-[#1F45D6]">Current week</span>
                            @endif
                            @if(! in_array($selected['status'], ['current', 'locked'], true))
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $chipClass[$selected['status']] }}">{{ $statusText[$selected['status']] }}</span>
                            @endif
                        </div>
                        <p class="mt-1 text-[13px] text-[#5B6478]">{{ $selected['start']->format('d M') }} to {{ $selected['end']->format('d M Y') }}</p>
                    </div>

                    <div class="min-w-[220px] flex-[1_1_240px]">
                        <div class="flex items-center justify-between text-[13px] font-semibold">
                            <span>
                                @if($selected['required'] > 0)
                                    {{ $selected['done'] }} of {{ $selected['required'] }} days complete
                                @else
                                    No entry needed this week
                                @endif
                            </span>
                            <span class="text-[#5B6478]">{{ $selected['percent'] }}%</span>
                        </div>
                        <div class="mt-1.5 h-2 overflow-hidden rounded-[5px] bg-[#E3E8F2]"><div class="h-full rounded-[5px] bg-[#2F5BFF]" style="width:{{ $selected['percent'] }}%"></div></div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $pill($selected['supervisor']) }}">Supervisor: {{ $selected['supervisor'] }}</span>
                    </div>
                </div>

                @if($selected['is_current'] && $selected['last_working'])
                    <p class="mb-4 text-[13px] text-[#5B6478]">This week is sent to your supervisor automatically after {{ $selected['last_working']->format('l, d M') }}. Days with no entry are shown to them as missing.</p>
                @endif

                <div class="space-y-3.5">
                    @foreach($selected['days'] as $day)
                        @php
                            $date = $day['date'];
                            $entry = $day['entry'];
                            $file = $entry ? $entry->attachments->first() : null;
                            $moreFiles = $entry ? max($entry->attachments->count() - 1, 0) : 0;
                            $attendanceRow = $day['attendance'];
                            $offType = $attendanceRow ? $attendanceRow->dayOffLabel() : '';
                            $offChip = $offType === 'MC' ? 'bg-[#FFE4E6] text-[#9F1239]' : ($offType === 'Public holiday' ? 'bg-[#E0E7FF] text-[#3730A3]' : 'bg-[#FEF3C7] text-[#7C2D12]');
                            $isToday = $day['state'] === 'today';
                            $rowClass = $isToday
                                ? 'border-2 border-[#2F5BFF] bg-white'
                                : ($day['state'] === 'upcoming' ? 'border border-[#E7ECF6] bg-[#F7F9FD]' : 'border border-[#E7ECF6] bg-[#FCFDFF]');
                        @endphp
                        <article class="flex flex-wrap items-start gap-5 rounded-[20px] p-[18px] {{ $rowClass }}">
                            <div class="w-14 flex-none pt-1 text-center">
                                <div class="text-xs font-extrabold tracking-wider text-[#5B6478]">{{ strtoupper($date->format('D')) }}</div>
                                <div class="text-[28px] font-extrabold leading-none">{{ $date->format('d') }}</div>
                                @if($date->isoWeekday() >= 6)
                                    <div class="mx-auto mt-1 w-fit rounded bg-[#FEF3C7] px-1.5 text-[10px] font-extrabold text-[#7C2D12]">Day {{ $date->isoWeekday() }}</div>
                                @endif
                            </div>

                            @if($day['state'] === 'done')
                                @if($entry->attachments->count() > 1)
                                    <div data-attachment-carousel class="relative h-[150px] w-full shrink-0 overflow-hidden rounded-2xl sm:w-[210px]" aria-label="Logbook attachments">
                                        @foreach($entry->attachments as $attachedFile)
                                            <div data-carousel-slide class="{{ $loop->first ? '' : 'hidden' }} absolute inset-0">
                                                @include('student.partials.file-preview', ['file' => $attachedFile])
                                            </div>
                                        @endforeach
                                        <button type="button" data-carousel-step="-1" aria-label="Show previous attachment" class="absolute left-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full bg-slate-900/65 text-white shadow transition hover:bg-slate-900/85"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
                                        <span data-carousel-count class="absolute bottom-2 left-1/2 -translate-x-1/2 rounded-full bg-slate-900/70 px-2.5 py-1 text-[11px] font-bold text-white">1 / {{ $entry->attachments->count() }}</span>
                                        <button type="button" data-carousel-step="1" aria-label="Show next attachment" class="absolute right-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full bg-slate-900/65 text-white shadow transition hover:bg-slate-900/85"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
                                    </div>
                                @else
                                    @include('student.partials.file-preview', ['file' => $file])
                                @endif
                            @elseif($day['state'] === 'dayoff')
                                @if($attendanceRow->hasMc())
                                    @include('student.partials.file-preview', ['file' => $attendanceRow->mcAttachment()])
                                @else
                                    <div class="flex h-[150px] w-full shrink-0 items-center justify-center rounded-2xl bg-white/70 text-4xl text-[#94A3B8] sm:w-[210px]">
                                        <i class="fa-solid {{ $attendanceRow->dayOffLabel() === 'Public holiday' ? 'fa-flag' : 'fa-umbrella-beach' }}"></i>
                                    </div>
                                @endif
                            @elseif($day['state'] === 'upcoming')
                                <div class="flex h-[150px] w-full shrink-0 flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-[#D5DBEA] bg-white/60 text-[#94A3B8] sm:w-[210px]">
                                    <i class="fa-solid fa-lock text-3xl"></i>
                                    <span class="text-xs font-bold">Not open yet</span>
                                </div>
                            @else
                                @include('student.partials.file-preview', ['file' => null])
                            @endif

                            <div class="flex min-w-0 flex-[1_1_300px] flex-col gap-2">
                                @if($day['state'] === 'done')
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded-full bg-[#D1FAE5] px-2.5 py-1 text-xs font-bold text-[#065F46]">Done</span>
                                            @if($day['wfh'])
                                                <span class="rounded-full bg-[#E8EFFF] px-2.5 py-1 text-xs font-bold text-[#1F45D6]"><i class="fa-solid fa-house"></i> Work from home</span>
                                            @endif
                                            @if($day['extra'])
                                                <span class="rounded-full bg-[#FEF3C7] px-2.5 py-1 text-xs font-bold text-[#7C2D12]">Extra day</span>
                                            @endif
                                        </div>
                                        @if($entry->isEditableByStudent())
                                            <a href="/student/logbook/edit/{{ $entry->logbook_id }}" data-edit-entry aria-label="{{ $entry->needsRevision() ? 'Revise entry' : 'Edit entry' }}" title="{{ $entry->needsRevision() ? 'Revise entry' : 'Edit entry' }}" class="flex h-[38px] w-[38px] items-center justify-center rounded-[11px] border transition {{ $entry->needsRevision() ? 'border-[#BE123C] bg-[#BE123C] text-white hover:bg-[#9F1239]' : 'border-[#E3E8F2] bg-white text-[#334155] hover:bg-[#F3F5FC]' }}"><i class="fa-solid fa-pen text-sm"></i></a>
                                        @else
                                            <span title="Validated by your supervisor, so it is locked" class="flex h-[38px] w-[38px] items-center justify-center rounded-[11px] border border-[#E3E8F2] bg-[#F7F9FD] text-[#94A3B8]"><i class="fa-solid fa-lock text-sm"></i></span>
                                        @endif
                                    </div>

                                    <p class="line-clamp-3 break-words text-sm leading-relaxed text-[#334155]">{{ $entry->description }}</p>

                                    <div class="mt-auto flex flex-wrap items-center gap-2">
                                        @if($file)
                                            <a href="{{ $file->url }}" target="_blank" rel="noopener" title="{{ $file->file_name }}" class="flex min-h-8 max-w-[260px] items-center gap-1.5 rounded-[9px] bg-[#EEF2FB] px-2.5 text-xs font-medium text-[#1E293B] transition hover:bg-[#E1E8F8]">
                                                <i class="fa-solid fa-paperclip text-[#94A3B8]"></i>
                                                <span class="truncate">{{ $file->file_name }}</span>
                                            </a>
                                            <a href="{{ $file->url }}" target="_blank" rel="noopener" class="flex min-h-8 items-center rounded-[9px] border border-[#CBD3E3] bg-white px-3 text-xs font-bold text-[#1F45D6] transition hover:bg-[#F3F5FC]">Open file</a>
                                            @if($moreFiles > 0)
                                                <span class="text-xs text-[#5B6478]">+{{ $moreFiles }} more</span>
                                            @endif
                                        @elseif($entry->isEditableByStudent())
                                            <a href="/student/logbook/edit/{{ $entry->logbook_id }}" data-edit-entry class="flex min-h-8 items-center gap-1.5 rounded-[9px] border border-dashed border-[#9DB4FF] bg-[#F5F7FF] px-3 text-xs font-bold text-[#1F45D6] transition hover:bg-[#E8EFFF]"><i class="fa-solid fa-plus"></i> Add a file</a>
                                        @endif

                                        @if($supervisorAssigned)
                                            <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $pill($entry->supervisor_status ?? 'Pending') }}">Supervisor: {{ $entry->supervisor_status ?? 'Pending' }}</span>
                                        @endif
                                    </div>

                                    @if($day['entries']->count() > 1)
                                        <a href="/student/logbooks" class="text-xs font-bold text-[#1F45D6] hover:underline">{{ $day['entries']->count() }} entries on this day. See them all</a>
                                    @endif

                                @elseif($day['state'] === 'dayoff')
                                        <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $offChip }}">{{ $offType }}</span>
                                        <span class="text-xs font-semibold text-[#5B6478]">No entry needed</span>
                                    </div>
                                    @if($attendanceRow->note)
                                        <p class="break-words text-sm leading-relaxed text-[#334155]">{{ $attendanceRow->note }}</p>
                                    @endif
                                    @if($attendanceRow->hasMc())
                                        <div>
                                            <a href="/attendance/{{ $attendanceRow->attendance_id }}/mc" target="_blank" rel="noopener" class="inline-flex min-h-8 max-w-[260px] items-center gap-1.5 rounded-[9px] bg-[#EEF2FB] px-2.5 text-xs font-medium text-[#1E293B] transition hover:bg-[#E1E8F8]">
                                                <i class="fa-solid fa-file-medical text-[#BE123C]"></i>
                                                <span class="truncate">{{ $attendanceRow->mc_file_name }}</span>
                                            </a>
                                        </div>
                                    @endif

                                @elseif($day['state'] === 'today')
                                    <div><span class="rounded-full bg-[#E8EFFF] px-2.5 py-1 text-xs font-bold text-[#1F45D6]">Today</span></div>
                                    <p class="text-sm leading-relaxed text-[#334155]">Today's entry is not written yet. Add what you worked on, then attach proof if you have it.</p>
                                    <div class="mt-1 flex flex-wrap gap-2">
                                        <a href="#new-entry" data-write-date="{{ $day['key'] }}" class="flex min-h-10 items-center rounded-xl bg-[#2F5BFF] px-4 text-sm font-bold text-white transition hover:bg-[#1F45D6]">Write today's entry</a>
                                        <a href="#new-entry" data-write-date="{{ $day['key'] }}" data-focus="description" class="flex min-h-10 items-center gap-1.5 rounded-xl bg-[#F0EAFF] px-4 text-sm font-bold text-[#6D28D9] transition hover:bg-[#E5DAFF]"><i class="fa-solid fa-wand-magic-sparkles text-xs"></i> Draft with AI Help</a>
                                    </div>

                                @elseif($day['state'] === 'missing')
                                    <div><span class="rounded-full bg-[#FFE4E6] px-2.5 py-1 text-xs font-bold text-[#9F1239]">Missing</span></div>
                                    <p class="text-sm leading-relaxed text-[#334155]">There is no entry for {{ $date->format('l, d F') }}. Your supervisor will see it as missing.</p>
                                    <div class="mt-1">
                                        <a href="#new-entry" data-write-date="{{ $day['key'] }}" class="inline-flex min-h-10 items-center rounded-xl bg-[#BE123C] px-4 text-sm font-bold text-white transition hover:bg-[#9F1239]">Add late entry</a>
                                    </div>

                                @else
                                    <div><span class="rounded-full bg-[#EEF1F7] px-2.5 py-1 text-xs font-bold text-[#5B6478]">Upcoming</span></div>
                                    <p class="text-sm text-[#5B6478]">Opens on {{ $date->format('l, d F') }}.</p>
                                @endif
                            </div>
                        </article>
                    @endforeach

                    @if(empty($selected['days']))
                        <div class="rounded-[20px] border border-dashed border-[#C3CCEA] bg-[#F5F7FF] p-8 text-center text-sm text-[#5B6478]">None of your working days fall in this week.</div>
                    @endif
                </div>
            @endif

            <div class="mt-5 flex justify-end">
                <a href="/student/logbooks" class="flex min-h-9 items-center gap-1.5 text-sm font-bold text-[#1F45D6] hover:underline">Open full logbook <i class="fa-solid fa-arrow-right text-xs"></i></a>
            </div>
        @endif
    </section>
