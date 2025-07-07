<div class="card mb-5 mb-xl-8" @if (app()->getLocale() == 'ar') dir="rtl" @endif>

    <div class="card-header border-0 p-0">
        <h3 class="card-title align-items-start flex-column">
            <span class="card-label fw-bold fs-3 mb-1 px-3">@lang('general::lang.Payments')</span>

        </h3>

    </div>

    <div class="card-body p-0">
        <div class="table-responsive">

            <table class="table align-middle gs-0 gy-4 text-center" id="salesTable">
                <thead>
                    <tr class="fw-bold  text-muted bg-light">
                        <th class="">#</th>
                        <th class="min-w-200px ">@lang('sales::fields.ref_no')</th>
                        <th class="min-w-80px">@lang('sales::lang.pament_on')</th>
                        <th class="min-w-100px">@lang('sales::lang.paid_amount')</th>
                        <th class="min-w-150px">@lang('sales::lang.payment_account_note')</th>
                        <th class="min-w-150px">@lang('accounting::lang.additionalNotes')</th>
                    </tr>
                </thead>
                <tbody id="table-body">
                    @foreach ($transaction->payment as $index => $line)
                        <tr>
                            <td>
                                <a class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6">
                                    {{ $index + 1 }}
                                </a>
                            </td>
                            <td>
                                <a class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6">
                                    {{ $line->payment_ref_no }}
                                </a>
                            </td>
                            <td>
                                <a class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6">
                                    {{ $line->paid_on }}
                                </a>
                            </td>

                            <td>
                                <a class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6">
                                    {{ $line->amount }}
                                </a>
                            </td>


                            <td>
                                <a class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6">
                                    ({{ $line?->account?->gl_code }})
                                    - @if (app()->getLocale() == 'ar')
                                        {{ $line?->account?->name_ar }}
                                    @else
                                        {{ $line?->account?->name_en }}
                                    @endif

                                </a>
                            </td>

                            <td>
                                <a class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6">
                                    {{ $line->note ?? '--' }}
                                </a>
                            </td>


                        </tr>
                    @endforeach

                </tbody>


            </table>

        </div>

    </div>

</div>
