@extends('employee::layouts.master')

@section('title', __('menuItemLang.employees'))

@section('content')
    <form id="edit_role_form" class="form d-flex flex-column flex-lg-row" method="POST" enctype="multipart/form-data"
        action="{{ route('roles.update', ['role' => $role]) }}">
        @method('patch')
        @csrf
        <x-employee::roles.form :role=$role :departments=$departments/>
    </form>
@endsection

@section('script')
    @parent
    <script>
        $(document).ready(function() {
            roleForm('edit_role_form', "{{ route('roles.update.validation') }}");
        });
    </script>
@endsection
