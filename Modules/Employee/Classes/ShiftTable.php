<?php


namespace Modules\Employee\Classes;
use Carbon\Carbon;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Schedule;
use Modules\Employee\Services\ShiftFilters;
use Modules\Employee\Services\ShiftService;
use Modules\Establishment\Models\Establishment;
use Yajra\DataTables\DataTables;

class ShiftTable
{
    protected $lang;
    protected $establishment_id;
    public function __construct(protected $table_type, protected $request)
    {
        $this->lang = session()->get('locale');
        $this->establishment_id = $this->request->get('filter_establishment') ?? Establishment::first()->id;
    }

    public static function getShiftColumns()
    {
        return [
            ["class" => "text-start min-w-75px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "id"],
            ["class" => "text-start min-w-150px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "employee"],
            ["class" => "text-start min-w-75px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "total_hours"],
            ["class" => "text-start min-w-75px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "total_wages"],
            ["class" => "text-start min-w-125px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "role"],
            ["class" => "text-start min-w-150px px-3 py-1 align-middle text-gray-800 fs-6", "name" => "establishment"],
        ];
    }

    public static function getShiftFooters()
    {
        $generateRow = function (string $text) {
            $row = [
                ['class' => 'border text-end px-5', 'colspan' => '3', 'text' => $text],
            ];
            for ($i = 0; $i < 11; $i++) {
                $row[] = ['class' => '', 'colspan' => '1', 'text' => ''];
            }
            return $row;
        };
        return [
            ['class' => 'd-none table-wages-footer total-wage', 'th' => $generateRow(__('employee::fields.total_wages'))],
            ['class' => 'd-none table-wages-footer forecasted-sales', 'th' => $generateRow(__('employee::fields.forecasted_sales'))],
            ['class' => 'd-none table-wages-footer mean-sales', 'th' => $generateRow(__('employee::fields.mean_sales'))],
            ['class' => 'd-none table-wages-footer forecasted-labor-cost', 'th' => $generateRow(__('employee::fields.forecasted_labor_cost'))],
            ['class' => 'd-none table-wages-footer mean-labor-cost', 'th' => $generateRow(__('employee::fields.mean_labor_cost'))],
            ['class' => 'd-none table-breaks-footer', 'th' => $generateRow(__('employee::fields.breaks_total'))],
            ['class' => 'd-none table-hours-footer', 'th' => $generateRow(__('employee::fields.total_hours'))],
        ];
    }

    public function getShiftTable()
    {
        $start_date = Carbon::createFromFormat('Y-m-d', $this->request->input('start_date'));
        $end_date = Carbon::createFromFormat('Y-m-d', $this->request->input('end_date'));
        $schedules_ids = Schedule::where('start_date', '<=', $start_date->format('Y-m-d'))->where('end_date', '>=', $end_date->format('Y-m-d'))->pluck('id')->toArray();
        $employees = Employee::with(['timecards', 'shifts', 'allRoles', 'wageEstablishments', 'wages'])->whereHas('wageEstablishments', fn ($query) => $query->whereIn('establishment_establishments.id', [$this->establishment_id]))->active();

        $filters = new ShiftFilters(['filter_role', 'filter_establishment', 'filter_employee_status']);
        $filters->applyFilters($this->request, $employees);

        $shiftService = new ShiftService($this->table_type, $this->request, $this->establishment_id);
        $employeeData = $shiftService->getEmployeeData($employees->get(['id', 'name', 'name_en']), $start_date, $end_date, $schedules_ids);

        return DataTables::of($employeeData)->rawColumns($employeeData->first() ? array_keys($employeeData->first()) : [])->make(true);
    }
}