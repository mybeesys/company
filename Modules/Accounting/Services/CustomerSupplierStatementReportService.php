<?php

declare(strict_types=1);

namespace Modules\Accounting\Services;

use App\Helpers\CurrencyHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Modules\Accounting\Utils\AccountingUtil;
use Modules\ClientsAndSuppliers\Models\Contact;
use Modules\Establishment\Models\Establishment;

final class CustomerSupplierStatementReportService
{
  /** @var array<string, string> */
  public const CHART_COLORS = [
    '#1B84FF',
    '#17C653',
    '#F6C000',
    '#F8285A',
  ];

  /** @var array<string, array<int, string>> */
  public const CATEGORY_SUB_TYPES = [
    'invoice' => ['sell', 'sell_cash', 'sales_revenue', 'purchases'],
    'payment' => ['receipt_voucher', 'payment_voucher'],
    'return' => ['sell-return', 'sell_return', 'purchases-return', 'purchase_return'],
    'voucher' => ['receipt_voucher', 'payment_voucher'],
    'adjustment' => ['journal_entry', 'expense', 'expense_refund', 'manual_journal', 'opening_balance'],
  ];

  /** @return array<string, mixed> */
  public static function dataset(Request $request): array
  {
    $contactId = (int) ($request->input('id') ?? Contact::query()->value('id'));
    $contact = Contact::findOrFail($contactId);
    $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
    $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());
    $costCenterIds = array_values(array_filter((array) $request->input('choose_cost_center_select', [])));
    $establishmentIds = array_values(array_filter((array) $request->input('establishment_ids', [])));
    $userId = $request->input('created_by') ? (int) $request->input('created_by') : null;
    $entryType = $request->input('entry_type') ?: $request->input('balance_side');
    $subType = $request->input('sub_type');
    $refNo = $request->input('ref_no');
    $unsettledOnly = filter_var($request->input('unsettled_only', false), FILTER_VALIDATE_BOOLEAN);
    $compareMode = $request->input('compare_mode', 'none');
    $periodGroup = $request->input('period_group', 'month');

    $rows = static::fetchMovements(
      $contactId,
      $startDate,
      $endDate,
      $costCenterIds,
      $establishmentIds,
      $userId,
      $entryType,
      $subType,
      $refNo,
      $unsettledOnly
    );

    $openingBalance = static::contactBalanceBefore($contactId, $startDate, $costCenterIds, $establishmentIds);
    $lines = static::buildStatementLines($rows, $openingBalance);
    $currentBalance = static::contactBalanceAllTime($contactId, $costCenterIds, $establishmentIds);
    $closingBalance = $openingBalance + static::periodNetMovement($rows);

    $categoryTotals = static::summarizeByCategory($rows);
    $periodDebit = (float) $rows->where('type', 'debit')->sum('amount');
    $periodCredit = (float) $rows->where('type', 'credit')->sum('amount');

    $kpis = static::buildKpis(
      $currentBalance,
      $categoryTotals,
      $periodDebit,
      $periodCredit,
      $rows->count(),
      $closingBalance
    );

    $chart = static::buildCompositionChart($categoryTotals, $closingBalance);
    $balanceTrend = static::monthlyBalanceTrend($contactId, $startDate, $endDate, $costCenterIds, $establishmentIds, $periodGroup);
    $barChart = static::buildInvoicePaymentBar($rows, $periodGroup);
    $statementSummary = static::buildStatementSummary(
      $openingBalance,
      $categoryTotals,
      $currentBalance,
      $closingBalance
    );

    $aging = static::buildContactAging($contactId, $endDate);
    $analytics = static::buildAnalytics($rows, $periodDebit, $periodCredit, $openingBalance, $closingBalance, $aging);

    $comparePeriod = null;
    $compareAnalytics = null;
    if (in_array($compareMode, ['previous_period', 'previous_year'], true)) {
      $comparePeriod = static::resolveComparePeriod($startDate, $endDate, $compareMode);
      $compareRows = static::fetchMovements(
        $contactId,
        $comparePeriod['start_date'],
        $comparePeriod['end_date'],
        $costCenterIds,
        $establishmentIds,
        $userId,
        $entryType,
        $subType,
        $refNo,
        $unsettledOnly
      );
      $compareOpening = static::contactBalanceBefore(
        $contactId,
        $comparePeriod['start_date'],
        $costCenterIds,
        $establishmentIds
      );
      $compareClosing = $compareOpening + static::periodNetMovement($compareRows);
      $compareAnalytics = [
        'period_debit' => (float) $compareRows->where('type', 'debit')->sum('amount'),
        'period_credit' => (float) $compareRows->where('type', 'credit')->sum('amount'),
        'closing_balance' => $compareClosing,
        'growth_percent' => CurrencyHelper::growth_percent($closingBalance, $compareClosing),
      ];
    }

    $availableSubTypes = static::availableSubTypes($contactId, $startDate, $endDate);
    $users = static::usersForContact($contactId, $startDate, $endDate);
    $establishments = Establishment::query()->orderBy('name')->get(['id', 'name']);

    return compact(
      'contact',
      'contactId',
      'startDate',
      'endDate',
      'costCenterIds',
      'establishmentIds',
      'userId',
      'entryType',
      'subType',
      'refNo',
      'unsettledOnly',
      'compareMode',
      'periodGroup',
      'lines',
      'openingBalance',
      'closingBalance',
      'currentBalance',
      'categoryTotals',
      'periodDebit',
      'periodCredit',
      'kpis',
      'chart',
      'balanceTrend',
      'barChart',
      'statementSummary',
      'aging',
      'analytics',
      'comparePeriod',
      'compareAnalytics',
      'availableSubTypes',
      'users',
      'establishments'
    );
  }

  public static function resolveCategory(?string $subType): string
  {
    if (! $subType) {
      return 'other';
    }

    foreach (['return', 'invoice', 'payment', 'adjustment'] as $category) {
      if (in_array($subType, static::CATEGORY_SUB_TYPES[$category], true)) {
        return $category;
      }
    }

    if (str_contains($subType, 'return')) {
      return 'return';
    }

    return 'other';
  }

  /** @return Collection<int, object> */
  public static function fetchMovements(
    int $contactId,
    string $startDate,
    string $endDate,
    array $costCenterIds = [],
    array $establishmentIds = [],
    ?int $userId = null,
    ?string $entryType = null,
    ?string $subType = null,
    ?string $refNo = null,
    bool $unsettledOnly = false
  ): Collection {
    return static::baseQuery($contactId, $costCenterIds, $establishmentIds, $userId, $entryType, $subType, $refNo, $unsettledOnly)
      ->whereDate('aat.operation_date', '>=', $startDate)
      ->whereDate('aat.operation_date', '<=', $endDate)
      ->orderBy('aat.operation_date')
      ->orderBy('aat.id')
      ->get();
  }

  private static function baseQuery(
    int $contactId,
    array $costCenterIds,
    array $establishmentIds,
    ?int $userId,
    ?string $entryType,
    ?string $subType,
    ?string $refNo,
    bool $unsettledOnly
  ) {
    return Contact::query()
      ->where('cs_contacts.id', $contactId)
      ->whereNotNull('cs_contacts.account_id')
      ->join('transactions as t', 'cs_contacts.id', '=', 't.contact_id')
      ->join('accounting_accounts_transactions as aat', 't.id', '=', 'aat.transaction_id')
      ->whereColumn('aat.accounting_account_id', 'cs_contacts.account_id')
      ->leftJoin('accounting_acc_trans_mappings as atm', 'aat.acc_trans_mapping_id', '=', 'atm.id')
      ->leftJoin('transaction_payments as tp', 'aat.transaction_payment_id', '=', 'tp.id')
      ->leftJoin('emp_employees as u', 'aat.created_by', '=', 'u.id')
      ->leftJoin('accounting_cost_centers as cc', 'aat.cost_center_id', '=', 'cc.id')
      ->leftJoin('est_establishments as est', 't.establishment_id', '=', 'est.id')
      ->when(! empty($costCenterIds), fn ($q) => $q->whereIn('aat.cost_center_id', $costCenterIds))
      ->when(! empty($establishmentIds), fn ($q) => $q->whereIn('t.establishment_id', $establishmentIds))
      ->when($userId, fn ($q) => $q->where('aat.created_by', $userId))
      ->when(! empty($entryType), fn ($q) => $q->where('aat.type', $entryType))
      ->when(! empty($subType), fn ($q) => $q->where('aat.sub_type', $subType))
      ->when($unsettledOnly, fn ($q) => $q->whereIn('t.payment_status', ['due', 'partial']))
      ->when(! empty($refNo), function ($query) use ($refNo) {
        $query->where(function ($q) use ($refNo) {
          $q->where('atm.ref_no', 'like', '%'.$refNo.'%')
            ->orWhere('t.ref_no', 'like', '%'.$refNo.'%')
            ->orWhere('tp.payment_ref_no', 'like', '%'.$refNo.'%');
        });
      })
      ->select(
        'aat.id',
        'aat.operation_date',
        'aat.sub_type',
        'aat.type',
        'aat.cost_center_id',
        'aat.acc_trans_mapping_id',
        'aat.transaction_id',
        'atm.ref_no as atm_ref_no',
        'tp.payment_ref_no',
        'atm.id as atm_id',
        'cc.name_ar as cost_center_name_ar',
        'cc.name_en as cost_center_name_en',
        'atm.note',
        'aat.amount',
        'u.name as added_by',
        't.ref_no as invoice_no',
        't.establishment_id',
        DB::raw("CASE WHEN '".app()->getLocale()."' = 'ar' THEN est.name ELSE COALESCE(est.name_en, est.name) END as establishment_name"),
        't.payment_status',
        't.final_total',
        't.tax_amount'
      );
  }

  /** @param  Collection<int, object>  $rows */
  private static function periodNetMovement(Collection $rows): float
  {
    $debit = (float) $rows->where('type', 'debit')->sum('amount');
    $credit = (float) $rows->where('type', 'credit')->sum('amount');

    return $debit - $credit;
  }

  public static function contactBalanceBefore(
    int $contactId,
    string $beforeDate,
    array $costCenterIds = [],
    array $establishmentIds = []
  ): float {
    $util = new AccountingUtil;

    $query = Contact::where('cs_contacts.id', $contactId)
      ->whereNotNull('cs_contacts.account_id')
      ->join('transactions as t', 'cs_contacts.id', '=', 't.contact_id')
      ->join('accounting_accounts_transactions as AAT', 't.id', '=', 'AAT.transaction_id')
      ->whereColumn('AAT.accounting_account_id', 'cs_contacts.account_id')
      ->leftJoin('accounting_accounts as accounting_accounts', 'AAT.accounting_account_id', '=', 'accounting_accounts.id')
      ->whereDate('AAT.operation_date', '<', $beforeDate)
      ->when(! empty($costCenterIds), fn ($q) => $q->whereIn('AAT.cost_center_id', $costCenterIds))
      ->when(! empty($establishmentIds), fn ($q) => $q->whereIn('t.establishment_id', $establishmentIds))
      ->select([DB::raw($util->balanceFormula())]);

    return (float) ($query->first()?->balance ?? 0);
  }

  public static function contactBalanceAllTime(
    int $contactId,
    array $costCenterIds = [],
    array $establishmentIds = []
  ): float {
    $util = new AccountingUtil;

    $query = Contact::where('cs_contacts.id', $contactId)
      ->whereNotNull('cs_contacts.account_id')
      ->join('transactions as t', 'cs_contacts.id', '=', 't.contact_id')
      ->join('accounting_accounts_transactions as AAT', 't.id', '=', 'AAT.transaction_id')
      ->whereColumn('AAT.accounting_account_id', 'cs_contacts.account_id')
      ->leftJoin('accounting_accounts as accounting_accounts', 'AAT.accounting_account_id', '=', 'accounting_accounts.id')
      ->when(! empty($costCenterIds), fn ($q) => $q->whereIn('AAT.cost_center_id', $costCenterIds))
      ->when(! empty($establishmentIds), fn ($q) => $q->whereIn('t.establishment_id', $establishmentIds))
      ->select([DB::raw($util->balanceFormula())]);

    return (float) ($query->first()?->balance ?? 0);
  }

  /**
   * @param  Collection<int, object>  $rows
   * @return array<int, array<string, mixed>>
   */
  private static function buildStatementLines(Collection $rows, float $openingBalance): array
  {
    $running = $openingBalance;
    $lines = [];

    $openingLine = [
      'row_type' => 'opening',
      'operation_date' => null,
      'ref_no' => '—',
      'transaction_type' => __('accounting::lang.css_opening_balance'),
      'category' => 'opening',
      'description' => __('accounting::lang.css_opening_balance'),
      'establishment_name' => '—',
      'cost_center' => '—',
      'debit' => 0.0,
      'credit' => 0.0,
      'running_balance' => $openingBalance,
      'added_by' => '—',
      'detail_url' => null,
      'group_key' => 'opening',
      'is_important' => true,
      'tax_amount' => null,
    ];
    $lines[] = $openingLine;

    foreach ($rows as $row) {
      $debit = $row->type === 'debit' ? (float) $row->amount : 0.0;
      $credit = $row->type === 'credit' ? (float) $row->amount : 0.0;
      $running += $debit - $credit;

      $ref = $row->atm_ref_no ?: ($row->invoice_no ?: ($row->payment_ref_no ?: '—'));
      $subTypeLabel = Lang::has('accounting::lang.'.$row->sub_type)
        ? __('accounting::lang.'.$row->sub_type)
        : (string) $row->sub_type;
      $costCenter = app()->getLocale() === 'ar'
        ? ($row->cost_center_name_ar ?? $row->cost_center_name_en ?? '—')
        : ($row->cost_center_name_en ?? $row->cost_center_name_ar ?? '—');

      $detailUrl = ! empty($row->atm_id)
        ? action('\Modules\Accounting\Http\Controllers\JournalEntryController@print', [$row->atm_id])
        : null;

      $category = static::resolveCategory($row->sub_type);

      $lines[] = [
        'row_type' => 'movement',
        'id' => $row->id,
        'operation_date' => $row->operation_date,
        'ref_no' => $ref,
        'transaction_type' => $subTypeLabel,
        'category' => $category,
        'description' => $row->note ?: $subTypeLabel,
        'establishment_name' => $row->establishment_name ?? '—',
        'cost_center' => $costCenter,
        'debit' => $debit,
        'credit' => $credit,
        'running_balance' => $running,
        'added_by' => $row->added_by ?? '—',
        'detail_url' => $detailUrl,
        'group_key' => (string) ($row->atm_id ?: ('t-'.$row->transaction_id)),
        'is_important' => in_array($category, ['invoice', 'payment', 'return'], true)
          || in_array($row->payment_status ?? '', ['due', 'partial'], true),
        'tax_amount' => isset($row->tax_amount) ? (float) $row->tax_amount : null,
        'payment_status' => $row->payment_status ?? null,
      ];
    }

    return $lines;
  }

  /**
   * @param  Collection<int, object>  $rows
   * @return array<string, float>
   */
  private static function summarizeByCategory(Collection $rows): array
  {
    $totals = [
      'invoice' => 0.0,
      'payment' => 0.0,
      'return' => 0.0,
      'voucher' => 0.0,
      'adjustment' => 0.0,
      'other' => 0.0,
    ];

    foreach ($rows as $row) {
      $category = static::resolveCategory($row->sub_type);
      if (in_array($category, ['payment', 'voucher'], true)) {
        $totals['payment'] += (float) $row->amount;
      } elseif (isset($totals[$category])) {
        $totals[$category] += (float) $row->amount;
      } else {
        $totals['other'] += (float) $row->amount;
      }
    }

    return $totals;
  }

  /**
   * @param  array<string, float>  $categoryTotals
   * @return array<string, mixed>
   */
  private static function buildKpis(
    float $currentBalance,
    array $categoryTotals,
    float $periodDebit,
    float $periodCredit,
    int $transactionCount,
    float $closingBalance
  ): array {
    $invoices = $categoryTotals['invoice'] ?? 0.0;
    $payments = ($categoryTotals['payment'] ?? 0.0) + ($categoryTotals['voucher'] ?? 0.0);
    $returns = $categoryTotals['return'] ?? 0.0;
    $amountDue = max(0, $closingBalance);
    $amountPaid = $payments;

    return [
      'current_balance' => $currentBalance,
      'closing_balance' => $closingBalance,
      'total_invoices' => $invoices,
      'total_payments' => $payments,
      'total_returns' => $returns,
      'transaction_count' => $transactionCount,
      'amount_due' => $amountDue,
      'amount_paid' => $amountPaid,
      'period_debit' => $periodDebit,
      'period_credit' => $periodCredit,
    ];
  }

  /**
   * @param  array<string, float>  $categoryTotals
   * @return array{labels: array<int, string>, series: array<int, float>, colors: array<int, string>}
   */
  private static function buildCompositionChart(array $categoryTotals, float $closingBalance): array
  {
    $invoices = max(0, $categoryTotals['invoice'] ?? 0);
    $payments = max(0, ($categoryTotals['payment'] ?? 0) + ($categoryTotals['voucher'] ?? 0));
    $returns = max(0, $categoryTotals['return'] ?? 0);
    $outstanding = max(0, $closingBalance);

    return [
      'labels' => [
        __('accounting::lang.css_cat_invoices'),
        __('accounting::lang.css_cat_payments'),
        __('accounting::lang.css_cat_returns'),
        __('accounting::lang.css_cat_outstanding'),
      ],
      'series' => [$invoices, $payments, $returns, $outstanding],
      'colors' => static::CHART_COLORS,
    ];
  }

  /**
   * @return array{labels: array<int, string>, balances: array<int, float>}
   */
  private static function monthlyBalanceTrend(
    int $contactId,
    string $startDate,
    string $endDate,
    array $costCenterIds,
    array $establishmentIds,
    string $periodGroup
  ): array {
    $start = Carbon::parse($startDate)->startOfMonth();
    $end = Carbon::parse($endDate)->endOfMonth();
    $labels = [];
    $balances = [];
    $cursor = $start->copy();

    while ($cursor <= $end) {
      if ($periodGroup === 'year') {
        $periodEnd = $cursor->copy()->endOfYear();
        $label = $cursor->format('Y');
        $cursor->addYear();
      } elseif ($periodGroup === 'quarter') {
        $periodEnd = $cursor->copy()->endOfQuarter();
        $label = 'Q'.$cursor->quarter.' '.$cursor->format('Y');
        $cursor->addQuarter();
      } else {
        $periodEnd = $cursor->copy()->endOfMonth();
        $label = $cursor->format('Y-m');
        $cursor->addMonth();
      }

      if ($periodEnd > $end) {
        $periodEnd = $end->copy();
      }

      $opening = static::contactBalanceBefore($contactId, $cursor->toDateString(), $costCenterIds, $establishmentIds);
      $periodRows = static::fetchMovements(
        $contactId,
        $cursor->toDateString(),
        $periodEnd->toDateString(),
        $costCenterIds,
        $establishmentIds
      );
      $closing = $opening + static::periodNetMovement($periodRows);

      $labels[] = $label;
      $balances[] = round($closing, 2);
    }

    return compact('labels', 'balances');
  }

  /**
   * @param  Collection<int, object>  $rows
   * @return array{labels: array<int, string>, invoices: array<int, float>, payments: array<int, float>}
   */
  private static function buildInvoicePaymentBar(Collection $rows, string $periodGroup): array
  {
    $buckets = [];

    foreach ($rows as $row) {
      $date = Carbon::parse($row->operation_date);
      $key = match ($periodGroup) {
        'year' => $date->format('Y'),
        'quarter' => 'Q'.$date->quarter.' '.$date->format('Y'),
        default => $date->format('Y-m'),
      };

      if (! isset($buckets[$key])) {
        $buckets[$key] = ['invoice' => 0.0, 'payment' => 0.0];
      }

      $category = static::resolveCategory($row->sub_type);
      $amount = (float) $row->amount;

      if ($category === 'invoice') {
        $buckets[$key]['invoice'] += $amount;
      } elseif (in_array($category, ['payment', 'voucher'], true)) {
        $buckets[$key]['payment'] += $amount;
      }
    }

    ksort($buckets);

    return [
      'labels' => array_keys($buckets),
      'invoices' => array_column($buckets, 'invoice'),
      'payments' => array_column($buckets, 'payment'),
    ];
  }

  /**
   * @param  array<string, float>  $categoryTotals
   * @return array<int, array<string, mixed>>
   */
  private static function buildStatementSummary(
    float $openingBalance,
    array $categoryTotals,
    float $currentBalance,
    float $closingBalance
  ): array {
    return [
      ['key' => 'opening', 'label' => __('accounting::lang.css_opening_balance'), 'amount' => $openingBalance],
      ['key' => 'invoice', 'label' => __('accounting::lang.css_cat_invoices'), 'amount' => $categoryTotals['invoice'] ?? 0],
      ['key' => 'payment', 'label' => __('accounting::lang.css_cat_payments'), 'amount' => ($categoryTotals['payment'] ?? 0) + ($categoryTotals['voucher'] ?? 0)],
      ['key' => 'return', 'label' => __('accounting::lang.css_cat_returns'), 'amount' => $categoryTotals['return'] ?? 0],
      ['key' => 'adjustment', 'label' => __('accounting::lang.css_cat_adjustments'), 'amount' => ($categoryTotals['adjustment'] ?? 0) + ($categoryTotals['other'] ?? 0)],
      ['key' => 'current', 'label' => __('accounting::lang.css_current_balance'), 'amount' => $currentBalance, 'highlight' => true],
      ['key' => 'closing', 'label' => __('accounting::lang.css_closing_balance'), 'amount' => $closingBalance, 'highlight' => true],
    ];
  }

  /** @return array<string, mixed> */
  private static function buildContactAging(int $contactId, string $asOfDate): array
  {
    $util = new AccountingUtil;
    $ageType = static::inferAgeingType($contactId);
    $filters = ['contact_id' => $contactId, 'as_of_date' => $asOfDate];
    $details = $util->getAgeingReport($ageType, 'due_date', $filters);

    $buckets = ['<1' => 0.0, '1_30' => 0.0, '31_60' => 0.0, '61_90' => 0.0, '>90' => 0.0, 'total_due' => 0.0];

    if (isset($details['current'])) {
      $map = [
        'current' => '<1',
        '1_30' => '1_30',
        '31_60' => '31_60',
        '61_90' => '61_90',
        '>90' => '>90',
      ];
      foreach ($map as $sourceKey => $bucketKey) {
        foreach ($details[$sourceKey] ?? [] as $item) {
          $due = (float) ($item['due'] ?? 0);
          $buckets[$bucketKey] += $due;
          $buckets['total_due'] += $due;
        }
      }
    } else {
      foreach ($details as $row) {
        if (! is_array($row)) {
          continue;
        }
        foreach (['<1', '1_30', '31_60', '61_90', '>90', 'total_due'] as $key) {
          $buckets[$key] += (float) ($row[$key] ?? 0);
        }
      }
    }

    $weightedDays = 0.0;
    $total = $buckets['total_due'];
    if ($total > 0) {
      $weightedDays = (
        $buckets['<1'] * 0
        + $buckets['1_30'] * 15
        + $buckets['31_60'] * 45
        + $buckets['61_90'] * 75
        + $buckets['>90'] * 120
      ) / $total;
    }

    return [
      'type' => $ageType,
      'buckets' => $buckets,
      'avg_collection_days' => round($weightedDays, 0),
    ];
  }

  private static function inferAgeingType(int $contactId): string
  {
    $sellCount = DB::table('transactions')
      ->where('contact_id', $contactId)
      ->where('type', 'sell')
      ->count();
    $purchaseCount = DB::table('transactions')
      ->where('contact_id', $contactId)
      ->where('type', 'purchases')
      ->count();

    return $purchaseCount > $sellCount ? 'purchases' : 'sell';
  }

  /**
   * @param  Collection<int, object>  $rows
   * @return array<string, mixed>
   */
  private static function buildAnalytics(
    Collection $rows,
    float $periodDebit,
    float $periodCredit,
    float $openingBalance,
    float $closingBalance,
    array $aging
  ): array {
    $topMovements = $rows
      ->sortByDesc('amount')
      ->take(5)
      ->map(function ($row) {
        $label = Lang::has('accounting::lang.'.$row->sub_type)
          ? __('accounting::lang.'.$row->sub_type)
          : $row->sub_type;
        $ref = $row->atm_ref_no ?: ($row->invoice_no ?: ($row->payment_ref_no ?? '—'));

        return [
          'label' => $ref.' — '.$label,
          'amount' => (float) $row->amount,
          'type' => $row->type,
        ];
      })
      ->values()
      ->all();

    return [
      'top_movements' => $topMovements,
      'collection_ratio' => $periodDebit > 0 ? round(($periodCredit / $periodDebit) * 100, 1) : null,
      'net_movement' => $closingBalance - $openingBalance,
      'aging' => $aging,
    ];
  }

  /** @return array{start_date: string, end_date: string} */
  private static function resolveComparePeriod(string $startDate, string $endDate, string $mode): array
  {
    $start = Carbon::parse($startDate);
    $end = Carbon::parse($endDate);
    $days = $start->diffInDays($end) + 1;

    if ($mode === 'previous_year') {
      return [
        'start_date' => $start->copy()->subYear()->toDateString(),
        'end_date' => $end->copy()->subYear()->toDateString(),
      ];
    }

    return [
      'start_date' => $start->copy()->subDays($days)->toDateString(),
      'end_date' => $end->copy()->subDays($days)->toDateString(),
    ];
  }

  /** @return array<int, string> */
  private static function availableSubTypes(int $contactId, string $startDate, string $endDate): array
  {
    return Contact::where('cs_contacts.id', $contactId)
      ->whereNotNull('cs_contacts.account_id')
      ->join('transactions as t', 'cs_contacts.id', '=', 't.contact_id')
      ->join('accounting_accounts_transactions as aat', 't.id', '=', 'aat.transaction_id')
      ->whereColumn('aat.accounting_account_id', 'cs_contacts.account_id')
      ->whereDate('aat.operation_date', '>=', $startDate)
      ->whereDate('aat.operation_date', '<=', $endDate)
      ->distinct()
      ->pluck('aat.sub_type')
      ->filter()
      ->values()
      ->all();
  }

  /** @return Collection<int, object> */
  private static function usersForContact(int $contactId, string $startDate, string $endDate): Collection
  {
    return DB::table('accounting_accounts_transactions as aat')
      ->join('transactions as t', 't.id', '=', 'aat.transaction_id')
      ->join('cs_contacts as c', 'c.id', '=', 't.contact_id')
      ->join('emp_employees as u', 'u.id', '=', 'aat.created_by')
      ->where('t.contact_id', $contactId)
      ->whereNotNull('c.account_id')
      ->whereColumn('aat.accounting_account_id', 'c.account_id')
      ->whereDate('aat.operation_date', '>=', $startDate)
      ->whereDate('aat.operation_date', '<=', $endDate)
      ->select('u.id', 'u.name')
      ->distinct()
      ->orderBy('u.name')
      ->get();
  }
}
