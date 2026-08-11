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

        $accountId = EstablishmentPaymentAccount::query()
            ->where('establishment_id', $establishmentId)
            ->where('id', $branchPaymentMethodId)
            ->whereNotNull('account_id')
            ->value('account_id');

        return $accountId ? (int) $accountId : null;
    }

    /**
     * Legacy / special POS cash (-1): resolve by key "cash" on this branch.
     */
    public static function resolveCashMethodId(int $establishmentId): ?int
    {
        $id = EstablishmentPaymentAccount::query()
            ->where('establishment_id', $establishmentId)
            ->where('payment_method_key', 'cash')
            ->whereNotNull('account_id')
            ->value('id');

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

            $key = self::uniqueKeyForEstablishment($establishmentId, $nameEn, $rowId);

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
            ->where('establishment_id', $establishmentId)
            ->orderBy('id')
            ->get(['id', 'name_ar', 'name_en', 'account_id', 'payment_method_key'])
            ->map(fn (EstablishmentPaymentAccount $row) => [
                'id' => (int) $row->id,
                'name_ar' => (string) ($row->name_ar ?? ''),
                'name_en' => (string) ($row->name_en ?? $row->payment_method_key ?? ''),
                'account_id' => $row->account_id ? (int) $row->account_id : null,
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
            ->where('establishment_id', $establishmentId)
            ->where('id', $branchPaymentMethodId)
            ->first();

        if (! $method || ! $method->account_id) {
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
            'account_id' => (int) $method->account_id,
            'method_id' => (int) $method->id,
            'method' => $method,
        ];
    }

    private static function uniqueKeyForEstablishment(int $establishmentId, string $nameEn, ?int $ignoreId = null): string
    {
        $baseKey = Str::slug($nameEn, '_');
        if ($baseKey === '') {
            $baseKey = 'method_'.Str::lower(Str::random(6));
        }

        // Keep classic cash key stable when labeled cash
        if (Str::lower(trim($nameEn)) === 'cash' || $baseKey === 'cash') {
            $baseKey = 'cash';
        }

        $key = $baseKey;
        $i = 1;
        while (
            EstablishmentPaymentAccount::query()
                ->where('establishment_id', $establishmentId)
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
