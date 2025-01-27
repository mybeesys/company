<div class="tab-pane fade show active" id="prefix_settings_tab" role="tabpanel">
    <div class="container">
        <form id="update-prefix" method="POST" action="{{ route('update-prefix') }}">
            @csrf

            <div class="row my-5">
                @foreach ($prefixes as $prefix)
                    <div class="col-4 mb-5">
                        <div class="d-flex flex-column" @if (app()->getLocale() == 'ar') dir="rtl" @endif>
                            <label class="fs-6 fw-semibold mb-2">
                                @lang('menuItemLang.' . $prefix->type)@if ($prefix->type == 'invoices')
                                    / @lang('menuItemLang.purchase_invoices')
                                @endif
                            </label>
                            <input class="form-control form-control-solid no-spin" name="prefixes[{{ $prefix->type }}]"
                                value="{{ $prefix->prefix }}" placeholder="@lang('messages.enter_prefix')" id="{{ $prefix->type }}"
                                type="text">
                        </div>
                    </div>
                @endforeach
                <div class="separator d-flex flex-center m-5">
                    <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
                </div>
                @foreach ($prefixes_payments as $prefix)
                    <div class="col-4 mb-5">
                        <div class="d-flex flex-column" @if (app()->getLocale() == 'ar') dir="rtl" @endif>
                            <label class="fs-6 fw-semibold mb-2">
                                @lang('menuItemLang.' . $prefix->type)@if ($prefix->type == 'invoices')
                                    / @lang('menuItemLang.purchase_invoices')
                                @endif
                            </label>
                            <input class="form-control form-control-solid no-spin"
                                name="prefixes_payments[{{ $prefix->type }}]" value="{{ $prefix->prefix }}"
                                placeholder="@lang('messages.enter_prefix')" id="{{ $prefix->type }}" type="text">
                        </div>
                    </div>
                @endforeach

                <div class="separator d-flex flex-center m-5">
                    <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
                </div>
                @foreach ($prefixes_mapp as $prefix)
                    <div class="col-4 mb-5">
                        <div class="d-flex flex-column" @if (app()->getLocale() == 'ar') dir="rtl" @endif>
                            <label class="fs-6 fw-semibold mb-2">
                                @lang('menuItemLang.' . $prefix->type)@if ($prefix->type == 'invoices')
                                    / @lang('menuItemLang.purchase_invoices')
                                @endif
                            </label>
                            <input class="form-control form-control-solid no-spin"
                                name="prefixes_mapp[{{ $prefix->type }}]" value="{{ $prefix->prefix }}"
                                placeholder="@lang('messages.enter_prefix')" id="{{ $prefix->type }}" type="text">
                        </div>
                    </div>
                @endforeach

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
