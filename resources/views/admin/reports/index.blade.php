@extends('layouts.app')

@section('content')
    @php
        $csvLinks = [
            ['type' => 'progress', 'label' => 'Student progress', 'icon' => 'fa-user-graduate'],
            ['type' => 'marks', 'label' => 'Student marks', 'icon' => 'fa-graduation-cap'],
            ['type' => 'weekly', 'label' => 'Weekly review', 'icon' => 'fa-calendar-week'],
            [
                'type' => 'attention',
                'label' => 'Attention list',
                'icon' => 'fa-triangle-exclamation',
            ],
            ['type' => 'lecturers', 'label' => 'Lecturers', 'icon' => 'fa-user-tie'],
            [
                'type' => 'supervisors',
                'label' => 'Company supervisors',
                'icon' => 'fa-building-user',
            ],
            ['type' => 'companies', 'label' => 'Companies', 'icon' => 'fa-building'],
        ];

        $field =
            'mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none';
    @endphp

    <div class="space-y-6">
        <div class="rounded-[16px] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/40">
            <div class="flex flex-col gap-3">
                <span class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">UITM E-LOGBOOK
                    PORTAL > Admin Console</span>
                <h2 class="text-2xl font-bold text-slate-800">Admin Report Page</h2>
            </div>
        </div>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">
                {{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">
                {{ session('error') }}</div>
        @endif

        {{-- Internship coordinator exports --}}
        <div class="rounded-[16px] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/40">
            <h3 class="text-xl font-semibold text-slate-800">Internship Coordinator Reports</h3>
            <p class="mt-2 text-sm text-slate-600">
                Pick who to include (leave everything on "All" for the whole programme), then download
                the Excel dashboard
                or one table as CSV. Task titles and logbook text are never exported, only counts and
                statuses.
            </p>

            <form
                id="reportFilters"
                class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
                onsubmit="return false;"
            >
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Lecturer
                    <select
                        name="lecturer_id"
                        class="{{ $field }}"
                    >
                        <option value="">All lecturers</option>
                        @foreach ($lecturers as $lecturer)
                            <option value="{{ $lecturer->lecturer_id }}">{{ $lecturer->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Company
                    <select
                        name="company"
                        class="{{ $field }}"
                    >
                        <option value="">All companies</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company }}">{{ $company }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Company
                    supervisor
                    <select
                        name="supervisor_id"
                        class="{{ $field }}"
                    >
                        <option value="">All supervisors</option>
                        @foreach ($supervisors as $supervisor)
                            <option value="{{ $supervisor->supervisor_id }}">{{ $supervisor->name }}
                                @if ($supervisor->company_name)
                                    ({{ $supervisor->company_name }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Course
                    <select
                        name="course"
                        class="{{ $field }}"
                    >
                        <option value="">All courses</option>
                        @foreach ($courses as $course)
                            <option value="{{ $course }}">{{ $course }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">University
                    <select
                        name="university_id"
                        class="{{ $field }}"
                    >
                        <option value="">All universities</option>
                        @foreach ($universities as $university)
                            <option value="{{ $university->university_id }}">{{ $university->name }}
                            </option>
                        @endforeach
                    </select>
                </label>
            </form>

            <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center">
                <button
                    type="button"
                    data-export="/admin/reports/export/dashboard"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700"
                >
                    <i class="fa-solid fa-chart-column"></i>
                    Download Excel Dashboard
                </button>
                <span class="text-xs text-slate-500">Summary with charts and evaluation marks, plus
                    student progress, student marks, weekly review, attention list, lecturers,
                    supervisors and companies.</span>
            </div>

            <div class="mt-6 border-t border-slate-100 pt-5">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Or download one
                    table as CSV</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($csvLinks as $link)
                        <button
                            type="button"
                            data-export="/admin/reports/export/csv/{{ $link['type'] }}"
                            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        >
                            <i class="fa-solid {{ $link['icon'] }} text-slate-400"></i>
                            {{ $link['label'] }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Existing exports
    <div class="bg-white rounded-[16px] border border-slate-200 shadow-sm shadow-slate-200/40 p-6">
        <h3 class="text-xl font-semibold text-slate-800">Academic Compilation Reports</h3>
        <p class="mt-3 text-sm text-slate-600">Export student logbook records, lecturer evaluations, and internship summaries into standardized formats.</p>

        <div class="mt-6 flex flex-col gap-3 sm:flex-row">
            <a href="/admin/reports/export/excel" class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700">
                <i class="fa-solid fa-file-excel"></i>
                Export Excel Sheet
            </a>
            <a href="/admin/reports/export/pdf" class="inline-flex items-center justify-center gap-2 rounded-lg bg-rose-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-rose-700">
                <i class="fa-solid fa-file-pdf"></i>
                Export Master Evaluation Audit PDF
            </a>
        </div>
    </div>
</div> --}}

        <script>
            // Adds the selected filters to the export link, then starts the download.
            document.querySelectorAll('[data-export]').forEach(function(button) {
                button.addEventListener('click', function() {
                    var params = new URLSearchParams(new FormData(document.getElementById(
                        'reportFilters')));

                    Array.from(params.keys()).forEach(function(key) {
                        if (!params.get(key)) {
                            params.delete(key);
                        }
                    });

                    var query = params.toString();
                    window.location.href = button.dataset.export+(query ? '?' + query : '');
                });
            });
        </script>
    @endsection
