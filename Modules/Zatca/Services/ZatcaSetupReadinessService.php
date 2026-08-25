<?php

namespace Modules\Zatca\Services;

use Modules\Zatca\Models\ZatcaSetting;

/**
 * Evaluates ZATCA connection-settings completeness for admin UX.
 */
class ZatcaSetupReadinessService
{
    /**
     * @return array{
     *   percent: int,
     *   level: string,
     *   summary: string,
     *   can_generate: bool,
     *   can_sync: bool,
     *   groups: array<int, array<string, mixed>>,
     *   missing: array<int, array{key: string, label: string, hint: string, group: string, anchor: string}>,
     *   done_count: int,
     *   total_count: int
     * }
     */
    public function analyze(ZatcaSetting $setting): array
    {
        $groups = [
            $this->environmentGroup($setting),
            $this->sellerGroup($setting),
            $this->addressGroup($setting),
            $this->csrGroup($setting),
            $this->certificatesGroup($setting),
        ];

        $done = 0;
        $total = 0;
        $missing = [];

        foreach ($groups as &$group) {
            $groupDone = 0;
            foreach ($group['items'] as $item) {
                $total++;
                if ($item['ok']) {
                    $done++;
                    $groupDone++;
                } else {
                    $missing[] = [
                        'key' => $item['key'],
                        'label' => $item['label'],
                        'hint' => $item['hint'],
                        'group' => $group['label'],
                        'anchor' => $item['anchor'],
                    ];
                }
            }
            $group['done'] = $groupDone;
            $group['total'] = count($group['items']);
            $group['percent'] = $group['total'] > 0
                ? (int) round(($groupDone / $group['total']) * 100)
                : 100;
            $group['complete'] = $groupDone === $group['total'];
        }
        unset($group);

        $percent = $total > 0 ? (int) round(($done / $total) * 100) : 0;
        $canGenerate = $this->canGenerate($setting);
        $canSync = $setting->isConfigured() && $this->hasCredentialTriplet($setting);

        $level = match (true) {
            $canSync && $percent >= 95 => 'ready',
            $percent >= 70 => 'almost',
            $percent >= 35 => 'partial',
            default => 'empty',
        };

        $summary = match ($level) {
            'ready' => __('zatca::lang.readiness_summary_ready'),
            'almost' => __('zatca::lang.readiness_summary_almost', ['count' => count($missing)]),
            'partial' => __('zatca::lang.readiness_summary_partial', ['percent' => $percent]),
            default => __('zatca::lang.readiness_summary_empty'),
        };

        return [
            'percent' => $percent,
            'level' => $level,
            'summary' => $summary,
            'can_generate' => $canGenerate,
            'can_sync' => $canSync,
            'groups' => $groups,
            'missing' => $missing,
            'done_count' => $done,
            'total_count' => $total,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function environmentGroup(ZatcaSetting $setting): array
    {
        $env = (string) ($setting->zatca_environment ?: '');
        $isProd = $env === 'production';

        return [
            'key' => 'environment',
            'label' => __('zatca::lang.readiness_group_environment'),
            'icon' => 'fa-globe',
            'items' => [
                $this->item(
                    'zatca_environment',
                    __('zatca::lang.section_environment'),
                    in_array($env, ['local', 'simulation', 'production'], true),
                    __('zatca::lang.readiness_hint_environment'),
                    '#zatca_environment'
                ),
                $this->item(
                    'zatca_app_key',
                    __('zatca::lang.app_key'),
                    ! $isProd || filled($setting->zatca_app_key),
                    __('zatca::lang.readiness_hint_app_key'),
                    '#zatca_app_key'
                ),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sellerGroup(ZatcaSetting $setting): array
    {
        $vat = preg_replace('/\D+/', '', (string) $setting->vat_number) ?: '';
        $ou = preg_replace('/\D+/', '', (string) $setting->organization_unit) ?: '';

        return [
            'key' => 'seller',
            'label' => __('zatca::lang.readiness_group_seller'),
            'icon' => 'fa-building',
            'items' => [
                $this->item('seller_name', __('zatca::lang.seller_name'), filled(trim((string) $setting->seller_name)), __('zatca::lang.readiness_hint_required'), '#seller_name'),
                $this->item('organization_name', __('zatca::lang.organization_name'), filled(trim((string) $setting->organization_name)), __('zatca::lang.readiness_hint_required'), '#organization_name'),
                $this->item('vat_number', __('zatca::lang.vat_number'), (bool) preg_match('/^3\d{13}3$/', $vat), __('zatca::lang.vat_number_help'), '#vat_number'),
                $this->item('commercial_registration_number', __('zatca::lang.commercial_registration_number'), filled(trim((string) $setting->commercial_registration_number)), __('zatca::lang.readiness_hint_required'), '#commercial_registration_number'),
                $this->item('organization_unit', __('zatca::lang.organization_unit'), (bool) preg_match('/^\d{10}$/', $ou), __('zatca::lang.organization_unit_help'), '#organization_unit'),
                $this->item('business_category', __('zatca::lang.business_category'), filled(trim((string) $setting->business_category)), __('zatca::lang.readiness_hint_required'), '#business_category'),
                $this->item('invoice_type', __('zatca::lang.invoice_type'), in_array((string) $setting->invoice_type, ['0100', '1000', '1100'], true), __('zatca::lang.readiness_hint_invoice_type'), '#invoice_type'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function addressGroup(ZatcaSetting $setting): array
    {
        return [
            'key' => 'address',
            'label' => __('zatca::lang.readiness_group_address'),
            'icon' => 'fa-map-marker-alt',
            'items' => [
                $this->item('city', __('zatca::lang.city'), filled(trim((string) $setting->city)), __('zatca::lang.readiness_hint_required'), '#city'),
                $this->item('district', __('zatca::lang.district'), filled(trim((string) $setting->district)), __('zatca::lang.readiness_hint_required'), '#district'),
                $this->item('building_number', __('zatca::lang.building_number'), (bool) preg_match('/^\d{4}$/', preg_replace('/\D+/', '', (string) $setting->building_number) ?: ''), __('zatca::lang.zatca_err_building_number'), '#building_number'),
                $this->item('postal_code', __('zatca::lang.postal_code'), (bool) preg_match('/^\d{5}$/', preg_replace('/\D+/', '', (string) $setting->postal_code) ?: ''), __('zatca::lang.zatca_err_postal_code'), '#postal_code'),
                $this->item('street_name', __('zatca::lang.street_name'), filled(trim((string) $setting->street_name)), __('zatca::lang.readiness_hint_street'), '#street_name'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function csrGroup(ZatcaSetting $setting): array
    {
        return [
            'key' => 'csr',
            'label' => __('zatca::lang.readiness_group_csr'),
            'icon' => 'fa-key',
            'items' => [
                $this->item('email_address', __('zatca::lang.email_address'), filter_var((string) $setting->email_address, FILTER_VALIDATE_EMAIL) !== false, __('zatca::lang.readiness_hint_email'), '#email_address'),
                $this->item(
                    'otp',
                    __('zatca::lang.otp'),
                    // OTP is only required when certificates are not generated yet.
                    $setting->isConfigured() || filled(trim((string) $setting->otp)),
                    __('zatca::lang.otp_help'),
                    '#otp'
                ),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function certificatesGroup(ZatcaSetting $setting): array
    {
        $hasTriplet = $this->hasCredentialTriplet($setting);

        return [
            'key' => 'certificates',
            'label' => __('zatca::lang.readiness_group_certificates'),
            'icon' => 'fa-certificate',
            'items' => [
                $this->item(
                    'status_configured',
                    __('zatca::lang.status_configured'),
                    $setting->status === 'configured',
                    __('zatca::lang.readiness_hint_status'),
                    '#zatca-credentials-card'
                ),
                $this->item(
                    'private_key',
                    __('zatca::lang.readiness_item_private_key'),
                    $hasTriplet && filled($setting->generated_credentials['private_key'] ?? null),
                    __('zatca::lang.readiness_hint_certs'),
                    '#zatca-credentials-card'
                ),
                $this->item(
                    'cert_production',
                    __('zatca::lang.readiness_item_cert'),
                    $hasTriplet && filled($setting->generated_credentials['cert_production'] ?? null),
                    __('zatca::lang.readiness_hint_certs'),
                    '#zatca-credentials-card'
                ),
                $this->item(
                    'secret_production',
                    __('zatca::lang.readiness_item_secret'),
                    $hasTriplet && filled($setting->generated_credentials['secret_production'] ?? null),
                    __('zatca::lang.readiness_hint_certs'),
                    '#zatca-credentials-card'
                ),
            ],
        ];
    }

    /**
     * @return array{key: string, label: string, ok: bool, hint: string, anchor: string}
     */
    private function item(string $key, string $label, bool $ok, string $hint, string $anchor): array
    {
        return compact('key', 'label', 'ok', 'hint', 'anchor');
    }

    private function hasCredentialTriplet(ZatcaSetting $setting): bool
    {
        $creds = $setting->generated_credentials ?? [];

        return filled($creds['private_key'] ?? null)
            && filled($creds['cert_production'] ?? null)
            && filled($creds['secret_production'] ?? null);
    }

    private function canGenerate(ZatcaSetting $setting): bool
    {
        $vat = preg_replace('/\D+/', '', (string) $setting->vat_number) ?: '';
        $ou = preg_replace('/\D+/', '', (string) $setting->organization_unit) ?: '';
        $isProd = $setting->zatca_environment === 'production';

        return in_array((string) $setting->zatca_environment, ['local', 'simulation', 'production'], true)
            && (! $isProd || filled($setting->zatca_app_key))
            && filled(trim((string) $setting->seller_name))
            && filled(trim((string) $setting->organization_name))
            && (bool) preg_match('/^3\d{13}3$/', $vat)
            && filled(trim((string) $setting->commercial_registration_number))
            && (bool) preg_match('/^\d{10}$/', $ou)
            && filled(trim((string) $setting->business_category))
            && filled(trim((string) $setting->city))
            && filled(trim((string) $setting->district))
            && filled(trim((string) $setting->building_number))
            && preg_match('/^\d{4}$/', preg_replace('/\D+/', '', (string) $setting->building_number) ?: '')
            && filled(trim((string) $setting->postal_code))
            && preg_match('/^\d{5}$/', preg_replace('/\D+/', '', (string) $setting->postal_code) ?: '')
            && filled(trim((string) $setting->street_name))
            && filter_var((string) $setting->email_address, FILTER_VALIDATE_EMAIL) !== false
            && filled(trim((string) $setting->otp));
    }
}
