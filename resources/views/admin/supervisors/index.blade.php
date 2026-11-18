@extends('layouts.app')

@section('content')
@php
    $chipTone = [
        'slate' => 'bg-slate-100 text-slate-700 border-slate-200',
        'emerald' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'sky' => 'bg-sky-50 text-sky-700 border-sky-200',
        'amber' => 'bg-amber-50 text-amber-800 border-amber-200',
        'rose' => 'bg-rose-50 text-rose-700 border-rose-200',
    ];

    $hasFilters = collect(request()->only(['q', 'company', 'state']))->filter()->isNotEmpty();
    $inputClass = 'w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500';
    $sortOptions = ['name' => 'Name A-Z', 'company' => 'Company', 'students' => 'Most students', 'newest' => 'Newest added'];
@endphp

<div class="space-y-5">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Company Supervisor Management</h2>
            <p class="mt-2 text-sm text-slate-500">Create supervisor accounts, see who has reviews waiting, and move students between supervisors.</p>
        </div>
        <a href="/admin/supervisors/create" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
            <i class="fa-solid fa-plus"></i> Add Supervisor
        </a>
    </div>

    @include('admin.partials.flash')

    {{-- Quick filters --}}
    <div class="flex flex-wrap gap-2">
        @foreach($chips as $chip)
            <a href="/admin/supervisors{{ $chip['query'] ? '?'.http_build_query($chip['query']) : '' }}"
               class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold transition hover:shadow-sm {{ $chipTone[$chip['tone']] }} {{ $activeChip === $chip['key'] ? 'ring-2 ring-blue-500' : '' }}">
                {{ $chip['label'] }}
                <span class="rounded-full bg-white/80 px-2 py-0.5 text-[11px]">{{ $chip['count'] }}</span>
            </a>
        @endforeach
    </div>

    {{-- Search and filters --}}
    <x-admin.filter-shell action="/admin/supervisors" grid-class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4" :has-filters="$hasFilters" :per-page="$perPage" :sort-options="$sortOptions">
            <div class="relative lg:col-span-2">
                <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name, email, company or position" class="{{ $inputClass }} pl-9">
            </div>

            <select name="company" onchange="this.form.submit()" class="{{ $inputClass }}">
                <option value="">All companies</option>
                @foreach($companies as $company)
                    <option value="{{ $company }}" {{ request('company') === $company ? 'selected' : '' }}>{{ $company }}</option>
                @endforeach
            </select>

            <select name="state" onchange="this.form.submit()" class="{{ $inputClass }}">
                <option value="">Any status</option>
                <option value="waiting" {{ request('state') === 'waiting' ? 'selected' : '' }}>Reviews waiting</option>
                <option value="no_students" {{ request('state') === 'no_students' ? 'selected' : '' }}>No students</option>
                <option value="temp_password" {{ request('state') === 'temp_password' ? 'selected' : '' }}>Temporary password</option>
            </select>
    </x-admin.filter-shell>

    {{-- Bulk actions --}}
    <x-admin.bulk-actions :action="'/admin/supervisors/bulk?'.http_build_query(request()->except('page'))" :input-class="$inputClass" noun="supervisors" :total="$supervisors->total()">
        <x-slot:actions>
            <option value="transfer_students">Move their students to another supervisor</option>
            <option value="set_company">Set company name</option>
            <option value="force_password_change">Require password change at next login</option>
            <option value="delete" data-danger="1" data-confirm="Delete {n} supervisors? Their students will become unassigned.">Delete supervisors</option>
        </x-slot:actions>
        <x-slot:fields>
                    <div data-panel="transfer_students" class="hidden">
                        <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Move all their students to</label>
                        <select name="to_supervisor_id" class="{{ $inputClass }} sm:max-w-md">
                            @foreach($allSupervisors as $option)
                                <option value="{{ $option->supervisor_id }}">{{ $option->name }}{{ $option->company_name ? ' - '.$option->company_name : '' }}</option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-xs text-slate-500">Pick a supervisor who is not one of the selected ones.</p>
                    </div>

                    <div data-panel="set_company" class="hidden">
                        <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Company name</label>
                        <input type="text" name="company_name" maxlength="255" placeholder="e.g. Northstar Digital" class="{{ $inputClass }} sm:max-w-md">
                    </div>

                    <div data-panel="force_password_change" class="hidden">
                        <p class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                            The selected supervisors keep their current password, but must choose a new one the next time they log in.
                        </p>
                    </div>

                    <div data-panel="delete" class="hidden">
                        <p class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700">
                            Their students are kept but become unassigned, so their logbook entries will have no reviewer until you assign a new supervisor.
                        </p>
                    </div>
        </x-slot:fields>
    </x-admin.bulk-actions>

    {{-- Table --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="w-10 px-4 py-3 text-left">
                            <input type="checkbox" id="select-all" class="h-4 w-4 rounded border-slate-300" aria-label="Select all supervisors on this page">
                        </th>
                        <th class="px-4 py-3 text-left">Supervisor</th>
                        <th class="px-4 py-3 text-left">Company</th>
                        <th class="px-4 py-3 text-left">Students</th>
                        <th class="px-4 py-3 text-left">Reviews</th>
                        <th class="px-4 py-3 text-left">Password</th>
                        <th class="px-4 py-3 text-left">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                    @forelse($supervisors as $supervisor)
                        <tr class="transition hover:bg-slate-50/70">
                            <td class="px-4 py-4">
                                <input type="checkbox" form="bulk-form" name="ids[]" value="{{ $supervisor->supervisor_id }}" class="row-check h-4 w-4 rounded border-slate-300" aria-label="Select {{ $supervisor->name }}">
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-semibold text-slate-800">{{ $supervisor->name }}</p>
                                <p class="text-xs text-slate-400">{{ $supervisor->email }}</p>
                                @if($supervisor->phone)
                                    <p class="text-xs text-slate-400">{{ $supervisor->phone }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                {{ $supervisor->company_name }}
                                <span class="block text-xs text-slate-400">{{ $supervisor->position ?: '-' }}</span>
                            </td>
                            <td class="px-4 py-4">
                                @if($supervisor->students_count > 0)
                                    <a href="/admin/students?supervisor={{ $supervisor->supervisor_id }}" class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700 transition hover:bg-blue-200" title="Show these students">
                                        {{ $supervisor->students_count }} {{ $supervisor->students_count === 1 ? 'student' : 'students' }}
                                    </a>
                                @else
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">No students</span>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                @if($supervisor->waiting_count > 0)
                                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">{{ $supervisor->waiting_count }} waiting</span>
                                @elseif($supervisor->students_count > 0)
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Up to date</span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                @if($supervisor->must_change_password)
                                    <span class="rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-700">Temporary</span>
                                @else
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Set by user</span>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <x-admin.row-actions
                                    :delete-confirmation="'Remove this supervisor? Their students will become unassigned.'"
                                    :delete-url="'/admin/supervisors/'.$supervisor->supervisor_id"
                                    :edit-url="'/admin/supervisors/'.$supervisor->supervisor_id.'/edit'"
                                    :name="$supervisor->name"
                                    :reset-confirmation="'Reset the password for '.$supervisor->name.'? A new temporary password will be shown once.'"
                                    :reset-url="'/admin/supervisors/'.$supervisor->supervisor_id.'/reset-password'"
                                />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-slate-500">
                                No company supervisors found.
                                @if($hasFilters)
                                    <a href="/admin/supervisors" class="font-semibold text-blue-600 hover:underline">Clear filters</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="flex justify-end">
        {{ $supervisors->links() }}
    </div>
</div>

@include('admin.partials.bulk-js')
@endsection
