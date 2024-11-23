@props(['roles'])
@php
    $endStatusOptions = [
        ['id' => 'clockout', 'name' => __('employee::fields.clockout')],
        ['id' => 'break', 'name' => __('employee::fields.break')],
    ];
@endphp
<x-employee::general.modal class="mw-1000px" header_class="px-10 py-5" body_class="pt-0" id="shift_add">
    <x-slot:header>
        <div class="d-flex flex-column gap-2">
            <h2 class="fw-bold work-time-modal-title"></h2>
            <span class="work-time-hint" style="color: #347cff;"></span>
        </div>
    </x-slot:header>
    <div>
        <div class="repeater-error-template d-none">
            <div class="invalid-feedback repeater-error mb-5 mt-n2"></div>
        </div>
        <div id="error-container"></div>
        <div id="shift_repeater">
            <div class="form-group">
                <div class="d-flex align-items-center gap-3 px-2 mb-2">
                    <label class="w-100px">@lang('employee::fields.start_time')</label>
                    <span class="px-1"></span>
                    <label class="w-100px">@lang('employee::fields.end_time')</label>
                    <label style="width: 316.38px;">@lang('employee::fields.end_status')</label>
                    <label style="width: 316.38px;" class="ps-2">@lang('employee::fields.role')</label>
                    <label style="width: 34.83px;"></label>
                </div>
                <div data-repeater-list="shift_repeater" class="d-flex flex-column gap-3">
                    <div data-repeater-item class="d-flex align-items-center gap-3">
                        <x-form.input :errors="$errors" required :placeholder="__('employee::fields.h_m')" name="shift_repeater[][startTime]"
                            class="form-control-solid py-2 w-100px" readonly />
                        <span>-</span>
                        <x-form.input :errors="$errors" required :placeholder="__('employee::fields.h_m')" name="shift_repeater[][endTime]"
                            class="form-control-solid py-2 w-100px" readonly />
                        <x-form.input-div class="w-100">
                            <x-form.select name="shift_repeater[][end_status]" required :options="$endStatusOptions"
                                :errors="$errors" data_allow_clear="false" />
                        </x-form.input-div>
                        <x-form.input-div class="w-100">
                            <x-form.select name="shift_repeater[][role]" required data_allow_clear="false"
                                :options="[]" :errors="$errors" />
                        </x-form.input-div>
                        <input type="hidden" name="shift_repeater[][shift_id]">
                        <button type="button" data-repeater-delete class="btn btn-sm btn-icon btn-light-danger">
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
</x-employee::general.modal>