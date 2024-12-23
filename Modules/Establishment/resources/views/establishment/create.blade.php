@extends('establishment::layouts.master')

@section('title', __('establishment::general.add_establishment'))
@section('css')
    <style>
        @media (min-width: 1300px) {
            .select2-selection.select2-selection--single {
                width: 333.24px !important;
            }
        }
    </style>
@endsection
@section('content')
    <form id="add_establishment_form" class="form d-flex flex-column gap-2" method="POST" enctype="multipart/form-data"
        action="{{ route('establishments.store') }}">
        @csrf
        <x-establishment::establishments.form formId="add_establishment_form" :establishments=$establishments />
    </form>
@endsection

@section('script')
    @parent
    <script src="{{ url('modules/establishment/js/create-edit-establishment.js') }}"></script>
    <script>
        $(document).ready(function() {
            let saveButton = $(`#add_establishment_form_button`);
            establishmentForm('add_establishment_form', "{{ route('establishments.create.validation') }}");
            handleImageInput('imageInput');
        });
    </script>
@endsection
