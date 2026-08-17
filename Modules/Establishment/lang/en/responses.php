<?php

return [
    'something_wrong_happened' => 'Something wrong happened!',
    'empty_repeater_warning' => 'Sorry, you can\'t delete all elements',
    'change_user_status_warning' => 'When the user is deactivated, they will be deactivated in all establishments',
    'created_successfully' => 'The :name has been created successfully',
    'updated_successfully' => 'The :name has been updated successfully',
    'deleted_successfully' => 'The :name has been deleted successfully',
    'operation_success' => 'Operation done successfully',
    'no_company_found' => 'No company were found, please contact the customer service',
    'cashier_payment_method_not_allowed' => 'Payment method (:method) is not enabled for this branch. Add or assign it from Payment methods settings.',
    'cashier_payment_account_required' => 'Payment method ":method" has no linked account. Configure it in Payment methods settings.',
    'cashier_payment_establishment_required' => 'establishment_id is required to list payment methods.',
    'cashier_payment_branch_account_required' => 'Every assigned branch must have a GL account for this payment method.',
    'internal_consumption_expense_account_required' => 'No internal consumption expense account is configured for this branch. Set it from Cashier internal consumption settings.',
    'internal_consumption_inventory_account_required' => 'Could not resolve an inventory account for the internal consumption journal. Check perpetual inventory and branch inventory account settings.',
    'internal_consumption_cost_required' => 'Could not calculate item cost for the internal consumption journal.',
    'internal_consumption_type_required' => 'No internal consumption type is configured for this branch. Add one from Cashier internal consumption settings and assign it to the branch.',
    'internal_consumption_type_account_required' => 'Internal consumption type «:type» has no linked collection account. Configure it in Cashier internal consumption settings.',
    'internal_consumption_charge_required' => 'Could not calculate the internal consumption charge amount.',
    'internal_consumption_variance_account_required' => 'The charge amount differs from inventory COGS and no branch variance expense account is configured. Set the legacy internal consumption expense account or use the «Inventory cost» value type.',
];
