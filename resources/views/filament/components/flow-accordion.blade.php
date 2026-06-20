@once
<script>
(function () {
    function collapseOtherSteps(clickedItem, repeater) {
        repeater.querySelectorAll('.fi-fo-repeater-item').forEach(function (item) {
            if (item === clickedItem) {
                return;
            }

            if (window.Alpine && typeof Alpine.$data === 'function') {
                var data = Alpine.$data(item);

                if (data && Object.prototype.hasOwnProperty.call(data, 'isCollapsed')) {
                    data.isCollapsed = true;
                }
            }
        });
    }

    function shouldIgnoreToggleClick(target) {
        return Boolean(
            target.closest('.fi-fo-repeater-item-header-start-actions')
            || target.closest('.fi-fo-repeater-item-header-end-actions > li:not(.fi-fo-repeater-item-header-collapsible-actions)')
        );
    }

    function bindFlowAccordion(repeater) {
        if (repeater.dataset.flowAccordionBound === '1') {
            return;
        }

        repeater.dataset.flowAccordionBound = '1';

        repeater.addEventListener('click', function (event) {
            if (shouldIgnoreToggleClick(event.target)) {
                return;
            }

            var header = event.target.closest('.fi-fo-repeater-item-header, .fi-fo-repeater-item-header-collapsible-actions');

            if (!header) {
                return;
            }

            var clickedItem = header.closest('.fi-fo-repeater-item');

            if (!clickedItem) {
                return;
            }

            window.setTimeout(function () {
                if (clickedItem.classList.contains('fi-collapsed')) {
                    return;
                }

                collapseOtherSteps(clickedItem, repeater);
            }, 50);
        });
    }

    function initFlowAccordion() {
        document.querySelectorAll('.flow-steps-repeater.fi-fo-repeater').forEach(bindFlowAccordion);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFlowAccordion);
    } else {
        initFlowAccordion();
    }

    document.addEventListener('livewire:navigated', initFlowAccordion);

    document.addEventListener('livewire:init', function () {
        Livewire.hook('morph.updated', function () {
            window.setTimeout(initFlowAccordion, 0);
        });
    });
})();
</script>
@endonce
