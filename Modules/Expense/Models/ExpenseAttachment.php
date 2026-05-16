<?php

namespace Modules\Expense\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseAttachment extends Model
{
    protected $guarded = ['id'];

    protected $table = 'expense_attachments';

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }
}
