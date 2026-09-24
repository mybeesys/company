<?php

declare(strict_types=1);

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Services\ContactAccountSettings;

class ContactAccountSettingsController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_parent_id' => ['nullable', 'integer', 'exists:accounting_accounts,id'],
            'supplier_parent_id' => ['nullable', 'integer', 'exists:accounting_accounts,id'],
        ]);

        ContactAccountSettings::save([
            'auto_create' => $request->boolean('auto_create'),
            'show_in_coa' => $request->boolean('show_in_coa'),
            'customer_parent_id' => (int) ($data['customer_parent_id'] ?? 0) ?: null,
            'supplier_parent_id' => (int) ($data['supplier_parent_id'] ?? 0) ?: null,
        ]);

        return redirect()
            ->route('accounting-settings', ['tab' => 'contact-accounts'])
            ->with('success', __('messages.updated_successfully'));
    }

    /**
     * Parent candidates for the settings selects (control accounts preferred).
     *
     * @return \Illuminate\Support\Collection<int, AccountingAccount>
     */
    public static function parentCandidates()
    {
        return AccountingAccount::query()
            ->whereIn('account_primary_type', ['asset', 'liability', 'liabilities'])
            ->orderBy('gl_code')
            ->get(['id', 'gl_code', 'name_ar', 'name_en', 'account_primary_type', 'parent_account_id']);
    }
}
