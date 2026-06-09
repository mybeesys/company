<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// use Modules\Accounting\Database\Factories\AccountingCostCenterFactory;

class AccountingCostCenter extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function chiledCostCenter()
    {
        return $this->hasMany(AccountingCostCenter::class, 'parent_id');
    }

    public function parentCostCenter()
    {
        return $this->belongsTo(AccountingCostCenter::class, 'parent_id');
    }

    /** IDs of cost centers that have at least one child. */
    public static function parentCostCenterIds(): \Illuminate\Support\Collection
    {
        return static::where('parent_id', '<>', 'null')->pluck('parent_id')->unique();
    }

    /** Root main cost center IDs (parent_id = null). */
    public static function mainCostCenterIds(): \Illuminate\Support\Collection
    {
        return static::where('parent_id', 'null')->pluck('id');
    }

    /**
     * Leaf-level cost centers only — the last level where transactions may be assigned.
     */
    public function scopeLeafLevel($query, bool $activeOnly = false)
    {
        return $query
            ->where('is_main', 0)
            ->whereNotIn('id', static::parentCostCenterIds())
            ->whereNotIn('id', static::mainCostCenterIds())
            ->when($activeOnly, fn ($q) => $q->where('active', 1));
    }

    public function isLeaf(): bool
    {
        if ((int) $this->is_main === 1 || (string) $this->parent_id === 'null') {
            return false;
        }

        return ! static::parentCostCenterIds()->contains($this->id);
    }

    public static function forDropdown()
    {
        return static::leafLevel(activeOnly: true)->get();
    }

    public function transactions()
    {
        return $this->hasMany(AccountingAccountsTransaction::class, 'cost_center_id');
    }
}
