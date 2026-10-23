@extends('layouts.app')

@section('content')
@php
    $student = $conversation->student;
    $chat = [
        'conversation_id' => $conversation->conversation_id,
        'me' => ['type' => \App\Models\Lecturer::class, 'role' => 'lecturer', 'id' => (int) $lecturer->lecturer_id, 'name' => $lecturer->name],
        'partner' => [
            'role' => 'student',
            'title' => $student->name,
            'name' => 'Student',
            'sub' => 'ID '.$student->student_id,
            'initials' => strtoupper(mb_substr($student->name, 0, 2)),
            'tone' => 'blue',
        ],
        'contacts' => [],
        'back' => ['href' => '/lecturer/messages', 'label' => 'All conversations'],
        'messages' => $conversation->messages->map(fn ($m) => $m->toChatPayload())->values()->all(),
    ];
@endphp

<div class="space-y-4">
    @include('partials.chat-room', ['chat' => $chat])
</div>
@endsection
