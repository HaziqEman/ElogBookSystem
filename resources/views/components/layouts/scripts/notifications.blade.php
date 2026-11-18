<script>
                                    (function() {
                                        var wrap = document.getElementById('notif-wrap');
                                        var bell = document.getElementById('notif-bell');
                                        var badge = document.getElementById('notif-badge');
                                        var panel = document.getElementById('notif-panel');
                                        var list = document.getElementById('notif-panel-list');
                                        var sub = document.getElementById('notif-panel-sub');
                                        var readAllBtn = document.getElementById('notif-panel-readall');
                                        if (!wrap || !bell || !badge || !panel) return;

                                        var unread = 0;
                                        var MAX_ITEMS = 8;

                                        function csrf() {
                                            var m = document.querySelector('meta[name="csrf-token"]');
                                            return m ? m.getAttribute('content') : '';
                                        }

                                        function el(tag, cls, text) {
                                            var node = document.createElement(tag);
                                            if (cls) node.className = cls;
                                            if (text !== undefined && text !== null) node.textContent = text;
                                            return node;
                                        }

                                        function setUnread(n) {
                                            unread = n;
                                            badge.textContent = n > 99 ? '99+' : n;
                                            badge.classList.toggle('hidden', n === 0);
                                            sub.textContent = n > 0 ? n + ' unread' : 'You are all caught up';
                                            readAllBtn.classList.toggle('hidden', n === 0);
                                        }

                                        function refreshCount() {
                                            return fetch(bell.getAttribute('data-count-url'), {
                                                    headers: {
                                                        'Accept': 'application/json',
                                                        'X-Requested-With': 'XMLHttpRequest'
                                                    },
                                                    credentials: 'same-origin'
                                                })
                                                .then(function(r) {
                                                    return r.ok ? r.json() : null;
                                                })
                                                .then(function(d) {
                                                    if (!d) return;
                                                    setUnread(parseInt(d.count, 10) || 0);
                                                })
                                                .catch(function() {});
                                        }

                                        function showMessage(iconClass, text) {
                                            list.innerHTML = '';
                                            var box = el('div', 'px-6 py-10 text-center text-sm text-slate-500');
                                            var icon = el('i', 'fa-solid ' + iconClass + ' mb-2 text-2xl text-slate-300');
                                            box.appendChild(icon);
                                            box.appendChild(el('p', '', text));
                                            list.appendChild(box);
                                        }

                                        // Hitting the /open route marks it as read on the server; the redirect is not followed.
                                        function markRead(data, row, btn, dot, title) {
                                            if (data.unread !== '1') return;
                                            data.unread = '0';
                                            btn.classList.remove('bg-blue-50/60');
                                            if (dot) dot.remove();
                                            title.classList.remove('font-bold', 'text-slate-900');
                                            title.classList.add('font-semibold', 'text-slate-700');

                                            fetch(data.openUrl, {
                                                method: 'GET',
                                                credentials: 'same-origin',
                                                redirect: 'manual',
                                                headers: {
                                                    'X-Requested-With': 'XMLHttpRequest'
                                                }
                                            }).catch(function() {}).then(refreshCount);
                                        }

                                        function buildRow(src) {
                                            var data = {
                                                openUrl: src.getAttribute('data-open-url'),
                                                type: src.getAttribute('data-type'),
                                                unread: src.getAttribute('data-unread'),
                                                title: src.getAttribute('data-title') || '',
                                                body: src.getAttribute('data-body') || '',
                                                time: src.getAttribute('data-time') || '',
                                                ago: src.getAttribute('data-ago') || '',
                                                count: parseInt(src.getAttribute('data-count'), 10) || 0,
                                                icon: src.getAttribute('data-icon') || 'fa-bell',
                                                iconClass: src.getAttribute('data-icon-class') ||
                                                    'bg-slate-100 text-slate-600'
                                            };

                                            var row = el('div', 'border-b border-slate-100 last:border-b-0');
                                            var btn = el('button',
                                                'flex w-full items-start gap-3 px-4 py-3 text-left transition hover:bg-slate-50' +
                                                (data.unread === '1' ? ' bg-blue-50/60' : ''));
                                            btn.type = 'button';

                                            var iconBox = el('span',
                                                'flex h-10 w-10 flex-none items-center justify-center rounded-xl ' + data
                                                .iconClass);
                                            iconBox.appendChild(el('i', 'fa-solid ' + data.icon));
                                            btn.appendChild(iconBox);

                                            var textWrap = el('div', 'min-w-0 flex-1');
                                            var title = el('p', 'break-words text-sm ' + (data.unread === '1' ?
                                                    'font-bold text-slate-900' : 'font-semibold text-slate-700'),
                                                data.title + (data.count > 1 ? ' (' + data.count + ')' : ''));
                                            textWrap.appendChild(title);

                                            var body = null;
                                            if (data.body) {
                                                body = el('p', 'mt-0.5 truncate text-sm text-slate-500', data.body);
                                                textWrap.appendChild(body);
                                            }
                                            if (data.ago) {
                                                textWrap.appendChild(el('p', 'mt-1 text-xs text-slate-400', data.ago));
                                            }
                                            btn.appendChild(textWrap);

                                            var dot = null;
                                            if (data.unread === '1') {
                                                dot = el('span', 'mt-2 h-2.5 w-2.5 flex-none rounded-full bg-blue-600');
                                                btn.appendChild(dot);
                                            }
                                            row.appendChild(btn);

                                            // Expanded details (full message, exact time, optional link)
                                            var detail = el('div', 'hidden px-4 pb-3 pl-[4.25rem]');
                                            if (data.time) detail.appendChild(el('p', 'text-xs text-slate-400', data.time));
                                            if (data.type !== 'reminder' && data.openUrl) {
                                                var link = el('a',
                                                    'mt-2 inline-block rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50',
                                                    'Open page');
                                                link.href = data.openUrl;
                                                detail.appendChild(link);
                                            }
                                            row.appendChild(detail);

                                            btn.addEventListener('click', function() {
                                                var open = detail.classList.toggle('hidden') === false;
                                                if (body) {
                                                    body.classList.toggle('truncate', !open);
                                                    body.classList.toggle('whitespace-pre-line', open);
                                                    body.classList.toggle('break-words', open);
                                                }
                                                if (open) markRead(data, row, btn, dot, title);
                                            });

                                            return row;
                                        }

                                        function loadList() {
                                            showMessage('fa-spinner fa-spin', 'Loading...');

                                            fetch(bell.getAttribute('data-list-url'), {
                                                    headers: {
                                                        'Accept': 'text/html',
                                                        'X-Requested-With': 'XMLHttpRequest'
                                                    },
                                                    credentials: 'same-origin'
                                                })
                                                .then(function(r) {
                                                    return r.ok ? r.text() : Promise.reject();
                                                })
                                                .then(function(html) {
                                                    var doc = new DOMParser().parseFromString(html, 'text/html');
                                                    var items = doc.querySelectorAll('[data-notif-item]');

                                                    if (!items.length) {
                                                        showMessage('fa-bell-slash', 'No notifications yet.');
                                                        return;
                                                    }

                                                    list.innerHTML = '';
                                                    Array.prototype.slice.call(items, 0, MAX_ITEMS).forEach(function(src) {
                                                        list.appendChild(buildRow(src));
                                                    });
                                                })
                                                .catch(function() {
                                                    showMessage('fa-triangle-exclamation',
                                                        'Could not load notifications. Please try again.');
                                                });
                                        }

                                        function openPanel() {
                                            panel.classList.remove('hidden');
                                            bell.setAttribute('aria-expanded', 'true');
                                            loadList();
                                            refreshCount();
                                        }

                                        function closePanel() {
                                            panel.classList.add('hidden');
                                            bell.setAttribute('aria-expanded', 'false');
                                        }

                                        bell.addEventListener('click', function(e) {
                                            e.stopPropagation();
                                            if (panel.classList.contains('hidden')) openPanel();
                                            else closePanel();
                                        });

                                        readAllBtn.addEventListener('click', function() {
                                            readAllBtn.disabled = true;
                                            fetch(bell.getAttribute('data-read-all-url'), {
                                                method: 'POST',
                                                credentials: 'same-origin',
                                                redirect: 'manual',
                                                headers: {
                                                    'X-CSRF-TOKEN': csrf(),
                                                    'X-Requested-With': 'XMLHttpRequest',
                                                    'Accept': 'application/json'
                                                }
                                            }).catch(function() {}).then(function() {
                                                readAllBtn.disabled = false;
                                                refreshCount();
                                                if (!panel.classList.contains('hidden')) loadList();
                                            });
                                        });

                                        document.addEventListener('click', function(e) {
                                            if (!panel.classList.contains('hidden') && !wrap.contains(e.target))
                                                closePanel();
                                        });

                                        document.addEventListener('keydown', function(e) {
                                            if (e.key === 'Escape' && !panel.classList.contains('hidden')) {
                                                closePanel();
                                                bell.focus();
                                            }
                                        });

                                        refreshCount();
                                        setInterval(refreshCount, 60000);
                                        document.addEventListener('visibilitychange', function() {
                                            if (!document.hidden) refreshCount();
                                        });
                                    })();
                                </script>
