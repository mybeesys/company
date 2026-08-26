<?php

namespace Modules\Zatca\Services;

use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Zatca\Models\ZatcaSetting;

/**
 * Maps central company profile fields onto ZATCA connection defaults.
 * Only fields that exist on the company profile are returned — never invents CRN/building/OTP.
 */
class ZatcaCompanyDefaultsService
{
    /**
     * @return array{
     *     values: array<string, string>,
     *     sources: array<string, string>,
     *     available: bool,
     *     company_name: string|null
     * }
     */
    public function forCurrentCompany(): array
    {
        $company = $this->resolveCompany();
        if (! $company) {
            return [
                'values' => [],
                'sources' => [],
                'available' => false,
                'company_name' => null,
            ];
        }

        $values = [];
        $sources = [];
        $label = __('zatca::lang.from_company_details');

        $tradeName = $this->firstFilled([
            $company->tax_name ?? null,
            $company->name ?? null,
            $company->name_ar ?? null,
        ]);
        $legalName = $this->firstFilled([
            $company->name ?? null,
            $company->tax_name ?? null,
            $company->name_ar ?? null,
        ]);

        if ($tradeName !== null) {
            $values['seller_name'] = $tradeName;
            $sources['seller_name'] = $label;
        }
        if ($legalName !== null) {
            $values['organization_name'] = $legalName;
            $sources['organization_name'] = $label;
        }

        $vat = preg_replace('/\D+/', '', (string) ($company->tax_number ?? '')) ?: '';
        if ($vat !== '' && preg_match('/^3\d{13}3$/', $vat)) {
            $values['vat_number'] = $vat;
            $sources['vat_number'] = $label;
            // ZATCA OU is the 10-digit TIN of the onboarded member — digits 2..11 of a valid VAT.
            $values['organization_unit'] = substr($vat, 1, 10);
            $sources['organization_unit'] = $label;
        } elseif (strlen($vat) === 10 && ctype_digit($vat)) {
            $values['organization_unit'] = $vat;
            $sources['organization_unit'] = $label;
        }

        $email = trim((string) ($company->email ?? ''));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $values['email_address'] = $email;
            $sources['email_address'] = $label;
        }

        $city = trim((string) ($company->city ?? ''));
        if ($city !== '') {
            $values['city'] = $city;
            $sources['city'] = $label;
        }

        $district = trim((string) ($company->state ?? ''));
        if ($district !== '') {
            $values['district'] = $district;
            $sources['district'] = $label;
        }

        $postal = preg_replace('/\D+/', '', (string) ($company->zipcode ?? '')) ?: '';
        if (strlen($postal) === 5) {
            $values['postal_code'] = $postal;
            $sources['postal_code'] = $label;
        }

        $street = trim((string) ($company->national_address ?? ''));
        if ($street !== '') {
            $values['street_name'] = $street;
            $sources['street_name'] = $label;
        }

        $countryCode = $this->resolveCountryCode($company);
        if ($countryCode !== null) {
            $values['country_code'] = $countryCode;
            $sources['country_code'] = $label;
        }

        $category = $this->mapBusinessCategory((string) ($company->business_type ?? ''));
        if ($category !== null) {
            $values['business_category'] = $category;
            $sources['business_category'] = $label;
        }

        return [
            'values' => $values,
            'sources' => $sources,
            'available' => $values !== [],
            'company_name' => $legalName,
        ];
    }

    /**
     * Prefer saved ZATCA values; fall back to company defaults for empty fields.
     *
     * @param  array<string, string>  $companyValues
     * @return array{values: array<string, string>, applied_from_company: array<string, true>}
     */
    public function mergeForForm(ZatcaSetting $setting, array $companyValues): array
    {
        $keys = [
            'seller_name',
            'organization_name',
            'vat_number',
            'commercial_registration_number',
            'organization_unit',
            'business_category',
            'country_code',
            'invoice_type',
            'city',
            'district',
            'building_number',
            'postal_code',
            'plot_identification',
            'street_name',
            'email_address',
            'common_name',
            'egs_serial_number',
        ];

        $values = [];
        $applied = [];

        foreach ($keys as $key) {
            $saved = trim((string) ($setting->{$key} ?? ''));
            if ($saved !== '') {
                $values[$key] = $saved;

                continue;
            }

            $fromCompany = trim((string) ($companyValues[$key] ?? ''));
            if ($fromCompany !== '') {
                $values[$key] = $fromCompany;
                $applied[$key] = true;
            } else {
                $values[$key] = '';
            }
        }

        if (($values['country_code'] ?? '') === '') {
            $values['country_code'] = 'SA';
        }
        if (($values['invoice_type'] ?? '') === '') {
            $values['invoice_type'] = '1100';
        }

        return [
            'values' => $values,
            'applied_from_company' => $applied,
        ];
    }

    private function resolveCompany(): ?object
    {
        $companyId = function_exists('get_company_id') ? get_company_id() : null;
        if (! $companyId) {
            return null;
        }

        try {
            if (class_exists(Company::class)) {
                $row = Company::query()->find($companyId);
                if ($row) {
                    return $row;
                }
            }
        } catch (\Throwable) {
            // Fall through to central query.
        }

        try {
            $connection = (string) config('tenancy.database.central_connection', 'mysql');
            if ($connection === '' || ! config("database.connections.{$connection}")) {
                $connection = 'mysql';
            }

            return DB::connection($connection)->table('companies')->where('id', $companyId)->first();
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveCountryCode(object $company): ?string
    {
        $countryId = $company->country_id ?? null;
        if (! $countryId) {
            return 'SA';
        }

        try {
            $connection = (string) config('tenancy.database.central_connection', 'mysql');
            if ($connection === '' || ! config("database.connections.{$connection}")) {
                $connection = 'mysql';
            }

            $columns = ['id'];
            foreach (['iso2', 'code', 'iso_code', 'country_code', 'name_en', 'name_ar', 'name'] as $col) {
                if (Schema::connection($connection)->hasColumn('countries', $col)) {
                    $columns[] = $col;
                }
            }

            $country = DB::connection($connection)->table('countries')->select($columns)->where('id', $countryId)->first();
            if (! $country) {
                return 'SA';
            }

            foreach (['iso2', 'code', 'iso_code', 'country_code'] as $col) {
                $code = strtoupper(trim((string) ($country->{$col} ?? '')));
                if (preg_match('/^[A-Z]{2}$/', $code)) {
                    return $code;
                }
            }

            $name = strtolower(trim((string) (
                $country->name_en
                ?? $country->name
                ?? $country->name_ar
                ?? ''
            )));
            if (str_contains($name, 'saudi') || str_contains($name, 'السعود')) {
                return 'SA';
            }
        } catch (\Throwable) {
            return 'SA';
        }

        return 'SA';
    }

    private function mapBusinessCategory(string $businessType): ?string
    {
        $businessType = trim($businessType);
        if ($businessType === '') {
            return null;
        }

        return match ($businessType) {
            'contractors' => 'Construction / contractors',
            'e-commerce' => 'E-commerce / online retail',
            'restaurant-cafe' => 'Restaurant / cafe',
            'services' => 'Professional services',
            'general' => 'General trading',
            default => ucfirst(str_replace(['-', '_'], ' ', $businessType)),
        };
    }

    /**
     * @param  array<int, mixed>  $candidates
     */
    private function firstFilled(array $candidates): ?string
    {
        foreach ($candidates as $value) {
            $text = trim((string) ($value ?? ''));
            if ($text !== '') {
                return $text;
            }
        }

        return null;
    }
}
