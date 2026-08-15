<?php

namespace Modules\Establishment\Http\Controllers;

use App\Http\Controllers\Controller;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Utils\PerpetualInventoryAccountResolver;
use Modules\Establishment\Classes\EstablishmentTable;
use Modules\Establishment\Http\Requests\StoreEstablishmentRequest;
use Modules\Establishment\Models\Establishment;
use Modules\Establishment\Models\EstPos;
use Modules\Establishment\Services\EstablishmentActions;
use Modules\Establishment\Services\EstablishmentInternalConsumptionTypeResolver;
use Modules\Establishment\Services\EstablishmentPaymentAccountResolver;
use Modules\Establishment\Services\EstablishmentServiceFeeResolver;
use Modules\General\Models\Setting;
use Modules\Product\Models\DiningType;

class EstablishmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $establishments = Establishment::select('id', 'name', 'name_en', 'address', 'is_main', 'parent_id', 'city', 'region', 'contact_details', 'is_active', 'deleted_at');
        if ($request->ajax()) {

            if ($request->has('deleted_records') && ! empty($request->deleted_records)) {
                $request->deleted_records == 'only_deleted_records'
                    ? $establishments->onlyTrashed()
                    : ($request->deleted_records == 'with_deleted_records' ? $establishments->withTrashed() : null);
            }
            if ($request->has('type') && $request->type === 'devices') {
                $devices = Estpos::with('establishment')->get();

                return EstablishmentTable::getDeviceTable($devices);
            }

            return EstablishmentTable::getEstablishmentTable($establishments);
        }
        $establishments = $this->getEstablishment();
        $columns = EstablishmentTable::getEstablishmentColumns();
        $deviceColumns = EstablishmentTable::getDeviceColumns();

        return view('establishment::establishment.index', compact('columns', 'establishments', 'deviceColumns'));
    }

    public function createLiveValidation(StoreEstablishmentRequest $request) {}

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $establishments = Establishment::where('is_main', true)->active()->get(['id', 'name', 'name_en']);
        $showPerpetualInventoryAccount = Setting::isPerpetualInventory();
        $perpetualInventoryAccounts = $showPerpetualInventoryAccount
            ? PerpetualInventoryAccountResolver::establishmentLinkableAssetAccounts()
            : collect();

        return view('establishment::establishment.create', compact(
            'establishments',
            'showPerpetualInventoryAccount',
            'perpetualInventoryAccounts'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEstablishmentRequest $request)
    {
        return DB::transaction(function () use ($request) {
            try {
                $filteredRequest = $request->safe()->collect()->filter(function ($item, $key) {
                    if (in_array($key, ['perpetual_inventory_account_id', 'internal_consumption_expense_account_id'], true)) {
                        return true;
                    }

                    return isset($item);
                });
                $storeEstablishment = new EstablishmentActions($filteredRequest);
                $storeEstablishment->store();

                return to_route('establishments.index')->with('success', __('employee::responses.created_successfully', ['name' => __('establishment::fields.establishment')]));
            } catch (\Throwable $e) {
                Log::error('Establishment creation failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return redirect()->back()->with('error', __('establishment::responses.something_wrong_happened'));
            }
        });
    }

    // this function return all categories with unspecified number of categories levels
    public function getLevels()
    {

        $establishments = Establishment::with('children')->whereNull('parent_id')->get();
        $allWithChildren = [];
        foreach ($establishments as $establishment) {
            $allWithChildren = array_merge($allWithChildren, [
                $establishment,
                ...$establishment->getAllDescendants()
                    ->whereNull('parent_id'),
            ]);
        }

        return $allWithChildren;
    }

    public function getEstablishment()
    {
        return Establishment::query()
            ->select('id', 'name', 'name_en', 'parent_id', 'is_main', 'is_active')
            ->whereNull('parent_id')
            ->with('childrenTree')
            ->get();
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id)
    {
        $establishment = Establishment::with(['children', 'paymentAccounts'])->findOrFail($id);
        $establishments = Establishment::where('is_main', true)->active()->whereNot('id', $establishment->id)->whereNotIn('id', $establishment->children->pluck('id'))->get(['id', 'name', 'name_en']);
        $showPerpetualInventoryAccount = Setting::isPerpetualInventory();
        $perpetualInventoryAccounts = $showPerpetualInventoryAccount
            ? PerpetualInventoryAccountResolver::establishmentLinkableAssetAccounts()
            : collect();
        $accounts = AccountingAccount::query()->orderBy('gl_code')->get();
        $cashierPaymentRows = EstablishmentPaymentAccountResolver::rowsForEstablishment((int) $establishment->id);
        $internalConsumptionRows = EstablishmentInternalConsumptionTypeResolver::rowsForEstablishment((int) $establishment->id);
        $diningTypes = DiningType::query()->orderBy('name_ar')->get(['id', 'name_ar', 'name_en']);
        try {
            $serviceFeeRows = EstablishmentServiceFeeResolver::rowsForEstablishment((int) $establishment->id);
        } catch (\Throwable $e) {
            $serviceFeeRows = [];
        }

        return view('establishment::establishment.edit', compact(
            'establishment',
            'establishments',
            'showPerpetualInventoryAccount',
            'perpetualInventoryAccounts',
            'accounts',
            'cashierPaymentRows',
            'internalConsumptionRows',
            'diningTypes',
            'serviceFeeRows'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreEstablishmentRequest $request, Establishment $establishment)
    {
        return DB::transaction(function () use ($request, $establishment) {
            try {
                $filteredRequest = $request->safe()->collect()->filter(function ($item, $key) {
                    if (in_array($key, [
                        'perpetual_inventory_account_id',
                        'cashier_payment_rows',
                        'internal_consumption_rows',
                        'service_fee_rows',
                    ], true)) {
                        return true;
                    }

                    return isset($item);
                })->except(['cashier_payment_rows', 'internal_consumption_rows', 'service_fee_rows']);

                $updateEstablishment = new EstablishmentActions($filteredRequest);
                $updateEstablishment->update($establishment);

                EstablishmentPaymentAccountResolver::syncForEstablishment(
                    (int) $establishment->id,
                    $request->input('cashier_payment_rows', [])
                );

                EstablishmentInternalConsumptionTypeResolver::syncForEstablishment(
                    (int) $establishment->id,
                    $request->input('internal_consumption_rows', [])
                );

                EstablishmentServiceFeeResolver::syncForEstablishment(
                    (int) $establishment->id,
                    $request->input('service_fee_rows', [])
                );

                return to_route('establishments.index')->with('success', __('establishment::responses.updated_successfully', ['name' => __('establishment::fields.establishment')]));
            } catch (\Throwable $e) {
                Log::error('Establishment updating failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return redirect()->back()->with('error', __('establishment::responses.something_wrong_happened'));
            }
        });
    }

    public function softDelete(Establishment $establishment)
    {
        $delete = $establishment->delete();
        if ($delete) {
            return response()->json(['message' => __('establishment::responses.deleted_successfully', ['name' => __('establishment::fields.establishment')])]);
        } else {
            return response()->json(['error' => __('establishment::responses.something_wrong_happened')], 500);
        }
    }

    public function forceDelete($id)
    {
        $delete = Establishment::where('id', $id)->forceDelete();
        if ($delete) {
            return response()->json(['message' => __('establishment::responses.deleted_successfully', ['name' => __('establishment::fields.establishment')])]);
        } else {
            return response()->json(['error' => __('establishment::responses.something_wrong_happened')], 500);
        }
    }

    public function restore($id)
    {
        $restore = Establishment::where('id', $id)->restore();
        if ($restore) {
            return response()->json(['message' => __('employee::responses.employee_restored_successfully')]);
        } else {
            return response()->json(['error' => __('employee::responses.something_wrong_happened')], 500);
        }
    }
}
