@props(['roles'])
@php
    $endStatusOptions = [
        ['id' => 'clockout', 'name' => __('employee::fields.clockout')],
        ['id' => 'break', 'name' => __('employee::fields.break')],
    ];
@endphp
<div class="modal fade" id="schedule_shift" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-1000px">
        <div class="modal-content">
            <div class="modal-header mb-2 px-10 py-5">
                <div class="d-flex flex-column gap-2">
                    <h2 class="fw-bold work-time-modal-title"></h2>
                    <spab class="work-time-hint" style="color: #347cff;"></spab>
                </div>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-outline ki-cross fs-1"></i>
                </div>
            </div>
            <div class="modal-body mx-5 pt-5">
                <form id="schedule_shift_form" class="form" action="#">
                    @csrf
                    <div id="kt_modal_update_schedule_shift_scroll">
                        <div class="repeater-error-template d-none">
                            <div class="invalid-feedback repeater-error mb-5 mt-n2"></div>
                        </div>
                        <div id="error-container"></div>
                        <div id="schedule_shift_repeater">
                            <div class="form-group">
                                <div class="d-flex align-items-center gap-3 px-2 mb-2">
                                    <label class="w-100px">@lang('employee::fields.start_time')</label>
                                    <span class="px-1"></span>
                                    <label class="w-100px">@lang('employee::fields.end_time')</label>
                                    <label style="width: 316.38px;">@lang('employee::fields.end_status')</label>
                                    <label style="width: 316.38px;" class="ps-2">@lang('employee::fields.role')</label>
                                    <label style="width: 34.83px;"></label>
                                </div>
                                <div data-repeater-list="schedule_shift_repeater" class="d-flex flex-column gap-3">
                                    <div data-repeater-item class="d-flex align-items-center gap-3">
                                        <x-form.input :errors="$errors" required :placeholder="__('employee::fields.h_m')"
                                            name="schedule_shift_repeater[][startTime]"
                                            class="form-control-solid py-2 w-100px" />
                                        <span>-</span>
                                        <x-form.input :errors="$errors" required :placeholder="__('employee::fields.h_m')"
                                            name="schedule_shift_repeater[][endTime]"
                                            class="form-control-solid py-2 w-100px" />
                                        <x-form.input-div class="w-100">
                                            <x-form.select name="schedule_shift_repeater[][end_status]" required
                                                :options="$endStatusOptions" :errors="$errors" data_allow_clear="false" />
                                        </x-form.input-div>
                                        <x-form.input-div class="w-100">
                                            <x-form.select name="dashboard_role_repeater[][role]" required
                                                data_allow_clear="false" :options=$roles :errors="$errors" />
                                        </x-form.input-div>
                                        <input type="hidden" name="schedule_shift_repeater[][shift_id]">
                                        <button type="button" data-repeater-delete
                                            class="btn btn-sm btn-icon btn-light-danger">
                                            <i class="ki-outline ki-cross fs-1"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group mt-7">
                                <button type="button" data-repeater-create class="btn btn-sm btn-light-primary">
                                    <i class="ki-outline ki-plus fs-2"></i>@lang('employee::general.add_more_shifts')</button>
                            </div>
                        </div>
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
