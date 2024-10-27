<div class="row mt-2" dir="ltr">
    <div class="col-md-4 mb-0 col-md-offset-4">
        <div class="input-group">
            <input type="text" class="search-input form-control form-control border h-lg-35px ps-13" id="cc_tree_search"
                placeholder="Search...">
            <span class="input-group-addon">
                <i
                    class="ki-outline ki-magnifier search-icon fs-2 text-gray-500 position-absolute top-50 translate-middle-y ms-5"></i>
            </span>
        </div>
    </div>

    <div class="col-md-8 d-flex justify-content-end gap-2">
        <button class="btn btn-flex btn-primary h-30px fs-7 fw-bold" id="expand_all">@lang('accounting::lang.expand_all')</button>
        <button class="btn btn-flex btn-primary h-30px fs-7 fw-bold" id="collapse_all">@lang('accounting::lang.collapse_all')</button>
    </div>
    <div class="container d-flex">
        <div class="col-md-12 " id="cc_tree_container" style="flex: 0 0 250px;">
            <ul>
                @foreach ($costCenters as $costCenter)
                    @if ($includeInactive && $costCenter->active == 0)
                        <li @if ($loop->index == 0) data-jstree='{ "opened" : true }' @endif>
                            ({{ $costCenter->account_center_number }})
                            - @if (app()->getLocale() == 'ar')
                                {{ $costCenter->name_ar }}
                            @else
                                {{ $costCenter->name_en }}
                            @endif

                            @if ($costCenter->active)
                                <span><i class="fas fa-check text-success"></i></span>
                            @else
                                <span><i class="fas fa-times text-danger" style="font-size: 14px;"></i></span>
                            @endif
                            <span class="tree-actions">
                                <div class="btn-group dropend">

                                    <button type="button"
                                        style="background: transparent;adding: 2px 7px 8px 13px;border-radius: 6px;"
                                        class="btn  dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-cog"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-left"
                                        @if (app()->getLocale() == 'ar') dir="rtl" @endif role="menu"
                                        style="padding: 8px 15px;">
                                        <li><a class="ledger-link" href="{{ action('Modules\Accounting\Http\Controllers\CostCenterConrollerController@transactions', $costCenter->id) }}"
                                            style="margin: 2px;">
                                                <i class="fas fa-file-alt"></i><span
                                                    style="margin-left: 5px;">@lang('accounting::lang.cost_center_transactions')</a>
                                        </li>
                                        <li>
                                            <a class="btn-xs btn-default text-primary" style="margin: 2px;"
                                                onclick="setCostCenter({{ $costCenter }})" data-bs-toggle="modal"
                                                data-bs-target="#kt_modal_edit_cost_center">
                                                <i class="fas fa-edit"></i><span style="margin-left: 5px;">
                                                    @lang('messages.edit')
                                            </a>
                                        </li>
                                        <li><a class="btn-xs btn-default text-primary" style="margin: 2px;"
                                                data-bs-toggle="modal" onclick="setCostCenter({{ $costCenter }})"
                                                data-bs-target="#kt_modal_create_cost_center">
                                                <i class="fas fa-plus"></i><span
                                                    style="margin-left: 5px;">@lang('accounting::lang.add_cost_center')
                                            </a></li>

                                        <li>
                                            @if ($costCenter->active)
                                                <a class="btn-xs btn-default text-danger" style="margin: 2px;"
                                                    data-bs-toggle="modal" onclick="setCostCenter({{ $costCenter }})"
                                                    data-bs-target="#kt_modal_deactive">
                                                    <i class="fas fa-power-off"></i><span style="margin-left: 5px;">
                                                        @lang('messages.deactivate')

                                                </a>
                                            @else
                                                <a class="btn-xs btn-default text-success" style="margin: 2px;"
                                                    data-bs-toggle="modal" onclick="setCostCenter({{ $costCenter }})"
                                                    data-bs-target="#kt_modal_active">
                                                    <i class="fas fa-power-off"></i><span style="margin-left: 5px;">
                                                        @lang('messages.activate')
                                                </a>
                                            @endif
                                        </li>
                                    </ul>
                                </div>



                            </span>
                            @if (count($costCenter->chiledCostCenter) > 0)
                                @include('accounting::costCenter.chiled_tree', [
                                    'costCenters' => $costCenter,
                                ])
                            @endif

                        </li>
                    @elseif (($includeInactive && $costCenter->active == 1) || (!$includeInactive && $costCenter->active == 1))
                        <li @if ($loop->index == 0) data-jstree='{ "opened" : true }' @endif>
                            ({{ $costCenter->account_center_number }})
                            - @if (app()->getLocale() == 'ar')
                                {{ $costCenter->name_ar }}
                            @else
                                {{ $costCenter->name_en }}
                            @endif

                            @if ($costCenter->active)
                                <span><i class="fas fa-check text-success"></i></span>
                            @else
                                <span><i class="fas fa-times text-danger" style="font-size: 14px;"></i></span>
                            @endif
                            <span class="tree-actions">
                                <div class="btn-group dropend">

                                    <button type="button"
                                        style="background: transparent;adding: 2px 7px 8px 13px;border-radius: 6px;"
                                        class="btn  dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-cog"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-left"
                                        @if (app()->getLocale() == 'ar') dir="rtl" @endif role="menu"
                                        style="padding: 8px 15px;">
                                        <li><a class="ledger-link"
                                                href="{{ action('Modules\Accounting\Http\Controllers\CostCenterConrollerController@transactions', $costCenter->id) }}"
                                                style="margin: 2px;">
                                                <i class="fas fa-file-alt"></i><span
                                                    style="margin-left: 5px;">@lang('accounting::lang.cost_center_transactions')</a>
                                        </li>
                                        <li>
                                            <a class="btn-xs btn-default text-primary" style="margin: 2px;"
                                                onclick="setCostCenter({{ $costCenter }})" data-bs-toggle="modal"
                                                data-bs-target="#kt_modal_edit_cost_center">
                                                <i class="fas fa-edit"></i><span style="margin-left: 5px;">
                                                    @lang('messages.edit')
                                            </a>
                                        </li>
                                        <li><a class="btn-xs btn-default text-primary" style="margin: 2px;"
                                                data-bs-toggle="modal" onclick="setCostCenter({{ $costCenter }})"
                                                data-bs-target="#kt_modal_create_cost_center">
                                                <i class="fas fa-plus"></i><span
                                                    style="margin-left: 5px;">@lang('accounting::lang.add_cost_center')
                                            </a></li>

                                        <li>
                                            @if ($costCenter->active)
                                                <a class="btn-xs btn-default text-danger" style="margin: 2px;"
                                                    data-bs-toggle="modal"
                                                    onclick="setCostCenter({{ $costCenter }})"
                                                    data-bs-target="#kt_modal_deactive">
                                                    <i class="fas fa-power-off"></i><span style="margin-left: 5px;">
                                                        @lang('messages.deactivate')

                                                </a>
                                            @else
                                                <a class="btn-xs btn-default text-success" style="margin: 2px;"
                                                    data-bs-toggle="modal"
                                                    onclick="setCostCenter({{ $costCenter }})"
                                                    data-bs-target="#kt_modal_active">
                                                    <i class="fas fa-power-off"></i><span style="margin-left: 5px;">
                                                        @lang('messages.activate')
                                                </a>
                                            @endif
                                        </li>
                                    </ul>
                                </div>



                            </span>
                            @if (count($costCenter->chiledCostCenter) > 0)
                                @include('accounting::costCenter.chiled_tree', [
                                    'costCenters' => $costCenter,
                                ])
                            @endif

                        </li>
                    @endif
                @endforeach
            </ul>
        </div>




    </div>
</div>
