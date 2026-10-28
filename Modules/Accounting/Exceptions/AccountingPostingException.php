<?php

declare(strict_types=1);

namespace Modules\Accounting\Exceptions;

use RuntimeException;

/**
 * User-fixable accounting posting failure (missing customer GL, routing, cash account).
 */
final class AccountingPostingException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        array $replace = [],
        ?string $message = null
    ) {
        parent::__construct($message ?? __('accounting::lang.cashier_posting.'.$errorCode, $replace));
    }
}
