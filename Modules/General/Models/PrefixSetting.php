<?php

namespace Modules\General\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\General\Database\Factories\PrefixSettingFactory;

class PrefixSetting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['id'];

    public static function updateRefNumbers()
    {
        $prefixSettings = PrefixSetting::all()->keyBy('type');

        Transaction::chunk(100, function ($transactions) use ($prefixSettings) {
            foreach ($transactions as $transaction) {
                $type = $transaction->type;
                if (in_array($type, ['sell', 'sell-return', 'purchases-return', 'purchases'])) {
                    $type = 'invoices';
                }

                if (isset($prefixSettings[$type])) {
                    $prefix = $prefixSettings[$type]->prefix;

                    $newRefNo = $prefix . '-' . date('Y') . '/' . str_pad($transaction->id, 4, '0', STR_PAD_LEFT);

                    $transaction->update(['ref_no' => $newRefNo]);
                }
            }
        });

        TransactionPayments::chunk(100, function ($transactionPayments) use ($prefixSettings) {
            foreach ($transactionPayments as $transaction) {
                $type = $transaction->transaction?->type;
                if (in_array($type, [ 'purchases-return', 'purchases'])) {
                    $type = 'purchase';
                }
                if (in_array($type, ['sell', 'sell-return'])) {
                    $type = 'sell';
                }

                if (isset($prefixSettings[$type])) {
                    $prefix = $prefixSettings[$type]->prefix;

                    $newRefNo = $prefix . '-' . date('Y') . '/' . str_pad($transaction->id, 4, '0', STR_PAD_LEFT);

                    $transaction->update(['payment_ref_no' => $newRefNo]);
                }
            }
        });
    }
}