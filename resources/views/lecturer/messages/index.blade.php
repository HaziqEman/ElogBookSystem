@extends('layouts.app')

@section('content')
@php
    $chat = [
        'conversation_id' => null,
        'me' => ['type' => \App\Models\Lecturer::class, 'role' => 'lecturer', 'id' => (int) $lecturer->lecturer_id, 'name' => $lecturer->name],
        'contacts' => $contacts,
        'list' => ['channel' => 'private-lecturer.'.$lecturer->lecturer_id, 'partnerType' => \App\Models\Student::class, 'openBase' => null],
        'placeholder' => 'Select a student to open the conversation.',
        'empty' => 'No students assigned yet.',
    ];
@endphp

<div class="space-y-4 -mt-4 md:-mt-8">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Messages</h1>
        <p class="text-sm text-slate-500">Chat with your students.</p>
    </div>

    @include('partials.chat-room', ['chat' => $chat])
</div>
@endsection