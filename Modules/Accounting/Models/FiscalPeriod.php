<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiscalPeriod extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_CLOSING = 'closing';

    public const STATUS_UPCOMING = 'upcoming';

    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'closed_at' => 'datetime',
    ];

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function allowsPosting(): bool
    {
        return $this->status === self::STATUS_OPEN
            && $this->financialYear
            && $this->financialYear->isOpen();
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
