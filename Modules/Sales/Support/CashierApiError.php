<?php

declare(strict_types=1);

namespace Modules\Sales\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Modules\Accounting\Exceptions\AccountingPostingException;
use RuntimeException;
use Throwable;

final class CashierApiError
{
    /**
     * Cashier-facing JSON. Accounting/data errors are 422 with a clear message and no stack trace.
     */
    public static function toResponse(Throwable $e): JsonResponse
    {
        if ($e instanceof ValidationException) {
            $message = collect($e->errors())->flatten()->first() ?: $e->getMessage();

            return response()->json([
                'message' => (string) $message,
                'code' => 'validation_error',
                'errors' => $e->errors(),
            ], 422);
        }

        if ($e instanceof AccountingPostingException) {
            return response()->json([
                'message' => __('accounting::lang.cashier_posting.'.$e->errorCode, [], self::responseLocale()),
                'code' => $e->errorCode,
            ], 422);
        }

        if ($e instanceof RuntimeException) {
            $mapped = self::mapRuntimeMessage($e->getMessage());
            if ($mapped !== null) {
                return response()->json($mapped, 422);
            }
        }

        return response()->json([
            'message' => __('sales::lang.cashier_unexpected', [], self::responseLocale()),
        ], 500);
    }

    private static function responseLocale(): string
    {
        $locale = strtolower((string) app()->getLocale());

        return str_starts_with($locale, 'en') ? 'en' : 'ar';
    }

    /**
     * @return array{message: string, code: string}|null
     */
    public static function mapRuntimeMessage(string $raw): ?array
    {
        $haystack = $raw;
        foreach (self::patterns() as $needle => $code) {
            if ($needle !== '' && mb_stripos($haystack, $needle) !== false) {
                return [
                    'message' => __('accounting::lang.cashier_posting.'.$code, [], self::responseLocale()),
                    'code' => $code,
                ];
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private static function patterns(): array
    {
        return [
            'Customer account is missing' => 'customer_account_missing',
            'Please link an accounting account to the customer' => 'customer_account_missing',
            'غير مربوط بحساب محاسبي' => 'customer_account_missing',
            'Customer accounting account' => 'customer_account_invalid',
            'Collection accounting account' => 'collection_account_invalid',
            'Cash account #' => 'cash_account_invalid',
            'Accounting routing missing for sell-return' => 'routing_missing_sell_return',
            'sales_sell_return' => 'routing_missing_sell_return',
            'Accounting routing missing for sell' => 'routing_missing_sell',
            'configure sales_sales' => 'routing_missing_sell',
            'توجيه حسابات المبيعات' => 'routing_missing_sell',
            'Cash/bank account is missing' => 'cash_account_missing',
            'select a payment account' => 'cash_account_missing',
            'حساب الصندوق/البنك لطريقة الدفع' => 'cash_account_missing',
            'sales_discount_allowed' => 'discount_routing_missing',
            'Invoice discount is present' => 'discount_routing_missing',
            'Accounting line is missing account_id' => 'account_line_missing',
            'does not exist. Please re-link' => 'account_missing',
            'is invalid for' => 'customer_account_invalid',
            'Auto journal is not balanced' => 'journal_unbalanced',
            'القيد غير متوازن' => 'journal_unbalanced',
        ];
    }
}
