<style>
    .flow-builder-section.fi-section {
        overflow: visible;
    }

    .flow-builder-section > .fi-section-header {
        margin-bottom: 0.5rem;
    }

    .flow-builder-empty-hint {
        margin: 0 0 0.75rem;
        padding: 0.65rem 0.85rem;
        border: 1px dashed rgb(63 63 70);
        border-radius: 0.65rem;
        font-size: 0.76rem;
        line-height: 1.45;
        color: rgb(161 161 170);
        background: rgb(24 24 27 / 0.5);
    }

    .flow-steps-repeater.fi-fo-repeater {
        counter-reset: flow-step;
    }

    .flow-steps-repeater.fi-fo-repeater > .fi-fo-field-wrp-label {
        display: none;
    }

    .flow-steps-repeater .fi-fo-repeater-actions {
        display: none;
    }

    .flow-steps-repeater .fi-fo-repeater-items {
        display: flex;
        flex-direction: column;
        gap: 0.65rem;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .flow-steps-repeater .fi-fo-repeater-item {
        counter-increment: flow-step;
        position: relative;
        width: 100%;
        margin: 0 !important;
        border: 1px solid rgb(63 63 70);
        border-radius: 0.65rem;
        background: rgb(39 39 42);
        box-shadow: 0 1px 0 rgb(255 255 255 / 0.03) inset;
        overflow: hidden;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .flow-steps-repeater .fi-fo-repeater-item:not(.fi-collapsed) {
        border-color: rgb(82 82 91);
        box-shadow: 0 8px 24px rgb(0 0 0 / 0.16);
    }

    .flow-steps-repeater .fi-fo-repeater-item-header {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        min-height: 3.35rem;
        padding: 0.55rem 0.65rem;
        border: 0 !important;
        background: transparent !important;
        cursor: pointer;
    }

    .flow-steps-repeater .fi-fo-repeater-item-header-start-actions {
        display: flex;
        align-items: center;
        padding: 0;
        margin: 0;
        list-style: none;
        opacity: 0.45;
        transition: opacity 0.15s ease;
    }

    .flow-steps-repeater .fi-fo-repeater-item:hover .fi-fo-repeater-item-header-start-actions,
    .flow-steps-repeater .fi-fo-repeater-item:not(.fi-collapsed) .fi-fo-repeater-item-header-start-actions {
        opacity: 0.9;
    }

    .flow-steps-repeater .fi-fo-repeater-item-header-start-actions .fi-icon-btn {
        width: 1.45rem;
        height: 1.45rem;
    }

    .flow-steps-repeater .fi-fo-repeater-item-header-label {
        flex: 1;
        min-width: 0;
        margin: 0;
        padding: 0;
        font-size: inherit;
        font-weight: inherit;
        line-height: inherit;
    }

    .flow-steps-repeater .fi-fo-repeater-item-header-label::before {
        content: counter(flow-step);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1.35rem;
        height: 1.35rem;
        margin-right: 0.45rem;
        border-radius: 0.4rem;
        background: rgb(24 24 27);
        color: rgb(161 161 170);
        font-size: 0.65rem;
        font-weight: 700;
        vertical-align: middle;
    }

    .flow-step-card {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        min-width: 0;
    }

    .flow-step-card__accent {
        width: 0.2rem;
        align-self: stretch;
        flex-shrink: 0;
        border-radius: 9999px;
        background: var(--step-color, rgb(113 113 122));
    }

    .flow-step-card__body {
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
        min-width: 0;
        flex: 1;
    }

    .flow-step-card__head {
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }

    .flow-step-card__type {
        display: inline-flex;
        align-items: center;
        padding: 0.08rem 0.45rem;
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
        font-size: 0.74rem;
        line-height: 1.35;
        color: rgb(212 212 216);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .flow-step-card__chips {
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem;
        margin-top: 0.15rem;
    }

    .flow-step-card__chip {
        display: inline-flex;
        align-items: center;
        padding: 0.06rem 0.35rem;
        border-radius: 0.35rem;
        background: rgb(24 24 27);
        border: 1px solid rgb(63 63 70);
        font-size: 0.62rem;
        line-height: 1.3;
        color: rgb(113 113 122);
    }

    .flow-steps-repeater .fi-fo-repeater-item-header-end-actions {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 0;
        padding: 0;
        margin: 0 0 0 0.35rem;
        list-style: none;
        border: 0;
        opacity: 0.45;
        transition: opacity 0.15s ease;
    }

    .flow-steps-repeater .fi-fo-repeater-item:hover .fi-fo-repeater-item-header-end-actions,
    .flow-steps-repeater .fi-fo-repeater-item:not(.fi-collapsed) .fi-fo-repeater-item-header-end-actions {
        opacity: 1;
    }

    .flow-steps-repeater .fi-fo-repeater-item-header-end-actions .fi-icon-btn {
        width: 1.5rem;
        height: 1.5rem;
    }

    .flow-steps-repeater .fi-fo-repeater-item.fi-collapsed .fi-fo-repeater-item-header-collapse-action {
        display: none;
    }

    .flow-steps-repeater .fi-fo-repeater-item:not(.fi-collapsed) .fi-fo-repeater-item-header-expand-action {
        display: none;
    }

    .flow-steps-repeater .fi-fo-repeater-item-content {
        border-top: 1px solid rgb(63 63 70);
        background: rgb(24 24 27);
        padding: 0.85rem 1rem 1rem !important;
    }

    .flow-steps-repeater .fi-fo-repeater-item.fi-collapsed .fi-fo-repeater-item-content {
        display: none !important;
    }

    .flow-steps-repeater .fi-fo-repeater-add {
        margin-top: 0.35rem;
    }

    .flow-steps-repeater .fi-fo-repeater-add .fi-btn {
        border: 1px dashed rgb(82 82 91) !important;
        border-radius: 0.65rem !important;
        background: rgb(24 24 27 / 0.35) !important;
        color: rgb(161 161 170) !important;
        box-shadow: none !important;
        font-size: 0.8rem !important;
    }

    .flow-steps-repeater .fi-fo-repeater-add .fi-btn:hover {
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

    @media (max-width: 640px) {
        .flow-step-card {
            flex-direction: column;
            align-items: flex-start;
        }

        .flow-step-card__accent {
            width: 100%;
            height: 0.2rem;
            align-self: auto;
        }
    }
</style>
