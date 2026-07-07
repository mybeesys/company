<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\CentralCompanyMembershipService;
use App\Support\TenantSwitchToken;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MyCompaniesController extends Controller
{
    public function __construct(protected CentralCompanyMembershipService $memberships) {}

    public function index(): View
    {
        $employee = auth()->user();
        $currentTenantId = tenant('id');

        $companies = $this->memberships->companiesForEmail(
            (string) $employee->email,
            is_string($currentTenantId) ? $currentTenantId : null,
        );

        return view('employee::my-companies.index', [
            'companies' => $companies,
            'hasMultipleCompanies' => $companies->count() > 1,
        ]);
    }

    public function switchUrl(Request $request, string $tenantId)
    {
        $employee = auth()->user();

        if (! $this->memberships->userCanAccessTenant((string) $employee->email, $tenantId)) {
            abort(403);
        }

        $company = $this->memberships->companiesForEmail((string) $employee->email)
            ->firstWhere('tenant_id', $tenantId);

        if ($company === null) {
            abort(404);
        }

        $token = TenantSwitchToken::issue((string) $employee->email, $tenantId);
        $url = $this->memberships->tenantBaseUrl($company->domain)
            .'/auth/tenant-switch?token='.urlencode($token);

        if ($request->expectsJson()) {
            return response()->json(['url' => $url]);
        }

        return redirect()->away($url);
    }
}
