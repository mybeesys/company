<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Modules\Accounting\Models\FinancialYear;
use Modules\Accounting\Models\FiscalPeriod;
use Mockery;
use Modules\Accounting\Services\FiscalPeriod\FiscalCloseRoutingResolver;
use Modules\Accounting\Services\FiscalPeriod\FiscalPeriodCloseReadinessChecker;
use Tests\TestCase;

class FiscalPeriodCloseReadinessCheckerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function checker(bool $routingComplete = false): FiscalPeriodCloseReadinessChecker
    {
        $routing = Mockery::mock(FiscalCloseRoutingResolver::class);
        $routing->shouldReceive('status')->andReturn([
            'complete' => $routingComplete,
            'current_period_result' => null,
            'retained_earnings' => null,
            'missing' => $routingComplete ? [] : ['fiscal_close_current_period_result'],
        ]);
        $routing->shouldReceive('validationErrors')->andReturn(
            $routingComplete ? [] : ['missing routing']
        );

        return new FiscalPeriodCloseReadinessChecker($routing);
    }

    public function test_year_end_boundary_is_true_when_closing_last_period(): void
    {
        $checker = new FiscalPeriodCloseReadinessChecker(new FiscalCloseRoutingResolver());

        $year = new FinancialYear([
            'name' => 'FY 2025',
            'start_date' => Carbon::parse('2025-01-01'),
            'end_date' => Carbon::parse('2025-12-31'),
            'status' => FinancialYear::STATUS_OPEN,
        ]);
        $year->setRelation('periods', collect([
            tap(new FiscalPeriod([
                'period_number' => 1,
                'name' => 'Jan',
                'start_date' => Carbon::parse('2025-01-01'),
                'end_date' => Carbon::parse('2025-01-31'),
                'status' => FiscalPeriod::STATUS_CLOSED,
            ]), fn ($p) => $p->id = 1),
            tap(new FiscalPeriod([
                'period_number' => 12,
                'name' => 'Dec',
                'start_date' => Carbon::parse('2025-12-01'),
                'end_date' => Carbon::parse('2025-12-31'),
                'status' => FiscalPeriod::STATUS_OPEN,
            ]), fn ($p) => $p->id = 2),
        ]));

        $last = $year->periods->last();

        $this->assertTrue($checker->isYearEndBoundary($year, $last));
    }

    public function test_year_end_boundary_is_false_for_middle_period(): void
    {
        $checker = $this->checker();

        $year = new FinancialYear([
            'name' => 'FY 2025',
            'start_date' => Carbon::parse('2025-01-01'),
            'end_date' => Carbon::parse('2025-12-31'),
            'status' => FinancialYear::STATUS_OPEN,
        ]);
        $year->setRelation('periods', collect([
            tap(new FiscalPeriod([
                'period_number' => 1,
                'name' => 'Jan',
                'start_date' => Carbon::parse('2025-01-01'),
                'end_date' => Carbon::parse('2025-01-31'),
                'status' => FiscalPeriod::STATUS_OPEN,
            ]), fn ($p) => $p->id = 1),
            tap(new FiscalPeriod([
                'period_number' => 12,
                'name' => 'Dec',
                'start_date' => Carbon::parse('2025-12-01'),
                'end_date' => Carbon::parse('2025-12-31'),
                'status' => FiscalPeriod::STATUS_OPEN,
            ]), fn ($p) => $p->id = 2),
        ]));

        $first = $year->periods->first();

        $this->assertFalse($checker->isYearEndBoundary($year, $first));
    }

    public function test_check_marks_non_boundary_period_as_not_previewable(): void
    {
        $checker = $this->checker();

        $year = new FinancialYear([
            'name' => 'FY 2025',
            'start_date' => Carbon::parse('2025-01-01'),
            'end_date' => Carbon::parse('2025-12-31'),
            'status' => FinancialYear::STATUS_OPEN,
        ]);
        $year->setRelation('periods', collect([
            tap(new FiscalPeriod([
                'period_number' => 1,
                'name' => 'Jan',
                'start_date' => Carbon::parse('2025-01-01'),
                'end_date' => Carbon::parse('2025-01-31'),
                'status' => FiscalPeriod::STATUS_OPEN,
            ]), fn ($p) => $p->id = 1),
            tap(new FiscalPeriod([
                'period_number' => 12,
                'name' => 'Dec',
                'start_date' => Carbon::parse('2025-12-01'),
                'end_date' => Carbon::parse('2025-12-31'),
                'status' => FiscalPeriod::STATUS_OPEN,
            ]), fn ($p) => $p->id = 2),
        ]));

        $result = $checker->check($year, $year->periods->first());

        $this->assertFalse($result['is_year_end_boundary']);
        $this->assertFalse($result['can_preview']);
        $this->assertNotEmpty($result['warnings']);
    }
}
