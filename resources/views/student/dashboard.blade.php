@extends('layouts.app')

@section('content')
<style>
    .cal-day.is-today { background: #eff6ff; }
    .cal-day.is-today .cal-date { color: #fff; background: #2F5BFF; }
    .cal-day.is-sel { background: #eff6ff; border-color: #2F5BFF; box-shadow: inset 0 0 0 1px #2F5BFF; }
    .cal-day.is-sel .cal-date { color: #fff; background: #2F5BFF; }
</style>

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

    $todayKey = $today->toDateString();
    $firstName = \Illuminate\Support\Str::words((string) optional($student)->name, 2, '');
    $firstMondayKey = $planner->hasStart() ? $planner->firstMonday()->toDateString() : '';
    $startKey = $planner->hasStart() ? $planner->start()->toDateString() : '';
    $workingDaysCsv = implode(',', $planner->workingDays());
@endphp

<div class="space-y-4 sm:space-y-6">

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
    @endif

    @if($errors->any() && ! old('day_off_type'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @include('student.dashboard.overview')
</div>

@include('student.dashboard.modals')

@endsection
