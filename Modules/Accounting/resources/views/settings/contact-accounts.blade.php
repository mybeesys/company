@php
    $settings = $contactAccountSettings ?? [];
    $localeAr = app()->getLocale() === 'ar';
    $parents = $contactAccountParents ?? collect();
@endphp

<div class="fy-card">
    <div class="fy-card-header">
        <h2 class="fy-card-title">@lang('accounting::financial_year.contact_accounts_tab')</h2>
        <p class="fy-card-subtitle mb-0">@lang('accounting::lang.contact_accounts_settings_help')</p>
    </div>

    <div class="p-4 p-md-5">
        <form method="POST" action="{{ route('accounting.contact-accounts-settings.store') }}" class="d-flex flex-column gap-4">
            @csrf

            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="contact_accounts_auto_create"
                    name="auto_create" value="1" @checked(!empty($settings['auto_create']))>
                <label class="form-check-label fw-semibold" for="contact_accounts_auto_create">
                    @lang('accounting::lang.contact_accounts_auto_create')
                </label>
                <div class="fy-help">@lang('accounting::lang.contact_accounts_auto_create_help')</div>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="customer_parent_id">
                        @lang('accounting::lang.contact_accounts_customer_parent')
                    </label>
                    <select name="customer_parent_id" id="customer_parent_id" class="form-select form-select-solid select-2">
                        <option value="">@lang('accounting::lang.contact_accounts_choose_parent')</option>
                        @foreach ($parents as $parent)
                            <option value="{{ $parent->id }}"
                                @selected((int) ($settings['customer_parent_id'] ?? 0) === (int) $parent->id)>
                                ({{ $parent->gl_code }})
                                {{ $localeAr ? $parent->name_ar : $parent->name_en }}
                            </option>
                        @endforeach
                    </select>
                    <div class="fy-help">
                        @lang('accounting::lang.contact_accounts_customer_parent_help', [
                            'gl' => $settings['default_customer_gl'] ?? '113',
                        ])
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="supplier_parent_id">
                        @lang('accounting::lang.contact_accounts_supplier_parent')
                    </label>
                    <select name="supplier_parent_id" id="supplier_parent_id" class="form-select form-select-solid select-2">
                        <option value="">@lang('accounting::lang.contact_accounts_choose_parent')</option>
                        @foreach ($parents as $parent)
                            <option value="{{ $parent->id }}"
                                @selected((int) ($settings['supplier_parent_id'] ?? 0) === (int) $parent->id)>
                                ({{ $parent->gl_code }})
                                {{ $localeAr ? $parent->name_ar : $parent->name_en }}
                            </option>
                        @endforeach
                    </select>
                    <div class="fy-help">
                        @lang('accounting::lang.contact_accounts_supplier_parent_help', [
                            'gl' => $settings['default_supplier_gl'] ?? '211',
                        ])
                    </div>
                </div>
            </div>

            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="contact_accounts_show_in_coa"
                    name="show_in_coa" value="1" @checked(!empty($settings['show_in_coa']))>
                <label class="form-check-label fw-semibold" for="contact_accounts_show_in_coa">
                    @lang('accounting::lang.contact_accounts_show_in_coa')
                </label>
                <div class="fy-help">@lang('accounting::lang.contact_accounts_show_in_coa_help')</div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">
                    @lang('messages.save')
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.jQuery && $('#customer_parent_id').length) {
            $('#customer_parent_id, #supplier_parent_id').select2({
                width: '100%',
                dropdownParent: $('#contact_accounts_settings_tab')
            });
        }
    });
</script>