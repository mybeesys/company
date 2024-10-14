@extends('layouts.app')

@section('title', __('accounting::lang.journalEntry'))
@section('css')
    <style>
        .dropend .dropdown-toggle::after {
            border-left: 0;
            border-right: 0;
        }
    </style>


@stop
@section('content')

    <div class="container">
        <div class="row" @if (app()->getLocale() == 'en') dir="rtl" @endif>
            <div class="col-6">
                <div class="d-flex align-items-center  gap-2 gap-lg-3">
                    <a href="{{ action('Modules\Accounting\Http\Controllers\JournalEntryController@create') }}"
                        class="btn btn-flex btn-primary h-40px fs-7 fw-bold create-journal-entry-link">
                        @lang('accounting::lang.add_journalEntry')
                    </a>
                </div>
            </div>
            <div class="col-6" style="justify-content: end;display: flex;">
                <h1> @lang('accounting::lang.journalEntry')</h1>
            </div>
        </div>
    </div>

    <div class="card-body py-6">
        <div class="table-responsive">

            <table class="table align-middle gs-0 gy-4">
                <thead>
                    <tr class="fw-bold  text-muted bg-light">
                        <th class="min-w-175px px-2">@lang('accounting::lang.journalEntry_date')</th>
                        <th class="min-w-125px">@lang('accounting::lang.type')</th>
                        <th class="min-w-150px">@lang('accounting::lang.ref_number')</th>
                        <th class="min-w-150px">@lang('accounting::lang.added_by')</th>
                        <th class="min-w-200px">@lang('accounting::lang.additionalNotes')</th>
                        <th class=""></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($acc_trans_mapping as $transactions)
                        <tr>
                            <td>
                                <a
                                    class="text-gray-900 fw-bold text-hover-primary d-block mb-1 fs-7">{{ \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $transactions->operation_date)->format('d/m/Y h:i A') }}</a>
                            </td>
                            <td> <span class="badge badge-light-primary fs-7">@lang('accounting::lang.' . $transactions->type)
                                </span>
                            </td>

                            <td> <a
                                    class="text-gray-900 fw-bold text-hover-primary d-block mb-1 fs-7">{{ $transactions->ref_no }}</a>
                            </td>
                            <td> <a
                                    class="text-gray-900 fw-bold text-hover-primary d-block mb-1 fs-7">{{ $transactions->added_by->name }}</a>
                            </td>



                            <td> <a
                                    class="text-gray-900 fw-bold text-hover-primary d-block mb-1 fs-7">{{ $transactions->note }}</a>
                            </td>
                            <td>
                                <div class="btn-group dropend">

                                    <button type="button"
                                        style="background: transparent;adding: 2px 7px 8px 13px;border-radius: 6px;"
                                        class="btn  dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-cog" style="font-size: 1.4rem; color: #c59a00;"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-left" role="menu"
                                        style=" width: max-content;padding: 10px;" style="padding: 8px 15px;">
                                        <li class="mb-5" style="text-align: justify;">
                                            <span class="card-label fw-bold fs-6 mb-1">@lang('messages.actions')</span>
                                        </li>
                                        <li>
                                            <div class="form-check form-switch my-3"
                                                style="    display: flex; justify-content: space-between; gap: 37px;">
                                                <i class="fas fa-edit"></i>
                                                <label class="form-check-label ml-4"
                                                    for="toggleCostCenter">@lang('messages.edit')</label>
                                            </div>
                                        </li>




                                    </ul>
                                </div>
                            </td>


                        </tr>
                    @endforeach

                </tbody>

            </table>

        </div>

    </div>






@stop

@section('script')
    <script type="text/javascript">
        $(document).on('click', 'a.create-journal-entry-link', function(e) {
            window.location.href = $(this).attr('href');
        });
    </script>
@stop
