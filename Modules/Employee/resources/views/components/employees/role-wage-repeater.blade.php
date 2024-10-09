@props(['employee' => null, 'roles'])
<label @class(['form-label'])>@lang('employee::fields.pos_roles_wages')</label>
<div id="role-wage-repeater">
    <div class="form-group">
        <div data-repeater-list="role-wage-repeater" class="d-flex flex-column gap-3">
            <div data-repeater-item class="d-flex flex-wrap align-items-center gap-3">
                <div class="w-100 fv-row flex-md-root">
                    <x-employee::form.select name="roles" :options=$roles :errors=$errors
                        value="{{ $employee?->roles?->first()?->id }}" />
                </div>
                <x-employee::form.input-div class="w-100">
                    <x-employee::form.input :errors=$errors type="number"
                        placeholder="{{ __('employee::fields.wage') }}" name="wage" />
                </x-employee::form.input-div>
                <a href="javascript:;" data-repeater-delete
                    class="btn btn-sm btn-icon btn-light-danger">
                    <i class="ki-outline ki-cross fs-1"></i>
                </a>
            </div>
        </div>
    </div>
    <div class="form-group mt-7">
        <a href="javascript:;" data-repeater-create class="btn btn-sm btn-light-primary">
            <i class="ki-outline ki-plus fs-2"></i>@lang('employee::general.add_more_roles')</a>
    </div>
</div>