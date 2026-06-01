<?php

namespace Modules\Accounting\Exceptions;

use Exception;

abstract class FiscalPeriodException extends Exception
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message ?: $this->defaultMessage(), $code, $previous);
    }

    abstract protected function defaultMessage(): string;
}
