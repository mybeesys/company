<!DOCTYPE html>
@php
    $local = session()->get('locale');
    $dir = $local == 'ar' ? 'rtl' : 'ltr';
    $rtl_files = $local == 'ar' ? '.rtl' : '';
    $menu_placement_x = $local == 'ar' ? 'right-start' : 'left-start';
    $menu_placement_y = $local == 'ar' ? 'bottom-start' : 'bottom-end';
@endphp
<html lang="en" @if (app()->getLocale() == 'ar') dir="rtl" @endif>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print: {{ $journal->ref_no }}</title>
    <link href="/assets/plugins/global/plugins.bundle{{ $rtl_files }}.css" rel="stylesheet" type="text/css" />
    <link href="/assets/css/style.bundle{{ $rtl_files }}.css" rel="stylesheet" type="text/css" />

    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            margin: 0;
            padding: 0;
            color: #000;
        }

        .template-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .section {
            margin-bottom: 20px;
        }

        .section-header {
            font-weight: bold;
            margin-bottom: 10px;
        }

        .content {
            text-align: justify;
        }

        @media print {
            .no-print {
                display: none;
            }
        }
    </style>

    <script>
        window.onload = function() {
            window.print();
        };

        window.onafterprint = function() {
            window.location.href = "{{ route('journal-entry-index') }}";
        };
    </script>
</head>

<body>
    <div class="template-header">
        {{-- <h1>Print Page</h1> --}}
    </div>

    <div class="section">
        <div class="section-header">
            <p>@lang('accounting::fields.ref_no'): {{ $journal->ref_no }}</p>
            <p>@lang('accounting::lang.operation_date'): {{ \Carbon\Carbon::parse($journal->operation_date)->format('Y-m-d') }}</p>
        </div>

        <div class="content">
            <table class="table table-bordered table-striped hide-footer" id="journal_table">
                <thead>
                    <tr>
                        <th>@lang('accounting::lang.account')</th>
                        <th>@lang('accounting::lang.cost_center')</th>
                        <th>@lang('accounting::lang.debit')</th>
                        <th>@lang('accounting::lang.credit')</th>
                        <th>@lang('accounting::lang.added_by')</th>
                        <th>@lang('accounting::lang.additionalNotes')</th>
                    </tr>
                </thead>

                <tbody>
                    @php
                        $total_debit = 0;
                        $total_credit = 0;
                    @endphp
                    @foreach ($journal->transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->account->gl_code }} -
                                {{ app()->getLocale() == 'ar' ? $transaction->account->name_ar : $transaction->account->name_en }}
                            </td>
                            <td>{{ $transaction->cost_center ? (app()->getLocale() == 'ar' ? $transaction->cost_center->name_ar : $transaction->cost_center->name_en) : '--' }}
                            </td>
                            <td>
                                @if ($transaction->type == 'debit')
                                    {{ $transaction->amount }}
                                    @php
                                        $total_debit += $transaction->amount;
                                    @endphp
                                @else
                                    0.0
                                @endif
                            </td>
                            <td>
                                @if ($transaction->type == 'credit')
                                    {{ $transaction->amount }}
                                    @php
                                        $total_credit += $transaction->amount;
                                    @endphp
                                @else
                                    0.0
                                @endif
                            </td>
                            <td>{{ $transaction->createdBy->name }}</td>
                            <td>{{ $transaction->note }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" class="text-center"><strong>@lang('messages.total')</strong></td>
                        <td><strong>{{ $total_debit }}</strong></td>
                        <td><strong>{{ $total_credit }}</strong></td>
                        @php
                            $text = '';
                            $Budget = $total_debit - $total_credit;
                            $budgetDifferenceText = __(
                                'accounting::lang.The journal entry is unbalanced with a difference of',
                            );
                            $text = $budgetDifferenceText . ' : ( ' . abs($Budget) . ' ) ';
                        @endphp
                        <td colspan="2" id="Budget" style="text-align: center;color:red">
                            {{ $Budget > 0 ?? $text }}
                        </td>
                    </tr>
                </tfoot>
            </table>

            <hr style="width:100%;text-align:left;margin-left:0">

            <div class="row">

                <div class="col-xs-6"
                    style="
                            /* padding: 37px; */
                        align-items: center;

                        justify-content: flex-start;
                         ">

                    <p>@lang('accounting::lang.additionalNotes'): {{ $journal->note }}</p>
                </div>


            </div>

        </div>
    </div>
</body>

</html>
