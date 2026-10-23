<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Lecturer;
use App\Models\Message;
use App\Models\Student;
use App\Models\Supervisor;
use Illuminate\Support\Str;

/** Builds the left-hand conversation list (students) for the lecturer and supervisor chat pages. */
class ChatContacts
{
    public static function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $first = mb_substr($parts[0] ?? '?', 0, 1);
        $second = isset($parts[1]) ? mb_substr($parts[1], 0, 1) : mb_substr($parts[0] ?? '', 1, 1);

        return mb_strtoupper($first . $second);
    }

    protected function preview(?Message $m, string $meType): string
    {
        if (! $m) {
            return '';
        }
        if ($m->deleted_at) {
            return 'This message was deleted';
        }

        $text = Str::limit($m->preview(), 60);

        return $m->sender_type === $meType ? 'You: ' . $text : $text;
    }

    /** Latest message of each conversation, keyed by conversation_id. */
    protected function lastMessages($conversationIds)
    {
        if ($conversationIds->isEmpty()) {
            return collect();
        }

        return Message::whereIn('message_id', Message::query()
                ->selectRaw('MAX(message_id)')
                ->whereIn('conversation_id', $conversationIds)
                ->groupBy('conversation_id'))
            ->get()
            ->keyBy('conversation_id');
    }

    public function forLecturer(Lecturer $lecturer, ?int $activeConversationId = null): array
    {
        $convs = Conversation::with('student')
            ->where('lecturer_id', $lecturer->lecturer_id)
            ->withCount(['messages as unread_count' => fn ($q) => $q->where('sender_type', Student::class)->whereNull('read_at')])
            ->get()
            ->filter(fn ($c) => $c->student);

        $last = $this->lastMessages($convs->pluck('conversation_id'));

        return $convs
            ->sortByDesc(fn ($c) => optional($last->get($c->conversation_id)?->created_at)->timestamp ?? 0)
            ->map(function ($c) use ($last, $activeConversationId) {
                $lm = $last->get($c->conversation_id);

                return [
                    'title'           => $c->student->name,
                    'name'            => null,
                    'initials'        => self::initials($c->student->name),
                    'tone'            => 'blue',
                    'href'            => '/lecturer/messages/' . $c->conversation_id,
                    'active'          => $activeConversationId === (int) $c->conversation_id,
                    'student_id'      => (int) $c->student->student_id,
                    'conversation_id' => (int) $c->conversation_id,
                    'preview'         => $this->preview($lm, Lecturer::class),
                    'time_iso'        => optional($lm?->created_at)->toIso8601String(),
                    'unread'          => (int) $c->unread_count,
                ];
            })
            ->values()
            ->all();
    }

    public function forSupervisor(Supervisor $supervisor, ?int $activeConversationId = null): array
    {
        $students = Student::where('supervisor_id', $supervisor->supervisor_id)->orderBy('name')->get();

        $convs = Conversation::where('supervisor_id', $supervisor->supervisor_id)
            ->withCount(['messages as unread_count' => fn ($q) => $q->where('sender_type', Student::class)->whereNull('read_at')])
            ->get()
            ->keyBy('student_id');

        $last = $this->lastMessages($convs->pluck('conversation_id'));

        return $students
            ->sortByDesc(function ($s) use ($convs, $last) {
                $c = $convs->get($s->student_id);

                return $c ? (optional($last->get($c->conversation_id)?->created_at)->timestamp ?? 0) : 0;
            })
            ->map(function ($s) use ($convs, $last, $activeConversationId) {
                $c = $convs->get($s->student_id);
                $lm = $c ? $last->get($c->conversation_id) : null;

                return [
                    'title'           => $s->name,
                    'name'            => null,
                    'initials'        => self::initials($s->name),
                    'tone'            => 'green',
                    'href'            => $c ? '/supervisor/messages/' . $c->conversation_id : '/supervisor/messages/with/' . $s->student_id,
                    'active'          => $c && $activeConversationId === (int) $c->conversation_id,
                    'student_id'      => (int) $s->student_id,
                    'conversation_id' => $c ? (int) $c->conversation_id : null,
                    'preview'         => $this->preview($lm, Supervisor::class),
                    'time_iso'        => optional($lm?->created_at)->toIso8601String(),
                    'unread'          => $c ? (int) $c->unread_count : 0,
                ];
            })
            ->values()
            ->all();
    }
}
