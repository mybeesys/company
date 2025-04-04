@extends('employee::layouts.master')

@section('title', __('employee::general.add_role'))

@section('css')
    <style>

    </style>
@endsection
@section('content')
    <form id="add_role_form" class="form d-flex flex-column flex-lg-row" method="POST" enctype="multipart/form-data"
        action="{{ route('dashboard-roles.store') }}">
        @csrf
        <x-employee::dashboard-roles.form :modules=$modules formId="add_role_form" />
    </form>
@endsection

@section('script')
    @parent
    <script src="{{ url('modules/employee/js/create-edit-role.js') }}"></script>
    <script src="{{ url('modules/employee/js/create-edit-dashboard-role.js') }}"></script>
    <script>
        "use strict";
        $(document).ready(function() {
            roleForm('add_role_form', "{{ route('dashboard-roles.create.validation') }}");
            dashboardRolePermissionsForm();
            fixedTableHeader();
        });
    </script>
@endsection
