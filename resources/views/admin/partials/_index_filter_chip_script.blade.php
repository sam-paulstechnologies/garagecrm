{{-- Shared collapsed-filter chip behavior for admin index pages. --}}
@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-index-filter-panel]').forEach(function (root) {
                var body = root.querySelector('[data-index-filter-body]');
                var toggle = root.querySelector('[data-index-filter-toggle]');
                var summary = root.querySelector('[data-index-filter-summary]');
                var chips = root.querySelectorAll('[data-index-filter-chip]');
                var dateRangeSelector = root.getAttribute('data-date-range-control');
                var customFieldsSelector = root.getAttribute('data-custom-fields');
                var dateRange = dateRangeSelector ? root.querySelector(dateRangeSelector) : null;
                var customFields = customFieldsSelector ? root.querySelector(customFieldsSelector) : null;
                var collapsed = true;

                if (!body || !toggle) {
                    return;
                }

                function applyState() {
                    if (collapsed) {
                        body.classList.add('hidden');
                        toggle.textContent = 'Show Filters';
                        toggle.setAttribute('aria-expanded', 'false');
                    } else {
                        body.classList.remove('hidden');
                        toggle.textContent = 'Hide Filters';
                        toggle.setAttribute('aria-expanded', 'true');
                    }
                }

                function syncCustomDateFields() {
                    if (!dateRange || !customFields) {
                        return;
                    }

                    customFields.style.display = dateRange.value === 'custom' ? '' : 'none';
                }

                function focusFilterControl(selector) {
                    if (!selector) {
                        return;
                    }

                    var control = root.querySelector(selector);

                    if (!control) {
                        return;
                    }

                    window.setTimeout(function () {
                        control.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                        control.focus({ preventScroll: true });

                        if (typeof control.showPicker === 'function') {
                            try {
                                control.showPicker();
                            } catch (e) {}
                        }
                    }, 80);
                }

                toggle.addEventListener('click', function () {
                    collapsed = !collapsed;
                    applyState();
                });

                if (summary) {
                    summary.addEventListener('click', function (event) {
                        if (event.target.closest('button, a, input, select, textarea, label')) {
                            return;
                        }

                        collapsed = !collapsed;
                        applyState();
                    });
                }

                chips.forEach(function (chip) {
                    chip.addEventListener('click', function () {
                        collapsed = false;
                        applyState();
                        focusFilterControl(chip.getAttribute('data-filter-target'));
                    });
                });

                if (dateRange) {
                    dateRange.addEventListener('change', function () {
                        syncCustomDateFields();

                        if (dateRange.value === 'custom') {
                            collapsed = false;
                            applyState();
                        }
                    });
                }

                applyState();
                syncCustomDateFields();
            });
        });
    </script>
@endonce
