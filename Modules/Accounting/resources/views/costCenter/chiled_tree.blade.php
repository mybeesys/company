 <ul>
     @foreach ($costCenters->chiledCostCenter as $child_costCenter)
         @if ($includeInactive && $child_costCenter->active == 0)
             <li @if (count($child_costCenter->chiledCostCenter) == 0) data-jstree='{ "icon" : "ki-outline ki-fasten" }' @endif>
                 <span class="gap-1">
                     ({{ $child_costCenter->account_center_number }})
                     - @if (app()->getLocale() == 'ar')
                         {{ $child_costCenter->name_ar }}
                     @else
                         {{ $child_costCenter->name_en }}
                     @endif

                 </span>
                 @if ($child_costCenter->active)
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
                         <ul class="dropdown-menu dropdown-menu-left" @if (app()->getLocale() == 'ar') dir="rtl" @endif
                             role="menu" style="padding: 8px 15px;">
                             <li><a class="ledger-link"
                                     href="{{ action('Modules\Accounting\Http\Controllers\CostCenterConrollerController@transactions', $child_costCenter->id) }}"
                                     style="margin: 2px;">
                                     <i class="fas fa-file-alt"></i><span
                                         style="margin-left: 5px;">@lang('accounting::lang.cost_center_transactions')</a>
                             </li>
                             <li>
                                 <a class="btn-xs btn-default text-primary" style="margin: 2px;"
                                     onclick="setCostCenter({{ $child_costCenter }})" data-bs-toggle="modal"
                                     data-bs-target="#kt_modal_edit_cost_center">
                                     <i class="fas fa-edit"></i><span style="margin-left: 5px;">
                                         @lang('messages.edit')
                                 </a>
                             </li>
                             <li><a class="btn-xs btn-default text-primary" style="margin: 2px;" data-bs-toggle="modal"
                                     onclick="setCostCenter({{ $child_costCenter }})"
                                     data-bs-target="#kt_modal_create_cost_center">
                                     <i class="fas fa-plus"></i><span style="margin-left: 5px;">@lang('accounting::lang.add_cost_center')
                                 </a></li>

                             <li>
                                 @if ($child_costCenter->active)
                                     <a class="btn-xs btn-default text-danger" style="margin: 2px;"
                                         data-bs-toggle="modal" onclick="setCostCenter({{ $child_costCenter }})"
                                         data-bs-target="#kt_modal_deactive">
                                         <i class="fas fa-power-off"></i><span style="margin-left: 5px;">
                                             @lang('messages.deactivate')

                                     </a>
                                 @else
                                     <a class="btn-xs btn-default text-success" style="margin: 2px;"
                                         data-bs-toggle="modal" onclick="setCostCenter({{ $child_costCenter }})"
                                         data-bs-target="#kt_modal_active">
                                         <i class="fas fa-power-off"></i><span style="margin-left: 5px;">
                                             @lang('messages.activate')
                                     </a>
                                 @endif
                             </li>
                         </ul>
                     </div>



                 </span>
                 @if (count($child_costCenter->chiledCostCenter) > 0)
                     @include('accounting::costCenter.chiled_tree', ['costCenters' => $child_costCenter])
                 @endif
             </li>
         @elseif (($includeInactive && $child_costCenter->active == 1) || (!$includeInactive && $child_costCenter->active == 1))
             <li @if (count($child_costCenter->chiledCostCenter) == 0) data-jstree='{ "icon" : "ki-outline ki-fasten" }' @endif>
                 <span class="gap-1">
                     ({{ $child_costCenter->account_center_number }})
                     - @if (app()->getLocale() == 'ar')
                         {{ $child_costCenter->name_ar }}
                     @else
                         {{ $child_costCenter->name_en }}
                     @endif

                 </span>
                 @if ($child_costCenter->active)
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
                                     href="{{ action('Modules\Accounting\Http\Controllers\CostCenterConrollerController@transactions', $child_costCenter->id) }}"
                                     style="margin: 2px;">
                                     <i class="fas fa-file-alt"></i><span
                                         style="margin-left: 5px;">@lang('accounting::lang.cost_center_transactions')</a>
                             </li>
                             <li>
                                 <a class="btn-xs btn-default text-primary" style="margin: 2px;"
                                     onclick="setCostCenter({{ $child_costCenter }})" data-bs-toggle="modal"
                                     data-bs-target="#kt_modal_edit_cost_center">
                                     <i class="fas fa-edit"></i><span style="margin-left: 5px;">
                                         @lang('messages.edit')
                                 </a>
                             </li>
                             <li><a class="btn-xs btn-default text-primary" style="margin: 2px;" data-bs-toggle="modal"
                                     onclick="setCostCenter({{ $child_costCenter }})"
                                     data-bs-target="#kt_modal_create_cost_center">
                                     <i class="fas fa-plus"></i><span style="margin-left: 5px;">@lang('accounting::lang.add_cost_center')
                                 </a></li>

                             <li>
                                 @if ($child_costCenter->active)
                                     <a class="btn-xs btn-default text-danger" style="margin: 2px;"
                                         data-bs-toggle="modal" onclick="setCostCenter({{ $child_costCenter }})"
                                         data-bs-target="#kt_modal_deactive">
                                         <i class="fas fa-power-off"></i><span style="margin-left: 5px;">
                                             @lang('messages.deactivate')

                                     </a>
                                 @else
                                     <a class="btn-xs btn-default text-success" style="margin: 2px;"
                                         data-bs-toggle="modal" onclick="setCostCenter({{ $child_costCenter }})"
                                         data-bs-target="#kt_modal_active">
                                         <i class="fas fa-power-off"></i><span style="margin-left: 5px;">
                                             @lang('messages.activate')
                                     </a>
                                 @endif
                             </li>
                         </ul>
                     </div>



                 </span>
                 @if (count($child_costCenter->chiledCostCenter) > 0)
                     @include('accounting::costCenter.chiled_tree', ['costCenters' => $child_costCenter])
                 @endif
             </li>
         @endif
     @endforeach
 </ul>
