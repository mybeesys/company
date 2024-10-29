@extends('employee::layouts.master')

@section('title', __('employee::general.add_role'))

@section('css')
    <style>
        .tooltip-inner {
            white-space: pre-wrap;

        }
    </style>
@endsection

@section('content')
    <form id="add_role_form" class="form d-flex flex-column flex-lg-row" method="POST" enctype="multipart/form-data"
        action="{{ route('roles.store') }}">
        @csrf
        <x-employee::pos-roles.form :departments=$departments :permissions=$permissions formId="add_role_form" />
    </form>
@endsection
@section('script')
    @parent
    <script src="{{ url('modules/employee/js/create-edit-role.js') }}"></script>
    <script src="{{ url('js/table.js') }}"></script>
    <script>
        let dataTable;
        $(document).ready(function() {
            roleForm('add_role_form', "{{ route('roles.create.validation') }}");
            rolePermissionsForm();
        });
    </script>
@endsection
