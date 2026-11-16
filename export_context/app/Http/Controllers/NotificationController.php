<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

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

        abort_unless($user, 403);

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

        if (! Schema::hasTable('user_notifications')) {
            $notifications = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20);

            return view('notifications.index', ['role' => $role, 'notifications' => $notifications, 'unread' => 0]);
        }

        $notifications = $this->ownQuery($user)
            ->orderByDesc('updated_at')
            ->orderByDesc('notification_id')
            ->paginate(20);

        $unread = $this->ownQuery($user)->whereNull('read_at')->count();

        return view('notifications.index', compact('role', 'notifications', 'unread'));
    }

    public function unreadCount(Request $request)
    {
        $role = $this->role($request);
        $user = $this->recipient($role);

        if (! Schema::hasTable('user_notifications')) {
            return response()->json(['count' => 0]);
        }

        return response()->json([
            'count' => $this->ownQuery($user)->whereNull('read_at')->count(),
        ]);
    }

    public function open(Request $request, $id)
    {
        $role = $this->role($request);
        $user = $this->recipient($role);

        $notification = $this->ownQuery($user)->where('notification_id', $id)->firstOrFail();

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

        $this->ownQuery($user)->whereNull('read_at')->update(['read_at' => now()]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'All notifications marked as read.']);
        }

        return back();    }
}