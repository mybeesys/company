@props(['role' => null])
@extends('employee::layouts.master')

@section('title', __('menuItemLang.employees'))

@section('content')
    <form id="add_role_form" class="form d-flex flex-column flex-lg-row" method="POST" enctype="multipart/form-data"
        action="{{ route('roles.store') }}">
        @csrf
        <x-employee::roles.form />
    </form>
@endsection

@section('script')
    @parent
    <script>
        $(document).ready(function() {
            form('add_role_form', "{{ route('roles.create.validation') }}");
        });
    </script>
@endsection
