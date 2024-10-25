@extends('layouts.app')

@section('title', __('menuItemLang.costCenter'))
@section('css')
    <style>
        .dropend .dropdown-toggle::after {
            border-left: 0;
            border-right: 0;
        }

        .fa-folder:before {
            color: #17c653 !important;

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
            /* max-width: 0% !important; */
        }

        /* .jstree-hoverd .jstree-anchor .jstree-clicked {
                                                                                                                                                                                                                                                                                                                            background: #beebff2e !important;
                                                                                                                                                                                                                                                                                                                            border-radius: 13px !important;
                                                                                                                                                                                                                                                                                                                            box-shadow: none !important;
                                                                                                                                                                                                                                                                                                                        } */

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
    </style>


@stop
@section('content')
    <div class="container">
        <div class="row">
            <div class="col-6">
                <div class="d-flex align-items-center gap-2 gap-lg-3">
                    <h1> @lang('menuItemLang.costCenter')</h1>

                </div>
            </div>
            <div class="col-6" style="justify-content: end;display: flex;">
                <a href="#" class="btn btn-flex btn-primary h-40px fs-7 fw-bold" data-bs-toggle="modal"
                    data-bs-target="#kt_modal_create_main_cost_center">
                    <i class="fas fa-plus"></i> @lang('accounting::lang.add_main_cost_center')
                </a>
            </div>
        </div>
    </div>

    @if (count($costCenters) == 0)
        <div class="card h-md-100 my-5" dir="ltr">
            <div class="card-body d-flex flex-column flex-center">
                <div class="mb-2">
                    <h4 class="fw-semibold text-gray-800 text-center lh-lg">
                        <span class="fw-bolder"> @lang('accounting::lang.no_costCenters')</span> <br>
                        @lang('accounting::lang.create_suggestion_tree_of_costCenters')
                    </h4>

                    <div class="py-10 text-center">
                        <img src="/assets/media/illustrations/empty-content.svg" class="theme-light-show w-200px"
                            alt="">
                        <img src="/assets/media/illustrations/empty-content.svg" class="theme-dark-show w-200px"
                            alt="">
                    </div>

                </div>

            </div>
        </div>
    @else
        <div class="col-md-1 " style="flex: 0 0 250px;">
            <div class="card-toolbar">
                <div class="btn-group dropend">

                    <button type="button" style="background: transparent;adding: 2px 7px 8px 13px;border-radius: 6px;"
                        class="btn  dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-cog" style="font-size: 1.4rem; color: #c59a00;"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-left" role="menu" style=" width: max-content;padding: 10px;"
                        style="padding: 8px 15px;">
                        <li class="mb-5" style="text-align: justify; border-bottom: 1px solid #00000029;
                        ">
                            <span class="card-label fw-bold fs-6 mb-1">@lang('messages.settings')</span>
                        </li>

                        <li>
                            <form method="GET" action="{{ route('cost-center-index') }}">
                                <input type="hidden" name="includeInactive" value="{{ $includeInactive }}" />
                                @if ($includeInactive)
                                    <button type="submit" style="width: 100%;text-align: start; padding: 0;"
                                        class="btn btn-">@lang('accounting::lang.unInclude inactive')</button>
                                @else
                                    <button type="submit" style="width: 100%;text-align: start; padding: 0;"
                                        class="btn btn-">@lang('accounting::lang.Include inactive')</button>
                                @endif

                            </form>
                        </li>

                        <li>
                            <div class="menu-item ">
                                <a href= "{{ url('/cost-center-print') }}"
                                    style="width: 100%;text-align: start; padding: 0;" class="btn">@lang('accounting::fields.print')</a>
                            </div>
                        </li>

                        <li>
                            <div class="menu-item ">
                                <a href= "{{ url('/cost-center-export-pdf') }}"
                                    style="width: 100%;text-align: start; padding: 0;" class="btn">@lang('general.export_as_pdf')</a>
                            </div>
                        </li>


                        <li>
                            <div class="menu-item ">
                                <a href= "{{ url('/cost-center-export-excel') }}"
                                    style="width: 100%;text-align: start; padding: 0;" class="btn">@lang('general.export_as_excel')</a>
                            </div>
                        </li>



                    </ul>
                </div>
            </div>
        </div>



        @include('accounting::costCenter.tree_cost_centers', [
            'costCenters' => $costCenters,
            'includeInactive' => $includeInactive,
        ])
    @endif



    @include('accounting::costCenter.create')
    @include('accounting::costCenter.edit')
    @include('accounting::costCenter.create_sub')
    @include('accounting::costCenter.active')
    @include('accounting::costCenter.deactive')


@stop

@section('script')





    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.2.1/themes/default/style.min.css" />

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.2.1/jstree.min.js"></script>
    <script type="text/javascript">
        function setCostCenter(costCenter) {


            sessionStorage.setItem('costCenter_name_ar', (costCenter.account_center_number) + ' - ' + costCenter.name_ar);
            sessionStorage.setItem('costCenter_name_en', (costCenter.account_center_number) + ' - ' + costCenter.name_en);
            sessionStorage.setItem('_costCenter_name_ar', costCenter.name_ar);
            sessionStorage.setItem('_costCenter_name_en', costCenter.name_en);
            sessionStorage.setItem('costCenter_id', costCenter.id);
            sessionStorage.setItem('_costCenter_id', costCenter.id);

        }
        var locale = '{{ session()->get('locale') }}';

        $(document).ready(function() {

            $(document).on('shown.bs.modal', '#kt_modal_create_cost_center', function() {
                var value = sessionStorage.getItem('_costCenter_id');

                var cost_center_title = locale == 'ar' ? sessionStorage.getItem('costCenter_name_ar') :
                    sessionStorage.getItem('costCenter_name_en');
                console.log(value);

                $('#parent_account_id_').val(value);
                $('#cost_center_title').text(cost_center_title);
            });


            $(document).on('shown.bs.modal', '#kt_modal_edit_cost_center', function() {


                $('#name_ar').val(sessionStorage.getItem('_costCenter_name_ar'));
                $('#name_en').val(sessionStorage.getItem('_costCenter_name_en'));
                $('#costCenter_id').val(sessionStorage.getItem('costCenter_id'));

            });


            $(document).on('shown.bs.modal', '#kt_modal_deactive', function() {
                var value = sessionStorage.getItem('costCenter_id');
                $('#cost_center_id_').val(value);
            });

            $(document).on('shown.bs.modal', '#kt_modal_active', function() {
                var value = sessionStorage.getItem('costCenter_id');
                $('#cost_center_id_A').val(value);
            });


            $.jstree.defaults.core.themes.variant = "large";
            $('#cc_tree_container').jstree({
                "core": {
                    "themes": {
                        "responsive": true
                    },
                },
                "types": {
                    "default": {
                        "icon": "fa fa-folder"
                    },
                    "file": {
                        "icon": "fa fa-file"
                    }
                },
                "plugins": ["types", "search"]
            });
            var to = false;
            $('#cc_tree_search').keyup(function() {
                if (to) {
                    clearTimeout(to);
                }
                to = setTimeout(function() {
                    var v = $('#cc_tree_search').val();
                    $('#cc_tree_container').jstree(true).search(v);
                }, 250);
            });
            $(document).on('click', '#expand_all', function(e) {
                $('#cc_tree_container').jstree("open_all");
            })
            $(document).on('click', '#collapse_all', function(e) {
                $('#cc_tree_container').jstree("close_all");
            });
            var value = sessionStorage.getItem('key');
            $('#targetInput').val(value);

            $('#toggleInclude_inactive').change(function() {
                var includeInactive = $(this).is(':checked');
                fetchTreeData(includeInactive);
            });

            fetchTreeData(false);

        });

        $(document).on('click', 'a.ledger-link', function(e) {
            window.location.href = $(this).attr('href');
        });
    </script>
@stop
