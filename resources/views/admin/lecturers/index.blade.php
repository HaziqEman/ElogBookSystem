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

    $hasFilters = collect(request()->only(['q', 'faculty', 'state']))->filter()->isNotEmpty();
    $inputClass = 'w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500';
    $sortOptions = ['name' => 'Name A-Z', 'students' => 'Most students', 'newest' => 'Newest added'];
@endphp

<div class="space-y-5">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Lecturer Management</h2>
            <p class="mt-2 text-sm text-slate-500">Manage lecturer accounts and move their students to another lecturer when needed.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="/admin/lecturers/import" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                <i class="fa-solid fa-file-excel"></i> Import Excel
            </a>
            <a href="/admin/lecturers/create" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
                <i class="fa-solid fa-plus"></i> Add Lecturer
            </a>
        </div>
    </div>

    @include('admin.partials.flash')

    {{-- Quick filters --}}
    <div class="flex flex-wrap gap-2">
        @foreach($chips as $chip)
            <a href="/admin/lecturers{{ $chip['query'] ? '?'.http_build_query($chip['query']) : '' }}"
               class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold transition hover:shadow-sm {{ $chipTone[$chip['tone']] }} {{ $activeChip === $chip['key'] ? 'ring-2 ring-blue-500' : '' }}">
                {{ $chip['label'] }}
                <span class="rounded-full bg-white/80 px-2 py-0.5 text-[11px]">{{ $chip['count'] }}</span>
            </a>
        @endforeach
    </div>

    {{-- Search and filters --}}
    <x-admin.filter-shell action="/admin/lecturers" grid-class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4" :has-filters="$hasFilters" :per-page="$perPage" :sort-options="$sortOptions">
            <div class="relative lg:col-span-2">
                <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name, email or faculty" class="{{ $inputClass }} pl-9">
            </div>

            <select name="faculty" onchange="this.form.submit()" class="{{ $inputClass }}">
                <option value="">All faculties</option>
                @foreach($faculties as $faculty)
                    <option value="{{ $faculty }}" {{ request('faculty') === $faculty ? 'selected' : '' }}>{{ $faculty }}</option>
                @endforeach
            </select>

            <select name="state" onchange="this.form.submit()" class="{{ $inputClass }}">
                <option value="">Any workload</option>
                <option value="has_students" {{ request('state') === 'has_students' ? 'selected' : '' }}>With students</option>
                <option value="no_students" {{ request('state') === 'no_students' ? 'selected' : '' }}>No students</option>
            </select>
    </x-admin.filter-shell>

    {{-- Bulk actions --}}
    <x-admin.bulk-actions :action="'/admin/lecturers/bulk?'.http_build_query(request()->except('page'))" :input-class="$inputClass" noun="lecturers" :total="$lecturers->total()">
        <x-slot:actions>
            <option value="transfer_students">Move their students to another lecturer</option>
            <option value="set_faculty">Set faculty</option>
            <option value="delete" data-danger="1" data-confirm="Delete {n} lecturers? Lecturers who still have students will be kept.">Delete lecturers</option>
        </x-slot:actions>
        <x-slot:fields>
                    <div data-panel="transfer_students" class="hidden">
                        <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Move all their students to</label>
                        <select name="to_lecturer_id" class="{{ $inputClass }} sm:max-w-md">
                            @foreach($allLecturers as $option)
                                <option value="{{ $option->lecturer_id }}">{{ $option->name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-xs text-slate-500">Use this before deleting a lecturer. Pick a lecturer who is not one of the selected ones.</p>
                    </div>

                    <div data-panel="set_faculty" class="hidden">
                        <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Faculty</label>
                        <input type="text" name="faculty" maxlength="255" placeholder="e.g. FSKM" class="{{ $inputClass }} sm:max-w-md">
                    </div>

                    <div data-panel="delete" class="hidden">
                        <p class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700">
                            Lecturers who still have students are skipped. Move their students first.
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
                            <input type="checkbox" id="select-all" class="h-4 w-4 rounded border-slate-300" aria-label="Select all lecturers on this page">
                        </th>
                        <th class="px-4 py-3 text-left">Lecturer</th>
                        <th class="px-4 py-3 text-left">Faculty</th>
                        <th class="px-4 py-3 text-left">Students</th>
                        <th class="px-4 py-3 text-left">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                    @forelse($lecturers as $lecturer)
                        <tr class="transition hover:bg-slate-50/70">
                            <td class="px-4 py-4">
                                <input type="checkbox" form="bulk-form" name="ids[]" value="{{ $lecturer->lecturer_id }}" class="row-check h-4 w-4 rounded border-slate-300" aria-label="Select {{ $lecturer->name }}">
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-semibold text-slate-800">{{ $lecturer->name }}</p>
                                <p class="text-xs text-slate-400">{{ $lecturer->email }}</p>
                            </td>
                            <td class="px-4 py-4">
                                @if($lecturer->faculty)
                                    {{ $lecturer->faculty }}
                                @else
                                    <span class="text-slate-400">Not set</span>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                @if($lecturer->students_count > 0)
                                    <a href="/admin/students?lecturer={{ $lecturer->lecturer_id }}" class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700 transition hover:bg-blue-200" title="Show these students">
                                        {{ $lecturer->students_count }} {{ $lecturer->students_count === 1 ? 'student' : 'students' }}
                                    </a>
                                @else
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">No students</span>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <x-admin.row-actions
                                    :delete-confirmation="'Delete this lecturer?'"
                                    :delete-disabled="$lecturer->students_count > 0"
                                    :delete-disabled-title="'Move their '.$lecturer->students_count.' students to another lecturer first'"
                                    :delete-url="'/admin/lecturers/'.$lecturer->lecturer_id"
                                    :edit-url="'/admin/lecturers/'.$lecturer->lecturer_id.'/edit'"
                                    :name="$lecturer->name"
                                    :reset-confirmation="'Reset the password for '.$lecturer->name.'? A new temporary password will be shown once.'"
                                    :reset-url="'/admin/lecturers/'.$lecturer->lecturer_id.'/reset-password'"
                                />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-slate-500">
                                No lecturers found.
                                @if($hasFilters)
                                    <a href="/admin/lecturers" class="font-semibold text-blue-600 hover:underline">Clear filters</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="flex justify-end">
        {{ $lecturers->links() }}
    </div>
</div>

@include('admin.partials.bulk-js')
@endsection
