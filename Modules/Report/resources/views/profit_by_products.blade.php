<div class="table-responsive">

    <p class="text-muted py-2">
        @lang('report::general.profit_note')
    </p>
    <table class="table align-middle table-striped table-row-bordered fs-6 gy-5" id="profit_by_products_table">
        <thead>
            <tr>
                <th class="min-w-10px p-3 align-middle no-border" style="text-align: inherit;">@lang('sales::lang.product')</th>
                <th class="min-w-10px p-3 align-middle no-border" style="text-align: inherit;">@lang('report::general.gross_profit')</th>
            </tr>
        </thead>
        <tfoot>
            <tr class="bg-gray font-17 footer-total" >
                <td style="text-align: inherit;"><strong>@lang('sales::lang.total_before_vat'):</strong></td>
                <td class="footer_total" style="text-align: inherit;"></td>
            </tr>
        </tfoot>
    </table>

</div>
