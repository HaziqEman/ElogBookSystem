<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use App\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class NotificationController extends Controller
{
    private const ROLES = ['student', 'lecturer', 'supervisor'];

    protected function role(Request $request): string
    {
        $role = (string) $request->segment(1);

        abort_unless(in_array($role, self::ROLES, true), 404);

        return $role;
    }

    protected function recipient(string $role): Model
    {
        $user = Auth::guard($role)->user();

        abort_unless($user !== null, 403);

        return $user;
    }

    protected function ownQuery(Model $user)
    {
        return UserNotification::where('recipient_type', get_class($user))
            ->where('recipient_id', $user->getKey());
    }

    public function index(Request $request)
    {
        $role = $this->role($request);
        $user = $this->recipient($role);
        $filter = $request->query('filter', 'all');

        if (! in_array($filter, ['all', 'unread', 'reminders', 'messages', 'entries'], true)) {
            $filter = 'all';
        }

        $records = collect();
        $readCount = 0;

        if (Schema::hasTable('user_notifications')) {
            $active = $this->ownQuery($user)->whereNull('dismissed_at');
            $readCount = (clone $active)->whereNotNull('read_at')->count();
            $records = $active
                ->orderByDesc('created_at')
                ->orderByDesc('notification_id')
                ->get();
        }

        $items = $this->inboxItems($records, $role);
        $allCount = $items->count();
        $unread = $items->where('unread', true)->count();
        $categoryCounts = [
            'reminders' => $items->where('category', 'reminders')->count(),
            'messages' => $items->where('category', 'messages')->count(),
            'entries' => $items->where('category', 'entries')->count(),
        ];

        $filtered = match ($filter) {
            'unread' => $items->where('unread', true),
            'reminders', 'messages', 'entries' => $items->where('category', $filter),
            default => $items,
        };
        $filtered = $filtered->values();

        $perPage = 20;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $pageItems = $filtered->forPage($currentPage, $perPage)->values();
        $query = $request->query();
        unset($query['partial']);
        $paginator = new LengthAwarePaginator(
            $pageItems,
            $filtered->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $query]
        );
        $nextUrl = $paginator->hasMorePages()
            ? $request->fullUrlWithQuery(['page' => $currentPage + 1, 'partial' => 1])
            : null;

        $viewData = compact(
            'role',
            'filter',
            'allCount',
            'unread',
            'readCount',
            'categoryCounts',
            'paginator',
            'pageItems',
            'nextUrl'
        );

        if ($request->boolean('partial')) {
            return view('notifications.partials.items', $viewData);
        }

        return view('notifications.index', $viewData);
    }

    public function unreadCount(Request $request)
    {
        $role = $this->role($request);
        $user = $this->recipient($role);

        if (! Schema::hasTable('user_notifications')) {
            return response()->json(['count' => 0]);
        }

        return response()->json([
            'count' => $this->ownQuery($user)->whereNull('dismissed_at')->whereNull('read_at')->count(),
        ]);
    }

    public function open(Request $request, $id)
    {
        $role = $this->role($request);
        $user = $this->recipient($role);

        $notification = $this->ownQuery($user)
            ->whereNull('dismissed_at')
            ->where('notification_id', $id)
            ->firstOrFail();

        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        $url = (string) $notification->url;

        return redirect(str_starts_with($url, '/') ? $url : "/{$role}/dashboard");
    }

    public function readAll(Request $request)
    {
        $role = $this->role($request);
        $user = $this->recipient($role);

        $this->ownQuery($user)
            ->whereNull('dismissed_at')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'All notifications marked as read.']);
        }

        return back();
    }

    public function readMany(Request $request)
    {
        $user = $this->recipient($this->role($request));
        $ids = $this->validatedOwnedIds($request, $user);

        $this->ownQuery($user)
            ->whereIn('notification_id', $ids)
            ->whereNull('dismissed_at')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->jsonReply($request, 'Notification marked as read.');
    }

    public function dismiss(Request $request)
    {
        $user = $this->recipient($this->role($request));
        $ids = $this->validatedOwnedIds($request, $user);

        $this->ownQuery($user)
            ->whereIn('notification_id', $ids)
            ->whereNull('dismissed_at')
            ->update(['dismissed_at' => now()]);

        return $this->jsonReply($request, 'Notification cleared.');
    }

    public function dismissRead(Request $request)
    {
        $user = $this->recipient($this->role($request));

        $this->ownQuery($user)
            ->whereNull('dismissed_at')
            ->whereNotNull('read_at')
            ->update(['dismissed_at' => now()]);

        return $this->jsonReply($request, 'Read notifications cleared.');
    }

    public function dismissAll(Request $request)
    {
        $user = $this->recipient($this->role($request));

        $this->ownQuery($user)
            ->whereNull('dismissed_at')
            ->update(['dismissed_at' => now()]);

        return $this->jsonReply($request, 'All notifications cleared.');
    }

    private function inboxItems(Collection $notifications, string $role): Collection
    {
        $weekNotifications = $role === 'student'
            ? collect()
            : $notifications->filter(fn (UserNotification $notification) => $notification->type === 'week'
                && preg_match('/^(weekremind|weeksent):(\d+):(\d+)$/', (string) $notification->group_key));
        $weeklyIds = $weekNotifications->pluck('notification_id')->map(fn ($id) => (string) $id)->flip();
        $groups = $weekNotifications->groupBy(function (UserNotification $notification) {
            preg_match('/^(weekremind|weeksent):(\d+):(\d+)$/', (string) $notification->group_key, $matches);

            return $matches[1].':'.$matches[2];
        });
        $studentIds = $groups->keys()
            ->map(fn (string $key) => (int) Str::after($key, ':'))
            ->unique()
            ->values();
        $studentNames = $studentIds->isEmpty()
            ? collect()
            : Student::whereIn('student_id', $studentIds)->pluck('name', 'student_id');

        $items = $notifications
            ->reject(fn (UserNotification $notification) => $weeklyIds->has((string) $notification->notification_id))
            ->map(fn (UserNotification $notification) => $this->singleInboxItem($notification))
            ->values();

        foreach ($groups as $key => $group) {
            [$kind, $studentId] = explode(':', $key, 2);
            $items->push($this->weeklyInboxItem(
                $group,
                $kind,
                $studentNames->get((int) $studentId, 'A student')
            ));
        }

        return $items->sort(function (array $left, array $right) {
            $leftTime = $left['when']?->getTimestamp() ?? 0;
            $rightTime = $right['when']?->getTimestamp() ?? 0;

            return $rightTime <=> $leftTime;
        })->values();
    }

    private function singleInboxItem(UserNotification $notification): array
    {
        $category = match ($notification->type) {
            'reminder' => 'reminders',
            'message' => 'messages',
            'week', 'review', 'task' => 'entries',
            default => null,
        };
        $label = match ($notification->type) {
            'reminder' => 'Reminder',
            'message' => 'Message',
            'week' => 'New entries',
            'review' => 'Activity',
            'task' => 'Task',
            default => 'Activity',
        };

        return [
            'ids' => [(int) $notification->notification_id],
            'type' => $notification->type,
            'category' => $category,
            'label' => $label,
            'title' => $notification->title,
            'body' => $notification->body,
            'url' => $notification->url,
            'when' => $notification->created_at,
            'unread' => $notification->read_at === null,
            'merged_count' => $notification->merged_count,
            'weeks' => [],
        ];
    }

    private function weeklyInboxItem(Collection $group, string $kind, string $studentName): array
    {
        $weeks = $group->map(function (UserNotification $notification) {
            preg_match('/^(?:weekremind|weeksent):\d+:(\d+)$/', (string) $notification->group_key, $matches);
            preg_match('/(\d+)\s+entries?/i', (string) $notification->body, $entryMatches);

            return [
                'week' => (int) ($matches[1] ?? 0),
                'entries' => (int) ($entryMatches[1] ?? 0),
                'url' => $notification->url,
            ];
        })->sortBy('week')->values();
        $weekNumbers = $weeks->pluck('week')->filter()->values();
        $weekCount = $weekNumbers->count();
        $entryCount = $weeks->sum('entries');
        $isReminder = $kind === 'weekremind';
        $latest = $group->sortByDesc(fn (UserNotification $notification) => $notification->created_at?->getTimestamp() ?? 0)->first();
        $firstWeek = $weeks->first();

        if ($isReminder) {
            $title = $studentName.' has '.$weekCount.' '.Str::plural('week', $weekCount).' waiting for review';
            $body = $entryCount.' '.Str::plural('entry', $entryCount).' not reviewed yet';
            if ($weekCount > 1) {
                $body .= ', oldest is Week '.$weekNumbers->first();
            }
            $category = 'reminders';
            $label = 'Reminder';
            $type = 'reminder';
        } else {
            $title = $studentName.' submitted '.$entryCount.' '.Str::plural('entry', $entryCount)
                .($weekCount === 1 ? ' for Week '.$weekNumbers->first() : ' across '.$weekCount.' weeks');
            $body = $weekCount === 1
                ? 'Ready for review.'
                : 'Weeks '.implode(', ', $weekNumbers->all()).' are ready for review.';
            $category = 'entries';
            $label = 'New entries';
            $type = 'week';
        }

        return [
            'ids' => $group->pluck('notification_id')->map(fn ($id) => (int) $id)->all(),
            'type' => $type,
            'category' => $category,
            'label' => $label,
            'title' => $title,
            'body' => $body,
            'url' => $firstWeek['url'] ?? $latest?->url,
            'when' => $latest?->created_at,
            'unread' => $group->contains(fn (UserNotification $notification) => $notification->read_at === null),
            'merged_count' => 1,
            'weeks' => $weeks->all(),
        ];
    }

    private function validatedOwnedIds(Request $request, Model $user): array
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|min:1',
        ]);
        $ids = array_values(array_unique(array_map('intval', $data['ids'])));
        $ownedCount = $this->ownQuery($user)
            ->whereNull('dismissed_at')
            ->whereIn('notification_id', $ids)
            ->count();

        abort_unless($ownedCount === count($ids), 404);

        return $ids;
    }

    private function jsonReply(Request $request, string $message)
    {
        if ($request->wantsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('success', $message);
    }
}
