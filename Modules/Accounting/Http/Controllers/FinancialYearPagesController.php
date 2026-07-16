<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Mpdf\Mpdf;
use Modules\Accounting\Models\FinancialYear;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Accounting\Services\FiscalPeriod\FinancialYearAnalyticsService;
use Modules\Employee\Models\Employee;

class FinancialYearPagesController extends Controller
{
    public function __construct(
        private readonly FinancialYearAnalyticsService $analytics
    ) {}

    public function showYear(int $id)
    {
        return redirect()->route('accounting-settings', [
            'tab' => 'financial-year',
            'year' => $id,
        ]);
    }

    public function reportYear(int $id)
    {
        $year = $this->loadYear($id);
        $report = $this->analytics->forYear($year, $this->resolveCurrentYearId());

        return view('accounting::settings.financial-year-report', [
            'report' => $report,
            'title' => __('accounting::financial_year.year_report_title', ['name' => $year->name]),
        ]);
    }

    public function reportYearPrint(int $id)
    {
        $year = $this->loadYear($id);
        $report = $this->analytics->forYear($year, $this->resolveCurrentYearId());

        return view('accounting::settings.financial-year-report-print', [
            'report' => $report,
            'title' => __('accounting::financial_year.year_report_title', ['name' => $year->name]),
        ]);
    }

    public function exportYearPdf(int $id)
    {
        $year = $this->loadYear($id);
        $report = $this->analytics->forYear($year, $this->resolveCurrentYearId());
        $title = __('accounting::financial_year.year_report_title', ['name' => $year->name]);

        $html = view('accounting::settings.financial-year-report-print', compact('report', 'title'))->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'DejaVuSans',
            'default_font_size' => 11,
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
        ]);
        $mpdf->WriteHTML($html);
        $filename = 'financial-year-'.$year->id.'.pdf';

        return $mpdf->Output($filename, 'D');
    }

    public function accountingClose(int $id, Request $request)
    {
        $year = $this->loadYear($id);
        $period = null;

        if ($request->filled('period_id')) {
            $period = $year->periods->firstWhere('id', (int) $request->query('period_id'));
        }

        return view('accounting::settings.fiscal-close-page', [
            'year' => $year,
            'period' => $period,
        ]);
    }

    public function showPeriod(int $periodId)
    {
        $period = $this->loadPeriod($periodId);
        $year = $period->financialYear;

        return view('accounting::settings.fiscal-period-show', [
            'period' => $period,
            'year' => $year,
            'creatorName' => $this->creatorLabel($period->created_by),
        ]);
    }

    public function reportPeriod(int $periodId)
    {
        $period = $this->loadPeriod($periodId);
        $report = $this->analytics->forPeriod($period, $this->resolveCurrentYearId());
        $title = __('accounting::financial_year.period_report_title', ['name' => $period->name]);

        return view('accounting::settings.fiscal-period-report', compact('report', 'title'));
    }

    public function reportPeriodPrint(int $periodId)
    {
        $period = $this->loadPeriod($periodId);
        $report = $this->analytics->forPeriod($period, $this->resolveCurrentYearId());
        $title = __('accounting::financial_year.period_report_title', ['name' => $period->name]);

        return view('accounting::settings.fiscal-period-report-print', compact('report', 'title'));
    }

    public function exportPeriodPdf(int $periodId)
    {
        $period = $this->loadPeriod($periodId);
        $report = $this->analytics->forPeriod($period, $this->resolveCurrentYearId());
        $title = __('accounting::financial_year.period_report_title', ['name' => $period->name]);

        $html = view('accounting::settings.fiscal-period-report-print', compact('report', 'title'))->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'DejaVuSans',
            'default_font_size' => 11,
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
        ]);
        $mpdf->WriteHTML($html);
        $filename = 'fiscal-period-'.$period->id.'.pdf';

        return $mpdf->Output($filename, 'D');
    }

    private function loadYear(int $id): FinancialYear
    {
        return FinancialYear::query()
            ->with(['periods', 'creator'])
            ->findOrFail($id);
    }

    private function loadPeriod(int $id): FiscalPeriod
    {
        return FiscalPeriod::query()
            ->with(['financialYear.periods', 'creator'])
            ->findOrFail($id);
    }

    private function resolveCurrentYearId(): ?int
    {
        $open = FinancialYear::query()->open()->orderBy('start_date')->first();

        if ($open) {
            return $open->id;
        }

        return FinancialYear::query()->orderByDesc('end_date')->value('id');
    }

    private function creatorLabel(?int $userId): string
    {
        if (! $userId) {
            return '—';
        }

        $employee = Employee::query()->find($userId);
        if (! $employee) {
            return '—';
        }

        return app()->getLocale() === 'ar'
            ? ($employee->name ?? $employee->name_en ?? '—')
            : ($employee->name_en ?? $employee->name ?? '—');
    }
}
