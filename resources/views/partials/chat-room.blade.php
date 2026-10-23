{{--
    Shared chat UI for student / lecturer / supervisor.
    Expects $chat = [
        'conversation_id' => int,
        'me'       => ['type' => FQCN, 'role' => 'student|lecturer|supervisor', 'id' => int, 'name' => string],
        'partner'  => ['role' => string, 'title' => string, 'name' => string, 'sub' => string|null, 'initials' => 'LE', 'tone' => 'blue|green'],
        'contacts' => [ ['title','name','initials','tone','href','active','disabled', optional: 'preview','time_iso','unread','student_id','conversation_id'] ]
        'tabs'     => bool (student view), 'list' => ['channel','partnerType','openBase'] (live list updates), 'placeholder' => string
        conversation_id = null -> only the list is shown (lecturer / supervisor index page)
        'back'     => ['href' => ..., 'label' => ...] | null,
        'messages' => array of Message::toChatPayload(),
        'deleteBase' => '/conversations/{id}/messages',
        'note'     => string|null,
    ]
--}}
@php
    $c = $chat;
    $contacts = $c['contacts'] ?? [];
    $cid = $c['conversation_id'] ?? null;          // null = list only, no chat selected
    $tabs = $c['tabs'] ?? false;                  // student: two tabs on mobile instead of a back arrow
    $js = $cid ? [
        'conversationId' => $cid,
        'me' => $c['me'],
        'partner' => $c['partner'],
        'messages' => $c['messages'],
        'maxMb' => 10,
        'urls' => [
            'store' => url("/conversations/{$cid}/messages"),
            'since' => url("/conversations/{$cid}/messages"),
            'read' => url("/conversations/{$cid}/read"),
            'deleteBase' => url("/conversations/{$cid}/messages"),
        ],
    ] : null;
@endphp

<style>
    .cm-root{--cm-blue:#2563eb;--cm-blue-d:#1d4ed8;--cm-ink:#0f172a;--cm-mute:#64748b;--cm-line:#e2e8f0;--cm-bg:#f1f5fd;
        display:flex;background:#fff;border:1px solid var(--cm-line);border-radius:20px;overflow:hidden;box-shadow:0 1px 3px rgba(15,23,42,.06);
        font-size:14px;color:var(--cm-ink);position:relative;min-height:480px}
    .cm-root *{box-sizing:border-box}
    :where(.cm-root) button{font:inherit;cursor:pointer;border:0;background:none;color:inherit}
    :where(.cm-root) a{color:inherit}
    .cm-root [hidden]{display:none!important}
    .cm-drop::after{content:"Drop files to attach";position:absolute;inset:0;z-index:30;display:flex;align-items:center;justify-content:center;background:rgba(37,99,235,.12);border:2px dashed var(--cm-blue);border-radius:20px;font-weight:700;color:var(--cm-blue)}

    /* contacts */
    .cm-side{width:320px;flex:none;border-right:1px solid var(--cm-line);padding:14px;display:none;flex-direction:column;gap:10px;background:#fff;overflow:auto}
    .cm-contact{display:flex;align-items:center;gap:12px;padding:14px;border-radius:16px;text-decoration:none;border:1px solid transparent;transition:background .15s}
    .cm-contact:hover{background:#f8fafc}
    .cm-contact.active{background:#eaf0fe;border-color:#dbe5fd}
    .cm-contact.disabled{opacity:.55;pointer-events:none}
    .cm-contact .cm-ct{flex:1;min-width:0}
    .cm-contact b{display:block;font-size:14px}
    .cm-contact small{display:block;color:var(--cm-mute);font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .cm-note{margin-top:6px;padding:12px 14px;border-radius:14px;background:#eef4ff;color:#3b5bdb;font-size:12.5px;line-height:1.45;display:flex;gap:8px}
    .cm-avatar{width:44px;height:44px;border-radius:50%;flex:none;display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:15px}
    .cm-tone-blue{background:#2563eb}.cm-tone-green{background:#0f9d7a}
    .cm-row .cm-avatar{width:32px;height:32px;font-size:11px;align-self:flex-end}

    .cm-contacts{display:flex;flex-direction:column;gap:4px}
    .cm-search{position:relative}
    .cm-search i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:13px}
    .cm-search input{width:100%;height:42px;border:1px solid var(--cm-line);border-radius:14px;padding:0 14px 0 38px;font:inherit;outline:0;background:#f8fafc}
    .cm-search input:focus{border-color:#93b4fb;background:#fff}
    .cm-contact .cm-row1,.cm-contact .cm-row2{display:flex;align-items:center;justify-content:space-between;gap:8px}
    .cm-contact .cm-row2{margin-top:2px}
    .cm-contact .cm-pv{flex:1;min-width:0;color:var(--cm-mute);font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .cm-contact .cm-ct-time{font-size:11.5px;color:var(--cm-mute);flex:none}
    .cm-contact.has-unread b,.cm-contact.has-unread .cm-pv{color:var(--cm-ink);font-weight:700}
    .cm-badge{min-width:20px;height:20px;border-radius:10px;background:#ef4444;color:#fff;font-size:11px;font-weight:700;padding:0 6px;display:inline-flex;align-items:center;justify-content:center;flex:none}
    .cm-side-empty{padding:24px 10px;text-align:center;color:var(--cm-mute)}
    .cm-ph{flex:1;display:flex;align-items:center;justify-content:center;background:var(--cm-bg);padding:24px}

    /* main */
    .cm-main{flex:1;min-width:0;display:flex;flex-direction:column;position:relative}
    .cm-head{display:flex;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid var(--cm-line);background:#fff}
    .cm-head .cm-avatar{width:42px;height:42px}
    .cm-back{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--cm-mute);text-decoration:none}
    .cm-back:hover{background:#f1f5f9}
    .cm-ht{min-width:0;flex:1}
    .cm-ht b{display:block;font-size:15px;line-height:1.2}
    .cm-ht small{display:block;color:var(--cm-mute);font-size:12.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .cm-st{display:inline-flex;align-items:center;gap:6px;font-size:12.5px;color:var(--cm-mute)}
    .cm-st i{width:8px;height:8px;border-radius:50%;background:#94a3b8;display:inline-block}
    .cm-st.on{color:#16a34a}.cm-st.on i{background:#22c55e}
    .cm-switch{display:flex;gap:6px}
    .cm-switch a{padding:6px 12px;border-radius:999px;font-size:12px;font-weight:600;text-decoration:none;border:1px solid var(--cm-line);color:var(--cm-mute);white-space:nowrap}
    .cm-switch a.active{background:var(--cm-blue);border-color:var(--cm-blue);color:#fff}
    .cm-offline{background:#fef3c7;color:#92400e;font-size:12.5px;text-align:center;padding:6px}

    .cm-list{flex:1;overflow-y:auto;padding:16px 14px;background:var(--cm-bg);display:flex;flex-direction:column;gap:6px;overscroll-behavior:contain;-webkit-overflow-scrolling:touch}
    .cm-empty{margin:auto;text-align:center;color:var(--cm-mute);max-width:260px;line-height:1.5}
    .cm-empty i{font-size:30px;color:#93a8d8;display:block;margin-bottom:8px}
    .cm-day{align-self:center;margin:10px 0 4px}
    .cm-day span{background:#e7ecf7;color:var(--cm-mute);font-size:12px;padding:4px 12px;border-radius:999px}

    .cm-row{display:flex;gap:8px;max-width:100%}
    .cm-row.cm-me{justify-content:flex-end}
    .cm-bubble{position:relative;max-width:min(78%,520px);padding:8px 12px 6px;border-radius:18px;line-height:1.45;word-wrap:break-word;overflow-wrap:anywhere;box-shadow:0 1px 1px rgba(15,23,42,.05)}
    .cm-them .cm-bubble{background:#fff;border:1px solid #e8edf6;border-bottom-left-radius:6px}
    .cm-me .cm-bubble{background:var(--cm-blue);color:#fff;border-bottom-right-radius:6px}
    .cm-text{white-space:pre-wrap}
    .cm-me .cm-text a{color:#fff;text-decoration:underline}
    .cm-them .cm-text a{color:var(--cm-blue);text-decoration:underline}
    .cm-meta{display:flex;justify-content:flex-end;align-items:center;gap:5px;font-size:11px;margin-top:2px;opacity:.75}
    .cm-me .cm-meta{color:#dbeafe;opacity:1}
    .cm-them .cm-meta{color:var(--cm-mute)}
    .cm-tick i{font-size:11px}.cm-read{color:#7dd3fc}.cm-fail{color:#fecaca}
    .cm-deleted{background:transparent!important;border:1px dashed #cbd5e1!important;color:var(--cm-mute)!important;box-shadow:none}
    .cm-deleted .cm-meta{color:var(--cm-mute)}
    .cm-del-text{font-style:italic;font-size:13px}
    .cm-more{position:absolute;top:4px;right:6px;width:22px;height:22px;border-radius:50%;font-size:10px;opacity:0;transition:opacity .12s;background:rgba(15,23,42,.12);color:inherit}
    .cm-bubble:hover .cm-more{opacity:1}
    @media (pointer:coarse){.cm-more{opacity:.55}}
    .cm-img{display:block;max-width:100%;max-height:300px;border-radius:12px;margin:2px 0 4px;cursor:zoom-in;background:#e2e8f0;min-width:140px}
    .cm-file{display:flex;align-items:center;gap:10px;text-decoration:none;padding:8px;margin:2px 0 4px;border-radius:12px;background:rgba(255,255,255,.95);color:var(--cm-ink);min-width:210px}
    .cm-file-ic{width:38px;height:38px;border-radius:10px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;font-size:19px;flex:none}
    .cm-file-info{min-width:0}.cm-file-info b{color:var(--cm-ink);display:block;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:200px}
    .cm-file-info small{color:var(--cm-mute);font-size:11.5px}
    .cm-audio{display:flex;align-items:center;gap:10px;min-width:210px;padding:2px 0}
    .cm-play{width:34px;height:34px;border-radius:50%;flex:none;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.95);color:var(--cm-blue)!important;font-size:12px}
    .cm-them .cm-play{background:var(--cm-blue);color:#fff!important}
    .cm-bar{flex:1;height:5px;border-radius:3px;background:rgba(255,255,255,.35);cursor:pointer;position:relative}
    .cm-them .cm-bar{background:#dbe3f3}
    .cm-bar i{display:block;height:100%;width:0;border-radius:3px;background:#fff}
    .cm-them .cm-bar i{background:var(--cm-blue)}
    .cm-dur{font-size:11.5px;min-width:34px;text-align:right}
    .cm-prog{height:3px;border-radius:2px;background:rgba(255,255,255,.3);overflow:hidden;margin-top:4px}.cm-prog i{display:block;height:100%;width:0;background:#fff;transition:width .15s}
    .cm-failrow{font-size:12px;margin-top:4px;display:flex;gap:8px;align-items:center}.cm-failrow button{text-decoration:underline;font-weight:600}
    .cm-newpill{position:absolute;left:50%;transform:translateX(-50%);bottom:92px;background:var(--cm-blue);color:#fff!important;padding:6px 14px;border-radius:999px;font-size:12px;box-shadow:0 4px 12px rgba(37,99,235,.35);z-index:5}

    /* composer */
    .cm-tray{display:flex;gap:8px;overflow-x:auto;padding:10px 14px;border-top:1px solid var(--cm-line);background:#fff}
    .cm-chip{display:flex;align-items:center;gap:8px;padding:6px 8px;border:1px solid var(--cm-line);border-radius:12px;background:#f8fafc;flex:none;max-width:240px}
    .cm-chip img{width:44px;height:44px;object-fit:cover;border-radius:8px}
    .cm-chip-ic{width:44px;height:44px;border-radius:8px;background:#eef2f7;display:flex;align-items:center;justify-content:center;font-size:20px}
    .cm-chip-info{min-width:0}.cm-chip-info b{display:block;font-size:12.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:130px}.cm-chip-info small{color:var(--cm-mute);font-size:11px}
    .cm-chip-x{width:24px;height:24px;border-radius:50%;color:var(--cm-mute)}.cm-chip-x:hover{background:#e2e8f0}
    .cm-composer,.cm-rec{display:flex;align-items:flex-end;gap:8px;padding:12px 14px calc(12px + env(safe-area-inset-bottom,0px));border-top:1px solid var(--cm-line);background:#fff;position:relative}
    .cm-ic{width:44px;height:44px;flex:none;border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--cm-mute);font-size:18px;border:1px solid var(--cm-line);background:#fff}
    .cm-ic:hover{background:#f1f5f9}
    .cm-field{flex:1;display:flex;align-items:flex-end;border:1px solid var(--cm-line);border-radius:24px;background:#fff;padding:0 6px 0 16px;min-width:0}
    .cm-field:focus-within{border-color:#93b4fb;box-shadow:0 0 0 3px rgba(37,99,235,.12)}
    .cm-field textarea{flex:1;border:0;outline:0;resize:none;padding:12px 0;font:inherit;max-height:140px;background:transparent;min-width:0;line-height:20px;height:44px}
    .cm-field .cm-emo{width:40px;height:44px;color:var(--cm-mute);font-size:18px}
    .cm-go{width:46px;height:46px;border-radius:50%;flex:none;background:var(--cm-blue);color:#fff!important;font-size:17px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(37,99,235,.3)}
    .cm-go:hover{background:var(--cm-blue-d)}
    .cm-menu{position:absolute;left:14px;bottom:70px;background:#fff;border:1px solid var(--cm-line);border-radius:16px;box-shadow:0 12px 30px rgba(15,23,42,.15);padding:6px;z-index:20;min-width:180px}
    .cm-menu button{display:flex;align-items:center;gap:12px;width:100%;padding:10px 12px;border-radius:10px;text-align:left;font-weight:500}
    .cm-menu button:hover{background:#f1f5f9}
    .cm-menu i{width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px}
    .cm-emoji{position:absolute;right:70px;bottom:70px;background:#fff;border:1px solid var(--cm-line);border-radius:16px;box-shadow:0 12px 30px rgba(15,23,42,.15);padding:8px;display:grid;grid-template-columns:repeat(8,1fr);gap:2px;z-index:20;max-width:320px}
    .cm-emoji button{font-size:20px;width:36px;height:36px;border-radius:8px}.cm-emoji button:hover{background:#f1f5f9}
    .cm-rec{align-items:center}
    .cm-rec .cm-dot{width:10px;height:10px;border-radius:50%;background:#ef4444;animation:cmpulse 1s infinite}
    @keyframes cmpulse{50%{opacity:.25}}
    .cm-rec .cm-rt{flex:1;display:flex;align-items:center;gap:10px;font-weight:600}
    .cm-rec .cm-ic{color:#ef4444}

    /* overlays */
    .cm-sheet,.cm-lb,.cm-cam{position:fixed;inset:0;z-index:60;background:rgba(15,23,42,.55);display:flex;align-items:center;justify-content:center;padding:16px}
    .cm-sheet-box{background:#fff;border-radius:20px;padding:18px;width:100%;max-width:380px;display:flex;flex-direction:column;gap:8px}
    .cm-sheet-box h3{margin:0 0 4px;font-size:16px;font-weight:700}
    .cm-sheet-box button{padding:12px;border-radius:12px;font-weight:600;background:#f1f5f9}
    .cm-sheet-box .danger{background:#dc2626;color:#fff}
    .cm-lb{background:rgba(2,6,23,.92)}
    .cm-lb img{max-width:100%;max-height:86vh;border-radius:8px}
    .cm-lb-bar{position:absolute;top:12px;right:12px;display:flex;gap:8px}
    .cm-lb-bar a,.cm-lb-bar button{width:42px;height:42px;border-radius:50%;background:rgba(255,255,255,.15);color:#fff;display:flex;align-items:center;justify-content:center;text-decoration:none}
    .cm-cam-box{background:#0f172a;border-radius:20px;padding:12px;max-width:520px;width:100%;display:flex;flex-direction:column;gap:10px}
    .cm-cam-box video{width:100%;border-radius:12px;background:#000}
    .cm-cam-box .row{display:flex;justify-content:center;gap:12px}
    .cm-cam-box button{padding:10px 18px;border-radius:999px;font-weight:600;background:#fff;color:#0f172a}
    .cm-toast{position:fixed;left:50%;bottom:90px;transform:translateX(-50%);background:#0f172a;color:#fff;padding:10px 16px;border-radius:12px;font-size:13px;z-index:70;max-width:90vw}

    /* responsive */
    @media (min-width:900px){.cm-side{display:flex}.cm-switch{display:none}.cm-has-side .cm-back{display:none}}
    @media (max-width:899px){.cm-index .cm-side{display:flex;width:100%;border-right:0}.cm-index .cm-main,.cm-index .cm-ph{display:none}.cm-head{flex-wrap:wrap}.cm-switch{width:100%;order:5}.cm-bubble{max-width:86%}.cm-root{border-radius:16px}}
</style>

<div id="cm-root" class="cm-root {{ ! $cid ? 'cm-index' : '' }} {{ count($contacts) && ! $tabs ? 'cm-has-side' : '' }}">
    @if(count($contacts))
        <aside class="cm-side" aria-label="Conversations">
            @if(! $tabs)
                <label class="cm-search"><i class="fa-solid fa-magnifying-glass"></i><input type="search" placeholder="Search students..." aria-label="Search"></label>
            @endif
            <div class="cm-contacts">
                @foreach($contacts as $ct)
                    @php $rich = array_key_exists('preview', $ct); @endphp
                    <a href="{{ $ct['href'] }}"
                       class="cm-contact {{ $ct['active'] ? 'active' : '' }} {{ ($ct['disabled'] ?? false) ? 'disabled' : '' }} {{ ($ct['unread'] ?? 0) > 0 ? 'has-unread' : '' }}"
                       data-name="{{ $ct['title'] }}"
                       @if(isset($ct['student_id'])) data-student-id="{{ $ct['student_id'] }}" @endif
                       @if(! empty($ct['conversation_id'])) data-conversation-id="{{ $ct['conversation_id'] }}" @endif>
                        <span class="cm-avatar cm-tone-{{ $ct['tone'] }}">{{ $ct['initials'] }}</span>
                        <span class="cm-ct">
                            @if($rich)
                                <span class="cm-row1"><b>{{ $ct['title'] }}</b><span class="cm-ct-time" data-iso="{{ $ct['time_iso'] ?? '' }}"></span></span>
                                <span class="cm-row2">
                                    <span class="cm-pv">{{ $ct['preview'] !== '' ? $ct['preview'] : 'No messages yet' }}</span>
                                    @if(($ct['unread'] ?? 0) > 0)<span class="cm-badge">{{ $ct['unread'] }}</span>@endif
                                </span>
                            @else
                                <b>{{ $ct['title'] }}</b>
                                <small>{{ $ct['name'] ?: 'Not assigned yet' }}</small>
                            @endif
                        </span>
                        @if(! $rich)<i class="fa-solid fa-chevron-right" style="color:#94a3b8;font-size:12px"></i>@endif
                    </a>
                @endforeach
            </div>
            @if(!empty($c['note']))
                <div class="cm-note"><i class="fa-solid fa-circle-info" style="margin-top:2px"></i><span>{{ $c['note'] }}</span></div>
            @endif
        </aside>
    @else
        <aside class="cm-side"><div class="cm-side-empty">{{ $c['empty'] ?? 'No conversations yet.' }}</div></aside>
    @endif

    @if(! $cid)
        <section class="cm-ph">
            <div class="cm-empty"><i class="fa-regular fa-comments"></i>{{ $c['placeholder'] ?? 'Select a conversation to start chatting.' }}</div>
        </section>
    @else
    <section class="cm-main">
        <header class="cm-head">
            @if(!empty($c['back']))
                <a class="cm-back" href="{{ $c['back']['href'] }}" aria-label="{{ $c['back']['label'] }}"><i class="fa-solid fa-arrow-left"></i></a>
            @endif
            <span class="cm-avatar cm-tone-{{ $c['partner']['tone'] }}">{{ $c['partner']['initials'] }}</span>
            <div class="cm-ht">
                <b>{{ $c['partner']['title'] }}</b>
                @if(!empty($c['partner']['name']))<small>{{ $c['partner']['name'] }}{{ !empty($c['partner']['sub']) ? ' · '.$c['partner']['sub'] : '' }}</small>@endif
                <span id="cm-status" class="cm-st"><i></i><span id="cm-status-text">Offline</span></span>
                <span id="cm-typing" hidden></span>
            </div>
            @if($tabs && count($contacts))
                <nav class="cm-switch" aria-label="Switch chat">
                    @foreach($contacts as $ct)
                        <a href="{{ $ct['href'] }}" class="{{ $ct['active'] ? 'active' : '' }}">{{ $ct['title'] }}</a>
                    @endforeach
                </nav>
            @endif
        </header>

        <div id="cm-offline" class="cm-offline" hidden><i class="fa-solid fa-wifi"></i> You're offline. Reconnect to send messages.</div>

        <div id="cm-list" class="cm-list" aria-live="polite">
            <div id="cm-empty" class="cm-empty" hidden><i class="fa-regular fa-comments"></i>No messages yet. Say hello to start the conversation.</div>
        </div>
        <button id="cm-newpill" class="cm-newpill" type="button" hidden>New messages <i class="fa-solid fa-arrow-down"></i></button>

        <div id="cm-tray" class="cm-tray" hidden></div>

        <form id="cm-form" class="cm-composer" autocomplete="off">
            <button id="cm-attach" class="cm-ic" type="button" aria-label="Attach"><i class="fa-solid fa-paperclip"></i></button>
            <div class="cm-field">
                <textarea id="cm-input" rows="1" placeholder="Type your message..." maxlength="1000" enterkeyhint="send"></textarea>
                <button id="cm-emoji-btn" class="cm-emo" type="button" aria-label="Emoji"><i class="fa-regular fa-face-smile"></i></button>
            </div>
            <button id="cm-send" class="cm-go" type="submit" aria-label="Send" hidden><i class="fa-solid fa-paper-plane"></i></button>
            <button id="cm-mic" class="cm-go" type="button" aria-label="Record voice message"><i class="fa-solid fa-microphone"></i></button>

            <div id="cm-menu" class="cm-menu" hidden>
                <button id="cm-pick-doc" type="button"><i class="fa-solid fa-file-lines" style="background:#6366f1"></i> Document</button>
                <button id="cm-pick-photo" type="button"><i class="fa-solid fa-image" style="background:#0ea5e9"></i> Photos</button>
                <button id="cm-pick-cam" type="button"><i class="fa-solid fa-camera" style="background:#ec4899"></i> Camera</button>
            </div>
            <div id="cm-emoji" class="cm-emoji" hidden></div>

            <input id="cm-in-doc" type="file" hidden multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,application/pdf">
            <input id="cm-in-photo" type="file" hidden multiple accept="image/*">
            <input id="cm-in-cam" type="file" hidden accept="image/*" capture="environment">
        </form>

        <div id="cm-rec" class="cm-rec" hidden>
            <button id="cm-rec-cancel" class="cm-ic" type="button" aria-label="Cancel recording"><i class="fa-solid fa-trash"></i></button>
            <div class="cm-rt"><span class="cm-dot"></span><span id="cm-rec-time">0:00</span><span style="color:var(--cm-mute);font-weight:400">Recording…</span></div>
            <button id="cm-rec-send" class="cm-go" type="button" aria-label="Send voice message"><i class="fa-solid fa-paper-plane"></i></button>
        </div>
    </section>

    <div id="cm-sheet" class="cm-sheet" hidden>
        <div class="cm-sheet-box">
            <h3>Delete message?</h3>
            <button id="cm-del-me" type="button">Delete for me</button>
            <button id="cm-del-everyone" class="danger" type="button">Delete for everyone</button>
            <button id="cm-del-cancel" type="button">Cancel</button>
        </div>
    </div>

    <div id="cm-lb" class="cm-lb" hidden>
        <div class="cm-lb-bar">
            <a id="cm-lb-open" href="#" target="_blank" rel="noopener" aria-label="Open original"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
            <button id="cm-lb-close" type="button" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <img id="cm-lb-img" alt="">
    </div>

    <div id="cm-cam" class="cm-cam" hidden>
        <div class="cm-cam-box">
            <video id="cm-cam-video" autoplay playsinline muted></video>
            <div class="row"><button id="cm-cam-snap" type="button"><i class="fa-solid fa-camera"></i> Take photo</button><button id="cm-cam-close" type="button">Cancel</button></div>
        </div>
    </div>

    <div id="cm-toast" class="cm-toast" hidden></div>
    @endif
</div>

<script>
    @if($cid)
    window.CHAT = {{ \Illuminate\Support\Js::from($js) }};
    // names app.js already uses for presence / channels
    window.CONVERSATION_ID = {{ (int) $cid }};
    window.CHAT_PARTNER_ROLE = @json($c['partner']['role']);
    window.CURRENT_USER = @json($c['me']);
    @endif
    window.CHAT_LIST = @json($c['list'] ?? null);
</script>
