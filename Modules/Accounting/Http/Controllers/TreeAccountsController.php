<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Accounting\classes\LedgerExport;
use Modules\Accounting\classes\TreeAccountsExcelImport;
use Modules\Accounting\classes\TreeAccountsTemplateExport;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Models\AccountingAccountTypes;
use Modules\Accounting\Models\AccountingCostCenter;
use Modules\Accounting\Support\AccountingOpeningBalanceScope;
use Modules\Accounting\Support\LedgerStatementPresenter;
use Modules\Accounting\Support\AccountingReportDateResolver;
use Modules\Accounting\Support\ImportedAccountTypeSync;
use Modules\Accounting\Services\ChartOfAccountsTreeBuilder;
use Modules\Accounting\Utils\AccountingUtil;
use Modules\Accounting\Utils\GlCodeRepairService;
use Modules\Accounting\Utils\ContractorsAccUtil;
use Modules\Accounting\Utils\E_commerceAccUtil;
use Modules\Accounting\Utils\GeneralTreeAccUtil;
use Modules\Accounting\Utils\RestaurantCafeAccUtil;
use Modules\Accounting\Utils\ServicesAccUtil;
use Mpdf\Mpdf;

class TreeAccountsController extends Controller
{
    /** @var list<string> */
    public const LEDGER_COLUMN_ORDER = [
        'ref_no',
        'operation_date',
        'narration',
        'transaction',
        'cost_center',
        'added_by',
        'debit',
        'credit',
        'balance',
    ];

    /**
     * Visible ledger table columns from ?ledger_cols= ref_no,operation_date,...
     * Default: all columns including "transaction" (يُعرض بجانب المرجع وليس عموداً منفصلاً في الجدول).
     * "balance" is always included.
     *
     * @return list<string>
     */
    protected function parseLedgerVisibleColumns(Request $request): array
    {
        $order = self::LEDGER_COLUMN_ORDER;
        $raw = $request->query('ledger_cols');
        if (is_string($raw) && $raw !== '') {
            $cols = array_values(array_filter(array_map('trim', explode(',', $raw))));
            $cols = array_values(array_intersect($order, $cols));
        } else {
            $cols = $order;
        }
        if (! in_array('balance', $cols, true)) {
            $cols[] = 'balance';
        }

        return array_values(array_intersect($order, $cols));
    }

    /** @return array{0: string, 1: string} */
    protected function ledgerDateRange(Request $request): array
    {
        [$start, $end] = AccountingReportDateResolver::range($request);

        if (! $request->filled('start_date') && ! $request->filled('end_date')) {
            return [$start, $end];
        }

        $end = $request->get('end_date', now()->addDay()->format('Y-m-d'));

        return [$request->get('start_date', $start), $end];
    }

    /** @return list<int|string> */
    protected function ledgerCostCenters(Request $request): array
    {
        return array_values((array) ($request->input('choose_cost_center_select', [])));
    }

    protected function ledgerIsDebitNature(AccountingAccount $account): bool
    {
        return in_array($account->account_primary_type, ['asset', 'expenses', 'analytical_accounts'], true);
    }

    /**
     * Opening balance before start_date (same cost-center scope as the ledger body).
     */
    protected function buildLedgerOpeningBalance(AccountingAccount $account, array $costCenterIds, string $startDate): float
    {
        $isDebitNature = $this->ledgerIsDebitNature($account);
        $openingQuery = AccountingAccountsTransaction::where('accounting_account_id', $account->id)
            ->when($costCenterIds, function ($query) use ($costCenterIds) {
                return $query->whereIn('cost_center_id', $costCenterIds);
            });

        AccountingOpeningBalanceScope::applyOpeningScope($openingQuery, $startDate);

        $openingTransactions = $openingQuery->get();
        $totalDebitOpening = $openingTransactions->where('type', 'debit')->sum('amount');
        $totalCreditOpening = $openingTransactions->where('type', 'credit')->sum('amount');

        if ($isDebitNature) {
            return (float) $totalDebitOpening - (float) $totalCreditOpening;
        }

        return (float) $totalCreditOpening - (float) $totalDebitOpening;
    }

    protected function buildLedgerTransactionsQuery(AccountingAccount $account, Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $costCenters = $this->ledgerCostCenters($request);
        [$start, $end] = $this->ledgerDateRange($request);

        return AccountingAccountsTransaction::with(['accTransMapping', 'createdBy', 'transaction', 'account', 'costCenter'])
            ->where('accounting_account_id', $account->id)
            ->whereBetween('operation_date', [$start, $end])
            ->tap(function ($query) use ($start) {
                AccountingOpeningBalanceScope::applyExcludeOpeningOnStartFromPeriod($query, $start);
            })
            ->when($costCenters, function ($query) use ($costCenters) {
                return $query->whereIn('cost_center_id', $costCenters);
            })
            ->when($request->filled('ref_no'), function ($query) use ($request) {
                $refNo = trim((string) $request->ref_no);

                return $query->where(function ($subQuery) use ($refNo) {
                    $subQuery->whereHas('accTransMapping', function ($q) use ($refNo) {
                        $q->where('ref_no', 'like', '%'.$refNo.'%');
                    })->orWhereHas('transaction', function ($q) use ($refNo) {
                        $q->where('ref_no', 'like', '%'.$refNo.'%');
                    });
                });
            })
            ->orderBy('operation_date')
            ->orderBy('id');
    }

    /**
     * Base query params for print / PDF / Excel (matches on-screen filters).
     *
     * @return array<string, mixed>
     */
    protected function ledgerExportBaseParams(Request $request, string $startDate, string $endDate): array
    {
        $params = [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
        if ($request->filled('ref_no')) {
            $params['ref_no'] = $request->ref_no;
        }
        $costCenters = $this->ledgerCostCenters($request);
        if ($costCenters !== []) {
            $params['choose_cost_center_select'] = $costCenters;
        }

        return $params;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // if(auth()->user()->hasDashboardPermission('')){
        //     return;
        // }
        // return  view('usermanagement::index');
        $account_types = AccountingAccountTypes::accounting_primary_type();
        $balance_formula = AccountingUtil::balanceFormula('AA');

        $accounts = AccountingAccount::whereNull('parent_account_id')
            ->with([
                'child_accounts' => function ($query) use ($balance_formula) {
                    $query->select([DB::raw("(SELECT $balance_formula from accounting_accounts_transactions AS AAT
                                    JOIN accounting_accounts AS AA ON AAT.accounting_account_id = AA.id
                                    WHERE AAT.accounting_account_id = accounting_accounts.id) AS balance"), 'accounting_accounts.*']);
                },
                'child_accounts.detail_type',
                'detail_type',
                'account_sub_type',
                'child_accounts.account_sub_type',
                'child_accounts.child_accounts' => function ($query) use ($balance_formula) {
                    $query->select([DB::raw("(SELECT $balance_formula from accounting_accounts_transactions AS AAT
                                    JOIN accounting_accounts AS AA ON AAT.accounting_account_id = AA.id
                                    WHERE AAT.accounting_account_id = accounting_accounts.id) AS balance"), 'accounting_accounts.*']);
                },
                'child_accounts.child_accounts.child_accounts' => function ($query) use ($balance_formula) {
                    $query->select([DB::raw("(SELECT $balance_formula from accounting_accounts_transactions AS AAT
                                    JOIN accounting_accounts AS AA ON AAT.accounting_account_id = AA.id
                                    WHERE AAT.accounting_account_id = accounting_accounts.id) AS balance"), 'accounting_accounts.*']);
                },
                'child_accounts.child_accounts.child_accounts.child_accounts' => function ($query) use ($balance_formula) {
                    $query->select([DB::raw("(SELECT $balance_formula from accounting_accounts_transactions AS AAT
                                    JOIN accounting_accounts AS AA ON AAT.accounting_account_id = AA.id
                                    WHERE AAT.accounting_account_id = accounting_accounts.id) AS balance"), 'accounting_accounts.*']);
                },
            ])
            ->select([
                DB::raw("(SELECT $balance_formula
                                FROM accounting_accounts_transactions AS AAT
                                JOIN accounting_accounts AS AA ON AAT.accounting_account_id = AA.id
                                WHERE AAT.accounting_account_id = accounting_accounts.id) AS balance"),
                'accounting_accounts.*',
            ])
            ->get();

        // $accounts = $query->get();
        $account_GLC = [];
        foreach ($account_types as $k => $v) {
            $account_types[$k] = $v['label'];
            $account_GLC[$k] = $v['GLC'];
        }

        $account_sub_types = AccountingAccountTypes::where('account_type', 'sub_type')->get();
        $account_exist = AccountingAccount::exists();
        $account_main_types = AccountingUtil::account_type();
        $account_category = AccountingUtil::account_category();
        $useImportedChartLayout = ChartOfAccountsTreeBuilder::usesImportedChartLayout();
        $imported_roots = $useImportedChartLayout
            ? ChartOfAccountsTreeBuilder::rootsByPrimaryType()
            : [];

        return view('accounting::treeOfAccounts.index', compact(
            'accounts',
            'account_category',
            'account_main_types',
            'account_exist',
            'account_types',
            'account_GLC',
            'account_sub_types',
            'useImportedChartLayout',
            'imported_roots',
        ));
    }

    public function importPage()
    {
        return view('accounting::treeOfAccounts.import');
    }

    public function downloadImportTemplate()
    {
        return Excel::download(new TreeAccountsTemplateExport, 'tree-of-accounts-template.xlsx');
    }

    public function importFromExcel(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
            'replace_existing' => ['nullable', 'boolean'],
        ]);

        $replaceExisting = $request->boolean('replace_existing');

        if ($replaceExisting) {
            if (AccountingAccountsTransaction::query()->exists()) {
                return redirect()->back()->with('error', __('accounting::lang.import_tree_accounts_replace_has_transactions'));
            }

            AccountingAccount::query()->delete();
        }

        $import = new TreeAccountsExcelImport;
        Excel::import($import, $validated['file']);
        $rows = $import->rows;

        if ($rows->isEmpty()) {
            return redirect()->back()->with('error', __('messages.no_data_found'));
        }

        $normalized = $rows->map(function ($row) {
            $gl = trim((string) ($row['gl_code'] ?? ''));
            $parentGl = trim((string) ($row['parent_gl_code'] ?? ''));

            return [
                'gl_code' => $gl,
                'name_ar' => trim((string) ($row['name_ar'] ?? '')),
                'name_en' => trim((string) ($row['name_en'] ?? '')),
                'account_primary_type' => trim((string) ($row['account_primary_type'] ?? '')),
                'parent_gl_code' => ($parentGl === '' ? null : $parentGl),
                'status' => trim((string) ($row['status'] ?? 'active')) ?: 'active',
            ];
        })->filter(fn ($r) => $r['gl_code'] !== '');

        if ($normalized->isEmpty()) {
            return redirect()->back()->with('error', __('messages.no_data_found'));
        }

        $fileDup = $normalized->groupBy('gl_code')->filter(fn ($g) => $g->count() > 1)->keys()->values();
        if ($fileDup->isNotEmpty()) {
            return redirect()->back()->with('error', __('accounting::lang.import_tree_accounts_duplicate_gl_code', [
                'codes' => $fileDup->implode(', '),
            ]));
        }

        $existing = AccountingAccount::query()
            ->whereIn('gl_code', $normalized->pluck('gl_code')->values())
            ->pluck('id', 'gl_code')
            ->all();
        if (! empty($existing)) {
            return redirect()->back()->with('error', __('accounting::lang.import_tree_accounts_gl_code_exists', [
                'codes' => implode(', ', array_keys($existing)),
            ]));
        }

        $sorted = $normalized->sortBy(fn ($r) => strlen((string) $r['gl_code']))->values();
        // Use prefixed keys so PHP doesn't cast numeric strings to ints.
        $createdMap = []; // "gl:111" => id

        DB::beginTransaction();
        try {
            $createdCount = 0;
            foreach ($sorted as $r) {
                $parentId = null;
                if (! empty($r['parent_gl_code'])) {
                    $parentKey = 'gl:'.(string) $r['parent_gl_code'];
                    $parentId = $createdMap[$parentKey] ?? AccountingAccount::query()
                        ->where('gl_code', $r['parent_gl_code'])
                        ->value('id');
                    if (! $parentId) {
                        throw new \RuntimeException(__('accounting::lang.import_tree_accounts_parent_not_found', [
                            'gl_code' => $r['gl_code'],
                            'parent_gl_code' => $r['parent_gl_code'],
                        ]));
                    }
                }

                $primaryType = trim((string) ($r['account_primary_type'] ?? ''));
                $accountType = in_array($primaryType, ['income', 'expenses'], true) ? $primaryType : 'normal';

                $acc = AccountingAccount::query()->create([
                    'gl_code' => $r['gl_code'],
                    'name_ar' => $r['name_ar'] ?: $r['gl_code'],
                    'name_en' => $r['name_en'] ?: $r['gl_code'],
                    'account_primary_type' => $primaryType ?: null,
                    'account_type' => $accountType,
                    'parent_account_id' => $parentId,
                    'status' => in_array($r['status'], ['active', 'inactive'], true) ? $r['status'] : 'active',
                ]);

                $createdMap['gl:'.(string) $r['gl_code']] = $acc->id;
                $createdCount++;
            }

            DB::commit();
            ImportedAccountTypeSync::syncFromPrimaryType();
            if ($createdCount <= 0) {
                return redirect()->back()->with('error', __('messages.no_data_found'));
            }

            return redirect()->route('tree-of-accounts')->with('success', __('accounting::lang.import_tree_accounts_success').' ('.$createdCount.')');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return redirect()->back()->with('error', config('app.debug') ? $e->getMessage() : __('messages.something_went_wrong'));
        }
    }

    public function repairGlCodes(Request $request)
    {
        try {
            DB::beginTransaction();
            $result = GlCodeRepairService::repairAll();
            DB::commit();

            return redirect()->route('tree-of-accounts')->with(
                'success',
                __('accounting::lang.repair_gl_codes_success', [
                    'sub_types' => $result['sub_types'],
                    'accounts' => $result['accounts'],
                ])
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return redirect()->back()->with(
                'error',
                config('app.debug') ? $e->getMessage() : __('messages.something_went_wrong')
            );
        }
    }

    public function createDefaultAccounts()
    {
        $company = DB::connection('mysql')->table('companies')->find(get_company_id());

        $business_type = $company->business_type ?? 'general';

        // $utils = [
        //     "contractors" => ContractorsAccUtil::class,
        //     "e-commerce" => E_commerceAccUtil::class,
        //     "restaurant-cafe" => RestaurantCafeAccUtil::class,
        //     "services" => ServicesAccUtil::class,
        //     "general" => GeneralTreeAccUtil::class,
        // ];

        // $utilClass = $utils[$business_type] ?? $utils['general'];

        // $default_accounting_account_types = $utilClass::default_accounting_account_types();
        $default_accounting_account_types = AccountingUtil::default_accounting_account_types();
        if (AccountingAccountTypes::count() === 0) {
            AccountingAccountTypes::insert($default_accounting_account_types);
        }

        $default_accounts = AccountingUtil::Default_Accounts();
        // $default_accounts = $utilClass::Default_Accounts();
        if (AccountingAccount::doesntExist()) {
            AccountingAccount::insert($default_accounts);
        }

        // $utilClass::default_accounting_route();
        AccountingUtil::default_accounting_route();

        // $default_accounting_account_types = AccountingUtil::default_accounting_account_types();
        // $accountingAccountType = AccountingAccountTypes::all();
        // if (count($accountingAccountType) == 0) {
        //     AccountingAccountTypes::insert($default_accounting_account_types);
        // }
        // $default_accounts = AccountingUtil::Default_Accounts();
        // if (AccountingAccount::doesntExist()) {
        //     AccountingAccount::insert($default_accounts);
        // }
        // AccountingUtil::default_accounting_route();

        // redirect back
        $output = [
            'success' => 1,
            'msg' => __('lang_v1.added_success'),
        ];

        return redirect()->back()->with('status', $output);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create($account_id)
    {
        $account = AccountingAccount::with(['account_sub_type', 'detail_type'])
            ->findorFail($account_id);

        $account_transactions = AccountingAccountsTransaction::with(['accTransMapping', 'transaction', 'createdBy'])
            ->where('accounting_account_id', $account->id)->get();

        return view('accounting::treeOfAccounts.ledger', compact('account', 'account_transactions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'account_id' => 'required|exists:accounting_accounts,id',
            'gl_code' => 'required|string|max:255|unique:accounting_accounts,gl_code',
        ]);

        DB::beginTransaction();

        try {
            $input = $request->only([
                'name_ar',
                'name_en',
                'account_id',
                'gl_code',
            ]);

            $parent = AccountingAccount::find($input['account_id']);

            $input['account_primary_type'] = $parent->account_primary_type;
            $input['account_sub_type_id'] = $parent->account_sub_type_id;
            $input['detail_type_id'] = $parent->detail_type_id;
            $input['parent_account_id'] = $parent->id;
            $input['account_type'] = $parent->account_type ?? $parent->account_primary_type;
            $input['created_by'] = Auth::user()->id;
            $input['status'] = 'active';
            $input['gl_code'] = trim((string) ($input['gl_code'] ?? ''));
            if ($input['gl_code'] === '') {
                $input['gl_code'] = AccountingUtil::next_GLC($parent->id);
            }

            AccountingAccount::create($input);

            DB::commit();

            return redirect()->back()->with('success', __('messages.add_successfully'));
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return redirect()->back()->with('error', __('messages.something_went_wrong'));
        }
    }

    public function storeSubAccount(Request $request)
    {
        // try {
        DB::beginTransaction();

        $input = $request->only([
            'name_ar',
            'name_en',
            'sub_account_id',
        ]);

        $account_sub_account = AccountingAccountTypes::find($input['sub_account_id']);

        $account = AccountingAccount::create([
            'name_en' => $input['name_ar'],
            'name_ar' => $input['name_ar'],
            'account_primary_type' => $account_sub_account->account_primary_type,
            'account_type' => $account_sub_account->account_primary_type,
            'account_sub_type_id' => $input['sub_account_id'],
            'detail_type_id' => null,
            'gl_code' => AccountingUtil::next_GLC_for_sub_type((int) $account_sub_account->id),
            'status' => 'active',
            'created_by' => Auth::user()->id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::commit();

        return redirect()->back();
        // } catch (\Exception $e) {
        //     DB::rollBack();
        //     return redirect()->back();
        // }

        return redirect()->back();
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('accounting::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('accounting::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {

        try {
            $request->validate([
                'account_id' => 'required|exists:accounting_accounts,id',
                'name_ar' => 'required|string|max:255',
                'name_en' => 'required|string|max:255',
                'gl_code' => 'required|string|max:255|unique:accounting_accounts,gl_code,'.$request->account_id,
            ]);
            DB::beginTransaction();
            $data = $request->only([
                'name_ar',
                'name_en',
                'gl_code',
            ]);

            $account = AccountingAccount::find($request->account_id);
            $account->update($data);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back();
        }

        return redirect()->back();
    }

    public function ledger(Request $request)
    {
        $account_id = $request->query('account_id') ?? optional(AccountingAccount::orderBy('id')->first())->id;
        $choose_cost_center_select = $this->ledgerCostCenters($request);
        [$start_date, $end_date] = $this->ledgerDateRange($request);

        $account = AccountingAccount::with(['account_sub_type', 'detail_type'])
            ->findOrFail($account_id);

        $account_type = $account->account_primary_type;
        $is_debit_nature = $this->ledgerIsDebitNature($account);

        $opening_balance = $this->buildLedgerOpeningBalance($account, $choose_cost_center_select, $start_date);

        $account_transactions = $this->buildLedgerTransactionsQuery($account, $request)->paginate(10);

        $current_bal = AccountingAccount::leftjoin(
            'accounting_accounts_transactions as AAT',
            'AAT.accounting_account_id',
            '=',
            'accounting_accounts.id'
        )
            ->where('accounting_accounts.id', $account->id)
            ->select([DB::raw(AccountingUtil::balanceFormula())])
            ->first()->balance;

        $previous = AccountingAccount::where('id', '<', $account_id)->orderBy('id', 'desc')->first();
        $next = AccountingAccount::where('id', '>', $account_id)->orderBy('id', 'asc')->first();
        $costCenters = AccountingCostCenter::where('is_main', 0)->get();
        // Include control/parent accounts (e.g. العملاء 12041) so AR linked to the parent is selectable.
        $accountingAccount = AccountingAccount::forDropdown('', true);
        $ledger_visible_columns = $this->parseLedgerVisibleColumns($request);
        $ledger_export_base_params = $this->ledgerExportBaseParams($request, $start_date, $end_date);

        return view('accounting::treeOfAccounts.ledger', compact(
            'account',
            'account_type',
            'is_debit_nature',
            'opening_balance',
            'start_date',
            'end_date',
            'choose_cost_center_select',
            'costCenters',
            'previous',
            'next',
            'accountingAccount',
            'current_bal',
            'account_transactions',
            'ledger_visible_columns',
            'ledger_export_base_params'
        ));
    }

    /** @return array<string, mixed> */
    protected function ledgerStatementViewData(Request $request, AccountingAccount $account, bool $isPdf = false): array
    {
        $choose_cost_center_select = $this->ledgerCostCenters($request);
        [$start_date, $end_date] = $this->ledgerDateRange($request);
        $is_debit_nature = $this->ledgerIsDebitNature($account);
        $opening_balance = $this->buildLedgerOpeningBalance($account, $choose_cost_center_select, $start_date);
        $account_transactions = $this->buildLedgerTransactionsQuery($account, $request)->get();

        $current_bal = AccountingAccount::leftjoin(
            'accounting_accounts_transactions as AAT',
            'AAT.accounting_account_id',
            '=',
            'accounting_accounts.id'
        )
            ->where('accounting_accounts.id', $account->id)
            ->select([DB::raw(AccountingUtil::balanceFormula())])
            ->first()
            ->balance;

        $company = DB::connection('mysql')->table('companies')->find(get_company_id());
        $localeAr = app()->getLocale() === 'ar';
        $ledger_visible_columns = $this->parseLedgerVisibleColumns($request);
        $showTransactionType = in_array('transaction', $ledger_visible_columns, true)
            || in_array('ref_no', $ledger_visible_columns, true);

        $lines = LedgerStatementPresenter::buildLines(
            $account_transactions,
            (float) $opening_balance,
            $is_debit_nature,
            $localeAr,
            $showTransactionType
        );

        $closing_balance = $lines !== []
            ? (float) end($lines)['balance_raw']
            : (float) $opening_balance;

        return [
            'company' => $company,
            'account' => $account,
            'current_bal' => $current_bal,
            'account_transactions' => $account_transactions,
            'opening_balance' => $opening_balance,
            'closing_balance' => $closing_balance,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'is_debit_nature' => $is_debit_nature,
            'is_pdf' => $isPdf,
            'locale_ar' => $localeAr,
            'currency' => LedgerStatementPresenter::defaultCurrency(),
            'company_name' => LedgerStatementPresenter::companyDisplayName($company, $localeAr),
            'company_address_lines' => LedgerStatementPresenter::companyAddressLines($company, $localeAr),
            'company_logo_path' => LedgerStatementPresenter::companyLogoPath($company),
            'company_logo_src' => LedgerStatementPresenter::companyLogoSrc($company, $isPdf),
            'statement_lines' => $lines,
            'account_class_label' => LedgerStatementPresenter::accountClassLabel($account, $localeAr),
            'printed_at' => now()->format('n/j/Y g:i A'),
        ];
    }

    public function ledgerPrint(Request $request, $id)
    {
        $account = AccountingAccount::with(['account_sub_type', 'detail_type'])
            ->findOrFail($id);

        return view('accounting::treeOfAccounts.print-ledger', $this->ledgerStatementViewData($request, $account));
    }

    public function ledgerExportPdf(Request $request, $id)
    {
        $account = AccountingAccount::with(['account_sub_type', 'detail_type'])
            ->findOrFail($id);

        $viewData = $this->ledgerStatementViewData($request, $account, true);
        $html = view('accounting::treeOfAccounts.print-ledger', $viewData)->render();

        $localeAr = $viewData['locale_ar'];
        $footerHtml = view('accounting::treeOfAccounts.partials.ledger-statement-footer', $viewData)->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'DejaVuSans',
            'default_font_size' => 9,
            'margin_top' => 10,
            'margin_bottom' => 20,
            'margin_left' => 12,
            'margin_right' => 12,
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
        ]);

        if ($localeAr) {
            $mpdf->SetDirectionality('rtl');
        } else {
            $mpdf->SetDirectionality('ltr');
        }

        $mpdf->SetHTMLFooter($footerHtml);
        $mpdf->WriteHTML($html);

        $accountLabel = $localeAr ? $account->name_ar : $account->name_en;
        $filename = __('accounting::lang.account_statement').' - '.$accountLabel.' ('.str_replace(['/', '\\'], ' - ', $account->gl_code).').pdf';

        return $mpdf->Output($filename, 'D');
    }

    public function ledgerExportExcel(Request $request, $id)
    {
        $account_id = $id;

        $account = AccountingAccount::with(['account_sub_type', 'detail_type'])
            ->findorFail($account_id);

        $ledger_visible_columns = $this->parseLedgerVisibleColumns($request);
        $choose_cost_center_select = $this->ledgerCostCenters($request);
        [$start_date, $end_date] = $this->ledgerDateRange($request);
        $is_debit_nature = $this->ledgerIsDebitNature($account);
        $opening_balance = $this->buildLedgerOpeningBalance($account, $choose_cost_center_select, $start_date);

        $account_transactions = $this->buildLedgerTransactionsQuery($account, $request)->get();

        $current_bal = AccountingAccount::leftjoin(
            'accounting_accounts_transactions as AAT',
            'AAT.accounting_account_id',
            '=',
            'accounting_accounts.id'
        )->where('accounting_accounts.id', $account->id)
            ->select([DB::raw(AccountingUtil::balanceFormula())]);
        $current_bal = $current_bal->first()->balance;

        $account['transactions'] = $account_transactions;
        $account['current_bal'] = $current_bal;
        $account['opening_balance'] = $opening_balance;
        $account['is_debit_nature'] = $is_debit_nature;

        $filename = __('accounting::lang.ledger').' '.(App::getLocale() == 'ar' ? $account->name_ar : $account->name_en).'- ('.str_replace(['/', '\\'], ' - ', $account->gl_code).')'.'.xlsx';

        return Excel::download(new LedgerExport($account, $ledger_visible_columns), $filename);
    }

    public function activateDeactivate(Request $request)
    {
        $request->validate([
            'account_id' => 'required|integer',
        ]);

        $account = AccountingAccount::find($request->account_id);
        if (! $account) {
            return redirect()->back()->with('error', __('accounting::lang.account_not_found'));
        }

        $account->status = $account->status === 'active' ? 'inactive' : 'active';
        $account->save();

        return redirect()->back()->with('success', __('messages.updated_successfully'));
    }

    public function nextGlCode(Request $request)
    {
        $request->validate([
            'parent_account_id' => 'required|exists:accounting_accounts,id',
        ]);

        $parentId = (int) $request->parent_account_id;

        return response()->json([
            'success' => true,
            'gl_code' => (string) AccountingUtil::next_GLC($parentId),
        ]);
    }

    public function deleteAccount(Request $request)
    {
        $request->validate([
            'account_id' => 'required|exists:accounting_accounts,id',
        ]);

        $account = AccountingAccount::withCount(['child_accounts'])->findOrFail($request->account_id);

        $hasMovements = AccountingAccountsTransaction::where('accounting_account_id', $account->id)->exists();
        if ($hasMovements) {
            return redirect()->back()->with('error', __('accounting::lang.cannot_delete_account_has_movements'));
        }

        if ((int) $account->child_accounts_count > 0) {
            return redirect()->back()->with('error', __('accounting::lang.cannot_delete_account_has_children'));
        }

        $account->delete();

        return redirect()->back()->with('success', __('messages.deleted_successfully'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function accountsDropdown()
    {
        //  AccountingAccount::forDropdown();
        if (request()->ajax()) {
            $q = request()->input('q', '');

            // Keep leaf-only for journal/client pickers; ledger page loads parents via forDropdown('', true).
            $accounts = AccountingAccount::forDropdown($q);
            $accounts_array = [];
            foreach ($accounts as $account) {
                if (app()->getLocale() == 'ar') {
                    $text = $account->name_ar.' - <small class="text-muted">'.__('accounting::lang.'.$account->account_primary_type).'</small>';
                    $html = $account->name_ar.' - <small class="text-muted">'.__('accounting::lang.'.$account->account_primary_type).'</small>';
                } else {
                    $text = $account->name_en.' - <small class="text-muted">'.__('accounting::lang.'.$account->account_primary_type).'</small>';
                    $html = $account->name_en.' - <small class="text-muted">'.__('accounting::lang.'.$account->account_primary_type).'</small>';
                }

                $accounts_array[] = [
                    'id' => $account->id,
                    'text' => $text,
                    'html' => $html,
                ];
            }
        }

        return $accounts_array;
    }
}
