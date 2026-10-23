@extends('layouts.app')

@section('content')
@php
    $isLecturer = $partnerRole === 'lecturer';
    $lect = $student->lecturer;
    $sup = $student->supervisor;

    $chat = [
        'conversation_id' => $conversation->conversation_id,
        'me' => ['type' => \App\Models\Student::class, 'role' => 'student', 'id' => (int) $student->student_id, 'name' => $student->name],
        'partner' => [
            'role' => $partnerRole,
            'title' => $isLecturer ? 'Lecturer' : 'Company Supervisor',
            'name' => $partner->name,
            'sub' => $isLecturer ? null : ($partner->company_name ?? null),
            'initials' => $isLecturer ? 'LE' : 'CS',
            'tone' => $isLecturer ? 'blue' : 'green',
        ],
        'contacts' => [
            ['title' => 'Lecturer', 'name' => $lect?->name, 'initials' => 'LE', 'tone' => 'blue', 'href' => '/student/messages', 'active' => $isLecturer, 'disabled' => ! $lect],
            ['title' => 'Company Supervisor', 'name' => $sup?->name, 'initials' => 'CS', 'tone' => 'green', 'href' => '/student/messages/supervisor', 'active' => ! $isLecturer, 'disabled' => ! $sup],
        ],
        'note' => 'You can only chat with your assigned lecturer and company supervisor.',
        'back' => null,
        'tabs' => true,
        'messages' => $messages->map(fn ($m) => $m->toChatPayload())->values()->all(),
    ];
@endphp

<div class="space-y-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Messages</h1>
        <p class="text-sm text-slate-500">Chat with your lecturer or company supervisor.</p>
    </div>

    @if(session('error'))
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
    @endif

    @include('partials.chat-room', ['chat' => $chat])
</div>
@endsection
