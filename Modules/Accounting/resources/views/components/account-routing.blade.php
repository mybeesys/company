<div class="card my-2" data-section="{{ $section }}">
    <div class="card-header card-header-stretch" style="min-height: 50px !important;background-color: #f9f9f9;">
        <div class="card-title d-flex align-items-center">
            <h6 class="fw-bold m-0 text-gray-800">{{ $title }}</h6>
        </div>
    </div>
    <div class="card-body">
        <div class="fv-row mb-2">
            <label class="fs-6 fw-semibold form-label">
                <span>@lang('accounting::lang.routing_type')</span>
            </label>
            <select class="form-select select-2 form-select-solid routing-type-select" name="{{ $typeSelectName }}" id="{{ $typeSelectName }}"
                data-section="{{ $accountSelectName }}">
                <option value="" {{ is_null($selectedType) ? 'selected' : '' }}>@lang('messages.select')</option>
                @isset($typeOptions)
                    @foreach ($typeOptions as $key => $value)
                        <option value="{{ $key }}" {{ $selectedType == $key ? 'selected' : '' }}>
                            @lang('accounting::lang.' . $key)
                        </option>
                    @endforeach
                @endisset
            </select>
        </div>

        <div class="fv-row mb-5 account-field" data-section="{{ $accountSelectName }}">
            <label class="fs-6 fw-semibold form-label">
                <span>@lang('accounting::lang.account')</span>
            </label>
            <select class="form-select select-2 form-select-solid account-select" name="{{ $accountSelectName }}" id="{{ $accountSelectName }}">
                <option value="" {{ is_null($selectedAccount) ? 'selected' : '' }}>@lang('messages.select')</option>
                @isset($accounts)
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}" {{ $selectedAccount == $account->id ? 'selected' : '' }}>
                            {{ app()->getLocale() == 'ar'
                                ? "{$account->gl_code} - {$account->name_ar} - " . __('accounting::lang.' . $account->account_primary_type)
                                : "{$account->gl_code} - {$account->name_en} - " . __('accounting::lang.' . $account->account_primary_type) }}
                        </option>
                    @endforeach
                @endisset
            </select>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        function toggleAccountField(section) {
            const routingTypeSelect = $(`.routing-type-select[data-section="${section}"]`);
            const accountField = $(`.account-field[data-section="${section}"]`);

            const accountSelect = accountField.find('.account-select');

            if (routingTypeSelect.val() === 'no_routing') {
                // accountSelect.val(null).trigger('change');
                accountField.hide();
            } else {
                accountField.show();
            }
        }

        $('.routing-type-select').each(function() {
            const section = $(this).data('section');
            toggleAccountField(section);
        });

        $('.routing-type-select').on('change', function() {
            const section = $(this).data('section');
            toggleAccountField(section);
        });
    });
</script>
