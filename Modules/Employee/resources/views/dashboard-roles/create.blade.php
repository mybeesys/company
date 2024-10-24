@extends('employee::layouts.master')

@section('title', __('menuItemLang.employees'))

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

            $("#dashboard-permissions-table").DataTable({
                paging: false,
                info: false,
                fixedHeader: {
                    header: true,
                    headerOffset: 100
                },
                ordering: false,
                autoWidth: false,
            });
            $(window).on('scroll', function() {
                var floatingParentChild = $('.dtfh-floatingparent > div');
                if (floatingParentChild.length) {
                    floatingParentChild.css('padding-right', '0');
                    $('.dtfh-floatingparent').addClass('rounded-start rounded-end');
                }
            });

        });
    </script>
@endsection
