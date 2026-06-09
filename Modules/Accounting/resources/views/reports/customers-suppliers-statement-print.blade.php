<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('accounting::lang.customers_and_suppliers_statement_of_account_report') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111827; margin: 0; padding: 0; }
        .header { text-align: center; margin-bottom: 12px; border-bottom: 2px solid #e9b71f; padding-bottom: 8px; }
        .header h1 { font-size: 16px; margin: 0 0 4px; }
        .header h2 { font-size: 13px; margin: 0; font-weight: normal; color: #4b5563; }
        .meta { margin: 8px 0; font-size: 10px; }
        .summary { margin: 10px 0; padding: 8px; border: 1px solid #d1d5db; background: #f9fafb; }
        .summary-grid { display: table; width: 100%; }
        .summary-cell { display: table-cell; text-align: center; padding: 4px; border-inline-end: 1px solid #e5e7eb; }
        .summary-cell:last-child { border-inline-end: none; }
        .summary-label { font-size: 8px; color: #6b7280; }
        .summary-value { font-size: 10px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #d1d5db; padding: 4px 5px; }
        thead th { background: #e5e7eb; font-size: 9px; text-align: center; }
        td.num { text-align: end; font-family: DejaVu Sans Mono, monospace; white-space: nowrap; }
        td.neg { color: #b91c1c; }
        tr.opening td { background: #f3f4f6; font-weight: bold; }
        .footer { margin-top: 12px; text-align: center; font-size: 8px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 6px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $company->name ?? '' }}</h1>
        <h2>{{ __('accounting::lang.customers_and_suppliers_statement_of_account_report') }}</h2>
        <p style="margin:4px 0 0;">{{ $contact->name }} — {{ $start_date }} / {{ $end_date }}</p>
    </div>

    <div class="summary">
        <div class="summary-grid">
            <div class="summary-cell">
                <div class="summary-label">{{ __('accounting::lang.css_opening_balance') }}</div>
                <div class="summary-value">{{ \App\Helpers\CurrencyHelper::format_accounting_amount($openingBalance ?? 0, false) }}</div>
            </div>
            <div class="summary-cell">
                <div class="summary-label">{{ __('accounting::lang.css_cat_invoices') }}</div>
                <div class="summary-value">{{ \App\Helpers\CurrencyHelper::format_accounting_amount($categoryTotals['invoice'] ?? 0, false) }}</div>
            </div>
            <div class="summary-cell">
                <div class="summary-label">{{ __('accounting::lang.css_cat_payments') }}</div>
                <div class="summary-value">{{ \App\Helpers\CurrencyHelper::format_accounting_amount(($categoryTotals['payment'] ?? 0) + ($categoryTotals['voucher'] ?? 0), false) }}</div>
            </div>
            <div class="summary-cell">
                <div class="summary-label">{{ __('accounting::lang.css_closing_balance') }}</div>
                <div class="summary-value">{{ \App\Helpers\CurrencyHelper::format_accounting_amount($closingBalance ?? 0, false) }}</div>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('accounting::lang.operation_date') }}</th>
                <th>{{ __('accounting::lang.transaction_number') }}</th>
                <th>{{ __('accounting::lang.transaction_type') }}</th>
                <th>{{ __('accounting::lang.ledger_stmt_col_description') }}</th>
                <th>{{ __('accounting::lang.establishment_name') }}</th>
                <th>{{ __('accounting::lang.cost_center') }}</th>
                <th>{{ __('accounting::lang.debit') }}</th>
                <th>{{ __('accounting::lang.credit') }}</th>
                <th>{{ __('accounting::lang.css_running_balance') }}</th>
                <th>{{ __('accounting::lang.added_by') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr class="{{ ($row['row_type'] ?? '') === 'opening' ? 'opening' : '' }}">
                    <td>{{ $row['operation_date'] ?? '—' }}</td>
                    <td>{{ $row['ref_no'] }}</td>
                    <td>{{ $row['transaction'] }}</td>
                    <td>{{ $row['description'] }}</td>
                    <td>{{ $row['establishment'] }}</td>
                    <td>{{ $row['cost_center'] }}</td>
                    <td class="num">{{ $row['debit'] > 0 ? \App\Helpers\CurrencyHelper::format_accounting_amount($row['debit'], false) : '—' }}</td>
                    <td class="num">{{ $row['credit'] > 0 ? \App\Helpers\CurrencyHelper::format_accounting_amount($row['credit'], false) : '—' }}</td>
                    <td class="num {{ \App\Helpers\CurrencyHelper::is_negative_amount($row['running_balance']) ? 'neg' : '' }}">
                        {{ \App\Helpers\CurrencyHelper::format_accounting_amount($row['running_balance'], false) }}
                    </td>
                    <td>{{ $row['added_by'] }}</td>
                </tr>
            @empty
                <tr><td colspan="10">{{ __('messages.no_data_found') }}</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" style="font-weight:bold;">{{ __('accounting::lang.total') }}</td>
                <td class="num">{{ \App\Helpers\CurrencyHelper::format_accounting_amount($periodDebit ?? 0, false) }}</td>
                <td class="num">{{ \App\Helpers\CurrencyHelper::format_accounting_amount($periodCredit ?? 0, false) }}</td>
                <td class="num">{{ \App\Helpers\CurrencyHelper::format_accounting_amount($closingBalance ?? 0, false) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        {{ __('accounting::lang.css_print_footer') }} — {{ now()->format('Y-m-d H:i') }}
    </div>
</body>
</html>
