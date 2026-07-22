<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Accounting\Exceptions\FiscalPeriodException;
use Modules\Accounting\Models\FinancialYear;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Accounting\Services\FiscalPeriod\FiscalPeriodAccountingCloseService;
use Modules\Accounting\Services\FiscalPeriod\FinancialYearApiPresenter;
use Modules\Accounting\Services\FiscalPeriod\FinancialYearService;
use Modules\Accounting\Services\FiscalPeriod\FiscalPeriodLifecycleService;
use Modules\Accounting\Services\FiscalPeriod\FiscalPeriodMaintenanceService;
use Modules\Accounting\Services\FiscalPeriod\FiscalPeriodStatusSync;
use Modules\General\Models\Setting;

class FinancialYearSettingsController extends Controller
{
    public function __construct(
        private readonly FinancialYearService $yearService,
        private readonly FiscalPeriodLifecycleService $lifecycle,
        private readonly FiscalPeriodMaintenanceService $periodMaintenance,
        private readonly FiscalPeriodAccountingCloseService $accountingClose,
    ) {}

    public function nextRange(): JsonResponse
    {
        try {
            [$start, $end] = $this->yearService->nextYearDateRange();

            return response()->json([
                'start_date' => $start,
                'end_date' => $end,
                'name' => app()->getLocale() === 'ar'
                    ? 'السنة المالية '.Carbon::parse($end)->year
                    : 'Fiscal year '.Carbon::parse($end)->year,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function index(): JsonResponse
    {
        FiscalPeriodStatusSync::promoteStartedPeriods();

        $years = FinancialYear::query()
            ->with('periods')
            ->orderBy('start_date')
            ->get();

        $current = $years->firstWhere('status', FinancialYear::STATUS_OPEN)
            ?? $years->last();

        return response()->json([
            'locking_enabled' => Setting::isFinancialPeriodLockingEnabled(),
            'first_saved' => $years->isNotEmpty(),
            'current_year_id' => $current?->id,
            'years' => $years->map(fn ($y) => FinancialYearApiPresenter::year($y))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['required_without:auto_next', 'date'],
            'end_date' => ['required_without:auto_next', 'date', 'after_or_equal:start_date'],
            'description' => ['nullable', 'string', 'max:191'],
            'status' => ['nullable', 'in:open,closed,closing'],
            'auto_next' => ['sometimes', 'boolean'],
        ]);

        try {
            $year = $this->yearService->create([
                'name' => $validated['description'] ?? null,
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'status' => $validated['status'] ?? FinancialYear::STATUS_OPEN,
            ], ! empty($validated['auto_next']));

            return response()->json([
                'message' => __('accounting::financial_year.save_success'),
                'year' => FinancialYearApiPresenter::year($year),
            ], 201);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $year = FinancialYear::query()->findOrFail($id);

        $validated = $request->validate([
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date'],
            'description' => ['nullable', 'string', 'max:191'],
            'status' => ['sometimes', 'in:open,closed,closing'],
        ]);

        if (isset($validated['start_date'], $validated['end_date'])
            && $validated['end_date'] < $validated['start_date']) {
            return response()->json([
                'message' => __('accounting::financial_year.validation_end_before_start'),
            ], 422);
        }

        try {
            $year = $this->yearService->update($year, [
                'name' => $validated['description'] ?? $year->name,
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'status' => $validated['status'] ?? null,
            ]);

            return response()->json([
                'message' => __('accounting::financial_year.year_updated_success'),
                'year' => FinancialYearApiPresenter::year($year),
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $year = FinancialYear::query()->findOrFail($id);

        try {
            $this->yearService->delete($year);

            return response()->json([
                'message' => __('accounting::financial_year.year_deleted_success'),
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function closeYear(Request $request, int $id): JsonResponse
    {
        $year = FinancialYear::query()->findOrFail($id);

        try {
            $force = $request->boolean('force_without_accounting_close');
            $year = $this->lifecycle->closeYear($year, $force);

            return response()->json([
                'message' => __('messages.add_successfully'),
                'year' => FinancialYearApiPresenter::year($year),
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function openYear(int $id): JsonResponse
    {
        $year = FinancialYear::query()->findOrFail($id);

        try {
            $year = $this->lifecycle->openYear($year);

            return response()->json([
                'message' => __('messages.add_successfully'),
                'year' => FinancialYearApiPresenter::year($year),
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function updatePeriod(Request $request, int $periodId): JsonResponse
    {
        $period = FiscalPeriod::query()->findOrFail($periodId);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:191'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date'],
            'status' => ['sometimes', 'in:open,closed,closing,upcoming'],
        ]);

        if (isset($validated['start_date'], $validated['end_date'])
            && $validated['end_date'] < $validated['start_date']) {
            return response()->json([
                'message' => __('accounting::financial_year.validation_end_before_start'),
            ], 422);
        }

        try {
            $period = $this->periodMaintenance->update($period, $validated);

            return response()->json([
                'message' => __('accounting::financial_year.period_updated_success'),
                'period' => FinancialYearApiPresenter::period($period),
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function destroyPeriod(int $periodId): JsonResponse
    {
        $period = FiscalPeriod::query()->findOrFail($periodId);

        try {
            $this->periodMaintenance->delete($period);

            return response()->json([
                'message' => __('accounting::financial_year.period_deleted_success'),
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function closePeriod(int $id): JsonResponse
    {
        $period = FiscalPeriod::query()->findOrFail($id);

        try {
            $period = $this->lifecycle->closePeriod($period);

            return response()->json([
                'message' => __('accounting::financial_year.period_closed_success'),
                'period' => FinancialYearApiPresenter::period($period),
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function openPeriod(int $id): JsonResponse
    {
        $period = FiscalPeriod::query()->findOrFail($id);

        try {
            $period = $this->lifecycle->openPeriod($period);

            return response()->json([
                'message' => __('accounting::financial_year.period_opened_success'),
                'period' => FinancialYearApiPresenter::period($period),
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    public function accountingCloseReadiness(Request $request, int $id): JsonResponse
    {
        $year = FinancialYear::query()->with('periods')->findOrFail($id);
        $period = $this->resolveClosingPeriod($request, $year);

        return response()->json(
            $this->accountingClose->readiness($year, $period)
        );
    }

    public function accountingClosePreview(Request $request, int $id): JsonResponse
    {
        $year = FinancialYear::query()->with('periods')->findOrFail($id);
        $period = $this->resolveClosingPeriod($request, $year);
        $payload = $this->accountingClose->preview($year, $period);

        if ($payload['preview'] === null) {
            return response()->json([
                'message' => __('accounting::fiscal_close.preview_not_available'),
                'readiness' => $payload['readiness'],
                'year' => $payload['year'],
            ], 422);
        }

        return response()->json($payload);
    }

    public function accountingCloseExecute(Request $request, int $id): JsonResponse
    {
        $year = FinancialYear::query()->with('periods')->findOrFail($id);
        $period = $this->resolveClosingPeriod($request, $year);

        try {
            $result = $this->accountingClose->execute($year, $period, (int) auth()->id());

            $message = match (true) {
                $result['already_posted'] ?? false => __('accounting::fiscal_close.execute_already_posted'),
                $result['repaired'] ?? false => __('accounting::fiscal_close.repair_success'),
                default => __('accounting::fiscal_close.execute_success'),
            };

            return response()->json([
                'message' => $message,
                ...$result,
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    private function resolveClosingPeriod(Request $request, FinancialYear $year): ?FiscalPeriod
    {
        $periodId = $request->query('period_id');

        if ($periodId === null || $periodId === '') {
            return null;
        }

        return $year->periods->firstWhere('id', (int) $periodId)
            ?? FiscalPeriod::query()
                ->where('financial_year_id', $year->id)
                ->where('id', (int) $periodId)
                ->firstOrFail();
    }

    public function updateLocking(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        Setting::updateOrCreate(
            ['key' => 'enable_financial_period_locking'],
            ['value' => $validated['enabled'] ? '1' : '0']
        );

        return response()->json([
            'locking_enabled' => Setting::isFinancialPeriodLockingEnabled(),
            'message' => __('messages.add_successfully'),
        ]);
    }

    private function errorResponse(\Throwable $e): JsonResponse
    {
        if ($e instanceof FiscalPeriodException || $e instanceof \InvalidArgumentException) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        report($e);

        return response()->json([
            'message' => $e->getMessage() ?: __('messages.something_went_wrong'),
        ], 500);
    }
}
