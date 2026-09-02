@props(['dashboardRole' => null, 'modules', 'rolePermissions' => null, 'disabled' => false, 'formId' => null])
@php
    $pageTitle = $disabled
        ? __('employee::general.show_role')
        : ($dashboardRole ? __('employee::general.edit_role') : __('employee::general.add_role'));
    $isActive = (bool) ($dashboardRole?->is_active ?? false);
@endphp
@once
<style>
    .ems-role-page .card { border-radius: 14px; }
    .ems-role-actions {
        position: sticky;
        bottom: 0;
        z-index: 20;
        background: linear-gradient(180deg, rgba(255,255,255,0) 0%, #fff 28%);
        padding-top: 1.15rem;
        padding-bottom: .2rem;
    }
    .ems-role-details {
        display: grid;
        grid-template-columns: minmax(0, 1.4fr) minmax(9rem, .7fr) minmax(12rem, .9fr);
        gap: 1rem;
        align-items: stretch;
    }
    .ems-role-field {
        background: #f9fafb;
        border: 1px solid #eef1f5;
        border-radius: 12px;
        padding: .9rem 1rem 1rem;
        height: 100%;
    }
    .ems-role-field .form-label {
        font-size: .78rem;
        font-weight: 700;
        color: #7e8299;
        margin-bottom: .45rem;
    }
    .ems-role-field .form-control {
        background: #fff;
        border-color: #e9edf3;
        min-height: 44px;
    }
    .ems-role-status {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .85rem;
        background: #fff;
        border: 1px solid #eef1f5;
        border-radius: 12px;
        padding: .9rem 1rem;
        height: 100%;
        min-height: 100%;
    }
    .ems-role-status__kicker {
        font-size: .72rem;
        font-weight: 700;
        color: #7e8299;
        margin-bottom: .2rem;
    }
    .ems-role-status__value {
        font-size: .95rem;
        font-weight: 700;
        color: #3f4254;
    }
    .ems-role-status.is-on {
        background: linear-gradient(135deg, var(--bs-primary-light, #f8efcf) 0%, #fff 70%);
        border-color: var(--bs-primary-border-subtle, #eed592);
    }
    .ems-role-status.is-on .ems-role-status__value {
        color: var(--bs-text-primary, #946f11);
    }
    @media (max-width: 991.98px) {
        .ems-role-details {
            grid-template-columns: 1fr 1fr;
        }
        .ems-role-status { grid-column: 1 / -1; }
    }
    @media (max-width: 575.98px) {
        .ems-role-details { grid-template-columns: 1fr; }
        .ems-role-status { grid-column: auto; }
    }
</style>
@endonce
<div class="ems-role-page d-flex flex-column flex-row-fluid gap-5 gap-lg-7">
    <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
        <div>
            <h1 class="fs-2 fw-bold text-gray-900 mb-1">{{ $pageTitle }}</h1>
            <p class="text-muted fs-7 mb-0">@lang('employee::permissions.role_page_lead')</p>
        </div>
    </div>

    <x-form.form-card :title="__('employee::general.role_details')" bodyClass="pt-2">
        <div class="ems-role-details">
            <div class="ems-role-field">
                <x-form.input-div class="mb-0">
                    <x-form.input required :errors=$errors :disabled=$disabled
                        placeholder="{{ __('employee::fields.name') }}"
                        value="{{ $dashboardRole?->name }}" name="name" :label="__('employee::fields.name')" />
                </x-form.input-div>
            </div>
            <div class="ems-role-field">
                <x-form.input-div class="mb-0">
                    <x-form.input required :errors=$errors placeholder="1–999"
                        :disabled=$disabled value="{{ $dashboardRole?->rank }}" name="rank" :label="__('employee::fields.rank')" />
                </x-form.input-div>
            </div>
            <div class="ems-role-status {{ $isActive ? 'is-on' : '' }}" data-ems-role-status
                data-label-on="@lang('employee::permissions.role_active')"
                data-label-off="@lang('employee::permissions.role_inactive')">
                <div>
                    <div class="ems-role-status__kicker">@lang('employee::permissions.role_status')</div>
                    <div class="ems-role-status__value" data-ems-role-status-label>
                        {{ $isActive ? __('employee::permissions.role_active') : __('employee::permissions.role_inactive') }}
                    </div>
                </div>
                <x-form.switch-div class="mb-0">
                    <input type="hidden" name="is_active" value="0">
                    <x-form.input :errors=$errors class="form-check-input" :solid="false" value="1" type="checkbox"
                        labelClass="d-none" name="is_active" :disabled=$disabled
                        label="{{ __('employee::permissions.role_status') }}"
                        checked="{{ $dashboardRole?->is_active }}" />
                </x-form.switch-div>
            </div>
        </div>
    </x-form.form-card>

    <x-employee::dashboard-roles.permissions-input :modules=$modules :rolePermissions=$rolePermissions :disabled=$disabled />

    <div @class(['ems-role-actions' => ! $disabled])>
        <x-form.form-buttons cancelUrl="{{ url('/dashboard-role') }}" :disabled=$disabled :id=$formId />
    </div>
</div>
