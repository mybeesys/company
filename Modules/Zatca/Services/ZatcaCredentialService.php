<?php

namespace Modules\Zatca\Services;

use Bl\FatooraZatca\Classes\InvoiceReportType;
use Bl\FatooraZatca\Objects\Setting as FatooraSetting;
use Bl\FatooraZatca\Zatca;
use Illuminate\Support\Facades\Config;
use Modules\Zatca\Models\ZatcaSetting;
use RuntimeException;
use Throwable;

class ZatcaCredentialService
{
    /**
     * Persist form values, optionally call ZATCA onboarding (CSR → CSID).
     *
     * @param  array<string, mixed>  $data
     * @return array{setting: ZatcaSetting, generated: bool, message: string}
     */
    public function save(ZatcaSetting $setting, array $data, bool $generateCertificates = true): array
    {
        $payload = [
            'zatca_environment' => $data['zatca_environment'],
            'seller_name' => $data['seller_name'],
            'vat_number' => $data['vat_number'],
            'commercial_registration_number' => $data['commercial_registration_number'],
            'organization_unit' => $data['organization_unit'],
            'organization_name' => $data['organization_name'],
            'country_code' => $data['country_code'] ?? 'SA',
            'city' => $data['city'] ?? null,
            'building_number' => $data['building_number'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'district' => $data['district'] ?? null,
            'street_name' => $data['street_name'] ?? null,
            'plot_identification' => $data['plot_identification'] ?? null,
            'email_address' => $data['email_address'] ?? null,
            'otp' => $data['otp'] ?? null,
            'common_name' => $data['common_name'] ?? null,
            'business_category' => $data['business_category'] ?? null,
            'egs_serial_number' => $data['egs_serial_number'] ?? null,
            'invoice_type' => $data['invoice_type'] ?? InvoiceReportType::BOTH,
        ];

        if (! empty($data['zatca_app_key'])) {
            $payload['zatca_app_key'] = $data['zatca_app_key'];
        }

        $setting->fill($payload);
        $setting->save();

        if (! $generateCertificates) {
            return [
                'setting' => $setting->fresh(),
                'generated' => false,
                'message' => __('zatca::lang.saved_without_generation'),
            ];
        }

        return $this->generateAndPersist($setting->fresh());
    }

    /**
     * @return array{setting: ZatcaSetting, generated: bool, message: string}
     */
    public function generateAndPersist(ZatcaSetting $setting): array
    {
        if (! class_exists(Zatca::class)) {
            throw new RuntimeException(__('zatca::lang.package_missing'));
        }

        $this->applyRuntimeConfig($setting);

        try {
            $fatooraSetting = $this->toFatooraSetting($setting);
            $result = Zatca::generateZatcaSetting($fatooraSetting);

            $setting->generated_credentials = $this->normalizeResult($result);
            $setting->status = 'configured';
            $setting->last_error = null;
            $setting->credentials_generated_at = now();
            $setting->save();

            return [
                'setting' => $setting->fresh(),
                'generated' => true,
                'message' => __('zatca::lang.certificates_generated'),
            ];
        } catch (Throwable $e) {
            $setting->status = 'failed';
            $setting->last_error = $e->getMessage();
            $setting->save();

            throw new RuntimeException(
                __('zatca::lang.generation_failed', ['error' => $e->getMessage()]),
                previous: $e
            );
        }
    }

    public function applyRuntimeConfig(ZatcaSetting $setting): void
    {
        Config::set('zatca.app.environment', $setting->zatca_environment ?: 'local');

        if ($setting->zatca_environment === 'production' && $setting->zatca_app_key) {
            Config::set('zatca.app.key', $setting->zatca_app_key);
        }
    }

    public function toFatooraSetting(ZatcaSetting $setting): FatooraSetting
    {
        $vat = (string) $setting->vat_number;
        $otp = (string) ($setting->otp ?: '');
        $envPrefix = match ($setting->zatca_environment) {
            'production' => 'PRD',
            'simulation' => 'SIM',
            default => 'TST',
        };

        $commonName = trim((string) ($setting->common_name ?: ''));
        if ($commonName === '') {
            $commonName = sprintf('%s-%s-%s', $envPrefix, $otp !== '' ? $otp : '000000', $vat);
        }

        $registeredAddress = (string) (
            $setting->building_number
            ?: $setting->street_name
            ?: $setting->commercial_registration_number
        );

        return new FatooraSetting(
            $otp,
            (string) $setting->email_address,
            $commonName,
            (string) $setting->organization_unit,
            (string) ($setting->organization_name ?: $setting->seller_name),
            $vat,
            $registeredAddress,
            (string) ($setting->business_category ?: 'Supply activities'),
            $setting->egs_serial_number ?: null,
            (string) $setting->commercial_registration_number,
            (string) ($setting->invoice_type ?: InvoiceReportType::BOTH),
            (string) ($setting->country_code ?: 'SA')
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeResult(object $result): array
    {
        return json_decode(json_encode($result), true) ?? [];
    }
}
