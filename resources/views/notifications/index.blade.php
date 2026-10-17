@extends('layouts.app')

@section('content')
@php
    $icons = [
        'message' => ['fa-comments', 'bg-[#E8EFFF] text-[#1F45D6]'],
        'review' => ['fa-clipboard-check', 'bg-[#D1FAE5] text-[#065F46]'],
        'reminder' => ['fa-bell', 'bg-[#FEF3C7] text-[#7C2D12]'],
        'task' => ['fa-list-check', 'bg-[#F0EAFF] text-[#6D28D9]'],
    ];
    $tz = \App\Models\Attendance::TIMEZONE;
@endphp

<div class="mx-auto max-w-3xl space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-5 shadow-xs md:p-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Notifications</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $unread > 0 ? $unread.' unread' : 'You are all caught up.' }}</p>
        </div>
        @if($unread > 0)
            <form method="POST" action="/{{ $role }}/notifications/read-all">
                @csrf
                <button type="submit" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Mark all as read</button>
            </form>
        @endif
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs">
        @forelse($notifications as $notification)
            @php
                $isUnread = $notification->read_at === null;
                [$icon, $iconClass] = $icons[$notification->type] ?? ['fa-bell', 'bg-slate-100 text-slate-600'];
                $when = $notification->updated_at ? $notification->updated_at->timezone($tz) : null;
            @endphp
            <a href="/{{ $role }}/notifications/{{ $notification->notification_id }}/open" class="flex items-start gap-3 border-b border-slate-100 p-4 transition last:border-b-0 hover:bg-slate-50 md:p-5 {{ $isUnread ? 'bg-blue-50/50' : '' }}">
                <span class="flex h-10 w-10 flex-none items-center justify-center rounded-xl {{ $iconClass }}"><i class="fa-solid {{ $icon }}"></i></span>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-x-2">
                        <p class="break-words text-sm {{ $isUnread ? 'font-bold text-slate-900' : 'font-semibold text-slate-700' }}">{{ $notification->title }}@if($notification->merged_count > 1) <span class="text-slate-400">({{ $notification->merged_count }})</span>@endif</p>
                    </div>
                    @if($notification->body)
                        <p class="mt-0.5 break-words text-sm text-slate-500">{{ $notification->body }}</p>
                    @endif
                    @if($when)
                        <p class="mt-1 text-xs text-slate-400" title="{{ $when->format('d M Y, H:i') }}">{{ $when->diffForHumans() }}</p>
                    @endif
                </div>
                @if($isUnread)
                    <span class="mt-2 h-2.5 w-2.5 flex-none rounded-full bg-blue-600" aria-label="Unread"></span>
                @endif
            </a>
        @empty
            <div class="p-8 text-center text-sm text-slate-500">
                <i class="fa-solid fa-bell-slash mb-2 text-2xl text-slate-300"></i>
                <p>No notifications yet. Feedback, new messages and reminders will show up here.</p>
            </div>
        @endforelse
    </div>

    <div>{{ $notifications->links() }}</div>
</div>
@endsection