<?php

declare(strict_types=1);

namespace Modules\Establishment\Services;

use Carbon\Carbon;
use Modules\Establishment\Models\EstablishmentPaymentAccount;
use Modules\Establishment\Models\EstablishmentServiceFee;

final class EstablishmentServiceFeeResolver
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public static function syncForEstablishment(int $establishmentId, array $rows): void
    {
        $keptIds = [];
        $sort = 0;

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $nameAr = trim((string) ($row['name_ar'] ?? ''));
            $nameEn = trim((string) ($row['name_en'] ?? ''));
            if ($nameAr === '' || $nameEn === '') {
                continue;
            }

            $rowId = self::nullableInt($row['id'] ?? null);
            $payload = [
                'name_ar' => $nameAr,
                'name_en' => $nameEn,
                'amount' => round((float) ($row['amount'] ?? 0), 4),
                'service_fee_type' => self::normalizeFlag($row['service_fee_type'] ?? EstablishmentServiceFee::TYPE_AMOUNT),
                'application_type' => self::normalizeFlag($row['application_type'] ?? EstablishmentServiceFee::APPLY_ORDER),
                'calculation_method' => self::normalizeFlag($row['calculation_method'] ?? EstablishmentServiceFee::CALC_BEFORE_TAX),
                'taxable' => filter_var($row['taxable'] ?? false, FILTER_VALIDATE_BOOL),
                'is_active' => filter_var($row['active'] ?? $row['is_active'] ?? false, FILTER_VALIDATE_BOOL),
                'auto_apply_type' => self::nullableFlag($row['auto_apply_type'] ?? null),
                'dining_type_ids' => array_values(array_filter(array_map('intval', (array) ($row['dining_type_ids'] ?? [])))),
                'guest_count' => self::nullableInt($row['guestCount'] ?? $row['guest_count'] ?? null),
                'cashier_payment_method_id' => self::nullableInt($row['credit_type'] ?? $row['cashier_payment_method_id'] ?? null),
                'from_date' => self::nullableDate($row['from_date'] ?? null),
                'to_date' => self::nullableDate($row['to_date'] ?? null),
                'sort_order' => $sort++,
            ];

            if ($payload['cashier_payment_method_id']) {
                $belongs = EstablishmentPaymentAccount::query()
                    ->where('establishment_id', $establishmentId)
                    ->where('id', $payload['cashier_payment_method_id'])
                    ->exists();
                if (! $belongs) {
                    $payload['cashier_payment_method_id'] = null;
                }
            }

            if ($rowId) {
                $existing = EstablishmentServiceFee::query()
                    ->where('establishment_id', $establishmentId)
                    ->where('id', $rowId)
                    ->first();

                if ($existing) {
                    $existing->update($payload);
                    $keptIds[] = (int) $existing->id;

                    continue;
                }
            }

            $created = EstablishmentServiceFee::query()->create(array_merge($payload, [
                'establishment_id' => $establishmentId,
            ]));
            $keptIds[] = (int) $created->id;
        }

        $deleteQuery = EstablishmentServiceFee::query()->where('establishment_id', $establishmentId);
        if ($keptIds !== []) {
            $deleteQuery->whereNotIn('id', $keptIds);
        }
        $deleteQuery->delete();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function rowsForEstablishment(int $establishmentId): array
    {
        return EstablishmentServiceFee::query()
            ->where('establishment_id', $establishmentId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (EstablishmentServiceFee $row) => self::toRow($row))
            ->all();
    }

    /**
     * Active fees for invoice UI, keyed-ready JSON.
     *
     * @return list<array<string, mixed>>
     */
    public static function invoiceCatalog(?int $establishmentId = null): array
    {
        $query = EstablishmentServiceFee::query()
            ->with('cashierPaymentMethod:id,account_id,establishment_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id');

        if ($establishmentId) {
            $query->where('establishment_id', $establishmentId);
        }

        return $query->get()->map(fn (EstablishmentServiceFee $row) => array_merge(self::toRow($row), [
            'payment_account_id' => $row->cashierPaymentMethod?->account_id
                ? (int) $row->cashierPaymentMethod->account_id
                : null,
        ]))->all();
    }

    /**
     * @return array<string, mixed>
     */
    private static function toRow(EstablishmentServiceFee $row): array
    {
        return [
            'id' => (int) $row->id,
            'establishment_id' => (int) $row->establishment_id,
            'name_ar' => (string) $row->name_ar,
            'name_en' => (string) $row->name_en,
            'amount' => (float) $row->amount,
            'service_fee_type' => (string) $row->service_fee_type,
            'application_type' => (string) $row->application_type,
            'calculation_method' => (string) $row->calculation_method,
            'taxable' => (bool) $row->taxable,
            'active' => (bool) $row->is_active,
            'is_active' => (bool) $row->is_active,
            'auto_apply_type' => $row->auto_apply_type !== null ? (string) $row->auto_apply_type : '',
            'dining_type_ids' => array_values(array_map('intval', $row->dining_type_ids ?? [])),
            'guestCount' => $row->guest_count,
            'credit_type' => $row->cashier_payment_method_id,
            'cashier_payment_method_id' => $row->cashier_payment_method_id,
            'from_date' => $row->from_date?->format('Y-m-d\TH:i'),
            'to_date' => $row->to_date?->format('Y-m-d\TH:i'),
        ];
    }

    private static function normalizeFlag(mixed $value): string
    {
        $flag = (string) $value;

        return in_array($flag, ['0', '1'], true) ? $flag : '0';
    }

    private static function nullableFlag(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $flag = (string) $value;

        return in_array($flag, ['0', '1', '2', '3'], true) ? $flag : null;
    }

    private static function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $int = (int) $value;

        return $int > 0 ? $int : null;
    }

    private static function nullableDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }
}
