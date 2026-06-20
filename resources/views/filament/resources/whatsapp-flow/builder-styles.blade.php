<style>
    .flow-builder-section.fi-section {
        overflow: visible;
    }

    .flow-builder-section > .fi-section-header {
        margin-bottom: 0.5rem;
    }

    .flow-builder-empty-hint {
        margin: 0 0 0.65rem;
        padding: 0.65rem 0.85rem;
        border: 1px dashed rgb(63 63 70);
        border-radius: 0.65rem;
        font-size: 0.76rem;
        line-height: 1.45;
        color: rgb(161 161 170);
        background: rgb(24 24 27 / 0.5);
    }

    .flow-builder.fi-fo-repeater {
        --flow-card-width: 11.75rem;
        --flow-card-min-height: 5.5rem;
        counter-reset: flow-step;
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        gap: 0.65rem 0;
    }

    .flow-builder.fi-fo-repeater > .fi-fo-field-wrp-label,
    .flow-builder > .fi-fo-field-wrp-label {
        display: none;
    }

    .flow-builder .fi-fo-repeater-actions {
        flex: 0 0 100%;
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
        margin-bottom: 0.15rem;
        font-size: 0.75rem;
    }

    .flow-builder .fi-fo-repeater-items {
        display: flex;
        flex: 1 1 auto;
        flex-wrap: wrap;
        align-items: flex-start !important;
        gap: 1.75rem 2.25rem;
        margin: 0;
        padding: 0.15rem 0.15rem 0.5rem;
        list-style: none;
        min-width: 0;
    }

    .flow-builder .fi-fo-repeater-item {
        counter-increment: flow-step;
        position: relative;
        flex: 0 0 var(--flow-card-width);
        min-width: var(--flow-card-width);
        max-width: var(--flow-card-width);
        margin: 0 !important;
        border: 1px solid rgb(63 63 70);
        border-radius: 0.65rem;
        background: rgb(39 39 42);
        box-shadow: 0 1px 0 rgb(255 255 255 / 0.03) inset;
        transition: border-color 0.15s ease, box-shadow 0.15s ease, max-width 0.2s ease, flex-basis 0.2s ease;
        overflow: hidden;
    }

    .flow-builder .fi-fo-repeater-item.fi-collapsed {
        min-height: 0;
    }

    .flow-builder .fi-fo-repeater-item:not(.fi-collapsed) {
        flex: 1 0 100%;
        min-width: 100%;
        max-width: 100%;
        order: 10;
        border-color: rgb(82 82 91);
        box-shadow: 0 10px 28px rgb(0 0 0 / 0.18);
    }

    .flow-builder .fi-fo-repeater-item.fi-collapsed:not(:last-child)::after {
        content: '';
        position: absolute;
        top: calc(var(--flow-card-min-height) / 2);
        right: -2.25rem;
        width: 2.25rem;
        height: 1px;
        background: rgb(82 82 91);
        pointer-events: none;
    }

    .flow-builder .fi-fo-repeater-item.fi-collapsed:not(:last-child)::before {
        content: '';
        position: absolute;
        top: calc(var(--flow-card-min-height) / 2);
        right: -0.45rem;
        width: 0.35rem;
        height: 0.35rem;
        transform: translateY(-50%) rotate(45deg);
        border-top: 1px solid rgb(113 113 122);
        border-right: 1px solid rgb(113 113 122);
        pointer-events: none;
    }

    .flow-builder .fi-fo-repeater-item:not(.fi-collapsed)::before,
    .flow-builder .fi-fo-repeater-item:not(.fi-collapsed)::after {
        display: none;
    }

    .flow-builder .fi-fo-repeater-item:hover.fi-collapsed {
        border-color: rgb(82 82 91);
    }

    .flow-builder .fi-fo-repeater-item-header {
        display: flex;
        align-items: flex-start;
        gap: 0.25rem;
        padding: 0.55rem 0.45rem 0.55rem 0.35rem;
        min-height: var(--flow-card-min-height);
        border: 0 !important;
        background: transparent !important;
        cursor: pointer;
    }

    .flow-builder .fi-fo-repeater-item:not(.fi-collapsed) .fi-fo-repeater-item-header {
        min-height: 0;
        padding-bottom: 0.45rem;
        border-bottom: 1px solid rgb(63 63 70) !important;
        cursor: default;
    }

    .flow-builder .fi-fo-repeater-item-header-start-actions {
        display: flex;
        align-items: center;
        padding: 0;
        margin: 0;
        list-style: none;
        opacity: 0.35;
        transition: opacity 0.15s ease;
    }

    .flow-builder .fi-fo-repeater-item:hover .fi-fo-repeater-item-header-start-actions,
    .flow-builder .fi-fo-repeater-item:not(.fi-collapsed) .fi-fo-repeater-item-header-start-actions {
        opacity: 0.85;
    }

    .flow-builder .fi-fo-repeater-item-header-start-actions .fi-icon-btn {
        width: 1.5rem;
        height: 1.5rem;
    }

    .flow-builder .fi-fo-repeater-item-header-label {
        flex: 1;
        min-width: 0;
        margin: 0;
        padding: 0 0.15rem 0 0;
        font-size: inherit;
        font-weight: inherit;
        line-height: inherit;
    }

    .flow-builder .fi-fo-repeater-item-header-label::before {
        content: counter(flow-step);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1.15rem;
        height: 1.15rem;
        margin: 0 0.35rem 0.15rem 0;
        padding: 0 0.25rem;
        border-radius: 0.35rem;
        background: rgb(24 24 27);
        color: rgb(161 161 170);
        font-size: 0.62rem;
        font-weight: 700;
        vertical-align: top;
    }

    .flow-step-card {
        min-width: 0;
    }

    .flow-step-card__head {
        display: flex;
        align-items: center;
        margin-bottom: 0.2rem;
    }

    .flow-step-card__type {
        display: inline-flex;
        align-items: center;
        padding: 0.1rem 0.45rem;
        border-radius: 9999px;
        font-size: 0.68rem;
        font-weight: 600;
        line-height: 1.2;
        color: var(--step-color, rgb(212 212 216));
        background: color-mix(in srgb, var(--step-color, rgb(113 113 122)) 14%, transparent);
        border: 1px solid color-mix(in srgb, var(--step-color, rgb(113 113 122)) 24%, transparent);
    }

    .flow-step-card__preview {
        margin: 0;
        font-size: 0.72rem;
        line-height: 1.35;
        color: rgb(212 212 216);
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        word-break: break-word;
    }

    .flow-step-card__chips {
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem;
        margin-top: 0.35rem;
    }

    .flow-step-card__chip {
        display: inline-flex;
        align-items: center;
        padding: 0.08rem 0.35rem;
        border-radius: 0.35rem;
        background: rgb(24 24 27);
        border: 1px solid rgb(63 63 70);
        font-size: 0.62rem;
        line-height: 1.3;
        color: rgb(113 113 122);
    }

    .flow-builder .fi-fo-repeater-item-header-end-actions {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 0;
        padding: 0;
        margin: 0 0 0 auto;
        list-style: none;
        border: 0;
        opacity: 0.35;
        transition: opacity 0.15s ease;
    }

    .flow-builder .fi-fo-repeater-item:hover .fi-fo-repeater-item-header-end-actions,
    .flow-builder .fi-fo-repeater-item:not(.fi-collapsed) .fi-fo-repeater-item-header-end-actions {
        opacity: 1;
    }

    .flow-builder .fi-fo-repeater-item-header-end-actions .fi-icon-btn {
        width: 1.55rem;
        height: 1.55rem;
    }

    .flow-builder .fi-fo-repeater-item.fi-collapsed .fi-fo-repeater-item-header-collapse-action {
        display: none;
    }

    .flow-builder .fi-fo-repeater-item:not(.fi-collapsed) .fi-fo-repeater-item-header-expand-action {
        display: none;
    }

    .flow-builder .fi-fo-repeater-item-content {
        border-top: 0;
        background: rgb(24 24 27);
        padding: 0.85rem 1rem 1rem !important;
    }

    .flow-builder .fi-fo-repeater-item.fi-collapsed .fi-fo-repeater-item-content {
        display: none !important;
    }

    .flow-builder .fi-fo-repeater-item:not(.fi-collapsed) .fi-fo-repeater-item-content .fi-sc {
        gap: 0.85rem;
    }

    .flow-builder .fi-fo-repeater-add {
        flex: 0 0 var(--flow-card-width);
        align-self: flex-start;
        margin: 0 0 0 0.15rem;
        padding-top: 0;
    }

    .flow-builder .fi-fo-repeater-add .fi-btn {
        width: 100%;
        min-height: var(--flow-card-min-height);
        border: 1px dashed rgb(82 82 91) !important;
        border-radius: 0.65rem !important;
        background: rgb(24 24 27 / 0.35) !important;
        color: rgb(161 161 170) !important;
        box-shadow: none !important;
        font-size: 0.78rem !important;
    }

    .flow-builder .fi-fo-repeater-add .fi-btn:hover {
        border-color: rgb(245 158 11 / 0.55) !important;
        color: rgb(251 191 36) !important;
        background: rgb(39 39 42 / 0.75) !important;
    }

    @media (max-width: 1024px) {
        .flow-builder-config.fi-section,
        .flow-builder-section.fi-section {
            grid-column: 1 / -1;
        }
    }

    @media (max-width: 768px) {
        .flow-builder.fi-fo-repeater {
            --flow-card-width: 10.5rem;
        }

        .flow-builder .fi-fo-repeater-items {
            gap: 1.5rem 2rem;
        }
    }
</style>
