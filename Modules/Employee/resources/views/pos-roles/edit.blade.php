@extends('employee::layouts.master')

@section('title', __('menuItemLang.employees'))

@section('content')
    <form id="edit_role_form" class="form d-flex flex-column flex-lg-row" method="POST" enctype="multipart/form-data"
        action="{{ route('roles.update', ['role' => $role]) }}">
        @method('patch')
        @csrf
        <x-employee::pos-roles.form :role=$role :departments=$departments :permissions=$permissions />
    </form>
@endsection

@section('script')
    @parent
    <script src="{{ url('modules/employee/js/create-edit-role.js') }}"></script>
    <script>
        $(document).ready(function() {
            roleForm('edit_role_form', "{{ route('roles.update.validation') }}");
        });
    </script>
@endsection
