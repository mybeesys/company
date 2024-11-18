@extends('employee::layouts.master')

@section('title', __('menuItemLang.show_employee'))

@section('content')
    <x-employee::employees.form :roles=$roles :employee=$employee :permissionSets=$permissionSets disabled :allowances_types="$allowances_types"
        :establishments=$establishments />
@endsection

@section('script')
    @parent
    <script src="{{ url('modules/employee/js/create-edit-employee.js') }}"></script>
    <script>
        $(document).ready(function() {
            permissionSetRepeater();
            roleRepeater();
            allowanceRepeater("{{ route('allowance_types.store') }}", "{{ session()->get('locale') }}");
            administrativeUser({{ $employee->administrativeUser()->exists() ? true : false }});
        });
    </script>
@endsection
