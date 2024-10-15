@extends('employee::layouts.master')

@section('title', __('menuItemLang.employees'))

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
        <x-employee::pos-roles.form :departments=$departments :permissions=$permissions />
    </form>
@endsection
@section('script')
    @parent
    <script src="{{ url('modules/employee/js/create-edit-role.js') }}"></script>
    <script>
        $(document).ready(function() {
            roleForm('add_role_form', "{{ route('roles.create.validation') }}");
            $('form').on('submit', function(event) {            
                const selectAllCheckbox = $('input[type="checkbox"][value="all"]');

                if (selectAllCheckbox.is(':checked')) {
                    const dataId = $('input[type="checkbox"][value="all"]').data('id');                         
                    const selectAllPermissionId = dataId;
                    $('input[type="checkbox"][value!="all"]').prop('disabled', true);
                    selectAllCheckbox.val(selectAllPermissionId);
                }
            });
        });
    </script>
@endsection
