<script>
            (function() {
                var content = document.getElementById('page-content');
                var main = document.querySelector('main');
                var bar = document.getElementById('nav-progress');
                if (!content || !main) return;

                var controller = null;
                var seg = function(p) {
                    return p.split('/')[1];
                };

                function progress(state) {
                    if (!bar) return;
                    if (state === 'start') {
                        bar.style.transition = 'none';
                        bar.style.width = '0';
                        bar.style.opacity = '1';
                        void bar.offsetWidth;
                        bar.style.transition = 'width 8s cubic-bezier(.1,.7,.2,1)';
                        bar.style.width = '80%';
                    } else {
                        bar.style.transition = 'width .2s';
                        bar.style.width = '100%';
                        setTimeout(function() {
                            bar.style.opacity = '0';
                        }, 220);
                    }
                }

                function handles(a, e) {
                    if (a.hasAttribute('data-live-nav')) return false;
                    if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey ||
                        e.altKey) return false;
                    if ((a.target && a.target !== '_self') || a.hasAttribute('download') || a
                        .hasAttribute('data-no-ajax')) return false;
                    var u = new URL(a.href, location.href);
                    if (u.origin !== location.origin) return false;
                    if (u.pathname === location.pathname && u.search === location.search && u.hash)
                        return false; // #anchor
                    if (seg(u.pathname) !== seg(location.pathname))
                        return false; // other portal / logout
                    if (/(logout|download|export|print)|\.[a-z0-9]{2,5}$/i.test(u.pathname))
                        return false;
                    return true;
                }

                function runScripts(root) {
                    root.querySelectorAll('script').forEach(function(old) {
                        var s = document.createElement('script');
                        Array.prototype.forEach.call(old.attributes, function(at) {
                            s.setAttribute(at.name, at.value);
                        });
                        s.text = old.textContent;
                        old.parentNode.replaceChild(s, old);
                    });
                }

                // spinner keyframes (added once)
                var spinStyle = document.createElement('style');
                spinStyle.textContent = '@keyframes navspin{to{transform:rotate(360deg)}}';
                document.head.appendChild(spinStyle);

                function hideSpinner() {
                    var old = document.getElementById('nav-spinner');
                    if (old) old.remove();
                }

                function showSpinner() {
                    hideSpinner();
                    content.style.position = 'relative';

                    var overlay = document.createElement('div');
                    overlay.id = 'nav-spinner';
                    overlay.setAttribute('aria-busy', 'true');
                    overlay.style.cssText =
                        'position:absolute;inset:0;z-index:30;border-radius:24px;' +
                        'background:rgba(255,255,255,.55);backdrop-filter:blur(1px);';

                    // sticky = always centered in the part of the page you can currently see
                    var holder = document.createElement('div');
                    holder.style.cssText =
                        'position:sticky;top:40%;display:flex;justify-content:center;pointer-events:none;';

                    var disc = document.createElement('div');
                    disc.style.cssText =
                        'width:60px;height:60px;border-radius:50%;background:#fff;' +
                        'display:flex;align-items:center;justify-content:center;' +
                        'box-shadow:0 12px 28px -8px rgba(37,56,140,.35);';

                    var ring = document.createElement('div');
                    ring.style.cssText =
                        'width:30px;height:30px;border-radius:50%;' +
                        'border:3px solid rgba(66,99,240,.2);border-top-color:#4263f0;' +
                        'animation:navspin .7s linear infinite;';

                    disc.appendChild(ring);
                    holder.appendChild(disc);
                    overlay.appendChild(holder);
                    content.appendChild(overlay);
                }

                function load(url, push) {
                    if (controller) controller.abort();
                    controller = new AbortController();

                    // Same page, different query (e.g. ?month=...) -> keep the scroll position
                    var samePath = new URL(url, location.href).pathname === location.pathname;
                    var savedTop = main.scrollTop;

                    progress('start');
                    showSpinner();

                    fetch(url, {
                            credentials: 'same-origin',
                            signal: controller.signal,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'text/html'
                            }
                        })
                        .then(function(res) {
                            if (!res.ok) throw new Error('hard: HTTP ' + res.status);
                            if ((res.headers.get('content-type') || '').indexOf('text/html') === -1)
                                throw new Error('hard: not html');
                            return res.text().then(function(html) {
                                return {
                                    html: html,
                                    url: res.url
                                };
                            });
                        })
                        .then(function(r) {
                            var doc = new DOMParser().parseFromString(r.html, 'text/html');
                            var next = doc.getElementById('page-content');
                            if (!next) throw new Error('hard: no #page-content');
                            if (doc.getElementById('cm-root')) throw new Error('hard: chat page');
                            if (next.querySelector('[data-hard-load]')) throw new Error(
                                'hard: data-hard-load');
                            if (seg(new URL(r.url).pathname) !== seg(location.pathname))
                                throw new Error('hard: different portal');

                            if (push) history.pushState({}, '', r.url);
                            document.title = doc.title;

                            var t = doc.getElementById('page-title'),
                                ct = document.getElementById('page-title');
                            if (t && ct) ct.textContent = t.textContent;
                            var n = doc.getElementById('sidebar-nav'),
                                cn = document.getElementById('sidebar-nav');
                            if (n && cn) cn.innerHTML = n.innerHTML;

                            content.innerHTML = next.innerHTML; // also removes the spinner
                            runScripts(content);

                            if (samePath) main.scrollTop = savedTop; // stay where you were
                            else main.scrollTo(0, 0); // new page -> start at top

                            var ov = document.getElementById('sidebar-overlay');
                            if (ov && window.innerWidth < 768) ov.click(); // close mobile drawer
                            document.dispatchEvent(new CustomEvent('page:loaded', {
                                detail: {
                                    url: r.url
                                }
                            }));
                            progress('done');
                        })
                        .catch(function(err) {
                            if (err && err.name === 'AbortError') return;
                            hideSpinner();
                            console.warn('ajax-nav fell back to full reload:', err);
                            if (push) location.href = url;
                            else location.reload();
                        });
                }

                document.addEventListener('click', function(e) {
                    var a = e.target.closest('#sidebar a[href], #page-content a[href]');
                    if (!a || !handles(a, e)) return;
                    if (document.getElementById('cm-root'))
                        return; // leaving the chat = normal load
                    e.preventDefault();
                    load(a.href, true);
                });

                window.addEventListener('popstate', function() {
                    if (document.getElementById('cm-root')) {
                        location.reload();
                        return;
                    }
                    load(location.href, false);
                });
            })();
        </script>
