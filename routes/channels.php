<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Auth;
use App\Models\Conversation;

// Authorize private conversation channel subscriptions.
Broadcast::channel('private-conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::find($conversationId);

    if (! $conversation) {
        return false;
    }

    $student = Auth::guard('student')->user();
    if ($student && (int) $student->student_id === (int) $conversation->student_id) {
        return true;
    }

    $lecturer = Auth::guard('lecturer')->user();
    if ($lecturer && $conversation->lecturer_id && (int) $lecturer->lecturer_id === (int) $conversation->lecturer_id) {
        return true;
    }

    $supervisor = Auth::guard('supervisor')->user();
    if ($supervisor && $conversation->supervisor_id && (int) $supervisor->supervisor_id === (int) $conversation->supervisor_id) {
        return true;
    }

    return false;
});

Broadcast::channel('presence-conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::find($conversationId);

    if (! $conversation) {
        return false;
    }

    $student = Auth::guard('student')->user();
    if ($student && (int) $student->student_id === (int) $conversation->student_id) {
        return [
            'id' => (int) $student->student_id,
            'name' => $student->name,
            'role' => 'student',
        ];
    }

    $lecturer = Auth::guard('lecturer')->user();
    if ($lecturer && $conversation->lecturer_id && (int) $lecturer->lecturer_id === (int) $conversation->lecturer_id) {
        return [
            'id' => (int) $lecturer->lecturer_id,
            'name' => $lecturer->name,
            'role' => 'lecturer',
        ];
    }

    $supervisor = Auth::guard('supervisor')->user();
    if ($supervisor && $conversation->supervisor_id && (int) $supervisor->supervisor_id === (int) $conversation->supervisor_id) {
        return [
            'id' => (int) $supervisor->supervisor_id,
            'name' => $supervisor->name,
            'role' => 'supervisor',
        ];
    }

    return false;
});

// Lecturer-wide channel (conversation list updates)
Broadcast::channel('private-lecturer.{lecturerId}', function ($user, $lecturerId) {
    $lecturer = Auth::guard('lecturer')->user();

    return (bool) ($lecturer && (int) $lecturer->lecturer_id === (int) $lecturerId);
});

// Supervisor-wide channel (conversation list updates)
Broadcast::channel('private-supervisor.{supervisorId}', function ($user, $supervisorId) {
    $supervisor = Auth::guard('supervisor')->user();

    return (bool) ($supervisor && (int) $supervisor->supervisor_id === (int) $supervisorId);
});