<?php

namespace Modules\Employee\Classes;

use Modules\Employee\Support\EmployeeAccess;
use Modules\Employee\Support\EmployeePermissions;
use Yajra\DataTables\DataTables;

class EmployeeTable
{
    public static function getEmployeeColumns()
    {
        return [
            ['class' => 'text-start min-w-200px w-250px px-3', 'name' => 'employee_profile'],
            ['class' => 'text-start min-w-110px px-3', 'name' => 'contact_info'],
            ['class' => 'text-start min-w-130px px-3', 'name' => 'employment_period'],
            ['class' => 'text-center min-w-90px px-3', 'name' => 'status'],
        ];
    }

    public static function getEmployeeTable($employees)
    {
        $locale = app()->getLocale();

        return DataTables::of($employees)
            ->addColumn('employee', function ($row) use ($locale) {
                $primaryName = $locale === 'ar'
                    ? (string) ($row->name ?: $row->name_en)
                    : (string) ($row->name_en ?: $row->name);
                $secondaryName = $locale === 'ar'
                    ? (string) ($row->name_en ?? '')
                    : (string) ($row->name ?? '');

                if ($secondaryName !== '' && $secondaryName === $primaryName) {
                    $secondaryName = '';
                }

                $imageUrl = $row->image
                    ? asset('storage/tenant'.tenancy()->tenant->id.'/'.$row->image)
                    : url('/assets/media/avatars/blank.png');

                $secondaryHtml = $secondaryName !== ''
                    ? '<span class="text-muted fs-8 text-truncate">'.e($secondaryName).'</span>'
                    : '';

                return '<div class="d-flex align-items-center gap-3 emp-row-profile">'
                    .'<div class="symbol symbol-40px symbol-circle overflow-hidden flex-shrink-0">'
                    .'<img src="'.e($imageUrl).'" alt="" class="emp-avatar" />'
                    .'</div>'
                    .'<div class="d-flex flex-column justify-content-center min-w-0 gap-1">'
                    .'<span class="text-gray-800 fw-semibold text-truncate lh-sm">'.e($primaryName ?: '—').'</span>'
                    .'<span class="text-muted fs-8 fw-semibold lh-sm">#'.(int) $row->id.'</span>'
                    .$secondaryHtml
                    .'</div>'
                    .'</div>';
            })
            ->addColumn('contact', function ($row) {
                $phone = trim((string) ($row->phone_number ?? ''));
                if ($phone === '') {
                    return '<span class="text-muted fs-8">—</span>';
                }

                return '<span class="emp-contact text-gray-700 fw-medium" dir="ltr">'.e($phone).'</span>';
            })
            ->addColumn('employment_period', function ($row) {
                $start = trim((string) ($row->employment_start_date ?? ''));
                $end = trim((string) ($row->employment_end_date ?? ''));

                if ($start === '' && $end === '') {
                    return '<span class="text-muted fs-8">—</span>';
                }

                $html = '<div class="emp-period lh-sm">';

                if ($start !== '') {
                    $html .= '<div class="fs-8 text-gray-700">'
                        .'<span class="text-muted">'.e(__('employee::fields.employment_from')).'</span> '
                        .e($start)
                        .'</div>';
                }

                if ($end !== '') {
                    $html .= '<div class="fs-8 text-gray-700">'
                        .'<span class="text-muted">'.e(__('employee::fields.employment_to')).'</span> '
                        .e($end)
                        .'</div>';
                } elseif ($start !== '') {
                    $html .= '<span class="badge badge-light-success fs-9 mt-1">'
                        .e(__('employee::fields.employment_ongoing'))
                        .'</span>';
                }

                return $html.'</div>';
            })
            ->addColumn(
                'actions',
                function ($row) {
                    $emsAccess = $row->ems_access;
                    $actions = '<a href="#" class="btn btn-sm btn-icon btn-light btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end" title="'.e(__('employee::fields.actions')).'"><i class="ki-outline ki-dots-horizontal fs-2"></i></a>
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-200px py-4" data-kt-menu="true">';

                    if (! $row->deleted_at) {
                        if (EmployeeAccess::can(EmployeePermissions::EMPLOYEE_UPDATE)) {
                            $actions .= '<div class="menu-item px-3">
                                   <a href="'.url("/employee/{$row->id}/edit").'" class="menu-link px-3">'.__('employee::fields.edit').'</a>
                               </div>';
                        }
                        if (EmployeeAccess::can(EmployeePermissions::POS_ROLE_UPDATE)) {
                            $actions .= '<div class="menu-item px-3">
                                   <a href="#" class="menu-link px-3 edit-pos-permission-button" data-id="'.$row->id.'">'.__('employee::general.edit_pos_permissions').'</a>
                               </div>';
                        }
                    }

                    if ($emsAccess && ! $row->deleted_at && EmployeeAccess::can(EmployeePermissions::DASHBOARD_ROLE_UPDATE)) {
                        $actions .= '<div class="menu-item px-3">
                                        <a href="#" class="menu-link px-3 edit-ems-permission-button" data-id="'.$row->id.'">'.__('employee::general.edit_dashboard_permissions').'</a>
                                    </div>';
                    }
                    if (EmployeeAccess::can(EmployeePermissions::EMPLOYEE_DELETE)) {
                        $actions .= '<div class="menu-item px-3">
                        <a class="menu-link px-3 delete-btn" data-id="'.$row->id.'" data-deleted="'.$row->deleted_at.'" data-name="'.($row->{get_name_by_lang()}).'">'.($row->deleted_at ? __('employee::fields.force_delete') : __('employee::fields.delete')).'</a>
                        </div>';
                    }
                    if ($row->deleted_at && EmployeeAccess::can(EmployeePermissions::EMPLOYEE_UPDATE)) {
                        $actions .=
                            '<div class="menu-item px-3">
                                    <a class="menu-link px-3 restore-btn" data-id="'.$row->id.'">'.__('employee::fields.restore').'</a>
                                </div>';

                    } else {
                        if (EmployeeAccess::can(EmployeePermissions::EMPLOYEE_SHOW)) {
                            $actions .= '<div class="menu-item px-3">
                            <a href="'.url("/employee/show/{$row->id}").'" class="menu-link px-3 show-btn" data-id="'.$row->id.'">'.__('employee::fields.show').'</a>
                            </div>';
                        }
                    }
                    if (EmployeeAccess::can(EmployeePermissions::EMPLOYEE_PRINT)) {
                        $actions .= '<div class="menu-item px-3">
                            <a href="#" class="menu-link px-3 print-btn" data-id="'.$row->id.'">'.__('employee::fields.print').'</a>
                            </div>';
                    }
                    $actions .= '</div>';

                    return $actions;
                }
            )
            ->editColumn('pos_is_active', function ($employee) {
                return $employee->pos_is_active
                    ? '<span class="badge badge-light-success fs-8">'.__('employee::fields.active').'</span>'
                    : '<span class="badge badge-light-danger fs-8">'.__('employee::fields.inActive').'</span>';
            })
            ->rawColumns(['actions', 'pos_is_active', 'employee', 'contact', 'employment_period'])
            ->make(true);
    }
}
