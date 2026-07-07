<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CentralCompanyMembershipService
{
    protected function centralConnection(): string
    {
        $connection = (string) config('tenancy.database.central_connection', 'mysql');

        return $connection !== '' ? $connection : 'mysql';
    }

    /**
     * @return Collection<int, object{
     *     company_id: int,
     *     company_name: string,
     *     tenant_id: string,
     *     domain: string,
     *     role: string,
     *     is_current: bool
     * }>
     */
    public function companiesForEmail(string $email, ?string $currentTenantId = null): Collection
    {
        $email = strtolower(trim($email));

        if ($email === '') {
            return collect();
        }

        $central = $this->centralConnection();

        $user = DB::connection($central)
            ->table('users')
            ->where('email', $email)
            ->whereNull('deleted_at')
            ->first();

        if ($user === null) {
            return collect();
        }

        $hasMembershipTable = Schema::connection($central)->hasTable('company_user');

        $memberCompanyIds = $hasMembershipTable
            ? DB::connection($central)->table('company_user')->where('user_id', $user->id)->pluck('company_id')
            : collect();

        $ownedCompanyIds = DB::connection($central)
            ->table('companies')
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->pluck('id');

        $companyIds = $memberCompanyIds->merge($ownedCompanyIds)->unique()->values();

        if ($companyIds->isEmpty()) {
            return collect();
        }

        $rows = DB::connection($central)
            ->table('companies')
            ->join('tenants', 'tenants.company_id', '=', 'companies.id')
            ->leftJoin('domains', 'domains.tenant_id', '=', 'tenants.id')
            ->when($hasMembershipTable, function ($query) use ($user) {
                $query->leftJoin('company_user', function ($join) use ($user) {
                    $join->on('company_user.company_id', '=', 'companies.id')
                        ->where('company_user.user_id', '=', $user->id);
                });
            })
            ->whereIn('companies.id', $companyIds)
            ->whereNull('companies.deleted_at')
            ->whereNotNull('domains.domain')
            ->orderBy('companies.name')
            ->get([
                'companies.id as company_id',
                'companies.name as company_name',
                'tenants.id as tenant_id',
                'domains.domain',
                $hasMembershipTable
                    ? DB::raw("COALESCE(company_user.role, 'owner') as role")
                    : DB::raw("'owner' as role"),
            ])
            ->unique('tenant_id')
            ->values();

        return $rows->map(function ($row) use ($currentTenantId) {
            $row->is_current = $currentTenantId !== null && $row->tenant_id === $currentTenantId;

            return $row;
        });
    }

    public function userCanAccessTenant(string $email, string $tenantId): bool
    {
        return $this->companiesForEmail($email)
            ->contains(fn ($company) => $company->tenant_id === $tenantId);
    }

    public function tenantBaseUrl(string $domain): string
    {
        $scheme = request()->secure() ? 'https' : 'http';
        $port = request()->getPort();
        $defaultPort = request()->secure() ? 443 : 80;
        $portSuffix = ($port && (int) $port !== $defaultPort) ? ':'.$port : '';

        return $scheme.'://'.$domain.$portSuffix;
    }
}
