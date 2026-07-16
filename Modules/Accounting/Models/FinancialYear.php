<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Employee\Models\Employee;

class FinancialYear extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_CLOSING = 'closing';

    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_first_year' => 'boolean',
        'accounting_closed_at' => 'datetime',
    ];

    public function periods(): HasMany
    {
        return $this->hasMany(FiscalPeriod::class)->orderBy('period_number');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeContainingDate($query, $date)
    {
        return $query->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date);
    }
}
