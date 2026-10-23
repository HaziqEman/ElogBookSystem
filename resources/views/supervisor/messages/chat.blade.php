@extends('layouts.app')

@section('content')
@php
    $student = $conversation->student;
    $chat = [
        'conversation_id' => $conversation->conversation_id,
        'me' => ['type' => \App\Models\Supervisor::class, 'role' => 'supervisor', 'id' => (int) $supervisor->supervisor_id, 'name' => $supervisor->name],
        'partner' => [
            'role' => 'student',
            'title' => $student->name,
            'name' => 'Student',
            'sub' => 'Matric '.$student->matric_no,
            'initials' => strtoupper(mb_substr($student->name, 0, 2)),
            'tone' => 'blue',
        ],
        'contacts' => $contacts,
        'list' => ['channel' => 'private-supervisor.'.$supervisor->supervisor_id, 'partnerType' => \App\Models\Student::class, 'openBase' => '/supervisor/messages'],
        'empty' => 'No students assigned yet.',
        'back' => ['href' => '/supervisor/messages', 'label' => 'All conversations'],
        'messages' => $messages->map(fn ($m) => $m->toChatPayload())->values()->all(),
    ];
@endphp

<div class="space-y-4">
    @include('partials.chat-room', ['chat' => $chat])
</div>
@endsection
