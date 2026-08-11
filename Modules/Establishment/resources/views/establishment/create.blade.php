@extends('establishment::layouts.master')

@section('title', __('establishment::general.add_establishment'))
@section('content')
    <form id="add_establishment_form" class="form d-flex flex-column gap-2" method="POST" enctype="multipart/form-data"
        action="{{ route('establishments.store') }}">
        @csrf
        <x-establishment::establishments.form formId="add_establishment_form" :establishments="$establishments"
            :showButtons="false" />
        <x-establishment::establishments.inventory-account-settings
            :showPerpetualInventoryAccount="$showPerpetualInventoryAccount ?? false"
            :perpetualInventoryAccounts="$perpetualInventoryAccounts ?? collect()" />
        <x-form.form-buttons cancelUrl="{{ url('/establishment') }}" id="add_establishment_form" />
    </form>
@endsection

@section('script')
    @parent
    <script src="{{ url('modules/establishment/js/create-edit-establishment.js') }}"></script>
    <script>
        $(document).ready(function() {
            establishmentForm('add_establishment_form', "{{ route('establishments.create.validation') }}");
            handleImageInput('imageInput', 'logo');
            $('#add_establishment_form .select-2').select2({
                allowClear: true,
                width: '100%'
            });
        });
    </script>
@endsection
