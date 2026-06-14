<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Modules\Employee\Services\DashboardHubService;
use Modules\General\Models\Transaction;
use Modules\Reservation\Events\OrderCreated;

class DashbordController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $hubService = app(DashboardHubService::class);
        $dashboardTabs = $hubService->visibleTabs();
        $activeDashboardTab = $hubService->resolveActiveTab($dashboardTabs, $request);

        if ($redirectUrl = $hubService->fullPageUrlForTab($activeDashboardTab, $dashboardTabs)) {
            return redirect()->to($redirectUrl);
        }

        // Http::post('http://127.0.0.1:3000/api/order-created', [
        //     'type' => 'reservation',
        //     'table_id' => 1,
        //     'table_name' => "T-1",
        //     'message' => 'تم حجز طاولة جديدة',
        //     'order_id' =>1,
        // ]);

        //    event(new OrderCreated(["amen"=>"test"]));

        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $currentMonthStart = Carbon::now()->startOfMonth();
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();
        $startDate = $request->filled('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : $currentMonthStart->copy()->startOfDay();
        $endDate = $request->filled('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : $today->copy()->endOfDay();
        if ($endDate->lt($startDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }
        $periodDays = (int) max(
            1,
            $startDate->copy()->startOfDay()->diffInDays($endDate->copy()->startOfDay()) + 1
        );
        $previousPeriodStart = $startDate->copy()->subDays($periodDays)->startOfDay();
        $previousPeriodEnd = $startDate->copy()->subDay()->endOfDay();

        $validStatuses = ['approved', 'final'];

        $todaySales = Transaction::where('type', 'sell')
            ->whereIn('status', $validStatuses)
            ->whereDate('transaction_date', $today)
            ->sum('final_total');

        $yesterdaySales = Transaction::where('type', 'sell')
            ->whereIn('status', $validStatuses)
            ->whereDate('transaction_date', $yesterday)
            ->sum('final_total');

        $currentMonthSales = Transaction::where('type', 'sell')
            ->whereIn('status', $validStatuses)
            ->whereBetween('transaction_date', [$currentMonthStart, $today])
            ->sum('final_total');

        $lastMonthSales = Transaction::where('type', 'sell')
            ->whereIn('status', $validStatuses)
            ->whereBetween('transaction_date', [$lastMonthStart, $lastMonthEnd])
            ->sum('final_total');

        $dailyChangePercent =
            $yesterdaySales != 0 ? round((($todaySales - $yesterdaySales) / $yesterdaySales) * 100, 2) : 0;

        $monthlyChangePercent =
            $lastMonthSales != 0
            ? round((($currentMonthSales - $lastMonthSales) / $lastMonthSales) * 100, 2)
            : 0;

        $formattedTodaySales = number_format($todaySales);
        $formattedCurrentMonthSales = number_format($currentMonthSales);

        $todayPurchases = Transaction::where('type', 'purchases')
            ->whereIn('status', $validStatuses)
            ->whereDate('transaction_date', $today)
            ->sum('final_total');

        $yesterdayPurchases = Transaction::where('type', 'purchases')
            ->whereIn('status', $validStatuses)
            ->whereDate('transaction_date', $yesterday)
            ->sum('final_total');

        $currentMonthPurchases = Transaction::where('type', 'purchases')
            ->whereIn('status', $validStatuses)
            ->whereBetween('transaction_date', [$currentMonthStart, $today])
            ->sum('final_total');

        $lastMonthPurchases = Transaction::where('type', 'purchases')
            ->whereIn('status', $validStatuses)
            ->whereBetween('transaction_date', [$lastMonthStart, $lastMonthEnd])
            ->sum('final_total');

        $dailyChangePercent_purchases =
            $yesterdayPurchases != 0 ? round((($todayPurchases - $yesterdayPurchases) / $yesterdayPurchases) * 100, 2) : 0;

        $monthlyChangePercent_purchases =
            $lastMonthPurchases != 0
            ? round((($currentMonthPurchases - $lastMonthPurchases) / $lastMonthPurchases) * 100, 2)
            : 0;

        $formattedTodayPurchases = number_format($todayPurchases);
        $formattedCurrentMonthPurchases = number_format($currentMonthPurchases);

        $paymentsSub = DB::table('transaction_payments as tp')
            ->selectRaw('tp.transaction_id, SUM(IF(tp.is_return = 1, -1 * tp.amount, tp.amount)) as total_paid')
            ->groupBy('tp.transaction_id');

        $customer_balances = DB::table('transactions as t')
            ->leftJoinSub($paymentsSub, 'tp_sum', function ($join) {
                $join->on('t.id', '=', 'tp_sum.transaction_id');
            })
            ->where('t.type', 'sell')
            ->whereIn('t.status', $validStatuses)
            ->whereNotNull('t.contact_id')
            ->selectRaw('SUM(t.final_total) as total_invoices, SUM(IFNULL(tp_sum.total_paid, 0)) as total_payments')
            ->first();

        $total_due = $customer_balances->total_invoices - $customer_balances->total_payments;
        $formatted_total_due = number_format($total_due);
        $total_unpaid_invoices = Transaction::where('type', 'sell')
            ->whereIn('status', $validStatuses)
            ->whereIn('payment_status', ['partial', 'due'])
            ->count();

        $supplier_balances = DB::table('transactions as t')
            ->leftJoinSub($paymentsSub, 'tp_sum', function ($join) {
                $join->on('t.id', '=', 'tp_sum.transaction_id');
            })
            ->where('t.type', 'purchases')
            ->whereIn('t.status', $validStatuses)
            ->whereNotNull('t.contact_id')
            ->selectRaw('SUM(t.final_total) as total_invoices, SUM(IFNULL(tp_sum.total_paid, 0)) as total_payments')
            ->first();

        $total_due_supplier = $supplier_balances->total_invoices - $supplier_balances->total_payments;
        $formatted_total_due_supplier = number_format($total_due_supplier);

        $total_unpaid_purchases_invoices = Transaction::where('type', 'purchases')
            ->whereIn('status', $validStatuses)
            ->whereIn('payment_status', ['partial', 'due'])
            ->count();

        $customersBalances = DB::table('transactions as t')
            ->leftJoinSub($paymentsSub, 'tp_sum', function ($join) {
                $join->on('t.id', '=', 'tp_sum.transaction_id');
            })
            ->join('cs_contacts as c', 't.contact_id', '=', 'c.id')
            ->where('t.type', 'sell')
            ->whereIn('t.status', $validStatuses)
            ->where('c.business_type', 'customer')
            ->select(
                'c.name',
                'c.phone_number',
                'c.id as contact_id',
                DB::raw('SUM(t.final_total) as total_invoices'),
                DB::raw('SUM(IFNULL(tp_sum.total_paid, 0)) as total_payments'),
                DB::raw('SUM(t.final_total) - SUM(IFNULL(tp_sum.total_paid, 0)) as balance'),
            )
            ->groupBy('c.id', 'c.name', 'c.phone_number')
            ->having('balance', '>', 0)
            ->orderBy('balance', 'desc')
            ->limit(20)
            ->get();

        $supplierBalances = DB::table('transactions as t')
            ->leftJoinSub($paymentsSub, 'tp_sum', function ($join) {
                $join->on('t.id', '=', 'tp_sum.transaction_id');
            })
            ->join('cs_contacts as c', 't.contact_id', '=', 'c.id')
            ->where('t.type', 'purchases')
            ->whereIn('t.status', $validStatuses)
            ->where('c.business_type', 'supplier')
            ->select(
                'c.name',
                'c.phone_number',
                'c.id as contact_id',
                DB::raw('SUM(t.final_total) as total_invoices'),
                DB::raw('SUM(IFNULL(tp_sum.total_paid, 0)) as total_payments'),
                DB::raw('SUM(t.final_total) - SUM(IFNULL(tp_sum.total_paid, 0)) as balance'),
            )
            ->groupBy('c.id', 'c.name', 'c.phone_number')
            ->having('balance', '>', 0)
            ->orderBy('balance', 'desc')
            ->limit(20)
            ->get();

        $months = collect(range(0, 5))->map(function ($i) {
            return Carbon::now()->subMonths($i)->format('Y-m');
        })->reverse()->values();

        $salesData = DB::table('transactions')
            ->selectRaw("DATE_FORMAT(transaction_date, '%Y-%m') as month, SUM(final_total) as total")
            ->where('type', 'sell')
            ->whereIn('status', $validStatuses)
            ->whereIn(DB::raw("DATE_FORMAT(transaction_date, '%Y-%m')"), $months)
            ->groupBy('month')
            ->pluck('total', 'month');

        $expensesData = DB::table('accounting_accounts_transactions')
            ->join('accounting_accounts', 'accounting_accounts_transactions.accounting_account_id', '=', 'accounting_accounts.id')
            ->selectRaw("DATE_FORMAT(operation_date, '%Y-%m') as month, SUM(amount) as total")
            ->where('accounting_accounts.account_primary_type', 'expenses')
            ->where('accounting_accounts_transactions.type', 'debit')
            ->whereIn(DB::raw("DATE_FORMAT(operation_date, '%Y-%m')"), $months)
            ->groupBy('month')
            ->pluck('total', 'month');

        $salesArray = [];
        $expensesArray = [];
        $monthLabelsAr = [];
        $monthLabelsEn = [];

        foreach ($months as $month) {
            $date = Carbon::createFromFormat('Y-m', $month);
            $monthLabelsAr[] = $date->locale('ar')->translatedFormat('F');
            $monthLabelsEn[] = $date->locale('en')->translatedFormat('F');
            $salesArray[] = (float) ($salesData[$month] ?? 0);
            $expensesArray[] = (float) ($expensesData[$month] ?? 0);
        }

        $todayExpenses = DB::table('accounting_accounts_transactions')
            ->join(
                'accounting_accounts',
                'accounting_accounts_transactions.accounting_account_id',
                '=',
                'accounting_accounts.id',
            )
            ->where('accounting_accounts.account_primary_type', 'expenses')
            ->where('accounting_accounts_transactions.type', 'debit')
            ->whereDate('accounting_accounts_transactions.operation_date', $today)
            ->sum('accounting_accounts_transactions.amount');

        $yesterdayExpenses = DB::table('accounting_accounts_transactions')
            ->join(
                'accounting_accounts',
                'accounting_accounts_transactions.accounting_account_id',
                '=',
                'accounting_accounts.id',
            )
            ->where('accounting_accounts.account_primary_type', 'expenses')
            ->where('accounting_accounts_transactions.type', 'debit')
            ->whereDate('accounting_accounts_transactions.operation_date', $yesterday)
            ->sum('accounting_accounts_transactions.amount');

        $currentMonthExpenses = DB::table('accounting_accounts_transactions')
            ->join(
                'accounting_accounts',
                'accounting_accounts_transactions.accounting_account_id',
                '=',
                'accounting_accounts.id',
            )
            ->where('accounting_accounts.account_primary_type', 'expenses')
            ->where('accounting_accounts_transactions.type', 'debit')
            ->whereBetween('accounting_accounts_transactions.operation_date', [$currentMonthStart, $today])
            ->sum('accounting_accounts_transactions.amount');

        $lastMonthExpenses = DB::table('accounting_accounts_transactions')
            ->join(
                'accounting_accounts',
                'accounting_accounts_transactions.accounting_account_id',
                '=',
                'accounting_accounts.id',
            )
            ->where('accounting_accounts.account_primary_type', 'expenses')
            ->where('accounting_accounts_transactions.type', 'debit')
            ->whereBetween('accounting_accounts_transactions.operation_date', [$lastMonthStart, $lastMonthEnd])
            ->sum('accounting_accounts_transactions.amount');

        $dailyChangePercent_Expenses =
            $yesterdayExpenses != 0
            ? round((($todayExpenses - $yesterdayExpenses) / $yesterdayExpenses) * 100, 2)
            : 0;

        $monthlyChangePercent_Expenses =
            $lastMonthExpenses != 0
            ? round((($currentMonthExpenses - $lastMonthExpenses) / $lastMonthExpenses) * 100, 2)
            : 0;

        $formattedTodayExpenses = number_format($todayExpenses);
        $formattedCurrentMonthExpenses = number_format($currentMonthExpenses);
        $periodSales = Transaction::where('type', 'sell')
            ->whereIn('status', $validStatuses)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->sum('final_total');
        $previousPeriodSales = Transaction::where('type', 'sell')
            ->whereIn('status', $validStatuses)
            ->whereBetween('transaction_date', [$previousPeriodStart, $previousPeriodEnd])
            ->sum('final_total');
        $periodSalesChange = $previousPeriodSales > 0
            ? round((($periodSales - $previousPeriodSales) / $previousPeriodSales) * 100, 2)
            : 0;

        $periodPurchases = Transaction::where('type', 'purchases')
            ->whereIn('status', $validStatuses)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->sum('final_total');
        $previousPeriodPurchases = Transaction::where('type', 'purchases')
            ->whereIn('status', $validStatuses)
            ->whereBetween('transaction_date', [$previousPeriodStart, $previousPeriodEnd])
            ->sum('final_total');
        $periodPurchasesChange = $previousPeriodPurchases > 0
            ? round((($periodPurchases - $previousPeriodPurchases) / $previousPeriodPurchases) * 100, 2)
            : 0;

        $periodExpenses = DB::table('accounting_accounts_transactions')
            ->join(
                'accounting_accounts',
                'accounting_accounts_transactions.accounting_account_id',
                '=',
                'accounting_accounts.id',
            )
            ->where('accounting_accounts.account_primary_type', 'expenses')
            ->where('accounting_accounts_transactions.type', 'debit')
            ->whereBetween('accounting_accounts_transactions.operation_date', [$startDate, $endDate])
            ->sum('accounting_accounts_transactions.amount');
        $previousPeriodExpenses = DB::table('accounting_accounts_transactions')
            ->join(
                'accounting_accounts',
                'accounting_accounts_transactions.accounting_account_id',
                '=',
                'accounting_accounts.id',
            )
            ->where('accounting_accounts.account_primary_type', 'expenses')
            ->where('accounting_accounts_transactions.type', 'debit')
            ->whereBetween('accounting_accounts_transactions.operation_date', [$previousPeriodStart, $previousPeriodEnd])
            ->sum('accounting_accounts_transactions.amount');
        $periodExpensesChange = $previousPeriodExpenses > 0
            ? round((($periodExpenses - $previousPeriodExpenses) / $previousPeriodExpenses) * 100, 2)
            : 0;
        $periodNet = $periodSales - $periodPurchases - $periodExpenses;

        return view('employee::dashboard.hub', compact(
            'dashboardTabs',
            'activeDashboardTab',
            'formattedTodaySales',
            'dailyChangePercent',
            'formattedCurrentMonthSales',
            'monthlyChangePercent',
            'yesterdaySales',
            'formattedTodayPurchases',
            'dailyChangePercent_purchases',
            'formattedCurrentMonthPurchases',
            'monthlyChangePercent_purchases',
            'yesterdayPurchases',
            'total_due',
            'formatted_total_due',
            'customer_balances',
            'total_unpaid_invoices',
            'total_due_supplier',
            'formatted_total_due_supplier',
            'supplier_balances',
            'total_unpaid_purchases_invoices',
            'customersBalances',
            'supplierBalances',
            'expensesArray',
            'salesArray',
            'monthLabelsAr',
            'monthLabelsEn',
            'dailyChangePercent_Expenses',
            'monthlyChangePercent_Expenses',
            'formattedTodayExpenses',
            'formattedCurrentMonthExpenses',
            'yesterdayExpenses',
            'startDate',
            'endDate',
            'periodDays',
            'periodSales',
            'periodSalesChange',
            'periodPurchases',
            'periodPurchasesChange',
            'periodExpenses',
            'periodExpensesChange',
            'periodNet'

        ));
    }
}
