@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    .cal-day.is-today { color: #2F5BFF; background: #fff; box-shadow: inset 0 0 0 2px #2F5BFF; }
    .cal-day.is-sel { color: #fff; background: #2F5BFF; border-color: #2F5BFF; box-shadow: none; }
    .cal-day.is-sel .cal-dot { background: #fff !important; }
</style>

@php
    $pill = function (string $status) {
        if (in_array($status, ['Approved', 'Validated'], true)) {
            return 'bg-[#D1FAE5] text-[#065F46]';
        }
        if (in_array($status, ['Rejected', 'Revision Requested', 'Revision needed'], true)) {
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
    $supervisorAssigned = (bool) optional($student)->supervisor_id;
    $chatHref = $supervisorAssigned ? '/student/messages/supervisor' : '/student/messages';
    $firstName = \Illuminate\Support\Str::words((string) optional($student)->name, 2, '');
    $aiPct = $aiLimit > 0 ? (int) round($aiLeft / $aiLimit * 100) : 0;
    $aiColor = $aiLeft <= (int) ceil($aiLimit * 0.2) ? '#C2410C' : '#6D28D9';
    $card = 'rounded-[22px] border border-[#E3E8F2] bg-white shadow-[0_6px_18px_rgba(15,27,77,0.05)]';
    $firstMondayKey = $planner->hasStart() ? $planner->firstMonday()->toDateString() : '';
    $startKey = $planner->hasStart() ? $planner->start()->toDateString() : '';
    $workingDaysCsv = implode(',', $planner->workingDays());
@endphp

<div class="space-y-6" style="font-family:'Plus Jakarta Sans',ui-sans-serif,system-ui,sans-serif">

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


    @include('partials.reminder-popup')

    @include('student.partials.dashboard-overview')
    <!-- @include('student.partials.dashboard-logbook') -->
</div>

@include('student.partials.dashboard-modals')

@endsection
