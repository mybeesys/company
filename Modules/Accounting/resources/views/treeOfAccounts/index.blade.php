@extends('layouts.app')

@section('title', __('accounting::lang.tree_of_accounts'))
@section('css')
    <style>
        .fa-folder:before {
            color: #d1a400 !important;
        }

        #accounts_tree_container>ul {
            text-align: justify !important;
        }

        .jstree-container-ul .jstree-children {
            text-align: justify !important;
        }

        .jstree-default .jstree-search {
            font-style: oblique !important;
            color: #1b84ff !important;
            font-weight: 700 !important;
        }

        .swal2-popup {
            width: 58em !important;
        }

        .jstree-default .jstree-clicked {
            background: #beebff2e !important;
            border-radius: 8px !important;
            box-shadow: none !important;
        }

        .jstree-default .jstree-anchor .jstree-hovered {
            background: #beebff2e !important;
            border-radius: 8px !important;
            box-shadow: none !important;
        }

        .btn.btn-secondary.show:hover {
            background-color: transparent !important;
        }

        .select-custom {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-color: #f3f4f6;
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 10px;
            font-size: 14px;
            color: #333;
        }

        /* Show + / − on expand control */
        .jstree-default .jstree-ocl {
            background-image: none !important;
            width: 20px;
            height: 20px;
            line-height: 20px;
            margin-top: 2px;
            text-align: center;
            color: #1f2937; /* darker */
            background: #eef2f7;
            border-radius: 5px;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.35);
        }

        .jstree-default .jstree-closed > .jstree-ocl:before {
            content: "+";
            font-weight: 900;
            font-size: 18px;
        }

        .jstree-default .jstree-open > .jstree-ocl:before {
            content: "−";
            font-weight: 900;
            font-size: 18px;
        }

        .jstree-default .jstree-leaf > .jstree-ocl:before {
            content: "";
        }

        .jstree-default .jstree-ocl:hover {
            background: #e5ebf5;
            color: #111827;
        }

        .jstree-default .jstree-anchor:focus-visible + .jstree-ocl,
        .jstree-default .jstree-ocl:focus-visible {
            outline: 2px solid rgba(27, 132, 255, 0.35);
            outline-offset: 1px;
        }
    </style>
@stop

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-6">
                <div class="d-flex align-items-center gap-2 gap-lg-3">
                    <h1> @lang('accounting::lang.tree_of_accounts')</h1>
                </div>
            </div>
            <div class="col-6" style="justify-content: end;display: flex;">
                <a href="{{ route('tree-of-accounts-import') }}" class="btn btn-flex btn-primary h-40px fs-7 fw-bold">
                    @lang('accounting::lang.import_tree_of_accounts')
                </a>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="container"><div class="alert alert-success mt-3">{{ session('success') }}</div></div>
    @endif
    @if (session('error'))
        <div class="container"><div class="alert alert-danger mt-3">{{ session('error') }}</div></div>
    @endif
    @if ($errors->any())
        <div class="container">
            <div class="alert alert-danger mt-3">
                <ul class="mb-0 ps-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @if (!$account_exist)
        <div class="card h-md-100 my-5" dir="ltr">
            <div class="card-body d-flex flex-column flex-center">
                <div class="mb-2">
                    <h4 class="fw-semibold text-gray-800 text-center lh-lg">
                        <span class="fw-bolder"> @lang('accounting::lang.no_accounts')</span> <br>
                        @lang('accounting::lang.create_suggestion_tree_of_accounts')
                    </h4>
                    <div class="py-10 text-center">
                        <img src="/assets/media/illustrations/empty-content.svg" class="theme-light-show w-200px" alt="">
                        <img src="/assets/media/illustrations/empty-content.svg" class="theme-dark-show w-200px" alt="">
                    </div>
                </div>
                <div class="text-center mb-1">
                    <a href="{{ route('create-default-accounts') }}"
                        class="btn btn-flex btn-outline btn-color-gray-700 btn-active-color-primary bg-body h-40px fs-7 fw-bold">
                        @lang('accounting::lang.create_defulte_accounts') </a>
                </div>
            </div>
        </div>
    @else
        <div>
            @include('accounting::treeOfAccounts.accounts_tree', ['account' => $accounts])
            @include('accounting::treeOfAccounts.edit-account', [
                'account_main_types' => $account_main_types,
                'account_category' => $account_category,
            ])
            @include('accounting::treeOfAccounts.create-account', [
                'account_main_types' => $account_main_types,
                'account_category' => $account_category,
            ])
            @include('accounting::treeOfAccounts.create-sub-account')
            @include('accounting::treeOfAccounts.deactive')
            @include('accounting::treeOfAccounts.active')
        </div>
    @endif
@stop

@section('script')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.2.1/themes/default/style.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.2.1/jstree.min.js"></script>

    <script type="text/javascript">
        window.confirmDeleteAccount = function(btn) {
            try {
                const form = btn && btn.closest ? btn.closest('form') : null;
                if (!form) return;

                const SwalApi = (typeof window.Swal !== 'undefined' && window.Swal) ? window.Swal :
                    (typeof window.Sweetalert2 !== 'undefined' ? window.Sweetalert2 : null);

                const go = () => form.submit();

                if (SwalApi && SwalApi.fire) {
                    SwalApi.fire({
                        icon: 'warning',
                        title: @json(__('messages.are_you_sure')),
                        text: @json(__('accounting::lang.delete_account_confirm')),
                        showCancelButton: true,
                        confirmButtonText: @json(__('messages.delete')),
                        cancelButtonText: @json(__('messages.cancel')),
                        reverseButtons: {{ app()->getLocale() === 'ar' ? 'true' : 'false' }}
                    }).then((r) => { if (r.isConfirmed) go(); });
                } else if (confirm(@json(__('accounting::lang.delete_account_confirm')))) {
                    go();
                }
            } catch (e) {
                if (confirm(@json(__('accounting::lang.delete_account_confirm')))) {
                    const form = btn && btn.closest ? btn.closest('form') : null;
                    if (form) form.submit();
                }
            }
        };

     function setAccountId(id, nature) {
   $('#parent_id').val(id);

     $('#sub_account_id').val(id);

    sessionStorage.setItem('sub_account_id', id);
    sessionStorage.setItem('account_id', id);
    let natureText = '';
    let badgeClass = '';

    if (nature === 'asset' || nature === 'expense' || nature ==='expenses') {
        natureText = 'مدين (Debit)';
        badgeClass = 'badge-light-primary';
    } else {
        natureText = 'دائن (Credit)';
        badgeClass = 'badge-light-success';
    }

    $('#account_nature_display').text(natureText)
                                .removeClass('badge-light-primary badge-light-success')
                                .addClass(badgeClass);
                                $('#account_nature_display_').text(natureText)
                                .removeClass('badge-light-primary badge-light-success')
                                .addClass(badgeClass);
$('#account_nature_display_1').text(natureText)
                                .removeClass('badge-light-primary badge-light-success')
                                .addClass(badgeClass);

}
        function setAccount(account) {
            sessionStorage.setItem('account_category', account.account_category);
            sessionStorage.setItem('name_ar', account.name_ar);
            sessionStorage.setItem('name_en', account.name_en);
            sessionStorage.setItem('account_type', account.account_type);
            sessionStorage.setItem('account_id', account.id);
            sessionStorage.setItem('status', account?.status);
            sessionStorage.setItem('gl_code', account.gl_code);
             let natureText = '';
    let badgeClass = '';

    if (account.account_primary_type === 'asset' || account.account_primary_type === 'expense' || account.account_primary_type ==='expenses') {
        natureText = 'مدين (Debit)';
        badgeClass = 'badge-light-primary';
    } else {
        natureText = 'دائن (Credit)';
        badgeClass = 'badge-light-success';
    }

    $('#account_nature_display').text(natureText)
                                .removeClass('badge-light-primary badge-light-success')
                                .addClass(badgeClass);
                                $('#account_nature_display_').text(natureText)
                                .removeClass('badge-light-primary badge-light-success')
                                .addClass(badgeClass);
$('#account_nature_display_1').text(natureText)
                                .removeClass('badge-light-primary badge-light-success')
                                .addClass(badgeClass);


        }

        const labels = {
            debit: "@lang('accounting::lang.debit')",
            credit: "@lang('accounting::lang.credit')",
            waiting: "@lang('messages.select_type_first')"
        };


        $(document).ready(function() {
            // keep delegated handler as a backup
            $(document).on('click', '.account-delete-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                window.confirmDeleteAccount(this);
            });

            $(document).on('shown.bs.modal', '#kt_modal_create_account', function() {
                var value = sessionStorage.getItem('account_id');
                $('#account_id_create').val(value);

                var url = @json(route('next-gl-code'));
                $.get(url, { parent_account_id: value })
                    .done(function(resp) {
                        if (resp && resp.gl_code) {
                            $('#create_gl_code').val(resp.gl_code);
                        }
                    });
            });

            $(document).on('shown.bs.modal', '#kt_modal_edit_account', function() {
                $('#gl_code').val(sessionStorage.getItem('gl_code'));
                $('#name_ar').val(sessionStorage.getItem('name_ar'));
                $('#name_en').val(sessionStorage.getItem('name_en'));

                $('#account_id').val(sessionStorage.getItem('account_id'));
            });

            $(document).on('shown.bs.modal', '#kt_modal_create_sub_account', function() {
                var value = sessionStorage.getItem('sub_account_id');
                $('#sub_account_id').val(value);
            });

            $.jstree.defaults.core.themes.variant = "large";
            $('#accounts_tree_container').jstree({
                "core": { "themes": { "responsive": true } },
                "types": {
                    "default": { "icon": "fa fa-folder" },
                    "file": { "icon": "fa fa-file" },
                },
                "plugins": ["types", "search"]
            });

            var to = false;
            $('#accounts_tree_search').keyup(function() {
                if (to) { clearTimeout(to); }
                to = setTimeout(function() {
                    var v = $('#accounts_tree_search').val();
                    $('#accounts_tree_container').jstree(true).search(v);
                }, 250);
            });

            $(document).on('click', '#expand_all', function(e) {
                $('#accounts_tree_container').jstree("open_all");
            });

            $(document).on('click', '#collapse_all', function(e) {
                $('#accounts_tree_container').jstree("close_all");
            });

            $(document).on('shown.bs.modal', '#kt_modal_deactive', function() {
                $('#account_id_deactive').val(sessionStorage.getItem('account_id'));
            });

            $(document).on('shown.bs.modal', '#kt_modal_active', function() {
                $('#account_id_A').val(sessionStorage.getItem('account_id'));
            });
        });

        $(document).on('click', 'a.ledger-link', function(e) {
            window.location.href = $(this).attr('href');
        });
    </script>
@stop
