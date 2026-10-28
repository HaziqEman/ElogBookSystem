<?php

namespace App\Http\Controllers;

use App\Events\MessageDeleted;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageDeleteController extends Controller
{
    public function deleteForMe(Request $request, $conversationId, $messageId)
    {
        $conversation = Conversation::where('conversation_id', $conversationId)->firstOrFail();

        $message = Message::where('message_id', $messageId)
            ->where('conversation_id', $conversation->conversation_id)
            ->firstOrFail();

        $actor = $this->participant($conversation, $this->roleFromUrl($request));

        if (! $actor) {
            abort(403);
        }

        $message->update([$actor['flag'] => true]);
        app(\App\Services\ChatUploader::class)->destroy($message); 

        return response()->json(['success' => true]);
    }

    public function deleteForEveryone(Request $request, $conversationId, $messageId)
    {
        $conversation = Conversation::where('conversation_id', $conversationId)->firstOrFail();

        $message = Message::where('message_id', $messageId)
            ->where('conversation_id', $conversation->conversation_id)
            ->firstOrFail();

        $actor = $this->participant($conversation, $this->roleFromUrl($request));

        // Only the person who sent a message may delete it for everyone.
        if (! $actor
            || $message->sender_type !== get_class($actor['user'])
            || (int) $message->sender_id !== $actor['id']
        ) {
            abort(403);
        }

        $message->update([
            'deleted_at' => now(),
            'deleted_for_student' => true,
            'deleted_for_lecturer' => true,
            'deleted_for_supervisor' => true,
        ]);

        broadcast(new MessageDeleted($message))->toOthers();

        return response()->json(['success' => true]);
    }

    /** /chat/{role}/... URLs name the portal the user is on; the old /conversations/... URLs return null. */
    protected function roleFromUrl(Request $request): ?string
    {
        $role = $request->segment(1) === 'chat' ? $request->segment(2) : null;

        return in_array($role, ['student', 'lecturer', 'supervisor'], true) ? $role : null;
    }

    /**
     * Which logged-in user is a participant of this conversation, and which "deleted for me" column is theirs.
     */
    protected function participant(Conversation $conversation, ?string $role = null): ?array
    {
        $student = (! $role || $role === 'student') ? Auth::guard('student')->user() : null;
        if ($student && (int) $student->student_id === (int) $conversation->student_id) {
            return ['user' => $student, 'id' => (int) $student->student_id, 'flag' => 'deleted_for_student'];
        }

        $lecturer = (! $role || $role === 'lecturer') ? Auth::guard('lecturer')->user() : null;
        if ($lecturer && $conversation->lecturer_id && (int) $lecturer->lecturer_id === (int) $conversation->lecturer_id) {
            return ['user' => $lecturer, 'id' => (int) $lecturer->lecturer_id, 'flag' => 'deleted_for_lecturer'];
        }

        $supervisor = (! $role || $role === 'supervisor') ? Auth::guard('supervisor')->user() : null;
        if ($supervisor && $conversation->supervisor_id && (int) $supervisor->supervisor_id === (int) $conversation->supervisor_id) {
            return ['user' => $supervisor, 'id' => (int) $supervisor->supervisor_id, 'flag' => 'deleted_for_supervisor'];
        }

        return null;
    }
}