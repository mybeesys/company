<?php

declare(strict_types=1);

namespace Modules\Establishment\Services;

use Illuminate\Support\Str;
use Modules\Accounting\Utils\InternalConsumptionAccountResolver;
use Modules\Establishment\Models\EstablishmentInternalConsumptionType;
use Modules\General\Models\Transaction;

final class EstablishmentInternalConsumptionTypeResolver
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public static function syncForEstablishment(int $establishmentId, array $rows): void
    {
        $keptIds = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $accountId = self::nullableInt($row['account_id'] ?? null);
            $nameAr = trim((string) ($row['name_ar'] ?? ''));
            $nameEn = trim((string) ($row['name_en'] ?? ''));
            $valueType = self::normalizeValueType($row['value_type'] ?? self::VALUE_TYPE_COST);
            $value = self::normalizeValue($valueType, $row['value'] ?? null);
            $rowId = self::nullableInt($row['id'] ?? null);

            if ($accountId === null || $nameAr === '' || $nameEn === '') {
                continue;
            }

            $payload = [
                'type_key' => self::uniqueKey($nameEn, $rowId),
                'name_ar' => $nameAr,
                'name_en' => $nameEn,
                'value_type' => $valueType,
                'value' => $value,
                'account_id' => $accountId,
                'is_active' => filter_var($row['is_active'] ?? false, FILTER_VALIDATE_BOOL),
            ];

            if ($rowId) {
                $existing = EstablishmentInternalConsumptionType::query()
                    ->where('establishment_id', $establishmentId)
                    ->where('id', $rowId)
                    ->first();

                if ($existing) {
                    $existing->update($payload);
                    $keptIds[] = (int) $existing->id;

                    continue;
                }
            }

            $created = EstablishmentInternalConsumptionType::query()->create(array_merge($payload, [
                'establishment_id' => $establishmentId,
            ]));
            $keptIds[] = (int) $created->id;
        }

        $deleteQuery = EstablishmentInternalConsumptionType::query()
            ->where('establishment_id', $establishmentId);

        if ($keptIds !== []) {
            $deleteQuery->whereNotIn('id', $keptIds);
        }

        $deleteQuery->delete();
    }

    /**
     * @return list<array{
     *     id: int,
     *     name_ar: string,
     *     name_en: string,
     *     value_type: string,
     *     value: float|null,
     *     account_id: int|null,
     *     type_key: string,
     *     is_active: bool
     * }>
     */
    public static function rowsForEstablishment(int $establishmentId): array
    {
        return EstablishmentInternalConsumptionType::query()
            ->with('assignedEstablishments:id')
            ->forEstablishment($establishmentId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (EstablishmentInternalConsumptionType $row) => [
                'id' => (int) $row->id,
                'name_ar' => (string) ($row->name_ar ?? ''),
                'name_en' => (string) ($row->name_en ?? ''),
                'value_type' => (string) ($row->value_type ?? self::VALUE_TYPE_COST),
                'value' => $row->value !== null ? (float) $row->value : null,
                'account_id' => $row->account_id ? (int) $row->account_id : null,
                'type_key' => (string) $row->type_key,
                'is_active' => (bool) $row->is_active,
                'establishment_ids' => $row->assignedEstablishmentIds(),
            ])
            ->all();
    }

    /**
     * @return array{
     *     ok: true,
     *     type: EstablishmentInternalConsumptionType,
     *     account_id: int
     * }|array{
     *     ok: false,
     *     status: int,
     *     message: string,
     *     code: string
     * }
     */
    public static function resolveForCashier(int $establishmentId, ?int $typeId = null): array
    {
        $type = null;

        if ($typeId && $typeId > 0) {
            $type = EstablishmentInternalConsumptionType::query()
                ->forEstablishment($establishmentId)
                ->where('id', $typeId)
                ->where('is_active', true)
                ->first();
        } else {
            $type = EstablishmentInternalConsumptionType::query()
                ->forEstablishment($establishmentId)
                ->where('is_active', true)
                ->whereNotNull('account_id')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->first();
        }

        if (! $type) {
            $legacyAccountId = InternalConsumptionAccountResolver::resolveExpenseAccountId($establishmentId);
            if ($legacyAccountId) {
                return [
                    'ok' => true,
                    'type' => self::legacyFallbackType($establishmentId, $legacyAccountId),
                    'account_id' => $legacyAccountId,
                ];
            }

            return [
                'ok' => false,
                'status' => 422,
                'message' => __('establishment::responses.internal_consumption_type_required'),
                'code' => 'internal_consumption_type_required',
            ];
        }

        if (! $type->account_id) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => __('establishment::responses.internal_consumption_type_account_required', [
                    'type' => $type->displayName(),
                ]),
                'code' => 'internal_consumption_type_account_required',
            ];
        }

        return [
            'ok' => true,
            'type' => $type,
            'account_id' => (int) $type->account_id,
        ];
    }

    public static function calculateChargeAmount(
        EstablishmentInternalConsumptionType $type,
        Transaction $transaction,
        float $cogsAmount
    ): float {
        $valueType = self::normalizeValueType($type->value_type);
        $value = (float) ($type->value ?? 0);

        if ($valueType === self::VALUE_TYPE_COST) {
            return round(max(0, $cogsAmount), 2);
        }

        $base = (float) ($transaction->final_total ?: $transaction->total_before_tax ?: $transaction->totalAfterDiscount ?: 0);

        if ($valueType === self::VALUE_TYPE_PERCENT) {
            $percent = min(100, max(0, $value));

            return round(max(0, $base * ($percent / 100)), 2);
        }

        return round(max(0, $value), 2);
    }

    public static function resolveVarianceExpenseAccountId(int $establishmentId): ?int
    {
        return InternalConsumptionAccountResolver::resolveExpenseAccountId($establishmentId);
    }

    private static function legacyFallbackType(int $establishmentId, int $accountId): EstablishmentInternalConsumptionType
    {
        $type = new EstablishmentInternalConsumptionType([
            'establishment_id' => $establishmentId,
            'type_key' => 'legacy_internal_consumption',
            'name_ar' => 'استهلاك داخلي',
            'name_en' => 'Internal consumption',
            'value_type' => self::VALUE_TYPE_COST,
            'value' => null,
            'account_id' => $accountId,
            'is_active' => true,
        ]);
        $type->id = 0;

        return $type;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function catalogRows(): array
    {
        return EstablishmentInternalConsumptionType::query()
            ->with('assignedEstablishments:id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (EstablishmentInternalConsumptionType $row) => [
                'id' => (int) $row->id,
                'name_ar' => (string) ($row->name_ar ?? ''),
                'name_en' => (string) ($row->name_en ?? ''),
                'value_type' => (string) ($row->value_type ?? self::VALUE_TYPE_COST),
                'value' => $row->value !== null ? (float) $row->value : null,
                'account_id' => $row->account_id ? (int) $row->account_id : null,
                'type_key' => (string) $row->type_key,
                'is_active' => (bool) $row->is_active,
                'establishment_ids' => $row->assignedEstablishmentIds(),
            ])
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public static function syncCatalog(array $rows): void
    {
        $keptIds = [];
        $sort = 0;

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $accountId = self::nullableInt($row['account_id'] ?? null);
            $nameAr = trim((string) ($row['name_ar'] ?? ''));
            $nameEn = trim((string) ($row['name_en'] ?? ''));
            $valueType = self::normalizeValueType($row['value_type'] ?? self::VALUE_TYPE_COST);
            $value = self::normalizeValue($valueType, $row['value'] ?? null);
            $rowId = self::nullableInt($row['id'] ?? null);
            $assignedIds = array_values(array_filter(array_map('intval', (array) ($row['establishment_ids'] ?? []))));

            if ($accountId === null || $nameAr === '' || $nameEn === '') {
                continue;
            }

            $existing = $rowId
                ? EstablishmentInternalConsumptionType::query()->where('id', $rowId)->first()
                : null;

            $payload = [
                'type_key' => $existing?->type_key ?: self::uniqueKey($nameEn, $rowId),
                'name_ar' => $nameAr,
                'name_en' => $nameEn,
                'value_type' => $valueType,
                'value' => $value,
                'account_id' => $accountId,
                'is_active' => filter_var($row['is_active'] ?? false, FILTER_VALIDATE_BOOL),
                'sort_order' => $sort++,
                'establishment_id' => $assignedIds[0] ?? null,
            ];

            if ($existing) {
                $existing->update($payload);
                $existing->syncAssignedEstablishments($assignedIds);
                $keptIds[] = (int) $existing->id;

                continue;
            }

            $created = EstablishmentInternalConsumptionType::query()->create($payload);
            $created->syncAssignedEstablishments($assignedIds);
            $keptIds[] = (int) $created->id;
        }

        if ($keptIds === []) {
            return;
        }

        EstablishmentInternalConsumptionType::query()->whereNotIn('id', $keptIds)->delete();
    }

    private static function uniqueKey(string $nameEn, ?int $ignoreId = null): string
    {
        $baseKey = Str::slug($nameEn, '_');
        if ($baseKey === '') {
            $baseKey = 'ic_type_'.Str::lower(Str::random(6));
        }

        $key = $baseKey;
        $i = 1;
        while (
            EstablishmentInternalConsumptionType::query()
                ->where('type_key', $key)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $key = $baseKey.'_'.$i;
            $i++;
        }

        return $key;
    }

    private static function normalizeValueType(mixed $valueType): string
    {
        $normalized = strtolower(trim((string) $valueType));

        return in_array($normalized, [self::VALUE_TYPE_COST, self::VALUE_TYPE_PERCENT, self::VALUE_TYPE_FIXED], true)
            ? $normalized
            : self::VALUE_TYPE_COST;
    }

    private static function normalizeValue(string $valueType, mixed $value): ?float
    {
        if ($valueType === self::VALUE_TYPE_COST) {
            return null;
        }

        if ($value === null || $value === '') {
            return 0.0;
        }

        return round((float) $value, 4);
    }

    private static function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $int = (int) $value;

        return $int > 0 ? $int : null;
    }

    public const VALUE_TYPE_COST = EstablishmentInternalConsumptionType::VALUE_TYPE_COST;

    public const VALUE_TYPE_PERCENT = EstablishmentInternalConsumptionType::VALUE_TYPE_PERCENT;

    public const VALUE_TYPE_FIXED = EstablishmentInternalConsumptionType::VALUE_TYPE_FIXED;
}
