<script>
                                (function() {
                                    var wrap = document.getElementById('profile-wrap');
                                    var btn = document.getElementById('profile-btn');
                                    var menu = document.getElementById('profile-menu');
                                    if (!wrap || !btn || !menu) return;

                                    function close() {
                                        menu.classList.add('hidden');
                                        btn.setAttribute('aria-expanded', 'false');
                                    }

                                    btn.addEventListener('click', function(e) {
                                        e.stopPropagation();
                                        var open = menu.classList.toggle('hidden') === false;
                                        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
                                    });

                                    document.addEventListener('click', function(e) {
                                        if (!wrap.contains(e.target)) close();
                                    });

                                    document.addEventListener('keydown', function(e) {
                                        if (e.key === 'Escape') close();
                                    });
                                })();
                            </script>
