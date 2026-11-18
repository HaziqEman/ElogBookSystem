@php
    $c = $chat;
    $contacts = $c['contacts'] ?? [];
    $role = $c['me']['role'] ?? 'student';
    $cid = $c['conversation_id'] ?? null; // null = list only, no chat selected
    $tabs = $c['tabs'] ?? false; // student: two tabs on mobile instead of a back arrow
    $config = $cid
        ? [
            'conversationId' => (int) $cid,
            'role' => $role,
            'currentUserId' => (int) $c['me']['id'],
            'partnerRole' => $c['partner']['role'],
            'routes' => [
                'store' => url("/chat/{$role}/conversations/{$cid}/messages"),
                'since' => url("/chat/{$role}/conversations/{$cid}/messages"),
                'read' => url("/chat/{$role}/conversations/{$cid}/read"),
                'deleteBase' => url("/chat/{$role}/conversations/{$cid}/messages"),
            ],
        ]
        : null;
    $js = $cid
        ? [
            'me' => [
                'type' => $c['me']['type'],
                'name' => $c['me']['name'],
            ],
            'partner' => $c['partner'],
            'messages' => $c['messages'],
            'maxMb' => 10,
        ]
        : null;
@endphp


<div
    id="cm-root"
    class="cm-root {{ !$cid ? 'cm-index' : '' }} {{ count($contacts) && !$tabs ? 'cm-has-side' : '' }}"
>
    @if (count($contacts))
        <aside
            class="cm-side"
            aria-label="Conversations"
        >
            <div class="cm-side-h">
                <span class="cm-side-ic"><i class="fa-solid fa-comment-dots"></i></span>
                <div><b>Conversations</b><small>{{ count($contacts) }}
                        {{ count($contacts) === 1 ? 'contact' : 'contacts' }}</small></div>
            </div>
            @if (!$tabs)
                <label class="cm-search"><i class="fa-solid fa-magnifying-glass"></i><input
                        type="search"
                        placeholder="Search students..."
                        aria-label="Search"
                    ></label>
            @endif
            <div class="cm-contacts">
                @foreach ($contacts as $ct)
                    @php $rich = array_key_exists('preview', $ct); @endphp
                    <a
                        href="{{ $ct['href'] }}"
                        class="cm-contact {{ $ct['active'] ? 'active' : '' }} {{ $ct['disabled'] ?? false ? 'disabled' : '' }} {{ ($ct['unread'] ?? 0) > 0 ? 'has-unread' : '' }}"
                        data-name="{{ $ct['title'] }}"
                        @if (isset($ct['student_id'])) data-student-id="{{ $ct['student_id'] }}" @endif
                        @if (!empty($ct['conversation_id'])) data-conversation-id="{{ $ct['conversation_id'] }}" @endif
                    >
                        <span
                            class="cm-avatar cm-tone-{{ $ct['tone'] }}">{{ $ct['initials'] }}</span>
                        <span class="cm-ct">
                            @if ($rich)
                                <span class="cm-row1"><b>{{ $ct['title'] }}</b><span
                                        class="cm-ct-time"
                                        data-iso="{{ $ct['time_iso'] ?? '' }}"
                                    ></span></span>
                                <span class="cm-row2">
                                    <span
                                        class="cm-pv">{{ $ct['preview'] !== '' ? $ct['preview'] : 'No messages yet' }}</span>
                                    @if (($ct['unread'] ?? 0) > 0)
                                        <span class="cm-badge">{{ $ct['unread'] }}</span>
                                    @endif
                                </span>
                            @else
                                <b>{{ $ct['title'] }}</b>
                                <small>{{ $ct['name'] ?: 'Not assigned yet' }}</small>
                            @endif
                        </span>
                        @if (!$rich)
                            <i
                                class="fa-solid fa-chevron-right"
                                style="color:#94a3b8;font-size:12px"
                            ></i>
                        @endif
                    </a>
                @endforeach
            </div>
            @if (!empty($c['note']))
                <div class="cm-note"><i
                        class="fa-solid fa-circle-info"
                        style="margin-top:2px"
                    ></i><span>{{ $c['note'] }}</span></div>
            @endif
        </aside>
    @else
        <aside class="cm-side">
            <div class="cm-side-empty">{{ $c['empty'] ?? 'No conversations yet.' }}</div>
        </aside>
    @endif

    @if (!$cid)
        <section class="cm-ph">
            <div class="cm-empty"><i
                    class="fa-regular fa-comments"></i>{{ $c['placeholder'] ?? 'Select a conversation to start chatting.' }}
            </div>
        </section>
    @else
        <section class="cm-main">
            <header class="cm-head">
                @if (!empty($c['back']))
                    <a
                        class="cm-back"
                        href="{{ $c['back']['href'] }}"
                        aria-label="{{ $c['back']['label'] }}"
                    ><i class="fa-solid fa-arrow-left"></i></a>
                @endif
                <span
                    class="cm-avatar cm-tone-{{ $c['partner']['tone'] }}">{{ $c['partner']['initials'] }}</span>
                <div class="cm-ht">
                    <b>{{ $c['partner']['title'] }}</b>
                    @if (!empty($c['partner']['name']))
                        <small>{{ $c['partner']['name'] }}{{ !empty($c['partner']['sub']) ? ' · ' . $c['partner']['sub'] : '' }}</small>
                    @endif
                    <span
                        id="cm-status"
                        class="cm-st"
                    ><i></i><span id="cm-status-text">Offline</span></span>
                    <span
                        id="cm-typing"
                        hidden
                    ></span>
                </div>
                @if ($tabs && count($contacts))
                    <nav
                        class="cm-switch"
                        aria-label="Switch chat"
                    >
                        @foreach ($contacts as $ct)
                            <a
                                href="{{ $ct['href'] }}"
                                class="{{ $ct['active'] ? 'active' : '' }}"
                            >{{ $ct['title'] }}</a>
                        @endforeach
                    </nav>
                @endif
            </header>

            <div
                id="cm-offline"
                class="cm-offline"
                hidden
            ><i class="fa-solid fa-wifi"></i> You're offline. Reconnect to send messages.</div>

            @include('partials.chat-room.message-list')

            @include('partials.chat-room.attachment-preview')

            @include('partials.chat-room.composer')
        </section>

        @include('partials.chat-room.modals')
    @endif
</div>

<script>
    window.CHAT_CONFIG = @json($config);
    window.CHAT_DATA = {{ \Illuminate\Support\Js::from($js) }};
    window.CHAT_LIST = @json($c['list'] ?? null);
</script>

@include('partials.chat-room.fit-script')
