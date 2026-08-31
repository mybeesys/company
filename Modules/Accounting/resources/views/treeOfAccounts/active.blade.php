<div class="modal fade" id="kt_modal_active" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-520px">
        <div class="modal-content coa-status-modal" @if (app()->getLocale() == 'ar') dir="rtl" @endif>
            <form action="{{ route('change-status-account') }}" method="POST">
                @csrf
                <input type="hidden" id="account_id_A" name="account_id" value="">
                <div class="modal-header border-0 pb-2">
                    <div class="d-flex align-items-center gap-3 flex-grow-1">
                        <span class="coa-status-modal__icon coa-status-modal__icon--success">
                            <i class="fas fa-check-circle"></i>
                        </span>
                        <div>
                            <h3 class="modal-title fs-4 fw-bold mb-0">@lang('accounting::lang.coa_activate_modal_title')</h3>
                            <span class="text-muted fs-7">@lang('accounting::lang.coa_activate_modal_subtitle')</span>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-icon btn-active-light-primary" data-bs-dismiss="modal"
                        aria-label="@lang('messages.cancel')">
                        <i class="ki-outline ki-cross fs-2"></i>
                    </button>
                </div>
                <div class="modal-body pt-2 pb-4">
                    <div class="coa-status-modal__account-box mb-4">
                        <span class="text-muted fs-8 d-block mb-1">@lang('accounting::lang.coa_account_name')</span>
                        <span class="fw-semibold text-gray-800" id="coa_active_account_label">—</span>
                    </div>
                    <p class="text-gray-700 fs-7 mb-3">@lang('accounting::lang.coa_activate_modal_lead')</p>
                    <ul class="coa-status-modal__points mb-0">
                        <li>@lang('accounting::lang.coa_activate_modal_point_1')</li>
                        <li>@lang('accounting::lang.coa_activate_modal_point_2')</li>
                        <li>@lang('accounting::lang.coa_activate_modal_point_3')</li>
                    </ul>
                </div>
                <div class="modal-footer border-0 pt-0 gap-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">@lang('messages.cancel')</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check-circle me-1"></i>@lang('messages.activate')
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
