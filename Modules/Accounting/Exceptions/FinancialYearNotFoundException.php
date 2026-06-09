<?php

namespace Modules\Accounting\Exceptions;

use Carbon\CarbonInterface;

class FinancialYearNotFoundException extends FiscalPeriodException
{
    public function __construct(
        public readonly CarbonInterface|string $operationDate,
        ?string $message = null
    ) {
        parent::__construct($message ?? '');
    }

    protected function defaultMessage(): string
    {
        $date = $this->operationDate instanceof CarbonInterface
            ? $this->operationDate->toDateString()
            : (string) $this->operationDate;

        return __('accounting::financial_year.exceptions.year_not_found', ['date' => $date]);
    }
}
