@props(['establishments', 'devices'])
<x-general.modal module="screen" id='add_playlist_modal' title='add_playlist' class='mw-900px' :submitButton="false">
    <style>
        #add_playlist_stepper .stepper-nav {
            padding: 0.5rem 0.25rem 1rem;
            border-bottom: 1px solid #e4e6ef;
            margin-bottom: 1.25rem !important;
        }

        #add_playlist_stepper .stepper-item.current .stepper-number {
            background: linear-gradient(135deg, #3699ff, #187de4);
            color: #fff;
            border-radius: 12px;
            box-shadow: 0 6px 16px rgba(54, 153, 255, 0.35);
        }

        #add_playlist_stepper .stepper-item.completed .stepper-number {
            background: linear-gradient(135deg, #50cd89, #2fb37a);
            color: #fff;
            border-radius: 12px;
        }

        #add_playlist_stepper .stepper-item .stepper-number {
            border-radius: 12px;
            font-weight: 700;
        }

        #add_playlist_stepper .stepper-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #181c32;
            margin-bottom: 0.15rem;
        }

        #add_playlist_stepper .stepper-desc {
            font-size: 0.78rem;
            color: #7e8299;
            font-weight: 500;
        }

        .playlist-step-card {
            border: 1px solid #e4e6ef;
            border-radius: 14px;
            padding: 1rem 1.1rem;
            background: linear-gradient(180deg, #fafbff 0%, #ffffff 100%);
            margin-bottom: 1rem;
            border-left: 4px solid #3699ff;
            box-shadow: 0 6px 22px rgba(82, 63, 105, 0.06);
        }

        .playlist-step-card .fw-bold {
            font-size: 0.92rem;
            color: #181c32;
        }

        .screen-playlist-table-wrap {
            border: 1px solid #e4e6ef;
            border-radius: 14px;
            overflow: hidden;
            background: #f5f8fa;
            padding: 0.35rem;
        }

        .screen-playlist-table-wrap .dataTables_wrapper {
            background: #fff;
            border-radius: 12px;
            padding: 0.75rem 0.75rem 0.5rem;
        }

        .screen-stepper-footer {
            margin-top: 1.5rem;
            padding-top: 1.25rem;
            border-top: 1px solid #e4e6ef;
        }

        .screen-stepper-footer .btn {
            border-radius: 10px;
            font-weight: 600;
            padding: 0.6rem 1.15rem;
        }

        .screen-stepper-footer .btn-primary {
            box-shadow: 0 8px 20px rgba(54, 153, 255, 0.28);
        }

        #add_playlist_modal .modal-content {
            border-radius: 16px;
            overflow: hidden;
        }
    </style>
    <input type="hidden" name="playlist_id" value="">
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
                        <div class="stepper-desc">@lang('screen::general.step_1_hint')</div>
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
                        <div class="stepper-desc">@lang('screen::general.step_2_hint')</div>
                    </div>

                </div>
                <div class="stepper-line h-40px"></div>
            </div>
        </div>
        <div class="mb-5">
            <div class="flex-column current" data-kt-stepper-element="content">
                <div class="playlist-step-card">
                    <div class="fw-bold">@lang('screen::general.playlist_step_setup_title')</div>
                    <div class="text-muted fs-7 mt-1">@lang('screen::general.playlist_step_setup_desc')</div>
                </div>
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
                        <x-form.select name="establishments_ids" :label="__('employee::fields.establishment')" :options=$establishments
                            :errors="$errors" data_allow_clear="false" required
                            placeholder="{{ __('employee::fields.establishment') }}" attribute="multiple" no_default>
                            <button type="button" id="est-select-all-btn"
                                class="btn btn-primary px-4 py-1 fs-7 ms-2 mb-1">{{ __('employee::general.select_all') }}</button>
                            <button type="button" id="est-deselect-all-btn"
                                class="btn btn-secondary px-4 py-1 fs-7 mb-1">{{ __('employee::general.deselect_all') }}</button>
                        </x-form.select>
                    </x-form.input-div>

                    <x-form.input-div class="mb-10 w-100">
                        <x-form.select name="devices" :label="__('screen::general.devices')" :options="[]" optionName="code" :errors="$errors"
                            data_allow_clear="false" attribute="multiple" no_default required>
                            <button type="button" id="device-select-all-btn"
                                class="btn btn-primary px-4 py-1 fs-7 ms-2 mb-1">{{ __('employee::general.select_all') }}</button>
                            <button type="button" id="device-deselect-all-btn"
                                class="btn btn-secondary px-4 py-1 fs-7 mb-1">{{ __('employee::general.deselect_all') }}</button>
                        </x-form.select>
                        <div class="form-text">{{ __('screen::general.select_branch_first') }}</div>
                    </x-form.input-div>
                </div>
            </div>
            <div class="flex-column" data-kt-stepper-element="content">
                <div class="playlist-step-card">
                    <div class="fw-bold">@lang('screen::general.playlist_step_materials_title')</div>
                    <div class="text-muted fs-7 mt-1">@lang('screen::general.playlist_step_materials_desc')</div>
                </div>
                <div class="mb-5">
                    <x-form.input-div class="w-100">
                        <x-form.input type="number" min="1" max="300" name="transition_seconds" value="5" :label="__('screen::general.transition_speed_seconds')" />
                    </x-form.input-div>
                </div>
                <div class="mb-2">
                    <div class="fw-semibold text-gray-800 fs-6">@lang('screen::general.promo_table_section_title')</div>
                    <div class="text-muted fs-7 mb-3">@lang('screen::general.promo_table_section_desc')</div>
                </div>
                <div class="screen-playlist-table-wrap mx-auto w-100">
                    <table class="table align-middle table-row-dashed fs-6 gy-3 w-100 mb-0" id="promo_Playlist_table">
                        <thead>
                            <tr class="not-hover"></tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="d-flex flex-stack flex-wrap gap-3 screen-stepper-footer">
            <div class="me-2">
                <button type="button" class="btn btn-light btn-active-light-primary" data-kt-stepper-action="previous">
                    <i class="fas fa-arrow-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} me-2"></i>@lang('screen::general.back')
                </button>
            </div>
            <div class="d-flex flex-wrap gap-2 justify-content-end">
                <button type="button" class="btn btn-primary" data-kt-stepper-action="submit">
                    <span class="indicator-label">
                        <i class="fas fa-check me-2"></i>@lang('general.save')
                    </span>
                </button>
                <button type="button" class="btn btn-primary" data-kt-stepper-action="next">
                    @lang('screen::general.next')<i class="fas fa-arrow-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }} ms-2"></i>
                </button>
            </div>
        </div>
    </div>
</x-general.modal>

<script>
    let playlistStepperInstance = null;

    function updateStepperActionButtons() {
        const $nextBtn = $('#add_playlist_stepper [data-kt-stepper-action="next"]');
        const $submitBtn = $('#add_playlist_stepper [data-kt-stepper-action="submit"]');
        const currentStep = $('#add_playlist_stepper [data-kt-stepper-element="nav"].current').index() + 1;

        $nextBtn.prop('disabled', false).removeClass('disabled');

        if (currentStep <= 1) {
            $nextBtn.removeClass('d-none');
            $submitBtn.addClass('d-none');
        } else {
            $nextBtn.addClass('d-none');
            $submitBtn.removeClass('d-none');
        }
    }

    function isPlaylistStepOneValid() {
        const name = ($('#add_playlist_modal input[name="name"]').val() || '').trim();
        const daysSettings = $('#add_playlist_modal select[name="days_settings"]').val();
        const orientation = $('#add_playlist_modal select[name="screen_orientation"]').val();
        const establishments = $('#add_playlist_modal select[name="establishments_ids"]').val() || [];
        const devices = $('#add_playlist_modal select[name="devices"]').val() || [];
        const startTime = ($('#add_playlist_modal input[name="start_time"]').val() || '').trim();
        const startDateTime = ($('#add_playlist_modal input[name="start_date_time"]').val() || '').trim();
        const weekDays = $('#add_playlist_modal select[name="days_of_the_weak"]').val() || [];

        let isValid = !!name && !!daysSettings && !!orientation && establishments.length > 0 && devices.length > 0;

        if (daysSettings === 'every_day') {
            isValid = isValid && !!startTime;
        } else if (daysSettings === 'days_of_the_weak') {
            isValid = isValid && !!startTime && weekDays.length > 0;
        } else if (daysSettings === 'custom_date_time') {
            isValid = isValid && !!startDateTime;
        }

        return isValid;
    }

    function addPlaylistModal() {
        var element = document.querySelector("#add_playlist_stepper");
        playlistStepperInstance = new KTStepper(element);
        playlistStepperInstance.on("kt.stepper.next", function(stepper) {
            if (stepper.getCurrentStepIndex() === 1 && !isPlaylistStepOneValid()) {
                if (typeof toastr !== 'undefined') {
                    toastr.warning(@json(__('screen::general.playlist_step1_incomplete')));
                }
                return;
            }
            stepper.goNext();
            setTimeout(updateStepperActionButtons, 0);
        });
        playlistStepperInstance.on("kt.stepper.previous", function(stepper) {
            stepper.goPrevious();
            setTimeout(updateStepperActionButtons, 0);
        });
        $('select[name="days_settings"], select[name="days_of_the_weak"], select[name="screen_orientation"], select[name="devices"], select[name="establishments_ids"]')
            .select2();
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
        $('select[name="establishments_ids"]').on('change', function() {
            syncDevicesByEstablishments();
        });

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
            if (playlistStepperInstance && typeof playlistStepperInstance.goTo === 'function') {
                playlistStepperInstance.goTo(1);
            }
            if ($.fn.dataTable.isDataTable(promoPlaylistTable)) {
                promoPlaylistDataTable.destroy();
            }
            $(promoPlaylistTable).empty().append(
                '<thead><tr class="not-hover"></tr></thead><tbody></tbody>'
            );
            addPlaylistModalPromosTable();
            updateStepperActionButtons();
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
                if (selectedInOrder.length) {
                    promoPlaylistDataTable.rows().every(function() {
                        const rowData = this.data();
                        if (selectedInOrder.includes(String(rowData.DT_RowId)) || selectedInOrder.includes(Number(rowData.DT_RowId))) {
                            this.select();
                        }
                    });
                    updateOrderIndicators();
                }

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
            if (playlistStepperInstance && typeof playlistStepperInstance.goTo === 'function') {
                playlistStepperInstance.goTo(1);
            }
            if ($.fn.DataTable.isDataTable(promoPlaylistTable)) {
                $(promoPlaylistTable).DataTable().destroy();
            }
            $(promoPlaylistTable).empty().append(
                '<thead><tr class="not-hover"></tr></thead><tbody></tbody>'
            );

            const isEdit = !!$('#add_playlist_modal_form [name="playlist_id"]').val();
            if (isEdit) {
                addPlaylistModalPromosTable();
                updateStepperActionButtons();
                return;
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
            $('#add_playlist_modal_form [name="playlist_id"]').val('');
            $('#add_playlist_modal_form [name="transition_seconds"]').val(5);
            addPlaylistModalPromosTable();
            updateStepperActionButtons();
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

            const playlistId = $('#add_playlist_modal_form [name="playlist_id"]').val();
            const isEdit = !!playlistId;
            if (isEdit) {
                data.push({
                    name: "_method",
                    value: "PATCH"
                });
            }
            ajaxRequest(isEdit ? `{{ url('/playlist') }}/${playlistId}` : "{{ route('playlists.store') }}", "POST", data)
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
            updateStepperActionButtons();
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

        updateStepperActionButtons();
    }

    function syncDevicesByEstablishments(selectedDevices = []) {
        const establishments = $('select[name="establishments_ids"]').val() || [];
        const $devices = $('select[name="devices"]');
        $devices.empty().trigger('change');

        if (!establishments.length) {
            return;
        }

        ajaxRequest("{{ route('devices.by-establishments') }}", "GET", {
            establishments_ids: establishments
        }, false, false, false).done(function(response) {
            const list = response?.data || [];
            list.forEach(device => {
                const selected = selectedDevices.includes(String(device.id)) || selectedDevices.includes(Number(device.id));
                const option = new Option(device.name, device.id, selected, selected);
                $devices.append(option);
            });
            $devices.trigger('change');
        }).fail(function() {
            $devices.trigger('change');
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
        if (!$('#playlist-custom-styles-v4').length) {
            $('<style id="playlist-custom-styles-v4">')
                .text(`
                #promo_Playlist_table tbody tr.selected {
                    background-color: transparent !important;
                }
                #promo_Playlist_table tbody tr.selected > td {
                    background-color: transparent !important;
                }
                .cursor-pointer {
                    cursor: pointer;
                }
                table.dataTable tbody td.select-checkbox:before {
                    content: ' ';
                    margin-top: 0;
                    margin-left: 0;
                    border: 2px solid #c9cbda;
                    border-radius: 6px;
                    width: 18px;
                    height: 18px;
                    display: block;
                    box-sizing: border-box;
                }
                table.dataTable tr.selected td.select-checkbox:before {
                    background: #e4e8ee;
                    border-color: #b5bcc9;
                }
                table.dataTable tr.selected td.select-checkbox:after {
                    content: '✓';
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    color: #3f4254;
                    font-size: 11px;
                    font-weight: 700;
                }
                .selection-order {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    min-width: 22px;
                    height: 22px;
                    padding: 0 6px;
                    border-radius: 999px;
                    font-weight: 700;
                    text-align: center;
                    font-size: 12px;
                    margin-inline-start: 8px;
                    background: #e4e6ef;
                    color: #3f4254;
                    box-shadow: none;
                    border: 1px solid #d8dce6;
                }
            `).appendTo('head');
        }
    }
</script>
