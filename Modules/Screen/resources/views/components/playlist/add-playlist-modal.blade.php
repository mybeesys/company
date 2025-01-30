@props(['establishments', 'devices'])
<x-general.modal module="screen" id='add_playlist_modal' title='add_playlist' class='mw-900px' :submitButton="false">
    <div class="stepper stepper-pills" id="add_playlist_stepper">
        <div class="stepper-nav flex-center flex-wrap mb-10">
            <div class="stepper-item mx-8 my-4 current" data-kt-stepper-element="nav">
                <div class="stepper-wrapper d-flex align-items-center">
                    <div class="stepper-icon w-40px h-40px">
                        <i class="stepper-check fas fa-check"></i>
                        <span class="stepper-number">1</span>
                    </div>
                    <div class="stepper-label">
                        <h3 class="stepper-title">
                            @lang('screen::general.step') 1
                        </h3>
                    </div>
                </div>
                <div class="stepper-line h-40px"></div>
            </div>
            <div class="stepper-item mx-8 my-4" data-kt-stepper-element="nav">
                <div class="stepper-wrapper d-flex align-items-center">
                    <div class="stepper-icon w-40px h-40px">
                        <i class="stepper-check fas fa-check"></i>
                        <span class="stepper-number">2</span>
                    </div>
                    <div class="stepper-label">
                        <h3 class="stepper-title">
                            @lang('screen::general.step') 2
                        </h3>
                    </div>

                </div>
                <div class="stepper-line h-40px"></div>
            </div>
        </div>
        <div class="mb-5">
            <div class="flex-column current" data-kt-stepper-element="content">
                <div class="d-flex flex-wrap gap-4">
                    <x-form.input-div class="mb-10 w-100 px-2">
                        <x-form.input required :errors=$errors placeholder="{{ __('sales::fields.name') }}"
                            value="" name="name" :label="__('sales::fields.name')" />
                    </x-form.input-div>
                    <x-form.input-div class="mb-10 w-100">
                        <x-form.select name="days_settings" :label="__('screen::fields.days_settings')" :options="[
                            ['id' => 'every_day', 'name' => __('screen::general.every_day')],
                            ['id' => 'days_of_the_weak', 'name' => __('screen::general.days_of_the_weak')],
                            ['id' => 'custom_date_time', 'name' => __('screen::general.custom_date_time')],
                            ['id' => 'manual', 'name' => __('screen::general.manual')],
                        ]" :errors="$errors"
                            data_allow_clear="false" required>
                        </x-form.select>
                    </x-form.input-div>
                </div>
                <div class="d-flex flex-wrap gap-4">
                    <x-form.input-div class="mb-10 w-100 d-none">
                        <x-form.select name="days_of_the_weak" :label="__('screen::general.days_of_the_weak')" :options="[
                            ['id' => 'saturday', 'name' => __('employee::general.saturday')],
                            ['id' => 'sunday', 'name' => __('employee::general.sunday')],
                            ['id' => 'monday', 'name' => __('employee::general.monday')],
                            ['id' => 'tuesday', 'name' => __('employee::general.tuesday')],
                            ['id' => 'wednesday', 'name' => __('employee::general.wednesday')],
                            ['id' => 'thursday', 'name' => __('employee::general.thursday')],
                            ['id' => 'friday', 'name' => __('employee::general.friday')],
                        ]" :errors="$errors"
                            data_allow_clear="false" required attribute="multiple" no_default>
                        </x-form.select>
                    </x-form.input-div>
                    <x-form.input-div class="mb-10 w-100 px-2 d-none">
                        <x-form.input required :errors=$errors placeholder="{{ __('employee::fields.h_m') }}"
                            name="start_time" :label="__('screen::general.start_time')" />
                    </x-form.input-div>

                    <x-form.input-div class="mb-10 w-100 d-none">
                        <x-form.input name="start_date_time" :label="__('screen::general.start_date_time')" :errors="$errors" required
                            :placeholder="__('screen::general.start_date_time')" />
                    </x-form.input-div>
                </div>
                <div class="d-flex flex-wrap gap-4">
                    <x-form.input-div class="mb-10 w-100">
                        <x-form.select name="screen_orientation" :label="__('screen::general.screen_orientation')" :options="[
                            ['id' => 'landscape', 'name' => __('screen::general.landscape')],
                            ['id' => 'portrait', 'name' => __('screen::general.portrait')],
                        ]"
                            :errors="$errors" data_allow_clear="false" required>
                        </x-form.select>
                    </x-form.input-div>

                    <x-form.input-div class="mb-10 w-100">
                        <x-form.select name="devices" :label="__('screen::general.devices')" :options="$devices" optionName="code" :errors="$errors"
                            data_allow_clear="false" attribute="multiple" no_default required>
                            <button type="button" id="device-select-all-btn"
                                class="btn btn-primary px-4 py-1 fs-7 ms-2 mb-1">{{ __('employee::general.select_all') }}</button>
                            <button type="button" id="device-deselect-all-btn"
                                class="btn btn-secondary px-4 py-1 fs-7 mb-1">{{ __('employee::general.deselect_all') }}</button>
                        </x-form.select>
                    </x-form.input-div>

                    <x-form.input-div class="mb-10 w-100">
                        <x-form.select name="establishments_ids" :label="__('employee::fields.establishment')" :options=$establishments
                            :errors="$errors" data_allow_clear="false" required
                            placeholder="{{ __('employee::fields.establishment') }}" attribute="multiple" no_default>
                            <button type="button" id="est-select-all-btn"
                                class="btn btn-primary px-4 py-1 fs-7 ms-2 mb-1">{{ __('employee::general.select_all') }}</button>
                            <button type="button" id="est-deselect-all-btn"
                                class="btn btn-secondary px-4 py-1 fs-7 mb-1">{{ __('employee::general.deselect_all') }}</button>
                        </x-form.select>
                    </x-form.input-div>
                </div>
            </div>
            <div class="flex-column" data-kt-stepper-element="content">
                <div class="mx-auto">
                    <table id="promo_Playlist_table">
                        <thead>
                            <tr class="not-hover"></tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="d-flex flex-stack">
            <div class="me-2">
                <button type="button" class="btn btn-light btn-active-light-primary" data-kt-stepper-action="previous">
                    @lang('screen::general.back')
                </button>
            </div>
            <div>
                <button type="button" class="btn btn-primary" data-kt-stepper-action="submit">
                    <span class="indicator-label">
                        @lang('general.save')
                    </span>
                </button>
                <button type="button" class="btn btn-primary" data-kt-stepper-action="next">
                    @lang('screen::general.next')
                </button>
            </div>
        </div>
    </div>
</x-general.modal>

<script>
    function addPlaylistModal() {
        var element = document.querySelector("#add_playlist_stepper");
        var stepper = new KTStepper(element);
        stepper.on("kt.stepper.next", function(stepper) {
            stepper.goNext();
        });
        stepper.on("kt.stepper.previous", function(stepper) {
            stepper.goPrevious();
        });
        $('select[name="days_settings"], select[name="days_of_the_weak"], select[name="screen_orientation"], select[name="devices"], select[name="establishments_ids"]')
            .select2({
                minimumResultsForSearch: -1,
            });
        Inputmask({
            regex: "([0-1][0-9]|2[0-3]):([0-5][0-9])",
            placeholder: "__:__"
        }).mask($('#start_time')[0]);

        $("#start_date_time").flatpickr({
            enableTime: true,
            dateFormat: "Y-m-d H:i",
        });

        selectDeselectAll($('#device-select-all-btn'), $('#device-deselect-all-btn'), 'select[name="devices"]');
        selectDeselectAll($('#est-select-all-btn'), $('#est-deselect-all-btn'), 'select[name="establishments_ids"]');

        $('select[name="days_settings"]').on('change', function() {
            const selectedValue = $(this).val();

            const toggleVisibility = (element, shouldShow) => {
                shouldShow ? element.parent().removeClass('d-none') : element.parent().addClass('d-none');
            };

            const resetValues = () => {
                $('#start_time').val(null);
                $('#start_date_time').val(null);
                $('select[name="days_of_the_weak"]').val(null).trigger('change');
            };

            switch (selectedValue) {
                case 'every_day':
                    toggleVisibility($('#start_time'), true);
                    toggleVisibility($('#start_date_time'), false);
                    toggleVisibility($('select[name="days_of_the_weak"]'), false);
                    break;

                case 'days_of_the_weak':
                    toggleVisibility($('#start_time'), true);
                    toggleVisibility($('#start_date_time'), false);
                    toggleVisibility($('select[name="days_of_the_weak"]'), true);
                    break;

                case 'custom_date_time':
                    toggleVisibility($('#start_time'), false);
                    toggleVisibility($('#start_date_time'), true);
                    toggleVisibility($('select[name="days_of_the_weak"]'), false);
                    break;

                case 'manual':
                    toggleVisibility($('#start_time'), false);
                    toggleVisibility($('#start_date_time'), false);
                    toggleVisibility($('select[name="days_of_the_weak"]'), false);
                    break;
            }
            resetValues();
        });
        $('#add_playlist_modal').on('shown.bs.modal', function() {
            if ($.fn.dataTable.isDataTable(promoPlaylistTable)) {
                promoPlaylistDataTable.destroy();
            }
            addPlaylistModalPromosTable();
        });
    }

    function addPlaylistModalPromosTable() {
        promoPlaylistDataTable = $(promoPlaylistTable).DataTable({
            processing: true,
            serverSide: true,
            ajax: promoPlaylistDataUrl,
            info: false,
            scrollX: true, // Enable horizontal scrolling
            scrollCollapse: true,
            select: {
                style: 'multi',
                selector: 'td:first-child'
            },
            columnDefs: [{
                targets: 0,
                orderable: false,
                className: 'select-checkbox',
                render: function() {
                    return '';
                }
            }],
            columns: [{
                    data: null,
                    className: 'min-w-50px'
                },
                {
                    data: 'main',
                    name: 'main',
                    orderable: false
                },
            ],
            order: [],
            pageLength: 5,
            drawCallback: function() {
                KTMenu.createInstances();
                initializeSelectionHandlers();

                // Adjust DataTable layout
                $(window).trigger('resize');
                this.api().columns.adjust();
            },
            rowCallback: function(row, data, index) {
                $(row).addClass('not-hover');
            },
            createdRow: function(row) {
                $(row).addClass('cursor-pointer');
            }
        });

        // Adjust DataTable when modal is fully visible
        setTimeout(function() {
            if (promoPlaylistDataTable) {
                promoPlaylistDataTable.columns.adjust();
            }
        }, 100);
    }

    function initializeModal() {
        $('#add_playlist_modal').on('shown.bs.modal', function() {
            if ($.fn.DataTable.isDataTable(promoPlaylistTable)) {
                $(promoPlaylistTable).DataTable().destroy();
                $(promoPlaylistTable).empty();
            }

            $('#add_playlist_modal_form input, select').each(function() {
                if ($(this).is(':checkbox, :radio')) {
                    $(this).prop('checked', false);
                } else if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).val(null).trigger('change');
                } else {
                    $(this).val(null);
                }
            });
            addPlaylistModalPromosTable();
        });
    }

    function resetPlaylistForm() {
        $('#add_playlist_modal_form')[0].reset();
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();
        selectedInOrder = [];

        if ($.fn.DataTable.isDataTable(promoPlaylistTable)) {
            const dt = $(promoPlaylistTable).DataTable();
            dt.rows().deselect();
            dt.columns.adjust();
        }

        $('.selection-order').remove();
        $('input[name="selected_promos[]"]').remove();
    }

    function addPlaylistForm() {
        $('#add_playlist_modal_form').off('submit');

        $('#add_playlist_modal_form').on('submit', function(e) {
            e.preventDefault();
            let data = $(this).serializeArray();

            data.push({
                name: "_token",
                value: window.csrfToken
            });

            ajaxRequest("{{ route('playlists.store') }}", "POST", data)
                .fail(function(data) {
                    $.each(data.responseJSON.errors, function(key, value) {
                        $(`[name='${key}']`).addClass('is-invalid');
                        $(`[name='${key}']`).after('<div class="invalid-feedback">' + value +
                            '</div>');
                    });
                })
                .done(function() {
                    $('#add_playlist_modal').modal('hide');
                    playlistDataTable.ajax.reload();
                    resetPlaylistForm();
                });
        });

        $('#add_playlist_modal').on('hidden.bs.modal', function() {
            resetPlaylistForm();
        });

        $('[data-kt-stepper-action="submit"]').off('click').on('click', function(e) {
            e.preventDefault();
            const $form = $('#add_playlist_modal_form');
            $form.find('input[name="selected_promos[]"]').remove();

            selectedInOrder.forEach(promo => {
                $('<input>', {
                    type: 'hidden',
                    name: 'selected_promos[]',
                    value: promo
                }).appendTo($form);
            });

            $form.submit();
        });
    }

    function initializeSelectionHandlers() {
        promoPlaylistDataTable.off('select deselect');

        promoPlaylistDataTable.on('select', function(e, dt, type, indexes) {
            if (type === 'row') {
                var rowData = promoPlaylistDataTable.rows(indexes).data().toArray()[0];
                if (!selectedInOrder.includes(rowData.DT_RowId)) {
                    selectedInOrder.push(rowData.DT_RowId);
                }
                updateOrderIndicators();
            }
        });

        promoPlaylistDataTable.on('deselect', function(e, dt, type, indexes) {
            if (type === 'row') {
                var rowData = promoPlaylistDataTable.rows(indexes).data().toArray()[0];
                selectedInOrder = selectedInOrder.filter(id => id !== rowData.DT_RowId);
                updateOrderIndicators();
            }
        });
    }

    function updateOrderIndicators() {
        $('.selection-order').remove();

        selectedInOrder.forEach((id, index) => {
            promoPlaylistDataTable.rows().every(function() {
                const rowData = this.data();
                if (rowData.DT_RowId === id) {
                    $(this.node()).find('td:first-child').append(
                        $('<span>', {
                            class: 'selection-order',
                            text: (index + 1)
                        })
                    );
                }
            });
        });
    }

    function initializeStyles() {
        if (!$('#playlist-custom-styles').length) {
            $('<style id="playlist-custom-styles">')
                .text(`
                #promo_Playlist_table tbody tr.selected {
                    background-color: #eee !important;
                }
                .cursor-pointer {
                    cursor: pointer;
                }
                table.dataTable tbody td.select-checkbox:before {
                    content: ' ';
                    margin-top: 0;
                    margin-left: 0;
                    border: 1px solid #000;
                    border-radius: 3px;
                    width: 18px;
                    height: 18px;
                    display: block;
                    box-sizing: border-box;
                }
                table.dataTable tr.selected td.select-checkbox:before {
                    background: #1e1e2d;
                    border-color: #1e1e2d;
                }
                table.dataTable tr.selected td.select-checkbox:after {
                    content: '✓';
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    color: white;
                    font-size: 12px;
                }
                .selection-order {
                    display: inline-block;
                    width: 20px;
                    height: 20px;
                    border-radius: 50%;
                    font-weight: bold;
                    text-align: center;
                    line-height: 20px;
                    font-size: 15px;
                }
            `).appendTo('head');
        }
    }
</script>
