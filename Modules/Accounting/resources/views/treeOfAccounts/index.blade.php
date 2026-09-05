@extends('layouts.app')

@section('title', __('accounting::lang.tree_of_accounts'))
@section('css')
    <style>
        #accounts_tree_container .fa-folder:before,
        #accounts_tree_container .jstree-themeicon,
        #accounts_tree_container .jstree-themeicon:before,
        #accounts_tree_container .jstree-themeicon.ki-outline,
        #accounts_tree_container .jstree-themeicon.ki-fasten {
            color: var(--coa-accent, #64748b) !important;
        }

        .coa-page { margin-top: 1rem; }

        .coa-toolbar {
            min-height: 34px;
        }

        .coa-search-wrap {
            width: 240px;
            max-width: 240px;
            flex-shrink: 0;
        }

        .coa-search-input {
            height: 34px;
            font-size: 0.82rem;
            padding-inline-start: 2.1rem;
            padding-inline-end: 0.65rem;
        }

        .coa-search-icon {
            font-size: 0.95rem;
            inset-inline-start: 0.65rem;
        }

        .coa-toolbar-actions {
            flex-shrink: 0;
        }

        .coa-toolbar-btn {
            height: 34px;
            padding: 0.25rem 0.7rem;
            font-size: 0.8rem;
            white-space: nowrap;
        }

        .coa-page-actions {
            flex-shrink: 0;
            overflow-x: auto;
        }

        .coa-page-action-btn {
            height: 36px;
            padding: 0.35rem 0.85rem;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .coa-tree-card {
            border: 1px solid #eef0f4;
        }

        .coa-tree-container {
            max-height: calc(100vh - 280px);
            overflow: auto;
        }

        #accounts_tree_container > ul,
        .jstree-container-ul .jstree-children {
            text-align: justify !important;
        }

        .coa-node-label-inner {
            display: inline-flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.3rem 0.55rem;
            vertical-align: middle;
        }

        .coa-node-title {
            line-height: 1.5;
            color: #1e293b;
        }

        .coa-type-heading,
        .coa-subtype-heading {
            display: inline-flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.35rem 0.5rem;
            color: #1e293b;
        }

        .coa-type-heading {
            font-weight: 700;
        }

        .coa-tone-code {
            color: #64748b;
            font-weight: 500;
        }

        .coa-balance-chip,
        .coa-type-heading .coa-balance,
        .coa-subtype-heading .coa-balance {
            display: inline-flex;
            align-items: center;
            padding: 0.12rem 0.65rem;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 600;
            line-height: 1.35;
            white-space: nowrap;
            letter-spacing: 0.01em;
        }

        .coa-balance-direct,
        .coa-type-heading .coa-balance-direct,
        .coa-subtype-heading .coa-balance-direct {
            background-color: #e6f4ec;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        .coa-balance-aggregated,
        .coa-type-heading .coa-balance-aggregated,
        .coa-subtype-heading .coa-balance-aggregated {
            background-color: #e8effc;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }

        .coa-status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
            flex-shrink: 0;
            cursor: default;
            background: none;
            border: none;
            box-shadow: none;
            padding: 0;
        }

        #accounts_tree_container .coa-status-icon {
            font-size: 0.95rem;
            line-height: 1;
        }

        #accounts_tree_container .coa-status-icon--active,
        #accounts_tree_container .coa-status-icon--active::before {
            color: #16a34a !important;
        }

        #accounts_tree_container .coa-status-icon--inactive,
        #accounts_tree_container .coa-status-icon--inactive::before {
            color: #dc2626 !important;
        }

        .coa-legend-dot {
            width: 0.65rem;
            height: 0.65rem;
            border-radius: 999px;
            display: inline-block;
            flex-shrink: 0;
        }

        .tree-actions {
            display: inline-block;
            vertical-align: middle;
        }

        .coa-gear-btn {
            padding: 2px 7px !important;
            border-radius: 6px;
            background: transparent !important;
            border: 0 !important;
        }

        .coa-gear-btn:hover {
            background: #f3f6f9 !important;
        }

        .coa-action-menu {
            min-width: 175px;
            font-size: 0.84rem;
            padding: 0.25rem 0 !important;
            border-radius: 8px;
        }

        .coa-action-menu .dropdown-item {
            padding: 0.3rem 0.75rem;
            line-height: 1.35;
            border-radius: 0;
        }

        .coa-action-menu .dropdown-item i {
            width: 1rem;
            text-align: center;
            font-size: 0.8rem;
        }

        .coa-action-menu .dropdown-divider {
            margin: 0.2rem 0;
        }

        .coa-action-menu form {
            margin: 0;
        }

        .coa-action-menu .account-delete-btn {
            width: 100%;
            text-align: inherit;
            border: 0;
            background: transparent;
            padding: 0.3rem 0.75rem;
            line-height: 1.35;
        }

        .coa-panel-compact .card-header { min-height: auto; }

        .coa-type-summary-item-compact {
            padding: 0.4rem 0.55rem;
            border-radius: 6px;
            background: #f9fafb;
            border: 1px solid #eef0f4;
        }

        .jstree-default .jstree-search {
            font-style: oblique !important;
            color: var(--bs-primary) !important;
            font-weight: 700 !important;
        }

        .jstree-default .jstree-clicked,
        .jstree-default .jstree-hovered {
            background: rgba(233, 183, 31, 0.15) !important;
            border-radius: 6px !important;
            box-shadow: none !important;
        }

        .jstree-default .jstree-ocl {
            background-image: none !important;
            width: 20px;
            height: 20px;
            line-height: 20px;
            margin-top: 2px;
            text-align: center;
            color: #1f2937;
            background: #eef2f7;
            border-radius: 5px;
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

        .swal2-popup { width: 58em !important; }

        .coa-status-modal {
            border-radius: 12px;
            overflow: hidden;
        }

        .coa-status-modal__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 50%;
            flex-shrink: 0;
            font-size: 1.1rem;
        }

        .coa-status-modal__icon--warning {
            background: #fef3c7;
            color: #d97706;
        }

        .coa-status-modal__icon--success {
            background: #dcfce7;
            color: #16a34a;
        }

        .coa-status-modal__account-box {
            padding: 0.75rem 0.9rem;
            border-radius: 8px;
            background: #f9fafb;
            border: 1px solid #eef0f4;
        }

        .coa-status-modal__points {
            padding-inline-start: 1.15rem;
            margin: 0;
            color: #4b5563;
            font-size: 0.84rem;
            line-height: 1.55;
        }

        .coa-status-modal__points li + li {
            margin-top: 0.35rem;
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
            <div class="col-6 d-flex flex-nowrap justify-content-end align-items-center gap-2 coa-page-actions">
                @dashboardcan(\Modules\Accounting\Support\AccountingPermissions::TREE_CREATE)
                <a href="{{ route('tree-of-accounts-import') }}" class="btn btn-flex btn-primary coa-page-action-btn fs-7 fw-bold">
                    @lang('accounting::lang.import_tree_of_accounts')
                </a>
                @enddashboardcan
                @dashboardcan(\Modules\Accounting\Support\AccountingPermissions::TREE_UPDATE)
                @if (config('accounting.show_repair_gl_codes'))
                    <form method="POST" action="{{ route('tree-of-accounts-repair-gl-codes') }}" class="d-inline"
                        onsubmit="return confirm(@json(__('accounting::lang.repair_gl_codes_confirm')));">
                        @csrf
                        <button type="submit" class="btn btn-flex btn-light-primary coa-page-action-btn fs-7 fw-bold">
                            <i class="ki-outline ki-wrench fs-6 me-1"></i>
                            @lang('accounting::lang.repair_gl_codes_button')
                        </button>
                    </form>
                @endif
                @enddashboardcan
                @dashboardcan(\Modules\Accounting\Support\AccountingPermissions::SETTINGS_UPDATE)
                @if (config('accounting.show_full_reset') && \Modules\Accounting\Utils\AccountingFullResetService::isAllowed())
                    <form method="POST" action="{{ route('accounting.staging-full-reset') }}" class="d-inline"
                        onsubmit="return confirm(@json(__('accounting::lang.staging_full_reset_confirm')));">
                        @csrf
                        <input type="hidden" name="confirm" value="RESET_ACCOUNTING_FULL">
                        <button type="submit" class="btn btn-flex btn-light-warning coa-page-action-btn fs-7 fw-bold">
                            <span>@lang('accounting::lang.staging_full_reset_button')</span>
                            <span class="badge badge-light-danger ms-2 fs-8 fw-semibold">@lang('accounting::lang.staging_full_reset_badge')</span>
                        </button>
                    </form>
                @endif
                @enddashboardcan
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="container"><div class="alert alert-success mt-3">{{ session('success') }}</div></div>
    @endif
    @php
        $statusFlash = session('status');
    @endphp
    @if (is_array($statusFlash) && ! empty($statusFlash['msg']))
        <div class="container">
            <div class="alert alert-{{ ! empty($statusFlash['success']) ? 'success' : 'info' }} mt-3">
                {{ $statusFlash['msg'] }}
            </div>
        </div>
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
            @if (! empty($useImportedChartLayout))
                @include('accounting::treeOfAccounts.accounts_tree_imported')
            @else
                @include('accounting::treeOfAccounts.accounts_tree', ['account' => $accounts])
            @endif
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
    <style>
        /* jsTree CDN loads after page CSS — enforce status icon colors here */
        #accounts_tree_container .coa-status-icon--active,
        #accounts_tree_container .coa-status-icon--active::before {
            color: #16a34a !important;
        }

        #accounts_tree_container .coa-status-icon--inactive,
        #accounts_tree_container .coa-status-icon--inactive::before {
            color: #dc2626 !important;
        }

        #accounts_tree_container .fa-folder:before,
        #accounts_tree_container .jstree-themeicon,
        #accounts_tree_container .jstree-themeicon:before {
            color: var(--coa-accent, #64748b) !important;
        }
    </style>
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
            if (!account) {
                return;
            }

            sessionStorage.setItem('account_category', account.account_category || '');
            sessionStorage.setItem('name_ar', account.name_ar || '');
            sessionStorage.setItem('name_en', account.name_en || '');
            sessionStorage.setItem('account_type', account.account_type || '');
            sessionStorage.setItem('account_id', account.id || '');
            sessionStorage.setItem('status', account?.status || '');
            sessionStorage.setItem('gl_code', account.gl_code || '');
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

        function coaParseAccountFromTrigger($el) {
            const raw = $el.attr('data-account');
            if (!raw) {
                return null;
            }

            try {
                return JSON.parse(raw);
            } catch (e) {
                return null;
            }
        }

        function coaAccountDisplayLabel(account) {
            if (!account) {
                return '—';
            }

            const locale = @json(app()->getLocale());
            const name = locale === 'ar' ? (account.name_ar || '') : (account.name_en || '');
            const code = account.gl_code || '';
            const parts = [];

            if (code) {
                parts.push('(' + code + ')');
            }
            if (name) {
                parts.push(name);
            }

            return parts.length ? parts.join(' ') : '—';
        }

        function coaFillStatusModalAccount(selector, account) {
            const label = account
                ? coaAccountDisplayLabel(account)
                : coaAccountDisplayLabel({
                    name_ar: sessionStorage.getItem('name_ar'),
                    name_en: sessionStorage.getItem('name_en'),
                    gl_code: sessionStorage.getItem('gl_code'),
                });

            $(selector).text(label);
        }

        function coaPositionDropdownMenu($dropdown) {
            const $btn = $dropdown.find('[data-bs-toggle="dropdown"]');
            const $menu = $dropdown.find('.dropdown-menu');
            if (!$btn.length || !$menu.length) return;

            const rect = $btn[0].getBoundingClientRect();
            const menuWidth = $menu.outerWidth() || 190;
            const isRtl = document.documentElement.getAttribute('dir') === 'rtl';
            let left = isRtl ? rect.left : (rect.right - menuWidth);

            $menu.css({
                position: 'fixed',
                top: (rect.bottom + 2) + 'px',
                left: Math.max(8, Math.min(left, window.innerWidth - menuWidth - 8)) + 'px',
                zIndex: 1060,
            });
        }

        $(document).ready(function() {
            $(document).on('click', '.account-delete-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                window.confirmDeleteAccount(this);
            });

            $(document).on('mousedown click', '.tree-actions, .tree-actions *', function(e) {
                e.stopPropagation();
            });

            $(document).on('click', '.coa-set-account-trigger', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const $trigger = $(this);
                const account = coaParseAccountFromTrigger($trigger);
                if (account) {
                    setAccount(account);
                }

                const target = $trigger.attr('data-bs-target');
                if (target === '#kt_modal_deactive') {
                    coaFillStatusModalAccount('#coa_deactive_account_label', account);
                } else if (target === '#kt_modal_active') {
                    coaFillStatusModalAccount('#coa_active_account_label', account);
                }

                if (target) {
                    const modalEl = document.querySelector(target);
                    if (modalEl && window.bootstrap && window.bootstrap.Modal) {
                        window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
                    }
                }
            });

            $(document).on('click', '.coa-action-edit, .coa-action-status', function(e) {
                const raw = $(this).attr('data-account');
                if (raw) {
                    try { setAccount(JSON.parse(raw)); } catch (err) {}
                }
            });

            $(document).on('click', '.coa-action-add-child, .coa-action-add-sub', function() {
                setAccountId($(this).data('account-id'), $(this).data('account-type'));
            });

            $(document).on('click', '#accounts_tree_container a.ledger-link', function(e) {
                e.stopPropagation();
                const href = $(this).attr('href');
                if (href && href !== '#') {
                    window.location.href = href;
                }
            });

            $(document).on('show.bs.dropdown', '.tree-actions .btn-group', function() {
                coaPositionDropdownMenu($(this));
            });

            $(document).on('hidden.bs.dropdown', '.tree-actions .btn-group', function() {
                $(this).find('.dropdown-menu').removeAttr('style');
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
                core: { themes: { responsive: true } },
                types: {
                    default: { icon: "fa fa-folder" },
                    file: { icon: "fa fa-file" },
                },
                plugins: ["types", "search"]
            }).on('ready.jstree', function () {
                $('#accounts_tree_container li').each(function () {
                    var tone = this.querySelector(':scope > a .coa-type-heading, :scope > a .coa-subtype-heading, :scope > a .coa-node-title');
                    if (!tone) {
                        return;
                    }
                    var accent = window.getComputedStyle(tone).getPropertyValue('--coa-accent').trim();
                    if (accent) {
                        this.style.setProperty('--coa-accent', accent);
                    }
                });
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
                coaFillStatusModalAccount('#coa_deactive_account_label');
            });

            $(document).on('shown.bs.modal', '#kt_modal_active', function() {
                $('#account_id_A').val(sessionStorage.getItem('account_id'));
                coaFillStatusModalAccount('#coa_active_account_label');
            });
        });
    </script>
@stop
