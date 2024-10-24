@extends('employee::layouts.master')

@section('title', __('menuItemLang.employees'))

@section('css')
    <style>

    </style>
@endsection
@section('content')
    <form id="add_role_form" class="form d-flex flex-column flex-lg-row" method="POST" enctype="multipart/form-data"
        action="{{ route('dashboard-roles.store') }}">
        @csrf
        <x-employee::dashboard-roles.form :modules=$modules formId="add_role_form" />
    </form>
@endsection

@section('script')
    @parent
    <script src="{{ url('modules/employee/js/create-edit-role.js') }}"></script>
    <script src="{{ url('modules/employee/js/create-edit-dashboard-role.js') }}"></script>
    <script>
        "use strict";
        $(document).ready(function() {
            roleForm('add_role_form', "{{ route('dashboard-roles.create.validation') }}");
            dashboardRolePermissionsForm();

            const isMobile = window.innerWidth < 550;
            let table = $("#dashboard-permissions-table").DataTable({
                paging: false,
                info: false,
                fixedHeader: {
                    header: true,
                    headerOffset: 100
                },
                responsive: true,
                ordering: false,
                autoWidth: false,
            });

            const targetNode = $("#dashboard-permissions-table")[0];
            const config = {
                childList: true,
                subtree: true
            };

            const observer = new MutationObserver(function(mutationsList) {
                mutationsList.forEach(function(mutation) {
                    const floatingParent = $('.dtfh-floatingparent');
                    const floatingParentChild = $('.dtfh-floatingparent > div');
                    floatingParentChild.css('padding-right', '0');
                    $('.dtfh-floatingparent').addClass('rounded-start rounded-end');
                    if (window.innerWidth < 990) {
                        floatingParent.css('top', '75px');
                    }
                });
            });
            observer.observe(targetNode, config);

            $(window).on('scroll', function() {
                if (window.innerWidth < 990) {
                    const floatingParent = $('.dtfh-floatingparent');
                    floatingParent.css('top', '65px');
                }
            });


            $('#kt_app_sidebar_toggle').on('click', function() {
                setTimeout(function() {
                    $('#dashboard-permissions-table').DataTable().fixedHeader.adjust();
                }, 300);
            });

            $(window).on('resize', function() {
                const newIsMobile = window.innerWidth < 500;
                const floatingParentChild = $('.dtfh-floatingparent > div');
                const floatingParent = $('.dtfh-floatingparent');

                if (newIsMobile && table.fixedHeader) {
                    table.fixedHeader.disable();
                    floatingParentChild.css('padding-right', '0');
                } else if (!newIsMobile) {
                    table.fixedHeader.enable();
                    table.fixedHeader.adjust();
                    floatingParentChild.css('padding-right', '0');
                }
            });

        });
    </script>
@endsection
