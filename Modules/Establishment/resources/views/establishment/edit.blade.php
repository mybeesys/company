@extends('establishment::layouts.master')

@section('title', __('establishment::general.edit_establishment'))
@section('content')
    <form id="edit_establishment_form" class="form d-flex flex-column gap-2" method="POST" enctype="multipart/form-data"
        action="{{ route('establishments.update', ['establishment' => $establishment]) }}">
        @method('patch')
        @csrf

        <ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-semibold mb-n2" role="tablist">
            <li class="nav-item">
                <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab" href="#establishment_main_tab"
                    role="tab">@lang('establishment::general.main_info_tab')</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#establishment_inventory_account_tab"
                    role="tab">@lang('establishment::general.inventory_account_settings_tab')</a>
            </li>
        </ul>

        <div class="tab-content pt-5">
            <div class="tab-pane fade show active" id="establishment_main_tab" role="tabpanel">
                <x-establishment::establishments.form :establishment="$establishment" formId="edit_establishment_form"
                    :establishments="$establishments" :showButtons="false" />
            </div>
            <div class="tab-pane fade" id="establishment_inventory_account_tab" role="tabpanel">
                <x-establishment::establishments.inventory-account-settings :establishment="$establishment"
                    :showPerpetualInventoryAccount="$showPerpetualInventoryAccount ?? false"
                    :perpetualInventoryAccounts="$perpetualInventoryAccounts ?? collect()" />
            </div>
        </div>

        <x-form.form-buttons cancelUrl="{{ url('/establishment') }}" id="edit_establishment_form" />
    </form>
@endsection

@section('script')
    @parent
    <script src="{{ url('modules/establishment/js/create-edit-establishment.js') }}"></script>
    <script>
        $(document).ready(function() {
            establishmentForm('edit_establishment_form', "{{ route('establishments.create.validation') }}");
            handleImageInput('imageInput', 'logo');
            $('#edit_establishment_form .select-2').select2({
                allowClear: true,
                width: '100%'
            });
        });
    </script>
@endsection
