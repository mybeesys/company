@extends('report::layouts.master')

@section('title', __('product::lang.add_type_of_services'))
@section('content')

<div class="container">
    <div class="row">
        <div class="col-6">
            <div class="d-flex my-3 align-items-center gap-2 gap-lg-3">
                <h1> @lang('product::lang.add_type_of_services')
                </h1>

            </div>
        </div>
    </div>
</div>

<div class="separator d-flex flex-center my-3">
    <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
</div>
<form id="typeService" method="POST" action="{{ route('typeService.store') }}">
    @csrf

<div class="container">
    <div class="row mb-4">

        <div class="col-md-6">
            <div class="align-items-center mb-3">
                <label class="fs-6 fw-semibold mb-2 required">@lang('product::fields.name_ar')</label>
                <input type="text" class="form-control form-control-solid" name="name_ar" id="name_ar" placeholder="@lang('product::fields.name_placeholder')" required>
            </div>
        </div>
        <div class="col-md-6">
            <div class="align-items-center mb-3">
                <label class="fs-6 fw-semibold mb-2 required">@lang('product::fields.name_en')</label>
                <input type="text" class="form-control form-control-solid" name="name_en" id="name_en" placeholder="@lang('product::fields.name_placeholder')" required>
            </div>
        </div>


        <div class="col-md-6">
            <div class="align-items-center mb-3">
                <label class="fs-6 fw-semibold mb-2">@lang('product::fields.description')</label>
                <textarea class="form-control form-control-solid" rows="1" name="description" id="description" placeholder="@lang('product::fields.description_placeholder')"></textarea>
            </div>
        </div>
    </div>


    <div class="row mb-4">
        <div class="col-md-6">
            <div class="align-items-center mb-3">
                <label class="fs-6 fw-semibold mb-2">@lang('product::fields.packing_charge_type')</label>
                <select class="form-select select-2 form-select-solid" name="packing_charge_type" id="packing_charge_type">
                    <option value="fixed">@lang('product::fields.fixed')</option>
                    <option value="percent">@lang('product::fields.percent')</option>
                </select>
            </div>
        </div>

        <div class="col-md-6">
            <div class="align-items-center mb-3">
                <label class="fs-6 fw-semibold mb-2">@lang('product::fields.packing_charge')</label>
                <input type="number" class="form-control form-control-solid" name="packing_charge" id="packing_charge" placeholder="@lang('product::fields.packing_charge_placeholder')">
            </div>
        </div>


    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            {{-- <div class="align-items-center mb-3">
                <label class="fs-6 fw-semibold mb-2">@lang('product::fields.enable_custom_fields')</label>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="enable_custom_fields" id="enable_custom_fields">
                    <label class="form-check-label" for="enable_custom_fields">@lang('product::fields.enable_custom_fields_label')</label>
                </div>
            </div> --}}
            <button type="submit" style="border-radius: 6px;" class="btn btn-bg-primary text-white ">
                @lang('messages.save')
            </button>

        </div>
    </div>

</div>

</form>
@endsection

@section('script')
    @parent
    <script src="{{ url('/js/table.js') }}"></script>
    <script></script>
@endsection
