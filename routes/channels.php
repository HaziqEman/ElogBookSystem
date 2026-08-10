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

    // Check student guard
    $student = Auth::guard('student')->user();
    if ($student && $student->student_id === $conversation->student_id) {
        return true;
    }

    // Check lecturer guard
    $lecturer = Auth::guard('lecturer')->user();
    if ($lecturer && $lecturer->lecturer_id === $conversation->lecturer_id) {
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
    if ($lecturer && (int) $lecturer->lecturer_id === (int) $conversation->lecturer_id) {
        return [
            'id' => (int) $lecturer->lecturer_id,
            'name' => $lecturer->name,
            'role' => 'lecturer',
        ];
    }

    return false;
});

// Authorize private channel for lecturer-wide notifications (conversation list updates)
Broadcast::channel('private-lecturer.{lecturerId}', function ($user, $lecturerId) {
    $lecturer = Auth::guard('lecturer')->user();

    if ($lecturer && $lecturer->lecturer_id == $lecturerId) {
        return true;
    }

    return false;
});
