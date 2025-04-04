@props(['modules', 'disabled' => false, 'rolePermissions' => null])
<div class="table-responsive w-75 mx-auto">
    <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4 mh-400px"
        id="dashboard-permissions-table">
        <thead>
            <tr class="border-0 rounded-start rounded-end">
                <th class="w-100 fs-6 fw-bold text-gray-800 p-0">
                    <div class="d-flex justify-content-between rounded-start rounded-end bg-light p-5">
                        <div class="w-100 d-flex">
                            <div class="px-10">
                                @lang('employee::main.permissions')
                            </div>
                        </div>
                        <div class="d-flex justify-content-between w-100">
                            <div class="px-4">@lang('employee::general.show')</div>
                            <div class="px-4">@lang('employee::general.print')</div>
                            <div class="px-4">@lang('employee::general.create')</div>
                            <div class="px-4">@lang('employee::general.edit')</div>
                            <div class="px-4">@lang('employee::general.deletion')</div>
                        </div>
                    </div>
                </th>
            </tr>
        </thead>
        <tbody class="border-bottom border-dashed">
            @foreach ($modules as $moduleName => $module)
                <tr>
                    <td>
                        {{-- parent row --}}
                        <div class="d-flex justify-content-between w-100 pt-2">
                            <div class="d-flex align-items-center collapsible py-3 toggle mb-0 {{ $disabled ? '' : 'collapsed' }} w-100"
                                data-bs-toggle="collapse" data-bs-target="#kt_{{ $loop->index }}" aria-expanded="true">
                                <div class="btn btn-sm btn-icon mw-20px btn-active-color-primary me-5 ps-3">
                                    <i class="ki-outline ki-minus-square toggle-on text-primary fs-1"></i>
                                    <i class="ki-outline ki-plus-square toggle-off fs-1"></i>
                                </div>
                                <div>
                                    <h4 class="text-gray-700 fw-bold cursor-pointer mb-0 lh-base">
                                        @lang("employee::main.{$moduleName}_management_module")
                                    </h4>
                                </div>
                            </div>
                            <div class="w-100 pe-5 my-auto">
                                <div class="d-flex justify-content-between w-100 gap-11">
                                    @foreach (['show', 'print', 'create', 'update', 'delete'] as $action)
                                        <x-form.input-div
                                            class="form-check form-check-custom form-check-solid {{ $loop->first ? 'ps-6' : ($loop->last ? 'pe-5' : '') }}"
                                            :row="false">
                                            <x-form.input :errors=$errors class="form-check-input" type="checkbox"
                                                :disabled=$disabled
                                                value="{{ $module->has('all') ? $module['all'][$action] : null }}"
                                                name="dashboard_permissions[{{ $moduleName }}.all.{{ $action }}]"
                                                checked="{{ $module->has('all') ? $rolePermissions?->contains($module['all'][$action]) : false }}"
                                                :form_control="false"
                                                attribute="data-select-all={{ $moduleName }}-all-{{ $action }}" />
                                        </x-form.input-div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        {{-- child rows --}}
                        <div id="kt_{{ $loop->index }}" class="collapse {{ $disabled ? 'show' : '' }}">
                            @foreach ($module as $key => $permission)
                                @if ($key == 'all')
                                    @continue
                                @endif
                                @php
                                    $name_ar = explode('.', $key)[1];
                                    $name_en = explode('.', $key)[0];
                                @endphp
                                <div @class(['d-flex', 'pt-10' => $loop->index === 1])>
                                    <div class="w-100 text-gray-700 fw-bold fs-4 d-flex">
                                        <div class="ps-11 w-100">
                                            {{ session('locale') == 'ar' ? $name_ar : $name_en }}
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between w-100 pe-5 gap-11">
                                        @foreach (['show', 'print', 'create', 'update', 'delete'] as $action)
                                            @php
                                                $isAvailable = $permission->has($action) ? $permission[$action] : null;
                                            @endphp
                                            <x-form.input-div
                                                class="fv-row form-check form-check-custom form-check-solid {{ $loop->first ? 'ps-6' : ($loop->last ? 'pe-5' : '') }}"
                                                :row="false">
                                                <x-form.input :errors=$errors class="form-check-input" type="checkbox"
                                                    value="{{ $isAvailable }}"
                                                    name="dashboard_permissions[{{ $moduleName . '.' . $name_en . '.' . $action }}]"
                                                    :form_control="false"
                                                    checked="{{ $rolePermissions?->contains($isAvailable) }}"
                                                    disabled="{{ $disabled ? true : ($isAvailable ? false : true) }}" />
                                            </x-form.input-div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="separator my-5 border-2"></div>
                            @endforeach
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
