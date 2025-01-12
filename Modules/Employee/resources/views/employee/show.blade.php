@extends('employee::layouts.master')

@section('title', __('menuItemLang.show_employee'))

@section('content')
    <x-employee::employees.form :dashboardRoles=$dashboardRoles :employee=$employee :posRoles=$posRoles disabled
        :allowances_types="$allowances_types" :establishments=$establishments />
@endsection

@section('script')
    @parent
    <script src="{{ url('modules/employee/js/create-edit-employee.js') }}"></script>
    <script src="{{ url('modules/employee/js/adjustments.js') }}"></script>
    <script>
        $(document).ready(function() {
            permissionSetRepeater();
            roleRepeater();
            allowanceRepeater("{{ route('adjustment_types.get-types') }}",
                "{{ route('adjustment_types.store') }}");
            administrativeUser({{ $employee->ems_access }});
        });
    </script>
@endsection
