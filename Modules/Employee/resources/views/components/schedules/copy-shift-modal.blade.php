@props(['roles'])
@php
    $endStatusOptions = [
        ['id' => 'clockout', 'name' => __('employee::fields.clockout')],
        ['id' => 'break', 'name' => __('employee::fields.break')],
    ];
@endphp
<div class="modal fade" id="schedule_shift_copy" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-600px">
        <div class="modal-content">
            <div class="modal-header px-8 py-5">
                <h2 class="copy-shifts-modal-title fs-5 mb-0 text-danger"></h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-outline ki-cross fs-1"></i>
                </div>
            </div>
            <div class="modal-body mx-5 pt-5">
                <form id="schedule_shift_copy_form" class="form" action="#">
                    @csrf
                    <div id="kt_modal_update_schedule_shift_copy_scroll">
                        <x-form.input-div class="mb-8 w-100 d-flex align-items-center" :row=false>
                            <label class="w-100 fs-5">@lang('employee::general.select_week_to_copy_to')</label>
                            <x-form.input class="form-control form-control-solid" name="copyShiftDatePicker" />
                        </x-form.input-div>
                    </div>
                    <div class="text-center pt-5">
                        <button type="reset" class="btn btn-light me-3"
                            data-bs-dismiss="modal">@lang('general.cancel')</button>
                        <button type="submit" class="submit-form-btn btn btn-primary"
                            data-kt-schedule-shift-modal-action="submit">
                            <span class="indicator-label">@lang('general.save')</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
