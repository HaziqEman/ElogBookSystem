<script>
    (function() {
        var root = document.getElementById('cm-root');
        if (!root) return;
        var main = root.closest('main');

        function setH(h) {
            root.style.setProperty('--cm-fit-h', Math.max(360, Math.floor(h)) + 'px');
        }

        // Box height = space left between the box top and the bottom of the visible page area.
        // It measures the real bottom after setting the height and corrects any difference,
        // so the typing bar and send button are never pushed below the screen.
        function fit() {
            var vh = window.visualViewport ? window.visualViewport.height : window.innerHeight;
            var edge = main ? Math.min(vh, main.getBoundingClientRect().bottom) : vh;
            var gap = window.innerWidth >= 768 ? 16 : 8;
            var h = edge - root.getBoundingClientRect().top - gap;
            setH(h);

            var over = root.getBoundingClientRect().bottom - (edge - gap);
            if (Math.abs(over) > 1) setH(h - over);
        }

        window.addEventListener('resize', fit);
        if (window.visualViewport) window.visualViewport.addEventListener('resize', fit);
        window.addEventListener('load', fit);
        if (document.fonts && document.fonts.ready) document.fonts.ready.then(fit);
        if (window.ResizeObserver) {
            var ro = new ResizeObserver(fit);
            if (main) ro.observe(main);
            ro.observe(document.body);
        }
        fit();
        [100, 300, 800, 1500].forEach(function(t) {
            setTimeout(fit, t);
        });
    })();
</script>
