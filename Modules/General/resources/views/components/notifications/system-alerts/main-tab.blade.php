@props(['employees', 'system_alert_preferences', 'system_alert_groups'])
@php
    $locale = app()->getLocale() === 'ar' ? 'ar' : 'en';
    $canUpdate = \Modules\General\Support\SettingAccess::canTab('notifications', 'update');
@endphp
<x-cards.card class="shadow-sm pb-5 px-5">
    <x-cards.card-header class="align-items-center py-3 px-2 gap-2 mb-4">
        <div>
            <h3 class="fs-4 fw-bold mb-1">@lang('general::notifications.system_alerts_title')</h3>
            <p class="text-muted mb-0 fs-7">@lang('general::notifications.system_alerts_hint')</p>
        </div>
    </x-cards.card-header>

    <form action="#" id="system_alert_preferences_form">
        @csrf
        <div class="d-flex flex-column gap-5">
            @foreach ($system_alert_groups as $module => $items)
                <div class="border rounded p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h4 class="fs-5 fw-bold mb-0">
                            @lang('general::notifications.module_'.$module)
                        </h4>
                        <span class="badge badge-light">{{ count($items) }}</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed gs-0 gy-3 mb-0">
                            <thead>
                                <tr class="fw-semibold text-muted fs-7">
                                    <th class="min-w-220px">@lang('general::notifications.alert_name')</th>
                                    <th class="min-w-90px">@lang('general::notifications.severity')</th>
                                    <th class="min-w-110px text-center">@lang('general::notifications.channel_internal')</th>
                                    <th class="min-w-110px text-center">@lang('general::notifications.channel_email')</th>
                                    <th class="min-w-240px">@lang('general::notifications.recipients')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $item)
                                    @php
                                        $key = $item['key'];
                                        $def = $item['definition'];
                                        $pref = $system_alert_preferences[$key] ?? [
                                            'internal_active' => true,
                                            'email_active' => ($def['severity'] ?? '') === 'critical',
                                            'recipient_ids' => [],
                                        ];
                                        $title = $def['title'][$locale] ?? $def['title']['ar'] ?? $key;
                                        $severity = $def['severity'] ?? 'info';
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-gray-800">{{ $title }}</div>
                                            <div class="text-muted fs-8">{{ $key }}</div>
                                        </td>
                                        <td>
                                            <span @class([
                                                'badge',
                                                'badge-light-primary' => $severity === 'info',
                                                'badge-light-warning' => $severity === 'warning',
                                                'badge-light-danger' => $severity === 'critical',
                                            ])>
                                                @lang('general::notifications.severity_'.$severity)
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <input type="hidden" name="alert_internal_active[{{ $key }}]" value="0">
                                            <div class="form-check form-switch form-check-custom form-check-solid justify-content-center">
                                                <input
                                                    class="form-check-input h-20px w-35px"
                                                    type="checkbox"
                                                    name="alert_internal_active[{{ $key }}]"
                                                    value="1"
                                                    title="@lang('general::notifications.channel_internal')"
                                                    @checked(!empty($pref['internal_active']))
                                                    @disabled(! $canUpdate)
                                                >
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <input type="hidden" name="alert_email_active[{{ $key }}]" value="0">
                                            <div class="form-check form-switch form-check-custom form-check-solid justify-content-center">
                                                <input
                                                    class="form-check-input h-20px w-35px"
                                                    type="checkbox"
                                                    name="alert_email_active[{{ $key }}]"
                                                    value="1"
                                                    title="@lang('general::notifications.channel_email')"
                                                    @checked(!empty($pref['email_active']))
                                                    @disabled(! $canUpdate)
                                                >
                                            </div>
                                        </td>
                                        <td>
                                            <select
                                                name="alert_recipients[{{ $key }}][]"
                                                class="form-select form-select-sm form-select-solid system-alert-recipient-select"
                                                multiple
                                                data-placeholder="@lang('general::notifications.recipients_placeholder')"
                                                @disabled(! $canUpdate)
                                            >
                                                @foreach ($employees as $employee)
                                                    <option
                                                        value="{{ $employee->id }}"
                                                        @selected(in_array((int) $employee->id, $pref['recipient_ids'] ?? [], true))
                                                    >
                                                        {{ $employee->translatedName ?? $employee->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="text-muted fs-8 mt-1">@lang('general::notifications.recipients_hint')</div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($canUpdate)
            <div class="d-flex justify-content-end mt-5">
                <button type="submit" class="btn btn-primary" id="system_alert_preferences_submit">
                    @lang('general::notifications.save_alerts')
                </button>
            </div>
        @endif
    </form>
</x-cards.card>

<script>
    (function () {
        const $form = $('#system_alert_preferences_form');
        if (!$form.length) return;

        if ($.fn.select2) {
            $('.system-alert-recipient-select').select2({
                width: '100%',
                allowClear: true,
                placeholder: @json(__('general::notifications.recipients_placeholder')),
                dir: @json(app()->getLocale() === 'ar' ? 'rtl' : 'ltr'),
            });
        }

        $form.on('submit', function (e) {
            e.preventDefault();
            const $btn = $('#system_alert_preferences_submit');
            $btn.prop('disabled', true);
            $.ajax({
                url: @json(route('store-system-alert-preferences')),
                method: 'POST',
                data: $form.serialize(),
                headers: { 'X-CSRF-TOKEN': @json(csrf_token()) },
                success: function (res) {
                    toastr.success(res.message || @json(__('employee::responses.operation_success')));
                },
                error: function (xhr) {
                    const msg = xhr?.responseJSON?.message || @json(__('messages.something_went_wrong'));
                    toastr.error(msg);
                },
                complete: function () {
                    $btn.prop('disabled', false);
                }
            });
        });
    })();
</script>
