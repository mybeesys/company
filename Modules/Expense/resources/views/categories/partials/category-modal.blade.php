<div class="modal fade" id="expense-category-modal" tabindex="-1" aria-labelledby="expense-category-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-500px">
        <div class="modal-content" @if (app()->getLocale() === 'ar') dir="rtl" @endif>
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="expense-category-modal-label">
                    <span id="expense_category_modal_title">@lang('expense::lang.add_category_heading')</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="expense-category-form" method="post" action="{{ route('expenses.categories.store') }}">
                @csrf
                <input type="hidden" name="_method" id="expense_category_method" value="POST" disabled>
                <input type="hidden" name="category_id" id="expense_category_id" value="">
                <div class="modal-body pt-2 pb-6">
                    <div class="fv-row fv-plugins-icon-container">
                        <label class="fs-6 fw-semibold mb-2 required" for="expense_category_name">@lang('expense::lang.field_category')</label>
                        <input type="text" class="form-control form-control-solid" name="name" id="expense_category_name"
                            maxlength="255" required autocomplete="off">
                        <div class="invalid-feedback d-block" id="expense_category_name_error"></div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">@lang('messages.cancel')</button>
                    <button type="submit" class="btn btn-primary" id="expense_category_submit_btn">@lang('messages.save')</button>
                </div>
            </form>
        </div>
    </div>
</div>
