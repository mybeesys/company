<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Accounting\Exceptions\FiscalPeriodException;
use Modules\Accounting\Models\FinancialYear;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Accounting\Services\FiscalPeriod\FinancialYearApiPresenter;
use Modules\Accounting\Services\FiscalPeriod\FinancialYearService;
use Modules\Accounting\Services\FiscalPeriod\FiscalPeriodLifecycleService;
use Modules\Accounting\Services\FiscalPeriod\FiscalPeriodStatusSync;
use Modules\General\Models\Setting;

class FinancialYearSettingsController extends Controller
{
    public function __construct(
        private readonly FinancialYearService $yearService,
        private readonly FiscalPeriodLifecycleService $lifecycle
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
