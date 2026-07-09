<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Employee\Models\Employee;

class CentralReferralService
{
    public function centralConnection(): string
    {
        $connection = (string) config('tenancy.database.central_connection', 'mysql');

        return $connection !== '' ? $connection : 'mysql';
    }

    public function tablesReady(): bool
    {
        $central = $this->centralConnection();

        return Schema::connection($central)->hasTable('referral_codes')
            && Schema::connection($central)->hasTable('referral_program_settings');
    }

    public function programEnabled(): bool
    {
        if (! $this->tablesReady()) {
            return false;
        }

        return (bool) DB::connection($this->centralConnection())
            ->table('referral_program_settings')
            ->value('is_enabled');
    }

    public function deviceId(Request $request): string
    {
        $cookie = config('referrals.device_cookie', 'mb_device_id');
        $existing = $request->cookie($cookie);

        if (is_string($existing) && strlen($existing) >= 16) {
            return $existing;
        }

        return (string) Str::uuid();
    }

    public function deviceHash(string $deviceId): string
    {
        return hash('sha256', $deviceId);
    }

    public function inviteUrl(string $code): string
    {
        return config('referrals.central_app_url').'/invite/'.$code;
    }

    /**
     * @return object{
     *     id:int,
     *     code:string,
     *     employee_name:?string,
     *     employee_email:?string,
     *     total_points:int,
     *     sender_device_hash:?string
     * }
     */
    public function codeForEmployee(Employee $employee, ?string $tenantId = null): ?object
    {
        if (! $this->tablesReady()) {
            return null;
        }

        $tenantId = $tenantId ?? tenant('id');

        if (! is_string($tenantId) || $tenantId === '') {
            return null;
        }

        $central = $this->centralConnection();

        $existing = DB::connection($central)
            ->table('referral_codes')
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employee->id)
            ->where('is_active', true)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        do {
            $code = strtoupper(Str::random(10));
        } while (DB::connection($central)->table('referral_codes')->where('code', $code)->exists());

        $name = $employee->translated_name ?: $employee->name ?: $employee->email;

        $id = DB::connection($central)->table('referral_codes')->insertGetId([
            'code' => $code,
            'tenant_id' => $tenantId,
            'employee_id' => $employee->id,
            'employee_name' => Str::limit((string) $name, 255, ''),
            'employee_email' => $employee->email,
            'is_active' => true,
            'total_points' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::connection($central)->table('referral_codes')->where('id', $id)->first();
    }

    public function syncSenderDeviceHash(int $referralCodeId, string $deviceHash): void
    {
        DB::connection($this->centralConnection())
            ->table('referral_codes')
            ->where('id', $referralCodeId)
            ->update([
                'sender_device_hash' => $deviceHash,
                'updated_at' => now(),
            ]);
    }

    /**
     * @param  list<string>  $recipientEmails
     */
    public function recordInvitation(
        int $referralCodeId,
        string $channel,
        ?array $recipientEmails,
        string $senderDeviceHash,
    ): int {
        $central = $this->centralConnection();

        $this->syncSenderDeviceHash($referralCodeId, $senderDeviceHash);

        return (int) DB::connection($central)->table('referral_invitations')->insertGetId([
            'referral_code_id' => $referralCodeId,
            'channel' => $channel,
            'recipient_emails' => $recipientEmails ? json_encode(array_values($recipientEmails)) : null,
            'sender_device_hash' => $senderDeviceHash,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function promotionalText(object $referralCode, ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $settings = DB::connection($this->centralConnection())
            ->table('referral_program_settings')
            ->first();

        $template = $locale === 'ar'
            ? ($settings->promotional_template_ar ?? $settings->promotional_template_en ?? '{link}')
            : ($settings->promotional_template_en ?? $settings->promotional_template_ar ?? '{link}');

        $link = $this->inviteUrl($referralCode->code);
        $name = $referralCode->employee_name ?: __('employee::referrals.default_referrer_name');

        return str_replace(
            ['{link}', '{name}', '{code}'],
            [$link, $name, $referralCode->code],
            (string) $template,
        );
    }

    /**
     * @return array{
     *     invitations:int,
     *     visits:int,
     *     distinct_visits:int,
     *     conversions:int,
     *     total_points:int
     * }
     */
    public function stats(int $referralCodeId): array
    {
        $central = $this->centralConnection();

        return [
            'invitations' => (int) DB::connection($central)->table('referral_invitations')
                ->where('referral_code_id', $referralCodeId)->count(),
            'visits' => (int) DB::connection($central)->table('referral_visits')
                ->where('referral_code_id', $referralCodeId)->count(),
            'distinct_visits' => (int) DB::connection($central)->table('referral_visits')
                ->where('referral_code_id', $referralCodeId)
                ->where('is_distinct_device', true)->count(),
            'conversions' => (int) DB::connection($central)->table('referral_conversions')
                ->where('referral_code_id', $referralCodeId)
                ->where('status', 'confirmed')->count(),
            'total_points' => (int) DB::connection($central)->table('referral_codes')
                ->where('id', $referralCodeId)->value('total_points'),
        ];
    }

    /**
     * @return Collection<int, object>
     */
    public function recentInvitations(int $referralCodeId, int $limit = 10): Collection
    {
        return DB::connection($this->centralConnection())
            ->table('referral_invitations')
            ->where('referral_code_id', $referralCodeId)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    public function recentConversions(int $referralCodeId, int $limit = 10): Collection
    {
        return DB::connection($this->centralConnection())
            ->table('referral_conversions')
            ->leftJoin('companies', 'companies.id', '=', 'referral_conversions.company_id')
            ->where('referral_conversions.referral_code_id', $referralCodeId)
            ->orderByDesc('referral_conversions.id')
            ->limit($limit)
            ->get([
                'referral_conversions.*',
                'companies.name as company_name',
            ]);
    }
}
