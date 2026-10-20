<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reads/writes central `saved_payment_cards` from the tenant app.
 */
class SavedPaymentCardRepository
{
    protected function connection(): string
    {
        return (string) config('tenancy.database.central_connection', 'mysql') ?: 'mysql';
    }

    public function ready(): bool
    {
        return Schema::connection($this->connection())->hasTable('saved_payment_cards');
    }

    /**
     * @return Collection<int, object>
     */
    public function forCompany(int $companyId): Collection
    {
        if (! $this->ready()) {
            return collect();
        }

        $this->dedupeForCompany($companyId);

        return DB::connection($this->connection())
            ->table('saved_payment_cards')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->where('status', 'active')
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Soft-delete active duplicates that share brand + last4 + expiry.
     * Keeps the default card when present, otherwise the newest id.
     */
    public function dedupeForCompany(int $companyId): int
    {
        if (! $this->ready() || $companyId <= 0) {
            return 0;
        }

        $db = DB::connection($this->connection());
        $removed = 0;

        $db->transaction(function () use ($db, $companyId, &$removed) {
            $cards = $db->table('saved_payment_cards')
                ->where('company_id', $companyId)
                ->whereNull('deleted_at')
                ->where('status', 'active')
                ->orderByDesc('is_default')
                ->orderByDesc('id')
                ->get();

            $seen = [];

            foreach ($cards as $card) {
                $brand = strtoupper(trim((string) ($card->brand ?? '')));
                $lastFour = preg_replace('/\D+/', '', (string) ($card->last_four ?? '')) ?: '';
                $lastFour = $lastFour !== '' ? substr($lastFour, -4) : '';
                $month = preg_replace('/\D+/', '', (string) ($card->expiry_month ?? '')) ?: '';
                $month = $month !== '' ? str_pad(substr($month, -2), 2, '0', STR_PAD_LEFT) : '';
                $year = preg_replace('/\D+/', '', (string) ($card->expiry_year ?? '')) ?: '';
                if (strlen($year) === 2) {
                    $year = '20'.$year;
                }
                $year = $year !== '' ? substr($year, -4) : '';

                if ($brand === '' || $lastFour === '' || $month === '' || $year === '') {
                    continue;
                }

                $key = $brand.'|'.$lastFour.'|'.$month.'|'.substr($year, -2);

                if (! isset($seen[$key])) {
                    $seen[$key] = (int) $card->id;

                    continue;
                }

                $db->table('saved_payment_cards')
                    ->where('id', $card->id)
                    ->where('company_id', $companyId)
                    ->update([
                        'registration_id' => mb_substr((string) $card->registration_id.'_dup'.$card->id, 0, 64),
                        'is_default' => false,
                        'status' => 'merged',
                        'deleted_at' => now(),
                        'updated_at' => now(),
                    ]);

                $removed++;
            }

            $default = $db->table('saved_payment_cards')
                ->where('company_id', $companyId)
                ->whereNull('deleted_at')
                ->where('status', 'active')
                ->where('is_default', true)
                ->orderByDesc('id')
                ->first();

            if ($default) {
                $db->table('saved_payment_cards')
                    ->where('company_id', $companyId)
                    ->where('id', '!=', $default->id)
                    ->whereNull('deleted_at')
                    ->update(['is_default' => false, 'updated_at' => now()]);
            } else {
                $next = $db->table('saved_payment_cards')
                    ->where('company_id', $companyId)
                    ->whereNull('deleted_at')
                    ->where('status', 'active')
                    ->orderByDesc('id')
                    ->first();

                if ($next) {
                    $db->table('saved_payment_cards')
                        ->where('id', $next->id)
                        ->update(['is_default' => true, 'updated_at' => now()]);
                }
            }
        });

        return $removed;
    }

    public function findForCompany(int $companyId, int $cardId): ?object
    {
        if (! $this->ready()) {
            return null;
        }

        return DB::connection($this->connection())
            ->table('saved_payment_cards')
            ->where('company_id', $companyId)
            ->where('id', $cardId)
            ->whereNull('deleted_at')
            ->first();
    }

    public function setDefault(int $companyId, int $cardId): bool
    {
        $card = $this->findForCompany($companyId, $cardId);
        if (! $card) {
            return false;
        }

        $db = DB::connection($this->connection());

        $db->transaction(function () use ($db, $companyId, $cardId) {
            $db->table('saved_payment_cards')
                ->where('company_id', $companyId)
                ->whereNull('deleted_at')
                ->update(['is_default' => false, 'updated_at' => now()]);

            $db->table('saved_payment_cards')
                ->where('id', $cardId)
                ->where('company_id', $companyId)
                ->update([
                    'is_default' => true,
                    'status' => 'active',
                    'updated_at' => now(),
                ]);
        });

        return true;
    }

    /**
     * @param  array{nickname?: ?string, holder?: ?string, is_default?: bool}  $data
     */
    public function update(int $companyId, int $cardId, array $data): bool
    {
        $card = $this->findForCompany($companyId, $cardId);
        if (! $card) {
            return false;
        }

        $db = DB::connection($this->connection());
        $payload = ['updated_at' => now()];

        if (array_key_exists('nickname', $data)) {
            $nickname = trim((string) ($data['nickname'] ?? ''));
            $payload['nickname'] = $nickname !== '' ? mb_substr($nickname, 0, 80) : null;
        }

        if (array_key_exists('holder', $data)) {
            $holder = trim((string) ($data['holder'] ?? ''));
            $payload['holder'] = $holder !== '' ? mb_substr($holder, 0, 120) : null;
        }

        $db->table('saved_payment_cards')
            ->where('id', $cardId)
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->update($payload);

        if (! empty($data['is_default'])) {
            return $this->setDefault($companyId, $cardId);
        }

        return true;
    }

    public function delete(int $companyId, int $cardId): bool
    {
        $card = $this->findForCompany($companyId, $cardId);
        if (! $card) {
            return false;
        }

        $db = DB::connection($this->connection());

        $db->transaction(function () use ($db, $companyId, $cardId, $card) {
            $db->table('saved_payment_cards')
                ->where('id', $cardId)
                ->where('company_id', $companyId)
                ->update([
                    'deleted_at' => now(),
                    'is_default' => false,
                    'updated_at' => now(),
                ]);

            if ($card->is_default) {
                $next = $db->table('saved_payment_cards')
                    ->where('company_id', $companyId)
                    ->whereNull('deleted_at')
                    ->where('status', 'active')
                    ->orderByDesc('id')
                    ->first();

                if ($next) {
                    $db->table('saved_payment_cards')
                        ->where('id', $next->id)
                        ->update(['is_default' => true, 'updated_at' => now()]);
                }
            }
        });

        return true;
    }

    public function label(object $card): string
    {
        if (! empty($card->nickname)) {
            return (string) $card->nickname;
        }

        $brand = strtoupper((string) ($card->brand ?: 'CARD'));
        $last = $card->last_four ? '•••• '.$card->last_four : '••••';

        return trim($brand.' '.$last);
    }

    public function expiryLabel(object $card): string
    {
        if (! $card->expiry_month || ! $card->expiry_year) {
            return '—';
        }

        $year = strlen((string) $card->expiry_year) === 4
            ? substr((string) $card->expiry_year, -2)
            : (string) $card->expiry_year;

        return str_pad((string) $card->expiry_month, 2, '0', STR_PAD_LEFT).'/'.$year;
    }
}
