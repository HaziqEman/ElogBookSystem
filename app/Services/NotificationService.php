<?php

namespace App\Services;

use App\Models\Lecturer;
use App\Models\Logbook;
use App\Models\Message;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\UserNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class NotificationService
{
    /**
     * Save a notification for a student, lecturer or supervisor.
     *
     * $groupKey with $merge = true: while the previous one is unread, update it and raise its count.
     * $groupKey with $merge = false: send only once per key (used for daily reminders).
     */
    public function notify(
        Model $recipient,
        string $type,
        string $title,
        ?string $body = null,
        ?string $url = null,
        ?string $groupKey = null,
        bool $merge = false
    ): ?UserNotification {
        try {
            if (! Schema::hasTable('user_notifications')) {
                return null;
            }

            if ($groupKey) {
                $existing = UserNotification::where('recipient_type', get_class($recipient))
                    ->where('recipient_id', $recipient->getKey())
                    ->where('group_key', $groupKey)
                    ->orderByDesc('notification_id')
                    ->first();

                if ($existing) {
                    if ($merge && $existing->read_at === null) {
                        $existing->update([
                            'merged_count' => $existing->merged_count + 1,
                            'title' => $title,
                            'body' => $body,
                        ]);

                        return $existing;
                    }

                    if (! $merge) {
                        return null;
                    }
                }
            }

            // Push notifications (Firebase) will be sent from here in the next step.
            return UserNotification::create([
                'recipient_type' => get_class($recipient),
                'recipient_id' => $recipient->getKey(),
                'type' => $type,
                'title' => Str::limit($title, 250, ''),
                'body' => $body,
                'url' => $url,
                'group_key' => $groupKey,
                'merged_count' => 1,
            ]);
        } catch (\Throwable $e) {
            Log::error('Notification failed: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Tell the student that a lecturer or company supervisor reviewed an entry.
     */
    public function reviewGiven(Logbook $logbook, string $reviewerRole, string $reviewerName, string $decision, ?string $comment = null): void
    {
        try {
            $logbook->loadMissing('student');
            $student = $logbook->student;

            if (! $student) {
                return;
            }

            $week = $logbook->week_no;
            $who = $reviewerRole === 'supervisor' ? 'Your company supervisor' : 'Your lecturer';

            $titles = [
                'Approved' => "{$who} approved Week {$week}",
                'Rejected' => "{$who} rejected Week {$week} - action needed",
                'Validated' => "{$who} validated Week {$week}",
                'Revision Requested' => "{$who} asked for a revision on Week {$week}",
            ];

            $needsAction = in_array($decision, ['Rejected', 'Revision Requested'], true);
            $body = $reviewerName.(filled($comment) ? ': '.Str::limit(trim($comment), 120) : '');
            $url = $needsAction ? '/student/logbook/edit/'.$logbook->logbook_id : '/student/feedback';

            $this->notify($student, 'review', $titles[$decision] ?? "{$who} reviewed Week {$week}", $body, $url);
        } catch (\Throwable $e) {
            Log::error('Review notification failed: '.$e->getMessage());
        }
    }

    /**
     * Tell the other person in a conversation that a new message arrived.
     */
    public function messageReceived(Message $message): void
    {
        try {
            $message->loadMissing('conversation.student', 'conversation.lecturer', 'conversation.supervisor');
            $conversation = $message->conversation;

            if (! $conversation) {
                return;
            }

            $senderName = optional($message->sender)->name ?? 'Someone';
            $preview = Str::limit(trim((string) $message->message), 80);

            if ($message->sender_type === Student::class) {
                $recipient = $conversation->lecturer ?: $conversation->supervisor;

                if (! $recipient) {
                    return;
                }

                $url = $recipient instanceof Lecturer
                    ? '/lecturer/messages/'.$conversation->conversation_id
                    : '/supervisor/messages/'.$conversation->conversation_id;
            } else {
                $recipient = $conversation->student;

                if (! $recipient) {
                    return;
                }

                $url = $message->sender_type === Supervisor::class ? '/student/messages/supervisor' : '/student/messages';
            }

            $this->notify(
                $recipient,
                'message',
                "New message from {$senderName}",
                $preview,
                $url,
                'chat:'.$conversation->conversation_id,
                true
            );
        } catch (\Throwable $e) {
            Log::error('Message notification failed: '.$e->getMessage());
        }
    }
}