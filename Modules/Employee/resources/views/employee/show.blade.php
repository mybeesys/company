@extends('employee::layouts.master')

@section('title', __('menuItemLang.employees'))

@section('content')
    <x-employee::employees.form :roles=$roles :employee=$employee :permissionSets=$permissionSets disabled
        :establishments=$establishments />
@endsection

@section('script')
    @parent
    <script src="{{ url('modules/employee/js/create-edit-employee.js') }}"></script>
    <script>
        $(document).ready(function() {
            datePicker('#employmentStartDate');
            permissionSetRepeater();
            roleRepeater();
            administrativeUser({{ $employee->administrativeUser()->exists() ? true : false }});
        });
    </script>
@endsection
