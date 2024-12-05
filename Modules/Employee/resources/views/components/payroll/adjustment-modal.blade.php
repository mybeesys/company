@props(['allowances_types' => null, 'deductions_types' => null, 'disabled' => false])
<x-employee::general.modal class="mw-1000px" id="payroll_allowance_modal" title="edit_allowances">
    <x-employee::payroll.adjustment-repeater type="allowance" :adjustment_types="$allowances_types" />
</x-employee::general.modal>

<x-employee::general.modal class="mw-1000px" id="payroll_deduction_modal" title="edit_deductions">
    <x-employee::payroll.adjustment-repeater type="deduction" :adjustment_types="$deductions_types" />
</x-employee::general.modal>
