<?php

namespace Modules\Establishment\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Establishment\Models\Establishment;
use Modules\Establishment\Services\EstablishmentInternalConsumptionTypeResolver;
use Modules\Establishment\Services\EstablishmentPaymentAccountResolver;
use Modules\Establishment\Services\EstablishmentServiceFeeResolver;
use Modules\Product\Models\DiningType;

class CashierCatalogSettingsController extends Controller
{
    public function paymentMethods()
    {
        $rows = EstablishmentPaymentAccountResolver::catalogRows();
        if ($rows === []) {
            $rows = EstablishmentPaymentAccountResolver::defaultCatalogRows();
            $allBranchIds = $this->branchOptions()->pluck('id')->all();
            $rows = array_map(function (array $row) use ($allBranchIds) {
                $row['establishment_ids'] = $allBranchIds;

                return $row;
            }, $rows);
        }

        return view('establishment::settings.payment-methods', [
            'accounts' => $this->accounts(),
            'cashierPaymentRows' => $rows,
            'branchOptions' => $this->branchOptions(),
        ]);
    }

    public function updatePaymentMethods(Request $request)
    {
        $this->normalizePaymentMethodFeeFlags($request);

        $request->validate([
            'cashier_payment_rows'                           => ['nullable', 'array'],
            'cashier_payment_rows.*.id'                      => ['nullable', 'integer'],
            'cashier_payment_rows.*.name_ar'                 => ['nullable', 'string', 'max:255'],
            'cashier_payment_rows.*.name_en'                 => ['nullable', 'string', 'max:255'],
            'cashier_payment_rows.*.account_id'              => ['nullable', 'integer', 'exists:accounting_accounts,id'],
            'cashier_payment_rows.*.payment_method_key'      => ['nullable', 'string', 'max:100'],
            'cashier_payment_rows.*.establishment_ids'       => ['nullable', 'array'],
            'cashier_payment_rows.*.establishment_ids.*'     => ['integer', 'exists:est_establishments,id'],
            'cashier_payment_rows.*.branch_accounts'         => ['nullable', 'array'],
            'cashier_payment_rows.*.branch_accounts.*'       => ['nullable', 'integer', 'exists:accounting_accounts,id'],
            // رسوم طريقة الدفع
            'cashier_payment_rows.*.fees'                    => ['nullable', 'array'],
            'cashier_payment_rows.*.fees.*.id'               => ['nullable', 'integer'],
            'cashier_payment_rows.*.fees.*.name_ar'          => ['nullable', 'string', 'max:255'],
            'cashier_payment_rows.*.fees.*.name_en'          => ['nullable', 'string', 'max:255'],
            'cashier_payment_rows.*.fees.*.fee_type'         => ['nullable', 'in:0,1'],
            'cashier_payment_rows.*.fees.*.application_type' => ['nullable', 'in:0,1'],
            'cashier_payment_rows.*.fees.*.amount'           => ['nullable', 'numeric', 'min:0'],
            'cashier_payment_rows.*.fees.*.is_active'        => ['nullable', 'boolean'],
        ]);

        $this->assertBranchAccounts($request->input('cashier_payment_rows', []));

        return $this->persist(function () use ($request) {
            EstablishmentPaymentAccountResolver::syncCatalog($request->input('cashier_payment_rows', []));

            return to_route('cashier-settings.payment-methods')
                ->with('success', __('establishment::responses.updated_successfully', [
                    'name' => __('establishment::general.cashier_payment_methods'),
                ]));
        });
    }

    public function internalConsumption()
    {
        return view('establishment::settings.internal-consumption', [
            'accounts' => $this->accounts(),
            'internalConsumptionRows' => EstablishmentInternalConsumptionTypeResolver::catalogRows(),
            'branchOptions' => $this->branchOptions(),
        ]);
    }

    public function updateInternalConsumption(Request $request)
    {
        $request->validate([
            'internal_consumption_rows' => ['nullable', 'array'],
            'internal_consumption_rows.*.id' => ['nullable', 'integer'],
            'internal_consumption_rows.*.name_ar' => ['nullable', 'string', 'max:255'],
            'internal_consumption_rows.*.name_en' => ['nullable', 'string', 'max:255'],
            'internal_consumption_rows.*.value_type' => ['nullable', 'in:cost,percent,fixed'],
            'internal_consumption_rows.*.value' => ['nullable', 'numeric', 'min:0'],
            'internal_consumption_rows.*.account_id' => ['nullable', 'integer', 'exists:accounting_accounts,id'],
            'internal_consumption_rows.*.is_active' => ['nullable', 'boolean'],
            'internal_consumption_rows.*.establishment_ids' => ['nullable', 'array'],
            'internal_consumption_rows.*.establishment_ids.*' => ['integer', 'exists:est_establishments,id'],
        ]);

        return $this->persist(function () use ($request) {
            EstablishmentInternalConsumptionTypeResolver::syncCatalog($request->input('internal_consumption_rows', []));

            return to_route('cashier-settings.internal-consumption')
                ->with('success', __('establishment::responses.updated_successfully', [
                    'name' => __('establishment::general.internal_consumption_settings'),
                ]));
        });
    }

    public function serviceFees()
    {
        return view('establishment::settings.service-fees', [
            'serviceFeeRows' => EstablishmentServiceFeeResolver::catalogRows(),
            'diningTypes' => DiningType::query()->orderBy('name_ar')->get(['id', 'name_ar', 'name_en']),
            'cashierPaymentRows' => EstablishmentPaymentAccountResolver::catalogRows(),
            'branchOptions' => $this->branchOptions(),
        ]);
    }

    public function updateServiceFees(Request $request)
    {
        $request->validate([
            'service_fee_rows' => ['nullable', 'array'],
            'service_fee_rows.*.id' => ['nullable', 'integer'],
            'service_fee_rows.*.name_ar' => ['nullable', 'string', 'max:255'],
            'service_fee_rows.*.name_en' => ['nullable', 'string', 'max:255'],
            'service_fee_rows.*.service_fee_type' => ['nullable', 'in:0,1'],
            'service_fee_rows.*.amount' => ['nullable', 'numeric', 'min:0'],
            'service_fee_rows.*.application_type' => ['nullable', 'in:0,1'],
            'service_fee_rows.*.calculation_method' => ['nullable', 'in:0,1'],
            'service_fee_rows.*.taxable' => ['nullable', 'boolean'],
            'service_fee_rows.*.active' => ['nullable', 'boolean'],
            'service_fee_rows.*.auto_apply_type' => ['nullable', 'in:0,1,2,3'],
            'service_fee_rows.*.establishment_ids' => ['nullable', 'array'],
            'service_fee_rows.*.establishment_ids.*' => ['integer', 'exists:est_establishments,id'],
        ]);

        return $this->persist(function () use ($request) {
            EstablishmentServiceFeeResolver::syncCatalog($request->input('service_fee_rows', []));

            return to_route('cashier-settings.service-fees')
                ->with('success', __('establishment::responses.updated_successfully', [
                    'name' => __('establishment::general.service_fee_settings'),
                ]));
        });
    }

    /**
     * @param  mixed  $rows
     */
    private function assertBranchAccounts(mixed $rows): void
    {
        if (! is_array($rows)) {
            return;
        }

        $errors = [];
        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $nameAr = trim((string) ($row['name_ar'] ?? ''));
            $nameEn = trim((string) ($row['name_en'] ?? ''));
            if ($nameAr === '' || $nameEn === '') {
                continue;
            }

            $assignedIds = array_values(array_unique(array_filter(array_map('intval', (array) ($row['establishment_ids'] ?? [])))));
            $branchAccounts = is_array($row['branch_accounts'] ?? null) ? $row['branch_accounts'] : [];

            foreach ($assignedIds as $establishmentId) {
                $accountId = (int) ($branchAccounts[$establishmentId] ?? $branchAccounts[(string) $establishmentId] ?? 0);
                if ($accountId <= 0) {
                    $errors['cashier_payment_rows.'.$index.'.branch_accounts.'.$establishmentId] =
                        __('establishment::responses.cashier_payment_branch_account_required');
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function normalizePaymentMethodFeeFlags(Request $request): void
    {
        $rows = $request->input('cashier_payment_rows', []);
        if (! is_array($rows)) {
            return;
        }

        foreach ($rows as $index => $row) {
            if (! is_array($row) || ! is_array($row['fees'] ?? null)) {
                continue;
            }

            foreach ($row['fees'] as $feeIndex => $fee) {
                if (! is_array($fee)) {
                    continue;
                }

                $active = $fee['is_active'] ?? true;
                if (is_array($active)) {
                    $active = end($active);
                }

                $rows[$index]['fees'][$feeIndex]['is_active'] = filter_var($active, FILTER_VALIDATE_BOOL) ? '1' : '0';
            }
        }

        $request->merge(['cashier_payment_rows' => $rows]);
    }

    private function persist(callable $callback)
    {
        try {
            return DB::transaction(function () use ($callback) {
                return $callback();
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Cashier catalog settings update failed', [
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);

            return redirect()->back()->withInput()->with('error', $this->readablePersistError($e));
        }
    }

    private function readablePersistError(\Throwable $e): string
    {
        $message = $e->getMessage();
        $sqlState = (string) ($e->getCode() ?: '');
        if ($e instanceof \Illuminate\Database\QueryException) {
            $sqlState = (string) ($e->errorInfo[1] ?? $e->errorInfo[0] ?? $sqlState);
        }

        if (str_contains($message, 'est_payment_method_fees') || str_contains($message, "1146")) {
            return __('establishment::responses.cashier_payment_fees_table_missing');
        }

        if (in_array($sqlState, ['1062', '23000'], true) || str_contains($message, 'Duplicate') || str_contains($message, 'UNIQUE')) {
            return __('establishment::responses.cashier_payment_duplicate_method');
        }

        if (in_array($sqlState, ['1451', '1452'], true) || str_contains($message, 'Integrity constraint')) {
            return __('establishment::responses.cashier_payment_constraint_failed');
        }

        $trimmed = trim($message);

        return $trimmed !== ''
            ? __('establishment::responses.something_wrong_happened').' — '.$trimmed
            : __('establishment::responses.something_wrong_happened');
    }

    private function accounts()
    {
        return AccountingAccount::query()->orderBy('gl_code')->get();
    }

    private function branchOptions()
    {
        return Establishment::query()
            ->notMain()
            ->orderBy('name')
            ->get(['id', 'name', 'name_en']);
    }
}
