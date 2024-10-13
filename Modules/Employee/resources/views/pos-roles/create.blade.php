@extends('employee::layouts.master')

@section('title', __('menuItemLang.employees'))

@section('content')
    <form id="add_role_form" class="form d-flex flex-column flex-lg-row" method="POST" enctype="multipart/form-data"
        action="{{ route('roles.store') }}">
        @csrf
        <x-employee::pos-roles.form :departments=$departments />
    </form>
@endsection

@section('script')
    @parent
    <script src="{{ url('modules/employee/js/create-edit-role.js') }}"></script>
    <script>
        $(document).ready(function() {
            roleForm('add_role_form', "{{ route('roles.create.validation') }}");
        });
    </script>
@endsection
