@extends('layouts.app')

@section('content')
@php
    $chat = [
        'conversation_id' => null,
        'me' => ['type' => \App\Models\Supervisor::class, 'role' => 'supervisor', 'id' => (int) $supervisor->supervisor_id, 'name' => $supervisor->name],
        'contacts' => $contacts,
        'list' => ['channel' => 'private-supervisor.'.$supervisor->supervisor_id, 'partnerType' => \App\Models\Student::class, 'openBase' => '/supervisor/messages'],
        'placeholder' => 'Select a student to open the conversation.',
        'empty' => 'No students assigned yet.',
    ];
@endphp

<div class="space-y-4 -mt-4 md:-mt-8">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Messages</h1>
        <p class="text-sm text-slate-500">Chat with your interns.</p>
    </div>

    @include('partials.chat-room', ['chat' => $chat])
</div>
@endsection