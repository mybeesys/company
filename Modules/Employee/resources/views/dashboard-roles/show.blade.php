@extends('employee::layouts.master')

@section('title', __('employee::general.show_role'))

@section('content')
    <x-employee::dashboard-roles.form :dashboardRole=$dashboardRole :modules=$modules :rolePermissions=$rolePermissions disabled/>
@endsection

@section('script')
    @parent
    <script src="{{ url('modules/employee/js/create-edit-role.js') }}"></script>
    <script src="{{ url('modules/employee/js/create-edit-dashboard-role.js') }}"></script>
    <script>
        "use strict";
        $(document).ready(function() {
            dashboardRolePermissionsForm();
        });
    </script>
@endsection
