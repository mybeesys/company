@extends('establishment::layouts.master')

@section('title', __('establishment::general.edit_establishment'))
@section('content')
    <form id="edit_establishment_form" class="form d-flex flex-column gap-2" method="POST" enctype="multipart/form-data"
        action="{{ route('establishments.update', ['establishment' => $establishment]) }}">
        @method('patch')
        @csrf
        <x-establishment::establishments.form :establishment=$establishment formId="edit_establishment_form" />
    </form>
@endsection

@section('script')
    @parent
    <script src="{{ url('modules/establishment/js/create-edit-establishment.js') }}"></script>
    <script>
        $(document).ready(function() {
            let saveButton = $(`#edit_establishment_form_button`);
            establishmentForm('edit_establishment_form', "{{ route('establishments.create.validation') }}");
            handleImageInput('imageInput', 'logo');
        });
    </script>
@endsection
