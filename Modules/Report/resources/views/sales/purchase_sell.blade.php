@extends('layouts.app')
@section('title', __('menuItemLang.purchase-sell'))
@section('content')

    <section class="content">


        <!-- Printable Content -->
        <div class="print-section">
            <h1>@lang('menuItemLang.purchase-sell')
                <small class="fs-6">@lang('report::general.purchase_sell_msg')</small>
            </h1>
            <hr class="py-1" style="width:100%;text-align:left;">
            <div class="row no-print">
                <div class="col-md-3 mb-3">
                    <label for="date_range" class="form-label">@lang('report::general.filter')</label>
                    <input type="text" class="form-control" id="date_range" name="date_range">
                </div>
            </div>
            <br>

            <div id="report-content">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>@lang('menuItemLang.purchases')</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="purchase-data"></tbody>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>@lang('menuItemLang.sales')</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="sales-data"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <hr style="width:100%;text-align:left;margin-left:0">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th class="fs-4">@lang('report::general.difference_total')</th>
                                <th class="fs-4">@lang('report::general.difference_due')</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td id="difference-total" class="fs-3"></td>
                                <td id="difference-due" class="fs-3"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="row no-print">
            <div class="col-sm-12">
                <button type="button" class="btn btn-primary pull-right" aria-label="Print" onclick="printReport();">
                    <i class="fa fa-print"></i> @lang('messages.print')
                </button>
            </div>
        </div>
    </section>

@endsection
@section('script')
    @parent
    <script src="{{ url('/modules/Sales/js/localeSettings.js') }}"></script>

    {{-- <script src="{{ url('/modules/Sales/js/daterangepicker.js') }}"></script> --}}

    <script>
        let currentLang = "{{ app()->getLocale() }}";


        function printReport() {
            const noPrintElements = document.querySelectorAll('.no-print');
            noPrintElements.forEach(element => element.style.display = 'none');
            var printContent = document.querySelector('.print-section').innerHTML;
            var originalContent = document.body.innerHTML;
            document.body.innerHTML = printContent;
            window.print();
            document.body.innerHTML = originalContent;
            noPrintElements.forEach(element => element.style.display = '');
            window.location.reload();
        }

        $(document).ready(function() {


            let ranges = currentLang === "ar" ? arabicRanges : customRanges;
            let dueDateRangeValue = '';


            $("#date_range").daterangepicker({
                locale: localeSettings[currentLang],
                opens: currentLang === "ar" ? "right" : "left",
                autoUpdateInput: false,
                ranges: ranges,
            });

            $("#date_range").on("apply.daterangepicker", function(ev, picker) {
                $(this).val(
                    picker.startDate.format("YYYY-MM-DD") +
                    (currentLang === "ar" ? " إلى " : " to ") +
                    picker.endDate.format("YYYY-MM-DD")
                );
            });


            $("#date_range").on("apply.daterangepicker", function(ev, picker) {
                dueDateRangeValue =
                    picker.startDate.format("YYYY-MM-DD") +
                    (currentLang === "ar" ? " إلى " : " to ") +
                    picker.endDate.format("YYYY-MM-DD");

                $(this).val(dueDateRangeValue);

                loadReport();
            });



            console.log('JavaScript Loaded'); // للتأكد من أن الملف يعمل


            $.ajax({
                url: '{{ route('purchase-sell') }}',
                type: 'GET',

                success: function(response) {
                    $('#purchase-data').html(response.purchase_data);
                    $('#sales-data').html(response.sales_data);
                    $('#difference-total').text(response.difference.total);
                    $('#difference-due').text(response.difference.due);
                },
                error: function(xhr, status, error) {
                    console.error('Error: ', error);
                }
            });

            function loadReport() {
                console.log('loadReport Called'); // للتحقق من استدعاء الدالة

                const dateRange = $('#purchase_sell_date_filter').text().trim();
                const locationId = $('#purchase_sell_location_filter').val();


                $.ajax({
                    url: '{{ route('purchase-sell') }}',
                    type: 'GET',
                    data: {
                        date_range: dueDateRangeValue,
                    },
                    success: function(response) {
                        console.log('Response: ', response);
                        $('#purchase-data').html(response.purchase_data);
                        $('#sales-data').html(response.sales_data);
                        $('#difference-total').text(response.difference.total);
                        $('#difference-due').text(response.difference.due);

                    },
                    error: function(xhr, status, error) {
                        console.error('Error: ', error);
                        console.error('Response: ', xhr.responseText);
                    }
                });
            }

        });
    </script>
@endsection
