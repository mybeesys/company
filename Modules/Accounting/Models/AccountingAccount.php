<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

// use Modules\Accounting\Database\Factories\AccountingAccountFactory;

class AccountingAccount extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['id'];

    protected $table = 'accounting_accounts';

    protected $casts = [
        'allow_direct_posting' => 'boolean',
    ];
    // protected $connection = 'tenant';

    public function scopeForTenant($query)
    {
        if (tenant()) {
            return $query->on(tenant()->database);
        }

        return $query;
    }

    public function child_accounts()
    {
        return $this->hasMany(AccountingAccount::class, 'parent_account_id');
    }

    public function account_sub_type()
    {
        return $this->belongsTo(AccountingAccountTypes::class, 'account_sub_type_id');
    }

    public function detail_type()
    {
        return $this->belongsTo(AccountingAccountTypes::class, 'detail_type_id');
    }

    /**
     * @param  bool  $includeParents  When true, control accounts (e.g. العملاء 12041) appear too.
     *                                Journal posting UIs keep leaf-only; ledger may include parents.
     */
    public static function forDropdown($q = '', bool $includeParents = false)
    {
        $query = AccountingAccount::query()->where('status', 'active');

        if (! $includeParents) {
            $parentAccountIds = AccountingAccount::query()
                ->whereNotNull('parent_account_id')
                ->pluck('parent_account_id')
                ->unique()
                ->filter();

            if ($parentAccountIds->isNotEmpty()) {
                $query->whereNotIn('accounting_accounts.id', $parentAccountIds);
            }

            if (Schema::hasColumn('accounting_accounts', 'allow_direct_posting')) {
                $query->where(function ($inner) {
                    $inner->where('allow_direct_posting', 1)
                        ->orWhereNull('allow_direct_posting');
                });
            }
        }

        if (! empty($q)) {
            $query->where(function ($inner) use ($q) {
                $inner->where('accounting_accounts.name_ar', 'like', "%{$q}%")
                    ->orWhere('accounting_accounts.name_en', 'like', "%{$q}%")
                    ->orWhere('accounting_accounts.gl_code', 'like', "%{$q}%");
            });
        }

        return $query->orderBy('gl_code')->get();
    }
}
