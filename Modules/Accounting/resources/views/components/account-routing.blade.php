{{-- @props(['section', 'title', 'typeSelectId', 'typeSelectName', 'accountSelectId', 'accountSelectName', 'accounts', 'typeOptions']) --}}
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
            <select id="{{ $typeSelectId }}" class="form-select select-2 form-select-solid "
                name="{{ $typeSelectName }}">
                <option value="" selected>@lang('messages.select')</option>
                @if (isset($typeOptions))
                    @foreach ($typeOptions as $key => $value)
                        <option value="{{ $key }}">@lang('accounting::lang.' . $key)</option>
                    @endforeach
                @endif
            </select>
        </div>

        <div class="fv-row mb-5">
            <label class="fs-6 fw-semibold form-label">
                <span>@lang('accounting::lang.account')</span>
            </label>
            <select id="{{ $accountSelectId }}" class="form-select select-2 form-select-solid"
                name="{{ $accountSelectName }}">
                <option value="" selected>@lang('messages.select')</option>
                @if (isset($accounts))
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}">
                            {{ app()->getLocale() == 'ar'
                                ? "{$account->gl_code} - {$account->name_ar} - " . __('accounting::lang.' . $account->account_primary_type)
                                : "{$account->gl_code} - {$account->name_en} - " . __('accounting::lang.' . $account->account_primary_type) }}
                        </option>
                    @endforeach
                @endif
            </select>
        </div>
    </div>
</div>
