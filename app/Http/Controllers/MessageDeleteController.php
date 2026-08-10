<?php

namespace App\Http\Controllers;

use App\Events\MessageDeleted;
use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageDeleteController extends Controller
{
    public function deleteForMe(Request $request, $conversationId, $messageId)
    {
        $student = Auth::guard('student')->user();
        $lecturer = Auth::guard('lecturer')->user();

        if (! $student && ! $lecturer) {
            abort(403);
        }

        $message = Message::where('message_id', $messageId)
            ->where('conversation_id', $conversationId)
            ->firstOrFail();

        $conversation = Conversation::where('conversation_id', $conversationId)->firstOrFail();

        if ($student) {
            if ((int) $conversation->student_id !== (int) $student->student_id) {
                abort(403);
            }
            $message->update(['deleted_for_student' => true]);
        } else {
            if ((int) $conversation->lecturer_id !== (int) $lecturer->lecturer_id) {
                abort(403);
            }
            $message->update(['deleted_for_lecturer' => true]);
        }

        return response()->json(['success' => true]);
    }

    public function deleteForEveryone(Request $request, $conversationId, $messageId)
    {
        $student = Auth::guard('student')->user();
        $lecturer = Auth::guard('lecturer')->user();

        if (! $student && ! $lecturer) {
            abort(403);
        }

        $message = Message::where('message_id', $messageId)
            ->where('conversation_id', $conversationId)
            ->firstOrFail();

        $conversation = Conversation::where('conversation_id', $conversationId)->firstOrFail();

        $studentIsActor = false;
        $lecturerIsActor = false;

        if ($student && ! $lecturer) {
            $studentIsActor = true;
        } elseif ($lecturer && ! $student) {
            $lecturerIsActor = true;
        } elseif ($student && $lecturer) {
            if ($message->sender_type === \App\Models\Student::class
                && (int) $message->sender_id === (int) $student->student_id
                && (int) $conversation->student_id === (int) $student->student_id
            ) {
                $studentIsActor = true;
            } elseif ($message->sender_type === \App\Models\Lecturer::class
                && (int) $message->sender_id === (int) $lecturer->lecturer_id
                && (int) $conversation->lecturer_id === (int) $lecturer->lecturer_id
            ) {
                $lecturerIsActor = true;
            }
        }

        if ($studentIsActor) {
            if ((int) $conversation->student_id !== (int) $student->student_id) {
                abort(403);
            }
            $expectedSenderClass = get_class($student);
            $expectedSenderId = (int) $student->student_id;
        } elseif ($lecturerIsActor) {
            if ((int) $conversation->lecturer_id !== (int) $lecturer->lecturer_id) {
                abort(403);
            }
            $expectedSenderClass = get_class($lecturer);
            $expectedSenderId = (int) $lecturer->lecturer_id;
        } else {
            abort(403);
        }

        if ($message->sender_type !== $expectedSenderClass || (int) $message->sender_id !== $expectedSenderId) {
            abort(403);
        }

        $message->update([
            'deleted_at' => now(),
            'deleted_for_student' => true,
            'deleted_for_lecturer' => true,
        ]);

        broadcast(new MessageDeleted($message))->toOthers();

        return response()->json(['success' => true]);
    }
}
