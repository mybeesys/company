@extends('report::layouts.master')

@section('title', __('product::lang.edit_type_of_services'))
@section('content')

<div class="container">
    <div class="row">
        <div class="col-6">
            <div class="d-flex my-3 align-items-center gap-2 gap-lg-3">
                <h1> @lang('product::lang.edit_type_of_services')
                </h1>
            </div>
        </div>
    </div>
</div>

<div class="separator d-flex flex-center my-3">
    <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
</div>

<form id="typeService" method="POST" action="{{ route('typeService.update') }}">
    @csrf
    @method('PUT')

    <input type="hidden" name="id" value="{{ $service->id }}">

    <div class="container">
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="align-items-center mb-3">
                    <label class="fs-6 fw-semibold mb-2 required">@lang('product::fields.name_ar')</label>
                    <input type="text" class="form-control form-control-solid" name="name_ar" id="name_ar"
                           value="{{ $service->name_ar }}" placeholder="@lang('product::fields.name_placeholder')" required>
                </div>
            </div>

            <div class="col-md-6">
                <div class="align-items-center mb-3">
                    <label class="fs-6 fw-semibold mb-2 required">@lang('product::fields.name_en')</label>
                    <input type="text" class="form-control form-control-solid" name="name_en" id="name_en"
                           value="{{ $service->name_en }}" placeholder="@lang('product::fields.name_placeholder')" required>
                </div>
            </div>

            <div class="col-md-6">
                <div class="align-items-center mb-3">
                    <label class="fs-6 fw-semibold mb-2">@lang('product::fields.description')</label>
                    <textarea class="form-control form-control-solid" rows="1" name="description" id="description"
                              placeholder="@lang('product::fields.description_placeholder')">{{ $service->description }}</textarea>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="align-items-center mb-3">
                    <label class="fs-6 fw-semibold mb-2">@lang('product::fields.packing_charge_type')</label>
                    <select class="form-select select-2 form-select-solid" name="packing_charge_type" id="packing_charge_type">
                        <option value="fixed" {{ $service->packing_charge_type == 'fixed' ? 'selected' : '' }}>@lang('product::fields.fixed')</option>
                        <option value="percent" {{ $service->packing_charge_type == 'percent' ? 'selected' : '' }}>@lang('product::fields.percent')</option>
                    </select>
                </div>
            </div>

            <div class="col-md-6">
                <div class="align-items-center mb-3">
                    <label class="fs-6 fw-semibold mb-2">@lang('product::fields.packing_charge')</label>
                    <input type="number" class="form-control form-control-solid" name="packing_charge" id="packing_charge"
                           value="{{ $service->packing_charge }}" placeholder="@lang('product::fields.packing_charge_placeholder')">
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <button type="submit" style="border-radius: 6px;" class="btn btn-bg-primary text-white">
                    @lang('messages.update')
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
