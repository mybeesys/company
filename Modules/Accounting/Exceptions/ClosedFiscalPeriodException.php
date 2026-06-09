<?php

namespace Modules\Accounting\Exceptions;

use Carbon\CarbonInterface;
use Modules\Accounting\Models\FiscalPeriod;

class ClosedFiscalPeriodException extends FiscalPeriodException
{
    public function __construct(
        public readonly ?FiscalPeriod $period,
        public readonly CarbonInterface|string $operationDate,
        ?string $message = null
    ) {
        parent::__construct($message ?? '');
    }

    protected function defaultMessage(): string
    {
        $name = $this->period?->name ?? '—';
        $date = $this->operationDate instanceof CarbonInterface
            ? $this->operationDate->toDateString()
            : (string) $this->operationDate;

        return __('accounting::financial_year.exceptions.period_closed', [
            'period' => $name,
            'date' => $date,
        ]);
    }
}
