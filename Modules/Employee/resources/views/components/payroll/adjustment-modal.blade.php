@props(['allowances_types' => null, 'deductions_types' => null, 'disabled' => false])
<x-general.modal module="employee" class="mw-1000px" id="payroll_allowance_modal" title="edit_allowances">
    <x-employee::payroll.adjustment-repeater type="allowance" :adjustment_types="$allowances_types" />
</x-general.modal>

<x-general.modal module="employee" class="mw-1000px" id="payroll_deduction_modal" title="edit_deductions">
    <x-employee::payroll.adjustment-repeater type="deduction" :adjustment_types="$deductions_types" />
</x-general.modal>
