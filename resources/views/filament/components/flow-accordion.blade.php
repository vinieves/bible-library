@once
<script>
(function () {
    function findRepeaterItemAlpineData(item) {
        if (!window.Alpine || typeof Alpine.$data !== 'function') {
            return null;
        }

        var candidates = [item].concat(Array.from(item.querySelectorAll('[x-data]')));

        for (var i = 0; i < candidates.length; i++) {
            var data = Alpine.$data(candidates[i]);

            if (data && Object.prototype.hasOwnProperty.call(data, 'isCollapsed')) {
                return data;
            }
        }

        return null;
    }

    function setItemCollapsed(item, collapsed) {
        var data = findRepeaterItemAlpineData(item);

        if (data) {
            data.isCollapsed = collapsed;

            return true;
        }

        var selector = collapsed
            ? '.fi-fo-repeater-item-header-collapse-action button, .fi-fo-repeater-item-header-collapse-action'
            : '.fi-fo-repeater-item-header-expand-action button, .fi-fo-repeater-item-header-expand-action';
        var action = item.querySelector(selector);

        if (action) {
            action.click();

            return true;
        }

        return false;
    }

    function collapseOtherSteps(clickedItem, repeater) {
        repeater.querySelectorAll('.fi-fo-repeater-item').forEach(function (item) {
            if (item === clickedItem) {
                return;
            }

            if (!item.classList.contains('fi-collapsed')) {
                setItemCollapsed(item, true);
            }
        });
    }

    function shouldIgnoreToggleClick(target) {
        return Boolean(
            target.closest('.fi-fo-repeater-item-header-start-actions')
            || target.closest('.fi-fo-repeater-item-header-end-actions > li:not(.fi-fo-repeater-item-header-collapsible-actions)')
        );
    }

    function createFlowArrow() {
        var arrow = document.createElement('div');
        arrow.className = 'flow-arrow';
        arrow.setAttribute('aria-hidden', 'true');
        arrow.innerHTML = '<div class="flow-arrow__line"></div><div class="flow-arrow__head"></div>';

        return arrow;
    }

    function syncFlowArrows(repeater) {
        var list = repeater.querySelector('.fi-fo-repeater-items');

        if (!list) {
            return;
        }

        list.querySelectorAll('.flow-arrow').forEach(function (arrow) {
            arrow.remove();
        });

        var items = list.querySelectorAll('.fi-fo-repeater-item');

        items.forEach(function (item, index) {
            if (index >= items.length - 1) {
                return;
            }

            var arrow = createFlowArrow();
            item.insertAdjacentElement('afterend', arrow);
        });
    }

    function bindFlowAccordion(repeater) {
        if (repeater.dataset.flowAccordionBound !== '1') {
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

                if (clickedItem.classList.contains('fi-collapsed')) {
                    collapseOtherSteps(clickedItem, repeater);
                }
            }, true);

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
                }, 0);
            });
        }

        syncFlowArrows(repeater);
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
