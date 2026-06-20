<style>
    .flow-builder-section.fi-section {
        overflow: visible;
    }

    .flow-builder-section > .fi-section-header {
        margin-bottom: 0.75rem;
    }

    .flow-builder {
        --flow-card-width: 15.5rem;
        --flow-connector: rgb(82 82 91);
        --flow-card-bg: rgb(39 39 42);
        --flow-card-border: rgb(63 63 70);
        --flow-card-hover: rgb(48 48 54);
        counter-reset: flow-step;
        position: relative;
    }

    .flow-builder > .fi-fo-field-wrp-label {
        display: none;
    }

    .flow-builder > .fi-fo-repeater > .grid {
        display: block;
    }

    .flow-builder .fi-fo-repeater-items,
    .flow-builder [data-sortable-group],
    .flow-builder > .fi-fo-repeater > ul {
        display: flex;
        flex-wrap: nowrap;
        align-items: stretch;
        gap: 2.75rem;
        overflow-x: auto;
        padding: 0.5rem 0.25rem 1.25rem;
        scroll-behavior: smooth;
        scrollbar-width: thin;
    }

    .flow-builder .fi-fo-repeater-item,
    .flow-builder [data-sortable-item] {
        counter-increment: flow-step;
        position: relative;
        flex: 0 0 var(--flow-card-width);
        min-width: var(--flow-card-width);
        margin: 0 !important;
        border: 1px solid var(--flow-card-border);
        border-radius: 0.75rem;
        background: var(--flow-card-bg);
        box-shadow: 0 1px 2px rgb(0 0 0 / 0.25);
        transition: border-color 0.15s ease, box-shadow 0.15s ease, flex-basis 0.2s ease;
    }

    .flow-builder .fi-fo-repeater-item:not(:last-child)::after,
    .flow-builder [data-sortable-item]:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 50%;
        right: -2.75rem;
        width: 2.75rem;
        height: 2px;
        transform: translateY(-50%);
        background: linear-gradient(90deg, var(--flow-connector), rgb(113 113 122));
        pointer-events: none;
    }

    .flow-builder .fi-fo-repeater-item:not(:last-child)::before,
    .flow-builder [data-sortable-item]:not(:last-child)::before {
        content: '';
        position: absolute;
        top: 50%;
        right: -0.55rem;
        width: 0.45rem;
        height: 0.45rem;
        transform: translateY(-50%) rotate(45deg);
        border-top: 2px solid rgb(113 113 122);
        border-right: 2px solid rgb(113 113 122);
        pointer-events: none;
    }

    .flow-builder .fi-fo-repeater-item:hover,
    .flow-builder [data-sortable-item]:hover {
        border-color: rgb(82 82 91);
        box-shadow: 0 8px 24px rgb(0 0 0 / 0.22);
    }

    .flow-builder .fi-fo-repeater-item-header,
    .flow-builder .fi-collapsible-header {
        padding: 0 !important;
        border: 0 !important;
        background: transparent !important;
    }

    .flow-builder .fi-fo-repeater-item-header > .flex,
    .flow-builder .fi-collapsible-header > .flex {
        width: 100%;
        align-items: stretch !important;
        gap: 0 !important;
    }

    .flow-builder .fi-fo-repeater-item-label,
    .flow-builder .fi-collapsible-heading {
        flex: 1;
        min-width: 0;
        padding: 0.85rem 0.85rem 0.85rem 0;
    }

    .flow-builder .fi-fo-repeater-item-label::before,
    .flow-builder .fi-collapsible-heading::before {
        content: counter(flow-step);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.35rem;
        height: 1.35rem;
        margin-right: 0.55rem;
        border-radius: 9999px;
        background: rgb(24 24 27);
        color: rgb(212 212 216);
        font-size: 0.68rem;
        font-weight: 700;
        vertical-align: middle;
    }

    .flow-step-card__label {
        display: flex;
        align-items: stretch;
        gap: 0.65rem;
        min-width: 0;
    }

    .flow-step-card__accent {
        width: 0.22rem;
        flex-shrink: 0;
        border-radius: 9999px;
        background: var(--flow-step-accent, rgb(113 113 122));
    }

    .flow-step-card__body {
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
        min-width: 0;
    }

    .flow-step-card__type {
        font-size: 0.82rem;
        font-weight: 600;
        line-height: 1.25;
        color: rgb(250 250 250);
    }

    .flow-step-card__preview {
        display: block;
        font-size: 0.72rem;
        line-height: 1.35;
        color: rgb(161 161 170);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .flow-step-card__meta {
        font-size: 0.68rem;
        color: rgb(113 113 122);
    }

    .flow-builder .fi-fo-repeater-item-actions,
    .flow-builder .fi-collapsible-actions {
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 0.15rem;
        padding: 0.35rem 0.45rem 0.35rem 0;
        border-left: 1px solid rgb(63 63 70 / 0.8);
    }

    .flow-builder .fi-fo-repeater-item-content,
    .flow-builder .fi-collapsible-content {
        border-top: 1px solid rgb(63 63 70);
        background: rgb(24 24 27);
        border-radius: 0 0 0.75rem 0.75rem;
        padding: 1rem !important;
    }

    .flow-builder .fi-fo-repeater-item.fi-expanded,
    .flow-builder [data-sortable-item].fi-expanded,
    .flow-builder .fi-fo-repeater-item:has(.fi-collapsible-content:not(.hidden)),
    .flow-builder [data-sortable-item]:has(.fi-collapsible-content:not(.hidden)) {
        flex: 1 0 100%;
        min-width: 100%;
        order: 99;
    }

    .flow-builder .fi-fo-repeater-item.fi-expanded::after,
    .flow-builder .fi-fo-repeater-item.fi-expanded::before,
    .flow-builder [data-sortable-item].fi-expanded::after,
    .flow-builder [data-sortable-item].fi-expanded::before {
        display: none;
    }

    .flow-builder .fi-fo-repeater-add {
        flex: 0 0 auto;
        align-self: center;
        margin-left: 0.25rem;
    }

    .flow-builder .fi-fo-repeater-add-action,
    .flow-builder .fi-fo-repeater-add .fi-btn {
        min-height: 7.5rem;
        min-width: var(--flow-card-width);
        border: 1px dashed rgb(82 82 91) !important;
        border-radius: 0.75rem !important;
        background: transparent !important;
        color: rgb(161 161 170) !important;
        box-shadow: none !important;
    }

    .flow-builder .fi-fo-repeater-add-action:hover,
    .flow-builder .fi-fo-repeater-add .fi-btn:hover {
        border-color: rgb(245 158 11 / 0.65) !important;
        color: rgb(251 191 36) !important;
        background: rgb(39 39 42 / 0.65) !important;
    }

    .flow-builder-empty-hint {
        margin: 0 0 0.75rem;
        padding: 0.85rem 1rem;
        border: 1px dashed rgb(63 63 70);
        border-radius: 0.75rem;
        font-size: 0.8rem;
        color: rgb(161 161 170);
        background: rgb(24 24 27 / 0.65);
    }

    .flow-builder .flow-step-fields {
        display: grid;
        gap: 1rem;
    }

    @media (max-width: 1024px) {
        .flow-builder-config.fi-section {
            grid-column: 1 / -1;
        }

        .flow-builder-section.fi-section {
            grid-column: 1 / -1;
        }
    }

    @media (max-width: 640px) {
        .flow-builder {
            --flow-card-width: 13.5rem;
        }
    }
</style>
