{{-- Modifiers & combos picker for web sell / quotation --}}
@php
    $sellWithModifiersCombos = $sellWithModifiersCombos ?? false;
@endphp
@if ($sellWithModifiersCombos)
<div class="modal fade" id="sellModifiersCombosModal" tabindex="-1" aria-hidden="true" aria-labelledby="sellModifiersCombosModalTitle">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg smc-modal">
            <div class="smc-modal__hero">
                <div class="smc-modal__hero-media">
                    <img src="" alt="" id="smcProductImage" class="smc-modal__image d-none">
                    <div class="smc-modal__image-fallback" id="smcProductImageFallback" aria-hidden="true">
                        <i class="ki-outline ki-parcel fs-2x"></i>
                    </div>
                </div>
                <div class="smc-modal__hero-copy">
                    <div class="text-muted fs-8 text-uppercase fw-semibold mb-1">@lang('sales::lang.smc_customize_item')</div>
                    <h3 class="smc-modal__title mb-1" id="sellModifiersCombosModalTitle"></h3>
                    <div class="smc-modal__base-price">
                        <span id="smcBasePrice"></span>
                        <span class="text-muted fs-8 ms-1">@lang('sales::lang.smc_base_price')</span>
                    </div>
                </div>
                <button type="button" class="btn-close smc-modal__close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body smc-modal__body">
                <div id="smcModifiersSection" class="d-none mb-6">
                    <h4 class="fs-6 fw-bold text-gray-800 mb-3">@lang('sales::lang.smc_modifiers_title')</h4>
                    <div id="smcModifiersGroups" class="d-flex flex-column gap-4"></div>
                </div>

                <div id="smcCombosSection" class="d-none mb-4">
                    <h4 class="fs-6 fw-bold text-gray-800 mb-3">@lang('sales::lang.smc_combos_title')</h4>
                    <div id="smcComboGroups" class="d-flex flex-column gap-4"></div>
                </div>

                <div id="smcEmptyState" class="text-muted text-center py-8 d-none">
                    @lang('sales::lang.smc_no_extras')
                </div>
            </div>

            <div class="smc-modal__footer">
                <div class="smc-modal__summary">
                    <div class="text-muted fs-8">@lang('sales::lang.smc_line_estimate')</div>
                    <div class="fs-3 fw-bold text-gray-900" id="smcEstimateTotal">0.00</div>
                </div>
                <div class="d-flex flex-wrap gap-2 justify-content-end">
                    <button type="button" class="btn btn-light" id="smcCancelBtn" data-bs-dismiss="modal">@lang('messages.cancel')</button>
                    <button type="button" class="btn btn-light-primary" id="smcSkipBtn">@lang('sales::lang.smc_skip_extras')</button>
                    <button type="button" class="btn btn-warning" id="smcConfirmBtn">@lang('sales::lang.smc_confirm')</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .smc-modal {
        border-radius: 1rem;
        overflow: hidden;
    }
    .smc-modal__hero {
        position: relative;
        display: flex;
        gap: 1rem;
        align-items: center;
        padding: 1.25rem 1.5rem;
        background: linear-gradient(135deg, rgba(212, 160, 23, 0.12), rgba(245, 248, 250, 0.95));
        border-bottom: 1px solid #eef0f4;
    }
    .smc-modal__hero-media {
        width: 72px;
        height: 72px;
        border-radius: 0.85rem;
        overflow: hidden;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
    }
    .smc-modal__image {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .smc-modal__image-fallback {
        color: #b5b5c3;
    }
    .smc-modal__title {
        font-size: 1.15rem;
        font-weight: 700;
        color: #1e1e2d;
        margin: 0;
    }
    .smc-modal__base-price {
        font-size: 0.95rem;
        font-weight: 700;
        color: #16a34a;
    }
    .smc-modal__close {
        position: absolute;
        top: 1rem;
        inset-inline-end: 1rem;
    }
    .smc-modal__body {
        padding: 1.25rem 1.5rem;
        max-height: min(55vh, 480px);
    }
    .smc-group-label {
        font-size: 0.8rem;
        font-weight: 700;
        color: #5e6278;
        margin-bottom: 0.55rem;
    }
    .smc-chip-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .smc-chip {
        appearance: none;
        border: 1px solid #e4e6ef;
        background: #fff;
        border-radius: 999px;
        padding: 0.45rem 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        cursor: pointer;
        transition: border-color .15s ease, background-color .15s ease, box-shadow .15s ease;
        font-size: 0.82rem;
        font-weight: 600;
        color: #3f4254;
    }
    .smc-chip:hover {
        border-color: #e5c76b;
        background: #fffbeb;
    }
    .smc-chip.is-selected {
        border-color: #d4a017;
        background: #fff8e1;
        box-shadow: 0 0 0 1px rgba(212, 160, 23, 0.25);
        color: #7a5a00;
    }
    .smc-chip__price {
        font-size: 0.72rem;
        font-weight: 700;
        color: #16a34a;
    }
    .smc-modal__footer {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.5rem 1.25rem;
        border-top: 1px solid #eef0f4;
        background: #fafbfc;
    }
    .smc-extras-wrap {
        margin-top: 0.45rem;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.35rem;
    }
    .smc-extras-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.15rem 0.5rem;
        border-radius: 999px;
        background: #f4f6fa;
        border: 1px solid #e8ebf1;
        font-size: 0.68rem;
        font-weight: 600;
        color: #5e6278;
        max-width: 100%;
    }
    .smc-extras-chip--combo {
        background: #eef6ff;
        border-color: #d6e8ff;
        color: #3e6aa8;
    }
    .smc-edit-extras {
        font-size: 0.72rem;
        padding: 0.1rem 0.45rem;
    }
    @media (max-width: 575.98px) {
        .smc-modal__footer {
            flex-direction: column;
            align-items: stretch;
        }
        .smc-modal__footer .d-flex {
            justify-content: stretch !important;
        }
        .smc-modal__footer .btn {
            flex: 1;
        }
    }
</style>
@endif
