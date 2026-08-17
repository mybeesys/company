<?php

namespace Modules\Establishment\Services;

use Illuminate\Support\Str;
use Modules\Establishment\Models\EstablishmentPaymentAccount;

class EstablishmentPaymentAccountResolver
{
    /**
     * Resolve GL account for a branch payment method row id.
     * Source of truth: est_establishment_payment_accounts only.
     */
    public static function resolveAccountIdByMethodId(int $establishmentId, int $branchPaymentMethodId): ?int
    {
        if ($establishmentId <= 0 || $branchPaymentMethodId <= 0) {
            return null;
        }

        $method = EstablishmentPaymentAccount::query()
            ->forEstablishment($establishmentId)
            ->where('id', $branchPaymentMethodId)
            ->first();

        return $method?->accountIdForEstablishment($establishmentId);
    }

    /**
     * Legacy / special POS cash (-1): resolve by key "cash" on this branch.
     */
    public static function resolveCashMethodId(int $establishmentId): ?int
    {
        $id = EstablishmentPaymentAccount::query()
            ->forEstablishment($establishmentId)
            ->where('payment_method_key', 'cash')
            ->get()
            ->first(fn (EstablishmentPaymentAccount $row) => $row->accountIdForEstablishment($establishmentId))
            ?->id;

        return $id ? (int) $id : null;
    }

    /**
     * Sync branch cashier payment methods from form rows (name_ar, name_en, account_id).
     * Fully replaces the set for the establishment. No link to global payment_methods.
     *
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
            $rowId = self::nullableInt($row['id'] ?? null);

            if ($accountId === null || $nameAr === '' || $nameEn === '') {
                continue;
            }

            $key = self::uniqueKey($nameEn, $rowId);

            if ($rowId) {
                $existing = EstablishmentPaymentAccount::query()
                    ->where('establishment_id', $establishmentId)
                    ->where('id', $rowId)
                    ->first();

                if ($existing) {
                    $existing->update([
                        'payment_method_key' => $key,
                        'name_ar' => $nameAr,
                        'name_en' => $nameEn,
                        'account_id' => $accountId,
                    ]);
                    $keptIds[] = (int) $existing->id;

                    continue;
                }
            }

            $created = EstablishmentPaymentAccount::query()->create([
                'establishment_id' => $establishmentId,
                'payment_method_key' => $key,
                'name_ar' => $nameAr,
                'name_en' => $nameEn,
                'account_id' => $accountId,
            ]);
            $keptIds[] = (int) $created->id;
        }

        $deleteQuery = EstablishmentPaymentAccount::query()
            ->where('establishment_id', $establishmentId);

        if ($keptIds !== []) {
            $deleteQuery->whereNotIn('id', $keptIds);
        }

        $deleteQuery->delete();
    }

    /**
     * @return list<array{id: int, name_ar: string, name_en: string, account_id: int|null, payment_method_key: string}>
     */
    public static function rowsForEstablishment(int $establishmentId): array
    {
        return EstablishmentPaymentAccount::query()
            ->forEstablishment($establishmentId)
            ->with('assignedEstablishments:id')
            ->orderBy('id')
            ->get(['id', 'name_ar', 'name_en', 'account_id', 'payment_method_key'])
            ->map(fn (EstablishmentPaymentAccount $row) => [
                'id' => (int) $row->id,
                'name_ar' => (string) ($row->name_ar ?? ''),
                'name_en' => (string) ($row->name_en ?? $row->payment_method_key ?? ''),
                'account_id' => $row->accountIdForEstablishment($establishmentId),
                'payment_method_key' => (string) $row->payment_method_key,
            ])
            ->all();
    }

    /**
     * @return array{ok: true, account_id: int, method_id: int, method: EstablishmentPaymentAccount}|array{ok: false, status: int, message: string}
     */
    public static function resolveForCashierPayment(int $establishmentId, int $branchPaymentMethodId): array
    {
        $method = EstablishmentPaymentAccount::query()
            ->forEstablishment($establishmentId)
            ->where('id', $branchPaymentMethodId)
            ->first();

        $accountId = $method?->accountIdForEstablishment($establishmentId);
        if (! $method || ! $accountId) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => __('establishment::responses.cashier_payment_account_required', [
                    'method' => $method
                        ? (app()->getLocale() === 'ar' ? ($method->name_ar ?: $method->name_en) : ($method->name_en ?: $method->name_ar))
                        : (string) $branchPaymentMethodId,
                ]),
            ];
        }

        return [
            'ok' => true,
            'account_id' => $accountId,
            'method_id' => (int) $method->id,
            'method' => $method,
        ];
    }

    /**
     * @return list<array{id: int, name_ar: string, name_en: string, account_id: int|null, payment_method_key: string, establishment_ids: list<int>, branch_accounts: array<int, int|null>}>
     */
    public static function catalogRows(): array
    {
        return EstablishmentPaymentAccount::query()
            ->with('assignedEstablishments:id')
            ->orderBy('id')
            ->get()
            ->map(function (EstablishmentPaymentAccount $row) {
                $establishmentIds = $row->assignedEstablishmentIds();
                $fromPivot = [];
                foreach ($row->assignedEstablishments as $establishment) {
                    $fromPivot[(int) $establishment->id] = (int) ($establishment->pivot->account_id ?: $row->account_id ?: 0) ?: null;
                }

                $branchAccounts = [];
                foreach ($establishmentIds as $establishmentId) {
                    $branchAccounts[$establishmentId] = $fromPivot[$establishmentId]
                        ?? ($row->account_id ? (int) $row->account_id : null);
                }

                return [
                    'id' => (int) $row->id,
                    'name_ar' => (string) ($row->name_ar ?? ''),
                    'name_en' => (string) ($row->name_en ?? $row->payment_method_key ?? ''),
                    'account_id' => $row->account_id ? (int) $row->account_id : null,
                    'payment_method_key' => (string) $row->payment_method_key,
                    'establishment_ids' => $establishmentIds,
                    'branch_accounts' => $branchAccounts,
                ];
            })
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public static function syncCatalog(array $rows): void
    {
        $keptIds = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $accountId = self::nullableInt($row['account_id'] ?? null);
            $nameAr = trim((string) ($row['name_ar'] ?? ''));
            $nameEn = trim((string) ($row['name_en'] ?? ''));
            $rowId = self::nullableInt($row['id'] ?? null);
            $assignedIds = array_values(array_filter(array_map('intval', (array) ($row['establishment_ids'] ?? []))));
            $branchAccounts = self::normalizeBranchAccounts($row['branch_accounts'] ?? [], $assignedIds, $accountId);

            if ($nameAr === '' || $nameEn === '') {
                continue;
            }

            $existing = $rowId
                ? EstablishmentPaymentAccount::query()->where('id', $rowId)->first()
                : null;
            $key = $existing?->payment_method_key
                ?: self::uniqueKey($nameEn, $rowId, (string) ($row['payment_method_key'] ?? ''));
            $firstAccountId = $branchAccounts !== [] ? (int) reset($branchAccounts) : $accountId;
            $payload = [
                'payment_method_key' => $key,
                'name_ar' => $nameAr,
                'name_en' => $nameEn,
                'account_id' => $firstAccountId,
                'establishment_id' => $assignedIds[0] ?? (array_key_first($branchAccounts) ?: null),
            ];

            if ($existing) {
                $existing->update($payload);
                $existing->syncBranchAccounts($branchAccounts);
                $keptIds[] = (int) $existing->id;

                continue;
            }

            $created = EstablishmentPaymentAccount::query()->create($payload);
            $created->syncBranchAccounts($branchAccounts);
            $keptIds[] = (int) $created->id;
        }

        if ($keptIds === []) {
            return;
        }

        EstablishmentPaymentAccount::query()->whereNotIn('id', $keptIds)->delete();
    }

    public static function defaultCatalogRows(): array
    {
        return [
            ['id' => null, 'name_ar' => 'نقداً', 'name_en' => 'Cash', 'account_id' => null, 'payment_method_key' => 'cash', 'establishment_ids' => [], 'branch_accounts' => []],
            ['id' => null, 'name_ar' => 'بطاقة', 'name_en' => 'Card', 'account_id' => null, 'payment_method_key' => 'card', 'establishment_ids' => [], 'branch_accounts' => []],
            ['id' => null, 'name_ar' => 'طلبات توصيل', 'name_en' => 'Delivery orders', 'account_id' => null, 'payment_method_key' => 'delivery_apps', 'establishment_ids' => [], 'branch_accounts' => []],
        ];
    }

    /**
     * @param  mixed  $raw
     * @param  list<int>  $assignedIds
     * @return array<int, int>
     */
    private static function normalizeBranchAccounts(mixed $raw, array $assignedIds, ?int $fallbackAccountId): array
    {
        $map = [];
        if (is_array($raw)) {
            foreach ($raw as $establishmentId => $accountId) {
                $estId = (int) $establishmentId;
                $accId = self::nullableInt($accountId);
                if ($estId > 0 && $accId) {
                    $map[$estId] = $accId;
                }
            }
        }

        if ($assignedIds === []) {
            return $map;
        }

        $filtered = [];
        foreach ($assignedIds as $estId) {
            if (isset($map[$estId])) {
                $filtered[$estId] = $map[$estId];
            } elseif ($fallbackAccountId) {
                $filtered[$estId] = $fallbackAccountId;
            }
        }

        return $filtered;
    }

    private static function uniqueKey(string $nameEn, ?int $ignoreId = null, ?string $preferredKey = null): string
    {
        $preferred = strtolower(trim((string) $preferredKey));
        $normalized = strtolower(trim($nameEn));

        if (in_array($preferred, ['cash', 'card', 'delivery_apps'], true)) {
            $baseKey = $preferred;
        } elseif (in_array($normalized, ['cash'], true)) {
            $baseKey = 'cash';
        } elseif (in_array($normalized, ['card'], true)) {
            $baseKey = 'card';
        } elseif (in_array($normalized, ['delivery_apps', 'delivery orders', 'delivery_orders'], true)) {
            $baseKey = 'delivery_apps';
        } else {
            $baseKey = Str::slug($nameEn, '_');
            if ($baseKey === '') {
                $baseKey = 'method_'.Str::lower(Str::random(6));
            }
        }

        $key = $baseKey;
        $i = 1;
        while (
            EstablishmentPaymentAccount::query()
                ->where('payment_method_key', $key)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $key = $baseKey.'_'.$i;
            $i++;
        }

        return $key;
    }

    private static function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $int = (int) $value;

        return $int > 0 ? $int : null;
    }
}
