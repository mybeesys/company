 <ul>
     @foreach ($account->child_accounts as $child_account)
         <li @if (count($child_account->child_accounts) == 0) data-jstree='{ "icon" : "ki-outline ki-fasten" }' @endif>
             @if (app()->getLocale() == 'ar')
                 @if (!empty($child_account->gl_code))
                     ({{ $child_account->gl_code }})
                     -
                 @endif

                 {{ $child_account->name_ar }}
             @else
                 @if (!empty($child_account->gl_code))
                     ({{ $child_account->gl_code }})
                 @endif
                 - {{ $child_account->name_en }}
             @endif
             - @format_currency($child_account->balance)

             @if ($child_account->status == 'active')
                 <span><i class="fas fa-check text-success" title="@lang('accounting::lang.active')"></i></span>
             @elseif($child_account->status == 'inactive')
                 <span><i class="fas fa-times text-danger" title="@lang('lang_v1.inactive')" style="font-size: 14px;"></i></span>
             @endif
             <span class="tree-actions">
                 <div class="btn-group dropend">
                     <button type="button" style="background: transparent;adding: 2px 7px 8px 13px;border-radius: 6px;"
                         class="btn  dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                         <i class="fas fa-cog"></i>
                     </button>
                     <ul class="dropdown-menu" style="padding: 8px 15px;">
                         <li><a class="btn-xs  btn-default  ledger-link"
                                 href="{{ action('Modules\Accounting\Http\Controllers\TreeAccountsController@ledger', $child_account->id) }}"
                                 data-href="{{ action('Modules\Accounting\Http\Controllers\TreeAccountsController@ledger', $child_account->id) }}"
                                 style="margin: 2px;">
                                 <i class="fas fa-file-alt"></i><span style="margin-left: 5px;">@lang('accounting::lang.ledger')</a>
                         </li>
                         <li>
                             <a class="btn-xs btn-default text-primary" style="margin: 2px;"
                                 onclick="setAccount({{ $child_account }})" data-bs-toggle="modal"
                                 data-bs-target="#kt_modal_edit_account">
                                 <i class="fas fa-edit"></i><span style="margin-left: 5px;">
                                     @lang('messages.edit')
                             </a>
                         </li>
                         <li><a class="btn-xs btn-default text-primary" style="margin: 2px;" data-bs-toggle="modal"
                                 onclick="setAccountId({{ $child_account->id }})"
                                 data-bs-target="#kt_modal_create_account">
                                 <i class="fas fa-plus"></i><span style="margin-left: 5px;">@lang('accounting::lang.add_account')
                             </a></li>

                         <li>
                             @if ($child_account->status == 'active')
                                 <a class="btn-xs btn-default text-danger" style="margin: 2px;" data-bs-toggle="modal"
                                     onclick="setAccount({{ $child_account }})" data-bs-target="#kt_modal_deactive">
                                     <i class="fas fa-power-off"></i><span style="margin-left: 5px;">
                                         @lang('messages.deactivate')
                                 </a>
                             @else
                                 <a class="btn-xs btn-default text-success" style="margin: 2px;" data-bs-toggle="modal"
                                     onclick="setAccount({{ $child_account }})" data-bs-target="#kt_modal_active">
                                     <i class="fas fa-power-off"></i><span style="margin-left: 5px;">
                                         @lang('messages.activate')
                                 </a>
                             @endif
                         </li>
                     </ul>
                 </div>



             </span>
             @if (count($child_account->child_accounts) > 0)
                 @include('accounting::treeOfAccounts.chiled_tree', ['account' => $child_account])
             @endif
         </li>
     @endforeach
 </ul>
