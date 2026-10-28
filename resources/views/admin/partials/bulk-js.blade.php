<script>
    (function () {
        var form = document.getElementById('bulk-form');
        if (!form) return;

        var bar = document.getElementById('bulk-bar');
        var checks = Array.prototype.slice.call(document.querySelectorAll('.row-check'));
        var selectAll = document.getElementById('select-all');
        var countEl = document.getElementById('selected-count');
        var applyAll = document.getElementById('apply-all');
        var applyAllWrap = document.getElementById('apply-all-wrap');
        var actionSelect = document.getElementById('bulk-action');
        var submitBtn = document.getElementById('bulk-submit');
        var clearBtn = document.getElementById('bulk-clear');
        var panels = Array.prototype.slice.call(form.querySelectorAll('[data-panel]'));
        var total = parseInt(form.getAttribute('data-total'), 10) || 0;
        var noun = form.getAttribute('data-noun') || 'items';

        function selectedCount() {
            return checks.filter(function (c) { return c.checked; }).length;
        }

        function useAll() {
            return !!(applyAll && applyAll.checked);
        }

        function syncPanels() {
            var value = actionSelect.value;

            panels.forEach(function (panel) {
                var show = panel.getAttribute('data-panel') === value;
                panel.classList.toggle('hidden', !show);

                Array.prototype.forEach.call(panel.querySelectorAll('input, select, textarea'), function (field) {
                    field.disabled = !show;
                });
            });

            submitBtn.disabled = !value;
            submitBtn.classList.toggle('opacity-50', !value);
            submitBtn.classList.toggle('cursor-not-allowed', !value);

            var option = actionSelect.options[actionSelect.selectedIndex];
            var danger = option && option.getAttribute('data-danger') === '1';
            submitBtn.classList.toggle('bg-rose-600', danger);
            submitBtn.classList.toggle('hover:bg-rose-700', danger);
            submitBtn.classList.toggle('bg-blue-600', !danger);
            submitBtn.classList.toggle('hover:bg-blue-700', !danger);
        }

        function refresh() {
            var n = selectedCount();

            if (n === 0 && applyAll) applyAll.checked = false;

            bar.classList.toggle('hidden', n === 0);
            countEl.textContent = useAll() ? total : n;

            if (applyAllWrap) applyAllWrap.classList.toggle('hidden', total <= n);

            if (selectAll) {
                selectAll.checked = checks.length > 0 && n === checks.length;
                selectAll.indeterminate = n > 0 && n < checks.length;
            }

            syncPanels();
        }

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                checks.forEach(function (c) { c.checked = selectAll.checked; });
                refresh();
            });
        }

        checks.forEach(function (c) { c.addEventListener('change', refresh); });
        if (applyAll) applyAll.addEventListener('change', refresh);
        actionSelect.addEventListener('change', syncPanels);

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                checks.forEach(function (c) { c.checked = false; });
                actionSelect.value = '';
                refresh();
            });
        }

        form.addEventListener('submit', function (event) {
            var option = actionSelect.options[actionSelect.selectedIndex];

            if (!option || !option.value) {
                event.preventDefault();
                return;
            }

            var n = useAll() ? total : selectedCount();
            var message = option.getAttribute('data-confirm') ||
                ('Apply "' + option.text.trim() + '" to {n} ' + noun + '?');

            if (!window.confirm(message.replace('{n}', n))) {
                event.preventDefault();
            }
        });

        refresh();
    })();
</script>
