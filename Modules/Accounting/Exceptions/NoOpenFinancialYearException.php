<?php

namespace Modules\Accounting\Exceptions;

class NoOpenFinancialYearException extends FiscalPeriodException
{
    protected function defaultMessage(): string
    {
        return __('accounting::financial_year.exceptions.no_open_year');
    }
}
