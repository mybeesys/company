<div class="card shadow-sm border border-dashed border-gray-400 mt-10 mb-5" data-section="expense-notes">
    <div class="card-header border-0 pt-6 pb-0 px-6 px-lg-8">
        <h3 class="card-title fw-bold fs-4 text-gray-900 mb-0">@lang('expense::lang.notes_and_attachments_title')</h3>
    </div>
    <div class="card-body pt-6 pb-8 px-6 px-lg-10">
        <div class="row g-8">
            <div class="col-12 fv-row fv-plugins-icon-container">
                <label class="fs-6 fw-semibold mb-2 required" for="expense_description">@lang('expense::lang.field_description')</label>
                <textarea id="expense_description" name="description" rows="3" class="form-control form-control-solid"
                    required style="resize: vertical; min-height: 5rem;"></textarea>
            </div>

            <div class="col-12 fv-row fv-plugins-icon-container mb-0">
                <label class="fs-6 fw-semibold mb-3 d-block">@lang('expense::lang.field_attachments')</label>

                <div class="dropzone dz-clickable rounded-3 border border-gray-300 border-dashed bg-body-secondary bg-opacity-40 py-2 px-4 px-lg-6"
                    id="kt_expense_upload_attachments" role="button" tabindex="0">
                    <div class="dz-message needsclick d-flex flex-column flex-sm-row align-items-center justify-content-center gap-4 py-6 py-lg-8">
                        <span class="symbol symbol-50px symbol-circle bg-light-primary flex-shrink-0">
                            <i class="ki-outline ki-file-up fs-2x text-primary"></i>
                        </span>
                        <div class="text-center text-sm-start flex-grow-1">
                            <span class="fw-bold text-gray-900 fs-5 d-block mb-1">@lang('accounting::lang.upload_attachment')</span>
                            <span id="expense_upload_instructions" class="fw-semibold fs-6 text-muted">@lang('accounting::lang.upload_file')</span>
                        </div>
                    </div>
                </div>

                <input type="file" id="expense_attachments_input" name="attachments[]" multiple class="d-none">
            </div>
        </div>
    </div>
</div>
