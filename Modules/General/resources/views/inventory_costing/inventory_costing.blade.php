<div class="tab-pane fade show" id="inventory_costing_tab" role="tabpanel">
    <div class="container">
        <form id="update-prefix" method="POST" action="{{ route('update-inventory-costing-method') }}">
            @csrf

            <div class="row my-5">
                <div class="col-4 mb-5">
                    <div class="d-flex flex-column" @if (app()->getLocale() == 'ar') dir="rtl" @endif>
                        <label class="fs-6 fw-semibold mb-2">
                            @lang('general::general.inventory_costing_method')
                        </label>
                    </div>
                    <select class="form-select select-2  form-select-solid kt_ecommerce_select2_account"
                        style="padding: 0px 12px;border: 1px solid var(--bs-gray-300); width: 60% !important"
                        name="inventory_costing_method" id="inventory_costing_method">
                        <option value="average" {{ ($inventory_costing_method ?? '') == 'average' ? 'selected' : '' }}>
                            @lang('general::general.average')
                        </option>
                        <option value="fifo" {{ ($inventory_costing_method ?? '') == 'fifo' ? 'selected' : '' }}>
                            @lang('general::general.fifo')
                        </option>
                        <option value="lifo" {{ ($inventory_costing_method ?? '') == 'lifo' ? 'selected' : '' }}>
                            @lang('general::general.lifo')
                        </option>

                    </select>

                </div>

            </div>
            <div class="separator d-flex flex-center m-5">
                <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
            </div>
            <button type="submit" style="border-radius: 6px;" class="btn btn-primary w-200px">
                @lang('messages.save')
            </button>
        </form>

    </div>

</div>
