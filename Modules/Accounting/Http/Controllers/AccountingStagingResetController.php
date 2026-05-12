<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Accounting\Utils\AccountingFullResetService;

/**
 * Staging / demo only: wipe tenant accounting tables and reseed defaults.
 */
class AccountingStagingResetController extends Controller
{
    public function __invoke(Request $request)
    {
        AccountingFullResetService::ensureAllowedOrAbort();

        $request->validate([
            'confirm' => ['required', 'string', 'in:RESET_ACCOUNTING_FULL'],
        ]);

        AccountingFullResetService::truncateAndReseedDefaults();

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Accounting cleared (including the full chart). Default account types restored; use "Create default accounts" to rebuild the chart and routings.',
            ]);
        }

        return redirect()->back()->with('status', [
            'success' => 1,
            'msg' => __('accounting::lang.staging_full_reset_success'),
        ]);
    }
}
