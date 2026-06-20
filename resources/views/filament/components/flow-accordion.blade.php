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

    function adjustExpandPanel(repeater) {
        var ul = repeater.querySelector('.fi-fo-repeater-items');

        if (!ul) {
            return;
        }

        var expanded = ul.querySelector('.fi-fo-repeater-item:not(.fi-collapsed)');

        if (!expanded) {
            ul.style.paddingBottom = '';
            ul.style.removeProperty('--flow-panel-top');
            ul.style.removeProperty('--flow-panel-left');
            ul.style.removeProperty('--flow-panel-width');

            return;
        }

        var content = expanded.querySelector('.fi-fo-repeater-item-content');

        if (!content) {
            ul.style.paddingBottom = '';
            ul.style.removeProperty('--flow-panel-left');
            ul.style.removeProperty('--flow-panel-width');

            return;
        }

        var trackBottom = 0;

        ul.querySelectorAll('.fi-fo-repeater-item .fi-fo-repeater-item-header').forEach(function (header) {
            var bottom = header.offsetTop + header.offsetHeight;

            if (bottom > trackBottom) {
                trackBottom = bottom;
            }
        });

        ul.style.setProperty('--flow-panel-top', (trackBottom + 12) + 'px');
        ul.style.setProperty('--flow-panel-left', (-expanded.offsetLeft) + 'px');
        ul.style.setProperty('--flow-panel-width', repeater.clientWidth + 'px');

        window.requestAnimationFrame(function () {
            ul.style.paddingBottom = (content.offsetHeight + 24) + 'px';
        });
    }

    function shouldIgnoreToggleClick(target) {
        return Boolean(
            target.closest('.fi-fo-repeater-item-header-start-actions')
            || target.closest('.fi-fo-repeater-item-header-end-actions > li:not(.fi-fo-repeater-item-header-collapsible-actions)')
        );
    }

    function schedulePanelAdjust(repeater) {
        window.setTimeout(function () {
            adjustExpandPanel(repeater);
        }, 0);

        window.setTimeout(function () {
            adjustExpandPanel(repeater);
        }, 120);
    }

    function watchRepeater(repeater) {
        var ul = repeater.querySelector('.fi-fo-repeater-items');

        if (!ul || ul.dataset.flowPanelWatch === '1') {
            return;
        }

        ul.dataset.flowPanelWatch = '1';

        new MutationObserver(function () {
            schedulePanelAdjust(repeater);
        }).observe(ul, {
            attributes: true,
            subtree: true,
            attributeFilter: ['class', 'style'],
            childList: true,
        });

        if (typeof ResizeObserver !== 'undefined') {
            new ResizeObserver(function () {
                adjustExpandPanel(repeater);
            }).observe(ul);
        }

        ul.addEventListener('scroll', function () {
            adjustExpandPanel(repeater);
        }, { passive: true });
    }

    function bindFlowAccordion(repeater) {
        if (repeater.dataset.flowAccordionBound === '1') {
            schedulePanelAdjust(repeater);

            return;
        }

        repeater.dataset.flowAccordionBound = '1';

        watchRepeater(repeater);

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

            schedulePanelAdjust(repeater);
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
                    adjustExpandPanel(repeater);

                    return;
                }

                collapseOtherSteps(clickedItem, repeater);
                schedulePanelAdjust(repeater);
            }, 0);
        });

        schedulePanelAdjust(repeater);
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

    window.addEventListener('resize', function () {
        document.querySelectorAll('.flow-steps-repeater.fi-fo-repeater').forEach(adjustExpandPanel);
    });

    document.addEventListener('livewire:init', function () {
        Livewire.hook('morph.updated', function () {
            window.setTimeout(initFlowAccordion, 0);
        });
    });
})();
</script>
@endonce
