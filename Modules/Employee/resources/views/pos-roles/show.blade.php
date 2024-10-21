@extends('employee::layouts.master')

@section('title', __('menuItemLang.employees'))

@section('content')
    <x-employee::pos-roles.form :role=$role :departments=$departments :permissions=$permissions disabled/>
@endsection

@section('script')
    @parent
    <script src="{{ url('modules/employee/js/create-edit-role.js') }}"></script>
    <script src="{{ url('modules/employee/js/table.js') }}"></script>
    <script>
        let dataTable;
        $(document).ready(function() {
            rolePermissionsForm();
        });
    </script>
@endsection
